var API_BASE = '/api';

// authFetch : fetch qui ajoute le JWT et relance apres refresh si 401
async function authFetch(url, options) {
    options = options || {};
    var headers = {'Content-Type':'application/json'};
    var token = Auth.getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;
    if (options.headers) Object.assign(headers, options.headers);

    var res = await fetch(url, Object.assign({}, options, {headers:headers}));

    // Token expire : tenter un refresh silencieux puis reessayer
    if (res.status === 401 && Auth.getRefresh()) {
        var refreshed = await Auth.refresh();
        if (refreshed) {
            headers['Authorization'] = 'Bearer ' + Auth.getToken();
            res = await fetch(url, Object.assign({}, options, {headers:headers}));
        }
    }
    return res;
}

// API Evenements
var EventAPI = {
    liste: async function() {
        var res = await fetch(API_BASE + '/evenements');
        if (!res.ok) throw new Error('Erreur chargement evenements');
        return res.json();
    },
    detail: async function(id) {
        var res = await fetch(API_BASE + '/evenements/' + id);
        if (!res.ok) throw new Error('Evenement introuvable');
        return res.json();
    }
};

// API Reservations
var ReservationAPI = {
    mesReservations: async function() {
        var res = await authFetch(API_BASE + '/reservations');
        if (!res.ok) throw new Error('Erreur chargement reservations');
        return res.json();
    },
    creer: async function(evenementId) {
        var res = await authFetch(API_BASE + '/reservations', {
            method:'POST', body:JSON.stringify({evenementId:evenementId})
        });
        var data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erreur reservation');
        return data;
    },
    annuler: async function(id) {
        var res = await authFetch(API_BASE + '/reservations/' + id, {method:'DELETE'});
        if (!res.ok) { var d=await res.json(); throw new Error(d.error||'Erreur annulation'); }
        return res.json();
    }
};

// API Profil
var UserAPI = {
    me: async function() {
        var res = await authFetch(API_BASE + '/auth/me');
        if (!res.ok) throw new Error('Non authentifie');
        return res.json();
    }
};
