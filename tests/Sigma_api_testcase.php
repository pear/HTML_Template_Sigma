<?php

/**
 * Unit tests for HTML_Template_Sigma package.
 * 
 * @author Alexey Borzov <avb@php.net>
 * 
 * $Id$
 */

class Sigma_api_TestCase extends PHPUnit_TestCase
{
   /**
    * A template object
    * @var object
    */
    var $tpl;

    function Sigma_api_TestCase($name)
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

    function _stripWhitespace($str)
    {
        return preg_replace('/\\s+/', '', $str);
    }

    function _methodExists($name) 
    {
        if (in_array(strtolower($name), array_map('strtolower', get_class_methods($this->tpl)))) {
            return true;
        }
        $this->assertTrue(false, 'method '. $name . ' not implemented in ' . get_class($this->tpl));
        return false;
    }

   /**
    * Tests a setTemplate method 
    *
    */
    function testSetTemplate()
    {
        $result = $this->tpl->setTemplate('A template', false, false);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting template: '. $result->getMessage());
        }
        $this->assertEquals('A template', $this->tpl->get());
    }

   /**
    * Tests a loadTemplatefile method 
    *
    */
    function testLoadTemplatefile()
    {
        $result = $this->tpl->loadTemplatefile('loadtemplatefile.html', false, false);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->assertEquals('A template', trim($this->tpl->get()));
    }

   /**
    * Tests a setVariable method
    *
    */
    function testSetVariable()
    {
        $result = $this->tpl->setTemplate('{placeholder1} {placeholder2} {placeholder3}', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting template: '. $result->getMessage());
        }
        // "scalar" call
        $this->tpl->setVariable('placeholder1', 'var1');
        // array call
        $this->tpl->setVariable(array(
            'placeholder2' => 'var2',
            'placeholder3' => 'var3'
        ));
        $this->assertEquals('var1 var2 var3', $this->tpl->get());
    }

   /**
    * Tests the <!-- INCLUDE --> functionality 
    *
    */
    function testInclude()
    {
        $result = $this->tpl->loadTemplateFile('include.html', false, false);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->assertEquals('Master file; Included file', trim($this->tpl->get()));
    }

    function testCurrentBlock()
    {
        $result = $this->tpl->loadTemplateFile('blockiteration.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setVariable('outer', 'a');
        $this->tpl->setCurrentBlock('inner_block');
        for ($i = 0; $i < 5; $i++) {
            $this->tpl->setVariable('inner', $i + 1);
            $this->tpl->parseCurrentBlock();
        } // for
        $this->assertEquals('a|1|2|3|4|5#', $this->_stripWhitespace($this->tpl->get()));
    }

    function testRemovePlaceholders()
    {
        $result = $this->tpl->setTemplate('{placeholder1},{placeholder2},{placeholder3}', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting template: '. $result->getMessage());
        }
        // we do not set {placeholder3}
        $this->tpl->setVariable(array(
            'placeholder1' => 'var1',
            'placeholder2' => 'var2'
        ));
        $this->assertEquals('var1,var2,', $this->tpl->get());

        // Default behaviour is to remove {stuff} from data as well
        $result = $this->tpl->setTemplate('{placeholder1},{placeholder2},{placeholder3}', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting template: '. $result->getMessage());
        }
        $this->tpl->setVariable(array(
            'placeholder1' => 'var1',
            'placeholder2' => 'var2',
            'placeholder3' => 'var3{stuff}'
        ));
        $this->assertEquals('var1,var2,var3', $this->tpl->get());
    }

    function testTouchBlock()
    {
        $result = $this->tpl->loadTemplateFile('blockiteration.html', false, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setVariable('outer', 'data');
        // inner_block should be preserved in output, even if empty
        $this->tpl->touchBlock('inner_block');
        $this->assertEquals('data|{inner}#', $this->_stripWhitespace($this->tpl->get()));
    }
   
    function testHideBlock()
    {
        if (!$this->_methodExists('hideBlock')) {
            return;
        }
        $result = $this->tpl->loadTemplateFile('blockiteration.html', false, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setVariable(array(
            'outer' => 'data',
            'inner' => 'stuff'
        ));
        // inner_block is not empty, but should be removed nonetheless
        $this->tpl->hideBlock('inner_block');
        $this->assertEquals('data#', $this->_stripWhitespace($this->tpl->get()));
    }

    function testSetGlobalVariable()
    {
        if (!$this->_methodExists('setGlobalVariable')) {
            return;
        }
        $result = $this->tpl->loadTemplateFile('globals.html', false, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setGlobalVariable('glob', 'glob');
        // {var2} is not, block_two should be removed
        $this->tpl->setVariable(array(
            'var1' => 'one',
            'var3' => 'three'
        ));
        for ($i = 0; $i < 3; $i++) {
            $this->tpl->setVariable('var4', $i + 1);
            $this->tpl->parse('block_four');
        } // for
        $this->assertEquals('glob:one#glob:three|glob:1|glob:2|glob:3#', $this->_stripWhitespace($this->tpl->get()));
    }

    function testOptionPreserveData()
    {
        if (!$this->_methodExists('setOption')) {
            return;
        }
        $this->tpl->setTemplate('{placeholder1},{placeholder2},{placeholder3}', true, true);
        $this->tpl->setOption('preserve_data', true);
        $this->tpl->setVariable(array(
            'placeholder1' => 'var1',
            'placeholder2' => 'var2',
            'placeholder3' => 'var3{stuff}'
        ));
        $this->assertEquals('var1,var2,var3{stuff}', $this->tpl->get());
    }

    function testPlaceholderExists()
    {
        $this->tpl->setTemplate('{var}');
        $this->assertTrue($this->tpl->placeholderExists('var'), 'Existing placeholder \'var\' reported as nonexistant');
        $this->assertTrue(!$this->tpl->placeholderExists('foobar'), 'Nonexistant placeholder \'foobar\' reported as existing');
        $this->assertTrue($this->tpl->placeholderExists('var', '__global__'), 'Existing in block \'__global__\' placeholder \'var\' reported as nonexistant');
        $this->assertTrue(!$this->tpl->placeholderExists('foobar', '__global__'), 'Nonexistant in block \'__global__\' placeholder \'foobar\' reported as existing');
    }

    function testBlockExists()
    {
        $this->tpl->setTemplate('{var}');
        $this->assertTrue($this->tpl->blockExists('__global__'), 'Existing block \'__global__\' reported as nonexistant');
        $this->assertTrue(!$this->tpl->blockExists('foobar'), 'Nonexistant block \'foobar\' reported as existing');
    }

    function testAddBlock()
    {
        $result = $this->tpl->loadTemplatefile('blocks.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->addBlock('var', 'added', 'added:{new_var}');
        $this->assertTrue($this->tpl->blockExists('added'), 'The new block seems to be missing');
        $this->assertTrue(!$this->tpl->placeholderExists('var'), 'The old variable seems to be still present in the template');
        $this->tpl->setVariable('new_var', 'new_value');
        $this->assertEquals('added:new_value', $this->_stripWhitespace($this->tpl->get()));
    }

    function testAddBlockfile()
    {
        $result = $this->tpl->loadTemplatefile('blocks.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $result = $this->tpl->addBlockfile('var', 'added', 'addblock.html');
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error adding block from file: '. $result->getMessage());
        }
        $this->assertTrue($this->tpl->blockExists('added'), 'The new block seems to be missing');
        $this->assertTrue(!$this->tpl->placeholderExists('var'), 'The old variable seems to be still present in the template');
        $this->tpl->setVariable('new_var', 'new_value');
        $this->assertEquals('added:new_value', $this->_stripWhitespace($this->tpl->get()));
    }

    function testReplaceBlock()
    {
        $result = $this->tpl->loadTemplatefile('blocks.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setVariable('old_var', 'old_value');
        $this->tpl->parse('old_block');
        // old_block's contents should be discarded
        $this->tpl->replaceBlock('old_block', 'replaced:{replaced_var}#', false);
        $this->assertTrue(!$this->tpl->blockExists('old_inner_block') && !$this->tpl->placeholderExists('old_var'),
                          'The replaced block\'s contents seem to be still present');
        $this->tpl->setVariable('replaced_var', 'replaced_value');
        $this->tpl->parse('old_block');
        // this time old_block's contents should be preserved
        $this->tpl->replaceBlock('old_block', 'replaced_again:{brand_new_var}', true);
        $this->tpl->setVariable('brand_new_var', 'brand_new_value');
        $this->assertEquals('replaced:replaced_value#replaced_again:brand_new_value', $this->_stripWhitespace($this->tpl->get()));
    }

    function testReplaceBlockfile()
    {
        $result = $this->tpl->loadTemplatefile('blocks.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setVariable('old_var', 'old_value');
        $this->tpl->parse('old_block');
        // old_block's contents should be discarded
        $result = $this->tpl->replaceBlockfile('old_block', 'replaceblock.html', false);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error replacing block from file: '. $result->getMessage());
        }
        $this->assertTrue(!$this->tpl->blockExists('old_inner_block') && !$this->tpl->placeholderExists('old_var'),
                          'The replaced block\'s contents seem to be still present');
        $this->tpl->setVariable(array(
            'replaced_var'       => 'replaced_value',
            'replaced_inner_var' => 'inner_value'
        ));
        $this->tpl->parse('old_block');
        // this time old_block's contents should be preserved
        $result = $this->tpl->replaceBlockfile('old_block', 'addblock.html', true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error replacing block from file: '. $result->getMessage());
        }
        $this->tpl->setVariable('new_var', 'again');
        $this->assertEquals('replaced:replaced_value|inner_value#added:again', $this->_stripWhitespace($this->tpl->get()));
    }

    function testCallback()
    {
        $result = $this->tpl->loadTemplatefile('callback.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->tpl->setVariable('username', 'luser');
        $this->tpl->setCallbackFunction('uppercase', 'strtoupper');
        $this->tpl->setCallbackFunction('russian', array(&$this, '_doRussian'), true);
        $this->tpl->setCallbackFunction('lowercase', 'strtolower');
        $this->tpl->setCallBackFunction('noarg', array(&$this, '_doCallback'));
        $this->assertEquals('callback#word#HELLO,LUSER!#Привет,luser!', $this->_stripWhitespace($this->tpl->get()));
    }

    function _doCallback()
    {
        return 'callback';
    }

    function _doRussian($arg)
    {
        $ary = array('Hello, {username}!' => 'Привет, {username}!');
        return isset($ary[$arg])? $ary[$arg]: $arg;
    }

    function testGetBlockList()
    {
        // expected tree...
        $tree = array(
            'name'     => '__global__',
            'children' => array(
                array(
                    'name'     => 'outer_block',
                    'children' => array(
                        array('name' => 'inner_block')
                    )
                )
            )
        );

        $result = $this->tpl->loadTemplatefile('blockiteration.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->assertEquals($tree, $this->tpl->getBlockList('__global__', true));
        $this->assertEquals(array('inner_block'), $this->tpl->getBlockList('outer_block'));
    }
    
    function testGetPlaceholderList()
    {
        $result = $this->tpl->loadTemplatefile('blockiteration.html', true, true);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error loading template file: '. $result->getMessage());
        }
        $this->assertEquals(array('outer'), $this->tpl->getPlaceholderList('outer_block'));
    }
}

?>
