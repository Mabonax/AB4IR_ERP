<?php

namespace App\Support\Branding;

class BrandingService
{
    public function payload(): array
    {
        return [
            'name' => $this->platformName(),
            'short_name' => (string) config('branding.short_name', 'POA ERP'),
            'tagline' => (string) config('branding.tagline', ''),
            'logo_url' => $this->logoUrl(),
            'support_email' => (string) config('branding.support_email', ''),
            'pdf_footer' => (string) config('branding.pdf_footer', ''),
        ];
    }

    public function platformName(): string
    {
        return (string) config('branding.platform_name', config('app.name', 'Programme of Action ERP'));
    }

    public function logoUrl(): ?string
    {
        $path = trim((string) config('branding.logo_path', 'logo.png'), '/');

        if (! file_exists(public_path($path))) {
            return null;
        }

        return asset($path);
    }
}
