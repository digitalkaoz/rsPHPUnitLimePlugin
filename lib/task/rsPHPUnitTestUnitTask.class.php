<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Launches unit tests.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfTestUnitTask.class.php 25036 2009-12-07 19:41:58Z Kris.Wallsmith $
 */

class sfPHPUnitTestUnitTask extends sfTestUnitTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('name', sfCommandArgument::OPTIONAL | sfCommandArgument::IS_ARRAY, 'The test name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('xml', null, sfCommandOption::PARAMETER_REQUIRED, 'The file name for the JUnit compatible XML log file'),
      new sfCommandOption('coverage-html', null, sfCommandOption::PARAMETER_REQUIRED, 'The folder name for the html coverage'),
      new sfCommandOption('coverage-clover', null, sfCommandOption::PARAMETER_REQUIRED, 'The file name for the clover XML log file'),
      new sfCommandOption('coverage-folder', null, sfCommandOption::PARAMETER_REQUIRED, 'The folder with files to cover'),
    ));

    $this->aliases = array('test-phpunit');
    $this->namespace = 'test';
    $this->name = 'phpunit';
    $this->briefDescription = 'Launches unit tests';

    $this->detailedDescription = <<<EOF
The [test:unit|INFO] task launches unit tests:

  [./symfony test:unit|INFO]

The task launches all tests found in [test/unit|COMMENT].

If some tests fail, you can use the [--trace|COMMENT] option to have more
information about the failures:

    [./symfony test:unit -t|INFO]

You can launch unit tests for a specific name:

  [./symfony test:unit strtolower|INFO]

You can also launch unit tests for several names:

  [./symfony test:unit strtolower strtoupper|INFO]

The task can output a JUnit compatible XML log file with the [--xml|COMMENT]
options:

  [./symfony test:unit --xml=log.xml|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $coverage = null;
    
    if (count($arguments['name']))
    {
      $files = array();

      foreach ($arguments['name'] as $name)
      {
        $finder = sfFinder::type('file')->follow_link()->name(basename($name).'Test.php');
        $files = array_merge($files, $finder->in(sfConfig::get('sf_test_dir').'/unit/'.dirname($name)));
      }

      if($allFiles = $this->filterTestFiles($files, $arguments, $options))
      {
        foreach ($allFiles as $file)
        {
          include($file);
        }
      }
      else
      {
        $this->logSection('test', 'no tests found', null, 'ERROR');
      }
    }
    else
    {
      require_once dirname(__FILE__).'/../test/PHPUnitLimeHarness.class.php';

      $h = new PHPUnitLimeHarness(array(
        'force_colors' => isset($options['color']) && $options['color'],
        'verbose'      => isset($options['trace']) && $options['trace'],
      ));
      $h->addPlugins(array_map(array($this->configuration, 'getPluginConfiguration'), $this->configuration->getPlugins()));
      $h->base_dir = sfConfig::get('sf_test_dir').'/unit';

      // filter and register unit tests
      $finder = sfFinder::type('file')->follow_link()->name('*Test.php');
      $h->register($this->filterTestFiles($finder->in($h->base_dir), $arguments, $options));

      if(($options['coverage-html'] || $options['coverage-clover']))
      {
        $coverage = new LimeCodeCoverage();
        $coverage->setFolder($options['coverage-folder']);
      }
      
      $ret = $h->run($coverage,($options['coverage-html'] || $options['coverage-clover'])) ? 0 : 1;

      //flush junit xml
      if ($options['xml'])
      {
        $this->logSection('junit', 'writing junit data to '.$options['xml']);
        require_once dirname(__FILE__).'/../test/LimePHPUnit_Util_Log_JUnit.php';
        $writer = new LimePHPUnit_Util_Log_JUnit($options['xml']);
        $writer->loadFromLime($h->to_xml());
        $writer->flush();
      }

      //flush clover xml
      if($options['coverage-clover'])
      {
        $this->logSection('clover', 'writing clover data to '.$options['coverage-clover']);
        require_once 'PHP/CodeCoverage/Report/Clover.php';
        $writer = new PHP_CodeCoverage_Report_Clover();
        $writer->process($coverage, $options['coverage-clover']);
      }
      
      //flush html
      if($options['coverage-html'])
      {
        $this->logSection('html', 'writing coverage html to '.$options['coverage-html']);
        require_once 'PHP/CodeCoverage/Report/HTML.php';
        $writer = new PHP_CodeCoverage_Report_HTML();
        $writer->process($coverage, $options['coverage-html']);      
      }
      
      return $ret;
    }
  }
}