import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

type PageModules = Record<string, () => Promise<unknown>>;

const toPascalSegment = (segment: string): string =>
    segment
        .split('-')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

const toPascalPath = (value: string): string =>
    value
        .split('/')
        .filter(Boolean)
        .map(toPascalSegment)
        .join('/');

export const createPageResolver = (pages: PageModules) => {
    const lowerCaseIndex = new Map(
        Object.keys(pages).map((path) => [path.toLowerCase(), path]),
    );

    return (name: string) => {
        const pascalName = toPascalPath(name);
        const candidates = [
            `./pages/${name}.tsx`,
            `./pages/${name}/index.tsx`,
            `./pages/${pascalName}.tsx`,
            `./pages/${pascalName}/Index.tsx`,
        ];

        for (const candidate of candidates) {
            if (candidate in pages) {
                return resolvePageComponent(candidate, pages);
            }
        }

        for (const candidate of candidates) {
            const matchedPath = lowerCaseIndex.get(candidate.toLowerCase());

            if (matchedPath) {
                return resolvePageComponent(matchedPath, pages);
            }
        }

        throw new Error(`Page not found: ./pages/${name}.tsx`);
    };
};
