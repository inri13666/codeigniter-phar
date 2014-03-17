<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
    exit("PHP must be 5.3.0+");
}

Phar::mapPhar();
$basePath = 'phar://' . __FILE__ . '/';
//Override CI load_class function
if (!function_exists('load_class')) {
    function &load_class($class, $directory = 'libraries', $prefix = 'CI_')
    {
        static $_classes = array();

        // Does the class exist?  If so, we're done...
        if (isset($_classes[$class])) {
            return $_classes[$class];
        } else {
            if (class_exists($name = $prefix . $class)) {
                $_classes[$class] = new $name();
                is_loaded($class);
                return load_class($class, $directory, $prefix);
            }
        }

        $name = FALSE;

        // Look for the class first in the local application/libraries folder
        // then in the native system/libraries folder
        foreach (array(APPPATH, BASEPATH) as $path) {
            if (file_exists($path . $directory . '/' . $class . '.php')) {
                $name = $prefix . $class;

                if (class_exists($name) === FALSE) {
                    require($path . $directory . '/' . $class . '.php');
                }

                break;
            }
        }

        // Is the request a class extension?  If so we load it too
        if (file_exists(APPPATH . $directory . '/' . config_item('subclass_prefix') . $class . '.php')) {
            $name = config_item('subclass_prefix') . $class;

            if (class_exists($name) === FALSE) {
                require(APPPATH . $directory . '/' . config_item('subclass_prefix') . $class . '.php');
            }
        }

        // Did we find the class?
        if ($name === FALSE) {
            // Note: We use exit() rather then show_error() in order to avoid a
            // self-referencing loop with the Excptions class
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            exit('Unable to locate the specified class: ' . $class . '.php');
        }

        // Keep track of what we just loaded
        is_loaded($class);

        $_classes[$class] = new $name();
        return $_classes[$class];
    }
}

//CI Class Auto-loader
spl_autoload_register(function ($class) {
    /**
     * CodeIgniter System Folder
     */
    if (strpos($class, 'CI_') === 0) {
        static $system_dir;
        if (!isset($system_dir)) {
            @include_once BASEPATH . "/helpers/file_helper.php";
            $system_dir = get_dir_file_info(BASEPATH, false, true);
            if ((!$system_dir)) {
                return false;
            }
        }
        if (array_key_exists(str_replace('CI_', '', $class) . '.php', $system_dir)) {
            include_once $system_dir[str_replace('CI_', '', $class) . '.php']['server_path'];
            return true;
        }
    }
}, false, true);


/**
 * Default ENVIRONMENT
 */
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

if (defined('ENVIRONMENT')) {
    switch (ENVIRONMENT) {
        case 'development':
            error_reporting(E_ALL);
            break;

        case 'testing':
        case 'production':
            error_reporting(0);
            break;

        default:
            exit('The application environment is not set correctly.');
    }
}

$system_path = 'system';

// Set the current directory correctly for CLI requests
//TODO ??????
if (defined('STDIN')) {
    chdir(dirname(__FILE__));
}

if (realpath($system_path) !== FALSE) {
    $system_path = realpath($system_path) . '/';
}

// ensure there's a trailing slash
$system_path = $basePath . rtrim($system_path, '/') . '/';

// Is the system path correct?
if (!is_dir($system_path)) {
    exit("Your system folder path does not appear to be set correctly. Please open the following file and correct this: " . pathinfo(__FILE__, PATHINFO_BASENAME));
}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
// The name of THIS file
if (!defined('SELF')) {
    define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
}
// The PHP file extension
// this global constant is deprecated.
if (!defined('EXT')) {
    define('EXT', '.php');
}

// Path to the system folder
if (!defined('BASEPATH')) {
    define('BASEPATH', str_replace("\\", "/", $system_path));
}

// Path to the front controller (this file)
if (!defined('FCPATH')) {
    define('FCPATH', str_replace(SELF, '', __FILE__));
}

// Name of the "system folder"
if (!defined('SYSDIR')) {
    define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));
}

if (!defined('CI_AS_LIBRARY')) {
    define('CI_AS_LIBRARY', false);
}
if (!CI_AS_LIBRARY) {
    if (!defined('APPPATH')) {
        exit("Your application folder path does not appear to be set correctly.");
    }
}

if (CI_AS_LIBRARY) {
    //Use Phar As Library
    /**
     * CodeIgniter Version
     *
     * @var string
     *
     */
    $ver = file_get_contents(BASEPATH . 'core' . DS . 'CodeIgniter.php');
    preg_match_all('/^.*CI_VERSION.*$/im', $ver, $matches);
    if (count($matches)) {
        $to_eval = trim($matches[0][0]);
        $to_eval .= 'return CI_VERSION;';
        $v = eval($to_eval);
        if (!defined('CI_VERSION')) {
            define('CI_VERSION', $v);
        }
    } else {
        define('CI_VERSION', '0.0.0');
    }


    /**
     * CodeIgniter Branch (Core = TRUE, Reactor = FALSE)
     *
     * @var boolean
     *
     */
    preg_match_all('/^.*CI_CORE.*$/im', $ver, $matches);
    if (count($matches)) {
        $to_eval = trim($matches[0][0]);
        $to_eval .= 'return CI_CORE;';
        $v = eval($to_eval);
        if (!defined('CI_CORE')) {
            define('CI_CORE', $v);
        }
    } else {
        define('CI_CORE', FALSE);
    }
    //Load the global functions
    require(BASEPATH . 'core/Common.php');
    //Load the framework constants
    if (defined('ENVIRONMENT') AND file_exists(APPPATH . 'config/' . ENVIRONMENT . '/constants.php')) {
        require(APPPATH . 'config/' . ENVIRONMENT . '/constants.php');
    } else {
        require(APPPATH . 'config/constants.php');
    }

    //TODO Understand Is It Needed, If We do not Use It As Framework
    //set_error_handler('_exception_handler');

    if (!is_php('5.3')) {
        @set_magic_quotes_runtime(0); // Kill magic quotes
    }

    if (isset($assign_to_config['subclass_prefix']) AND $assign_to_config['subclass_prefix'] != '') {
        get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
    }
    //$BM =& load_class('Benchmark', 'core');
    //$BM->mark('total_execution_time_start');
    //$BM->mark('loading_time:_base_classes_start');
    //$EXT =& load_class('Hooks', 'core');
    $CFG =& load_class('Config', 'core');
    // Do we have any manually set config items in the index.php file?
    if (isset($assign_to_config)) {
        $CFG->_assign_to_config($assign_to_config);
    }
    $UNI =& load_class('Utf8', 'core');
    $URI =& load_class('URI', 'core');
    $SEC =& load_class('Security', 'core');
    $IN =& load_class('Input', 'core');
    $LANG =& load_class('Lang', 'core');
    // Load the base controller class
    require BASEPATH . 'core/Controller.php';

    function &get_instance()
    {
        return CI_Controller::get_instance();
    }

    $CI = new CI_Controller();
} else {
    //Use Phar As Frame Work
    /*
     * --------------------------------------------------------------------
     * LOAD THE BOOTSTRAP FILE
     * --------------------------------------------------------------------
     *
     * And away we go...
     *
     */
    require_once BASEPATH . 'core/CodeIgniter.php';
}
__HALT_COMPILER();
?>
