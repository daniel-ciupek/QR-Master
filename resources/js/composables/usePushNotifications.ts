import { ref } from 'vue'

export type PushPermission = 'default' | 'granted' | 'denied'

export function usePushNotifications() {
    const permission = ref<PushPermission>(
        typeof Notification !== 'undefined' ? (Notification.permission as PushPermission) : 'denied',
    )
    const supported = typeof Notification !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window
    const subscribed = ref(false)
    const loading = ref(false)

    async function fetchVapidKey(): Promise<string> {
        const res = await fetch(route('push.vapid-key'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        const data = await res.json()
        return data.public_key as string
    }

    function urlBase64ToUint8Array(base64String: string): Uint8Array {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
        const rawData = atob(base64)
        return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)))
    }

    async function subscribe(): Promise<void> {
        if (!supported) return
        loading.value = true

        try {
            const result = await Notification.requestPermission()
            permission.value = result as PushPermission

            if (result !== 'granted') return

            const registration = await navigator.serviceWorker.ready
            const vapidKey = await fetchVapidKey()

            const pushSubscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey).buffer as ArrayBuffer,
            })

            const sub = pushSubscription.toJSON()
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''

            await fetch(route('push.subscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    keys: sub.keys,
                }),
            })

            subscribed.value = true
        } finally {
            loading.value = false
        }
    }

    async function unsubscribe(): Promise<void> {
        if (!supported) return
        loading.value = true

        try {
            const registration = await navigator.serviceWorker.ready
            const pushSubscription = await registration.pushManager.getSubscription()

            if (!pushSubscription) {
                subscribed.value = false
                return
            }

            const endpoint = pushSubscription.endpoint
            await pushSubscription.unsubscribe()

            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
            await fetch(route('push.unsubscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ endpoint }),
            })

            subscribed.value = false
        } finally {
            loading.value = false
        }
    }

    async function checkSubscription(): Promise<void> {
        if (!supported) return
        const registration = await navigator.serviceWorker.ready
        const existing = await registration.pushManager.getSubscription()
        subscribed.value = existing !== null
    }

    return { permission, supported, subscribed, loading, subscribe, unsubscribe, checkSubscription }
}
