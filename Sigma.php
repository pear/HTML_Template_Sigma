<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Ulf Wendel <ulf.wendel@phpdoc.de>                           |
// |          Alexey Borzov <avb@php.net>                                 |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'PEAR.php';

define('SIGMA_OK',                         1);
define('SIGMA_ERROR',                     -1);
define('SIGMA_TPL_NOT_FOUND',             -2);
define('SIGMA_BLOCK_NOT_FOUND',           -3);
define('SIGMA_BLOCK_DUPLICATE',           -4);
define('SIGMA_CACHE_ERROR',               -5);
define('SIGMA_UNKNOWN_OPTION',            -6);
define('SIGMA_PLACEHOLDER_NOT_FOUND',     -10);
define('SIGMA_PLACEHOLDER_DUPLICATE',     -11);
define('SIGMA_BLOCK_EXISTS',              -12);
define('SIGMA_INVALID_CALLBACK',          -13);

/**
* HTML_Template_Sigma: implementation of Integrated Templates API with 
* template 'compilation' added.
*
* The main new feature in Sigma is the template 'compilation'. Consider the
* following: when loading a template file the engine has to parse it using
* regular expressions to find all the blocks and variable placeholders. This
* is a very "expensive" operation and is definitely an overkill to do on 
* every page request: templates seldom change on production websites. This is
* where the cache kicks in: it saves an internal representation of the 
* template structure into a file and this file gets loaded instead of the 
* source one on subsequent requests (unless the source changes, of course).
* 
* While HTML_Template_Sigma inherits PHPLib Template's template syntax, it has
* an API which is easier to understand. When using HTML_Template_PHPLIB, you
* have to explicitly name a source and a target the block gets parsed into.
* This gives maximum flexibility but requires full knowledge of template 
* structure from the programmer.
* 
* Integrated Template on the other hands manages block nesting and parsing 
* itself. The engine knows that inner1 is a child of block2, there's
* no need to tell it about this:
*
* + __global__ (hidden and automatically added)
*     + block1
*     + block2
*         + inner1
*         + inner2
*
* To add content to block1 you simply type:
* <code>$tpl->setCurrentBlock("block1");</code>
* and repeat this as often as needed:
* <code>
*   $tpl->setVariable(...);
*   $tpl->parseCurrentBlock();
* </code>
*
* To add content to block2 you would type something like:
* <code>
* $tpl->setCurrentBlock("inner1");
* $tpl->setVariable(...);
* $tpl->parseCurrentBlock();
*
* $tpl->setVariable(...);
* $tpl->parseCurrentBlock();
*
* $tpl->parse("block2");
* </code>
*
* This will result in one repetition of block2 which contains two repetitions
* of inner1. inner2 will be removed if $removeEmptyBlock is set to true (which 
* is the default).
*
* Usage:
* <code>
* $tpl = new HTML_Template_Sigma( [string filerootdir], [string cacherootdir] );
*
* // load a template or set it with setTemplate()
* $tpl->loadTemplatefile( string filename [, boolean removeUnknownVariables, boolean removeEmptyBlocks] )
*
* // set "global" Variables meaning variables not beeing within a (inner) block
* $tpl->setVariable( string variablename, mixed value );
*
* // like with the HTML_Template_PHPLIB there's a second way to use setVariable()
* $tpl->setVariable( array ( string varname => mixed value ) );
*
* // Let's use any block, even a deeply nested one
* $tpl->setCurrentBlock( string blockname );
*
* // repeat this as often as you need it.
* $tpl->setVariable( array ( string varname => mixed value ) );
* $tpl->parseCurrentBlock();
*
* // get the parsed template or print it: $tpl->show()
* $html = $tpl->get();
* </code>
*
* @author   Ulf Wendel <uw@netuse.de>
* @author   Alexey Borzov <avb@php.net>
* @version  $Id$
* @access   public
* @package  HTML_Template_Sigma
*/
class HTML_Template_Sigma extends PEAR
{
   /**
    * First character of a variable placeholder ( _{_VARIABLE} ).
    * @var      string
    * @access   public
    * @see      $closingDelimiter, $blocknameRegExp, $variablenameRegExp
    */
    var $openingDelimiter = '{';

   /**
    * Last character of a variable placeholder ( {VARIABLE_}_ )
    * @var      string
    * @access   public
    * @see      $openingDelimiter, $blocknameRegExp, $variablenameRegExp
    */
    var $closingDelimiter     = '}';

   /**
    * RegExp for matching the block names in the template
    * Per default "sm" is used as the regexp modifier, "i" is missing.
    * That means a case sensitive search is done.
    * @var      string
    * @access   public
    * @see      $variablenameRegExp, $openingDelimiter, $closingDelimiter
    */
    var $blocknameRegExp    = '[0-9A-Za-z_-]+';

   /**
    * RegExp matching a variable placeholder in the template
    * Per default "sm" is used as the regexp modifier, "i" is missing.
    * That means a case sensitive search is done.
    * @var      string    
    * @access   public
    * @see      $blocknameRegExp, $openingDelimiter, $closingDelimiter
    */
    var $variablenameRegExp    = '[0-9A-Za-z_-]+';

   /**
    * RegExp used to find variable placeholder, filled by the constructor
    * @var      string    Looks somewhat like @(delimiter varname delimiter)@
    * @see      HTML_Template_Sigma()
    */
    var $variablesRegExp = '';

   /**
    * RegExp used to strip unused variable placeholders
    * @brother  $variablesRegExp
    * @see      HTML_Template_Sigma()
    */
    var $removeVariablesRegExp = '';

   /**
    * RegExp used to find blocks and their content, filled by the constructor
    * @var      string
    * @see      HTML_Template_Sigma()
    */
    var $blockRegExp = '';

   /**
    * Controls the handling of unknown variables, default is remove
    * @var      boolean
    * @access   public
    */
    var $removeUnknownVariables = true;

   /**
    * Controls the handling of empty blocks, default is remove
    * @var      boolean
    * @access   public
    */
    var $removeEmptyBlocks = true;

   /**
    * Name of the current block
    * @var      string
    */
    var $currentBlock = '__global__';

   /**
    * Template blocks and their content
    * @var      array
    * @see      _buildBlocks()
    */
    var $_blocks = array();

   /**
    * Content of parsed blocks
    * @var      array
    */
    var $_parsedBlocks = array();

