<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\MonthFilter;
use App\Models\CompanyBalance;
use App\Models\ProjectJob;
use App\Models\User;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    use MonthFilter;

    public function index(Request $request)
    {
        [$start, $end] = $this->getMonthRange($request);
        $selectedMonth = $this->getSelectedMonth($request);
        $availableMonths = $this->getAvailableMonths();

        $balance = CompanyBalance::getBalance();

        $employees = User::where('role', 'employee')
            ->withCount([
                'assignedJobs as total_jobs' => function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end]);
                },
                'assignedJobs as completed_jobs' => function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end])->where('status', 'completed');
                },
            ])
            ->withSum('assignedJobs as total_expenses', 'expense')
            ->withSum('assignedJobs as total_revenue', 'cost')
            ->get()
            ->map(function ($employee) use ($start, $end) {
                $employee->total_profit = (float) $employee->total_revenue - (float) $employee->total_expenses;

                $employee->total_expenses = (float) $employee->assignedJobs
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<=', $end)
                    ->sum('expense');

                $employee->total_revenue = (float) $employee->assignedJobs
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<=', $end)
                    ->sum('cost');

                $employee->total_profit = $employee->total_revenue - $employee->total_expenses;

                return $employee;
            });

        $businessTotals = [
            'total_jobs' => ProjectJob::whereBetween('created_at', [$start, $end])->count(),
            'completed_jobs' => ProjectJob::whereBetween('created_at', [$start, $end])->where('status', 'completed')->count(),
            'in_progress_jobs' => ProjectJob::whereBetween('created_at', [$start, $end])->where('status', 'in_progress')->count(),
            'cancelled_jobs' => ProjectJob::whereBetween('created_at', [$start, $end])->where('status', 'cancelled')->count(),
            'total_expenses' => (float) ProjectJob::whereBetween('created_at', [$start, $end])->sum('expense'),
            'total_revenue' => (float) ProjectJob::whereBetween('created_at', [$start, $end])->sum('cost'),
        ];
        $businessTotals['total_profit'] = $businessTotals['total_revenue'] - $businessTotals['total_expenses'];

        return view('admin.financials', compact('balance', 'employees', 'businessTotals', 'selectedMonth', 'availableMonths'));
    }
}
