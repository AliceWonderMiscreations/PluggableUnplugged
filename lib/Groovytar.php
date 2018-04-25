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
 * Class for Groovytar specific functions
 */
abstract class Groovytar
{
    /**
     * White list of domains not to obfuscate.
     *
     * @var array
     */
    protected $domains = array();
    
    /**
     * White list of e-mail addresses not to obfuscate.
     *
     * @var array
     */
    protected $addresses = array(
        'anonymous@gravatar.com',
        'wapuu@wordpress.example'
    );
    
    /**
     * The salts to use when created an obfuscated hash.
     * If empty, generated when needed. Should be set by the constructor
     * of classes extending this one.
     *
     * @var array
     */
    protected $salts = array();
    
    /**
     * The defaults theme to use for generated avatars. Defaults to monsterid.
     *
     * @var string
     */
    protected $defAvatar = 'monsterid';
    
    /**
     * The maximum rating for custom avatars. Defaults to g.
     *
     * @var string
     */
    protected $avatarRating = 'g';

    /**
     * Generates the salts to use.
     *
     * @return array An array with two different salts.
     */
    public static function generateSalts()
    {
        $salts = array();
        $salts[]=UnpluggedStatic::saltShaker();
        $salts[]=UnpluggedStatic::saltShaker();
        return $salts;
    }//end generateSalts()

    /**
     * Adds domain to white-list of domains that will not be obfuscated.
     *
     * @param string $input The domain to add to white list.
     *
     * @return array The updated domain white list.
     */
    public function addDomain(string $input): array
    {
        $domain=trim(strtolower($input));
        $domain=UnpluggedStatic::punycodeDomain($domain);
        $test='user@' . $domain;
        if (! filter_var($test, FILTER_VALIDATE_EMAIL)) {
            throw InvalidArgumentException::invalidDomain($input);
        }
        if (! in_array($domain, $this->domains)) {
            $this->domains[] = $domain;
        }
        return $this->domains;
    }//end addDomain()

    /**
     * Add e-mail address to white-list of addresses that will not be obfuscated
     *
     * @param string $input The e-mail to add to white list.
     *
     * @return array The updated e-mail white list.
     */
    public function addEmailAddress($input): array
    {
        $address=trim(strtolower($input));
        $paddress=UnpluggedStatic::punycodeEmail($address);
        if (! filter_var($paddress, FILTER_VALIDATE_EMAIL)) {
            throw InvalidArgumentException::invalidEmail($input);
        }
        if (! in_array($paddress, $this->addresses)) {
            $this->addresses[] = $paddress;
        }
        return $this->addresses;
    }//end addEmailAddress()

    /**
     * Removes domain from white-list of domains that will not be obfuscated.
     *
     * @param string $input The domain to remove from white list.
     *
     * @return array The updated domain white list.
     */
    public function removeDomain(string $input): array
    {
        $domain=trim(strtolower($input));
        $domain=UnpluggedStatic::punycodeDomain($domain);
        $test='user@' . $domain;
        if (! filter_var($test, FILTER_VALIDATE_EMAIL)) {
            throw InvalidArgumentException::invalidDomain($input);
        }
        $newArray = array();
        $n = count($this->domains);
        foreach ($this->domains as $whitelisted) {
            if ($whitelisted !== $domain) {
                $newArray[] = $whitelisted;
            }
        }
        $m = count($newArray);
        if ($m < $n) {
            $this->domains = $newArray;
        }
        return $this->domains;
    }//end removeDomain()

    /**
     * Removes e-mail address from white-list of addresses that will not be obfuscated
     *
     * @param string $input The e-mail to remove from white list.
     *
     * @return array The updated e-mail white list.
     */
    public function removeEmailAddress($input): array
    {
        $address=trim(strtolower($input));
        $paddress=UnpluggedStatic::punycodeEmail($address);
        if (! filter_var($paddress, FILTER_VALIDATE_EMAIL)) {
            throw InvalidArgumentException::invalidEmail($input);
        }
        $newArray = array();
        $n = count($this->addresses);
        foreach ($this->addresses as $whitelisted) {
            if ($whitelisted !== $paddress) {
                $newArray[] = $whitelisted;
            }
        }
        $m = count($newArray);
        if ($m < $n) {
            $this->addresses = $newArray;
        }
        return $this->addresses;
    }//end removeEmailAddress()

