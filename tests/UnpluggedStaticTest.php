<?php
declare(strict_types=1);

/**
 * Unit tests for UnpluggedStatic class.
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/PluggableUnplugged
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the UnpluggedStatic class
 */
// @codingStandardsIgnoreLine
final class UnpluggedStaticTest extends TestCase
{
    /**
     * Test punycode of a domain
     *
     * @return void
     */
    public function testPunycodeFunctiond(): void
    {
        $nonascii = 'ουτοπία.δπθ.gr';
        $ascii = 'xn--kxae4bafwg.xn--pxaix.gr';
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::punycodeDomain($nonascii);
        $this->assertEquals($ascii, $actual);
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::unpunycodeDomain($ascii);
        $this->assertEquals($nonascii, $actual);
        $expected = 'user@' . $ascii;
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::punycodeEmail('user@' . $nonascii);
        $this->assertEquals($expected, $actual);
    }//end testPunycodeFunctiond()

    
    /**
     * Test nonce generation
     *
     * @return void
     */
    public function testNonceGeneration(): void
    {
        //first a standard 16 byte nonce (strlen 24)\)
        $foo = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::generateNonce();
        $expected = 24;
        $actual = strlen($foo);
        $this->assertEquals($expected, $actual);
        //now request 8 byte nonce, should still give us a 16
        $foo = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::generateNonce(8);
        $actual = strlen($foo);
        $this->assertEquals($expected, $actual);
        //now rest a 32 byte nonce, should give use one (strlen 44)
        $foo = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::generateNonce(32);
        $expected = 44;
        $actual = strlen($foo);
        $this->assertEquals($expected, $actual);
    }//end testNonceGeneration()

    
    /**
     * Test modification of query args
     *
     * @return void
     */
    public function testModifyQueryArgs(): void
    {
        $baseurl = 'http://example.org';
        $query = array(
            'foo' => 'a',
            'bar' => 'b',
            'foobar' => 'c'
        );
        $expected = 'http://example.org/?foo=a&bar=b&foobar=c';
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::modifyQueryArgs($baseurl, $query);
        $this->assertEquals($expected, $actual);
        // replace bar with something else
        $expected = 'http://example.org/?foo=a&bar=hello&foobar=c';
        $query = array(
            'bar' => 'hello'
        );
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::modifyQueryArgs($actual, $query);
        $this->assertEquals($expected, $actual);
        // remove bar
        $expected = 'http://example.org/?foo=a&foobar=c';
        $rmquery = array('bar');
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::modifyQueryArgs($actual, array(), $rmquery);
        $this->assertEquals($expected, $actual);
        // remove foo and add crazy
        $expected = 'http://example.org/?foobar=c&crazy=bitch';
        $query = array(
            'crazy' => 'bitch'
        );
        $rmquery = array('foo');
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::modifyQueryArgs($actual, $query, $rmquery);
        $this->assertEquals($expected, $actual);
    }//end testModifyQueryArgs()

    
    /**
     * Test cryptohash function
     *
     * @return void
     */
    public function testCryptoHash(): void
    {
        $string = 'Mary had a secret the doctor could not know.';
        $salt = '2QZZHNfcALliPdD1b42ofga0gjb681QuIXLSZlW0';
        $expected = 'm4uYSpbLaARR2zQvLqdKoYthgeVSU5Bl9nPMp8HI+QI=';
        $actual = \AWonderPHP\PluggableUnplugged\UnpluggedStatic::cryptoHash($string, $salt, 32);
        $this->assertEquals($expected, $actual);
    }//end testCryptoHash()
}//end class

?>