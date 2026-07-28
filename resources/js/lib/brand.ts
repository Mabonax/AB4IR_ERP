import { type SharedData } from '@/types';

export type BrandAssetVariant = 'horizontal' | 'light' | 'dark' | 'stacked' | 'svg';

const defaultBrandAssets: Record<BrandAssetVariant, string> = {
    horizontal: '/poa-assets/poa-logo-horizontal.png',
    light: '/poa-assets/poa-logo-light.png',
    dark: '/poa-assets/poa-logo-dark.png',
    stacked: '/poa-assets/poa-logo-icon.png',
    svg: '/logo.svg',
};

export function getBrandAsset(
    variant: BrandAssetVariant,
    shared: Pick<SharedData, 'brand' | 'organization'>,
) {
    const organization = shared.organization;
    const brand = shared.brand;

    if (variant === 'horizontal') {
        return organization?.primary_logo_url ?? brand?.logo_url ?? defaultBrandAssets.horizontal;
    }

    if (variant === 'light') {
        return organization?.light_logo_url ?? defaultBrandAssets.light;
    }

    if (variant === 'dark') {
        return organization?.dark_logo_url ?? defaultBrandAssets.dark;
    }

    if (variant === 'stacked') {
        return organization?.icon_logo_url ?? defaultBrandAssets.stacked;
    }

    return defaultBrandAssets.svg;
}

export function getBrandIdentity(shared: Pick<SharedData, 'brand' | 'organization' | 'name'>) {
    return {
        name: shared.brand?.name ?? shared.organization?.name ?? shared.name ?? 'Programme of Action ERP',
        shortName: shared.brand?.short_name ?? 'POA ERP',
        tagline:
            shared.organization?.tagline ??
            shared.brand?.tagline ??
            'Programme delivery, governance, and institutional operations in one system.',
    };
}
