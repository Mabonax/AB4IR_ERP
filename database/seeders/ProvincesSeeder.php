<?php

namespace Database\Seeders;

use App\Models\Provinces;


use Illuminate\Database\Seeder;

class ProvincesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $locations = [
            'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal',
            'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'
        ];

        foreach ($locations as $province) {
            Provinces::firstOrCreate(['name' => $province]);
        }
    }
}
