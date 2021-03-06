<?php
declare(strict_types=1);

/**
 * Plugin Name: AWM Pluggable Unplugged
 * Plugin URI:  https://github.com/AliceWonderMiscreations/PluggableUnplugged
 * Description: Replacements for some (not all) of the WordPress pluggable.php functions.
 * Version:     0.3
 * Author:      Alice Wonder Miscreations
 * Author URI:  https://github.com/AliceWonderMiscreations/
 * License:     MIT
 * Licens URI:  https://opensource.org/licenses/MIT
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

require_once(__DIR__ . '/lib/class-tgm-plugin-activation.php');

use \AWonderPHP\Groovytar\WordPressGroovytar as Groovytar;

// make sure PHP has what we need

if (function_exists('sodium_memzero') && (PHP_MAJOR_VERSION >= 7)) {
    /* hash, salt, and nonce functions */
    
    if (! function_exists('wp_salt')) :
        /**
        * Get a salt associated with a specific scheme, setting the salt
        * if need be.
        *
        * @param string $scheme Optional. The scheme want a salt for. Defaults to 'auth'.
        *
        * @psalm-suppress UndefinedFunction
        * @psalm-suppress UndefinedClass
        *
        * @return string The generated salt.
        */
        function wp_salt(string $scheme = 'auth'): string
        {
            static $cached_salts = array();

            if (isset($cached_salts[$scheme])) {
                return apply_filters('salt', $cached_salts[$scheme], $scheme);
            }
        
            static $duplicated_keys;
            if (is_null($duplicated_keys)) {
                $duplicated_keys = array( 'put your unique phrase here' => true );
                foreach (array('AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET') as $first) {
                    foreach (array('KEY', 'SALT') as $second) {
                        if (! defined("${first}_${second}")) {
                            continue;
                        }
                        $value = constant("{$first}_{$second}");
                        $duplicated_keys[$value] = isset($duplicated_keys[$value]);
                    }
                }
            }
            // should these actually be initialized as null?
            $values = array('key' => '', 'salt' => '');
            if (defined('SECRET_KEY') && SECRET_KEY && empty($duplicated_keys[SECRET_KEY])) {
                $values['key'] = 'SECRET_KEY';
            }
            if ($scheme === 'auth' && defined('SECRET_SALT') && SECRET_SALT && empty($duplicated_keys[SECRET_SALT])) {
                $values['salt'] = 'SECRET_SALT';
            }
        
            if (in_array($scheme, array('auth', 'secure_auth', 'logged_in', 'nonce'))) {
                foreach (array('key', 'salt') as $type) {
                    $const = strtoupper("{$scheme}_{$type}");
                    if (defined($const) && constant($const) && empty($duplicated_keys[constant($const)])) {
                        $values[$type] = constant($const);
                    } elseif (! $values[$type]) {
                        $values[$type] = get_site_option("{$scheme}_{$type}");
                        if (! $values[$type]) {
                            $values[$type] = \AWonderPHP\PluggableUnplugged\Misc::saltShaker();
                            update_site_option("{$scheme}_{$type}", $values[$type]);
                        }
                    }
                }
            } else {
                if (! $values['key']) {
                    $values['key'] = get_site_option('secret_key');
                    if (! $values['key']) {
                        $values['key'] = \AWonderPHP\PluggableUnplugged\Misc::saltShaker();
                        update_site_option('secret_key', $values['key']);
                    }
                }
                // WordPress version uses `hash_hmac( 'md5', $scheme, $values['key'] );` here
                //  so I use crytoHash instead but I don't like that very much. However since
                //  this is NOT stored in site_option database, it has to generate to same
                //  value every time so I have to use a hash function.
                $values['salt'] = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::cryptoHash($scheme, $values['key']);
            }
        
            $cached_salts[$scheme] = $values['key'] . $values['salt'];
            // I am unsure what benefit there is to allowing filters on a salt,
            //  does it pose more danger than it is worth?
            return apply_filters('salt', $cached_salts[$scheme], $scheme);
        }//end wp_salt()
    endif;
    
    if (! function_exists('wp_hash')) :
        /**
         * Generates a secure hash of a specified string using the salt associated
         * with a specified scheme.
         *
         * @param string $data   Plain text to hash.
         * @param string $scheme Authentication scheme (auth, secure_auth, logged_in, nonce).
         *
         * @psalm-suppress UndefinedClass
         *
         * @return string Hash of $data.
         */
        function wp_hash(string $data, $scheme = 'auth')
        {
            $salt = wp_salt($scheme);
            return \AWonderPHP\PluggableUnplugged\UnpluggedStatic::cryptoHash($data, $salt, 16);
        }//end wp_hash()
    endif;
    
    if (! function_exists('wp_nonce_tick')) :
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
         * @psalm-suppress UndefinedFunction
         *
         * @return int Value rounded up to the next highest integer.
         */
        function wp_nonce_tick(): int
        {
            $nonce_life = apply_filters('nonce_life', 10800);
            $return = ceil(time() / ( $nonce_life / 2 ));
            return intval($return, 10);
        }//end wp_nonce_tick()
    endif;
    
    if (! function_exists('wp_create_nonce')) :
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
         * @psalm-suppress UndefinedFunction
         *
         * @return string The base64 encoded 128-bit nonce token.
         */
        function wp_create_nonce($action = -1): string
        {
            $user = wp_get_current_user();
            // check to see if we really need to recast
            $uid  = (int) $user->ID;
            if (! $uid) {
                /** This filter is documented in wp-includes/pluggable.php */
                $uid = apply_filters('nonce_user_logged_out', $uid, $action);
            }
            $token = wp_get_session_token();
            $i = wp_nonce_tick();
            $str = $i . '|' . $action . '|' . $uid . '|' . $token;
            $prenonce = wp_hash($str, 'nonce');
            // effing WordPress sometimes puts the nonce in GET variables
            $nonce = preg_replace('/[^A-Za-z0-9]/', '', $prenonce);
            return $nonce;
        }//end wp_create_nonce()
    endif;
    
    if (! function_exists('wp_verify_nonce')) :
        /**
         * Verifies the correct nonce was used and has not expired.
         *
         * @param string     $nonce  The nonce to verify.
         * @param string|int $action The context of the nonce.
         *
         * @psalm-suppress UndefinedFunction
         *
         * @return false|int False on failure, 1 if valid and recent, 2 if valid but old
         */
        function wp_verify_nonce($nonce, $action = -1)
        {
            $user = wp_get_current_user();
            $uid  = (int) $user->ID;
            if (! $uid) {
                $uid = apply_filters('nonce_user_logged_out', $uid, $action);
            }
            $token = wp_get_session_token();
            $i = wp_nonce_tick();
            $str = $i . '|' . $action . '|' . $uid . '|' . $token;
            $prenonce = wp_hash($str, 'nonce');
            $expected = preg_replace('/[^A-Za-z0-9]/', '', $prenonce);
            if (hash_equals($expected, $nonce)) {
                return 1;
            }
            $i--;
            $str = $i . '|' . $action . '|' . $uid . '|' . $token;
            $prenonce = wp_hash($str, 'nonce');
            $expected = preg_replace('/[^A-Za-z0-9]/', '', $prenonce);
            if (hash_equals($expected, $nonce)) {
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
            do_action('wp_verify_nonce_failed', $nonce, $action, $user, $token);
            return false;
        }//end wp_verify_nonce()
    endif;

    /* Password Functions */
    if ($useArgonHashing=get_option('PluggableUnpluggedUseArgon')) {
        if (! function_exists('wp_set_password')) :
            /**
             * Updates user password with new hash.
             *
             * @param string $password The plain text password.
             * @param int    $user_id  User ID.
             *
             * @psalm-suppress UndefinedFunction
             * @psalm-suppress UndefinedClass
             *
             * @return void
             */
            function wp_set_password(string $password, int $user_id): void
            {
                // fixme throw exception if bad/weak password
                global $wpdb;
                $hash = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::hashPassword($password);
                $wpdb->update(
                    $wpdb->users,
                    array(
                    'user_pass'           => $hash,
                    'user_activation_key' => '',
                    ),
                    array( 'ID' => $user_id )
                );
                wp_cache_delete($user_id, 'users');
            }//end wp_set_password()
        endif;
    
        if (! function_exists('wp_hash_password')) :
            /**
             * Create a hash of a plain text password using sodium.
             *
             * The WordPress version of this function calls a filter called 'random_password'
             * which takes $password as an argument. That is dangerous, so this function
             * does not use that filter.
             *
             * @param string $password The password to hash.
             *
             * @psalm-suppress UndefinedClass
             *
             * @return string The hashed password
             */
            function wp_hash_password($password)
            {
                $hash = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::hashPassword($password);
                sodium_memzero($password);
                return $hash;
            }//end wp_hash_password()
        endif;
    
        if (! function_exists('wp_check_password')) :
            /**
             * Checks the plain text password against the hashed password, updating old
             * versions of the hashing algorithm if necessary to argon2id.
             *
             * The WordPress version of this function calls a filter called 'check_password'
             * which takes $password as an argument. That is dangerous, so this function
             * does not use that filter.
             *
             * @param string     $password The password to be checked.
             * @param string     $hash     The hash to be checked.
             * @param null|int   $user_id  Optional. The user id to match against.
             *
             * @psalm-suppress UndefinedClass
             * @psalm-suppress UndefinedConstant
             * @psalm-suppress UnresolvableInclude
             *
             * @return bool True on success, False on failure.
             */
            function wp_check_password(string $password, string $hash, $user_id = null): bool
            {
                global $wp_hasher;
    
                // Insane if md5 still being used...
                if (ctype_xdigit($hash)) {
                    if (strlen($hash) === 32) {
                        //assume md5sum
                        $check = hash_equals($hash, md5($password));
                        if ($check) {
                            if (is_int($user_id)) {
                                wp_set_password($password, $user_id);
                            }
                        }
                        sodium_memzero($password);
                        return $check;
                    }
                }
                $str = substr($hash, 0, 12);
                if ($str !== '$argon2id$v=') {
                    // doing things the old way, eh?
                    if (empty($wp_hasher)) {
                        require_once(ABSPATH . WPINC . '/class-phpass.php');
                        $wp_hasher = new \PasswordHash(8, true);
                    }
                    $check = $wp_hasher->CheckPassword($password, $hash);
                    if ($check) {
                        if (is_int($user_id)) {
                            wp_set_password($password, $user_id);
                        }
                    }
                    sodium_memzero($password);
                    return $check;
                }
                // doing things the right way
                $check = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::checkPassword($password, $hash);
                if ($check) {
                    if (is_int($user_id)) {
                        // 20% of time this will recreate the hash
                        $random = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::safeRandInt(0, 4);
                        if ($random === 3) {
                            wp_set_password($password, $user_id);
                        }
                    }
                }
                sodium_memzero($password);
                return $check;
            }//end wp_check_password()
        endif;
    
        if (! function_exists('wp_generate_password')) :
            /**
             * Generates a random password from defined characters
             *
             * @param int  $length              Optional. The length of the password.
             * @param bool $special_chars       Optional. Whether to include standard special characters.
             *                                  Default True.
             * @param bool $extra_special_chars Optional. Whether to include other special characters.
             *                                  Default False.
             *
             * @psalm-suppress UndefinedClass
             *
             * @return string The generated password.
             */
            // @codingStandardsIgnoreLine
            function wp_generate_password(int $length = 12, bool $special_chars = true, bool $extra_special_chars = false): string
            {
                $password = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::generatePassword(
                    $length,
                    $special_chars,
                    $extra_special_chars
                );
                return $password;
            }//end wp_generate_password()
        endif;
    }

    /* Misc function */
    
    if (! function_exists('wp_rand')) :
        /**
         * Generates a random number.
         *
         * @param int $min Lower limit for the generated number. Defaults to 0.
         * @param int $max Upper limit for the generated number. Defaultd to 0.
         *
         * @psalm-suppress UndefinedClass
         *
         * @return int A random number between the two parameters.
         */
        function wp_rand(int $min = 0, int $max = 0): int
        {
            return \AWonderPHP\PluggableUnplugged\UnpluggedStatic::safeRandInt($min, $max);
        }//end wp_rand()
    endif;
    
    /* Gravatar functions */
    
    if (! function_exists('get_avatar')) :
        /**
         * Replacement for the pluggable.php get_avatar function.
         *
         * @param mixed      $id_or_email The e-mail address to generate an avatar for or
         *                                possibly an object we can use to fetch the e-mail.
         * @param int        $size        Optional. The size of the avatar in pixels. Defaults to 96.
         * @param string     $default     Optional. The default type of avatar to use. Defaults to
         *                                system setting or 'monsterid'.
         * @param string     $alt         Optional. The img alt tag to use.
         * @param null|array $args        Optional. Array of argument that impact creation of img node.
         *
         * @psalm-suppress UndefinedFunction
         * @psalm-suppress UndefinedClass
         *
         * @return string The img tag to use.
         */
        function get_avatar($id_or_email, $size = 96, $default = '', $alt = '', $args = null): string
        {
            /* Gravatar Obfuscation */
            $groovytar = new Groovytar();
            if (! $whitedomains=get_option('groovytarDomains')) {
                $whitedomains=array();
            }
            foreach ($whitedomains as $input) {
                try {
                    $groovytar->addDomain($input);
                } catch (\InvalidArgumentException $e) {
                    error_log($e->getMessage());
                }
            }
            if (! $whiteaddresses=get_option('groovytarAddresses')) {
                $whiteaddresses=array();
            }
            foreach ($whiteaddresses as $input) {
                try {
                    $groovytar->addEmailAddress($input);
                } catch (\InvalidArgumentException $e) {
                    error_log($e->getMessage());
                }
            }
        
            if (is_null($args)) {
                $args = array();
            }
            $args['size']    = (int) $size;
            $args['default'] = $default;
            $args['alt']     = $alt;
            if (! isset($args['default'])) {
                $args['default'] = get_option('avatar_default', 'monsterid');
            }
            if (empty($args['default'])) {
                $args['default'] = get_option('avatar_default', 'monsterid');
            }
            if (! isset($args['force_default'])) {
                $args['force_default'] = false;
            }
            $args['force_default'] = (bool) $args['force_default'];
            if (! isset($args['rating'])) {
                $args['rating'] = 'g';
                $args['rating'] = strtolower($args['rating']);
            }
            if (! isset($args['scheme'])) {
                $args['scheme'] = null;
            }
            if (! isset($args['class'])) {
                $args['class'] = null;
            }
            if (! isset($args['force_display'])) {
                $args['force_display'] = false;
            }
            $args['force_display'] = (bool) $args['force_display'];
            if (! isset($args['extra_attr'])) {
                $args['extra_attr'] = '';
            }
        
            // TODO?? - pre_get
        
            if (! $args['force_display'] && ! get_option('show_avatars')) {
                return false;
            }
            $url2x = $groovytar->getAvatarUrl($id_or_email, array_merge($args, array( 'size' => $args['size'] * 2 )));
            $args = $groovytar->getAvatarData($id_or_email, $args);
            $url = $args['url'];
            if (! $url || is_wp_error($url)) {
                return false;
            }
        
            $class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );
            if (! $args['found_avatar'] || $args['force_default']) {
                $class[] = 'avatar-default';
            }
            if ($args['class']) {
                if (is_array($args['class'])) {
                    $class = array_merge($class, $args['class']);
                } else {
                    $class[] = $args['class'];
                }
            }
            $avatar = sprintf(
                "<img alt='%s' src='%s' srcset='%s' class='%s' height='%d' width='%d' %s/>",
                esc_attr($args['alt']),
                esc_url($url),
                esc_url($url2x) . ' 2x',
                esc_attr(join(' ', $class)),
                (int) $args['height'],
                (int) $args['width'],
                $args['extra_attr']
            );
            return $avatar;
        }//end get_avatar()

    endif;
    
    
    /**
     * The admin interface options menu.
     *
     * @psalm-suppress UndefinedFunction
     *
     * @psalm-suppress UndefinedClass
     *
     * @return void
     */
    function pluggableUnpluggedAdminOptions()
    {
        if (! current_user_can('manage_options')) {
            wp_die(__('What does the fox say?'));
        }
        if (isset($_POST['pluggableUnpluggedAuthKey'])) {
            $chk=trim($_POST['pluggableUnpluggedAuthKey']);
            $key=trim(get_option('pluggableUnpluggedAuthKey'));
            if (strcmp($chk, $key) == 0) {
                \AWonderPHP\PluggableUnplugged\AdminMenu::processForm();
            }
        }
        echo('<div class="wrap">' . "\n");
        // @codingStandardsIgnoreLine
        echo('<div id="icon-options-general" class="icon32"><br /></div><h2>Pluggable Unplugged Administration</h2>' . "\n");
        // @codingStandardsIgnoreLine
        echo('<form id="pluggableUnpluggedForm" method="post" action="options-general.php?page=PluggableUnpluggable">' . "\n");
        $key=\AWonderPHP\PluggableUnplugged\Misc::generateNonce(24);
        update_option('pluggableUnpluggedAuthKey', $key);
        // @codingStandardsIgnoreLine
        echo('<input type="hidden" name="pluggableUnpluggedAuthKey" id="pluggableUnpluggedAuthKey" value="' . $key . '" />' . "\n");
    
        \AWonderPHP\PluggableUnplugged\AdminMenu::domainMenu();
        \AWonderPHP\PluggableUnplugged\AdminMenu::addressMenu();
        \AWonderPHP\PluggableUnplugged\AdminMenu::saltMenu();
        \AWonderPHP\PluggableUnplugged\AdminMenu::switchToArgon();
        \AWonderPHP\PluggableUnplugged\AdminMenu::userNotify();
    
        // @codingStandardsIgnoreLine
        echo('<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" /></p>');
        echo("</form>\n");
        echo("</div>\n");
    }//end pluggableUnpluggedAdminOptions()
    
    /**
     * Options page for admin menu
     *
     * @psalm-suppress UndefinedFunction
     *
     * @return void
     */
    function pluggableUnpluggedAdminMenu()
    {
        add_options_page(
            'PluggableUnpluggable Administration',
            'PluggableUnpluggable',
            'manage_options',
            'PluggableUnpluggable',
            'pluggableUnpluggedAdminOptions'
        );
        //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
    }//end pluggableUnpluggedAdminMenu()


    add_action('admin_menu', 'pluggableUnpluggedAdminMenu');
    
    if ($footerPermission=get_option('PluggableUnpluggedFooter')) {
        add_action('wp_footer', '\AWonderPHP\Groovytar\WordPressGroovytar::groovytarFooter');
    }
    
    /**
     * TGMPA menu
     *
     * @return void
     */
    function pluggableUnpluggedTGMPA()
    {
        $plugins = array(
            array(
                'name' => 'Disable Emojis', //name
                'slug' => 'disable-emojis', //slug
                'required' => false,
            ),
        );
        $config = array(
            'id' => 'awm-pluggable-unplugged',
            'default_path' => '',
            'menu' => 'tgmpa-install-plugins',
            'parent_slug' => 'plugins.php',
            'capability' => 'manage_options',
            'has_notices' => 'true',
            'dismissable'  => true,
            'dismiss_msg'  => '',
            'is_automatic' => true,
            'message'      => '',
            
        );
        tgmpa($plugins, $config);
    }//end pluggableUnpluggedTGMPA()

    add_action('tgmpa_register', 'pluggableUnpluggedTGMPA');
} else {
    error_log('The AWM Pluggable Unplugged plugin requires PHP 7+ with the libsodium PECL extension.');
}

?>