<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GnDivision;
use App\Models\District;
use Illuminate\Support\Facades\DB;

class GnDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/gn_seed_data.json');
        if (!file_exists($path)) {
            $this->command->error("Could not find seed data at $path");
            return;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (!$data) {
            $this->command->error("Failed to decode JSON data");
            return;
        }

        // Preload districts for faster lookup
        $districts = District::all()->keyBy('name');
        
        $this->command->info("Starting GN Divisions seeding (" . count($data) . " records)...");
        
        // Clear existing to avoid duplicates if re-running
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GnDivision::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $insertData = [];
        $now = now();

        $bar = $this->command->getOutput()->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $gn) {
            $districtName = $gn['district_name'];
            $districtId = null;

            if (isset($districts[$districtName])) {
                $districtId = $districts[$districtName]->id;
            } else {
                $fuzzy = District::where('name', 'LIKE', '%' . $districtName . '%')->first();
                if ($fuzzy) {
                    $districtId = $fuzzy->id;
                }
            }

            if ($districtId) {
                // Keep name within varchar limits just in case
                $name = substr($gn['name'], 0, 255);
                
                $insertData[] = [
                    'name' => $name,
                    'gn_code' => $gn['gn_code'],
                    'ds_division_name' => $gn['ds_division_name'],
                    'province_name' => $gn['province_name'],
                    'latitude' => $gn['latitude'],
                    'longitude' => $gn['longitude'],
                    'district_id' => $districtId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $bar->advance();
            
            // Insert in chunks to avoid memory/query limits
            if (count($insertData) >= 1000) {
                GnDivision::insert($insertData);
                $insertData = [];
            }
        }

        // Insert remaining
        if (!empty($insertData)) {
            GnDivision::insert($insertData);
        }

        $bar->finish();
        $this->command->info("\nGN Divisions seeding completed successfully!");
    }
}
