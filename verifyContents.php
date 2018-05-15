#!/usr/bin/env php
<?php
/**
 * Verifies the checksums in present against a digest file.
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/AWonderPHP
 */

$pluginDigest = __DIR__ . '/PluginDigest.xml';

if (! file_exists($pluginDigest)) {
    echo "Digest file could not be found.\n";
    exit;
}

$string = file_get_contents($pluginDigest);

$digest = new DOMDocument();
$digest->loadXML($string);

/**
 * Verifies a file meets the specified parameters.
 *
 * @param string $path The full path to file being checked.
 * @param int    $size The expected size in bytes.
 * @param string $algo The algorithm to use when checking the hash.
 * @param string $hash The base64 encoded hash to check against.
 *
 * @return bool True on validation, otherwise false.
 */
function validateFile(string $path, int $size, string $algo, string $hash): bool
{
    if (! file_exists($path)) {
        echo "File " . $path . " does not exist.\n";
        return false;
    }
    $checksize = intval(filesize($path));
    if ($size !== $checksize) {
        echo "File " . $path . " has the wrong filesize.\n";
        return false;
    }
    $raw = hash_file($algo, $path, true);
    $chkhash = base64_encode($raw);
    if ($chkhash !== $hash) {
        echo "File " . $path . " failed the checksum test.\n";
        return false;
    }
    return true;
}//end validateFile()


$fileNodeList = $digest->getElementsByTagName('file');
$j = $fileNodeList->length;

$error_files = array();

for ($i=0; $i<$j; $i++) {
    $node = $fileNodeList->item($i);
    $filename = __DIR__ . '/' . $node->nodeValue;
    $filesize = intval($node->getAttribute('bytes'));
    $algo = $node->getAttribute('algo');
    $hash = $node->getAttribute('hash');
    if (! validateFile($filename, $filesize, $algo, $hash)) {
        $error_files[] = $filename;
    }
}

if (count($error_files) !== 0) {
    echo "The following files had errors:\n";
    foreach ($error_files as $str) {
        echo "* " . $str . "\n";
    }
} else {
    echo "Package Integrity Passed.\n";
}

?>