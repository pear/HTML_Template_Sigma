HTML_Template_Sigma: implementation of Integrated Templates API with 
caching added.

This template engine implements Integrated Templates API designed by Ulf
Wendel. This API is also implemented by the original Integrated Template
(now HTML_Template_IT in PEAR) and thus this package can (probably, YMMV)
be used instead of it if you do not call any private methods in your 
programs and do not directly access objects' private properties.

1) New features and additions to API:
  a) Transparent caching of prepared templates. You just have to pass a second
     parameter to the constructor: a directory name (the directory should be
     writable for PHP). After the template gets parsed for blocks and 
     variables, the resultant arrays are serialized and saved into files in 
     this directory. The next time the template file needs to be loaded, the
     prepared one is loaded instead, with a cheap unserialize() instead of
     very expensive RegExp matching logic.

  b) Global variables. Variables set by a function setGlobalVariable() do
     not get cleared after first substitution, unlike ordinary ones, and do
     not trigger "block not empty" logic (block with only global variables is
     still considered "empty"). Can be used for directory prefixes, session 
     identifiers and the stuff like that.

  c) hideBlock(): an opposite of touchBlock(). It prevents block from 
     appearing in the result even if it is "not empty".

2) Removed features and incompatibilities:
  a) $flagCacheTemplatefile and related logic was dropped in favor of a more
     generic approach, see (1.a)

  b) $clearCache and $clearCacheOnParse were removed. $clearCache functionality 
     is now controlled by passing a second parameter to get(), 
     $clearCacheOnParse is assumed to always be false.

  c) Public functions (from IT) init() and free() were removed. Public 
     functions (from ITX) getFunctioncalls(), setFunctioncontent(), 
     getBlocklist(), setCallbackFuntiontable() (sic!), getBlockvariables(),
     BlockvariableExists() were removed.

  e) Callback functions and caching do not mix! If you pass a second parameter
     to the constructor, the template WILL NOT be parsed for function calls.
     If you attempt to call setCallbackFunction() of performCallback() 
     afterwards, an error will be thrown.

Alexey Borzov <avb@php.net>
