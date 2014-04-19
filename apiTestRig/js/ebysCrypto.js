/**
 * Provides a JQuery plugin for browser side cryptographic services.
 * This library has been implemented using selected portions of the forge library 
 * see https://github.com/digitalbazaar/forge 
 * and designed specifically with the needs of the browser eBook reader in mind
 */

(function($) {
    if (typeof $.ebysCrypto == 'undefined') {
        $.ebysCrypto = {
            keyPair : undefined,
            publicPem64 : undefined,
            buildApiAuthHeader : function(url, method, context, key, secret) {
                var url_regx = /^http:\/\/.*?(\/.*)/gi;
                var matches = url_regx.exec(url);
                var request_uri = matches[1];
                var timestamp = Math.round((new Date()).getTime() / 1000, 0);
                var nonce = forge.md.md5.create().update('thisisasalt' + Math.random() + timestamp).digest().toHex();
                var sessionId = (context !== null) ? context.session_id : 0;
                method = method.toUpperCase();
                // prepare the string used to generate the signature
                var signatureBase = method + ' ' + request_uri + ' ' + sessionId + ' ' + timestamp + ' ' + nonce;
                var hmac = forge.hmac.create();
                hmac.start('sha1', secret);
                hmac.update(signatureBase);
                var signature = hmac.digest().toHex();
                return 'Anobiiv1 ' + key + ' ' + sessionId + ' ' + timestamp + ' ' + nonce + ' ' + signature;
            },
            decryptBookPage : function(pageKey, encryptedPage) {
                var decryptedKey = $.ebysCrypto.keyPair.privateKey.decrypt(forge.util.decode64(pageKey));
                var ivKey = forge.md.md5.create().update(decryptedKey).digest();                
                var pageDecryptionKey = forge.md.md5.create().update(decryptedKey).digest();
                pageDecryptionKey.putBuffer(pageDecryptionKey);
                var cipher = forge.aes.createDecryptionCipher(pageDecryptionKey, 'CBC');
                var pageBuffer = forge.util.createBuffer(forge.util.decode64(encryptedPage));
                cipher.start(ivKey);
                cipher.update(pageBuffer);
                cipher.finish();
                return cipher.output.toString();
            },
            generateKeys : function() {
                if (typeof $.ebysCrypto.keyPair == 'undefined') {
                    $.ebysCrypto.keyPair = forge.pki.rsa.generateKeyPair({
                        bits : 2048,
                        e : 0x10101
                    });
                    $.ebysCrypto.publicPem64 = forge.util.encode64(forge.pki.publicKeyToPem(
                            $.ebysCrypto.keyPair.publicKey).toString());
                }
            }
        };
    }
}(jQuery));
