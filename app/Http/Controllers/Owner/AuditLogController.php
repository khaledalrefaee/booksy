<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\OwnerAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filterOwnerId = $request->input('owner_id', '');
        $filterAction  = $request->input('action', '');
        $filterFrom    = $request->input('from', '');
        $filterTo      = $request->input('to', '');

        $query = OwnerAuditLog::query()->with('owner')->latest('id');

        if ($filterOwnerId !== '') {
            $query->where('owner_id', (int) $filterOwnerId);
        }

        if ($filterAction !== '') {
            $query->where('action', $filterAction);
        }

        if ($filterFrom !== '') {
            $query->whereDate('created_at', '>=', $filterFrom);
        }

        if ($filterTo !== '') {
            $query->whereDate('created_at', '<=', $filterTo);
        }

        $logs    = $query->paginate(25)->withQueryString();
        $owners  = Owner::query()->orderBy('name')->get(['id', 'name']);
        $actions = OwnerAuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('owner.audit-log.index', compact(
            'logs', 'owners', 'actions',
            'filterOwnerId', 'filterAction', 'filterFrom', 'filterTo',
        ));
    }
}
