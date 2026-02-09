<?php

namespace Database\Seeders;

use App\Domains\Staff\Models\StaffDepartment;
use Illuminate\Database\Seeder;

class StaffDepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Technical', 'description' => null],
            ['name' => 'Marketing', 'description' => null],
            ['name' => 'Admin', 'description' => null],
            ['name' => 'Business Development', 'description' => null],
        ];

        foreach ($departments as $department) {
            StaffDepartment::firstOrCreate(
                ['name' => $department['name']],
                ['description' => $department['description']]
            );
        }
    }
}
