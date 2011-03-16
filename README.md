rsPHPUnitLimePlugin
=======

collect coverage data for clover or html from lime tests

Installation
------------

**Git:** https://github.com/digitalkaoz/rsPHPUnitLimePlugin.git

**Svn:** https://svn.github.com/digitalkaoz/rsPHPUnitLimePlugin.git


Usage
-----

**unit test coverage**

* symfony test:phpunit-unit --coverage-clover=log/clover-unit.xml
* symfony test:phpunit-unit --coverage-html=log/coverage-unit
* symfony test:phpunit-unit --coverage-folder=lib/
* symfony test:phpunit-unit --xml=log/junit-unit.xml

**functional test coverage**

* symfony test:phpunit-functional --coverage-clover=log/clover-functional.xml frontend
* symfony test:phpunit-functional --coverage-html=log/coverage-functional frontend
* symfony test:phpunit-functional --coverage-folder=apps/ frontend
* symfony test:phpunit-functional --xml=log/junit-functional.xml frontend

**plugin test coverage**

* symfony test:phpunit-plugin --coverage-clover=log/clover-functional.xml sfGuardPlugin
* symfony test:phpunit-plugin --coverage-html=log/coverage-functional sfGuardPlugin
* symfony test:phpunit-plugin --coverage-folder=plugin/sfGuardPlugin sfGuardPlugin
* symfony test:phpunit-plugin --xml=log/junit-functional.xml sfGuardPlugin

**all test coverage**

* symfony test:phpunit-all --coverage-clover=log/clover-functional.xml
* symfony test:phpunit-all --coverage-html=log/coverage-functional sfGuardPlugin
* symfony test:phpunit-all --coverage-folder=./
* symfony test:phpunit-all --xml=log/junit-functional.xml
