import { onMounted, onUnmounted, ref } from 'vue'

const DB_NAME = 'qrmaster'
const DB_VERSION = 1
const STORE = 'qr_drafts'

export interface QrDraft {
    id: string
    title: string
    type: string
    formData: Record<string, unknown>
    savedAt: number
}

function openDb(): Promise<IDBDatabase> {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION)
        req.onupgradeneeded = (e) => {
            const db = (e.target as IDBOpenDBRequest).result
            if (!db.objectStoreNames.contains(STORE)) {
                const store = db.createObjectStore(STORE, { keyPath: 'id' })
                store.createIndex('savedAt', 'savedAt')
            }
        }
        req.onsuccess = () => resolve(req.result)
        req.onerror = () => reject(req.error)
    })
}

async function withStore<T>(
    mode: IDBTransactionMode,
    fn: (store: IDBObjectStore) => IDBRequest<T>,
): Promise<T> {
    const db = await openDb()
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode)
        const req = fn(tx.objectStore(STORE))
        req.onsuccess = () => resolve(req.result)
        req.onerror = () => reject(req.error)
        tx.oncomplete = () => db.close()
    })
}

export function useOfflineDrafts() {
    const isOnline = ref(navigator.onLine)
    const drafts = ref<QrDraft[]>([])
    const pendingSync = ref(0)

    function onOnline() {
        isOnline.value = true
        loadDrafts()
    }

    function onOffline() {
        isOnline.value = false
    }

    onMounted(() => {
        window.addEventListener('online', onOnline)
        window.addEventListener('offline', onOffline)
        loadDrafts()
    })

    onUnmounted(() => {
        window.removeEventListener('online', onOnline)
        window.removeEventListener('offline', onOffline)
    })

    async function loadDrafts(): Promise<void> {
        try {
            const db = await openDb()
            const result = await new Promise<QrDraft[]>((resolve, reject) => {
                const tx = db.transaction(STORE, 'readonly')
                const req = tx.objectStore(STORE).getAll()
                req.onsuccess = () => resolve(req.result as QrDraft[])
                req.onerror = () => reject(req.error)
                tx.oncomplete = () => db.close()
            })
            drafts.value = result.sort((a, b) => b.savedAt - a.savedAt)
            pendingSync.value = drafts.value.length
        } catch {
            // IndexedDB unavailable (e.g. private mode)
        }
    }

    async function saveDraft(draft: Omit<QrDraft, 'id' | 'savedAt'> & { id?: string }): Promise<string> {
        const id = draft.id ?? crypto.randomUUID()
        const record: QrDraft = { ...draft, id, savedAt: Date.now() }
        await withStore('readwrite', store => store.put(record))
        await loadDrafts()
        return id
    }

    async function deleteDraft(id: string): Promise<void> {
        await withStore('readwrite', store => store.delete(id))
        await loadDrafts()
    }

    async function clearAllDrafts(): Promise<void> {
        await withStore('readwrite', store => store.clear())
        drafts.value = []
        pendingSync.value = 0
    }

    return {
        isOnline,
        drafts,
        pendingSync,
        saveDraft,
        deleteDraft,
        clearAllDrafts,
        loadDrafts,
    }
}
