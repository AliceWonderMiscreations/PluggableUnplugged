<?php

/**
 * Replacements for some (not all) of the WordPress pluggable.php functions
 *
 */

// make sure PHP has what we need

if (function_exists('sodium_memzero') && (PHP_MAJOR_VERSION >= 7)) {
  
    require_once(__DIR__ . '/lib/UnpluggedStatic.php');
  
    use \AWonderPHP\PluggableUnplugged\UnpluggedStatic as UnpluggedStatic;
  
    /* hash, salt, and nonce functions */
    
    /**
     * Get a salt associated with a specific scheme, setting the salt
     * if need be.
     *
     * @param string $scheme Optional. The scheme want a salt for. Defaults to 'auth'.
     *
     * @return string The generated salt.
     */
    function wp_salt(string $scheme = 'auth'): string
    {
        static $cached_salts = array();
        if(isset($cached_salts[$scheme])) {
            return apply_filters('salt', $cached_salts[$scheme], $scheme);
        }
        
        static $duplicated_keys;
        if(is_null($duplicated_keys)) {
            $duplicated_keys = array( 'put your unique phrase here' => true );
            foreach(array('AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET') as $first) {
                foreach(array('KEY', 'SALT') as $second) {
                    if(! defined("${first}_${second}")) {
                        continue;
                    }
                    $value = constant("{$first}_{$second}");
                    $duplicated_keys[$value] = isset($duplicated_keys[$value]);
                }
            }
        }
        // should these actually be initialized as null?
        $values = array('key' => '', 'salt' => '');
        if(defined('SECRET_KEY') && SECRET_KEY && empty($duplicated_keys[SECRET_KEY])) {
            $values['key'] = 'SECRET_KEY';
        }
        if($scheme === 'auth' && defined('SECRET_SALT') && SECRET_SALT && empty($duplicated_keys[SECRET_SALT])) {
            $values['salt'] = 'SECRET_SALT';
        }
        
        if(in_array($scheme, array('auth', 'secure_auth', 'logged_in', 'nonce'))) {
            foreach(array('key', 'salt') as $type) {
                $const = strtoupper( "{$scheme}_{$type}" );
                if(defined($const) && constant($const) && empty($duplicated_keys[constant($const))) {
                    $values[$type] = constant($const);
                } elseif(! $values[$type]) {
                    $values[$type] = get_site_option("{$scheme}_{$type}");
                    if(! $values[$type]) {
                        $values[$type] = UnpluggedStatic::saltShaker();
                        update_site_option("{$scheme}_{$type}", $values[$type]);
                    }
                }
            }
        } else {
            if(! $values['key']) {
                $values['key'] = get_site_option('secret_key');
                if(! $values['key']) {
                    $values['key'] = UnpluggedStatic::saltShaker();
                    update_site_option('secret_key', $values['key']);
                }
            }
            // WordPress version uses `hash_hmac( 'md5', $scheme, $values['key'] );` here
            //  so I use crytoHash instead but I don't like that very much. However since
            //  this is NOT stored in site_option database, it has to generate to same
            //  value every time so I have to use a hash function.
            $values['salt'] = UnpluggedStatic::cryptoHash($scheme, $values['key']);
        }
        
        $cached_salts[$scheme] = $values['key'] . $values['salt'];
        // I am unsure what benefit there is to allowing filters on a salt,
        //  does it pose more danger than it is worth?
        return apply_filters('salt', $cached_salts[$scheme], $scheme);
    }
    
    /**
     * Generates a secure hash of a specified string using the salt associated
     * with a specified scheme
     *
     * @param string $data   Plain text to hash
     * @param string $scheme Authentication scheme (auth, secure_auth, logged_in, nonce)
     *
     * @return string Hash of $data
     */
    function wp_hash(string $data, $scheme = 'auth') {
        $salt = wp_salt($scheme);
        return UnpluggedStatic::cryptoHash($data, $salt, 16);
    }
    
    /**
     * Get the time-dependent variable for nonce creation.
     *
     * A WordPress CSRF nonce has a lifespan of two ticks.
     *
     * Default WP uses 24 hour by default, that's bad. Using 3 hours.
     * Default WP return float that is an integer. This return an int.
     * The returned value will always be smaller than time() so even
     * after 2038 there is no need to return a float.
     *
     * @return int Value rounded up to the next highest integer.
     */
    function wp_nonce_tick(): int
    {
        $nonce_life = apply_filters('nonce_life', 10800);
        $return = ceil( time() / ( $nonce_life / 2 ) );
        return intval($return, 10);
    }

    /**
     * Creates a cryptographic token tied to a specific action, user, user session,
     * and window of time.
     *
     * The WordPress version of this function produces a weak 10 character nonce
     * that is equivalent to just 5 bytes. This version of the function produces a
     * 16 byte nonce which is the best practice for a nonce.
     *
     * @param string|int $action Scalar value to add context to the nonce.
     *
     * @return string The base64 encoded 128-bit nonce token.
     */
    function wp_create_nonce($action = -1): string
    {
        $user = wp_get_current_user();
        // check to see if we really need to recast
        $uid  = (int) $user->ID;
        if ( ! $uid ) {
            /** This filter is documented in wp-includes/pluggable.php */
            $uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
        }
        $token = wp_get_session_token();
        $i = wp_nonce_tick();
        $str = $i . '|' . $action . '|' . $uid . '|' . $token;
        return wp_hash($str, 'nonce');
    }
    
    /**
     * Verifies the correct nonce was used and has not expired.
     *
     * @param string     $nonce  The nonce to verify.
     * @param string|int $action The context of the nonce.
     *
     * @return false|int False on failure, 1 if valid and recent, 2 if valid but old
     */
    function wp_verify_nonce($nonce, $action = -1)
    {
        $user = wp_get_current_user();
        $uid  = (int) $user->ID;
        if ( ! $uid ) {
            $uid = apply_filters('nonce_user_logged_out', $uid, $action);
        }
        $token = wp_get_session_token();
        $i = wp_nonce_tick();
        $str = $i . '|' . $action . '|' . $uid . '|' . $token;
        $expected = wp_hash($str, 'nonce');
        if(hash_equals($expected, $nonce)) {
            return 1;
        }
        $i--;
        $str = $i . '|' . $action . '|' . $uid . '|' . $token;
        $expected = wp_hash($str, 'nonce');
        if(hash_equals($expected, $nonce)) {
            return 2;
        }
        /**
         * Fires when nonce verification fails.
         *
         * @param string     $nonce  The invalid nonce.
         * @param string|int $action The nonce action.
         * @param WP_User    $user   The current user object.
         * @param string     $token  The user's session token.
         */
        do_action( 'wp_verify_nonce_failed', $nonce, $action, $user, $token );
        return false;
    }
    
    // BETTER nonce functions for CSRF but would break some plugins
    // if I did these as default
    
    /**
     * Create a cryptographically strong CSRF nonce.
     *
     * @param int $ttl       Optional. The time in seconds the nonce token is valid for.
     *                       Defaults to 3 hours. Admin functions should probably be
     *                       shorter.
     * @param string $action Optional. Defaults to 'generic'.
     *
     * @return string The 128-bit nonce token.
     */
    function awm_create_nonce(int $ttl = 10800, string $action = 'generic'): string
    {
        $user = wp_get_current_user();
        $action = trim(strtolower($action));
        if(strlen($action) === 0) {
            $action = 'generic';
        }
        $nonce_type = $action . '_nonces';
        // TODO check to see if always created for non-logged in users
        $wp_session = \WP_Session::get_instance();
        if (! isset($wp_session[$nonce_type])) {
            $wp_session[$nonce_type] = array();
        }
        $nonce = UnpluggedStatic::generateNonce();
        $expires = time() + $ttl;
        $wp_session[$nonce_type][$nonce] = $expires;
        return $nonce;
    }
    
    /**
     * Validate that a particular nonce is valid, and invalidate it after
     * validation.
     *
     * @param string $nonce  The nonce to validate.
     * @param string $action Optional. Defaults to 'generic'.
     *
     * @return bool True if the nonce was valid, otherwise False.
     */
    function awm_verify_nonce(string $nonce, string $action = 'generic'): bool
    {
        $user = wp_get_current_user();
        $action = trim(strtolower($action));
        if(strlen($action) === 0) {
            $action = 'generic';
        }
        $nonce_type = $action . '_nonces';
        // TODO check to see if always created for non-logged in users
        //  and destroyed upon logged in user logout
        $wp_session = \WP_Session::get_instance();
        if (! isset($wp_session[$nonce_type])) {
            return false;
        }
        if (! isset($wp_session[$nonce_type][$nonce])) {
            return false;
        }
        if(! is_numeric($wp_session[$nonce_type][$nonce])) {
            return false;
        }
        $expires = intval($wp_session[$nonce_type][$nonce], 10);
        if ($expires < time()) {
            return false;
        }
        $wp_session[$nonce_type][$nonce] = 0;
        return true;
    }

    /* Password Functions */

    /**
     * Updates user password with new hash.
     *
     * @param string $password The plain text password
     * @param int    $user_id  User ID
     *
     * @return void
     */
    function wp_set_password(string $password, int $user_id): void
    {
        // fixme throw exception if bad/weak password
        global $wpdb;
        $hash = UnpluggedStatic::hashPassword($password);
        $wpdb->update(
            $wpdb->users, array(
                'user_pass'           => $hash,
                'user_activation_key' => '',
            , array( 'ID' => $user_id )
        );
        wp_cache_delete($user_id, 'users');
    }

    /**
     * Create a hash of a plain text password using sodium.
     *
     * The WordPress version of this function calls a filter called 'random_password'
     * which takes $password as an argument. That is dangerous, so this function
     * does not use that filter.
     *
     * @input string $password The password to hash
     *
     * @return string The hashed password
     */
    function wp_hash_password($password) {
        $hash = UnpluggedStatic::hashPassword($password);
        sodium_memzero($password);
        return $hash;
    }

    /**
     * Checks the plain text password against the hashed password, updating old
     * versions of the hashing algorithm if necessary to argon2id.
     *
     * The WordPress version of this function calls a filter called 'check_password'
     * which takes $password as an argument. That is dangerous, so this function
     * does not use that filter.
     *
     * @param string     $password The password to be checked
     * @param string     $hash     The hash to be checked
     * @param null|int   $user_id  Optional. The user id to match against
     *
     * @return bool True on success, False on failure.
     */
    function wp_check_password(string $password, string $hash, $user_id = null): bool
    {
        global $wp_hasher;
    
        // Insane if md5 still being used...
        if(ctype_xdigit($hash)) {
            if(strlen($hash) === 32) {
                //assume md5sum
                $check = hash_equals($hash, md5($password));
                if ($check) {
                    if(is_int($user_id)) {
                        wp_set_password($password, $user_id);
                    }
                }
                sodium_memzero($password);
                return $check;
            }
        }
        $str = substr($hash, 0, 12);
        if($str !== '$argon2id$v=') {
            // doing things the old way, eh?
            if (empty($wp_hasher)) {
                require_once( ABSPATH . WPINC . '/class-phpass.php' );
                $wp_hasher = new \PasswordHash(8, true);
            }
            $check = $wp_hasher->CheckPassword($password, $hash);
            if ($check) {
                if(is_int($user_id)) {
                    wp_set_password($password, $user_id);
                }
            }
            sodium_memzero($password);
            return $check;
        }
        // doing things the right way
        $check = UnpluggedStatic::checkPassword($password, $hash);
        if ($check) {
            if(is_int($user_id)) {
                // 20% of time this will recreate the hash
                $random = UnpluggedStatic::safeRandInt(0, 4);
                if($random === 3) {
                    wp_set_password($password, $user_id);
                }
            }
        }
        sodium_memzero($password);
        return $check;
    }

    /**
     * Generates a random password from defined characters
     *
     * @param int  $length              Optional. The length of the password.
     * @param bool $special_chars       Optional. Whether to include standard special characters.
     *                                  Default True.
     * @param bool $extra_special_chars Optional. Whether to include other special characters.
     *                                  Default False.
     *
     * @return string The generated password.
     */

    function wp_generate_password(int $length = 12, bool $special_chars = true; bool $extra_special_chars = false): string
    {
        $password = UnpluggedStatic::generatePassword($length, $special_chars, $extra_special_chars);
        return $password;
    }
    
    /* Misc function */
    
    /**
     * Generates a random number.
     *
     * @param int $min Lower limit for the generated number. Defaults to 0.
     * @param int $max Upper limit for the generated number. Defaultd to 0.
     *
     * @return int A random number between the two parameters.
     */
    function wp_rand(int $min=0, int $max=0): int
    {
        return UnpluggedStatic::safeRandInt($min, $max);
    }
}

?>