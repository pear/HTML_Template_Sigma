<?php
/**
 * Unit tests for HTML_Template_Sigma package.
 *
 * @author Alexey Borzov <avb@php.net>
 *
 * $Id$
 */

class Sigma_bug_testcase extends PHPUnit_TestCase
{
   /**
    * A template object
    * @var object
    */
    var $tpl;

    function Sigma_bug_TestCase($name)
    {
        $this->PHPUnit_TestCase($name);
    }

    function setUp()
    {
        $className = 'HTML_Template_' . $GLOBALS['IT_class'];
        $this->tpl =& new $className('./templates');
    }

    function tearDown()
    {
        unset($this->tpl);
    }

    function testBug6902()
    {
        global $Sigma_cache_dir;

        if (OS_WINDOWS) {
            // realpath() on windows will return full path including drive letter
            $this->tpl->setRoot('');
            $this->tpl->setCacheRoot($Sigma_cache_dir);
            $result = $this->tpl->loadTemplatefile(realpath('./templates') . '\\' . 'loadtemplatefile.html');
            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
            }
            $this->assertEquals('A template', trim($this->tpl->get()));
            $result = $this->tpl->loadTemplatefile(realpath('./templates') . '\\' . 'loadtemplatefile.html');
            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
            }
            $this->assertEquals('A template', trim($this->tpl->get()));
        }
    }
}
?>
