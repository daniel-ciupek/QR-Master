<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import {
    FlexRender,
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, Copy, Download, GitCompareArrows, MoreHorizontal, Pause, Pencil, Play, Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'
import ExportModal from '@/components/qr/ExportModal.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { useDebounceFn } from '@vueuse/core'
import AppLayout from '@/layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

interface TagItem {
    id: number
    name: string
    color: string
}

interface QrCodeRow {
    id: number
    title: string
    type: string
    short_hash: string
    destination_url: string | null
    is_active: boolean
    is_expired: boolean
    expires_at: string | null
    created_at: string
    tags: TagItem[]
}

interface Paginator {
    data: QrCodeRow[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    prev_page_url: string | null
    next_page_url: string | null
}

const props = defineProps<{
    qrCodes: Paginator
    filters: { search: string; sort: string; direction: string; tag_id: number }
    userTags: TagItem[]
}>()

const { t } = useI18n()

const search = ref(props.filters.search)

// ── Row selection ──────────────────────────────────────────────────
const selectedIds = ref<Set<number>>(new Set())

const isAllSelected = computed(() =>
    props.qrCodes.data.length > 0 && selectedIds.value.size === props.qrCodes.data.length,
)
const isPartiallySelected = computed(() =>
    selectedIds.value.size > 0 && selectedIds.value.size < props.qrCodes.data.length,
)

function toggleSelect(id: number) {
    const next = new Set(selectedIds.value)
    if (next.has(id)) {
        next.delete(id)
    } else {
        next.add(id)
    }
    selectedIds.value = next
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(props.qrCodes.data.map((r) => r.id))
    }
}

function clearSelection() {
    selectedIds.value = new Set()
}

// ── Bulk actions ───────────────────────────────────────────────────
const bulkDeleteOpen = ref(false)

function bulkPause() {
    router.post('/qr/bulk/pause', { ids: [...selectedIds.value] }, {
        preserveScroll: true,
        onSuccess: clearSelection,
    })
}

function bulkActivate() {
    router.post('/qr/bulk/activate', { ids: [...selectedIds.value] }, {
        preserveScroll: true,
        onSuccess: clearSelection,
    })
}

function bulkAssignTag(tagId: number) {
    router.post('/qr/bulk/tag', { ids: [...selectedIds.value], tag_id: tagId }, {
        preserveScroll: true,
        onSuccess: clearSelection,
    })
}

function executeBulkDelete() {
    router.post('/qr/bulk/delete', { ids: [...selectedIds.value] }, {
        onSuccess: () => {
            clearSelection()
            bulkDeleteOpen.value = false
        },
    })
}

function goToCompare() {
    const params = new URLSearchParams()
    for (const id of selectedIds.value) params.append('ids[]', String(id))
    router.visit(`/qr/compare?${params.toString()}`)
}

const canCompare = computed(() => selectedIds.value.size >= 2 && selectedIds.value.size <= 5)

// ── Single-row delete dialog ───────────────────────────────────────
const deleteTarget = ref<QrCodeRow | null>(null)

function confirmDelete(row: QrCodeRow) {
    deleteTarget.value = row
}

function executeDelete() {
    if (!deleteTarget.value) return
    router.delete(`/qr/${deleteTarget.value.id}`, { preserveScroll: true })
    deleteTarget.value = null
}

// ── Export modal ───────────────────────────────────────────────────
const exportTarget = ref<QrCodeRow | null>(null)

function exportQrData(row: QrCodeRow): string {
    return `${window.location.origin}/q/${row.short_hash}`
}

// ── Search / sort / tag filter ─────────────────────────────────────
const debouncedSearch = useDebounceFn((value: string) => {
    router.get('/qr', {
        search: value,
        sort: props.filters.sort,
        direction: props.filters.direction,
        tag_id: props.filters.tag_id || undefined,
    }, { preserveState: true, replace: true })
}, 300)

watch(search, debouncedSearch)

function toggleSort(col: string) {
    const direction = props.filters.sort === col && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/qr', {
        search: props.filters.search,
        sort: col,
        direction,
        tag_id: props.filters.tag_id || undefined,
    }, { preserveState: true, replace: true })
}

