<?php
/**
 * Unit tests for HTML_Template_Sigma class
 * 
 * $Id$
 */

require_once 'System.php';

$Sigma_cache_dir = System::mktemp('-d sigma');

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

if (@file_exists('../' . $IT_class . '.php')) {
    require_once '../' . $IT_class . '.php';
} else {
    require_once 'HTML/Template/' . $IT_class . '.php';
}

require_once 'PHPUnit.php';

$suite =& new PHPUnit_TestSuite();

foreach ($testcases as $testcase) {
    include_once $testcase . '.php';
    $methods = preg_grep('/^test/', get_class_methods($testcase));
    foreach ($methods as $method) {
        $suite->addTest(new $testcase($method));
    }
}

require_once './Console_TestListener.php';
$result =& new PHPUnit_TestResult();
$result->addListener(new Console_TestListener);

$suite->run($result);
?>
