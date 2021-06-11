<?php

function getCountryList () {

  ini_set('auto_detect_line_endings',TRUE);
  $handle = fopen(__DIR__.'/PISA.csv','r');
  $codes = array();
  while ( ($data = fgetcsv($handle) ) !== FALSE ) {
    $codes[$data[0]]=$data[1];
  }
  ini_set('auto_detect_line_endings',FALSE);
  return $codes;
};


$data = getCountryList ();

file_put_contents('PISA.json',json_encode($data));

