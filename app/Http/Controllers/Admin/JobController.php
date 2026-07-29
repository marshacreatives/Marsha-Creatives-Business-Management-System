<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CompanyBalance;
use App\Models\ProjectJob;
use App\Models\User;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index()
    {
        $jobs = ProjectJob::with(['assignee', 'creator'])
            ->latest()
            ->get();

        return view('admin.jobs.index', compact('jobs'));
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
        ]);

        CompanyBalance::adjustBalance(-$expense);

        $assignee = User::find($request->assigned_to);
        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'job_created',
            'description' => "Created job '{$request->project_name}' assigned to {$assignee->name} (Expense: KSh " . number_format($expense, 2) . ")",
            'subject_id' => $job->id,
            'subject_type' => ProjectJob::class,
        ]);

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

        $job->update([
            'project_name' => $request->project_name,
            'expense' => $newExpense,
            'cost' => $request->cost,
            'status' => $newStatus,
            'assigned_to' => $request->assigned_to,
        ]);

        $this->handleBalanceAdjustment($job, $oldStatus, $newStatus, $oldExpense, $newExpense);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'job_updated',
            'description' => "Updated job '{$job->project_name}'",
            'subject_id' => $job->id,
            'subject_type' => ProjectJob::class,
        ]);

        return redirect()->route('admin.jobs.index')->with('success', 'Job updated successfully.');
    }

    public function destroy(ProjectJob $job)
    {
        $expense = (float) $job->expense;

        CompanyBalance::adjustBalance($expense);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'job_deleted',
            'description' => "Deleted job '{$job->project_name}'",
        ]);

        $job->delete();

        return redirect()->route('admin.jobs.index')->with('success', 'Job deleted successfully.');
    }

    private function handleBalanceAdjustment(ProjectJob $job, string $oldStatus, string $newStatus, float $oldExpense, float $newExpense): void
    {
        if ($oldExpense !== $newExpense) {
            CompanyBalance::adjustBalance($oldExpense - $newExpense);
        }
    }
}
