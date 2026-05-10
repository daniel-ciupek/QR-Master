<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import {
    FlexRender,
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, Plus } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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

interface QrCodeRow {
    id: number
    title: string
    type: string
    short_hash: string
    is_active: boolean
    is_expired: boolean
    expires_at: string | null
    created_at: string
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
    filters: { search: string; sort: string; direction: string }
}>()

const { t } = useI18n()

const search = ref(props.filters.search)

const debouncedSearch = useDebounceFn((value: string) => {
    router.get('/qr', { search: value, sort: props.filters.sort, direction: props.filters.direction }, {
        preserveState: true,
        replace: true,
    })
}, 300)

watch(search, debouncedSearch)

function toggleSort(col: string) {
    const direction = props.filters.sort === col && props.filters.direction === 'asc' ? 'desc' : 'asc'
    router.get('/qr', { search: props.filters.search, sort: col, direction }, { preserveState: true, replace: true })
}

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

const columnHelper = createColumnHelper<QrCodeRow>()

const columns = [
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
    columnHelper.accessor('expires_at', {
        header: () => t('qr.index.columns.expires'),
        cell: (info) => info.getValue() ?? t('qr.index.never'),
    }),
    columnHelper.accessor('created_at', {
        header: () => t('qr.index.columns.created'),
        cell: (info) => info.getValue(),
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
                            :class="sortableCols.includes(header.id) ? 'cursor-pointer select-none' : ''"
                            @click="sortableCols.includes(header.id) ? toggleSort(header.id) : undefined"
                        >
                            <span class="inline-flex items-center gap-1">
                                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                <ArrowUpDown
                                    v-if="sortableCols.includes(header.id)"
                                    class="size-3 text-muted-foreground"
                                    :class="{ 'text-foreground': filters.sort === header.id }"
                                />
                            </span>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <template v-if="qrCodes.data.length > 0">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            class="hover:bg-muted/50"
                        >
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
                            <TableCell class="text-muted-foreground text-sm">
                                {{ row.original.expires_at ?? t('qr.index.never') }}
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">
                                {{ row.original.created_at }}
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
</template>
