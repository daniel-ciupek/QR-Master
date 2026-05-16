export default {
    lang: { pl: 'Polaco', en: 'Inglés', de: 'Alemán', es: 'Español', fr: 'Francés', it: 'Italiano' },
    theme: { light: 'Claro', dark: 'Oscuro', system: 'Sistema' },
    ui: { search: 'Buscar', save: 'Guardar', cancel: 'Cancelar', delete: 'Eliminar', confirm: 'Confirmar', back: 'Volver', close: 'Cerrar', edit: 'Editar', create: 'Crear', loading: 'Cargando…', error: 'Error', success: 'Éxito' },
    nav: { dashboard: 'Panel', qrCodes: 'Códigos QR', analytics: 'Análisis', billing: 'Facturación', profile: 'Perfil', logout: 'Cerrar sesión', admin: 'Admin' },
    auth: {
        login: { title: 'Iniciar sesión', email: 'Correo electrónico', password: 'Contraseña', submit: 'Iniciar sesión', forgotPassword: '¿Olvidaste tu contraseña?', noAccount: '¿No tienes cuenta?', register: 'Registrarse' },
        register: { title: 'Registrarse', name: 'Nombre', email: 'Correo electrónico', password: 'Contraseña', confirmPassword: 'Confirmar contraseña', submit: 'Crear cuenta', hasAccount: '¿Ya tienes cuenta?', login: 'Iniciar sesión' },
    },
    dashboard: { title: 'Panel', welcome: 'Bienvenido de nuevo', totalScans: 'Escaneos totales', activeQrCodes: 'Códigos QR activos', totalQrCodes: 'Códigos QR totales' },
    qr: {
        create: { title: 'Crear código QR', save: 'Guardar', saving: 'Guardando…', headTitle: 'Crear', cancel: 'Cancelar', subtitle: 'Crea un nuevo código QR' },
        edit: { title: 'Editar código QR', save: 'Guardar cambios', saving: 'Guardando…', cancel: 'Cancelar', headTitle: 'Editar', subtitle: 'Modifica la configuración del código QR', fields: { title: 'Título', customSlug: { label: 'Enlace corto personalizado', placeholder: 'mi-promo', hint: 'Solo letras, números, guiones. Accesible en /s/tu-slug.' } } },
        index: { title: 'Códigos QR', create: 'Crear', empty: 'No hay códigos QR todavía', emptyDescription: 'Crea tu primer código QR' },
        status: { active: 'Activo', inactive: 'Inactivo' },
    },
    notifications: { title: 'Notificaciones', empty: 'Sin notificaciones.', loading: 'Cargando…', markRead: 'Marcar como leído', markAllRead: 'Marcar todas', delete: 'Eliminar', justNow: 'Ahora mismo', minutesAgo: 'hace {n} min', hoursAgo: 'hace {n}h', scanAnomaly: 'Anomalía detectada en «{title}»', planLimit: 'Límite del plan alcanzado: {type}', system: 'Notificación del sistema' },
    chat: { title: 'Asistente QR-Master', welcome: 'Hola. Pregúntame sobre tus códigos QR.', placeholder: 'Hacer una pregunta… (Enter para enviar)', error: 'Algo salió mal. Inténtalo de nuevo.' },
    offline: { noConnection: 'Sin conexión a internet.', draftsWillSync: 'Tu trabajo se guardará localmente y se sincronizará cuando vuelvas a estar en línea.', pendingDrafts: '{n} borrador guardado sin conexión', syncNow: 'Sincronizar ahora', untitledDraft: 'Borrador sin título' },
    collab: { editing: 'También editando:' },
    ai: { suggestName: 'Sugerir nombre con IA', suggestCta: 'Sugerir CTA con IA', generating: 'Generando…', rateLimitReached: 'Límite mensual de IA alcanzado. Actualiza tu plan.' },
}
