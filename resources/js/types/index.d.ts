import type Echo from 'laravel-echo'
import type Pusher from 'pusher-js'
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
    locale: 'pl' | 'en'
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & SharedData

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, SharedData {}
}

interface TurnstileInstance {
    render(container: HTMLElement, options: {
        sitekey: string
        callback?: (token: string) => void
        'expired-callback'?: () => void
        'error-callback'?: () => void
        theme?: 'auto' | 'light' | 'dark'
        size?: 'normal' | 'compact'
    }): string
    reset(widgetId?: string): void
    remove(widgetId: string): void
    getResponse(widgetId?: string): string
}

declare global {
    interface Window {
        turnstile?: TurnstileInstance
        Pusher: typeof Pusher
        Echo: Echo
    }
}
