<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Traits\MonthFilter;
use App\Models\Activity;
use App\Models\CompanyBalance;
use App\Models\FundRequest;
use App\Models\ProjectJob;
use Illuminate\Http\Request;

class JobController extends Controller
{
    use MonthFilter;

    public function index(Request $request)
    {
        $user = auth()->user();
        [$start, $end] = $this->getMonthRange($request);
        $selectedMonth = $this->getSelectedMonth($request);
        $availableMonths = $this->getAvailableMonths();

        $jobs = ProjectJob::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('creator')
            ->latest()
            ->get();

        return view('employee.jobs.index', compact('jobs', 'selectedMonth', 'availableMonths'));
    }

    public function create()
    {
        $balance = CompanyBalance::getBalance();
        return view('employee.jobs.create', compact('balance'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'project_name' => 'required|string|max:255',
            'expense' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:in_progress,completed,cancelled',
        ]);

        $expense = (float) $request->expense;
        $balance = CompanyBalance::getBalance();

        if ($expense > $balance) {
            FundRequest::create([
                'user_id' => $user->id,
                'amount' => $expense - $balance,
                'message' => "Insufficient funds for job '{$request->project_name}'. Expense: KSh " . number_format($expense, 2) . ", Available: KSh " . number_format($balance, 2),
            ]);

            Activity::create([
                'user_id' => $user->id,
                'type' => 'fund_requested',
                'description' => "{$user->name} requested KSh " . number_format($expense - $balance, 2) . " additional funds for job '{$request->project_name}'",
            ]);

            return redirect()->route('employee.jobs.create')
                ->with('error', "Insufficient funds! Your expense of KSh " . number_format($expense, 2) . " exceeds the available balance of KSh " . number_format($balance, 2) . ". A fund request has been sent to the admin.");
        }

        $job = ProjectJob::create([
            'project_name' => $request->project_name,
            'expense' => $expense,
            'cost' => $request->cost,
            'status' => $request->status,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);

        CompanyBalance::adjustBalance(-$expense);

        Activity::create([
            'user_id' => $user->id,
            'type' => 'job_created',
            'description' => "Logged new job '{$request->project_name}' (Expense: KSh " . number_format($expense, 2) . ")",
            'subject_id' => $job->id,
            'subject_type' => ProjectJob::class,
        ]);

        return redirect()->route('employee.jobs.index')->with('success', 'Job logged successfully. KSh ' . number_format($expense, 2) . ' deducted from company balance.');
    }

    public function updateStatus(Request $request, ProjectJob $job)
    {
        $user = auth()->user();

        $request->validate([
            'status' => 'required|in:in_progress,completed,cancelled',
        ]);

        $oldStatus = $job->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return redirect()->route('employee.jobs.index')->with('success', 'Job status is already ' . str_replace('_', ' ', $newStatus) . '.');
        }

        $activityDesc = "Updated '{$job->project_name}' status to " . str_replace('_', ' ', $newStatus);

        $job->update(['status' => $newStatus]);

        Activity::create([
            'user_id' => $user->id,
            'type' => 'job_status_updated',
            'description' => $activityDesc,
            'subject_id' => $job->id,
            'subject_type' => ProjectJob::class,
        ]);

        return redirect()->route('employee.jobs.index', ['month' => request('month')])->with('success', 'Job status updated.');
    }

    public function history(Request $request)
    {
        $user = auth()->user();
        [$start, $end] = $this->getMonthRange($request);
        $selectedMonth = $this->getSelectedMonth($request);
        $availableMonths = $this->getAvailableMonths();

        $jobs = ProjectJob::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('creator')
            ->latest()
            ->get();

        $completedJobs = $jobs->where('status', 'completed');
        $totalEarnings = $completedJobs->sum(fn ($job) => (float) $job->cost);
        $totalExpenses = $completedJobs->sum(fn ($job) => (float) $job->expense);
        $totalProfit = $totalEarnings - $totalExpenses;

        return view('employee.jobs.history', compact('jobs', 'completedJobs', 'totalEarnings', 'totalExpenses', 'totalProfit', 'selectedMonth', 'availableMonths'));
    }
}
