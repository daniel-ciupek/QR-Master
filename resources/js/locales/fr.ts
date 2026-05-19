export default {
    lang: { pl: 'Polonais', en: 'Anglais', de: 'Allemand', es: 'Espagnol', fr: 'Français', it: 'Italien' },
    theme: { light: 'Clair', dark: 'Sombre', system: 'Système' },
    ui: { search: 'Recherche', save: 'Enregistrer', cancel: 'Annuler', delete: 'Supprimer', confirm: 'Confirmer', back: 'Retour', close: 'Fermer', edit: 'Modifier', create: 'Créer', loading: 'Chargement…', error: 'Erreur', success: 'Succès' },
    nav: { dashboard: 'Tableau de bord', qrCodes: 'Codes QR', analytics: 'Analytique', billing: 'Facturation', profile: 'Profil', logout: 'Se déconnecter', admin: 'Admin' },
    auth: {
        login: { title: 'Connexion', email: 'E-mail', password: 'Mot de passe', submit: 'Se connecter', forgotPassword: 'Mot de passe oublié ?', noAccount: 'Pas encore de compte ?', register: "S'inscrire" },
        register: { title: "S'inscrire", name: 'Nom', email: 'E-mail', password: 'Mot de passe', confirmPassword: 'Confirmer le mot de passe', submit: 'Créer un compte', hasAccount: 'Déjà un compte ?', login: 'Se connecter' },
    },
    dashboard: { title: 'Tableau de bord', welcome: 'Bon retour', totalScans: 'Scans totaux', activeQrCodes: 'Codes QR actifs', totalQrCodes: 'Codes QR totaux' },
    qr: {
        create: { title: 'Créer un code QR', save: 'Enregistrer', saving: 'Enregistrement…', headTitle: 'Créer', cancel: 'Annuler', subtitle: 'Créez un nouveau code QR' },
        edit: { title: 'Modifier le code QR', save: 'Enregistrer les modifications', saving: 'Enregistrement…', cancel: 'Annuler', headTitle: 'Modifier', subtitle: 'Modifier les paramètres du code QR', fields: { title: 'Titre', customSlug: { label: 'Lien court personnalisé', placeholder: 'ma-promo', hint: 'Lettres, chiffres, tirets uniquement. Accessible à /s/votre-slug.' } } },
        index: { title: 'Codes QR', create: 'Créer', empty: 'Pas encore de codes QR', emptyDescription: 'Créez votre premier code QR' },
        status: { active: 'Actif', inactive: 'Inactif' },
    },
    notifications: { title: 'Notifications', empty: 'Aucune notification.', loading: 'Chargement…', markRead: 'Marquer comme lu', markAllRead: 'Tout marquer', delete: 'Supprimer', justNow: "À l'instant", minutesAgo: 'il y a {n} min', hoursAgo: 'il y a {n}h', scanAnomaly: 'Anomalie détectée dans « {title} »', planLimit: 'Limite du forfait atteinte : {type}', system: 'Notification système' },
    chat: { title: 'Assistant QR-Master', welcome: 'Bonjour ! Posez-moi des questions sur vos codes QR.', placeholder: 'Poser une question… (Entrée pour envoyer)', error: "Une erreur s'est produite. Réessayez." },
    offline: { noConnection: 'Pas de connexion Internet.', draftsWillSync: 'Votre travail est sauvegardé localement et se synchronisera dès que vous serez en ligne.', pendingDrafts: '{n} brouillon sauvegardé hors ligne', syncNow: 'Synchroniser maintenant', untitledDraft: 'Brouillon sans titre' },
    collab: { editing: 'Édite aussi :' },
    ai: { suggestName: "Suggérer un nom avec l'IA", suggestCta: "Suggérer un CTA avec l'IA", generating: 'Génération…', rateLimitReached: "Limite mensuelle d'IA atteinte. Passez à un forfait supérieur." },
}