function filterByTag(tagId: number) {
    const newTagId = props.filters.tag_id === tagId ? undefined : tagId
    router.get('/qr', {
        search: props.filters.search,
        sort: props.filters.sort,
        direction: props.filters.direction,
        tag_id: newTagId,
    }, { preserveState: true, replace: true })
}

// ── Status helpers ─────────────────────────────────────────────────
function statusVariant(row: QrCodeRow): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (row.is_expired) return 'destructive'
    if (!row.is_active) return 'secondary'
    return 'default'
}

function statusLabel(row: QrCodeRow): string {
    if (row.is_expired) return t('qr.index.status.expired')
    if (!row.is_active) return t('qr.index.status.inactive')
    return t('qr.index.status.active')
}

// ── Row actions ────────────────────────────────────────────────────
function copyLink(row: QrCodeRow) {
    navigator.clipboard.writeText(`${window.location.origin}/q/${row.short_hash}`)
    toast.success(t('qr.index.actions.copyLinkDone'))
}

function toggleActive(row: QrCodeRow) {
    router.patch(`/qr/${row.id}/toggle-active`, {}, { preserveScroll: true })
}

function duplicate(row: QrCodeRow) {
    router.post(`/qr/${row.id}/duplicate`, {}, { preserveScroll: true })
}

// ── Table ──────────────────────────────────────────────────────────
const columnHelper = createColumnHelper<QrCodeRow>()

