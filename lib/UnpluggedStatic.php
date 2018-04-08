<?php

namespace AWonderPHP\PluggableUnplugged;

class UnpluggedStatic
{
    /**
     * Takes a valid domain name and returns punycode variant, assuming the
     * idn_to_ascii function is available.
     *
     * GIGO function, invalid domain name will not throw exception.
     *
     * @param string $domain The domain name to translate into punycode.
     *
     * @return string The ascii punycode version of the domain name.
     */
    public static function punycodeDomain(string $domain): string
    {
        if (function_exists('idn_to_ascii')) {
            $domain=idn_to_ascii($domain);
        }
        return $domain;
    }
    
    /**
     * Takes a valid ascii domain name and returns UTF-8 variant, assuming the
     * idn_to_utf8 function is available.
     *
     * GIGO function, invalid domain name will not throw exception.
     *
     * @param string $domain The domain name to translate into utf8.
     *
     * @return string The utf8 variant of the domain name.
     */
    public static function unpunycodeDomain(string $domain): string
    {
        if (function_exists('idn_to_utf8')) {
            $domain=idn_to_utf8($domain);
        }
        return $domain;
    }
    /**
     * Takes a valid international e-mail address and return user@ punycode variant,
     * assuming idn_to_ascii is available.
     *
     * GIGO function, invalid e-mail will not throw exception.
     *
     * @param string $domain The domain name to translate into utf8.
     *
     * @return string The e-mail with ascii variant of the domain name
     */
    public static function punycodeEmail(string $email): string
    {
        if (substr_count($email, '@') === 1) {
            $tmp=explode('@', $email);
            $email=$tmp[0] . '@' . self::punycodeDomain($tmp[1]);
        }
        return $email;
    }
    
    /**
     * Creates a nonce that is at least 16 bytes. If a smaller nonce is requested it
     * will return a 16 byte nonce.
     *
     * @param int $bytes The size in bytes of the requested nonce.
     *
     * @return string The base64 encoded nonce.
     */
    public static function generateNonce(int $bytes=16): string
    {
        if($bytes < 16) {
            $bytes = 16;
        }
        $raw = random_bytes($bytes);
        return base64_encode($raw);
    }
    
    /**
     * Creates a cryptographically strong 256 bit salt.
     *
     * @return string The generated salt, a base64 encoded string.
     */
    public static function saltShaker(): string
    {
        $raw = random_bytes(32);
        return base64_encode($raw);
    }
    
    /* Pluggable Methods */
    
    /**
     * For use with `wp_hash()` pluggable function. Creates a secure hash string if the
     * specified number of bytes.
     *
     * If the specified number of bytes is < SODIUM_CRYPTO_GENERICHASH_BYTES_MIN
     * then SODIUM_CRYPTO_GENERICHASH_BYTES_MIN is used.
     *
     * If the specified number of bytes is > SODIUM_CRYPTO_GENERICHASH_BYTES_MAX
     * then SODIUM_CRYPTO_GENERICHASH_BYTES_MAX is used.
     *
     * If the specified number of bytes us null then SODIUM_CRYPTO_GENERICHASH_BYTES
     * is used.
     *
     * @param string $data The string to be hashed.
     * @param string $key  The key (salt) to be used.
     * @param null|int $bytes Optional. The length in bytes for the hash. Defaults to
     *                        SODIUM_CRYPTO_GENERICHASH_BYTES.
     *
     * @return string      The base64 encoded hash
     */
    public static function cryptoHash(string $data, string $salt, $bytes=null): string
    {
        if(is_null($bytes)) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES;
        }
        if(! is_numeric($bytes)) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES;
        }
        $bytes = (int) $bytes;
        if($bytes > SODIUM_CRYPTO_GENERICHASH_BYTES_MAX) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES_MAX;
        }
        if($bytes < SODIUM_CRYPTO_GENERICHASH_BYTES_MIN) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES_MIN;
        }
        // We have to do this because the WP supplied salt may not actually
        // be suitable key
        $key = hash('sha256', $salt, true);
        $raw = sodium_crypto_generichash($data, $key, $bytes);
        sodium_memzero($key);
        return base64_encode($raw);
    }
    
    /**
     * For use with `wp_rand()` pluggable function. Generates a random integer.
     *
     * @param int $min Optional. The lower limit inclusive.
     * @param int $max Optional. The max limit inclusive.
     *
     * @return The random number between min and max inclusive
     */
    public static function safeRandInt(int $min=0, int $max=0): int
    {
        if($min > $max) {
            $tmp = $min;
            $min = $max;
            $max = $min;
        }
        return random_int($min, $max);
    }
    
    /**
     * For use with `wp_generate_password()` pluggable function. Generates a random password drawn
     * from the defined set of characters. Always generates a password at least 12 characters long.
     *
     * @param int  $length              Optional. The length of the password.
     * @param bool $special_chars       Optional. Whether to include standard special characters.
     *                                  Default True.
     * @param bool $extra_special_chars Optional. Whether to include other special characters.
     *                                  Default False.
     *
     * @return string The generated password.
     */
     public static function generatePassword(int $length = 12, bool $special_chars = true, bool $extra_special_chars = false): string
     {
        if($length < 12) {
            $length = 12;
        }
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $alphabet .= '!@#$%^&*()';
        }
        if ($extra_special_chars) {
            $alphabet .= '-_ []{}<>~`+=,.;:/?|';
        }
        $alphabet = str_shuffle($alphabet);
        $max = (strlen($alphabet) - 1);
        $password = '';
        for($i=0; $i<= $length; $i++) {
            $rnd = self::safeRandInt(0, $max);
            $password .= $alphabet[$rnd];
        }
        return $password;
     }
     
    /**
     * For use with `wp_hash_password()` pluggable function. Create a hash (encrypt)
     * of a plain text password.
     *
     * @param string $password The plain text password
     *
     * @return string The hash of the plain text password
     */
    public static function hashPassword(string $password): string
    {
        $hash_str = sodium_crypto_pwhash_str(
            $password,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        sodium_memzero($password);
        return $hash_str;
    }
    
    /**
     * For use with `wp_check_password()` pluggable function. Checks plain text against encrypted.
     *
     * @param string $password The plain text password
     * @param string $hash     The hash to check against
     *
     * @return bool True on valid, False on failure
     */
    public static function checkPassword(string $password, string $hash): bool
    {
        $return = false;
        if (sodium_crypto_pwhash_str_verify($hash, $password)) {
            $return = true;
        }
        sodium_memzero($password);
        return $return;
    }
     
}


























?>