import type { PageProps as InertiaPageProps } from '@inertiajs/core'

export interface User {
    id: number
    name: string
    email: string
    email_verified_at: string | null
    created_at: string
    updated_at: string
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null
    }
    flash: {
        success?: string
        error?: string
        warning?: string
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, PageProps {}
}
