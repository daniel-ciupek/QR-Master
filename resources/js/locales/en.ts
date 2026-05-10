export default {
    app: { name: 'QR-Master', tagline: 'SaaS Platform' },

    nav: {
        dashboard: 'Dashboard',
        qrCodes: 'QR Codes',
        analytics: 'Analytics',
        account: 'Account',
        profile: 'Profile',
        security: 'Security (2FA)',
        sessions: 'Sessions',
        passkeys: 'Passkeys',
        soon: 'soon',
        logout: 'Log out',
    },

    theme: { light: 'Light', dark: 'Dark', system: 'System' },
    lang: { pl: 'Polski', en: 'English' },

    ui: {
        search: 'Search…',
        searchPlaceholder: 'Search or type a command…',
        noResults: 'No results.',
        save: 'Save',
        cancel: 'Cancel',
        confirm: 'Confirm',
        loading: 'Loading…',
    },

    command: {
        navigation: 'Navigation',
        account: 'Account',
        securityProfile: 'Profile — Security',
        activeSessions: 'Active sessions',
    },

    auth: {
        emailLabel: 'Email',
        emailPlaceholder: 'you@company.com',
        passwordLabel: 'Password',
        newPasswordLabel: 'New password',
        passwordConfirmLabel: 'Confirm password',
        nameLabel: 'Full name',

        login: {
            headTitle: 'Sign in',
            subtitle: 'Sign in to your account',
            submit: 'Sign in',
            submitting: 'Signing in…',
            forgotPassword: 'Forgot password',
            noAccount: "Don't have an account?",
            register: 'Register',
        },

        register: {
            headTitle: 'Register',
            subtitle: 'Create a new account',
            submit: 'Create account',
            submitting: 'Creating account…',
            haveAccount: 'Already have an account?',
            login: 'Sign in',
        },

        forgotPassword: {
            headTitle: 'Reset password',
            title: 'Forgot your password?',
            description: "Enter your email — we'll send you a password reset link.",
            submit: 'Send reset link',
            backToLogin: 'Back to login',
        },

        resetPassword: {
            headTitle: 'Set new password',
            title: 'New password',
            submit: 'Set password',
        },

        verifyEmail: {
            headTitle: 'Verify email',
            title: 'Verify your email',
            description: "We've sent a verification link to your email. Check your inbox and click the link.",
            resend: 'Resend',
            linkSent: 'A new verification link has been sent.',
            logout: 'Log out',
        },

        confirmPassword: {
            headTitle: 'Confirm password',
            title: 'Confirm password',
            description: 'This action requires you to confirm your identity.',
            submit: 'Confirm',
        },

        twoFactor: {
            headTitle: 'Two-factor authentication',
            title: '2FA Verification',
            codeDescription: 'Enter the code from your authenticator app.',
            recoveryDescription: 'Enter your recovery code.',
            codeLabel: 'One-time code',
            recoveryLabel: 'Recovery code',
            submit: 'Sign in',
            switchToRecovery: 'Use recovery code',
            switchToCode: 'Use authenticator code',
        },
    },

    dashboard: {
        title: 'Dashboard',
        subtitle: 'Welcome to QR-Master — Stage 1 in progress.',
    },

    profile: {
        index: {
            headTitle: 'My profile',
            personalInfo: {
                title: 'Personal information',
                subtitle: 'Update your name and email address.',
                name: 'Full name',
                email: 'Email address',
                emailNote: 'Changing your email requires re-verification.',
                emailPending: 'Email is pending verification.',
                save: 'Save changes',
                saved: 'Changes saved.',
            },
            password: {
                title: 'Change password',
                subtitle: 'Make sure you use a strong, unique password.',
                current: 'Current password',
                new: 'New password',
                confirm: 'Confirm new password',
                save: 'Change password',
                saved: 'Password changed.',
            },
            gdpr: {
                title: 'Data export (GDPR)',
                subtitle: 'Download a copy of all your data stored in QR-Master as JSON.',
                download: 'Download my data',
            },
            danger: {
                title: 'Danger zone',
                subtitle: 'Deleting your account is irreversible — all data will be permanently removed.',
                delete: 'Delete account',
                dialogTitle: 'Delete account',
                dialogDesc: 'This action is irreversible. Enter your password to confirm account deletion.',
                passwordLabel: 'Password',
                confirmButton: 'Yes, delete my account',
                cancel: 'Cancel',
            },
        },

        security: {
            headTitle: 'Account security',
            title: 'Security',
            subtitle: 'Manage two-factor authentication (2FA)',
            twoFactor: {
                title: 'Two-factor authentication',
                disabledDesc: 'Add an extra layer of protection to your account using an authenticator app (Google Authenticator, Authy, etc.)',
                enable: 'Enable 2FA',
                scanTitle: 'Scan QR code',
                scanDesc: 'Scan the code below with your authenticator app, then confirm with a one-time code.',
                confirmLabel: 'Confirm code from app',
                confirm: 'Confirm',
                enabledTitle: '2FA is enabled',
                showRecovery: 'Show recovery codes',
                hideRecovery: 'Hide codes',
                disable: 'Disable 2FA',
                recoveryTitle: 'Recovery codes',
                recoveryDesc: 'Save these codes in a safe place. Use them if you lose access to your authenticator app.',
                regenerate: 'Generate new codes',
            },
        },

        sessions: {
            headTitle: 'Active sessions',
            title: 'Active sessions',
            description: "Devices logged into your account. End sessions you don't recognize.",
            current: 'Current',
            unknownIp: 'Unknown IP',
            empty: 'No active sessions.',
            revokeOthers: 'End all other sessions',
            revoke: 'End',
        },

        passkeys: {
            headTitle: 'Passkeys',
            title: 'Passkeys',
            subtitle: 'Passkeys are a secure alternative to passwords — they use biometrics or device PIN (Face ID, Touch ID, Windows Hello).',
            empty: 'You have no passkeys yet.',
            addedOn: 'Added',
            add: 'Add passkey',
            revoke: 'Remove',
            notSupported: 'Your browser does not support WebAuthn / Passkeys.',
        },
    },
}
