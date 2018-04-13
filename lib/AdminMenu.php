<?php
declare(strict_types=1);

/**
 * PluggableUnplugged Admin Interface
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

namespace AWonderPHP\PluggableUnplugged;

/**
 * Static functions for the admin menu
 */
class AdminMenu
{
    /**
     * Generate the form for domain white list management.
     *
     * @return void
     */
    public static function domainMenu(): void
    {
        echo("<div>\n");
        echo('<h2 style="font-variant: small-caps;">Domain White-List Management</h2>' . "\n");
        // @codingStandardsIgnoreLine
        echo("<p>E-Mail addresses at domains in your white-list will not have the MD5 hash of their e-mail address obfuscated. If these users at white-listed domains have gr*vatar.com accounts, their custom avatars will be used with their comments. Whether or not they have gr*vatar.com accounts, the MD5 hash of their e-mail address will be public information. Please do not white-list domains you or the company you work for do not control.</p>\n");
        echo("<h3>Add Domain to White-List</h3>\n");
        echo("<p>If entering more than one domain, separate domains with a semi-colon ; character.</p>\n");
        
        echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
        echo('<th scope="row"><label for="addDomains">Domains to White-List</label></th>' . "\n");
        // @codingStandardsIgnoreLine
        echo('<td><input type="text" id="addDomains" name="addDomains" size="64" title="Enter domains to white-list" autocomplete="off" /></td>' . "\n");
        echo('</tr></table>' . "\n");
        
        if (! $domains=get_option('groovytarDomains')) {
            $domains=array();
        }
        $j=sizeof($domains);
        if ($j !== 0) {
            echo("<h3>Remove Existing Domains</h3>\n");
            // @codingStandardsIgnoreLine
            echo("<p>If you wish to remove an existing domain from the white-list, check the box next to the domain name.</p>\n");
        
            echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
            echo('<th scope="row">Domains to Remove</th>' . "\n");
            // @codingStandardsIgnoreLine
            echo('<td>' . "\n" . '<fieldset><legend class="screen-reader-text"><span>Domains to Remove</span></legend>');
        
            sort($domains);
            $search=array();
            $replace=array();
            $search[]='/\./';
            $replace[]='_DOT_';
            for ($i=0; $i<$j; $i++) {
                $name='del_' . preg_replace($search, $replace, $domains[$i]);
                $label=UnpluggedStatic::unpunycodeDomain($domains[$i]);
                // @codingStandardsIgnoreLine
                echo('   <input type="checkbox" name="' . $name . '" value="T" id="' . $name . '" /><label for="' . $name . '" title="' . $domains[$i] . '"> ' . $label . "</label><br />\n");
            }
            echo("</fieldset>\n</td></tr></table>");
        }
        echo("</div>\n");
        return;
    }//end domainMenu()

