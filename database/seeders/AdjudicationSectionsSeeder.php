<?php

namespace Database\Seeders;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationSection;
use Illuminate\Database\Seeder;

class AdjudicationSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'key' => 'business_model',
                'title' => 'Business Model',
                'description' => 'Is there a customer value proposition? Is there a sustainable revenue-model?',
                'max_points' => 15,
                'sort_order' => 1,
            ],
            [
                'key' => 'market_potential',
                'title' => 'Market Potential',
                'description' => 'Is there a clear target market? Is the market size realistic, and competitors identified?',
                'max_points' => 10,
                'sort_order' => 2,
            ],
            [
                'key' => 'innovation',
                'title' => 'Innovation',
                'description' => 'Is the platform developed introducing a new way of solving a meaningful problem?',
                'max_points' => 15,
                'sort_order' => 3,
            ],
            [
                'key' => 'functionality',
                'title' => 'Functionality',
                'description' => 'Is the platform complete, integrated with its database, and appealing to users?',
                'max_points' => 10,
                'sort_order' => 4,
            ],
        ];

        foreach ($sections as $section) {
            AdjudicationSection::query()->updateOrCreate(
                ['key' => $section['key']],
                $section
            );
        }
    }
}
