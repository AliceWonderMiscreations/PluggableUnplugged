PluggableUnplugged
==================

This plugin installs but is not fully tested, as in, use at your own risk.

This plugin definitely works with the PHP 7.1.x branch, *probably* works with
the PHP 7.2.x branch, and *likely* works with the PHP 7.0.0 branch, though I
encourage PHP 7.0.0 users to upgrade to at least 7.1.x.

This plugin does not work with PHP 5.6.x or earlier, and users of PHP 5.6.x or
earlier really should upgrade, your PHP install is slow and out of date.

This plugin also requires the PHP
[libsodium](https://pecl.php.net/package/libsodium) extension. That extension
is built by default starting with PHP 7.2 but it must be installed separately
for either earlier versions of PHP or with PHP 7.2.x where it was specifically
not built when PHP was compiled.

This is the github readme, intended primarily for developers. Non-developers
are of course free to read this, but *may* be better served by the readme that
is not yet written but will accompany the actual plugin.

This plugin does five things:

* [Generate WordPress Config Salts](#wordpress-config-salts)
* [Better Hash, Salt, and WordPress Nonce Generation](#better-hash-salt-and-wordpress-nonce-generation)
* [Blog Comment Avatars with Privacy](#blog-comment-avatars-with-privacy)
* [Optional Argon2id Password Hashing](#optional-argon2id-password-hashing)
* [UnpluggedStatic Class](#unpluggedstatic-class)


WordPress Config Salts
----------------------

When setting up a WordPress install, a very important part of the setup that is
skipped by their ‘Famous Five Minute Install’ is configuring the salts used in
the `wp-config.php` file.

The traditional way to generate those salts is to visit the website
[https://api.wordpress.org/secret-key/1.1/salt/](https://api.wordpress.org/secret-key/1.1/salt/)
where it will generate the salts for you, and you can copy and paste the
results into your `wp-config.php` file.

That is *probably* safe to do, but there are two issues:

1. You have no idea what their server is actually using to generate those
salts.
2. You have no idea whether or not they are logging the salts that are
generated.

It may be paranoid, but why trust an unknown when you can generate the salts in
a way that you can inspect and know for sure are not logged by a third party?

In the `bin/` directory of this plugin is a PHP shell script called
[`mksalts.php`](bin/mksalts.php) that you can inspect to make sure it does the
salt generation in a safe way, and also know for sure that the salts generated
are not being logged by a third party.

To run the script:

    cd /path/to/wordpress/wp-content/plugins/awm-pluggable-unplugged/bin/
    php mksalts.php

That will produce output similar to the following:

    define('AUTH_KEY',         'clbv2KOi126xgEZdoGBRLlC/uTZwH+O4AYJBN0nJoiS=ng4aYe1Vp=WzML6phbtuM/+yxmoEyhmqzLkjvpYb0hfS');
    define('SECURE_AUTH_KEY',  'O5=9yk608Hp37vTX2yrmL67oMeLMwjh2Rq/AjJQTR+aL7R1qMawHnOKX2ABl5lW9trzoXfO3L9Ee=PrWugNeoqvr');
    define('LOGGED_IN_KEY',    'kEFyqlZgEkJA+JF6FHb7RwX6eK=GdI49LTH4Kw8Rn2rWyqrKgGVwEYPxwOmaaHOLR2PGcTnHxsm6H=afEwFJjSHc');
    define('NONCE_KEY',        'Vq=BmmYJHroj9=2Qve38NSoC6mhKa44OOy3OveQClboj9aTGeUI5un4r35csaLiyy/PuzqGc6nxtObU2QjccRCr0');
    define('AUTH_SALT',        'IEi0Xsj7DK7x=vdJagocko3FHH=vQozT7chpD9HAltcJkf7BjajoHEowOjsO8tJsdAV+3ba6mQKaNUe3nzPumhYi');
    define('SECURE_AUTH_SALT', '3GpehX5T8g4pwMZApv2jnoMzcaufbVQ/v=PQiT8tQnW5ICIEqx11QeOaCqKqfMBu2sfOnd+zwY5l5hikjop=feCX');
    define('LOGGED_IN_SALT',   '0yboi+/5rEOXG=Eqb+fEUZVq7Tu+nkmbdJRenub3exco71Z7V4OZXF5=4FwRoEt/eDWNSdorwkJzLu7kE6kEmbnk');
    define('NONCE_SALT',       'DQE2+QqYWhG+mGfshObSw3vx3AZCWELiPXzZrHF4IOBjer=UxkBCKu+eUmVJcFPID6Br6qpFf=NPaHxCxi07eGd8');

Each salt is generated using the cryptographically suitable `random_bytes()`
function, the result of which is then base64 encoded and then finally shuffled
(which is why the padding `==` is not at the end).

64 bytes is significantly more than the 256 bits (32 bytes) of random data I
personally usually recommend for a salt that is to be used more than once.

I personally like to change my salts every time I do a major update that
requires downtime just to make previous salts invalid, since WordPress uses
them in the generation of CSRF tokens they *wrongly* call nonces.


Better Hash, Salt, and WordPress Nonce Generation
-------------------------------------------------

I am not a fan of how WordPress generates hashes, salts, and what they wrongly
call a nonce. Really they have no right to call it nonce. They can call it a
token, but calling it a nonce is misleading and can result in a false sense of
security that can be dangerous.

### WordPress Hashes

The WordPress functions for creating non-password related hashes use the PHP
[`hash_hmac()`](http://php.net/manual/en/function.hash-hmac.php) function with
`md5` as the algorithm.

There is no good reason to continue using `md5`,
[md5 is broken](https://en.wikipedia.org/wiki/MD5#Security) and there just is
not a justifiable reason to keep using it.

The `wp_hash()` function is replaced in this plugin by one that uses
`sodium_crypto_generichash()` instead. That is a secure alternative to
`hash_hmac()` that does not use `md5` and is safe to use where a hash is
needed.

### WordPress Salts

The `wp_salt()` function that WordPress uses generates salts either using the
random password generating facilities or by using `hash_hmac()` with `md5`.

The salt generation functions have been replaced to instead create the salt
using a base64 encoding of 32 bytes of random data from a cryptographically
secure pRNG or by using `sodium_crypto_generichash()` when it is a salt that
is not stored but needs to generate the same value every time it is generated.

### WordPress Nonce

A __nonce__ is a cryptography term to literally indicate a __N__ umber that is
used only __once__.

The original use of the word was actually not numeric in nature, but used to
indicate an [occasionalism](https://en.wikipedia.org/wiki/Nonce_word), a word
created for ‘a single occasion to solve an immediate problem of communication.’

In the context of computer science, it literally means a number used only once
that is then invalid for future use. There generally are two types of nonces:

1. With some cryptography ciphers, a nonce that does not need to be kept a
secret is used. These nonces can be publicly disclosed and predictable without
impacting the security of the secret key or the encoded message. They are often
used with AEAD ciphers and are frequently encoded in plain text as part of the
Associated Data with the ciphertext. The same nonce should never be used twice
with the same secret key, or it may be possible for an attacker to derive the
secret key.

2. In other uses, the nonce needs to be kept a secret and should be random so
that it can not be guessed. Use of a nonce as a CSRF token is one such example.
In these cases, a nonce should be at *least* a 128-bit random number generated
using a cryptographically suitable pseudo Random Number Generator (pRNG).

WordPress calls what they generate a nonce, but it is incorrect for them to do
so. They use it in the context of CSRF tokens yet it is both predictable *and*
they re-use the same nonce for up to twelve hours, with the nonce remaining
valid for an additional 12 hours after they move to a different nonce.

Furthermore, they intentionally limit their token to 80 bits even though it
starts life as a 128 bit token.

This plugin is not able to completely completely correct their abuse of what
a nonce is suppose to be, but it does make some improvements.

Instead of using `hash_hmac()` with `md5` on predictable data to generate the
CSRF token, `sodium_crypto_generichash()` is used. The result is then base64
encoded instead of hex encoded, and the only characters removed are the `+`
and `/` characters, which are removed because WordPress often uses the token in
`GET` variables rather than always using `POST`.

Finally, the life of a token is reduced to a three hours maximum, with a new
token generated every 90 minutes.

Unfortunately it still creates the token in a predictable way and reuses the
same token many times during the given time period it is valid for, but it
isn't *quite as bad* as the native WordPress ‘nonce’ generation.

For those writing WordPress plugins who want an *actual nonce* instead of the
less secure token that WordPress calls a nonce, this plugin provides two
non-standard functions you can use:

* `awm_create_nonce(int $ttl = 10800, string $action = 'generic'): string`  
  This function creates a random 128-bit nonce, stores it in the user
  session data associated with the specified `$action`, and returns the
  nonce so that it can be included as a hidden input in a generated form.

* `awm_verify_nonce(string $nonce, string $action = 'generic'): bool`  
  This function verifies that the specified nonce has been stored in the user's
  session data associated with the specified `$action` and then invalidates
  it from validating in the future, as it has been used. It returns `true`
  when it is fed a nonce that validates, and `false` when it is fed a nonce
  that does not validate.

If you write plugins and have need for a CSRF token, if you do not mind your
plugin requiring this one, you can safely use those functions for your CSRF
nonce validation instead of the less secure WordPress versions.


Blog Comment Avatars with Privacy
---------------------------------

By default, WordPress uses an avatar system owned by
[Automattic](https://automattic.com/) called Gravatar. The Gravatar system
leaks information about people who post comments on your blog and in a very bad
way.

What it does, it uses a plain unsalted `md5` hash of the person's private
e-mail address to reference the Avatar image that is used. This is done whether
or not the user has a Gravatar account without any consideration for whether or
not that user values privacy.

This makes it simple for an attacker looking to find out information about a
person to locate all the WordPress blogs that person makes posts to. All the
attacker has to do is take a simple `md5()` hash of the target's e-mail address
and the attacker can set up a search over the web for WordPress blog comments
where that hash appears as part of the Gravatar.

Tracking cookies are also set on the gravatar.com domain, allowing the included
gravatar to be used as a source for Automattic tracking your users.

### The Solution

Long term, the goal is to replace Gravatar with a completely different system
that values privacy and does not use any tracking cookies with the avatars that
are served.

For the short term, the e-mail address is salted using salts specific to the
WordPress install before the hash is calculated, so that the same e-mail
address will never have the same hash at different sites and will not have a
hash that an attacker can guess. Also, the secure URL for the avatar is always
fetched whether or not the WordPress function `is_ssl()` returns `true`.

In the long term as time *and money* allow, an alternate service that does not
use tracking cookies will be set up. This planned service will serve SVG
avatars *except* when the user has uploaded their own.

When a user writes a comment on a blog, the user will have an option to check
a box indicating they want the avatar at that blog to be tied to the avatar
they have uploaded to the alternate service. That hash will still be unique to
that blog, but the blog will send an anonymized identifier that allows us to
serve the user's desired image in response to that specific hash.

The system will be both opt-in and opt-out with the user having complete
control over whether or not an image they uploaded is used and at what blogs it
is used.

#### Non-obfuscation

Presently a system administrator may create a white list of domains and/or
e-mail addresses where the hash is not obfuscated. Once gravatar.com is no
longer used, that option will no longer be necessary.

#### PHP Classes

A generic abstract class in the file `lib/Groovytar.php` does most of the work.

A WordPress specific class `lib/WordPressGroovytar.php` extends that class so
that it can deal with WordPress specific issues, like extracting the e-mail to
hash from a WordPress post object.

This was done because in the past when I created Gravatar obfuscation
solutions, WordPress changed to break my obfuscation. A WordPress specific
class that extends a generic class seemed like the easiest way to deal with
such changes in the future, as I suspect they are likely to happen again.


Optional Argon2id Password Hashing
----------------------------------

[Argon2](https://github.com/P-H-C/phc-winner-argon200) is an advanced password
hashing algorithm.

As you hopefully are aware, passwords should *never* be stored in plain text.
instead, a hash should be computed against the password and the hash of the
password should be stored.

When the user wants to log in, their supplied password is hashed and checked
against the stored hash to make sure it matches.

It is not however that simple. If a hacker manages to get a dump of the
WordPress database, the attacker can run a dictionary attack against the
stored password hashes until the attacker finds some matches - passwords that
produce the desired hash.

Good hashing algorithms will be both processor and memory intensive to make it
computationally expensive for an attacker who gained access to the database
of hashes to try a dictionary attack against the hashes in the database.

The Argon2 algorithms do that. In fact in PHP 7.2 the Argon2i variant is now
the default with the native PHP `password_*` functions, see
[https://wiki.php.net/rfc/argon2_password_hash](https://wiki.php.net/rfc/argon2_password_hash)

This class *optionally* uses the Argon2id variant for WordPress password
hashing.

Nutshell: The Argon2i variant is vulnerable to side-channel attacks and the
Argon2d variant is vulnerable to time-memory trade off attacks. Argon2id is a
hybrid variant that uses Argon2i initially followed by Argon2d giving it some
measure of protection against both types of attacks.

This plugin uses the PHP libsodium wrapper to provide Argon2id password
hashing. By default, it is turned off because as each user logs in to your
system after enabling, their password hash will be updated to use Argon2id
for the hash which means if you ever disable this plugin, they will not be able
to log in again without doing a password reset.

I highly recommend enabling this feature. WordPress uses an older hashing
algorithm in order to maintain compatibility with older versions of PHP, but
that is not proactive security. WordPress developers value running everywhere
with their ‘Famous Five Minute Install’ over running as safely as they should.

Just be warned that once you do enable this feature, reverting will require
that users reset their password as vanilla WordPress is not (yet) capable of
dealing with Argon2id password hashes without a plugin to give it that ability.

When this is enabled, the first time a user logs in again their hash will be
updated to use Argon2id. After that, every time they log in there is a 20%
chance their hash will be regenerated. This is to allow for improvements in
the hardware to be taken into consideration resulting in a stronger hash that
is even more computational and memory resource intensive to try and brute force
with a dictionary attack.


UnpluggedStatic Class
---------------------

This plugin provides a namespaced class of static functions called
`\AWonderPHP\PluggableUnplugged\UnpluggedStatic` that exists to provide
functions of benefit both to this plugin and potentially to other plugins.

Placing the functions as static methods inside a namespaced class makes it
easy to avoid function name collisions with functions provided by other
plugins.

For example, the WordPress `wp_rand()` function does the right thing but a
plugin developer probably should not use it because any plugin can *replace*
that function with one that does __not__ do the right thing.

The UnpluggedStatic class hopes to provide stable functions within a namespace
that won't be replaced by other plugins, e.g.

    \AWonderPHP\PluggableUnplugged\UnpluggedStatic::safeRandInt($a, $b)

To safely produce a random integer between `$a` and `$b` inclusive regardless
of what other plugins may have redefined `wp_rand()` to do.

Please note that as I continue to develop additional privacy related plugins,
this class will receive updates to include additional static methods that are
generic in nature and potentially useful to other plugins.

---------------------------------------------------
__EOF__
