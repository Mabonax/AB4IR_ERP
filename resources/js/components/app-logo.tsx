import BrandMark from './brand-mark';

export default function AppLogo() {
    return (
        <BrandMark
            className="flex items-center gap-3"
            iconClassName="h-10 w-auto max-w-[11rem] object-contain"
            textClassName="hidden"
            variant="horizontal"
            showWordmark={false}
        />
    );
}
