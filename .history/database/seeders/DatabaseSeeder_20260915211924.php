<?php

namespace Database\Seeders;

use App\Models\Geography;
use App\Models\PayoutRecord;
use App\Models\ServedList;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $region = Geography::create(['psgc_code' => '110000000', 'name' => 'Region XI', 'level' => 'region']);
        $provinces = [
            ['code' => '112500000', 'name' => 'Davao de Oro'],
            ['code' => '112300000', 'name' => 'Davao del Norte'],
            ['code' => '112400000', 'name' => 'Davao del Sur'],
            ['code' => '118300000', 'name' => 'Davao Occidental'],
            ['code' => '112600000', 'name' => 'Davao Oriental'],
        ];

        $servedList = ServedList::create([
            'file_name' => 'seeded-aics-served-list.csv',
            'program' => 'AICS',
            'disaster_type' => 'Typhoon',
            'imported_by' => $user->id,
            'imported_at' => now(),
            'record_count' => 0,
        ]);

        foreach ($provinces as $index => $provinceData) {
            $province = Geography::create([
                'psgc_code' => $provinceData['code'] . $index,
                'name' => $provinceData['name'],
                'level' => 'province',
                'parent_id' => $region->id,
            ]);
            $municipality = Geography::create([
                'psgc_code' => $provinceData['code'] . '001',
                'name' => $province->name . ' Municipality',
                'level' => 'municipality',
                'parent_id' => $province->id,
            ]);
            $barangay = Geography::create([
                'psgc_code' => $provinceData['code'] . '001001',
                'name' => 'Barangay Central',
                'level' => 'barangay',
                'parent_id' => $municipality->id,
            ]);

            for ($record = 1; $record <= ($index + 2); $record++) {
                PayoutRecord::create([
                    'served_list_id' => $servedList->id,
                    'program' => 'AICS',
                    'disaster_type' => 'Typhoon',
                    'region_id' => $region->id,
                    'province_id' => $province->id,
                    'municipality_id' => $municipality->id,
                    'barangay_id' => $barangay->id,
                    'payout_site' => 'Site ' . (($index % 2) + 1),
                    'beneficiary_reference' => "SEED-{$index}-{$record}",
                    'target_amount' => 10000,
                    'disbursed_amount' => $record % 2 ? 10000 : 0,
                    'is_paid' => $record % 2 === 1,
                    'served_date' => now()->subDays($record),
                ]);
            }
        }

        $servedList->update(['record_count' => PayoutRecord::where('served_list_id', $servedList->id)->count()]);

        $ectList = ServedList::create([
            'file_name' => 'seeded-ect-served-list.csv',
            'program' => 'ECT',
            'disaster_type' => 'Earthquake',
            'imported_by' => $user->id,
            'imported_at' => now(),
            'record_count' => 0,
        ]);

        foreach (Geography::where('level', 'province')->where('parent_id', $region->id)->get() as $index => $ectProvince) {
            $ectMunicipality = Geography::where('level', 'municipality')->where('parent_id', $ectProvince->id)->firstOrFail();
            $ectBarangay = Geography::where('level', 'barangay')->where('parent_id', $ectMunicipality->id)->firstOrFail();

            foreach (range(1, 3) as $record) {
                PayoutRecord::create([
                    'served_list_id' => $ectList->id,
                    'program' => 'ECT',
                    'disaster_type' => 'Earthquake',
                    'region_id' => $region->id,
                    'province_id' => $ectProvince->id,
                    'municipality_id' => $ectMunicipality->id,
                    'barangay_id' => $ectBarangay->id,
                    'payout_site' => 'ECT Site ' . (($index % 2) + 1),
                    'beneficiary_reference' => "ECT-SEED-{$index}-{$record}",
                    'target_amount' => 15000,
                    'disbursed_amount' => $record === 1 ? 15000 : 0,
                    'is_paid' => $record === 1,
                    'served_date' => now()->subDays($record),
                ]);
            }
        }

        $ectList->update(['record_count' => PayoutRecord::where('served_list_id', $ectList->id)->count()]);
    }
}
