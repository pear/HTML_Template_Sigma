# Changes in HTML_Template_Sigma

## 1.4.0 - 2025-10-31

This is the first release installable with composer, changelogs for older versions are available in
package.xml file or [on PEAR website](https://pear.php.net/package/HTML_Template_Sigma/download/All)

* Added a proper `__construct()` method ([PEAR bug #20967], [PEAR bug #23751])
* Fixed examples to run on recent PHP versions
* Unit tests are run on PHP 5.6 to PHP 8.4 thanks to [PHPUnit Polyfills package],
  continuos integration is enabled on GitHub.
* It is possible to throw an instance of `HTML_Template_Sigma_Exception`
  instead of returning `PEAR_Error` in case of error, this is enabled by
  setting an `'exceptions'` option to `true` (it defaults to `false`): 
```
$tpl->setOption('exceptions', true);
```
* Minimum required PHP version is now 5.6

[PEAR bug #20967]: https://pear.php.net/bugs/bug.php?id=20967
[PEAR bug #23751]: https://pear.php.net/bugs/bug.php?id=23751
[PHPUnit Polyfills package]: https://github.com/Yoast/PHPUnit-Polyfills