/**
 * Cliente AJAX unificado — un solo endpoint: ajax/api.php
 */
window.API = {
    url() {
        if (window.API_URL) return window.API_URL;
        const p = window.location.pathname;
        return (p.includes('/front/') || p.endsWith('/front'))
            ? 'ajax/api.php'
            : 'front/ajax/api.php';
    },

    async post(module, data = {}) {
        const fd = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            Object.entries(data).forEach(([k, v]) => {
                if (v !== undefined && v !== null) fd.append(k, v);
            });
        }
        fd.set('module', module);

        const res = await fetch(this.url(), {
            method: 'POST',
            cache: 'no-store',
            body: fd,
        });

        let json;
        try {
            json = await res.json();
        } catch {
            return {
                success: false,
                message: res.ok
                    ? 'Respuesta inválida del servidor'
                    : `Error del servidor (${res.status})`,
            };
        }

        if (!res.ok && json.success !== false) {
            return {
                success: false,
                message: json.message || `Error del servidor (${res.status})`,
            };
        }

        if (res.status === 401 && json.message === 'No autenticado') {
            const loginUrl = window.SITE_URL ? `${window.SITE_URL}/dash.php` : '../dash.php';
            window.location.href = loginUrl;
            return json;
        }

        return json;
    },
};
