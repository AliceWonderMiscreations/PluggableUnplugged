#!/usr/bin/env php
<?php

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
    print sprintf("define(%s '%s');\n", str_pad($key[$i],19, " "), $salt);
}
?>
