<?php

/**
 * Unit tests for HTML_Template_Sigma package.
 * 
 * @author Alexey Borzov <avb@php.net>
 * 
 * $Id$
 */

require_once 'Sigma_api_testcase.php';

class Sigma_cache_TestCase extends Sigma_api_TestCase
{
    function Sigma_cache_TestCase($name)
    {
        $this->Sigma_api_TestCase($name);
    }

    function setUp()
    {
        global $Sigma_cache_dir;

        $className = 'HTML_Template_' . $GLOBALS['IT_class'];
        $this->tpl =& new $className('./templates', $Sigma_cache_dir);
    }

    function _removeCachedFiles($filename)
    {
        if (!is_array($filename)) {
            $filename = array($filename);
        }
        foreach ($filename as $file) {
            $cachedName = $this->tpl->_cachedName($file);
            if (@file_exists($cachedName)) {
                @unlink($cachedName);
            }
        }
    }

    function assertCacheExists($filename)
    {
        if (!is_array($filename)) {
            $filename = array($filename);
        }
        foreach ($filename as $file) {
            $cachedName = $this->tpl->_cachedName($file);
            if (!@file_exists($cachedName)) {
                $this->assertTrue(false, "File '$file' is not cached");
            }
        }
    }

    function testLoadTemplatefile()
    {
        if (!$this->_methodExists('_isCached')) {
            return;
        }
        $this->_removeCachedFiles('loadtemplatefile.html');
        parent::testLoadTemplateFile();
        $this->assertCacheExists('loadtemplatefile.html');
        parent::testLoadTemplateFile();
    }

    function testAddBlockfile()
    {
        if (!$this->_methodExists('_isCached')) {
            return;
        }
        $this->_removeCachedFiles(array('blocks.html', 'addblock.html'));
        parent::testAddBlockfile();
        $this->assertCacheExists(array('blocks.html', 'addblock.html'));
        parent::testAddBlockfile();
    }

    function testReplaceBlockFile()
    {
        if (!$this->_methodExists('_isCached')) {
            return;
        }
        $this->_removeCachedFiles(array('blocks.html', 'replaceblock.html'));
        parent::testReplaceBlockfile();
        $this->assertCacheExists(array('blocks.html', 'replaceblock.html'));
        parent::testReplaceBlockfile();
    }

    function testInclude()
    {
        if (!$this->_methodExists('_isCached')) {
            return;
        }
        $this->_removeCachedFiles(array('include.html', '__include.html'));
        parent::testInclude();
        $this->assertCacheExists(array('include.html', '__include.html'));
        parent::testInclude();
    }

    function testCallback()
    {
        if (!$this->_methodExists('_isCached')) {
            return;
        }
        $this->_removeCachedFiles('callback.html');
        parent::testCallback();
        $this->assertCacheExists('callback.html');
        parent::testCallback();
    }
}
?>
