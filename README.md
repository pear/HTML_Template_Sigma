# HTML_Template_Sigma

[![Build Status](https://github.com/pear/HTML_Template_Sigma/actions/workflows/continuous-integration.yml/badge.svg?branch=master)](https://github.com/pear/HTML_Template_Sigma/actions/workflows/continuous-integration.yml)

This is a repository for [PEAR HTML_Template_Sigma] package that has been migrated from PEAR SVN.

The package is a fork of [PEAR HTML_Template_IT] and can be used as a drop-in replacement for that. The main
advantages of Sigma are
 * implementation of [template caching] and 
 * support for some [extra syntax].

Please report issues via the [PEAR bug tracker] or [GitHub issues].

Pull requests are welcome.

[PEAR HTML_Template_Sigma]: https://pear.php.net/package/HTML_Template_Sigma/
[PEAR HTML_Template_IT]: https://pear.php.net/package/HTML_Template_IT/
[PEAR bug tracker]: https://pear.php.net/bugs/search.php?cmd=display&package_name[]=HTML_Template_Sigma
[GitHub issues]: https://github.com/pear/HTML_Template_Sigma/issues
[template caching]: https://pear.php.net/manual/en/package.html.html-template-sigma.intro-cache.php
[extra syntax]: https://pear.php.net/manual/en/package.html.html-template-sigma.intro-syntax.php

## Installation

The package may be installed either with PEAR

    $ pear install HTML_Template_Sigma

or with composer

    $ composer require pear/html_template_sigma

Composer installation relies completely on autoloading and does not contain `require_once` calls or
use `include-path` option.

## Documentation

...is [available on PEAR website](https://pear.php.net/manual/en/package.html.html-template-sigma.php).

Larger usage examples are [in the repository](./docs).

## Testing, Packaging and Installing (Pear)

To test, run

    $ phpunit tests/

after installing dependencies with composer. You can also test the installed package with

    $ phpunit [PEAR tests dir]/HTML_Template_Sigma

Since PEAR package needs its `require_once` statements re-enabled, please run the helper file before packaging and
installing

    $ php pear-package-helper.php

Then to build, simply

    $ pear package .pear-package/package.xml

To install from scratch

    $ pear install .pear-package/package.xml

To upgrade

    $ pear upgrade -f .pear-package/package.xml
