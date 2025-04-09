<?php declare(strict_types = 1);
/**
 * This file is to handle the Encryption and Decryption
 */

namespace SpryPhp\Provider;

use Exception;

/**
 * Class for managing Encryption and Decryption using AES-256-CTR with hash algorithm sha256
 */
class Crypt
{
    const METHOD = 'aes-256-ctr';
    const HASH_ALGO = 'sha256';

    /**
     * Encrypts then MACs a message
     *
     * @param mixed  $message - Message to encrypt. Uses json_encode() to ensure string.
     * @param string $key     - A Secure Key/Passphrase to use to Encrypt.
     *
     * @return string (Json and Base64 Encoded)
     */
    public static function encrypt(mixed $message, string $key): string
    {
        $message = json_encode($message);

        if ($message === false) {
            throw new Exception('SpryPHP: Encryption failure. Error Json Encoding your Message.');
        }

        $encKey     = hash_hmac(self::HASH_ALGO, 'ENCRYPTION', $key, true);
        $authKey    = hash_hmac(self::HASH_ALGO, 'AUTHENTICATION', $key, true);
        $nonce      = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::METHOD));
        $cipherText = openssl_encrypt(
            $message,
            self::METHOD,
            $encKey,
            OPENSSL_RAW_DATA,
            $nonce
        );

        $cipherText = $nonce.$cipherText;

        // Calculate a MAC of the IV and cipherText
        $mac = hash_hmac(self::HASH_ALGO, $cipherText, $authKey, true);

        // Prepend MAC to the cipherText and return to caller
        return base64_encode($mac.$cipherText);
    }

    /**
     * Decrypts a message (after verifying integrity)
     *
     * @param string $message - CipherText Message to Decrypt.
     * @param string $key     - A Secure Key/Passphrase to use to Decrypt. Should be the same as the one used to Encrypt.
     *
     * @return mixed (Json and Base64 Decoded)
     */
    public static function decrypt(string $message, string $key)
    {
        $encKey  = hash_hmac(self::HASH_ALGO, 'ENCRYPTION', $key, true);
        $authKey = hash_hmac(self::HASH_ALGO, 'AUTHENTICATION', $key, true);
        $message = base64_decode($message, true);
        if (false === $message) {
            throw new Exception('SpryPHP: Decryption failure. String is not Base64 Formatted.');
        }

        // Hash Size -- in case HASH_ALGO is changed
        $hs = mb_strlen(hash(self::HASH_ALGO, '', true), '8bit');
        $mac = mb_substr($message, 0, $hs, '8bit');

        $cipherText = mb_substr($message, $hs, null, '8bit');

        $calculated = hash_hmac(
            self::HASH_ALGO,
            $cipherText,
            $authKey,
            true
        );

        // Compare two strings without leaking timing information.
        if (!hash_equals($mac, $calculated)) {
            throw new Exception('SpryPHP: Decryption failure. Calculated Values to not match.');
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $cipherText = mb_substr($cipherText, $nonceSize, null, '8bit');

        $message = openssl_decrypt(
            $cipherText,
            self::METHOD,
            $encKey,
            OPENSSL_RAW_DATA,
            mb_substr($cipherText, 0, $nonceSize, '8bit')
        );

        if (empty($message) || !is_string($message)) {
            throw new Exception('SpryPHP: Decryption failure. Decrypted value is empty or not formatted correctly.');
        }

        return json_decode($message);
    }
}
