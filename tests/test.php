<?php

// This is where "prepared" templates will be stored, if you decide to run 
// Sigma_cache_testcase. Make sure that this directory is writeable for PHP
$Sigma_cache_dir = './templates/prepared';

// What class are we going to test?
// It is possible to also use the unit tests to test HTML_Template_ITX, which
// also implements Integrated Templates API
$IT_class = 'Sigma';
// $IT_class = 'ITX';

// Sigma_cache_testcase is useless if testing HTML_Template_ITX
$testcases = array(
    'Sigma_api_testcase',
    'Sigma_cache_testcase',
    'Sigma_usage_testcase'
);

// BC hack to define PATH_SEPARATOR for version of PHP prior 4.3
if(!defined('PATH_SEPARATOR')) {
    if(defined('DIRECTORY_SEPARATOR') && DIRECTORY_SEPARATOR == "\\") {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}
ini_set('include_path', '..'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit.php';
require_once $IT_class . '.php';

$suite =& new PHPUnit_TestSuite();

foreach ($testcases as $testcase) {
    include_once $testcase . '.php';
    $methods = preg_grep('/^test/', get_class_methods($testcase));
    foreach ($methods as $method) {
        $suite->addTest(new $testcase($method));
    }
}

require_once 'Console_TestListener.php';
$result =& new PHPUnit_TestResult();
$result->addListener(new Console_TestListener);

$suite->run($result);
?>
