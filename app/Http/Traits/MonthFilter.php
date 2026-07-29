<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Carbon\Carbon;

trait MonthFilter
{
    protected const SYSTEM_START_MONTH = '2026-07';

    protected function getMonthRange(Request $request): array
    {
        $month = $request->input('month', $this->getDefaultMonth());
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        return [$start, $end];
    }

    protected function getAvailableMonths(): array
    {
        $months = [];
        $now = Carbon::now();
        $start = Carbon::parse(self::SYSTEM_START_MONTH)->startOfMonth();

        $current = $now->copy()->startOfMonth();

        while ($current->gte($start)) {
            array_unshift($months, [
                'value' => $current->format('Y-m'),
                'label' => $current->format('F Y'),
            ]);
            $current->subMonth();
        }

        return $months;
    }

    protected function getSelectedMonth(Request $request): string
    {
        return $request->input('month', $this->getDefaultMonth());
    }

    protected function getDefaultMonth(): string
    {
        $now = Carbon::now();
        $start = Carbon::parse(self::SYSTEM_START_MONTH);

        if ($now->lt($start)) {
            return $start->format('Y-m');
        }

        return $now->format('Y-m');
    }
}
