import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import { edit as editAppearance } from '@/routes/appearance';
import { type BreadcrumbItem } from '@/types';
import { DomainNav } from '@/components/domain-nav';
import { settingsNavItems } from '@/config/domain-nav/settings';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: editAppearance().url,
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <div className="p-4 space-y-8">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Appearance</h1>
                        <div className="text-sm text-muted-foreground">
                            Update your account appearance
                        </div>
                    </div>
                    <DomainNav items={settingsNavItems} />
                </div>

                <div className="rounded-xl border bg-white p-6 shadow-sm">
                    <Heading
                        variant="small"
                        title="Appearance settings"
                        description="Update your account's appearance settings"
                    />
                    <div className="mt-6">
                        <AppearanceTabs />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
