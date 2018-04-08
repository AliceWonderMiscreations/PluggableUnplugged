PluggableUnplugged
==================

This plugin is not yet installable.

The purpose of this plugin is to improve how some of the WordPress
`pluggable.php` functions do their thing on hosts that are both running PHP 7
or newer and have the libsodium wrapper functions available.

At this point in time, PHP 7 should be the norm for any WordPress host, as PHP
5.6.x only receives security fixes.

Starting with PHP 7.2, a default build of PHP includes the libsodium extensions
so it soon will become very common for PHP on commercial hosting companies to
already have those extensions available with PHP.

The plugin installs a class of static methods that other plugins can use and
also provides some improvements to some of the standard `wp_` functions that
can be found in the WordPress `pluggable.php` file.


Password Related
----------------

This class provides for password hashing using the
[`argon2id`](https://en.wikipedia.org/wiki/Argon2) function to generate the
password hash.

The replacement password related functions also call `sodium_memzero()` to
properly zero out the plain text password in memory after doing their thing.

The WordPress functions the password related functions replace allow for
callback functions to do things but take the plain text password as an
argument. I see that as dangerous so those filters are not run in the
replacement functions.

That means if for example you have a plugin that makes sure a user does not set
their password to one they have used before, it will not work. Too bad so sad,
I have seen no evidence that such measures actually improve the security of a
website anyway and I fear what some plugins that create callback functions may
do with the plain text password, thinking they are being helpful.

I would like to be add a cracklib check on passwords when the cracklib
functions are available but the
[PECL extension](https://pecl.php.net/package/crack) has not been updated since
2005 and has a [known 64-bit bug](https://bugs.php.net/bug.php?id=56611) not
fixed in a released version.

When a password is verified, there is a 20% chance the hash in the database
will be updated. This allows for stronger hashes to automatically be migrated
into use for existing accounts as the server hardware is upgraded allowing for
stronger (more rounds) hashes.


Non-Password Hash, Salt, and Nonce Related
------------------------------------------

The WordPress functions for creating non-password related hashes use the PHP
[`hash_hmac()`](http://php.net/manual/en/function.hash-hmac.php) function with
`md5` as the algorithm.

There is no good reason to continue using `md5`,
[md5 is broken](https://en.wikipedia.org/wiki/MD5) and there really is not a
justifiable reason to keep using it.

The `wp_hash()` function is replaced by one that uses
`sodium_crypto_generichash()` instead. That is a secure alternative to
`hash_hmac()` and is safe to use.

The `wp_salt()` function that WordPress uses generates salts either using the
random password generating facilities or by using `hash_hmac()` with `md5`.

The salt generation functions have been replaced to instead create the salt
using a base64 encoding of 32 bytes of random data from a cryptographically
secure pRNG or by using `sodium_crypto_generichash()` when it is a salt that
is not stored but needs to regenerate the same every time it is generated.

### Nonces

With respect to nonces, WordPress has no fucking right to call what they are
generate a nonce.

A nonce __by definition__ is only used once and then is invalid for further
use.

When used in the context of
[CSRF Tokens](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet)
which is what WordPress uses them for, they need to be random without the
ability to guess them. That means generated with a quality cryptographically
secure pRNG with at least 128 bits of entropy.

WordPress intentionally creates them in a predictable fashion and then actually
reduces the nonce to only 80 bits.

A nonce for a particular action only changes every twelve hours and is still
valid for twelve hours after it has changed. That violates the very definition
of use once then invalid.

I was not able to completely fix their CSRF nonce infrastructure without
breaking every plugin that uses them, but I did make some improvements with the
replacement functions.

First of all, the nonce is created using `sodium_crypto_generichash()` and the
full 128 bits is used for the nonce, not just 80 bits of it.

Additionally I shortened the tick time in `wp_nonce_tick()` to give the nonce
a valid lifetime of up to only three hours instead of up to twenty-four hours.

However the nonce can still potentially be predicted by an attacker who is able
to get a few pieces of information that can potentially be leaked by bugs in
the platform or bugs in various plugins. No randomness exists in the generation
of a nonce.

To compensate, two new functions have been added:

* `function awm_create_nonce(int $ttl = 10800, string $action = 'generic'): string`
* `function awm_verify_nonce(string $nonce, string $action = 'generic'): bool`

The first creates a completely random nonce that uses 16 bytes of pRNG data (128
bits) and the second validates a nonce, making the nonce invalid for any future
validation.

How long a nonce is valid for before it expires even when not used can also be
specified. Certain actions, such as administrative actions, should probably
have a much shorter life than the default three hours.

Plugin developers who need a CSRF nonce should use those functions if available
instead of the `wp_` nonce functions.

If a plugin uses the nonce in the context of AJAX submission, the server-side
AJAX processing should generate a new nonce that is returned with the response
as the nonce sent with the form submission will be invalidated once used.


TO BE CONTINUED
===============






