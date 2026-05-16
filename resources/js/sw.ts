/// <reference lib="webworker" />
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching'
import { registerRoute } from 'workbox-routing'
import { CacheFirst, NetworkFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'

declare const self: ServiceWorkerGlobalScope & { __WB_MANIFEST: { url: string; revision: string | null }[] }

cleanupOutdatedCaches()
precacheAndRoute(self.__WB_MANIFEST)

// Google Fonts — CacheFirst 1 year
registerRoute(
    ({ url }) => url.origin === 'https://fonts.googleapis.com' || url.origin === 'https://fonts.gstatic.com',
    new CacheFirst({
        cacheName: 'google-fonts-cache',
        plugins: [new ExpirationPlugin({ maxEntries: 10, maxAgeSeconds: 60 * 60 * 24 * 365 })],
    }),
)

// Only cache public/non-sensitive API endpoints — never cache authenticated user data
// (avoids leaking data to next user on same device after logout)
const PUBLIC_API_PATTERNS = ['/api/v1/qr-codes/search']

registerRoute(
    ({ url, request }) =>
        url.pathname.startsWith('/api/v1/') &&
        PUBLIC_API_PATTERNS.some(p => url.pathname.startsWith(p)) &&
        request.method === 'GET',
    new NetworkFirst({
        cacheName: 'api-public-cache',
        plugins: [new ExpirationPlugin({ maxEntries: 20, maxAgeSeconds: 60 * 5 })],
        networkTimeoutSeconds: 10,
    }),
)

// Push notifications
self.addEventListener('push', (event: PushEvent) => {
    if (!event.data) return

    let payload: { title?: string; body?: string; icon?: string; badge?: string; data?: Record<string, unknown> }
    try {
        payload = event.data.json() as typeof payload
    } catch {
        payload = { title: 'QR-Master', body: event.data.text() }
    }

    const title = payload.title ?? 'QR-Master'
    const options: NotificationOptions = {
        body: payload.body ?? '',
        icon: payload.icon ?? '/icons/icon-192x192.png',
        badge: payload.badge ?? '/icons/icon-72x72.png',
        data: payload.data ?? {},
    }

    event.waitUntil(self.registration.showNotification(title, options))
})

self.addEventListener('notificationclick', (event: NotificationEvent) => {
    event.notification.close()
    const url = (event.notification.data as { url?: string })?.url ?? '/dashboard'
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients: ReadonlyArray<WindowClient>) => {
            for (const client of clients) {
                if ('focus' in client) return (client as WindowClient).focus()
            }
            return self.clients.openWindow(url)
        }),
    )
})