const columns = [
    columnHelper.display({ id: 'select' }),
    columnHelper.accessor('title', {
        header: () => t('qr.index.columns.title'),
        cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('type', {
        header: () => t('qr.index.columns.type'),
        cell: (info) => t(`qr.index.types.${info.getValue()}`),
    }),
    columnHelper.accessor('short_hash', {
        header: () => t('qr.index.columns.hash'),
        cell: (info) => info.getValue(),
    }),
    columnHelper.display({
        id: 'status',
        header: () => t('qr.index.columns.status'),
    }),
    columnHelper.display({
        id: 'tags',
        header: () => t('qr.tags.label'),
    }),
    columnHelper.accessor('expires_at', {
        header: () => t('qr.index.columns.expires'),
        cell: (info) => info.getValue() ?? t('qr.index.never'),
    }),
    columnHelper.accessor('created_at', {
        header: () => t('qr.index.columns.created'),
        cell: (info) => info.getValue(),
    }),
    columnHelper.display({
        id: 'actions',
        header: () => t('qr.index.columns.actions'),
    }),
]

const table = useVueTable({
    get data() { return props.qrCodes.data },
    columns,
    manualSorting: true,
    manualFiltering: true,
    manualPagination: true,
    getCoreRowModel: getCoreRowModel(),
})

const sortableCols = ['title', 'type', 'is_active', 'created_at', 'expires_at']
</script>

<template>
    <Head :title="t('qr.index.headTitle')" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ t('qr.index.title') }}</h1>
                <p class="text-sm text-muted-foreground mt-1">{{ t('qr.index.subtitle') }}</p>
            </div>
            <Button as-child>
                <Link href="/qr/create">
                    <Plus class="size-4 mr-2" />
                    {{ t('qr.index.createBtn') }}
                </Link>
            </Button>
        </div>

        <!-- Search -->
        <div class="max-w-sm">
            <Input
                v-model="search"
                :placeholder="t('qr.index.searchPlaceholder')"
                autocomplete="off"
            />
        </div>

        <!-- Tag filter pills -->
        <div v-if="userTags.length > 0" class="flex flex-wrap gap-2">
            <button
                type="button"
                :class="[
                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors border',
                    filters.tag_id === 0
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'border-border text-muted-foreground hover:border-ring hover:text-foreground',
                ]"
                @click="filterByTag(0)"
            >
                {{ t('qr.tags.filterAll') }}
            </button>
            <button
                v-for="tag in userTags"
                :key="tag.id"
                type="button"
                :class="[
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition-colors border',
                    filters.tag_id === tag.id
                        ? 'border-transparent text-white'
                        : 'border-border text-muted-foreground hover:border-ring hover:text-foreground',
                ]"
                :style="filters.tag_id === tag.id ? { backgroundColor: tag.color, borderColor: tag.color } : {}"
                @click="filterByTag(tag.id)"
            >
                <span class="size-2 rounded-full shrink-0" :style="{ backgroundColor: tag.color }" />
                {{ tag.name }}
            </button>
        </div>

        <!-- Bulk action bar -->
        <div
            v-if="selectedIds.size > 0"
            class="flex flex-wrap items-center gap-3 rounded-lg border border-primary/30 bg-primary/5 px-4 py-2.5"
        >
            <span class="text-sm font-medium">
                {{ t('qr.bulk.selected', { n: selectedIds.size }) }}
            </span>
            <div class="flex flex-wrap gap-2 ml-auto">
                <Button size="sm" variant="outline" @click="bulkActivate">
                    <Play class="mr-1.5 size-3" />
                    {{ t('qr.bulk.activate') }}
                </Button>
                <Button size="sm" variant="outline" @click="bulkPause">
                    <Pause class="mr-1.5 size-3" />
                    {{ t('qr.bulk.pause') }}
                </Button>
                <DropdownMenu v-if="userTags.length > 0">
                    <DropdownMenuTrigger as-child>
                        <Button size="sm" variant="outline">
                            {{ t('qr.bulk.assignTag') }}
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-44">
                        <DropdownMenuItem
                            v-for="tag in userTags"
                            :key="tag.id"
                            @click="bulkAssignTag(tag.id)"
                        >
                            <span class="mr-2 size-2.5 rounded-full shrink-0" :style="{ backgroundColor: tag.color }" />
                            {{ tag.name }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <Button
                    v-if="canCompare"
                    size="sm"
                    variant="outline"
                    @click="goToCompare"
                >
                    <GitCompareArrows class="mr-1.5 size-3" />
                    {{ t('qr.index.actions.compare', { n: selectedIds.size }) }}
                </Button>
                <Button size="sm" variant="destructive" @click="bulkDeleteOpen = true">
                    <Trash2 class="mr-1.5 size-3" />
                    {{ t('qr.bulk.delete') }}
                </Button>
                <Button size="sm" variant="ghost" @click="clearSelection">
                    {{ t('qr.bulk.clearSelection') }}
                </Button>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-md border border-border overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="[
                                sortableCols.includes(header.id) ? 'cursor-pointer select-none' : '',
                                header.id === 'actions' ? 'w-12' : '',
                                header.id === 'select' ? 'w-10' : '',
                            ]"
                            @click="sortableCols.includes(header.id) ? toggleSort(header.id) : undefined"
                        >
                            <template v-if="header.id === 'select'">
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-border cursor-pointer accent-primary"
                                    :checked="isAllSelected"
                                    :indeterminate="isPartiallySelected"
                                    @change="toggleSelectAll"
                                >
                            </template>
                            <template v-else>
                                <span class="inline-flex items-center gap-1">
                                    <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                    <ArrowUpDown
                                        v-if="sortableCols.includes(header.id)"
                                        class="size-3 text-muted-foreground"
                                        :class="{ 'text-foreground': filters.sort === header.id }"
                                    />
                                </span>
                            </template>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <template v-if="qrCodes.data.length > 0">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :class="['hover:bg-muted/50', selectedIds.has(row.original.id) ? 'bg-primary/5' : '']"
                        >
                            <TableCell>
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-border cursor-pointer accent-primary"
                                    :checked="selectedIds.has(row.original.id)"
                                    @change="toggleSelect(row.original.id)"
                                >
                            </TableCell>
                            <TableCell class="font-medium">{{ row.original.title }}</TableCell>
                            <TableCell>
                                <Badge variant="outline">{{ t(`qr.index.types.${row.original.type}`) }}</Badge>
                            </TableCell>
                            <TableCell>
                                <code class="font-mono text-xs bg-muted px-1.5 py-0.5 rounded">{{ row.original.short_hash }}</code>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="statusVariant(row.original)">{{ statusLabel(row.original) }}</Badge>
                            </TableCell>
                            <!-- Tags -->
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in row.original.tags"
                                        :key="tag.id"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs text-white font-medium"
                                        :style="{ backgroundColor: tag.color }"
                                    >
                                        {{ tag.name }}
                                    </span>
                                    <span v-if="row.original.tags.length === 0" class="text-xs text-muted-foreground">—</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">
                                {{ row.original.expires_at ?? t('qr.index.never') }}
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">
                                {{ row.original.created_at }}
                            </TableCell>

                            <!-- Actions dropdown -->
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="size-8">
                                            <MoreHorizontal class="size-4" />
                                            <span class="sr-only">{{ t('qr.index.columns.actions') }}</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/qr/${row.original.id}/edit`">
                                                <Pencil class="mr-2 size-4" />
                                                {{ t('qr.index.actions.edit') }}
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="duplicate(row.original)">
                                            <Copy class="mr-2 size-4" />
                                            {{ t('qr.index.actions.duplicate') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem @click="copyLink(row.original)">
                                            <Copy class="mr-2 size-4" />
                                            {{ t('qr.index.actions.copyLink') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="exportTarget = row.original">
                                            <Download class="mr-2 size-4" />
                                            {{ t('qr.index.actions.download') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem @click="toggleActive(row.original)">
                                            <Pause v-if="row.original.is_active" class="mr-2 size-4" />
                                            <Play v-else class="mr-2 size-4" />
                                            {{ row.original.is_active ? t('qr.index.actions.pause') : t('qr.index.actions.activate') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-destructive focus:text-destructive"
                                            @click="confirmDelete(row.original)"
                                        >
                                            <Trash2 class="mr-2 size-4" />
                                            {{ t('qr.index.actions.delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </template>

                    <TableEmpty v-else :colspan="columns.length">
                        {{ search ? t('qr.index.emptySearch', { query: search }) : t('qr.index.empty') }}
                    </TableEmpty>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="qrCodes.last_page > 1" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>{{ qrCodes.from }}–{{ qrCodes.to }} / {{ qrCodes.total }}</span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!qrCodes.prev_page_url"
                    @click="router.get(qrCodes.prev_page_url!, {}, { preserveState: true })"
                >
                    ←
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!qrCodes.next_page_url"
                    @click="router.get(qrCodes.next_page_url!, {}, { preserveState: true })"
                >
                    →
                </Button>
            </div>
        </div>
    </div>

    <!-- Single delete confirmation -->
    <Dialog :open="deleteTarget !== null" @update:open="(v) => { if (!v) deleteTarget = null }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('qr.index.deleteDialog.title') }}</DialogTitle>
                <DialogDescription>{{ t('qr.index.deleteDialog.description') }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="deleteTarget = null">{{ t('qr.index.deleteDialog.cancel') }}</Button>
                <Button variant="destructive" @click="executeDelete">{{ t('qr.index.deleteDialog.confirm') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Bulk delete confirmation -->
    <Dialog :open="bulkDeleteOpen" @update:open="(v) => { bulkDeleteOpen = v }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('qr.bulk.deleteDialog.title') }}</DialogTitle>
                <DialogDescription>
                    {{ t('qr.bulk.deleteDialog.description', { n: selectedIds.size }) }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="bulkDeleteOpen = false">{{ t('qr.bulk.deleteDialog.cancel') }}</Button>
                <Button variant="destructive" @click="executeBulkDelete">{{ t('qr.bulk.deleteDialog.confirm') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Export modal -->
    <ExportModal
        v-if="exportTarget !== null"
        :open="exportTarget !== null"
        :data="exportQrData(exportTarget)"
        ecc-level="M"
        @update:open="(v) => { if (!v) exportTarget = null }"
    />
</template>
