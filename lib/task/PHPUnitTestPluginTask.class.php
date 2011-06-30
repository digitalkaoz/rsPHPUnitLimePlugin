<?php

/**
 * Launches a plugin test suite.
 * 
 * @package     sfTaskExtraPlugin
 * @subpackage  task
 * @author      Kris Wallsmith <kris.wallsmith@symfony-project.com>
 * @version     SVN: $Id: sfTestPluginTask.class.php 25047 2009-12-07 20:58:07Z Kris.Wallsmith $
 */
class PHPUnitTestPluginTask extends BasePHPUnitTestTask
{
  protected
    $plugins = array();

  /**
   * @see sfTask
   */
  protected function configure()
  {
    parent::configure();
    
    $this->addArguments(array(
      new sfCommandArgument('plugin', sfCommandArgument::REQUIRED | sfCommandArgument::IS_ARRAY, 'The plugin name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('only', null, sfCommandOption::PARAMETER_REQUIRED, 'Only run "unit" or "functional" tests'),
    ));

    $this->namespace = 'test';
    $this->name = 'phpunit-plugin';

    $this->briefDescription = 'Launches a plugin test suite';

    $this->detailedDescription = <<<EOF
The [test:plugin|INFO] task launches a plugin's test suite:

  [./symfony test:plugin sfExamplePlugin|INFO]

You can specify only unit or functional tests with the [--only|COMMENT] option:

  [./symfony test:plugin sfExamplePlugin --only=unit|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    foreach ($arguments['plugin'] as $plugin)
    {
      $this->checkPluginExists($plugin);
    }

    if ($options['only'] && !in_array($options['only'], array('unit', 'functional')))
    {
      throw new sfCommandException(sprintf('The --only option must be either "unit" or "functional" ("%s" given)', $options['only']));
    }

    // use the test:* task but filter the files
    $this->plugins = $arguments['plugin'];
    $this->dispatcher->connect('task.test.filter_test_files', array($this, 'filterTestFiles'));

    switch ($options['only'])
    {
      case 'unit':
        $task = new PHPUnitTestUnitTask($this->dispatcher, $this->formatter);
        break;
      case 'functional':
        $task = new PHPUnitTestFunctionalTask($this->dispatcher, $this->formatter);
        break;
      default:
        $task = new PHPUnitTestAllTask($this->dispatcher, $this->formatter);
    }

    unset($options['plugin'],$options['only']);

    $task->setConfiguration($this->configuration);
    $task->setCommandApplication($this->commandApplication);
    $task->run(array(),$options);

    $this->dispatcher->disconnect('task.test.filter_test_files', array($this, 'filterFiles'));
  }

  /**
   * Listens to the task.test.filter_test_files event.
   * 
   * @param sfEvent $event
   * @param array   $files
   * 
   * @return array
   */
  public function filterFiles(sfEvent $event, $files)
  {
    $filtered = array();
    foreach ($this->plugins as $plugin)
    {
      $filtered = $this->configuration->getPluginConfiguration($plugin)->filterTestFiles($event, $filtered);
    }

    return $filtered;
  }

  /**
   * @see doCheckPluginExists()
   */
  public function checkPluginExists($plugin, $boolean = true)
  {
    self::doCheckPluginExists($this, $plugin, $boolean);
  }

  /**
   * Checks if a plugin exists.
   *
   * The plugin directory must exist and have at least one file or folder
   * inside for that plugin to exist.
   *
   * @param   string  $plugin
   * @param   boolean $boolean Whether to throw exception if plugin exists (false) or doesn't (true)
   *
   * @throws  sfException If the plugin does not exist
   */
  static public function doCheckPluginExists($task, $plugin, $boolean = true)
  {
    if (in_array($plugin, $task->configuration->getPlugins()))
    {
      // plugin exists if a plugin configuration exists
      $exists = true;
    }
    else
    {
      // otherwise check the plugins directory
      $root = sfConfig::get('sf_plugins_dir').'/'.$plugin;
      $exists = is_dir($root) && count(sfFinder::type('any')->in($root)) > 0;
    }

    if ($boolean != $exists)
    {
      throw new sfException(sprintf($boolean ? 'Plugin "%s" does not exist' : 'Plugin "%s" exists', $plugin));
    }
  }

}
