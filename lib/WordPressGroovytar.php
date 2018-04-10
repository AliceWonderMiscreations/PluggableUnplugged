<?php
declare(strict_types=1);

/**
 * Replacements for Gravatar.
 *
 * Currently only obfuscates the gravatar hash but plans are a complete
 * replacement.
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

namespace AWonderPHP\PluggableUnplugged;

/**
 * Class for WordPress Groovytar specific functions
 */
class WordPressGroovytar extends \AWonderPHP\PluggableUnplugged\Groovytar
{
    /**
     * Extracts the e-mail address from the mixed clusterfuck that is $id_or_email.
     * Unfortunately this function requires a lot of WordPress specific functions.
     *
     * @param mixed $id_or_email The clusterfuck to get the e-mail address from.
     *
     * @return string The e-mail to return.
     */
    protected function getEmailFromIdOrEmail($id_or_email)
    {
        $user = false;
        $email = null;
        
        // TODO - research the get_comment function
        if (is_object($id_or_email) && isset($id_or_email->comment_ID)) {
            if(isset($id_or_email->comment_author_email)) {
                $email = $id_or_email->comment_author_email;
            } else {
                $id_or_email = get_comment($id_or_email);
            }
        }
        
        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', absint($id_or_email));
        } elseif (is_string($id_or_email)) {
            // I'm treating hash@md5.gravatar.com as an e-mail - it gets obfuscated
            $email = $id_or_email;
        } elseif ($id_or_email instanceof WP_User) {
            $user = $id_or_email;
        } elseif ($id_or_email instanceof WP_Post) {
            $user = get_user_by('id', (int) $id_or_email->post_author);
        } elseif ($id_or_email instanceof WP_Comment) {
            /**
             * Filters the list of allowed comment types for retrieving avatars.
             *
             * @since 3.0.0
             *
             * @param array $types An array of content types. Default only contains 'comment'.
             */
            $allowed_comment_types = apply_filters('get_avatar_comment_types', array( 'comment' ));
            if (! empty($id_or_email->comment_type) && ! in_array($id_or_email->comment_type, (array) $allowed_comment_types)) {
                return 'user@example.org';
            }
            if (! empty($id_or_email->user_id)) {
                $user = get_user_by('id', (int) $id_or_email->user_id);
            }
            if (( ! $user || is_wp_error($user)) && ! empty($id_or_email->comment_author_email)) {
                $email = $id_or_email->comment_author_email;
            }
        }
        if (is_null($email)) {
            if ($user) {
                $email = $user->user_email;
            }
        }
        if (! is_null($email)) {
            $email=trim(strtolower($email));
            $email=UnpluggedStatic::punycodeEmail($email);
        }
        if (! $test = filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'user@example.org';
        }
        return $email;
    }//end getEmailFromIdOrEmail()


    /**
     * The WordPressGroovytar constructor
     */
    public function __construct()
    {
        if (! $salts=get_option('groovytarSalts')) {
            $salts = Groovytar::generateSalts();
            update_option('groovytarSalts', $salts);
        }
        $this->salts = $salts;
        $this->defAvatar = get_option('avatar_default', 'monsterid');
        $this->avatarRating = get_option('avatar_rating', 'g');
    }//end __construct()
}//end class

?>