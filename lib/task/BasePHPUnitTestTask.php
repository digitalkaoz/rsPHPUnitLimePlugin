<?php

class BasePHPUnitTestTask extends sfTestUnitTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('coverage-html', null, sfCommandOption::PARAMETER_REQUIRED, 'The folder name for the html coverage'),
      new sfCommandOption('coverage-clover', null, sfCommandOption::PARAMETER_REQUIRED, 'The file name for the clover XML log file'),
      new sfCommandOption('coverage-folder', null, sfCommandOption::PARAMETER_REQUIRED, 'The folder with files to cover'),
    ));

    $this->namespace = 'test';
  }
  
  protected function flushLogs($options,$coverage,$h)
  {
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
  }
  
}
