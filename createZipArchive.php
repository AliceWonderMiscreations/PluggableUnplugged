#!/usr/bin/env php
<?php
/**
 * Creates a zip archive containing the needed class files and a
 * digest xml file with the current checksum of each file
 *
 * @package AWonderPHP/PluggableUnplugged
 * @author  Alice Wonder <paypal@domblogger.net>
 * @license https://opensource.org/licenses/MIT MIT
 * @link    https://github.com/AliceWonderMiscreations/AWonderPHP
 */

$timezone = 'America/Los_Angeles';
$pluginName = 'AWM Pluggable Unplugged';
$url = 'https://github.com/AliceWonderMiscreations/PluggableUnplugged/';
$file_hash_algo = 'ripemd160';
$excludedirlist = array('vendor', 'tests');
$excludefilelist = array('createZipArchive.php');
$explicitFiles = array(
    'donate.md',
    'README.md',
    'LICENSE.md',
    'TGMPA-GPLv2-LICENSE.md'
);

date_default_timezone_set($timezone);
$now = time();

$digest = new DOMDocument();
$digest->formatOutput = true;
$docstring = '<?xml version="1.0" encoding="UTF-8"?><xml />';
$digest->loadXML($docstring);
$xml = $digest->getElementsByTagName('xml')->item(0);

$date = date("Y-m-d\TH:i:s", $now);
$offset = date("P", $now);

$collection = $digest->createElement('plugin', $pluginName);
$xml->appendChild($collection);
$url = $digest->createElement('url', $url);
$xml->appendChild($url);
$tstamp = $digest->createElement('timestamp', $date);
$tstamp->setAttribute('timezone', $timezone);
$tstamp->setAttribute('offset', $offset);
$xml->appendChild($tstamp);

$filelist = array();
$dirlist = array('');

/**
 * Creates an array containing all directories to scan for files in
 *
 * @param string $parent The parent directory of what is being scanned relative to the
 *                       directory script is being run from.
 *
 * @return void
 */
function addToDirectoryList($parent = '')
{
    global $dirlist;
    global $excludedirlist;
    if ($parent === '') {
        $fullpath = __DIR__;
    } else {
        $fullpath = __DIR__ . '/' . $parent;
    }
    $tmplist = scandir($fullpath);
    foreach ($tmplist as $chk) {
        $dottest = $chk[0];
        if ($dottest !== '.') {
            $relchk = $chk;
            if ($parent !== '') {
                $relchk = $parent . '/' . $chk;
            }
            if (is_dir($relchk)) {
                $basename = basename($relchk);
                if (! in_array($basename, $excludedirlist)) {
                    if (! in_array($relchk, $dirlist)) {
                        $dirlist[] = $relchk;
                    }
                }
            }
        }
    }
}//end addToDirectoryList()

/**
 * Creates an array containing all filenames that are part of the collection.
 *
 * @param string $dir The directory being scanned relative to the directory the script is
 *                    being run from.
 *
 * @return void
 */
function addToFileList($dir)
{
    global $filelist;
    global $excludefilelist;
    if ($dir === '') {
        $fullpath = __DIR__;
    } else {
        $fullpath = __DIR__ . '/' . $dir;
    }
    $tmplist = scandir($fullpath);
    foreach ($tmplist as $chk) {
        $dottest = $chk[0];
        if ($dottest !== '.') {
            $phptest = substr($chk, -4);
            if ($phptest === '.php') {
                if (! in_array($chk, $excludefilelist)) {
                    $file = $chk;
                    if ($dir !== '') {
                        $file = $dir . '/' . $chk;
                    }
                    if (! in_array($file, $filelist)) {
                        $filelist[] = $file;
                    }
                }
            }
        }
    }
}//end addToFileList()

// make recursive directory list
while (true) {
    $start = count($dirlist);
    foreach ($dirlist as $dir) {
        addToDirectoryList($dir);
    }
    $end = count($dirlist);
    if ($start === $end) {
        break;
    }
}

foreach ($dirlist as $dir) {
    addToFileList($dir);
}
asort($filelist);
foreach($explicitFiles as $file) {
    $filelist[] = $file;
}

$xmlfilelist = $digest->createElement('filelist');
$xml->appendChild($xmlfilelist);

foreach ($filelist as $file) {
    $fullpath = __DIR__ . '/' . $file;
    $raw = hash_file($file_hash_algo, $fullpath, true);
    $hash = base64_encode($raw);
    $node = $digest->createElement('file', $file);
    $node->setAttribute('algo', $file_hash_algo);
    $node->setAttribute('hash', $hash);
    // find the namespace
    $contents = file_get_contents($fullpath);
    $arr = explode("\n", $contents);
    $nspaceArr = preg_grep('/^namespace/', $arr);
    if (count($nspaceArr) > 0) {
        $arr = array_values($nspaceArr);
        $nspaceLine = $arr[0];
        $nspaceLine = preg_replace('/^namespace\s+/', "\\", $nspaceLine);
        $arr = explode(';', $nspaceLine);
        $nspace = trim($arr[0]);
        $node->setAttribute('namespace', $nspace);
    }
    $node->setAttribute('bytes', filesize($fullpath));
    $xmlfilelist->appendChild($node);
}

$classDigest = __DIR__ . '/PluginDigest.xml';

$write = file_put_contents($classDigest, $digest->saveXML());
if (! is_bool($write)) {
  //create zip archive
    $zipPluginName = strtolower($pluginName);
    $zipPluginName = preg_replace('/\s+/', '-', $zipPluginName);
    $archiveName = $zipPluginName . '-' . $now . '.zip';
    $zip = new ZipArchive();
  
    $zip->open($archiveName, ZipArchive::CREATE);
    $zip->addFile($classDigest, $zipPluginName . '/PluginDigest.xml');
    foreach ($filelist as $file) {
        $fullpath = __DIR__ . '/' . $file;
        $zip->addFile($fullpath, $zipPluginName . '/' . $file);
    }
    $zip->close();
    echo "Archive Collection Created. Please double check contents.\n";
} else {
    echo "Houston, we have a problem.\n";
}

?>