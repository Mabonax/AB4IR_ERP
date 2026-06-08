<?php

namespace App\Domains\Organization\Enums;

enum OrganizationDocumentSlot: string
{
    case CompanyDefaultSignature = 'company_default_signature';
    case CeoSignature = 'ceo_signature';
    case BoardSignature = 'board_signature';
    case CompanyProfileMaster = 'company_profile_master';
    case ConceptDocumentMaster = 'concept_document_master';
    case PosterMaster = 'poster_master';
    case BrochureMaster = 'brochure_master';
    case MarketingPlanMaster = 'marketing_plan_master';
    case ContentPlanMaster = 'content_plan_master';
    case LetterheadMaster = 'letterhead_master';
    case BrandTemplateMaster = 'brand_template_master';
    case WebsiteBannerMaster = 'website_banner_master';
    case PressReleaseMaster = 'press_release_master';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CompanyDefaultSignature => 'Company Default Signature',
            self::CeoSignature => 'CEO Signature',
            self::BoardSignature => 'Board Signature',
            self::CompanyProfileMaster => 'Company Profile Master',
            self::ConceptDocumentMaster => 'Concept Document Master',
            self::PosterMaster => 'Poster Master',
            self::BrochureMaster => 'Brochure Master',
            self::MarketingPlanMaster => 'Marketing Plan Master',
            self::ContentPlanMaster => 'Content Plan Master',
            self::LetterheadMaster => 'Letterhead Master',
            self::BrandTemplateMaster => 'Brand Template Master',
            self::WebsiteBannerMaster => 'Website Banner Master',
            self::PressReleaseMaster => 'Press Release Master',
        };
    }

    public function documentType(): OrganizationDocumentType
    {
        return match ($this) {
            self::CompanyDefaultSignature,
            self::CeoSignature,
            self::BoardSignature => OrganizationDocumentType::EmailSignature,
            self::CompanyProfileMaster => OrganizationDocumentType::BrandAsset,
            self::ConceptDocumentMaster => OrganizationDocumentType::ConceptDocument,
            self::PosterMaster => OrganizationDocumentType::Poster,
            self::BrochureMaster => OrganizationDocumentType::Brochure,
            self::MarketingPlanMaster => OrganizationDocumentType::MarketingPlan,
            self::ContentPlanMaster => OrganizationDocumentType::ContentPlan,
            self::LetterheadMaster => OrganizationDocumentType::LetterCommunication,
            self::BrandTemplateMaster => OrganizationDocumentType::BrandedTemplate,
            self::WebsiteBannerMaster => OrganizationDocumentType::WebsiteBanner,
            self::PressReleaseMaster => OrganizationDocumentType::PressRelease,
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $slot) => [
            'value' => $slot->value,
            'label' => $slot->label(),
            'document_type' => $slot->documentType()->value,
        ], self::cases());
    }

    public static function optionsForType(string $documentType): array
    {
        return array_values(array_filter(self::options(), fn (array $slot) => $slot['document_type'] === $documentType));
    }
}
