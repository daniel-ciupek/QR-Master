export default {
    lang: { pl: 'Polnisch', en: 'Englisch', de: 'Deutsch', es: 'Spanisch', fr: 'Französisch', it: 'Italienisch' },
    theme: { light: 'Hell', dark: 'Dunkel', system: 'System' },
    ui: { search: 'Suche', save: 'Speichern', cancel: 'Abbrechen', delete: 'Löschen', confirm: 'Bestätigen', back: 'Zurück', close: 'Schließen', edit: 'Bearbeiten', create: 'Erstellen', loading: 'Laden…', error: 'Fehler', success: 'Erfolg' },
    nav: { dashboard: 'Dashboard', qrCodes: 'QR-Codes', analytics: 'Analytik', billing: 'Abrechnung', profile: 'Profil', logout: 'Abmelden', admin: 'Admin' },
    auth: {
        login: { title: 'Anmelden', email: 'E-Mail', password: 'Passwort', submit: 'Anmelden', forgotPassword: 'Passwort vergessen?', noAccount: 'Noch kein Konto?', register: 'Registrieren' },
        register: { title: 'Registrieren', name: 'Name', email: 'E-Mail', password: 'Passwort', confirmPassword: 'Passwort bestätigen', submit: 'Konto erstellen', hasAccount: 'Bereits ein Konto?', login: 'Anmelden' },
    },
    dashboard: { title: 'Dashboard', welcome: 'Willkommen zurück', totalScans: 'Scans gesamt', activeQrCodes: 'Aktive QR-Codes', totalQrCodes: 'QR-Codes gesamt' },
    qr: {
        create: { title: 'QR-Code erstellen', save: 'Speichern', saving: 'Speichern…', headTitle: 'Erstellen', cancel: 'Abbrechen', subtitle: 'Erstelle einen neuen QR-Code' },
        edit: { title: 'QR-Code bearbeiten', save: 'Änderungen speichern', saving: 'Speichern…', cancel: 'Abbrechen', headTitle: 'Bearbeiten', subtitle: 'Einstellungen des QR-Codes ändern', fields: { title: 'Titel', customSlug: { label: 'Benutzerdefinierter Link', placeholder: 'mein-angebot', hint: 'Nur Buchstaben, Zahlen, Bindestriche. Erreichbar unter /s/dein-slug.' } } },
        index: { title: 'QR-Codes', create: 'Erstellen', empty: 'Noch keine QR-Codes', emptyDescription: 'Erstellen Sie Ihren ersten QR-Code' },
        status: { active: 'Aktiv', inactive: 'Inaktiv' },
    },
    notifications: { title: 'Benachrichtigungen', empty: 'Keine Benachrichtigungen.', loading: 'Laden…', markRead: 'Als gelesen markieren', markAllRead: 'Alle markieren', delete: 'Löschen', justNow: 'Gerade eben', minutesAgo: 'vor {n} Min.', hoursAgo: 'vor {n}h', scanAnomaly: 'Anomalie erkannt in „{title}"', planLimit: 'Planlimit erreicht: {type}', system: 'Systembenachrichtigung' },
    chat: { title: 'QR-Master Assistent', welcome: 'Hallo! Stellen Sie mir Fragen zu Ihren QR-Codes.', placeholder: 'Frage stellen… (Enter zum Senden)', error: 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.' },
    offline: { noConnection: 'Keine Internetverbindung.', draftsWillSync: 'Ihre Arbeit wird lokal gespeichert und synchronisiert, wenn Sie wieder online sind.', pendingDrafts: '{n} Entwurf offline gespeichert', syncNow: 'Jetzt synchronisieren', untitledDraft: 'Unbenannter Entwurf' },
    collab: { editing: 'Bearbeitet auch:' },
    ai: { suggestName: 'Name mit KI vorschlagen', suggestCta: 'CTA mit KI vorschlagen', generating: 'Generieren…', rateLimitReached: 'Monatliches KI-Limit erreicht. Plan upgraden.' },
}