   /**
    * Variable names that appear in the block
    * @var      array
    */
    var $_blockVariables = array();

   /**
    * Inner blocks inside the block
    * @var      array
    */
    var $_children = array();

   /**
    * List of blocks to preserve even if they are "empty"
    * @var  array    $_touchedBlocks
    * @see  touchBlock(), $removeEmptyBlocks
    */
    var $_touchedBlocks = array();

   /**
    * List of blocks which should not be shown even if not "empty"
    * @var  array    $_hiddenBlocks
    * @see  hideBlock(), $removeEmptyBlocks
    */
    var $_hiddenBlocks = array();

   /**
    * Variables for substitution
    *
    * Variables are kept in this array before the replacements are done.
    * This allows automatic removal of empty blocks.
    * 
    * @var    array
    * @see    setVariable()
    */
    var $_variables = array();

   /**
    * Global variables for substitution
    * 
    * These are substituted into all blocks, are not cleared on
    * block parsing and do not trigger "non-empty" logic. I.e. if 
    * only global variables are substituted into the block, it is
    * still considered "empty".
    *
    * @var array
    * @see setVariable(), setGlobalVariable()
    */
    var $_globalVariables = array();

   /**
    * Root directory for "source" templates
    * 
    * @var    string
    * @see    HTML_Template_Sigma(), setRoot()
    */
    var $fileRoot = '';

   /**
    * Directory for "prepared" templates cache
    * 
    * @var    string
    * @see    HTML_Template_Sigma(), setCacheRoot()
    */
    var $_cacheRoot = null;

   /**
    * Flag indicating that the global block was parsed
    * @var    boolean
    */
    var $flagGlobalParsed = false;

   /**
    * $_options['preserve_data'] If false, then substitute variables and remove empty 
    * placeholders also in data passed through setVariable (see also bugs #20199, #21951)
    * $_options['trim_on_save'] Whether to trim extra whitespace from template on cache save.
    * Generally safe to have this on, unless you have <pre></pre> in templates or want to 
    * preserve HTML indentantion
    */
    var $_options = array(
        'preserve_data' => false,
        'trim_on_save'  => true
    );

   /**
    * Function name prefix used when searching for function calls in the template
    * @var    string
    */
    var $functionPrefix = 'func_';

   /**
    * Function name RegExp
    * @var    string
    */
    var $functionnameRegExp = '[_a-zA-Z]+[A-Za-z_0-9]*';

   /**
    * RegExp used to grep function calls in the template (set by the constructor)
    *
    * @var    string
    * @see    HTML_Template_Sigma()
    */
    var $functionRegExp = '';

   /**
    * List of functions found in the template.
    * @var    array
    */
    var $_functions = array();

   /**
    * List of callback functions specified by the user
    * @var    array
    */
    var $_callback = array();

   /**
    * RegExp used to find file inclusion calls in the template (should have 'e' modifier)
    * @var  string
    */
    var $includeRegExp = '#<!--\s+INCLUDE\s+(\S+)\s+-->#ime';

   /**
    * Files queued for inclusion
    * @var array
    */
    var $_triggers = array();

   /**
    * Constructor: builds some complex regular expressions and optionally 
    * sets the root directories.
    *
    * Make sure that you call this constructor if you derive your template
    * class from this one.
    *
    * @param $root       string  root directory for templates
    * @param $cacheRoot  string  directory to cache "prepared" templates in
    * @see setRoot()
    */
    function HTML_Template_Sigma($root = '', $cacheRoot = '')
    {
        // the class is inherited from PEAR to be able to use $this->setErrorHandling()
        $this->PEAR();
        $this->variablesRegExp       = '@'.$this->openingDelimiter.'('.$this->variablenameRegExp.')'.$this->closingDelimiter.'@sm';
        $this->removeVariablesRegExp = '@'.$this->openingDelimiter.'\s*('.$this->variablenameRegExp.')\s*'.$this->closingDelimiter.'@sm';
        $this->blockRegExp           = '@<!--\s+BEGIN\s+('.$this->blocknameRegExp.')\s+-->(.*)<!--\s+END\s+\1\s+-->@sm';
        $this->functionRegExp        = '@' . $this->functionPrefix . '(' . $this->functionnameRegExp . ')\s*\(@sm';
        $this->setRoot($root);
        $this->setCacheRoot($cacheRoot);
    }


   /**
    * Sets the file root for templates. The file root gets prefixed to all 
    * filenames passed to the object.
    * 
    * @param    string  directory name
    * @see      HTML_Template_Sigma()
    * @access   public
    */
    function setRoot($root)
    {
        if (('' != $root) && ('/' != substr($root, -1))) {
            $root .= '/';
        }
        $this->fileRoot = $root;
    }


   /**
    * Sets the directory to cache "prepared" templates in.
    * 
    * The directory should be writable to PHP, of course. The "prepared"
    * template is essentially a serialized array of $_blocks, $_blockVariables 
    * and $_children. This allows to bypass expensive calls to 
    * _buildBlockVariables() and especially _buildBlocks() when reading the
    * "prepared" template instead of the "source" one.
    * 
    * @param    string  directory name
    * @see      HTML_Template_Sigma(), _getCached(), _writeCache()
    * @access   public
    */
    function setCacheRoot($root)
    {
        if (empty($root)) {
            return true;
        } elseif (('' != $root) && ('/' != substr($root, -1))) {
            $root .= '/';
        }
        $this->_cacheRoot = $root;
    }


   /**
    * Sets the option for the template class
    * 
    * @access public
    * @param  string  option name
    * @param  mixed   option value
    * @return mixed   SIGMA_OK on success, error object on failure
    */
    function setOption($option, $value)
    {
        if (isset($this->_options[$option])) {
            $this->_options[$option] = $value;
            return SIGMA_OK;
        }
        return $this->raiseError($this->errorMessage(SIGMA_UNKNOWN_OPTION, $option), SIGMA_UNKNOWN_OPTION);
    }


