function bufferToBase64Url(buffer) {
    return btoa(
        Array.from(new Uint8Array(buffer))
            .map(b => String.fromCharCode(b)).join('')
    ).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}

function base64UrlToBuffer(base64url) {
    var b64 = base64url.replace(/-/g,'+').replace(/_/g,'/')
        + '='.repeat((4 - base64url.length % 4) % 4);
    return Uint8Array.from(atob(b64), function(c){ return c.charCodeAt(0); }).buffer;
}

var Auth = {

    getToken:   function() { return localStorage.getItem('jwt_token'); },
    getRefresh: function() { return localStorage.getItem('refresh_token'); },
    setTokens:  function(t,r) {
        localStorage.setItem('jwt_token',t);
        localStorage.setItem('refresh_token',r);
    },
    clearTokens: function() {
        localStorage.removeItem('jwt_token');
        localStorage.removeItem('refresh_token');
    },
    isLoggedIn:         function() { return !!this.getToken(); },
    isPasskeySupported: function() { return typeof window.PublicKeyCredential === 'function'; },

    // --- INSCRIPTION avec Passkey ---
    register: async function(email, displayName) {
        // 1. Demander le challenge au serveur
        var optRes = await fetch('/api/auth/register/options', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({email:email, displayName:displayName})
        });
        if (!optRes.ok) throw new Error((await optRes.json()).error);
        var opts = await optRes.json();

        // 2. Le navigateur demande la biometrie et cree la cle
        var credential = await navigator.credentials.create({
            publicKey: {
                ...opts,
                challenge: base64UrlToBuffer(opts.challenge),
                user: { ...opts.user, id: base64UrlToBuffer(opts.user.id) },
                excludeCredentials: (opts.excludeCredentials||[])
                    .map(function(c){ return {...c, id:base64UrlToBuffer(c.id)}; })
            }
        });

        // 3. Envoyer la preuve cryptographique au serveur
        var verRes = await fetch('/api/auth/register/verify', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                email: email,
                credential: {
                    id: credential.id,
                    rawId: bufferToBase64Url(credential.rawId),
                    response: {
                        clientDataJSON:    bufferToBase64Url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64Url(credential.response.attestationObject)
                    },
                    type: credential.type,
                    clientExtensionResults: credential.getClientExtensionResults()
                }
            })
        });
        var result = await verRes.json();
        if (!verRes.ok) throw new Error(result.error);
        this.setTokens(result.token, result.refresh_token);
        return result.user;
    },

    // --- CONNEXION avec Passkey ---
    login: async function() {
        var optRes = await fetch('/api/auth/login/options', {method:'POST'});
        if (!optRes.ok) throw new Error((await optRes.json()).error);
        var opts = await optRes.json();

        var assertion = await navigator.credentials.get({
            publicKey: {
                ...opts,
                challenge: base64UrlToBuffer(opts.challenge),
                allowCredentials: (opts.allowCredentials||[])
                    .map(function(c){ return {...c, id:base64UrlToBuffer(c.id)}; })
            }
        });

        var verRes = await fetch('/api/auth/login/verify', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                credential: {
                    id: assertion.id,
                    rawId: bufferToBase64Url(assertion.rawId),
                    response: {
                        clientDataJSON:    bufferToBase64Url(assertion.response.clientDataJSON),
                        authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                        signature:         bufferToBase64Url(assertion.response.signature),
                        userHandle: assertion.response.userHandle
                            ? bufferToBase64Url(assertion.response.userHandle) : null
                    },
                    type: assertion.type,
                    clientExtensionResults: assertion.getClientExtensionResults()
                }
            })
        });
        var result = await verRes.json();
        if (!verRes.ok) throw new Error(result.error);
        this.setTokens(result.token, result.refresh_token);
        return result.user;
    },

    // --- REFRESH silencieux : appele auto si la requete retourne 401 ---
    refresh: async function() {
        var refreshToken = this.getRefresh();
        if (!refreshToken) return false;
        var res = await fetch('/api/token/refresh', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({refresh_token: refreshToken})
        });
        if (!res.ok) { this.clearTokens(); return false; }
        var d = await res.json();
        this.setTokens(d.token, d.refresh_token || refreshToken);
        return true;
    },

    logout: function() { this.clearTokens(); }
};
