<?php

//Verwaltet Liste aller Projekte
//Verwaltet Config Datei mit Freischaltung der einzelnen Sprachen

require_once __DIR__.'/Json.php';
require_once __DIR__.'/WizardProject.php';

class WizardProjects {
  private $directories = array();
  private $repo;
  private $projectLanguages;

  public function __construct($repo) {
    global $themeURL;

    $this->directories['projects'] = 'wizardProjects';
    $this->directories['config'] = $this->directories['projects'] . '/config';
    $this->directories['lang'] = $this->directories['projects'] . '/lang';
    $this->repo = $repo;
  }

  public function getDirectory($key) {
    return $this->directories[$key];
  }

  // check whether a language is valid
  public static function validLanguage($code) {
    return preg_match('/^[a-z]{2}$/', $code);
  }

  // find all wizard projects
  public function find() {
    $projects = array();
    foreach ($this->repo->listFiles($this->directories['config']) as $name) {
       if (preg_match('/^(.*)\.json$/', $name, $m)
           && WizardProject::validURL($m[1])) {
         $projects[] = $this->fetch($m[1]);
       }
    }
    return $projects;
  }

  // fetch a wizard project
  public function fetch($url) {
    $project = new WizardProject($this, $this->repo);
    $project->setURL($url);
    return $project;
  }

  // create new project with default configuration and add to projects
  public function addDefault($title,$user,$defaultFile) {
    $project = new WizardProject($this, $this->repo);
    $project->loadDefaultConfig($title,$user,$defaultFile);
    $project->save(TRUE);
    return $project;
  }

  // update existing project
  public function update($config) {
    $project = new WizardProject($this, $this->repo);
    $project->setConfig($config);
    $project->save();
    return $project;
  }

  public function findLanguages($url) {
    if (!isset($this->projectLanguages)) {
      $this->projectLanguages = Json::decode($this->repo->readFile($this->directories['projects'].'/projectLanguages.json'));
    }
    $langs = @$this->projectLanguages[$url];
    return is_array($langs) ? $langs : array();
  }

  public function setLanguages($languages) {
    if (!is_array($languages)) throw new Exception("associative array expected");
    foreach ($languages as $project => &$langs) {
      if (!WizardProject::validURL($project)) throw new Exception("invalid project url: $project");
      if (!is_array($langs)) throw new Exception("array of languages expected");
      // remove empty language (marks project as updated)
      $langs = array_diff($langs, array(''));
      // ignore keys in language array and sort alphabetically
      sort($langs);
      foreach ($langs as $lang) {
        if (!self::validLanguage($lang)) throw new Exception("invalid language: $lang");
      }
    }
    $this->findLanguages('');
    // keep settings for projects which were not updated
    $languages = array_replace($this->projectLanguages, $languages);
    $commitID = $this->repo->commit($this->directories['projects'].'/projectLanguages.json', NULL, Json::encode($languages));
    $this->projectLanguages = $languages;
    return $commitID;
  }
}
