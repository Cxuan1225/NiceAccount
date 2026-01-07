<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        // Basic filters
        $q         = trim((string) $request->query('q', ''));
        $action    = $request->query('action');     // e.g. INSERT/UPDATE/DELETE
        $tableName = $request->query('table_name'); // e.g. customers

        // (Optional now, useful later) date range
        $dateFrom = $request->query('date_from'); // YYYY-MM-DD
        $dateTo   = $request->query('date_to');   // YYYY-MM-DD

        $query = AuditTrail::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($sub) use ($q) {
                    $sub->where('user_label', 'like', "%{$q}%")
                        ->orWhere('screen_name', 'like', "%{$q}%")
                        ->orWhere('table_name', 'like', "%{$q}%")
                        ->orWhere('table_id', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%");
                });
            })
            ->when($action, fn ($builder) => $builder->where('action', $action))
            ->when($tableName, fn ($builder) => $builder->where('table_name', $tableName))
            ->when($dateFrom, fn ($builder) => $builder->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($builder) => $builder->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id');

        // For dropdown filters (table list + action list)
        // Keep it simple + fast; can cache later if needed.
        $availableTables = AuditTrail::query()
            ->select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name');

        $availableActions = AuditTrail::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $auditTrails = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function ($row) {
                return [
                    'id'         => $row->id,
                    'user_id'    => $row->user_id,
                    'user_label' => $row->user_label,
                    'screen_name'=> $row->screen_name,
                    'table_name' => $row->table_name,
                    'table_id'   => $row->table_id,
                    'action'     => $row->action,
                    'ip_address' => $row->ip_address,
                    'created_at' => $row->created_at?->toDateTimeString(),

                    // For details modal later
                    'old_data'   => $row->old_data,
                    'new_data'   => $row->new_data,
                    'user_agent' => $row->user_agent,
                ];
            });

        return Inertia::render('AuditTrails/Index', [
            'auditTrails' => $auditTrails,
            'filters' => [
                'q'          => $q,
                'action'     => $action,
                'table_name' => $tableName,
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
            ],
            'options' => [
                'tables'  => $availableTables,
                'actions' => $availableActions,
            ],
        ]);
    }
}
