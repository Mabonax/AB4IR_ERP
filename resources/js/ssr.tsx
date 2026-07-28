import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';

import { createPageResolver } from './lib/inertia-page-resolver';

const appName = import.meta.env.VITE_APP_NAME || 'Programme of Action ERP';
const pages = import.meta.glob('./pages/**/*.tsx');
const resolvePage = createPageResolver(pages);

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: resolvePage,
        setup: ({ App, props }) => {
            return <App {...props} />;
        },
    }),
);
