#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Generate salts for wp-config.php file.
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

$key = array(
    '\'AUTH_KEY\',',
    '\'SECURE_AUTH_KEY\',',
    '\'LOGGED_IN_KEY\',',
    '\'NONCE_KEY\',',
    '\'AUTH_SALT\',',
    '\'SECURE_AUTH_SALT\',',
    '\'LOGGED_IN_SALT\',',
    '\'NONCE_SALT\','
);

for ($i=0; $i<8; $i++) {
    $raw = random_bytes(64);
    $salt = str_shuffle(base64_encode($raw));
    print sprintf("define(%s '%s');\n", str_pad($key[$i], 19, " "), $salt);
}
?>
