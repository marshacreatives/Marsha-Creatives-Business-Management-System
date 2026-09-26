<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\FundRequest;
use App\Services\NotificationService;

class FundRequestController extends Controller
{
    public function approve(FundRequest $fundRequest)
    {
        if ($fundRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $fundRequest->update(['status' => 'approved']);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'fund_request_approved',
            'description' => "Approved fund request from {$fundRequest->user->name} for KSh ".number_format($fundRequest->amount, 2),
        ]);

        NotificationService::notifyUser(
            $fundRequest->user,
            'Fund request approved',
            'KSh '.number_format((float) $fundRequest->amount, 2).' was approved. You can log the job now.',
            route('employee.jobs.create'),
            'fund',
        );

        return back()->with('success', 'Fund request approved. Please update the company balance.');
    }

    public function dismiss(FundRequest $fundRequest)
    {
        if ($fundRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $fundRequest->update(['status' => 'dismissed']);

        Activity::create([
            'user_id' => auth()->id(),
            'type' => 'fund_request_dismissed',
            'description' => "Dismissed fund request from {$fundRequest->user->name} for KSh ".number_format($fundRequest->amount, 2),
        ]);

        NotificationService::notifyUser(
            $fundRequest->user,
            'Fund request declined',
            'Your request for KSh '.number_format((float) $fundRequest->amount, 2).' was declined. Please speak to the admin.',
            route('employee.jobs.index'),
            'fund',
        );

        return back()->with('success', 'Fund request dismissed.');
    }
}
