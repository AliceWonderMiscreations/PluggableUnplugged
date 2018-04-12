PluggableUnplugged
==================

This plugin installs but is not fully tested, as in, use at your own risk.

The is the github readme, intended primarily for developers. Non-developers are
of course free to read this, but *may* be better served by the readme that is
not yet written but will accompany the actual plugin.

This plugin does four things:

* [Generate WordPress Config Salts](#wordpress-config-salts)
* [Better Hash, Salt, and WordPress Nonce Generation](#better-hash-salt-and-wordpress-nonce-generation)
* [Blog Comment Avatars with Privacy](#blog-comment-avatars-with-privacy)


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

I am not a fan of how WordPress generates hashes, salts, and what they call
a nonce but have no right to call a nonce.

### WordPress Hashes

The WordPress functions for creating non-password related hashes use the PHP
[`hash_hmac()`](http://php.net/manual/en/function.hash-hmac.php) function with
`md5` as the algorithm.

There is no good reason to continue using `md5`,
[md5 is broken](https://en.wikipedia.org/wiki/MD5#Security) and there just is
not a justifiable reason to keep using it.

The `wp_hash()` function is replaced by one that uses
`sodium_crypto_generichash()` instead. That is a secure alternative to
`hash_hmac()` that does not use `md5` and is safe to use where a hash is
needed.

### WordPress Salts

The `wp_salt()` function that WordPress uses generates salts either using the
random password generating facilities or by using `hash_hmac()` with `md5`.

The salt generation functions have been replaced to instead create the salt
using a base64 encoding of 32 bytes of random data from a cryptographically
secure pRNG or by using `sodium_crypto_generichash()` when it is a salt that
is not stored but needs to regenerate the same every time it is generated.

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






TTTTTTTTTTTTTTTTTTTTTt
======================



If you install, all password hashes will be updated to argon2id which means
removing the plugin will result in an inability to log in, you'll have to do a
password reset after removing / de-activating the plugin.

Very alpha still.

__YOU HAVE BEEN WARNED__

Now, ignore my warning and use on heavy production sites so I can have the bug
reports. I can filter out the cursing that will accompany them.

---------------------------------------------------

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







### Nonces

With respect to nonces, WordPress has no fucking right to call what they are
generating a nonce.

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

In the `bin/` directory is a PHP shell script called
[`mksalts.php`](bin/mksalts.php) that will generate suitable salts you can copy
and paste into your `wp-config.php` file that are quality generated salts. It
is a good idea to change those salts every now and then in case they have been
compromised by a bug in the platform or a plugin.

However the nonce can still potentially be predicted by an attacker who is able
to get a few pieces of information that can potentially be leaked by bugs in
the platform or bugs in various plugins. No randomness exists in the generation
of a WordPress nonce other than what is in the static salts and the user
session token, both of which can potentially be discovered by an exploit.

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


Gravatar Related
----------------

Gravatar is a huge privacy leak. It results in an unsalted hash of the user
e-mail address being published on the web page. This makes it trivial for
hackers and others to create tables of e-mail addresses they are interested in
and search the web for blogs that e-mail address has posted to.

This class obfuscates the e-mail hash using a pair of salts that are custom to
to the blog so that when someone comments on the blog, the avatar will not
include an unsalted hash of their e-mail address and will in fact be different
from a hash where the same e-mail address was used to comment on a different
blog.


TO BE CONTINUED
===============

I still need to write unit tests and an admin interface so that the blog admin
can decide whether or not they want `argon2id` password hashing and whether or
not they want to add any domains or e-mail addresses to a white list of what
does *not* get obfuscated when making a gravatar hash.





---------------------------------------------------
__EOF__
