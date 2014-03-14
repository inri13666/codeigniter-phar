<?php
define('DS', DIRECTORY_SEPARATOR);
if (!is_dir(__DIR__ . DS . 'compiled')) {
    if (!mkdir(__DIR__ . DS . 'compiled')) {
        throw new Exception('Cant Create Folder');
    };
}

$filename = __DIR__ . DS . 'compiled' . DS . 'codeigniter';
/**
 * Remove Previous Compiled Archives
 */
if (is_readable($filename)) {
    unlink($filename);
}

$archive = new Phar($filename . '.phar', 0, 'Smarty');
$archive->buildFromDirectory('libs');
$bootstrap = file_get_contents(__DIR__ . DS . 'phar-bootstrap.php');
$archive->setStub($bootstrap);
$archive = null;
unset($archive);
$ver = @file_get_contents(__DIR__ . DS . 'libs' . DS . 'system' . DS . 'core' . DS . 'CodeIgniter.php');
preg_match_all('/^.*CI_VERSION.*$/im', $ver, $matches);
if (count($matches)) {
    $to_eval = trim($matches[0][0]);
    $to_eval .= 'return CI_VERSION;';
    define('CI_VERSION', eval($to_eval));
    if ((defined('CI_VERSION')) && (CI_VERSION)) {
        file_put_contents($filename . '-' . CI_VERSION . '.phar', file_get_contents($filename . '.phar'));
    }
};

if (extension_loaded('zlib')) {
    //Create GZ Archive, That will use Phar's Stub
    if (function_exists('gzopen')) {
        if (is_readable($filename . '.gz')) {
            unlink($filename . '.gz');
        }
        $gz = gzopen($filename . '.gz', 'w9');
        gzwrite($gz, file_get_contents($filename . '.phar'));
        gzclose($gz);
        if ((defined('CI_VERSION')) && (CI_VERSION)) {
            file_put_contents($filename . '-' . CI_VERSION . '.gz', file_get_contents($filename . '.gz'));
        }
    }
}
if (extension_loaded('bz2')) {
    //Create BZ2 Archive, That will use Phar's Stub
    if (function_exists('bzopen')) {
        if (is_readable($filename . '.bz2')) {
            unlink($filename . '.bz2');
        }
        $bz2 = bzopen($filename . '.bz2', 'w');
        bzwrite($bz2, bzcompress(file_get_contents($filename . '.phar'), 9));
        bzclose($bz2);
        if ((defined('CI_VERSION')) && (CI_VERSION)) {
            file_put_contents($filename . '-' . CI_VERSION . '.bz2', file_get_contents($filename . '.bz2'));
        }
    }
} else {
    //
}