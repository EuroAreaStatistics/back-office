<?php

// find name for project in langIndexDefault
function projectName($project) {
  global $langIndexDefault;

  $name = NULL;
  $name = @$langIndexDefault['projects'][$project.'_main'];
  if ($name === NULL) $name = @$langIndexDefault['projects'][$project];
  if ($name === NULL) $name = $project;
  return $name;
}
