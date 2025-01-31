<?php

namespace Database\Seeders;

use App\Models\MainSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MainSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MainSettings::create([
            'accountId' => 'b8f81691-de0a-11ef-0a80-0f510003c8c8',
            'ms_token' => 'ad5bfe0e27db11b9e886b2ee11327d719cea9c3b',
            'UID_ms' => 'admin@pidife',
        ]);
    }
}