   /**
    * Returns a textual error message for an error code
    *  
    * @access public
    * @param  integer  error code
    * @param  string   additional data to insert into message
    * @return string   error message
    */
    function errorMessage($code, $data = null)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                SIGMA_ERROR                 => 'unknown error',
                SIGMA_OK                    => '',
                SIGMA_TPL_NOT_FOUND         => 'Cannot read the template file \'%s\'',
                SIGMA_BLOCK_NOT_FOUND       => 'Cannot find block \'%s\'',
                SIGMA_BLOCK_DUPLICATE       => 'The name of a block must be unique within a template. Block \'%s\' found twice.',
                SIGMA_CACHE_ERROR           => 'Cannot save template file \'%s\'',
                SIGMA_UNKNOWN_OPTION        => 'Unknown option \'%s\'',
                SIGMA_PLACEHOLDER_NOT_FOUND => 'Variable placeholder \'%s\' not found',
                SIGMA_PLACEHOLDER_DUPLICATE => 'Placeholder \'%s\' should be unique, found in multiple blocks',
                SIGMA_BLOCK_EXISTS          => 'Block \'%s\' already exists',
                SIGMA_INVALID_CALLBACK      => 'Callback does not exist'
            );
        }

        if (PEAR::isError($code)) {
            $code = $code->getCode();
        }
        if (!isset($errorMessages[$code])) {
            return $errorMessages[SIGMA_ERROR];
        } else {
            return (null === $data)? $errorMessages[$code]: sprintf($errorMessages[$code], $data);
        }
    }


   /**
    * Prints a block with all replacements done.
    * 
    * @access  public
    * @param   string  block name
    * @brother get()
    */
    function show($block = '__global__')
    {
        print $this->get($block);
    }


   /**
    * Returns a block with all replacements done.
    * 
    * @param    string     block name
    * @param    bool       whether to clear parsed block contents
    * @return   string     block with all replacements done
    * @throws   PEAR_Error
    * @access   public
    * @see      show()
    */
    function get($block = '__global__', $clear = true)
    {
        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if ('__global__' == $block && !$this->flagGlobalParsed) {
            $this->parse('__global__');
        }
        // return the parsed block, removing the unknown placeholders if needed
        if (!isset($this->_parsedBlocks[$block])) {
            return '';

        } else {
            $ret = $this->_parsedBlocks[$block];
            if ($clear) {
                unset($this->_parsedBlocks[$block]);
            }
            if ($this->removeUnknownVariables) {
                $ret = preg_replace($this->removeVariablesRegExp, '', $ret);
            }
            if ($this->_options['preserve_data']) {
                $ret = str_replace($this->openingDelimiter . '%preserved%' . $this->closingDelimiter, $this->openingDelimiter, $ret);
            }
            return $ret;
        }
    }


   /**
    * Parses the given block.
    *    
    * @param    string    block name
    * @param    boolean   true if the function is called recursively
    * @param    boolean   true if we are inside a "hidden" block
    * @access   public
    * @see      parseCurrentBlock()
    * @throws   PEAR_Error
    */
    function parse($block = '__global__', $flagRecursion = false, $fakeParse = false)
    {
        static $vars;

        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if ('__global__' == $block) {
            $this->flagGlobalParsed = true;
        }
        if (!isset($this->_parsedBlocks[$block])) {
            $this->_parsedBlocks[$block] = '';
        }
        $outer = $this->_blocks[$block];

        if (!$flagRecursion) {
            $vars = array();
        }
        // block is not empty if its local var is substituted
        $empty = true;
        foreach ($this->_blockVariables[$block] as $allowedvar => $v) {
            if (isset($this->_variables[$allowedvar])) {
                $vars[$this->openingDelimiter . $allowedvar . $this->closingDelimiter] = $this->_variables[$allowedvar];
                $empty = false;
                // vital for checking "empty/nonempty" status
                unset($this->_variables[$allowedvar]);
            }
        }

        // processing of the inner blocks
        if (isset($this->_children[$block])) {
            foreach ($this->_children[$block] as $innerblock => $v) {
                $placeholder = $this->openingDelimiter.'__'.$innerblock.'__'.$this->closingDelimiter;

                if (isset($this->_hiddenBlocks[$innerblock])) {
                    // don't bother actually parsing this inner block; but we _have_
                    // to go through its local vars to prevent problems on next iteration
                    $this->parse($innerblock, true, true);
                    unset($this->_hiddenBlocks[$innerblock]);
                    $outer = str_replace($placeholder, '', $outer);

                } else {
                    $this->parse($innerblock, true, $fakeParse);
                    // block is not empty if its inner block is not empty
                    if ('' != $this->_parsedBlocks[$innerblock]) {
                        $empty = false;
                    }

                    $outer = str_replace($placeholder, $this->_parsedBlocks[$innerblock], $outer);
                    $this->_parsedBlocks[$innerblock] = '';
                }
            }
        }

        // add "global" variables to the static array
        foreach ($this->_globalVariables as $allowedvar => $value) {
            if (isset($this->_blockVariables[$block][$allowedvar])) {
                $vars[$this->openingDelimiter . $allowedvar . $this->closingDelimiter] = $value;
            }
        }
        // if we are inside a hidden block, don't bother
        if (!$fakeParse) {
            if (0 != count($vars) && (!$flagRecursion || !empty($this->_functions[$block]))) {
                $varKeys     = array_keys($vars);
                $varValues   = $this->_options['preserve_data']? array_map(array(&$this, '_preserveOpeningDelimiter'), array_values($vars)): array_values($vars);
            }
            // perform callbacks
            if (!empty($this->_functions[$block])) {
                foreach ($this->_functions[$block] as $id => $data) {
                    $placeholder = $this->openingDelimiter . '__function_' . $id . '__' . $this->closingDelimiter;
                    // do not waste time calling function more than once
                    if (!isset($vars[$placeholder])) {
                        $args = array();
                        foreach ($data['args'] as $arg) {
                            $args[] = empty($varKeys)? $arg: str_replace($varKeys, $varValues, $arg);
                        }
                        if (isset($this->_callback[$data['name']])) {
                            $res = call_user_func_array($this->_callback[$data['name']], $args);
                        } else {
                            $res = isset($args[0])? $args[0]: '';
                        }
                        $outer = str_replace($placeholder, $res, $outer);
                        // save the result to variable cache, it can be requested somewhere else
                        $vars[$placeholder] = $res;
                    }
                }
            }
            // substitute variables only on non-recursive call, thus all
            // variables from all inner blocks get substituted
            if (!$flagRecursion && !empty($varKeys)) {
                $outer = str_replace($varKeys, $varValues, $outer);
            }

            // check whether the block is considered "empty" and append parsed content if not
            if (!$empty || ('__global__' == $block) || !$this->removeEmptyBlocks || isset($this->_touchedBlocks[$block])) {
                $this->_parsedBlocks[$block] .= $outer;
                if (isset($this->_touchedBlocks[$block])) {
                    unset($this->_touchedBlocks[$block]);
                }
            }
        }
        return $empty;
    }


   /**
    * Sets a variable value.
    * 
    * The function can be used either like setVariable("varname", "value")
    * or with one array $variables["varname"] = "value" given setVariable($variables)
    * 
    * @access public
    * @param mixed $variable  variable name or array ('varname'=>'value')
    * @param string $value    variable value if $variable is not an array
    */
    function setVariable($variable, $value = '')
    {
        if (is_array($variable)) {
            $this->_variables = array_merge($this->_variables, $variable);
        } else {
            $this->_variables[$variable] = $value;
        }
    }


   /**
    * Sets a global variable value.
    * 
    * @access public
    * @param mixed $variable  variable name or array ('varname'=>'value')
    * @param string $value    variable value if $variable is not an array
    * @see setVariable()
    */
    function setGlobalVariable($variable, $value = '')
    {
        if (is_array($variable)) {
            $this->_globalVariables = array_merge($this->_globalVariables, $variable);
        } else {
            $this->_globalVariables[$variable] = $value;
        }
    }


   /**
    * Sets the name of the current block: the block where variables are added
    *
    * @param    string      block name
    * @return   mixed       SIGMA_OK on success, error object on failure
    * @throws   PEAR_Error
    * @access   public
    */
    function setCurrentBlock($block = '__global__')
    {
        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        $this->currentBlock = $block;
        return SIGMA_OK;
    }


   /**
    * Parses the current block
    * 
    * @see      parse(), setCurrentBlock(), $currentBlock
    * @access   public
    */
    function parseCurrentBlock()
    {
        return $this->parse($this->currentBlock);
    }


   /**
    * Returns the current block name
    *
    * @return string    block name
    * @access public
    */
    function getCurrentBlock()
    {
        return $this->currentBlock;
    }


   /**
    * Preserves the block even if empty blocks should be removed
    *
    * This is something special. Sometimes you have blocks that
    * should be preserved although they are empty (no placeholder replaced).
    * Think of a shopping basket. If it's empty you have to drop a message to
    * the user. If it's filled you have to show the contents of the shopping baseket.
    * Now where do you place the message that the basket is empty? It's no good
    * idea to place it in you applications as customers tend to like unecessary minor
    * text changes. Having another template file for an empty basket means that it's
    * very likely that one fine day the filled and empty basket templates have different
    * layout. I decided to introduce blocks that to not contain any placeholder but only
    * text such as the message "Your shopping basked is empty".
    *
    * Now if there is no replacement done in such a block the block will be recognized
    * as "empty" and by default ($removeEmptyBlocks = true) be stripped off. To avoid this
    * you can call touchBlock()
    *
    * @param    string      block name
    * @return   mixed       SIGMA_OK on success, error object on failure
    * @throws   PEAR_Error    
    * @access   public
    * @see      $removeEmptyBlocks, $_touchedBlocks
    */
    function touchBlock($block)
    {
        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if (isset($this->_hiddenBlocks[$block])) {
            unset($this->_hiddenBlocks[$block]);
        }
        $this->_touchedBlocks[$block] = true;
        return SIGMA_OK;
    }


   /**
    * Hides the block even if it is not "empty"
    * 
    * Is somewhat an opposite to touchBlock().
    * 
    * Consider a block that should be visible to registered/"special" users only, but 
    * its visibility is triggered by some little field passed in a large array 
    * into setVariable(). You can either carefully juggle your variables to prevent the
    * block from appearing (a fragile solution) or simply call hideBlock()
    *
    * @param    string      block name
    * @return   mixed       SIGMA_OK on success, error object on failure
    * @throws   PEAR_Error    
    * @access   public
    */
    function hideBlock($block)
    {
        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if (isset($this->_touchedBlocks[$block])) {
            unset($this->_touchedBlocks[$block]);
        }
        $this->_hiddenBlocks[$block] = true;
        return SIGMA_OK;
    }


   /**
    * Sets the template.
    *
    * You can either load a template file from disk with LoadTemplatefile() or set the
    * template manually using this function.
    * 
    * @param        string      template content
    * @param        boolean     remove unknown/unused variables?
    * @param        boolean     remove empty blocks?
    * @see          loadTemplatefile()
    * @access       public
    * @return       mixed       SIGMA_OK on success, error object on failure
    */
    function setTemplate($template, $removeUnknownVariables = true, $removeEmptyBlocks = true)
    {
        $this->_resetTemplate($removeUnknownVariables, $removeEmptyBlocks);
        $list = $this->_buildBlocks('<!-- BEGIN __global__ -->'.$template.'<!-- END __global__ -->');
        if (PEAR::isError($list)) {
            return $list;
        }
        $this->_buildBlockVariables();
        return SIGMA_OK;
    }


   /**
    * Loads a template file
    * 
    * If caching is on, then it checks whether a "prepared" template exists
    * If it does, it gets loaded instead of the original, if it does not, then
    * the original gets loaded and prepared and then the prepared version is saved.
    * addBlockfile() and replaceBlockfile() implement quite the same logic.
    *
    * @param    string      filename
    * @param    boolean     remove unknown/unused variables?
    * @param    boolean     remove empty blocks?
    * @access   public
    * @return   mixed       SIGMA_OK on success, error object on failure
    * @see      setTemplate(), $removeUnknownVariables, $removeEmptyBlocks
    */
    function loadTemplateFile($filename, $removeUnknownVariables = true, $removeEmptyBlocks = true)
    {
        if ($this->_isCached($filename)) {
            $this->_resetTemplate($removeUnknownVariables, $removeEmptyBlocks);
            return $this->_getCached($filename);
        }
        $template = $this->_getFile($this->_sourceName($filename));
        if (PEAR::isError($template)) {
            return $template;
        }
        $this->_triggers = array();
        $template = preg_replace($this->includeRegExp, "\$this->_makeTrigger('\\1', '__global__')", $template);
        if (PEAR::isError($res = $this->setTemplate($template, $removeUnknownVariables, $removeEmptyBlocks))) {
            return $res;
        } else {
            return $this->_writeCache($filename, '__global__');
        }
    }


   /**
    * Adds a block to the template changing a variable placeholder to a block placeholder.
    *
    * Add means "replace a variable placeholder by a new block".
    * This is different to PHPLib templates. The function loads a
    * block, creates a handle for it and assigns it to a certain
    * variable placeholder. To to the same with PHPLib templates you would
    * call set_file() to create the handle and parse() to assign the
    * parsed block to a variable. By this PHPLibstemplates assume that you tend
    * to assign a block to more than one one placeholder. To assign a parsed block
    * to more than only the placeholder you specify in this function you have
    * to use a combination of get() and setVariable().
    *
    * The block content must not start with <!-- BEGIN blockname --> and end with
    * <!-- END blockname --> this would cause overhead and produce an error.
    * 
    * @param    string    name of the variable placeholder, the name must be unique within the template.
    * @param    string    name of the block to be added
    * @param    string    content of the block
    * @return   mixed     SIGMA_OK on success, error object on failure
    * @throws   PEAR_Error
    * @see      addBlockfile()
    * @access   public
    */
    function addBlock($placeholder, $block, $template)
    {
        if (isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_EXISTS, $block), SIGMA_BLOCK_EXISTS);
        }
        $parents = $this->_findParentBlocks($placeholder);
        if (0 == count($parents)) {
            return $this->raiseError($this->errorMessage(SIGMA_PLACEHOLDER_NOT_FOUND, $placeholder), SIGMA_PLACEHOLDER_NOT_FOUND);
        } elseif (count($parents) > 1) {
            return $this->raiseError($this->errorMessage(SIGMA_PLACEHOLDER_DUPLICATE, $placeholder), SIGMA_PLACEHOLDER_DUPLICATE);
        }
        
        $template = "<!-- BEGIN $block -->" . $template . "<!-- END $block -->";
        $list     = $this->_buildBlocks($template);
        if (PEAR::isError($list)) {
            return $list;
        }
        $this->_replacePlaceholder($parents[0], $placeholder, $block);
        $this->_buildBlockVariables($block);
        return SIGMA_OK;
    }
    

   /**
    * Adds a block taken from a file to the template, changing a variable placeholder 
    * to a block placeholder.
    * 
    * @param      string    name of the variable placeholder
    * @param      string    name of the block to be added
    * @param      string    template file that contains the block
    * @return     mixed     SIGMA_OK on success, error object on failure
    * @throws     PEAR_Error
    * @brother    addBlock()
    * @access     public
    */
    function addBlockfile($placeholder, $block, $filename)
    {
        if ($this->_isCached($filename)) {
            return $this->_getCached($filename, $block, $placeholder);
        }
        $template = $this->_getFile($this->_sourceName($filename));
        if (PEAR::isError($template)) {
            return $template;
        }
        $template = preg_replace($this->includeRegExp, "\$this->_makeTrigger('\\1', '{$block}')", $template);
        if (PEAR::isError($res = $this->addBlock($placeholder, $block, $template))) {
            return $res;
        } else {
            return $this->_writeCache($filename, $block);
        }
    }


   /**
    * Replaces an existing block with new content.
    * 
    * This function will replace a block of the template and all blocks contained in
    * the replaced block and add a new block insted, means you can dynamically change your
    * template.
    * 
    * In contrast to other systems Sigma analyses the way you've nested blocks and knows
    * which block belongs into another block. The nesting information helps to make the API
    * short and simple. Replacing blocks does not only mean that Sigma has to update
    * the nesting information (relatively time consumpting task) but you have to
    * make sure that you do not get confused due to the template change itself.
    * 
    * @param   string    name of a block to replace
    * @param   string    new content
    * @param   boolean   true if the parsed contents of the block should be kept
    * @access  public
    * @see     replaceBlockfile(), addBlock()
    * @return  mixed     SIGMA_OK on success, error object on failure
    * @throws  PEAR_Error
    */
    function replaceBlock($block, $template, $keepContent = false)
    {
        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        // should not throw a error as we already checked for block existance
        $this->_removeBlockData($block, $keepContent);
        $template = "<!-- BEGIN $block -->" . $template . "<!-- END $block -->";

        $list = $this->_buildBlocks($template);
        if (PEAR::isError($list)) {
            return $list;
        }
        // renew the variables list
        $this->_buildBlockVariables($block);
        return SIGMA_OK;
    }


   /**
    * Replaces an existing block with new content from a file.
    * 
    * @param      string    name of a block to replace
    * @param      string    template file that contains the block
    * @param      boolean   true if the parsed contents of the block should be kept
    * @access     public
    * @brother    replaceBlock()
    * @return     mixed     SIGMA_OK on success, error object on failure
    * @throws     PEAR_Error
    */
    function replaceBlockfile($block, $filename, $keepContent = false)
    {
        if ($this->_isCached($filename)) {
            if (PEAR::isError($res = $this->_removeBlockData($block, $keepContent))) {
                return $res;
            } else {
                return $this->_getCached($filename, $block);
            }
        }
        $template = $this->_getFile($this->_sourceName($filename));
        if (PEAR::isError($template)) {
            return $template;
        }
        $template = preg_replace($this->includeRegExp, "\$this->_makeTrigger('\\1', '{$block}')", $template);
        if (PEAR::isError($res = $this->replaceBlock($block, $template, $keepContent))) {
            return $res;
        } else {
            return $this->_writeCache($filename, $block);
        }
    }


   /**
    * Checks if the block exists in the template
    *
    * @param  string  block name
    * @return bool    yes/no
    * @access public
    */
    function blockExists($block)
    {
        return isset($this->_blocks[$block]);
    }


   /**
    * Returns the name of the (first) block that contains the specified placeholder.
    *
    * @param    string  Name of the placeholder you're searching
    * @param    string  Name of the block to scan. If left out (default) all blocks are scanned.
    * @return   string  Name of the (first) block that contains the specified placeholder.
    *                   If the placeholder was not found an empty string is returned.
    * @access   public
    * @throws   PEAR_Error
    */
    function placeholderExists($placeholder, $block = '')
    {
        if ('' != $block && !isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if ('' != $block) {
            // if we search in the specific block, we should just check the array
            return isset($this->_blockVariables[$block][$placeholder])? $block: '';
        } else {
            // _findParentBlocks returns an array, we need only the first element
            $parents = $this->_findParentBlocks($placeholder);
            return empty($parents)? '': $parents[0];
        }
    } // end func placeholderExists


   /**
    * Sets a callback function.
    *
    * Sigma templates can contain simple function calls.
    * "function call" means that the editor of the template can add
    * special placeholder to the template like 'func_h1("embedded in h1")'.
    * Sigma will grab this function calls and allow you to define a callback
    * function for them.
    *
    * This is an absolutely evil feature. If your application makes heavy
    * use of such callbacks and you're even implementing if-then etc. on
    * the level of a template engine you're reiventing the wheel... - that's
    * actually how PHP came into life. Anyway, sometimes it's handy.
    *
    * Consider also using XML/XSLT or native PHP.
    *
    * <?php
    * ...
    * function h_one($args) {
    *    return sprintf('<h1>%s</h1>', $args[0]);
    * }
    *
    * ...
    * $tpl = new HTML_Template_Sigma( ... );
    * ...
    * $tpl->setCallbackFunction('h1', 'h_one');
    * $tpl->performCallback();
    * ?>
    *
    * template:
    * func_h1('H1 Headline');
    *
    * @param    string    Function name in the template
    * @param    string    Name of the callback function
    * @param    string    Name of the callback object
    * @return   mixed     SIGMA_OK on success, error object on failure
    * @throws   PEAR_Error
    * @access   public
    */
    function setCallbackFunction($tplFunction, $callback)
    {
        if (!$this->_callbackExists($callback)) {
            return $this->raiseError($this->errorMessage(SIGMA_INVALID_CALLBACK), SIGMA_INVALID_CALLBACK);
        }
        $this->_callback[$tplFunction] = $callback;
        return SIGMA_OK;
    } // end func setCallbackFunction


    //------------------------------------------------------------
    //
    // Private methods follow
    //
    //------------------------------------------------------------


   /**
    * Reads the file and returns its content
    * 
    * @param    string    filename
    * @return   string    file content (or error object)
    */    
    function _getFile($filename)
    {
        if (!($fh = @fopen($filename, 'r'))) {
            return $this->raiseError($this->errorMessage(SIGMA_TPL_NOT_FOUND, $filename), SIGMA_TPL_NOT_FOUND);
        }
        $content = fread($fh, filesize($filename));
        fclose($fh);
        return $content;
    }


   /**
    * Recursively builds a list of all variables within a block
    * 
    * @param string block name
    */
    function _buildBlockVariables($block = '__global__')
    {
        $this->_blockVariables[$block] = array();
        preg_match_all($this->variablesRegExp, $this->_blocks[$block], $regs);
        if (0 != count($regs[1])) {
            foreach ($regs[1] as $k => $var) {
                $this->_blockVariables[$block][$var] = true;
            }
        }
        $this->_buildFunctionlist($block);
        if (isset($this->_children[$block]) && is_array($this->_children[$block])) {
            foreach ($this->_children[$block] as $child => $v) {
                $this->_buildBlockVariables($child);
            }
        }
    }


   /**
    * Recusively builds a list of all blocks within the template.
    * 
    * @param    string    template to be scanned
    * @see      $_blocks
    * @throws   PEAR_Error
    * @return   mixed     array of block names on success or error object on failure
    */
    function _buildBlocks($string)
    {
        $blocks = array();
        if (preg_match_all($this->blockRegExp, $string, $regs, PREG_SET_ORDER)) {
            foreach ($regs as $k => $match) {
                $blockname    = $match[1];
                $blockcontent = $match[2];
                if (isset($this->_blocks[$blockname]) || isset($blocks[$blockname])) {
                    return $this->raiseError($this->errorMessage(SIGMA_BLOCK_DUPLICATE, $blockname), SIGMA_BLOCK_DUPLICATE);
                }
                $this->_blocks[$blockname] = $blockcontent;
                $blocks[$blockname] = true;
                $inner              = $this->_buildBlocks($blockcontent);
                if (PEAR::isError($inner)) {
                    return $inner;
                }
                foreach ($inner as $name => $v) {
                    $pattern     = sprintf('@<!--\s+BEGIN\s+%s\s+-->(.*)<!--\s+END\s+%s\s+-->@sm', $name, $name);
                    $replacement = $this->openingDelimiter.'__'.$name.'__'.$this->closingDelimiter;
                    $this->_blocks[$blockname]          = preg_replace($pattern, $replacement, $this->_blocks[$blockname]);
                    $this->_children[$blockname][$name] = true;
                }
            }
        }
        return $blocks;
    }


   /**
    * Resets the object's properties, used before processing a new template
    *
    * @access   private
    * @param    boolean     remove unknown/unused variables?
    * @param    boolean     remove empty blocks?
    * @see setTemplate(), loadTemplateFile()
    */
    function _resetTemplate($removeUnknownVariables = true, $removeEmptyBlocks = true)
    {
        $this->removeUnknownVariables = $removeUnknownVariables;
        $this->removeEmptyBlocks      = $removeEmptyBlocks;
        $this->currentBlock           = '__global__';
        $this->_variables             = array();
        $this->_blocks                = array();
        $this->_children              = array();
        $this->_parsedBlocks          = array();
        $this->_touchedBlocks         = array();
        $this->_functions             = array();
        $this->flagGlobalParsed       = false;
    } // _resetTemplate


   /**
    * Checks whether we have a "prepared" template cached
    * 
    * If we do not do caching, always returns false
    * 
    * @access private
    * @param  $filename source filename
    * @return bool yes/no
    */
    function _isCached($filename)
    {
        if (null === $this->_cacheRoot) {
            return false;
        }
        $cachedName = $this->_cachedName($filename);
        $sourceName = $this->_sourceName($filename);
        // if $sourceName does not exist, error will be thrown later
        $sourceTime = @filemtime($sourceName);
        if ((false !== $sourceTime) && @file_exists($cachedName) && (filemtime($cachedName) > $sourceTime)) {
            return true;
        } else {
            return false;
        }
    } // _isCached


   /**
    * Loads a "prepared" template file
    *
    * @access   private
    * @param    string  filename
    * @param    string  block name
    * @param    string  variable placeholder to replace by a block
    * @return   mixed   SIGMA_OK on success, error object on failure
    */
    function _getCached($filename, $block = '__global__', $placeholder = '')
    {
        $content = $this->_getFile($this->_cachedName($filename));
        if (PEAR::isError($content)) {
            return $content;
        }
        $cache = unserialize($content);
        if ('__global__' != $block) {
            $this->_blocks[$block]         = $cache['blocks']['__global__'];
            $this->_blockVariables[$block] = $cache['variables']['__global__'];
            $this->_children[$block]       = $cache['children']['__global__'];
            $this->_functions[$block]      = $cache['functions']['__global__'];
            unset($cache['blocks']['__global__'], $cache['variables']['__global__'], $cache['children']['__global__'], $cache['functions']['__global__']);
        }
        $this->_blocks         = array_merge($this->_blocks, $cache['blocks']);
        $this->_blockVariables = array_merge($this->_blockVariables, $cache['variables']);
        $this->_children       = array_merge($this->_children, $cache['children']);
        $this->_functions      = array_merge($this->_functions, $cache['functions']);

        // the same thing gets done in addBlockfile()
        if (!empty($placeholder)) {
            $parents = $this->_findParentBlocks($placeholder);
            $this->_replacePlaceholder($parents[0], $placeholder, $block);
        }
        // pull the triggers, if any
        if (isset($cache['triggers'])) {
            return $this->_pullTriggers($cache['triggers']);
        }
        return SIGMA_OK;
    } // _getCached


   /**
    * Returns a full name of a "prepared" template file
    * 
    * @access private
    * @param string  source filename, relative to root directory
    * @return string filename
    */
    function _cachedName($filename)
    {
        if ('/' == $filename{0} && '/' == substr($this->_cacheRoot, -1)) {
            $filename = substr($filename, 1);
        }
        $filename = str_replace('/', '__', $filename);
        return $this->_cacheRoot. $filename. '.it';
    } // _cachedName


   /**
    * Returns a full name of a "source" template file
    *
    * @param string   source filename, relative to root directory
    * @access private
    * @return string
    */
    function _sourceName($filename)
    {
        if ('/' == $filename{0} && '/' == substr($this->fileRoot, -1)) {
            $filename = substr($filename, 1);
        }
        return $this->fileRoot . $filename;
    } // _sourceName


   /**
    * Writes a prepared template to disk
    *
    * @access private
    * @param string   source filename, relative to root directory
    * @param string   name of the block to save
    * @return mixed   SIGMA_OK on success, error object on failure
    */
    function _writeCache($filename, $block)
    {
        // do not save anything if no cache dir, but do pull triggers
        if (null !== $this->_cacheRoot) {
            $cache = array(
                'blocks'    => array(),
                'variables' => array(),
                'children'  => array(),
                'functions' => array()
            );
            $cachedName = $this->_cachedName($filename);
            $this->_buildCache($cache, $block);
            if ('__global__' != $block) {
                foreach (array_keys($cache) as $k) {
                    $cache[$k]['__global__'] = $cache[$k][$block];
                    unset($cache[$k][$block]);
                }
            }
            if (isset($this->_triggers[$block])) {
                $cache['triggers'] = $this->_triggers[$block];
            }
            if (!($fh = @fopen($cachedName, 'w'))) {
                return $this->raiseError($this->errorMessage(SIGMA_CACHE_ERROR, $cachedName), SIGMA_CACHE_ERROR);
            }
            fwrite($fh, serialize($cache));
            fclose($fh);
        }
        // now pull triggers
        if (isset($this->_triggers[$block])) {
            if (PEAR::isError($res = $this->_pullTriggers($this->_triggers[$block]))) {
                return $res;
            }
            unset($this->_triggers[$block]);
        }
        return SIGMA_OK;
    } // _writeCache


   /**
    * Readies the prepared template for saving
    *
    * @access private
    * @param array   prepared template
    * @param string  block to add
    */
    function _buildCache(&$cache, $block)
    {
        if (!$this->_options['trim_on_save']) {
            $cache['blocks'][$block] = $this->_blocks[$block];
        } else {
            $cache['blocks'][$block] = preg_replace(
                                         array('/^\\s+/m', '/\\s+$/m', '/(\\r?\\n)+/'),
                                         array('', '', "\n"),
                                         $this->_blocks[$block]
                                       );
        }
        $cache['variables'][$block] = $this->_blockVariables[$block];
        $cache['functions'][$block] = isset($this->_functions[$block])? $this->_functions[$block]: array();
        if (!isset($this->_children[$block])) {
            $cache['children'][$block] = array();
        } else {
            $cache['children'][$block] = $this->_children[$block];
            foreach (array_keys($this->_children[$block]) as $child) {
                $this->_buildCache($cache, $child);
            }
        }
    }


   /**
    * Recursively removes all data belonging to a block
    * 
    * @param    string    block name
    * @param    boolean   true if the parsed contents of the block should be kept
    * @return   mixed     SIGMA_OK on success, error object on failure
    * @see      replaceBlock(), replaceBlockfile()
    */
    function _removeBlockData($block, $keepContent = false)
    {
        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if (!empty($this->_children[$block])) {
            foreach (array_keys($this->_children[$block]) as $child) {
                $this->_removeBlockData($child, false);
            }
            unset($this->_children[$block]);
        }
        unset($this->_blocks[$block]);
        unset($this->_blockVariables[$block]);
        unset($this->_hiddenBlocks[$block]);
        unset($this->_touchedBlocks[$block]);
        unset($this->_functions[$block]);
        if (!$keepContent) {
            unset($this->_parsedBlocks[$block]);
        }
        return SIGMA_OK;
    }


   /**
    * Returns the names of the blocks where the variable placeholder appears
    *
    * @param    string    variable name
    * @return    array    block names
    */
    function _findParentBlocks($variable)
    {
        $parents = array();
        foreach ($this->_blockVariables as $blockname => $varnames) {
            if (!empty($varnames[$variable])) {
                $parents[] = $blockname;
            }
        }
        return $parents;
    }


   /**
    * Replaces a variable placeholder by a block placeholder
    * 
    * Of course, it also updates the necessary arrays
    * 
    * @param    string  name of the block containing the placeholder
    * @param    string  variable name
    * @param    string  block name
    */
    function _replacePlaceholder($parent, $placeholder, $block)
    {
        $this->_children[$parent][$block] = true;
        $this->_blockVariables[$parent]['__'.$block.'__'] = true;
        $this->_blocks[$parent]    = str_replace($this->openingDelimiter.$placeholder.$this->closingDelimiter,
                                                        $this->openingDelimiter.'__'.$block.'__'.$this->closingDelimiter,
                                                        $this->_blocks[$parent] );
        unset($this->_blockVariables[$parent][$placeholder]);
    }


   /**
    * Generates a placeholder to replace an <!-- INCLUDE --> statement
    * 
    * @access   private
    * @param    string  filename
    * @param    string  current block name
    * @return   string  a placeholder
    */
    function _makeTrigger($filename, $block)
    {
        $name = 'trigger_' . substr(md5($filename . ' ' . uniqid($block)), 0, 10);
        $this->_triggers[$block][$name] = $filename;
        return $this->openingDelimiter . $name . $this->closingDelimiter;
    }


   /**
    * Replaces the "trigger" placeholders by the matching file contents.
    * 
    * @see addBlockfile()
    * @param    array   array ('trigger placeholder' => 'filename')
    * @return   mixed   SIGMA_OK on success, error object on failure
    */
    function _pullTriggers($triggers)
    {
        foreach ($triggers as $placeholder => $filename) {
            if (PEAR::isError($res = $this->addBlockfile($placeholder, $placeholder, $filename))) {
                return $res;
            }
            // we actually do not need the resultant block...
            $parents = $this->_findParentBlocks('__' . $placeholder . '__');
            // merge current block's children and variables with the parent's ones
            if (isset($this->_children[$placeholder])) {
                $this->_children[$parents[0]] = array_merge($this->_children[$parents[0]], $this->_children[$placeholder]);
            }
            $this->_blockVariables[$parents[0]] = array_merge($this->_blockVariables[$parents[0]], $this->_blockVariables[$placeholder]);
            if (isset($this->_functions[$placeholder])) {
                $this->_functions[$parents[0]] = array_merge($this->_functions[$parents[0]], $this->_functions[$placeholder]);
            }
            // substitute the block's contents into parent's
            $this->_blocks[$parents[0]] = str_replace(
                                            $this->openingDelimiter . '__' . $placeholder . '__' . $this->closingDelimiter, 
                                            $this->_blocks[$placeholder], 
                                            $this->_blocks[$parents[0]]
                                          );
            // remove the stuff that is no more needed
            unset($this->_blocks[$placeholder], $this->_blockVariables[$placeholder], $this->_children[$placeholder], $this->_functions[$placeholder]);
            unset($this->_children[$parents[0]][$placeholder], $this->_blockVariables[$parents[0]]['__' . $placeholder . '__']);
        }
        return SIGMA_OK;
    }


   /**
    * Builds a list of functions from the template
    *
    * @param    string  template
    * @return   string  template with function calls replaced by placeholders
    */
    function _buildFunctionlist($block)
    {
        $this->_functions[$block] = array();
        $template = $this->_blocks[$block];

        while (preg_match($this->functionRegExp, $template, $regs)) {

            $template = substr($template, strpos($template, $regs[0]) + strlen($regs[0]));
            $head     = $this->_getToken($template, ')');
            $args     = array();
            $funcId   = substr(md5($regs[0] . $head . ')'), 0, 10);
            $template = str_replace($regs[0] . $head . ')', '{__function_' . $funcId . '__}', $template);

            // update block info
            $this->_blocks[$block] = str_replace($regs[0] . $head . ')', '{__function_' . $funcId . '__}', $this->_blocks[$block]);
            $this->_blockVariables[$block]['__function_' . $funcId . '__'] = true;

            while ('' != $head && $arg2 = trim($this->_getToken($head, ','))) {
                $args[] = ('"' == $arg2{0} || "'" == $arg2{0}) ? substr($arg2, 1, -1) : $arg2;
                if ($arg2 == $head) {
                    break;
                }
                $head = substr($head, strlen($arg2) + 1);
            }

            $this->_functions[$block][$funcId] = array(
                'name'    => $regs[1],
                'args'    => $args
            );
        }
    } // end func _buildFunctionlist


   /**
    * Returns a part of string up to a delimiter
    * 
    * It should handle strings enclosed in single and double quotes, 
    * thus the need for a non-trivial function
    * 
    * @param    string  a string from which to extract
    * @param    mixed   a delimiter or an array of ('delimiter' => true)
    * @return   string  an extracted string
    */
    function _getToken($code, $delimiter)
    {
        if (!is_array($delimiter)) {
            $delimiter = array( $delimiter => true );
        }
        if ('' == $code || isset($delimiter[$code{0}])) {
            return '';
        }

        $len         = strlen($code);
        $enclosed    = false;
        $enclosed_by = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $code{$i};

            if (('"' == $char || "'" == $char) && 
                ($char == $enclosed_by || '' == $enclosed_by) && 
                (0 == $i || ($i > 0 && '\\' != $code{$i - 1}))) 
            {
                $enclosed_by = $enclosed? '': $char;
                $enclosed    = !$enclosed;
            }
            if (!$enclosed && isset($delimiter[$char])) {
                break;
            }
        }
        return substr($code, 0, $i);
    } // end func _getToken


   /**
    * Replaces an opening delimiter by a special string
    * 
    * @param string
    * @return string
    */
    function _preserveOpeningDelimiter($str)
    {
        return (false === strpos($str, $this->openingDelimiter))? 
                $str:
                str_replace($this->openingDelimiter, $this->openingDelimiter . '%preserved%' . $this->closingDelimiter, $str);
    }


   /**
    * Checks for callback function existance
    * 
    * Borrowed from HTML_QuickForm, only accepts static methods as well
    *
    * @param  mixed     a callback, like one used by call_user_func()
    * @access private
    * @return bool
    */
    function _callbackExists($callback)
    {
        if (is_string($callback)) {
            return function_exists($callback);
        } elseif (is_array($callback) && is_object($callback[0])) {
            return method_exists($callback[0], $callback[1]);
        } elseif (is_array($callback) && is_string($callback[0])) {
            return in_array(strtolower($callback[1]), get_class_methods($callback[0]));
        } else {
            return false;
        }
    }
}
?>