    /**
     * Generates the md5 mimic hash to use with the gravatar system.
     *
     * @param string $email The e-mail address to generate a hash for.
     *
     * @return string The md5sum mimic string to return.
     */
    protected function mimicHash($email): string
    {
        // should never happen but just in case
        if (count($this->salts) !== 2) {
            $this->salts = $this->generateSalts();
        }
        
        $email=trim(strtolower($email));
        $pemail=UnpluggedStatic::punycodeEmail($email);
        //validate email
        if (! filter_var($pemail, FILTER_VALIDATE_EMAIL)) { //hopefully wordpress already has validated this but...
            $pemail='unknown@gravatar.com';
        }
        $foo=explode('@', $pemail);
        $domino=$foo[1]; //this is domain part of @domain
        $qq=0;
        
        //check for white-listed domain
        $j=sizeof($this->domains);
        for ($i=0; $i<$j; $i++) {
            $test=$this->domains[$i];
            $dummy='user@' . $test;
            if (filter_var($dummy, FILTER_VALIDATE_EMAIL)) {
                //check for exact match first
                if ($domino === $test) {
                    $qq++;
                } else {
                    $domino='.' . $domino; //for testing if $test is subdomain
                    $qq = $qq + substr_count($domino, $test); //any matches and $qq is no longer 0
                }
            }
        }
        
        //check for white-listed address
        if ($qq === 0) {
            if (in_array($pemail, $this->addresses)) {
                $qq++;
            }
        }
        
        if ($qq === 0) {
            // obfuscate
            $obf=hash('sha256', $this->salts[0] . $pemail, false);
            $obf=hash('sha256', $this->salts[1] . $obf, false);
            return substr($obf, 4, 32);
        } else {
            // there was a white-list match, do not obfuscate
            return hash('md5', $pemail, false);
        }
    }//end mimicHash()

    /**
     * Generates a reference hash. Future plans, not currently used.
     * An e-mail address can not easily be figured out from the hash but
     * rainbow tables could be created by running this function on list
     * of e-mail addresses.
     *
     * Future purpose is to allow for an alternative to gravatar where
     * users can have a different obfuscated hash at each blog it appears
     * but opt in to have the hashes tied together at some (or all) of the
     * sites so that same avatar is served to them.
     *
     * The ripemd160 of the multiple rounds of sha256 will be what allows
     * that opting in.
     *
     * @param string $email The e-mail address to generate a hash for.
     *
     * @return string A base64 encoded ripemd160 hash
     */
    public function referenceHash($email)
    {
        $email=trim(strtolower($email));
        $pemail=UnpluggedStatic::punycodeEmail($email);
        //validate email
        if (! filter_var($pemail, FILTER_VALIDATE_EMAIL)) { //hopefully wordpress already has validated this but...
            throw InvalidArgumentException::invalidEmail($email);
        }
        $prehash = $pemail;
        for ($i=0; $i<5; $i++) {
            $prehash = hash('sha384', $prehash, false);
        }
        $data = hash('ripemd160', $prehash, true);
        return base64_encode($data);
    }//end referenceHash()

    /**
     * This is intended to be replaced by classes that extend this class but may need
     * additional operations to extract the e-mail address from what is provided.
     *
     * @param mixed $email The clusterfuck to get the e-mail address from.
     *
     * @return null|string The e-mail to return or null if we do not have one.
     */
    protected function getEmailFromIdOrEmail($email)
    {
        if (! is_null($email)) {
            if (is_string($email)) {
                $email=trim(strtolower($email));
                $email=UnpluggedStatic::punycodeEmail($email);
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }
        }
        return $email;
    }//end getEmailFromIdOrEmail()

