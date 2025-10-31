<?php
/**
 * Unit tests for HTML_Template_Sigma
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_Template_Sigma
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2025 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @link        http://pear.php.net/package/HTML_Template_Sigma
 * @ignore
 */

/**
 * Test case for cache functionality
 *
 * The class builds upon API tests, checking that methods that should produce
 * cache files actually do this.
 *
 * @category    HTML
 * @package     HTML_Template_Sigma
 * @author      Alexey Borzov <avb@php.net>
 * @version     @package_version@
 * @ignore
 */
class SigmaCacheTest extends SigmaApiTest
{
    private static $cacheDir;

    public static function set_up_before_class()
    {
        if (!class_exists(System::class)) {
            require_once 'System.php';
        }
        self::$cacheDir = System::mktemp('-d sigma');
    }

    protected function set_up()
    {
        $this->tpl = new HTML_Template_Sigma(__DIR__ . '/templates', self::$cacheDir);
        $this->tpl->setOption('exceptions', true);
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
                $this->fail("File '$file' is not cached");
            }
        }
    }

    function testLoadTemplatefile()
    {
        $this->_removeCachedFiles('loadtemplatefile.html');
        parent::testLoadTemplateFile();
        $this->assertCacheExists('loadtemplatefile.html');
        parent::testLoadTemplateFile();
    }

    function testAddBlockfile()
    {
        $this->_removeCachedFiles(array('blocks.html', 'addblock.html'));
        parent::testAddBlockfile();
        $this->assertCacheExists(array('blocks.html', 'addblock.html'));
        parent::testAddBlockfile();
    }

    function testReplaceBlockFile()
    {
        $this->_removeCachedFiles(array('blocks.html', 'replaceblock.html'));
        parent::testReplaceBlockfile();
        $this->assertCacheExists(array('blocks.html', 'replaceblock.html'));
        parent::testReplaceBlockfile();
    }

    function testInclude()
    {
        $this->_removeCachedFiles(array('include.html', '__include.html'));
        parent::testInclude();
        $this->assertCacheExists(array('include.html', '__include.html'));
        parent::testInclude();
    }

    function testCallback()
    {
        $this->_removeCachedFiles('callback.html');
        parent::testCallback();
        $this->assertCacheExists('callback.html');
        parent::testCallback();
    }

    function testBug6902()
    {
        if (!OS_WINDOWS) {
            $this->markTestSkipped('Test for a Windows-specific bug');
        }
        // realpath() on windows will return full path including drive letter
        $this->tpl->setRoot('');
        $this->tpl->setCacheRoot(self::$cacheDir);
        $this->tpl->loadTemplatefile(realpath(dirname(__FILE__) . '\\templates') . '\\' . 'loadtemplatefile.html');
        $this->assertEquals('A template', trim($this->tpl->get()));
        $this->tpl->loadTemplatefile(realpath(dirname(__FILE__) . '\\templates') . '\\' . 'loadtemplatefile.html');
        $this->assertEquals('A template', trim($this->tpl->get()));
    }
}
?>
