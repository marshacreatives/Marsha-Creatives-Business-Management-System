<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\MonthFilter;
use App\Models\Activity;
use App\Models\CompanyBalance;
use App\Models\FundRequest;
use App\Models\ProjectJob;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use MonthFilter;

    public function index(Request $request)
    {
        [$start, $end] = $this->getMonthRange($request);
        $selectedMonth = $this->getSelectedMonth($request);
        $availableMonths = $this->getAvailableMonths();

        $balance = CompanyBalance::getBalance();
        $totalJobs = ProjectJob::whereBetween('created_at', [$start, $end])->count();
        $completedJobs = ProjectJob::whereBetween('created_at', [$start, $end])->where('status', 'completed')->count();
        $inProgressJobs = ProjectJob::whereBetween('created_at', [$start, $end])->where('status', 'in_progress')->count();
        $cancelledJobs = ProjectJob::whereBetween('created_at', [$start, $end])->where('status', 'cancelled')->count();
        $totalEmployees = User::where('role', 'employee')->count();
        $totalExpenses = (float) ProjectJob::whereBetween('created_at', [$start, $end])->sum('expense');
        $totalRevenue = (float) ProjectJob::whereBetween('created_at', [$start, $end])->sum('cost');
        $totalProfit = $totalRevenue - $totalExpenses;

        $recentActivities = Activity::with('user')
            ->latest()
            ->take(15)
            ->get();

        $pendingFundRequests = FundRequest::where('status', 'pending')
            ->with('user')
            ->latest()
            ->get();

        return view('admin.dashboard', compact(
            'balance',
            'totalJobs',
            'completedJobs',
            'inProgressJobs',
            'cancelledJobs',
            'totalEmployees',
            'totalExpenses',
            'totalRevenue',
            'totalProfit',
            'recentActivities',
            'pendingFundRequests',
            'selectedMonth',
            'availableMonths'
        ));
    }
}
