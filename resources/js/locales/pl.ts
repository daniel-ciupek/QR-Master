export default {
    app: { name: 'QR-Master', tagline: 'Platforma SaaS' },

    nav: {
        dashboard: 'Dashboard',
        qrCodes: 'Kody QR',
        analytics: 'Analityka',
        account: 'Konto',
        profile: 'Profil',
        security: 'Bezpieczeństwo (2FA)',
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
        emailPlaceholder: "ty{'@'}firma.pl",
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

    onboarding: {
        step: 'Krok :current z :total',
        next: 'Dalej',
        finish: 'Zacznij korzystać',
        steps: {
            welcome: {
                title: 'Witaj w QR-Master!',
                subtitle: 'Platforma do tworzenia i zarządzania dynamicznymi kodami QR. Zacznijmy!',
            },
            personalize: {
                title: 'Personalizacja',
                subtitle: 'Dostosuj wygląd aplikacji do swoich preferencji.',
                theme: 'Motyw',
                language: 'Język',
            },
            ready: {
                title: 'Wszystko gotowe!',
                subtitle: 'Twoje konto jest skonfigurowane. Możesz teraz tworzyć swoje pierwsze kody QR.',
                cta: 'Przejdź do panelu',
            },
        },
    },

    dashboard: {
        title: 'Dashboard',
        subtitle: 'Witaj w QR-Master — Etap 1 w budowie.',
    },

    profile: {
        index: {
            headTitle: 'Mój profil',
            personalInfo: {
                title: 'Dane osobowe',
                subtitle: 'Zaktualizuj swoje imię i adres e-mail.',
                name: 'Imię i nazwisko',
                email: 'Adres e-mail',
                emailNote: 'Zmiana e-maila wymaga ponownej weryfikacji adresu.',
                emailPending: 'E-mail oczekuje na weryfikację.',
                save: 'Zapisz zmiany',
                saved: 'Zmiany zostały zapisane.',
            },
            password: {
                title: 'Zmiana hasła',
                subtitle: 'Upewnij się, że używasz silnego, unikalnego hasła.',
                current: 'Aktualne hasło',
                new: 'Nowe hasło',
                confirm: 'Potwierdź nowe hasło',
                save: 'Zmień hasło',
                saved: 'Hasło zostało zmienione.',
            },
            gdpr: {
                title: 'Eksport danych (RODO)',
                subtitle: 'Pobierz kopię wszystkich Twoich danych przechowywanych w QR-Master w formacie JSON.',
                download: 'Pobierz moje dane',
            },
            danger: {
                title: 'Strefa niebezpieczna',
                subtitle: 'Usunięcie konta jest nieodwracalne — wszystkie dane zostaną trwale usunięte.',
                delete: 'Usuń konto',
                dialogTitle: 'Usuń konto',
                dialogDesc: 'Ta operacja jest nieodwracalna. Wprowadź hasło, aby potwierdzić usunięcie konta.',
                passwordLabel: 'Hasło',
                confirmButton: 'Tak, usuń moje konto',
                cancel: 'Anuluj',
            },
        },

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

    qr: {
        create: {
            headTitle: 'Nowy kod QR',
            title: 'Utwórz kod QR',
            subtitle: 'Wypełnij formularz, a podgląd zaktualizuje się automatycznie.',
        },
        tabs: {
            url: 'URL',
            text: 'Tekst',
            email: 'E-mail',
            phone: 'Telefon',
            sms: 'SMS',
        },
        fields: {
            url: { label: 'Adres URL', placeholder: 'https://example.com' },
            text: { label: 'Treść', placeholder: 'Wpisz dowolny tekst…' },
            email: {
                address: 'Adres e-mail',
                addressPlaceholder: 'odbiorca@example.com',
                subject: 'Temat (opcjonalnie)',
                subjectPlaceholder: 'Temat wiadomości',
                body: 'Treść wiadomości (opcjonalnie)',
                bodyPlaceholder: 'Treść…',
            },
            phone: { label: 'Numer telefonu', placeholder: '+48123456789' },
            sms: {
                number: 'Numer telefonu',
                numberPlaceholder: '+48123456789',
                message: 'Treść SMS (opcjonalnie)',
                messagePlaceholder: 'Treść wiadomości…',
            },
        },
        preview: {
            title: 'Podgląd',
            empty: 'Wpisz dane po lewej, aby wygenerować kod QR.',
        },
        ecc: {
            label: 'Korekcja błędów',
            L: 'L — 7%',
            M: 'M — 15%',
            Q: 'Q — 25%',
            H: 'H — 30%',
        },
        validation: {
            urlScheme: 'Adres URL musi zaczynać się od https:// lub http://.',
            tooLong: 'Treść jest zbyt długa ({count}/{max} znaków). Kod QR może być nieczytelny.',
            nearLimit: '{count}/{max} znaków',
        },
        export: {
            title: 'Pobierz',
            png: 'PNG',
            svg: 'SVG',
            pdf: 'PDF',
            eps: 'EPS',
            downloading: 'Pobieranie…',
        },
    },
}
