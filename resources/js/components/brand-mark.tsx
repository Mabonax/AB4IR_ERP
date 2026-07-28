import { usePage } from '@inertiajs/react';

import { getBrandAsset, getBrandIdentity, type BrandAssetVariant } from '@/lib/brand';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';

type BrandMarkProps = {
    className?: string;
    iconClassName?: string;
    textClassName?: string;
    showWordmark?: boolean;
    variant?: BrandAssetVariant;
};

export default function BrandMark({
    className,
    iconClassName,
    textClassName,
    showWordmark = true,
    variant = 'horizontal',
}: BrandMarkProps) {
    const shared = usePage<SharedData>().props;
    const { name, shortName } = getBrandIdentity(shared);
    const logoUrl = getBrandAsset(variant, shared);
    const iconOnly = variant === 'light' || variant === 'dark' || variant === 'stacked';

    return (
        <div className={cn('flex items-center gap-3', className)}>
            <img
                src={logoUrl}
                alt={`${name} logo`}
                className={cn(
                    iconOnly ? 'h-12 w-12 object-contain' : 'h-11 w-auto object-contain',
                    iconClassName,
                )}
            />

            {showWordmark ? (
                <div className={cn('min-w-0', textClassName)}>
                    <div className="truncate text-sm font-semibold tracking-[0.14em] text-[#111111] uppercase">
                        {shortName}
                    </div>
                    <div className="truncate text-xs text-[#4B5563]">{name}</div>
                </div>
            ) : null}
        </div>
    );
}
