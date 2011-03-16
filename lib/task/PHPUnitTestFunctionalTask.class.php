<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Launches functional tests.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfTestFunctionalTask.class.php 25036 2009-12-07 19:41:58Z Kris.Wallsmith $
 */
class PHPUnitTestFunctionalTask extends BasePHPUnitTestTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
      new sfCommandArgument('controller', sfCommandArgument::OPTIONAL | sfCommandArgument::IS_ARRAY, 'The controller name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('xml', null, sfCommandOption::PARAMETER_REQUIRED, 'The file name for the JUnit compatible XML log file'),
    ));

    $this->aliases = array('phpunit-test-functional');
    $this->name = 'phpunit-functional';
    $this->briefDescription = 'Launches functional tests with phpunit';

    $this->detailedDescription = <<<EOF
The [test:functional|INFO] task launches functional tests for a
given application:

  [./symfony test:functional frontend|INFO]

The task launches all tests found in [test/functional/%application%|COMMENT].

If some tests fail, you can use the [--trace|COMMENT] option to have more
information about the failures:

    [./symfony test:functional frontend -t|INFO]

You can launch all functional tests for a specific controller by
giving a controller name:

  [./symfony test:functional frontend article|INFO]

You can also launch all functional tests for several controllers:

  [./symfony test:functional frontend article comment|INFO]

The task can output a JUnit compatible XML log file with the [--xml|COMMENT]
options:

  [./symfony test:functional --xml=log.xml|INFO]
EOF;
    
    parent::configure();
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $coverage = null;
    
    $app = $arguments['application'];

    if (count($arguments['controller']))
    {
      $files = array();

      foreach ($arguments['controller'] as $controller)
      {
        $finder = sfFinder::type('file')->follow_link()->name(basename($controller).'Test.php');
        $files = array_merge($files, $finder->in(sfConfig::get('sf_test_dir').'/functional/'.$app.'/'.dirname($controller)));
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
        $this->logSection('functional', 'no controller found', null, 'ERROR');
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
      $h->base_dir = sfConfig::get('sf_test_dir').'/functional/'.$app;
      
      $finder = sfFinder::type('file')->follow_link()->name('*Test.php');
      $projectTests = $finder->in($h->base_dir);
      
      $pluginTests = array();
      foreach($this->configuration->getPlugins() as $plugin)
      {
        $finder = sfFinder::type('file')->follow_link()->name('*Test.php');
        $pluginTests = array_merge($pluginTests,$finder->in(sfConfig::get('sf_test_dir').'/../plugins/'.$plugin.'/test/functional'));
      }
      
      $tests = array_merge($projectTests,$pluginTests);
      
      //$h->register($this->filterTestFiles($tests, $arguments, $options));
      $h->register($tests);
      
      if(($options['coverage-html'] || $options['coverage-clover']))
      {
        $coverage = new LimeCodeCoverage();
        $coverage->setFolder($options['coverage-folder']);
      }
      
      $ret = $h->run($coverage,($options['coverage-html'] || $options['coverage-clover'])) ? 0 : 1;

      $this->flushLogs($options,$coverage,$h);
      
      return $ret;
    }
  }
  
}