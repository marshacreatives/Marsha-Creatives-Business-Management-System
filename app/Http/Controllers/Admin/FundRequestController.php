<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\FundRequest;
use Illuminate\Http\Request;

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
            'description' => "Approved fund request from {$fundRequest->user->name} for KSh " . number_format($fundRequest->amount, 2),
        ]);

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
            'description' => "Dismissed fund request from {$fundRequest->user->name} for KSh " . number_format($fundRequest->amount, 2),
        ]);

        return back()->with('success', 'Fund request dismissed.');
    }
}