    /**
     * Generate the form for address white list management.
     *
     * @return void
     */
    public static function addressMenu(): void
    {
        echo("<div>\n");
        echo('<h2 style="font-variant: small-caps;">E-Mail White-List Management</h2>' . "\n");
        // @codingStandardsIgnoreLine
        echo("<p>E-Mail addresses in your white-list will not have their MD5 hash of their e-mail address obfuscated. If users with white-listed e-mail addresses have gr*vatar.com accounts, their custom avatars will be used with their comments. Whether or not they have gr*vatar.com accounts, the MD5 hash of their e-mail address will be public information. Please do not white-list e-mail addresses without consent of the user.</p>");
        echo("<h3>Add E-Mail Address to White-List</h3>\n");
        echo("<p>If entering more than one e-mail address, separate them with a semi-colon ; character.</p>\n");
    
        echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
        echo('<th scope="row"><label for="addEmails">New Addresses to White-List</label></th>' . "\n");
        // @codingStandardsIgnoreLine
        echo('<td><input type="text" id="addEmails" name="addEmails" size="64" title="Enter e-mail addresses to white-list" autocomplete="off" /></td>' . "\n");
        echo('</tr></table>' . "\n");
        
        $addys = array();
        if (! $configAddys=get_option('groovytarAddresses')) {
            $configAddys=array();
        }
        foreach($configAddys as $eadd) {
            if(! in_array($eadd, array('anonymous@gravatar.com', 'wapuu@wordpress.example'))) {
                $addys[] = $eadd;
            }
        }
        $j=sizeof($addys);
        if ($j !== 0) {
            echo("<h3>Remove Existing E-Mail Addresses</h3>\n");
            // @codingStandardsIgnoreLine
            echo("<p>If you wish to remove an e-mail address from the white-list, check the box next to the e-mail address.</p>\n");
        
            echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n");
            echo('<th scope="row">Addresses to Remove</th>' . "\n");
            // @codingStandardsIgnoreLine
            echo('<td>' . "\n" . '<fieldset><legend class="screen-reader-text"><span>Addresses to Remove</span></legend>');
        
            sort($addys);
            $search=array();
            $replace=array();
            $search[]='/@/';
            $replace[]='_AT_';
            $search[]='/\./';
            $replace[]='_DOT_';
            for ($i=0; $i<$j; $i++) {
                $name='del_' . preg_replace($search, $replace, $addys[$i]);
                $tmp=explode('@', $addys[$i]);
                $label=$tmp[0] . '@' . UnpluggedStatic::unpunycodeDomain($tmp[1]);
                // @codingStandardsIgnoreLine
                echo('   <input type="checkbox" name="' . $name . '" value="T" id="' . $name . '" /><label for="' . $name . '" title="' . $addys[$i] . '"> ' . $label . "</label><br />\n");
            }
            echo("</fieldset>\n</td></tr></table>");
        }
        echo("</div>\n");
        return;
    }//end addressMenu()

