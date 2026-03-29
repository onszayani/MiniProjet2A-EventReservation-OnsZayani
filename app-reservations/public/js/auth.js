function bufferToBase64Url(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)))
        .replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}

function base64UrlToBuffer(b64url) {
    const pad = '='.repeat((4 - b64url.length % 4) % 4);
    const b64 = b64url.replace(/-/g,'+').replace(/_/g,'/') + pad;
    return Uint8Array.from(atob(b64), c => c.charCodeAt(0)).buffer;
}

var Auth = {

    getToken:   () => localStorage.getItem('jwt_token'),
    getRefresh: () => localStorage.getItem('refresh_token'),
    setTokens:  (jwt, ref) => { localStorage.setItem('jwt_token', jwt); localStorage.setItem('refresh_token', ref); },
    clearTokens: () => { localStorage.removeItem('jwt_token'); localStorage.removeItem('refresh_token'); },

    isLoggedIn: () => !!localStorage.getItem('jwt_token'),
    isPasskeySupported: () => typeof window.PublicKeyCredential === 'function',

    /* ---------- REGISTER ---------- */
    register: async function(email, displayName) {

        const optRes = await fetch('/api/auth/register/options', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ email, displayName })
        });

        const opts = await optRes.json();
        if (!optRes.ok) throw new Error(opts.error);

        const credential = await navigator.credentials.create({
            publicKey: {
                ...opts,
                challenge: base64UrlToBuffer(opts.challenge),
                user: {
                    ...opts.user,
                    id: base64UrlToBuffer(opts.user.id)
                }
            }
        });

        const verRes = await fetch('/api/auth/register/verify', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                email,
                credential: {
                    id: credential.id,
                    rawId: bufferToBase64Url(credential.rawId),
                    type: credential.type,
                    clientExtensionResults: credential.getClientExtensionResults(),
                    response: {
                        clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64Url(credential.response.attestationObject)
                    }
                }
            })
        });

        const result = await verRes.json();
        if (!verRes.ok) throw new Error(result.error);

        this.setTokens(result.token, result.refresh_token);
        return result.user;
    },

    /* ---------- LOGIN PASSKEY ---------- */
    loginWithPasskey: async function(email) {

        const optRes = await fetch('/api/auth/login/options', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ email })
        });

        const opts = await optRes.json();
        if (!optRes.ok) throw new Error(opts.error);

        opts.challenge = base64UrlToBuffer(opts.challenge);
        opts.allowCredentials = (opts.allowCredentials || []).map(c => ({
            ...c,
            id: base64UrlToBuffer(c.id)
        }));

        const assertion = await navigator.credentials.get({ publicKey: opts });

        const verRes = await fetch('/api/auth/login/verify', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                credential: {
                    id: assertion.id,
                    type: assertion.type,
                    rawId: bufferToBase64Url(assertion.rawId),
                    clientExtensionResults: assertion.getClientExtensionResults(),
                    response: {
                        clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
                        authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                        signature: bufferToBase64Url(assertion.response.signature),
                        userHandle: assertion.response.userHandle
                            ? bufferToBase64Url(assertion.response.userHandle)
                            : null
                    }
                }
            })
        });

        const result = await verRes.json();
        if (!verRes.ok) throw new Error(result.error);

        this.setTokens(result.token, result.refresh_token);
        return result.user;
    }
};
