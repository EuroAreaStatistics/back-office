<?php

class ProjectUpdates {
  private $file, $updated, $languages;

  public function __construct($themeURL, $languages) {
    $this->languages = $languages;
    $themeURL = preg_match('/^[a-z]+$/', $themeURL) ? $themeURL : 'default';
    $this->file = __DIR__ . "/../../02projects/$themeURL/wizard-edit-repo/updated.json";
    $this->load();
  }

  private function load() {
    $this->updated = file_exists($this->file) ? json_decode(file_get_contents($this->file), TRUE) : array();
  }

  private function save() {
    if (!count($this->updated)) {
      file_put_contents($this->file, "{}\n");
    } else {
      file_put_contents($this->file, json_encode($this->updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }
  }

  public function getLastUpdate($project, $language) {
    if (isset($this->updated[$project]['commit'][$language])) {
       return $this->updated[$project]['commit'][$language];
    } else {
      return NULL;
    }
  }

  public function getDate($project, $format) {
    if (isset($this->updated[$project])) {
      return (new DateTime($this->updated[$project]['date']))->format('Y-m-d');
    } else {
      return NULL;
    }
  }

  public function isOutOfDate($project, $language) {
    return $language != 'en' && isset($this->updated[$project]['commit'][$language]);
  }

  public function updateProject($project, $language, $previousCommit, $upToDate) {
    if ($language == 'en') {
      $this->updated[$project]['date'] = date('c');
      foreach ($this->languages as $l) {
        if ($l != 'en' && !isset($this->updated[$project]['commit'][$l])) {
          $this->updated[$project]['commit'][$l] = $previousCommit;
        }
      }
    } else if ($upToDate && isset($this->updated[$project]['commit'][$language])) {
      unset($this->updated[$project]['commit'][$language]);
      if (!count($this->updated[$project]['commit'])) {
        unset($this->updated[$project]);
      }
    }
    $this->save();
  }
}
