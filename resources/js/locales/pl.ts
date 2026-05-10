export default {
    app: { name: 'QR-Master', tagline: 'Platforma SaaS' },

    nav: {
        dashboard: 'Dashboard',
        qrCodes: 'Kody QR',
        analytics: 'Analityka',
        profile: 'Profil',
        sessions: 'Sesje',
        passkeys: 'Klucze dostępu',
        soon: 'wkrótce',
        logout: 'Wyloguj się',
    },

    theme: { light: 'Jasny', dark: 'Ciemny', system: 'System' },
    lang: { pl: 'Polski', en: 'English' },

    ui: {
        search: 'Szukaj…',
        searchPlaceholder: 'Szukaj lub wpisz komendę…',
        noResults: 'Brak wyników.',
        save: 'Zapisz',
        cancel: 'Anuluj',
        confirm: 'Potwierdź',
        loading: 'Ładowanie…',
    },

    command: {
        navigation: 'Nawigacja',
        account: 'Konto',
        securityProfile: 'Profil — Bezpieczeństwo',
        activeSessions: 'Aktywne sesje',
    },

    auth: {
        emailLabel: 'Email',
        emailPlaceholder: 'ty@firma.pl',
        passwordLabel: 'Hasło',
        newPasswordLabel: 'Nowe hasło',
        passwordConfirmLabel: 'Powtórz hasło',
        nameLabel: 'Imię i nazwisko',

        login: {
            headTitle: 'Zaloguj się',
            subtitle: 'Zaloguj się do swojego konta',
            submit: 'Zaloguj się',
            submitting: 'Logowanie…',
            forgotPassword: 'Nie pamiętam hasła',
            noAccount: 'Nie masz konta?',
            register: 'Zarejestruj się',
        },

        register: {
            headTitle: 'Rejestracja',
            subtitle: 'Utwórz nowe konto',
            submit: 'Zarejestruj się',
            submitting: 'Rejestrowanie…',
            haveAccount: 'Masz już konto?',
            login: 'Zaloguj się',
        },

        forgotPassword: {
            headTitle: 'Reset hasła',
            title: 'Nie pamiętasz hasła?',
            description: 'Podaj email — wyślemy link do resetu hasła.',
            submit: 'Wyślij link resetujący',
            backToLogin: 'Wróć do logowania',
        },

        resetPassword: {
            headTitle: 'Ustaw nowe hasło',
            title: 'Nowe hasło',
            submit: 'Ustaw hasło',
        },

        verifyEmail: {
            headTitle: 'Weryfikacja email',
            title: 'Potwierdź email',
            description: 'Wysłaliśmy link weryfikacyjny na Twój adres email. Sprawdź skrzynkę i kliknij w link.',
            resend: 'Wyślij ponownie',
            linkSent: 'Nowy link weryfikacyjny został wysłany.',
            logout: 'Wyloguj się',
        },

        confirmPassword: {
            headTitle: 'Potwierdź hasło',
            title: 'Potwierdź hasło',
            description: 'Ta operacja wymaga potwierdzenia tożsamości.',
            submit: 'Potwierdź',
        },

        twoFactor: {
            headTitle: 'Weryfikacja dwuetapowa',
            title: 'Weryfikacja 2FA',
            codeDescription: 'Wprowadź kod z aplikacji uwierzytelniającej.',
            recoveryDescription: 'Wprowadź kod odzyskiwania.',
            codeLabel: 'Kod jednorazowy',
            recoveryLabel: 'Kod odzyskiwania',
            submit: 'Zaloguj się',
            switchToRecovery: 'Użyj kodu odzyskiwania',
            switchToCode: 'Użyj kodu z aplikacji',
        },
    },

    dashboard: {
        title: 'Dashboard',
        subtitle: 'Witaj w QR-Master — Etap 1 w budowie.',
    },

    profile: {
        security: {
            headTitle: 'Bezpieczeństwo konta',
            title: 'Bezpieczeństwo',
            subtitle: 'Zarządzaj weryfikacją dwuetapową (2FA)',
            twoFactor: {
                title: 'Weryfikacja dwuetapowa',
                disabledDesc: 'Dodaj dodatkową warstwę ochrony do swojego konta używając aplikacji uwierzytelniającej (Google Authenticator, Authy, itp.)',
                enable: 'Włącz 2FA',
                scanTitle: 'Skanuj kod QR',
                scanDesc: 'Zeskanuj poniższy kod w aplikacji uwierzytelniającej, następnie potwierdź kodem jednorazowym.',
                confirmLabel: 'Potwierdź kod z aplikacji',
                confirm: 'Potwierdź',
                enabledTitle: '2FA jest włączone',
                showRecovery: 'Pokaż kody odzyskiwania',
                hideRecovery: 'Ukryj kody',
                disable: 'Wyłącz 2FA',
                recoveryTitle: 'Kody odzyskiwania',
                recoveryDesc: 'Zapisz te kody w bezpiecznym miejscu. Służą do odzyskania dostępu jeśli utracisz dostęp do aplikacji uwierzytelniającej.',
                regenerate: 'Wygeneruj nowe kody',
            },
        },

        sessions: {
            headTitle: 'Aktywne sesje',
            title: 'Aktywne sesje',
            description: 'Urządzenia zalogowane na Twoje konto. Zakończ sesje, których nie rozpoznajesz.',
            current: 'Bieżąca',
            unknownIp: 'Nieznany IP',
            empty: 'Brak aktywnych sesji.',
            revokeOthers: 'Zakończ wszystkie inne sesje',
            revoke: 'Zakończ',
        },

        passkeys: {
            headTitle: 'Klucze dostępu (Passkeys)',
            title: 'Klucze dostępu',
            subtitle: 'Passkeys to bezpieczna alternatywa dla hasła — używają biometrii lub PIN urządzenia (Face ID, Touch ID, Windows Hello).',
            empty: 'Nie masz jeszcze żadnych kluczy dostępu.',
            addedOn: 'Dodany',
            add: 'Dodaj klucz dostępu',
            revoke: 'Usuń',
            notSupported: 'Twoja przeglądarka nie obsługuje WebAuthn / Passkeys.',
        },
    },
}
