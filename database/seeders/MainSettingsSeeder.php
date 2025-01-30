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
            'accountId' => '1dd5bd55-d141-11ec-0a80-055600047495',
            'ms_token' => 'ad5bfe0e27db11b9e886b2ee11327d719cea9c3b',  // Укажите токен для МойСклад
            'UID_ms' => 'admin@pidife',
        ]);
    }
}
