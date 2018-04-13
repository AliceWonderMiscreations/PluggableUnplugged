<?php
declare(strict_types=1);

/**
 * Invalid Argument Exceptions
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
 * Throws a \InvalidArgumentException exception.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * Filter special html characters.
     *
     * @param string $input The string to filter.
     *
     * @return string The filtered string.
     */
    public static function specialCharacterFilter(string $input): string
    {
        $s = array();
        $r = array();
        $s[] = '/&/';
        $r[] = '&amp;';
        $s[] = '/</';
        $r[] = '&lt;';
        $s[] = '/>/';
        $r[] = '&gt;';
        return preg_replace($s, $r, $input);
    }//end specialCharacterFilter()

    
    /**
     * Exception message for an invalid domain.
     *
     * @param string $str The invalid domain name.
     *
     * @return \InvalidArgumentException
     */
    public static function invalidDomain(string $str)
    {
        $str = self::specialCharacterFilter($str);
        return new self(sprintf(
            'The supplied domain <code>%s</code> is not a valid domain name.',
            $str
        ));
    }//end invalidDomain()

    
    /**
     * Exception message for an invalid email.
     *
     * @param string $str The invalid e-mail address.
     *
     * @return \InvalidArgumentException
     */
    public static function invalidEmail(string $str)
    {
        $str = self::specialCharacterFilter($str);
        return new self(sprintf(
            'The supplied e-mail address <code>%s</code> is not a valid e-mail address.',
            $str
        ));
    }//end invalidEmail()
}//end class

?>