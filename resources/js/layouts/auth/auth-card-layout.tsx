import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

import BrandMark from '@/components/brand-mark';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';
import { type SharedData } from '@/types';

export default function AuthCardLayout({
    children,
    title,
    description,
}: PropsWithChildren<{
    name?: string;
    title?: string;
    description?: string;
}>) {
    const { brand } = usePage<SharedData>().props;

    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-zinc-900 p-6 md:p-10">
            <div className="flex w-full max-w-md flex-col gap-6">
              

                <div className="flex flex-col gap-6">
                    <Card className="rounded-xl">
                        <CardHeader className="px-10 pt-8 pb-0 text-center">
                            <Link
                                href={home()}
                                className="flex flex-col items-center gap-2 self-center font-medium"
                            >
                                <BrandMark
                                    className="flex flex-col items-center gap-3"
                                    iconClassName="h-14 w-14 object-contain"
                                    textClassName="text-center"
                                />
                            </Link>
                            <CardTitle className="text-xl">{title}</CardTitle>
                            <CardDescription>{description ?? brand?.tagline}</CardDescription>
                        </CardHeader>
                        <CardContent className="px-10 py-8">
                            {children}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
