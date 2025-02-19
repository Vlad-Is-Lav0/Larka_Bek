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
            'ms_token' => '5037c8e908665621dcbf34a44200f26e218e86f7',
            'UID_ms' => 'vladislav@smart_demo',
        ]);
    }
}
