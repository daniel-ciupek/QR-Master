import type { PageProps as InertiaPageProps } from '@inertiajs/core'

export interface AuthUser {
    id: number
    name: string
    email: string
    email_verified_at: string | null
    two_factor_enabled: boolean
    two_factor_confirmed_at: string | null
}

export interface SharedData {
    auth: {
        user: AuthUser | null
    }
    flash: {
        success?: string | null
        error?: string | null
        warning?: string | null
    }
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & SharedData

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, SharedData {}
}
