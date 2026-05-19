export default {
    lang: { pl: 'Polacco', en: 'Inglese', de: 'Tedesco', es: 'Spagnolo', fr: 'Francese', it: 'Italiano' },
    theme: { light: 'Chiaro', dark: 'Scuro', system: 'Sistema' },
    ui: { search: 'Cerca', save: 'Salva', cancel: 'Annulla', delete: 'Elimina', confirm: 'Conferma', back: 'Indietro', close: 'Chiudi', edit: 'Modifica', create: 'Crea', loading: 'Caricamento…', error: 'Errore', success: 'Successo' },
    nav: { dashboard: 'Dashboard', qrCodes: 'Codici QR', analytics: 'Analisi', billing: 'Fatturazione', profile: 'Profilo', logout: 'Esci', admin: 'Admin' },
    auth: {
        login: { title: 'Accedi', email: 'E-mail', password: 'Password', submit: 'Accedi', forgotPassword: 'Password dimenticata?', noAccount: 'Non hai un account?', register: 'Registrati' },
        register: { title: 'Registrati', name: 'Nome', email: 'E-mail', password: 'Password', confirmPassword: 'Conferma password', submit: 'Crea account', hasAccount: 'Hai già un account?', login: 'Accedi' },
    },
    dashboard: { title: 'Dashboard', welcome: 'Bentornato', totalScans: 'Scansioni totali', activeQrCodes: 'Codici QR attivi', totalQrCodes: 'Codici QR totali' },
    qr: {
        create: { title: 'Crea codice QR', save: 'Salva', saving: 'Salvataggio…', headTitle: 'Crea', cancel: 'Annulla', subtitle: 'Crea un nuovo codice QR' },
        edit: { title: 'Modifica codice QR', save: 'Salva modifiche', saving: 'Salvataggio…', cancel: 'Annulla', headTitle: 'Modifica', subtitle: 'Modifica le impostazioni del codice QR', fields: { title: 'Titolo', customSlug: { label: 'Link breve personalizzato', placeholder: 'mia-promo', hint: 'Solo lettere, numeri, trattini. Accessibile a /s/il-tuo-slug.' } } },
        index: { title: 'Codici QR', create: 'Crea', empty: 'Nessun codice QR ancora', emptyDescription: 'Crea il tuo primo codice QR' },
        status: { active: 'Attivo', inactive: 'Inattivo' },
    },
    notifications: { title: 'Notifiche', empty: 'Nessuna notifica.', loading: 'Caricamento…', markRead: 'Segna come letto', markAllRead: 'Segna tutto', delete: 'Elimina', justNow: 'Proprio ora', minutesAgo: '{n} min fa', hoursAgo: '{n}h fa', scanAnomaly: 'Anomalia rilevata in «{title}»', planLimit: 'Limite del piano raggiunto: {type}', system: 'Notifica di sistema' },
    chat: { title: 'Assistente QR-Master', welcome: 'Ciao! Fai domande sui tuoi codici QR.', placeholder: 'Fai una domanda… (Invio per inviare)', error: 'Qualcosa è andato storto. Riprova.' },
    offline: { noConnection: 'Nessuna connessione Internet.', draftsWillSync: 'Il tuo lavoro è salvato localmente e si sincronizzerà quando torni online.', pendingDrafts: '{n} bozza salvata offline', syncNow: 'Sincronizza ora', untitledDraft: 'Bozza senza titolo' },
    collab: { editing: 'Sta modificando anche:' },
    ai: { suggestName: "Suggerisci nome con l'IA", suggestCta: "Suggerisci CTA con l'IA", generating: 'Generazione…', rateLimitReached: "Limite mensile dell'IA raggiunto. Aggiorna il piano." },
}
