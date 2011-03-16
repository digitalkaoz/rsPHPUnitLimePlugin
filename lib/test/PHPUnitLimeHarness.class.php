<?php

class PHPUnitLimeHarness extends sfLimeHarness
{
  
  public function run(PHP_CodeCoverage &$coverage = null, $should_cover=false)
  {
    if (!count($this->files))
    {
      throw new Exception('You must register some test files before running them!');
    }

    // sort the files to be able to predict the order
    sort($this->files);

    $this->stats = array(
      'files'        => array(),
      'failed_files' => array(),
      'failed_tests' => 0,
      'total'        => 0,
    );

    foreach ($this->files as $file)
    {
      if($should_cover)
      {
        $coverage->start($file);
      }
      
      $this->stats['files'][$file] = array();
      $stats = &$this->stats['files'][$file];

      $relative_file = $this->get_relative_file($file);

      $test_file = tempnam(sys_get_temp_dir(), 'lime');
      $result_file = tempnam(sys_get_temp_dir(), 'lime');
      $coverage_file = tempnam(sys_get_temp_dir(), 'lime');
      
      $pluginDir = substr($file, 0,  strpos($file, '/test/'));
      $fixtureProject = $pluginDir.'/test/fixtures/project';
      
      file_put_contents($test_file, <<<EOF
<?php
function lime_shutdown()
{
    \$coverage = xdebug_get_code_coverage();
    file_put_contents('$coverage_file', serialize(\$coverage));
    xdebug_stop_code_coverage();
              
  file_put_contents('$result_file', serialize(lime_test::to_array()));
}
              
register_shutdown_function('lime_shutdown');
              
  xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
              
include('$file');
EOF
      );
      
      ob_start();
      // see http://trac.symfony-project.org/ticket/5437 for the explanation on the weird "cd" thing
      passthru(sprintf('cd & %s %s 2>&1', escapeshellarg($this->php_cli), escapeshellarg($test_file)), $return);
      ob_end_clean();
      
      if($should_cover)
      {
        $covered_files = unserialize(file_get_contents($coverage_file));
        $coverage->append($covered_files,  str_replace('/', '_', $file));
        unlink($coverage_file);
      }
      
      unlink($test_file);

      $output = file_get_contents($result_file);
      $stats['output'] = $output ? unserialize($output) : '';
      if (!$stats['output'])
      {
        $stats['output'] = array(array('file' => $file, 'tests' => array(), 'stats' => array('plan' => 1, 'total' => 1, 'failed' => array(0), 'passed' => array(), 'skipped' => array(), 'errors' => array())));
      }
      unlink($result_file);

      $file_stats = &$stats['output'][0]['stats'];

      $delta = 0;
      if ($return > 0)
      {
        $stats['status'] = $file_stats['errors'] ? 'errors' : 'dubious';
        $stats['status_code'] = $return;
      }
      else
      {
        $this->stats['total'] += $file_stats['total'];

        if (!$file_stats['plan'])
        {
          $file_stats['plan'] = $file_stats['total'];
        }

        $delta = $file_stats['plan'] - $file_stats['total'];
        if (0 != $delta)
        {
          $stats['status'] = $file_stats['errors'] ? 'errors' : 'dubious';
          $stats['status_code'] = 255;
        }
        else
        {
          $stats['status'] = $file_stats['failed'] ? 'not ok' : ($file_stats['errors'] ? 'errors' : 'ok');
          $stats['status_code'] = 0;
        }
      }

      $this->output->echoln(sprintf('%s%s%s', substr($relative_file, -min(67, strlen($relative_file))), str_repeat('.', 70 - min(67, strlen($relative_file))), $stats['status']));

      if ('dubious' == $stats['status'])
      {
        $this->output->echoln(sprintf('    Test returned status %s', $stats['status_code']));
      }

      if ('ok' != $stats['status'])
      {
        $this->stats['failed_files'][] = $file;
      }

      if ($delta > 0)
      {
        $this->output->echoln(sprintf('    Looks like you planned %d tests but only ran %d.', $file_stats['plan'], $file_stats['total']));

        $this->stats['failed_tests'] += $delta;
        $this->stats['total'] += $delta;
      }
      else if ($delta < 0)
      {
        $this->output->echoln(sprintf('    Looks like you planned %s test but ran %s extra.', $file_stats['plan'], $file_stats['total'] - $file_stats['plan']));
      }

      if (false !== $file_stats && $file_stats['failed'])
      {
        $this->stats['failed_tests'] += count($file_stats['failed']);

        $this->output->echoln(sprintf("    Failed tests: %s", implode(', ', $file_stats['failed'])));
      }

      if (false !== $file_stats && $file_stats['errors'])
      {
        $this->output->echoln('    Errors:');

        $error_count = count($file_stats['errors']);
        for ($i = 0; $i < 3 && $i < $error_count; ++$i)
        {
          $this->output->echoln('    - ' . $file_stats['errors'][$i]['message'], null, false);
        }
        if ($error_count > 3)
        {
          $this->output->echoln(sprintf('    ... and %s more', $error_count-3));
        }
      }
      
      if($should_cover)
      {
        $coverage->stop();
      }
    }
    
    if (count($this->stats['failed_files']))
    {
      $format = "%-30s  %4s  %5s  %5s  %5s  %s";
      $this->output->echoln(sprintf($format, 'Failed Test', 'Stat', 'Total', 'Fail', 'Errors', 'List of Failed'));
      $this->output->echoln("--------------------------------------------------------------------------");
      foreach ($this->stats['files'] as $file => $stat)
      {
        if (!in_array($file, $this->stats['failed_files']))
        {
          continue;
        }
        $relative_file = $this->get_relative_file($file);

        if (isset($stat['output'][0]))
        {
          $this->output->echoln(sprintf($format, substr($relative_file, -min(30, strlen($relative_file))), $stat['status_code'], count($stat['output'][0]['stats']['failed']) + count($stat['output'][0]['stats']['passed']), count($stat['output'][0]['stats']['failed']), count($stat['output'][0]['stats']['errors']), implode(' ', $stat['output'][0]['stats']['failed'])));
        }
        else
        {
          $this->output->echoln(sprintf($format, substr($relative_file, -min(30, strlen($relative_file))), $stat['status_code'], '', '', ''));
        }
      }

      $this->output->red_bar(sprintf('Failed %d/%d test scripts, %.2f%% okay. %d/%d subtests failed, %.2f%% okay.',
        $nb_failed_files = count($this->stats['failed_files']),
        $nb_files = count($this->files),
        ($nb_files - $nb_failed_files) * 100 / $nb_files,
        $nb_failed_tests = $this->stats['failed_tests'],
        $nb_tests = $this->stats['total'],
        $nb_tests > 0 ? ($nb_tests - $nb_failed_tests) * 100 / $nb_tests : 0
      ));

      if ($this->options['verbose'])
      {
        foreach ($this->to_array() as $testsuite)
        {
          $first = true;
          foreach ($testsuite['stats']['failed'] as $testcase)
          {
            if (!isset($testsuite['tests'][$testcase]['file']))
            {
              continue;
            }

            if ($first)
            {
              $this->output->echoln('');
              $this->output->error($this->get_relative_file($testsuite['file']).$this->extension);
              $first = false;
            }

            $this->output->comment(sprintf('  at %s line %s', $this->get_relative_file($testsuite['tests'][$testcase]['file']).$this->extension, $testsuite['tests'][$testcase]['line']));
            $this->output->info('  '.$testsuite['tests'][$testcase]['message']);
            $this->output->echoln($testsuite['tests'][$testcase]['error'], null, false);
          }
        }
      }
    }
    else
    {
      $this->output->green_bar(' All tests successful.');
      $this->output->green_bar(sprintf(' Files=%d, Tests=%d', count($this->files), $this->stats['total']));
    }

    return $this->stats['failed_files'] ? false : true;
  }
  
}
