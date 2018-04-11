<?php
declare(strict_types=1);

/**
 * Unit tests for Groovytar class.
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the Groovytar class
 */
// @codingStandardsIgnoreLine
final class GroovytarTest extends TestCase
{
    /**
     * PHPUnit setup, create an anonymous instance of the Groovytar class
     *
     * @return void
     */
    public function setup()
    {
        $this->testGroovy = new class extends \AWonderPHP\PluggableUnplugged\Groovytar {
            /**
             * Predictable but different tests salts
             */
            protected $salts = array('aaaaaa', 'bbbbbb');
            
            /**
             * Access a protected method
             *
             * @param string $email The parameter to feed to mimicHash().
             *
             * @return string The hash to return.
             */
            public function getMimicHash($email)
            {
                return $this->mimicHash($email);
            }//end getMimicHash()

        };
    }//end setup()

    /**
     * Make sure generation of salts works as expected
     *
     * @return void
     */
    public function testSaltGeneration(): void
    {
        $bad = false;
        $usedArray = array();
        for ($i=0; $i<25; $i++) {
            $salt = $this->testGroovy->generateSalts();
            $a = $salt[0];
            $b = $salt[1];
            $usedCount = 0;
            if ($a === $b) {
                $usedCount++;
            }
            if (in_array($a, $usedArray)) {
                $usedCount++;
            }
            if (in_array($b, $usedArray)) {
                $usedCount++;
            }
            $usedArray[] = $a;
            $usedArray[] = $b;
            $aa = strlen($a);
            $bb = strlen($b);
            $this->assertEquals(0, $usedCount);
            $this->assertEquals(44, $aa);
            $this->assertEquals(44, $bb);
        }
    }//end testSaltGeneration()

    /**
     * Make sure we the email obfuscation works when we want it to but can be turned off
     * when we do not want it.
     *
     * @return void
     */
    public function testHashObfuscation(): void
    {
        $foo = 'someuser@example.org';
        // first test - the expected
        
        $obf=hash('sha256', 'aaaaaa' . $foo, false);
        $obf=hash('sha256', 'bbbbbb' . $obf, false);
        $expected = substr($obf, 4, 32);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        // now test same address without obfuscation
        $this->testGroovy->addEmailAddress($foo);
        $expected = md5($foo);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        //now test different user same domain
        $foo = 'someotheruser@example.org';
        $obf=hash('sha256', 'aaaaaa' . $foo, false);
        $obf=hash('sha256', 'bbbbbb' . $obf, false);
        $expected = substr($obf, 4, 32);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        // now whitelist whole domain
        $this->testGroovy->addDomain('example.org');
        $expected = md5($foo);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        //now test a subdomain
        $foo = 'crazy@test.test.test.example.org';
        $expected = md5($foo);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        //now test one of the default whitelisted addresses
        $foo='wapuu@wordpress.example';
        $expected = md5($foo);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        // now un-whitelist
        $this->testGroovy->removeEmailAddress($foo);
        $obf=hash('sha256', 'aaaaaa' . $foo, false);
        $obf=hash('sha256', 'bbbbbb' . $obf, false);
        $expected = substr($obf, 4, 32);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
        
        // now un-whitelist domain
        $this->testGroovy->removeDomain('example.org');
        $foo = 'crazy@test.test.test.example.org';
        $obf=hash('sha256', 'aaaaaa' . $foo, false);
        $obf=hash('sha256', 'bbbbbb' . $obf, false);
        $expected = substr($obf, 4, 32);
        $actual = $this->testGroovy->getMimicHash($foo);
        $this->assertEquals($expected, $actual);
    }//end testHashObfuscation()


    /**
     * Make sure generation of a reference ripemd160 works
     *
     * @return void
     */
    public function testReferenceHash(): void
    {
        $foo = 'user@example.org';
        $prehash = $foo;
        for ($i=0; $i<5; $i++) {
            $prehash = hash('sha384', $prehash, false);
        }
        $data = hash('ripemd160', $prehash, true);
        $expected = base64_encode($data);
        $actual = $this->testGroovy->referenceHash($foo);
        $this->assertEquals($expected, $actual);
    }//end testReferenceHash()

    /**
     * Make sure we can populate an array of avatar data like WordPress wants.
     *
     * @return void
     */
    public function testGetAvatarData(): void
    {
        $foo = 'user@example.org';
        $expected = array(
            'size' => 96,
            'height' => 96,
            'width' => 96,
            'default' => 'monsterid',
            'force_default' => false,
            'rating' => 'g',
            'processed_args' => null,
            'extra_attr' => '',
            'scheme' => null,
            'found_avatar' => true,
            'url' => 'https://secure.gravatar.com/avatar/6f7a27a19540c56af900cd8c98eed5b4?s=96&d=monsterid&r=g'
        );
        $actual = $this->testGroovy->getAvatarData($foo);
        //var_dump($actual);
        $this->assertEquals($expected, $actual);
        //$url2x = $groovytar->getAvatarUrl($id_or_email, array_merge($args, array( 'size' => $args['size'] * 2 )));
        $expected = 'https://secure.gravatar.com/avatar/6f7a27a19540c56af900cd8c98eed5b4?s=192&d=monsterid&r=g';
        $actual = $this->testGroovy->getAvatarUrl($foo, array_merge($actual, array( 'size' => $actual['size'] * 2 )));
        $this->assertEquals($expected, $actual);
    }//end testGetAvatarData()
}//end class

?>