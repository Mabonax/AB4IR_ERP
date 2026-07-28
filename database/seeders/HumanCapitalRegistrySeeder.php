<?php

namespace Database\Seeders;

use App\Domains\Employment\Models\EmploymentProfile;
use App\Domains\Geography\Models\Branch;
use App\Domains\Geography\Models\Municipality;
use App\Domains\Geography\Models\Region;
use App\Domains\Geography\Models\Township;
use App\Domains\Geography\Models\Ward;
use App\Domains\Members\Models\Member;
use App\Models\Provinces;
use Illuminate\Database\Seeder;

class HumanCapitalRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $gauteng = Provinces::query()->firstOrCreate(['name' => 'Gauteng']);

        $municipality = Municipality::query()->firstOrCreate(
            ['province_id' => $gauteng->id, 'name' => 'City of Tshwane'],
            ['code' => 'TSH']
        );

        $region = Region::query()->firstOrCreate(
            ['municipality_id' => $municipality->id, 'name' => 'Region 1'],
            ['province_id' => $gauteng->id, 'code' => 'R1']
        );

        $township = Township::query()->firstOrCreate(
            ['region_id' => $region->id, 'name' => 'Soshanguve Block L'],
            ['province_id' => $gauteng->id, 'municipality_id' => $municipality->id]
        );

        $ward = Ward::query()->firstOrCreate(
            ['township_id' => $township->id, 'name' => 'Ward 12'],
            [
                'province_id' => $gauteng->id,
                'municipality_id' => $municipality->id,
                'region_id' => $region->id,
                'code' => '12',
            ]
        );

        $branch = Branch::query()->firstOrCreate(
            ['ward_id' => $ward->id, 'name' => 'Block L Branch'],
            [
                'province_id' => $gauteng->id,
                'municipality_id' => $municipality->id,
                'region_id' => $region->id,
                'township_id' => $township->id,
                'code' => 'BLL',
            ]
        );

        $member = Member::query()->firstOrCreate(
            ['id_number' => '9201015800087'],
            [
                'first_name' => 'Lerato',
                'last_name' => 'Mokoena',
                'date_of_birth' => '1992-01-01',
                'gender' => 'Female',
                'phone' => '0820000000',
                'email' => 'lerato.mokoena@example.test',
                'province_id' => $gauteng->id,
                'municipality_id' => $municipality->id,
                'region_id' => $region->id,
                'township_id' => $township->id,
                'ward_id' => $ward->id,
                'branch_id' => $branch->id,
                'member_type' => 'Graduate',
                'status' => 'active',
                'youth_indicator' => true,
                'household_size' => 4,
                'dependants' => 1,
            ]
        );

        $member->qualifications()->firstOrCreate([
            'qualification_name' => 'National Diploma in Information Technology',
            'field_of_study' => 'Information Technology',
        ], [
            'qualification_type' => 'Diploma',
            'institution' => 'Tshwane South TVET College',
            'nqf_level' => 'NQF 6',
            'completed_flag' => true,
            'completion_year' => 2024,
        ]);

        $member->skills()->firstOrCreate([
            'skill_name' => 'Software Development',
        ], [
            'category' => 'Digital',
            'proficiency_level' => 'Intermediate',
            'years_experience' => 2,
        ]);

        EmploymentProfile::query()->firstOrCreate(
            ['member_id' => $member->id],
            [
                'employment_status' => 'Unemployed',
                'occupation' => 'Junior Developer',
                'industry' => 'Technology',
                'years_experience' => 2,
                'monthly_income_band' => 'No income',
            ]
        );
    }
}
