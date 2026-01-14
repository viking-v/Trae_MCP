(function(global) {
    'use strict';

    const ApiClient = {
        getToken: () => localStorage.getItem('auth_token'),

        clearToken: () => localStorage.removeItem('auth_token'),

        fetch: async (path, options = {}) => {
            const url = path.startsWith('http') ? path : `${SYSTEM_CONFIG.API_BASE_URL}${path.startsWith('/') ? '' : '/'}${path}`;
            const headers = new Headers(options.headers || {});

            if (!headers.has('Accept')) headers.set('Accept', 'application/json');

            const token = ApiClient.getToken();
            if (token && !headers.has('Authorization')) {
                headers.set('Authorization', `Bearer ${token}`);
            }

            let body = options.body;
            if (body && !(body instanceof FormData) && typeof body === 'object') {
                if (!headers.has('Content-Type')) headers.set('Content-Type', 'application/json');
                body = JSON.stringify(body);
            }

            const response = await fetch(url, {
                ...options,
                headers,
                body
            });

            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');
            const data = isJson ? await response.json().catch(() => null) : await response.text().catch(() => null);

            if (!response.ok) {
                const error = new Error((data && data.message) ? data.message : `HTTP ${response.status}`);
                error.status = response.status;
                error.data = data;
                throw error;
            }

            return data;
        }
    };

    global.ApiClient = ApiClient;
})(typeof window !== 'undefined' ? window : this);

