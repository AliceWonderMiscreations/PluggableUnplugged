<?php
declare(strict_types=1);

/**
 * Replacements for some (not all) of the WordPress pluggable.php functions.
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

namespace AWonderPHP\PluggableUnplugged;

/**
 * Static methods of use to pluggable functions and other WordPress plugins
 */
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
    }//end punycodeDomain()

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
    }//end unpunycodeDomain()

    /**
     * Takes a valid international e-mail address and return user@ punycode variant,
     * assuming idn_to_ascii is available.
     *
     * GIGO function, invalid e-mail will not throw exception.
     *
     * @param string $email The e-maill address with domain name to translate into utf8.
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
    }//end punycodeEmail()
    
    /**
     * Creates a nonce that is at least 16 bytes. If a smaller nonce is requested it
     * will return a 16 byte nonce.
     *
     * @param int $bytes The size in bytes of the requested nonce.
     *
     * @return string The base64 encoded nonce.
     */
    public static function generateNonce(int $bytes = 16): string
    {
        if ($bytes < 16) {
            $bytes = 16;
        }
        $raw = random_bytes($bytes);
        return base64_encode($raw);
    }//end generateNonce()
    
    /**
     * A substitute for the WordPress add_query_arg function.
     * This function does NOT support user, pass, or fragment.
     *
     * @param string      $url              The url to modify.
     * @param array       $addQueryArgs     Optional. An array of key value pairs.
     * @param array       $removeQueryArgs  Optional. An array of query args to remove from $url, only key matters.
     * @param null|string $scheme           Optional. The scheme to use. Only supports http or https.
     *
     * @return null|string The url with query args added, or null on failure.
     */
    // @codingStandardsIgnoreLine
    public static function modifyQueryArgs(string $url, array $addQueryArgs = array(), array $removeQueryArgs = array(), $scheme = null)
    {
        $parsed = parse_url($url);
        if (! is_null($scheme)) {
            $scheme = strtolower($scheme);
            if (in_array($scheme, array('http', 'https'))) {
                $parsed['scheme'] = $scheme;
            }
        }
        // todo - throw exception when host is missing
        
        if (isset($parsed['query'])) {
            $queryArray = explode('&', $parsed['query']);
        } else {
            $queryArray = array();
        }
        $newQueryArray = array();
        foreach ($queryArray as $string) {
            $keypair = explode('=', $string);
            $key = $keypair[0];
            $value = $keypair[1];
            if (! in_array($key, $removeQueryArgs)) {
                $newQueryArray[$key] = $value;
            }
        }
        
        foreach ($addQueryArgs as $key => $value) {
            if (! is_bool($value)) {
                $newQueryArray[$key] = $value;
            }
        }
        if (count($newQueryArray) > 0) {
            $arr = array();
            foreach($newQueryArray as $key => $value) {
                $arr[] = $key . '=' . $value;
            }
            $parsed['query'] = implode('&', $arr);
        }
        $url = '';
        $realurl = '';
        if (isset($parsed['scheme'])) {
            $url = $parsed['scheme'] . '://';
            $realurl = $parsed['scheme'] . '://';
        }
        $url = $url . self::punycodeDomain($parsed['host']);
        $realurl = $realurl . $parsed['host'];
        if (isset($parsed['port'])) {
            $url = $url . ':' . $parsed['port'];
            $realurl = $realurl . ':' . $parsed['port'];
        }
        if(! isset($parsed['path'])) {
            $parsed['path'] = '/';
        }
        $url = $url . $parsed['path'];
        $realurl = $realurl . $parsed['path'];
        if (count($newQueryArray) > 0) {
            $url = $url . '?' . $parsed['query'];
            $realurl = $realurl . '?' . $parsed['query'];
        }
        if ($test = filter_var($url, FILTER_VALIDATE_URL)) {
            return $realurl;
        }
        return null;
    }//end modifyQueryArgs()

    /**
     * Creates a cryptographically strong 256 bit salt.
     *
     * @return string The generated salt, a base64 encoded string.
     */
    public static function saltShaker(): string
    {
        $raw = random_bytes(32);
        return base64_encode($raw);
    }//end saltShaker()

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
     * @param string $data  The string to be hashed.
     * @param string $salt  The key (salt) to be used.
     * @param int    $bytes Optional. The length in bytes for the hash. Defaults to
     *                        SODIUM_CRYPTO_GENERICHASH_BYTES.
     *
     * @return string      The base64 encoded hash
     */
    public static function cryptoHash(string $data, string $salt, int $bytes = 0): string
    {
        if ($bytes === 0) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES;
        }
        if ($bytes > SODIUM_CRYPTO_GENERICHASH_BYTES_MAX) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES_MAX;
        }
        if ($bytes < SODIUM_CRYPTO_GENERICHASH_BYTES_MIN) {
            $bytes = SODIUM_CRYPTO_GENERICHASH_BYTES_MIN;
        }
        // We have to do this because the WP supplied salt may not actually
        // be suitable key
        $key = hash('sha256', $salt, true);
        $raw = sodium_crypto_generichash($data, $key, $bytes);
        sodium_memzero($salt);
        sodium_memzero($key);
        return base64_encode($raw);
    }//end cryptoHash()

    /**
     * For use with `wp_rand()` pluggable function. Generates a random integer.
     *
     * @param int $min Optional. The lower limit inclusive.
     * @param int $max Optional. The max limit inclusive.
     *
     * @return int The random number between min and max inclusive
     */
    public static function safeRandInt(int $min = 0, int $max = 0): int
    {
        if ($min > $max) {
            $tmp = $min;
            $min = $max;
            $max = $tmp;
        }
        return random_int($min, $max);
    }//end safeRandInt()

    /**
     * For use with `wp_generate_password()` pluggable function. Generates a random password drawn
     * from the defined set of characters. Always generates a password at least 12 characters long.
     *
     * @param int  $length              Optional. The length of the password. Defaults to 16.
     * @param bool $special_chars       Optional. Whether to include standard special characters.
     *                                  Default True.
     * @param bool $extra_special_chars Optional. Whether to include other special characters.
     *                                  Default False.
     *
     * @return string The generated password.
     */
    // @codingStandardsIgnoreLine
    public static function generatePassword(int $length = 16, bool $special_chars = true, bool $extra_special_chars = false): string
    {
        if ($length < 12) {
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
        for ($i=0; $i< $length; $i++) {
            $rnd = self::safeRandInt(0, $max);
            $password .= $alphabet[$rnd];
        }
        return $password;
    }//end generatePassword()

    /**
     * For use with `wp_hash_password()` pluggable function. Create a hash (encrypt)
     * of a plain text password.
     *
     * @param string $password The plain text password.
     *
     * @return string The hash of the plain text password.
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
    }//end hashPassword()

    /**
     * For use with `wp_check_password()` pluggable function. Checks plain text against encrypted.
     *
     * @param string $password The plain text password.
     * @param string $hash     The hash to check against.
     *
     * @return bool True on valid, False on failure.
     */
    public static function checkPassword(string $password, string $hash): bool
    {
        $return = false;
        if (sodium_crypto_pwhash_str_verify($hash, $password)) {
            $return = true;
        }
        sodium_memzero($password);
        return $return;
    }//end checkPassword()
}//end class

?>