    /**
     * Generate the form for salt management.
     *
     * @return void
     */
    public static function saltMenu(): void
    {
        echo("<div>\n");
        echo('<h2 style="font-variant: small-caps;">Obfuscation Salts</h2>' . "\n");
        // @codingStandardsIgnoreLine
        echo("<p>A salt is a randomized string that should have at least 256 bits (2<sup>256</sup>) of entropy that is often used when obfuscating a hash to thwart <a href=\"https://en.wikipedia.org/wiki/Rainbow_table\" target=\"_blank\">Rainbow Table</a> attacks. If the attacker does not know the value of the salts, the attacker can not generate a table of hashes that will correspond to the hash you use. The PluggableUnplugged plugin uses two salts in the obfuscation of e-mail address hashes.</p>\n");
        // @codingStandardsIgnoreLine
        echo("<p>It is suggested you allow PluggableUnplugged to generate the salts for you. The salts generated by this class are base64 encoded strings of a 256-bit random number generated using a random generator suitable for cryptography. See <a href=\"http://php.net/manual/en/function.random-bytes.php\" target=\"_blank\">random_bytes</a> for more information on the random generation used for the salts by this plugin.</p>");
        // @codingStandardsIgnoreLine
        echo("<p>If you run multiple blogs and you want your users to have the same obfuscated hash between blogs, then you can manually create the salts. If you do so, make sure they are at least 40 characters long and are made up using an arrangement of many different characters. Please note that with the base64 alphabet (64 characters) it takes 43 characters to reach the 256-bits of entropy that are generally recommended for a quality salt. With the larger alphabet some salt generators use, you can reach that level of entropy with fewer characters, but I recommend using at least 40. A minimum of 18 is required.</p>\n");
    // @codingStandardsIgnoreLine
        echo('<p>The salts currently being used by your install of PluggableUnplugged:</p>' . "\n" . '<div style="background-color: #cccccc; padding: 1em;">');
        echo('<ol style="font-family: monospace;">' . "\n");
        if (! $salts=get_option('groovytarSalts')) {
            $salts = Groovytar::generateSalts();
            update_option('groovytarSalts', $salts);
        }
        $search=array();
        $replace=array();
        $search[]='/&/';
        $replace[]='&amp;';
        $search[]='/</';
        $replace[]='&lt;';
        $search[]='/>/';
        $replace[]='&gt;';
        $aa=preg_replace($search, $replace, $salts[0]);
        $bb=preg_replace($search, $replace, $salts[1]);
        echo('<li>' . $aa . '</li>' . "\n" . '<li>' . $bb . '</li>' . "\n</ol>\n</div>");
        
        echo("<h3>Regenerate Salts</h3>\n");
        echo("<p>If for some reason you wish to regenerate the salts, check the box below:</p>\n");
        // @codingStandardsIgnoreLine
        echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">Random Salts</th>' . "\n");
        
        // @codingStandardsIgnoreLine
        echo('<td><input type="checkbox" name="groovytarRegenSalts" id="groovytarRegenSalts" value="T" /><label for="groovytarRegenSalts"> Regenerate Salts</label></td>' . "\n");
        echo("</tr>\n</table>");
    
        echo("<h3>Custom Salts</h3>\n");
        // @codingStandardsIgnoreLine
        echo("<p>If you wish to manually create your own salts, place your salt strings in the two input fields below. They must be at least 18 characters in length.</p>\n");
        // @codingStandardsIgnoreLine
        echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">Custom Salts</th>' . "\n");
        echo('<td><fieldset><legend class="screen-reader-text"><span>Custom Salts</span></legend>');
        // @codingStandardsIgnoreLine
        echo('<input type="text" id="groovytarSaltOne" name="groovytarSaltOne" size="64" title="Enter a salt string at least 18 characters long." autocomplete="off" /><br />' . "\n");
        echo('<label for="groovytarSaltOne">First Custom Salt</label><br />&#160;<br />' . "\n");
        // @codingStandardsIgnoreLine
        echo('<input type="text" id="groovytarSaltTwo" name="groovytarSaltTwo" size="64" title="Enter another salt string at least 18 characters long." autocomplete="off" /><br />' . "\n");
        echo('<label for="groovytarSaltTwo">Second Custom Salt</label>' . "\n");
        echo("</fieldset>\n</td>\n</tr>\n</table>");
    
        echo("</div>\n");
    }//end saltMenu()

    /**
     * Generate the form for user notify
     *
     * @return void
     */
    public static function userNotify(): void
    {
        echo("<div>\n");
        echo('<h2 style="font-variant: small-caps;">User Notification</h2>' . "\n");
        // @codingStandardsIgnoreLine
        echo("<p>You can notify users of your blog that you are using PluggableUnplugged by checking the box below. I would greatly appreciate this, and it will also let your users know that you care about their privacy and that their e-mail hash will be obfuscated when they post a comment so that they can not be tracked.</p>\n");
        echo("<p>If you check the box, the following notice will appear in the footer of your pages:</p>");
        groovytarFooter();
        echo("\n");
        // @codingStandardsIgnoreLine
        echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">User Notification</th>' . "\n");
        if ($footerPermission=get_option('PluggableUnpluggedFooter')) {
            $checked=' checked="checked"';
        } else {
            $checked='';
        }
        // @codingStandardsIgnoreLine
        echo('<td><input type="checkbox" name="pluggableUnpluggedNotify" value="T" id="pluggableUnpluggedNotify"' . $checked . ' /><label for="pluggableUnpluggedNotify"> Allow Notification to Users of PluggableUnplugged usage</label></td>' . "\n");
        echo("</tr>\n</table>");
    
        echo("</div>\n");
    }//end userNotify()
    
