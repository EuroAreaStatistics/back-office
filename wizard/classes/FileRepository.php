<?php

class FileRepository {

  function getFileID($file) {
    if (file_exists("$this->previewDir/$file")) {
      return 1;
    } else {
      return NULL;
    }
  }

  function readFile($file) {
    return @file_get_contents("$this->previewDir/$file");
  }

  function getLastCommit() {
    return NULL;
  }

  function commit($file, $oldCommitID, $input) {
    // write to preview folder
    @mkdir(dirname("$this->previewDir/$file"), 0777, TRUE);
    $tmp = fopen("$this->previewDir/$file.tmp", "x");
    if ($tmp !== FALSE) {
      fwrite($tmp, $input);
      fclose($tmp);
      rename("$this->previewDir/$file.tmp", "$this->previewDir/$file");
    }
    return 1;
  }

  public function listFiles($dir) {
    $files = array();
    $path = $this->previewDir.'/'.$dir;
    foreach (scandir($path) as $file) {
      if (is_file($path.'/'.$file)) {
        $files[] = $file;
      }
    }
    return $files;
  }

  function __construct($previewDir) {
    $this->previewDir = $previewDir;
    if (!file_exists("$previewDir/")) throw new Exception("$previewDir does not exist");
  }
}
