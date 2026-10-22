import * as Sentry from '@sentry/vue';

// Suivi des erreurs du navigateur : actif seulement si un DSN Sentry est configuré côté serveur.
// La configuration est injectée au runtime par app.blade.php (window.__SENTRY__), pas au build :
// la même image Docker s'active en définissant SENTRY_LARAVEL_DSN.
export function initSentry(app) {
    const config = window.__SENTRY__;

    if (!config?.dsn) {
        return;
    }

    const tracesSampleRate = Number(config.tracesSampleRate) || 0;

    Sentry.init({
        app,
        dsn: config.dsn,
        environment: config.environment || undefined,
        release: config.release || undefined,
        integrations: tracesSampleRate > 0 ? [Sentry.browserTracingIntegration()] : [],
        tracesSampleRate,
        // Coupures réseau et bruits de navigateur : inutiles à remonter
        ignoreErrors: ['Network Error', 'Failed to fetch', 'Load failed', 'NetworkError', 'ResizeObserver loop'],
    });
}