    /**
     * Generate the form for argon2id
     *
     * @return void
     */
    public static function switchToArgon(): void
    {
        echo("<div>\n");
        echo('<h2 style="font-variant: small-caps;">Use Argon2id Password Hashing</h2>' . "\n");
        echo('<p>Argon2 is a modern algorithm for password hashing that is currently generally recommended over all other forms of password hashing, including what WordPress natively uses.</p>');
        echo('<p>Argon2 is the winner of the <a href="https://password-hashing.net/" target="_blank">Password Hashing Competition</a> that ran from 2013 to 2015 looking for the best password hashing algorithm. Argon2 is also the password hashing algorithm recommended by <a href="https://download.libsodium.org/doc/password_hashing/the_argon2i_function.html" target="_blank">libsodium</a> for passwords.</p>');
        echo("<p>There are three variants of Argon2. The Argon2id variant is the variant used here.</p>");
        echo("<p>If you wish to enable Argon2id hashing, as each registered user of your blog logs in, their existing password hash will be updated to use Argon2id for the password hashing. However, there is a caveat:</p>");
        echo("<p>If you ever remove this plugin, the password hashes can not be reverted without the users resetting their password.</p>");
        echo("<p>The advantage Argon2id gives over standard WordPress password hashing is only present when an attacker manages to get a dump of your password database, a fairly common occurrence with WordPress because WordPress unwisely uses a single database for everything. When the passwords are hashed with Argon2id, it becomes a lot more difficult for the attacker to figure out what the actual passwords are even when they have the hashes to them.</p>");
        echo("<p>Whether you choose to enable this feature or not is up to you. Obviously I recommend it or it would not be here, but it does mean that removing this plugin will result in your users needing to do a password reset, therefore I could not in good conscious default to it. So the choice is yours.</p>");
        echo('<table class="form-table">' . "\n" . '<tr valign="top">' . "\n" . '<th scope="row">Use Argon2id Password Hashing</th>' . "\n");
        if ($argonPassHash=get_option('PluggableUnpluggedUseArgon')) {
            $checked=' checked="checked"';
        } else {
            $checked='';
        }
        echo('<td><input type="checkbox" name="argon2idHash" value="T" id="argon2idHash"' . $checked . ' /><label for="argon2idHash"> Use the Argon2id Password Hashing Algorithm</label></td>' . "\n");
        echo("</tr>\n</table>");
        
        echo("</div>\n");
    }

