<?php

namespace App\Domains\Organization\Enums;

enum OrganizationDocumentType: string
{
    case EmailSignature = 'email_signature';
    case ConceptDocument = 'concept_document';
    case Poster = 'poster';
    case Brochure = 'brochure';
    case SocialMediaAsset = 'social_media_asset';
    case ContentPlan = 'content_plan';
    case MarketingPlan = 'marketing_plan';
    case LetterCommunication = 'letter_communication';
    case WebsiteBanner = 'website_banner';
    case PressRelease = 'press_release';
    case BrandedTemplate = 'branded_template';
    case BrandAsset = 'brand_asset';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::EmailSignature => 'Email Signature',
            self::ConceptDocument => 'Concept Document',
            self::Poster => 'Poster',
            self::Brochure => 'Brochure',
            self::SocialMediaAsset => 'Social Media Asset',
            self::ContentPlan => 'Content Plan',
            self::MarketingPlan => 'Marketing Plan',
            self::LetterCommunication => 'Letter Communication',
            self::WebsiteBanner => 'Website Banner',
            self::PressRelease => 'Press Release',
            self::BrandedTemplate => 'Branded Template',
            self::BrandAsset => 'Brand Asset',
            self::Other => 'Other',
        };
    }

    public static function defaultForMarketingJobType(string $jobType): self
    {
        return match ($jobType) {
            'email_signature' => self::EmailSignature,
            'content_plan' => self::ContentPlan,
            'letter_communication' => self::LetterCommunication,
            'social_media' => self::SocialMediaAsset,
            'graphic_design' => self::BrandAsset,
            default => self::Other,
        };
    }

    public static function defaultForMarketingAssetType(string $assetType): self
    {
        return match ($assetType) {
            'email_signature' => self::EmailSignature,
            'concept_document' => self::ConceptDocument,
            'poster' => self::Poster,
            'brochure' => self::Brochure,
            'social_media' => self::SocialMediaAsset,
            'website_banner' => self::WebsiteBanner,
            'press_release' => self::PressRelease,
            'branded_template' => self::BrandedTemplate,
            default => self::Other,
        };
    }
}
