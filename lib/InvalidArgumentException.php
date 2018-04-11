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
     * Exception message for an invalid domain.
     *
     * @param string $str The invalid domain name.
     *
     * @return \InvalidArgumentException
     */
    public static function invalidDomain(string $str)
    {
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
        return new self(sprintf(
            'The supplied e-mail address <code>%s</code> is not a valid e-mail address.',
            $str
        ));
    }//end invalidEmail()
}//end class

?>