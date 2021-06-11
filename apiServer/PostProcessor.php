<?php

class PostProcessor {
  // example usage in oneOffSettings:
  //
  // $oneOffSettingsCharts = array (
  //   'chartID' =>  array (
  //     'options' =>    array (
  //       'postProcess' => array(array('scale', 100)),
  //     ),


  // public entry point: post-process all charts according to options.postProcess
  static function process($config) {
    foreach ($config['charts'] as $chartID => &$c) {
      $c = self::processChart($c);
    }
    return $config;
  }

  private static function processChart($chart) {
    // call each job method with $chart as first parameter
    if (isset($chart['options']['postProcess'])) {
      foreach ($chart['options']['postProcess'] as $job) {
        $method = 'job_' . $job[0];
        $job[0] = $chart;
        $chart = call_user_func_array(array(self, $method), $job);
      }
    }
    return $chart;
  }

  // user-defined post-processing jobs start here
  // method name must start with 'job_'

  // multiply each entry of the data by x
  private static function job_scale($chart, $x) {
    if (isset($chart['data']['data'])) {
      array_walk_recursive($chart['data']['data'], function(&$v) use($x) {
        if (is_numeric($v)) {
          $v *= $x;
        }
      });
    }
    return $chart;
  }
}