    /**
     * Extracts a useful $args array from the getAvatarData $args argument.
     *
     * @param null|array $args The argument to turn into a useful array.
     *
     * @return array The $args array to return.
     */
    protected function gravatarArgs($args)
    {
        if (is_null($args)) {
            $args = array();
        }
        
        // size
        if (! isset($args['size'])) {
            $args['size'] = 96;
        }
        if (! is_numeric($args['size'])) {
            $args['size'] = 96;
        }
        $args['size'] = intval($args['size'], 10);
        if ($args['size'] < 16) {
            $args['size'] = 96;
        }
        
        // height
        if (! isset($args['height'])) {
            $args['height'] = $args['size'];
        }
        if (! is_numeric($args['height'])) {
            $args['height'] = $args['size'];
        }
        $args['height'] = intval($args['height'], 10);
        if ($args['height'] < 16) {
            $args['height'] = $args['size'];
        }
        
        // width
        if (! isset($args['width'])) {
            $args['width'] = $args['size'];
        }
        if (! is_numeric($args['width'])) {
            $args['width'] = $args['size'];
        }
        $args['width'] = intval($args['width'], 10);
        if ($args['width'] < 16) {
            $args['width'] = $args['size'];
        }
        
        // default
        if (! isset($args['default'])) {
            $args['default'] = $this->defAvatar;
        }
        if (empty($args['default'])) {
            $args['default'] = $this->defAvatar;
        }
        
        switch ($args['default']) {
            case 'mm':
            case 'mystery':
            case 'mysteryman':
                $args['default'] = 'mm';
                break;
            case 'gravatar_default':
                $args['default'] = false;
                break;
        }
        
        // force_default
        if (! isset($args['force_default'])) {
            $args['force_default'] = false;
        }
        $args['force_default'] = (bool) $args['force_default'];
        
        // rating
        if (! isset($args['rating'])) {
            $args['rating'] = $this->avatarRating;
        }
        $args['rating'] = strtolower($args['rating']);
        
        // scheme
        if (! isset($args['scheme'])) {
            $args['scheme'] = null;
        }
        
        // processed_args
        if (! isset($args['processed_args'])) {
            $args['processed_args'] = null;
        }
        
        // extra_attr
        if (! isset($args['extra_attr'])) {
            $args['extra_attr'] = '';
        }

        $args['found_avatar'] = false;
        
        // TODO - research the pre_get_avatar_data filter
        
        return $args;
    }//end gravatarArgs()

    /**
     * A substitute for the get_avatar_data() function
     *
     * @param mixed $id_or_email The email associated with the avatar to retrieve.
     * @param array $args        Optional. Arguments to return instead of the default arguments.
     *
     * @return array An array of settings related to the e-mail address.
     */
    public function getAvatarData($id_or_email, $args = null)
    {
        $args = $this->gravatarArgs($args);
        $email = $this->getEmailFromIdOrEmail($id_or_email);
        if (is_null($email)) {
            $args['url'] = false;
            return $args;
        } else {
            $email_hash = $this->mimicHash($email);
            $args['found_avatar'] = true;
        }
        $url_args = array(
                's' => $args['size'],
                'd' => $args['default'],
                'f' => $args['force_default'] ? 'y' : false,
                'r' => $args['rating'],
        );
        $url = 'https://devel.trippyid.com/avatar/' . $email_hash;
        $args['url'] = UnpluggedStatic::modifyQueryArgs($url, $url_args);
        return $args;
    }//end getAvatarData()

    /**
     * A substitute for the WordPress get_avatar_url() function.
     *
     * @param mixed $id_or_email The email associated with the avatar to retrieve.
     * @param array $args        Optional. Arguments to return instead of the default arguments.
     *
     * @return null|string The string for the Avatar URL, or null.
     */
    public function getAvatarUrl($id_or_email, $args = null)
    {
        $args = $this->getAvatarData($id_or_email, $args);
        return $args['url'];
    }//end getAvatarUrl()
}//end class

?>