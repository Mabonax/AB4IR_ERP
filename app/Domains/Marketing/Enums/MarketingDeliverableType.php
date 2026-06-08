<?php

namespace App\Domains\Marketing\Enums;

enum MarketingDeliverableType: string
{
    case Poster = 'poster';
    case Brochure = 'brochure';
    case SocialMedia = 'social_media';
    case EmailSignature = 'email_signature';
    case ConceptDocument = 'concept_document';
    case WebsiteBanner = 'website_banner';
    case PressRelease = 'press_release';
    case BrandedTemplate = 'branded_template';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
