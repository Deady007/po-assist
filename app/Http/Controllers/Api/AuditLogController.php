<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($type = $request->query('entity_type')) {
            $query->where('entity_type', $type);
        }
        if ($id = $request->query('entity_id')) {
            $query->where('entity_id', $id);
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
