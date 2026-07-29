<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Traits\MonthFilter;
use App\Models\Activity;
use App\Models\CompanyBalance;
use App\Models\ProjectJob;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use MonthFilter;

    public function index(Request $request)
    {
        $user = auth()->user();
        [$start, $end] = $this->getMonthRange($request);
        $selectedMonth = $this->getSelectedMonth($request);
        $availableMonths = $this->getAvailableMonths();

        $balance = CompanyBalance::getBalance();

        $assignedJobs = ProjectJob::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('creator')
            ->latest()
            ->get();

        $stats = [
            'total' => $assignedJobs->count(),
            'in_progress' => $assignedJobs->where('status', 'in_progress')->count(),
            'completed' => $assignedJobs->where('status', 'completed')->count(),
            'cancelled' => $assignedJobs->where('status', 'cancelled')->count(),
        ];

        $totalEarnings = $assignedJobs->where('status', 'completed')
            ->sum(fn ($job) => (float) $job->cost);

        $totalExpenses = $assignedJobs->where('status', 'completed')
            ->sum(fn ($job) => (float) $job->expense);

        $recentActivities = Activity::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return view('employee.dashboard', compact(
            'balance',
            'assignedJobs',
            'stats',
            'totalEarnings',
            'totalExpenses',
            'recentActivities',
            'selectedMonth',
            'availableMonths'
        ));
    }
}
