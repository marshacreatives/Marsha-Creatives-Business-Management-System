<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\MonthFilter;
use App\Models\Activity;
use App\Models\BalanceLog;
use App\Models\CompanyBalance;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    use MonthFilter;

    public function index(Request $request)
    {
        $balance = CompanyBalance::getBalance();
        [$start, $end] = $this->getMonthRange($request);
        $selectedMonth = $this->getSelectedMonth($request);
        $availableMonths = $this->getAvailableMonths();

        $balanceLogs = BalanceLog::with('user')
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $totalTopups = (float) BalanceLog::where('type', 'add')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $totalSets = BalanceLog::where('type', 'set')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return view('admin.balance', compact(
            'balance',
            'balanceLogs',
            'totalTopups',
            'totalSets',
            'selectedMonth',
            'availableMonths'
        ));
    }

    public function set(Request $request)
    {
        $request->validate([
            'balance' => 'required|numeric|min:0',
        ]);

        $previousBalance = CompanyBalance::getBalance();
        $newBalance = (float) $request->balance;

        CompanyBalance::updateBalance($newBalance);

        BalanceLog::create([
            'user_id' => auth()->id(),
            'type' => 'set',
            'amount' => $newBalance,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
        ]);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'balance_updated',
            'description' => 'Company balance set to KSh ' . number_format($newBalance, 2),
        ]);

        return redirect()->route('admin.balance')->with('success', 'Balance updated successfully.');
    }

    public function add(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $amount = (float) $request->amount;
        $previousBalance = CompanyBalance::getBalance();

        CompanyBalance::adjustBalance($amount);

        $newBalance = CompanyBalance::getBalance();

        BalanceLog::create([
            'user_id' => auth()->id(),
            'type' => 'add',
            'amount' => $amount,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
        ]);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'balance_updated',
            'description' => 'Added KSh ' . number_format($amount, 2) . ' to company balance',
        ]);

        return redirect()->route('admin.balance')->with('success', 'KSh ' . number_format($amount, 2) . ' added to balance successfully.');
    }
}
