<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CompanyBalance;
use App\Models\ProjectJob;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectJob::with(['assignee', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('assigned_to', $request->employee_id);
        }

        $jobs = $query->latest()->get();
        $employees = User::where('role', 'employee')->orderBy('name')->get();
        $statuses = ['in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

        return view('admin.jobs.index', compact('jobs', 'employees', 'statuses'));
    }

    public function create()
    {
        $employees = User::where('role', 'employee')->get();
        $balance = CompanyBalance::getBalance();

        return view('admin.jobs.create', compact('employees', 'balance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'expense' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:in_progress,completed,cancelled',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $expense = (float) $request->expense;

        $job = ProjectJob::create([
            'project_name' => $request->project_name,
            'expense' => $expense,
            'cost' => $request->cost,
            'status' => $request->status,
            'assigned_to' => $request->assigned_to,
            'created_by' => auth()->id(),
            'job_date' => Carbon::now(),
        ]);

        CompanyBalance::adjustBalance(-$expense);

        $assignee = User::find($request->assigned_to);
        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'job_created',
            'description' => "Created job '{$request->project_name}' assigned to {$assignee->name} (Expense: KSh ".number_format($expense, 2).')',
            'subject_id' => $job->id,
            'subject_type' => ProjectJob::class,
        ]);

        NotificationService::notifyUser(
            $assignee,
            'New job assigned to you',
            "'{$job->project_name}' was assigned to you by ".auth()->user()->name.'.',
            route('employee.jobs.index'),
            'job',
        );

        return redirect()->route('admin.jobs.index')->with('success', 'Job created successfully.');
    }

    public function edit(ProjectJob $job)
    {
        $employees = User::where('role', 'employee')->get();
        $balance = CompanyBalance::getBalance();

        return view('admin.jobs.edit', compact('job', 'employees', 'balance'));
    }

    public function update(Request $request, ProjectJob $job)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'expense' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:in_progress,completed,cancelled',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $oldStatus = $job->status;
        $oldExpense = (float) $job->expense;
        $newStatus = $request->status;
        $newExpense = (float) $request->expense;
        $previousAssigneeId = (int) $job->assigned_to;

        $job->update([
            'project_name' => $request->project_name,
            'expense' => $newExpense,
            'cost' => $request->cost,
            'status' => $newStatus,
            'assigned_to' => $request->assigned_to,
            'job_date' => $job->job_date ?? Carbon::now(),
        ]);

        $this->handleBalanceAdjustment($job, $oldStatus, $newStatus, $oldExpense, $newExpense);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'job_updated',
            'description' => "Updated job '{$job->project_name}'",
            'subject_id' => $job->id,
            'subject_type' => ProjectJob::class,
        ]);

        $this->notifyAssigneeOfUpdate($job, $previousAssigneeId);

        return redirect()->route('admin.jobs.index')->with('success', 'Job updated successfully.');
    }

    /**
     * Tell the employee about an edit to their job. When the job changed hands,
     * both the new owner and the previous one are told.
     */
    private function notifyAssigneeOfUpdate(ProjectJob $job, int $previousAssigneeId): void
    {
        $newAssigneeId = (int) $job->assigned_to;

        if ($newAssigneeId !== $previousAssigneeId) {
            if ($previous = User::find($previousAssigneeId)) {
                NotificationService::notifyUser(
                    $previous,
                    'Job reassigned',
                    "'{$job->project_name}' is no longer assigned to you.",
                    route('employee.jobs.index'),
                    'job',
                );
            }
        }

        if (! $assignee = User::find($newAssigneeId)) {
            return;
        }

        NotificationService::notifyUser(
            $assignee,
            $newAssigneeId === $previousAssigneeId ? 'Your job was updated' : 'Job assigned to you',
            "'{$job->project_name}' is now ".str_replace('_', ' ', (string) $job->status).' (KSh '.number_format((float) $job->expense, 2).' expense).',
            route('employee.jobs.index'),
            'job',
        );
    }

    public function destroy(ProjectJob $job)
    {
        $expense = (float) $job->expense;
        $assignee = $job->assignee;
        $projectName = $job->project_name;

        CompanyBalance::adjustBalance($expense);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'job_deleted',
            'description' => "Deleted job '{$projectName}'",
        ]);

        $job->delete();

        if ($assignee) {
            NotificationService::notifyUser(
                $assignee,
                'Job removed',
                "'{$projectName}' was deleted from your jobs.",
                route('employee.jobs.index'),
                'job',
            );
        }

        return redirect()->route('admin.jobs.index')->with('success', 'Job deleted successfully.');
    }

    private function handleBalanceAdjustment(ProjectJob $job, string $oldStatus, string $newStatus, float $oldExpense, float $newExpense): void
    {
        if ($oldExpense !== $newExpense) {
            CompanyBalance::adjustBalance($oldExpense - $newExpense);
        }
    }
}