    /**
     * Process the form
     *
     * @return void
     */
    public static function processForm(): void
    {
        $groovytar = new \AWonderPHP\PluggableUnplugged\WordPressGroovytar;
        if (! $whitedomains=get_option('groovytarDomains')) {
            $whitedomains=array();
        }
        foreach ($whitedomains as $input) {
            try {
                $groovytar->addDomain($input);
            } catch (\InvalidArgumentException $e) {
                error_log($e->getMessage());
            }
        }
        if (! $whiteaddresses=get_option('groovytarAddresses')) {
            $whiteaddresses=array();
        }
        foreach ($whiteaddresses as $input) {
            try {
                $groovytar->addEmailAddress($input);
            } catch (\InvalidArgumentException $e) {
                error_log($e->getMessage());
            }
        }
        
        $error = array();
        // remove white-listed domains
        if (! $domains=get_option('groovytarDomains')) {
            $domains=array();
        }
        $j=sizeof($domains);
        $remove=array();
        $search=array();
        $replace=array();
        $search[]='/\./';
        $replace[]='_DOT_';
        for ($i=0; $i<$j; $i++) {
            $test='del_' . preg_replace($search, $replace, $domains[$i]);
            if (isset($_POST[$test])) {
                $remove[]=$domains[$i];
            }
        }
        if (sizeof($remove) > 0) {
            foreach ($remove as $nuke) {
                try {
                    $res = $groovytar->removeDomain($nuke);
                } catch (\InvalidArgumentException $e) {
                    $error[] = $e->getMessage();
                }
            }
            if (isset($res)) {
                update_option('groovytarDomains', $res);
            }
        }
        //add white-list domains
        if (isset($_POST['addDomains'])) {
            $domainsToAdd=trim(urldecode($_POST['addDomains']));
            if (strlen($domainsToAdd) > 0) {
                $add = explode(';', $domainsToAdd);
                foreach ($add as $dmn) {
                    try {
                        $addResult = $groovytar->addDomain($dmn);
                    } catch (\InvalidArgumentException $e) {
                        $error[] = $e->getMessage();
                    }
                }
                if (isset($addResult)) {
                    update_option('groovytarDomains', $addResult);
                }
            }
        }
        //remove white-list e-mail addresses
        if (! $addys=get_option('groovytarAddresses')) {
            $addys=array();
        }
        $j=sizeof($addys);
        $remove=array();
        $search=array();
        $replace=array();
        $search[]='/@/';
        $replace[]='_AT_';
        $search[]='/\./';
        $replace[]='_DOT_';
        for ($i=0; $i<$j; $i++) {
            $test='del_' . preg_replace($search, $replace, $addys[$i]);
            if (isset($_POST[$test])) {
                $remove[]=$addys[$i];
            }
        }
        if (sizeof($remove) > 0) {
            foreach ($remove as $rmAdd) {
                try {
                    $rmAddRes = $groovytar->removeEmailAddress($rmAdd);
                } catch (\InvalidArgumentException $e) {
                    $error[] = $e->getMessage();
                }
            }
            if (isset($rmAddRes)) {
                update_option('groovytarAddresses', $rmAddRes);
            }
        }
        //add white-listed e-mails
        if (isset($_POST['addEmails'])) {
            $emailsToAdd=trim(urldecode($_POST['addEmails']));
            if (strlen($emailsToAdd) > 0) {
                $arr = explode(';', $emailsToAdd);
                foreach ($arr as $emailAddy) {
                    try {
                        $addAddy = $groovytar->addEmailAddress($emailAddy);
                    } catch (\InvalidArgumentException $e) {
                        $error[] = $e->getMessage();
                    }
                }
                if (isset($addAddy)) {
                    update_option('groovytarAddresses', $addAddy);
                }
            }
        }
        //salts
        $nsalt=array();
        if (isset($_POST['groovytarRegenSalts'])) {
            delete_option('groovytarSalts');
        }
        if (isset($_POST['groovytarSaltOne'])) {
            $sone=trim($_POST['groovytarSaltOne']);
        } else {
            $sone='';
        }
        if (isset($_POST['groovytarSaltTwo'])) {
            $stwo=trim($_POST['groovytarSaltTwo']);
        } else {
            $stwo='';
        }
        
        if (strlen($sone) > 0) {
            if (strlen($sone) > 17) {
                $nsalt[]=$sone;
            } else {
                $error[]='First custom salt is too short. It must be at least 18 characters long.';
            }
        }
        if (strlen($stwo) > 0) {
            if (strlen($stwo) > 17) {
                $nsalt[]=$stwo;
            } else {
                $error[]='Second custom salt is too short. It must be at least 18 characters long.';
            }
        }
        if (sizeof($nsalt) === 1) {
            $error[]='If using custom salts, you need two custom salts, each at least 18 characters long.';
        }
        if (sizeof($nsalt) === 2) {
            update_option('groovytarSalts', $nsalt);
        }
        
        
        
        // user notify
        //notify
        if (isset($_POST['pluggableUnpluggedNotify'])) {
            update_option('PluggableUnpluggedFooter', 't');
        } else {
            delete_option('PluggableUnpluggedFooter');
        }
        
        // switch to argon hash?
        if(isset($_POST['argon2idHash'])) {
            update_option('PluggableUnpluggedUseArgon', 't');
        } else {
            delete_option('PluggableUnpluggedUseArgon');
        }
        
        // report errors
        $j=sizeof($error);
        if ($j > 0) {
            if ($j === 1) {
                echo('<div class="error">' . "\n<p>The following error occurred:</p><ol>");
            } else {
                echo('<div class="error">' . "\n<p>The following errors occurred:</p><ol>");
            }
            for ($i=0; $i<$j; $i++) {
                echo('<li>' . $error[$i] . '</li>' . "\n");
            }
            echo("</ol>\n</div>\n");
        }
    }//end processForm()
}//end class

?>