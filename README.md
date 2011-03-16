rsPHPUnitLimePlugin
=======

collect coverage data for clover or html from lime tests

Installation
------------

**Git:** [https://github.com/digitalkaoz/rsPHPUnitLimePlugin.git][1]

**Svn:** [https://svn.github.com/digitalkaoz/rsPHPUnitLimePlugin.git][2]


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
