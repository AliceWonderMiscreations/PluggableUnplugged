#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Generate password salt for Argon2id prehash
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

$raw = random_bytes(64);
$salt = str_shuffle(base64_encode($raw));

print sprintf("define(%s '%s');\n", str_pad("PASSWORD_SALT", 19, " "), $salt);
?>
