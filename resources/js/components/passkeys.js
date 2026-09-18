const decode = (value) => {
    const base64 = value.replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')), c => c.charCodeAt(0));
};

const encode = (value) => {
    if (value == null) return null;
    let binary = '';
    for (const byte of new Uint8Array(value)) binary += String.fromCharCode(byte);
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
};

window.dashclipPasskeys = {
    async perform(options, purpose) {
        options.challenge = decode(options.challenge);
        if (options.user) options.user.id = decode(options.user.id);
        for (const key of ['allowCredentials', 'excludeCredentials']) {
            if (options[key]) options[key] = options[key].map(item => ({ ...item, id: decode(item.id) }));
        }

        const credential = purpose === 'register'
            ? await navigator.credentials.create({ publicKey: options })
            : await navigator.credentials.get({ publicKey: options });
        if (!credential) throw new Error('No credential returned');

        const response = { clientDataJSON: encode(credential.response.clientDataJSON) };
        if (purpose === 'register') {
            response.attestationObject = encode(credential.response.attestationObject);
            response.transports = credential.response.getTransports?.() ?? [];
        } else {
            response.authenticatorData = encode(credential.response.authenticatorData);
            response.signature = encode(credential.response.signature);
            response.userHandle = encode(credential.response.userHandle);
        }
        return {
            id: credential.id,
            rawId: encode(credential.rawId),
            type: credential.type,
            response,
            clientExtensionResults: credential.getClientExtensionResults(),
        };
    },
};
