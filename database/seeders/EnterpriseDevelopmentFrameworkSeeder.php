<?php

namespace Database\Seeders;

use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentCriterion;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentDimension;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EnterpriseDevelopmentFrameworkSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = [
            'Enterprise Foundation' => ['weighting' => 1.1, 'criteria' => ['Business concept defined', 'Business purpose documented', 'Founder commitment established', 'Product or service described']],
            'Identity & Professionalisation' => ['weighting' => 1, 'criteria' => ['Business name established', 'Logo available', 'Company profile available', 'Business email available', 'Quotation template available', 'Invoice template available']],
            'Compliance & Governance' => ['weighting' => 1.25, 'criteria' => ['Company registration', 'CIPC documentation', 'Business bank account', 'Tax registration status', 'Beneficial ownership requirements', 'B-BBEE documentation where applicable', 'UIF where applicable', 'COIDA where applicable']],
            'Business Model & Strategy' => ['weighting' => 1.1, 'criteria' => ['Value proposition defined', 'Revenue model defined', 'Pricing model established', 'Target customer understood', 'Competitor awareness']],
            'Business Administration' => ['weighting' => 1, 'criteria' => ['Record keeping process', 'Customer register', 'Supplier register', 'Document filing process', 'Basic policies and templates']],
            'Financial Capability' => ['weighting' => 1.2, 'criteria' => ['Bookkeeping process', 'Budget available', 'Cash-flow management', 'Financial records', 'Management accounts', 'Funding readiness']],
            'Market Readiness' => ['weighting' => 1, 'criteria' => ['Product or service catalogue', 'Sales channels identified', 'Customer pipeline', 'Marketing materials', 'Market access readiness']],
            'Operational Capability' => ['weighting' => 1, 'criteria' => ['Delivery process defined', 'Quality control approach', 'Resource planning', 'Operational tools available', 'Risk management awareness']],
            'Entrepreneur Capability' => ['weighting' => 1, 'criteria' => ['Leadership readiness', 'Financial management understanding', 'Sales capability', 'Compliance awareness', 'Learning commitment']],
            'Digital Capability' => ['weighting' => 0.9, 'criteria' => ['Digital presence', 'Website or landing page', 'Business communication tools', 'Digital record keeping', 'Cybersecurity awareness']],
            'Growth & Impact' => ['weighting' => 0.9, 'criteria' => ['Jobs baseline captured', 'Turnover baseline captured', 'Existing customer baseline', 'Market access baseline', 'Funding accessed baseline']],
        ];

        $dimensionSequence = 1;
        foreach ($dimensions as $dimensionName => $config) {
            $dimensionCode = Str::slug($dimensionName, '_');
            $dimension = EnterpriseDevelopmentDimension::query()->updateOrCreate(
                ['code' => $dimensionCode],
                [
                    'name' => $dimensionName,
                    'description' => "AB4IR enterprise development dimension: {$dimensionName}.",
                    'sequence' => $dimensionSequence,
                    'weighting' => $config['weighting'],
                    'active' => true,
                ]
            );

            foreach ($config['criteria'] as $index => $criterionName) {
                $criterionCode = $dimensionCode.'_'.Str::slug($criterionName, '_');
                $evidenceRequired = str_contains(strtolower($criterionName), 'documentation')
                    || str_contains(strtolower($criterionName), 'registration')
                    || str_contains(strtolower($criterionName), 'bank')
                    || str_contains(strtolower($criterionName), 'tax')
                    || str_contains(strtolower($criterionName), 'profile')
                    || str_contains(strtolower($criterionName), 'logo')
                    || str_contains(strtolower($criterionName), 'template')
                    || str_contains(strtolower($criterionName), 'accounts');

                EnterpriseDevelopmentCriterion::query()->updateOrCreate(
                    ['code' => $criterionCode],
                    [
                        'dimension_id' => $dimension->id,
                        'name' => $criterionName,
                        'description' => "Assesses whether the enterprise has made developmental progress on {$criterionName}.",
                        'sequence' => $index + 1,
                        'weighting' => 1,
                        'required' => in_array($dimensionCode, ['enterprise_foundation', 'compliance_governance'], true),
                        'active' => true,
                        'evidence_required' => $evidenceRequired,
                        'guidance' => $evidenceRequired
                            ? 'Use an existing document/file where practical. Do not duplicate physical evidence.'
                            : 'Capture the assessor observation from the diagnostic conversation.',
                        'expires' => str_contains(strtolower($criterionName), 'tax'),
                    ]
                );
            }

            $dimensionSequence++;
        }
    }
}
