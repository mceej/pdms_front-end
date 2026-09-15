<?php

namespace App\Http\Controllers;

use App\Models\Geography;
use App\Models\PayoutRecord;
use App\Models\ServedList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServedListController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'program' => ['required', 'in:AICS,ECT'],
            'disaster_type' => ['required', 'string', 'max:120'],
        ]);

        $handle = fopen($data['file']->getRealPath(), 'rb');
        $headers = array_map(fn ($header) => strtolower(trim($header)), fgetcsv($handle) ?: []);
        $required = ['province_psgc', 'municipality_psgc', 'barangay_psgc', 'payout_site', 'is_paid', 'disbursed_amount', 'served_date'];
        $missing = array_values(array_diff($required, $headers));
        if ($missing) {
            return response()->json(['message' => 'Invalid CSV columns.', 'missing' => $missing], 422);
        }

        $records = [];
        while (($row = fgetcsv($handle)) !== false) {
            $records[] = array_combine($headers, $row);
        }
        fclose($handle);

        return DB::transaction(function () use ($data, $records, $request) {
            $servedList = ServedList::create([
                'file_name' => $data['file']->getClientOriginalName(),
                'program' => $data['program'],
                'disaster_type' => $data['disaster_type'],
                'imported_by' => $request->user()?->id,
                'imported_at' => now(),
                'record_count' => count($records),
            ]);

            foreach ($records as $row) {
                $province = Geography::where('psgc_code', $row['province_psgc'])->where('level', 'province')->firstOrFail();
                $municipality = Geography::where('psgc_code', $row['municipality_psgc'])->where('level', 'municipality')->where('parent_id', $province->id)->firstOrFail();
                $barangay = Geography::where('psgc_code', $row['barangay_psgc'])->where('level', 'barangay')->where('parent_id', $municipality->id)->firstOrFail();

                PayoutRecord::create([
                    'served_list_id' => $servedList->id,
                    'program' => $data['program'],
                    'disaster_type' => $data['disaster_type'],
                    'region_id' => $province->parent_id,
                    'province_id' => $province->id,
                    'municipality_id' => $municipality->id,
                    'barangay_id' => $barangay->id,
                    'payout_site' => $row['payout_site'] ?: null,
                    'beneficiary_reference' => $row['beneficiary_reference'] ?? null,
                    'target_amount' => (float) ($row['target_amount'] ?? 0),
                    'disbursed_amount' => (float) ($row['disbursed_amount'] ?? 0),
                    'is_paid' => filter_var($row['is_paid'], FILTER_VALIDATE_BOOLEAN),
                    'served_date' => $row['served_date'] ?: null,
                ]);
            }

            return response()->json($servedList->fresh(), 201);
        });
    }
}
