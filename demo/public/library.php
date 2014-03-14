<?php
/**
 * Created by PhpStorm.
 * User: Nikita.Makarov
 * Date: 3/14/14
 * Time: 8:52 AM
 */
define('CI_AS_LIBRARY',true);
define('APPPATH', str_replace('\\', '/', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'application/'));
require_once 'phar://' . dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'codeigniter.bz2';
//Check DB Driver
var_dump(get_instance()->load->database('default',true));
//Check Class Auto-loader
var_dump(new CI_Encrypt());