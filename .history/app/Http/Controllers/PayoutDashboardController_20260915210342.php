<?php

namespace App\Http\Controllers;

use App\Models\Geography;
use App\Models\PayoutRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'program' => ['nullable', 'in:AICS,ECT'],
            'disaster_type' => ['nullable', 'string', 'max:120'],
            'province_id' => ['nullable', 'integer'],
            'municipality_id' => ['nullable', 'integer'],
            'barangay_id' => ['nullable', 'integer'],
            'payout_site' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = PayoutRecord::query()->with(['servedList']);
        foreach (['program', 'disaster_type', 'province_id', 'municipality_id', 'barangay_id', 'payout_site'] as $field) {
            if (array_key_exists($field, $filters) && $filters[$field] !== null && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }
        if (!empty($filters['from'])) $query->whereDate('served_date', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('served_date', '<=', $filters['to']);

        $records = $query->get();
        $level = $filters['barangay_id'] ? 'detail' : ($filters['municipality_id'] ? 'barangay' : ($filters['province_id'] ? 'municipality' : 'province'));
        $groupField = match ($level) {
            'municipality' => 'municipality_id',
            'barangay' => 'barangay_id',
            default => 'province_id',
        };
        $groups = $records->groupBy($groupField)->map(function ($rows, $id) {
            $geography = Geography::find($id);
            $target = $rows->count();
            $paid = $rows->where('is_paid', true)->count();
            return [
                'id' => $id,
                'name' => $geography?->name ?? 'Unknown geography',
                'psgc_code' => $geography?->psgc_code,
                'target' => $target,
                'paid' => $paid,
                'remaining' => max(0, $target - $paid),
                'progress' => $target ? round(($paid / $target) * 100, 2) : 0,
                'amount_disbursed' => (float) $rows->sum('disbursed_amount'),
            ];
        })->values();

        return response()->json([
            'filters' => $filters,
            'level' => $level,
            'summary' => [
                'target' => $records->count(),
                'paid' => $records->where('is_paid', true)->count(),
                'remaining' => max(0, $records->count() - $records->where('is_paid', true)->count()),
                'amount_disbursed' => (float) $records->sum('disbursed_amount'),
                'progress' => $records->count() ? round(($records->where('is_paid', true)->count() / $records->count()) * 100, 2) : 0,
            ],
            'rows' => $groups,
            'payout_sites' => $records->pluck('payout_site')->filter()->unique()->values(),
            'disaster_types' => PayoutRecord::query()->when($filters['program'] ?? null, fn ($q, $value) => $q->where('program', $value))->distinct()->orderBy('disaster_type')->pluck('disaster_type'),
        ]);
    }
}
