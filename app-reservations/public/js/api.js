var API_BASE = '/api';

async function authFetch(url, options = {}) {
    const headers = { 'Content-Type': 'application/json' };
    const token = Auth.getToken();

    if (token) headers['Authorization'] = 'Bearer ' + token;
    if (options.headers) Object.assign(headers, options.headers);

    let res = await fetch(url, { ...options, headers });

    if (res.status === 401 && Auth.getRefresh()) {
        const refreshed = await Auth.refresh();
        if (refreshed) {
            headers['Authorization'] = 'Bearer ' + Auth.getToken();
            res = await fetch(url, { ...options, headers });
        }
    }
    return res;
}

var EventAPI = {
    liste: async () => (await fetch(API_BASE + '/evenements')).json(),
    detail: async id => (await fetch(API_BASE + '/evenements/' + id)).json(),
};

var ReservationAPI = {
    mesReservations: async () => (await authFetch(API_BASE + '/reservations')).json(),
    creer: async evenementId =>
        (await authFetch(API_BASE + '/reservations', { method:'POST', body:JSON.stringify({evenementId}) })).json(),
    annuler: async id =>
        (await authFetch(API_BASE + '/reservations/' + id, {method:'DELETE'})).json()
};

var UserAPI = {
    me: async () => (await authFetch(API_BASE + '/auth/me')).json(),
};
