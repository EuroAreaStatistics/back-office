<?php

class ProjectUpdates {
  private $file, $updated, $languages;

  public function __construct($themeURL, $languages) {
    $this->languages = $languages;
    $themeURL = preg_match('/^[a-z]+$/', $themeURL) ? $themeURL : 'default';
    $this->file = __DIR__ . "/../02projects/$themeURL/wizard-edit-repo/updated.json";
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
    if (isset($this->updated[$project]['oldRefLang'][$language])) {
       return $this->updated[$project]['oldRefLang'][$language];
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
    return $language != 'en' && isset($this->updated[$project]['oldRefLang'][$language]);
  }

  public function updateProject($project, $language, $diffsRef, $upToDate) {
    unset($this->updated[$project]['commit']);
    if ($language == 'en') {
      $this->updated[$project]['date'] = date('c');
      foreach ($this->languages as $l) {
        if ($l != 'en') {
          $diffs = isset($this->updated[$project]['oldRefLang'][$l]) ? $this->updated[$project]['oldRefLang'][$l] : [];
          foreach ($diffsRef as $diff) {
            if (!isset($diffs[$diff['keyName']])) {
              $diffs[$diff['keyName']] = $diff['srcTranslation'];
            }
          }
          if (count($diffs)) {
            $this->updated[$project]['oldRefLang'][$l] = $diffs;
          }
        }
      }
    } else if ($upToDate && isset($this->updated[$project]['oldRefLang'][$language])) {
      unset($this->updated[$project]['oldRefLang'][$language]);
      if (!count($this->updated[$project]['oldRefLang'])) {
        unset($this->updated[$project]);
      }
    }
    $this->save();
  }
}
