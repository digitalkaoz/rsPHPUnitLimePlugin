<?php

require_once 'PHPUnit/Util/Printer.php';
require_once 'PHPUnit/Framework/TestListener.php';
require_once 'PHPUnit/Util/Log/JUnit.php';

class LimePHPUnit_Util_Log_JUnit extends PHPUnit_Util_Log_JUnit
{
  public function loadFromLime($xml)
  {
    $this->document = new DOMDocument('1.0', 'UTF-8');
    $this->document->formatOutput = TRUE;
    
    $this->document->loadXML($xml);
  }
}