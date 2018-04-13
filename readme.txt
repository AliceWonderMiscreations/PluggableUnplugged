=== PluggableUnplugged ===
Contributors: AliceWonderMiscreations
Tags: gravatar, privacy, security, argon2
Donate link: https://github.com/AliceWonderMiscreations/PluggableUnplugged/blob/master/donate.md
Requires at least: 4.9.0
Tested up to: 4.9.5
Requires PHP 7.0
Stable tag: trunk
License: MIT
License URI: https://opensource.org/licenses/MIT

This plugin provides better CSRF token management, gravatar obfuscation, and optional Argon2id password hashing.
This plugin requires the PECL libsodium extension to function. It is worth it, and is built by default in PHP 7.2+.

== Description ==

This plugin replaces some of the functions in the `pluggable.php` file with better alternatives:

* `wp_nonce_tick`
* `wp_verify_nonce`
* `wp_create_nonce`
* `wp_salt`
* `wp_hash`
* `wp_rand`
* `get_avatar`

Optionally, it alsp replaces the password related functions to use the `Argon2id` hashing algorithm.
Please note that if you choose to transition to `Argon2id` password hashing and then decide to remove
this plugin, all your users will need to go through password recovery to recreate their password hashes
using the default WordPress hashing algorithm.

So please do not enable Argon2id hashing on a whim, think about it first.

The only real world benefit is if an attacker manages to get your database, Argon2id makes dictionary
attacks against the hashes harder.

Password related `pluggable.php` functions optionally replaced if you explicitly opt in to Argon2id
password hashing:

* `wp_hash_password`
* `wp_check_password`
* `wp_generate_password`
* `wp_set_password`

== Installation ==

1. Place the directory containing this plugin and libs in the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin through the 'PluggableUnpluggable' sub-menu of 'Settings'
4. If you so choose, check the box near the bottom of the admin page to `Use the Argon2id
   Password Hashing Algorithm' to upgrade your WordPress password hashing.
5. If you so choose, check the box at bottom of admin page to 'Allow Notification to Users of
   PluggableUnplugged usage' so users of your blog will know you care about their privacy.

== Development ==

Development is taking place at Github:
[https://github.com/AliceWonderMiscreations/PluggableUnplugged](https://github.com/AliceWonderMiscreations/PluggableUnplugged)
A more extensive README.md file is located there.

== Gravatar Note ==

Gravatar is a massive privacy leak. If anyone knows your e-mail address, they can obtain the `md5()`
hash of your e-mail hash and write bots that search the Internet for WordPress blogs you have left
comments on.

Gravatar also uses tracking cookies.

The tracking cookie problem is not yes solved, but there are plans to solve that issue in a future
release. The hash problem however has been solved.

An e-mail address is hashed using a blog specific salt, and that hash is then hashed again using a
second blog specific salt. A 32 character substring that mimics a `md5()` hash is taken from that
second hash and used for the Gravatar image URL.

This means if you comment on three different blogs running this plugin using the same e-mail address,
three different hashes will be generated, none of which are the actual `md5()` hash of the e-mail
address you used.

The system administrator of a blog can white-list specific domains and/or e-mail addresses that are
not obfuscated, but by default, the hash of your e-mail address is obfuscated protecting the
anonymity of the users who comment.

The plan is in the future to use a different service than Gravatar that does not use tracking cookies,
feel free to contact me if you want more information about my future plans in this respect.

EOF