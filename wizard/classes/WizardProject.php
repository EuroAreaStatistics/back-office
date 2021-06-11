<?php

// laden und speichern einzelner Projekte
// Validierung der einzelnen Eintraege

require_once __DIR__.'/Json.php';

class WizardProject {
  private $collection;
  private $repo;
  private $url;
  private $languages;
  private $previousCommit;

  public function __construct($collection, $repo) {
    $this->collection = $collection;
    $this->repo = $repo;
  }

  // check whether an URL is valid
  public static function validURL($url) {
    return preg_match('/^[a-z0-9-]+$/', $url);
  }

  // raise exception if config is invalid
  public static function validateConfig($config) {
    if (!is_array($config)) throw new Exception("associative array expected as config");
    $wantKeys = array('project', 'charts', 'tabs');
    sort($wantKeys);
    $haveKeys = array_keys($config);
    sort($haveKeys);
    if ($haveKeys != $wantKeys) throw new Exception("config keys mismatch");

    // validate tabs
    if (!is_array($config['tabs'])) throw new Exception("associative array expected as config.tabs");
    foreach ($config['tabs'] as $tab) {
      if (!is_array($tab)) throw new Exception("associative array expected as tab");
      if (!is_array(@$tab['charts'])) throw new Exception("array expected as tab.charts");
      foreach ($tab['charts'] as $chart) {
        if (!isset($config['charts'][$chart])) throw new Exception("chart '$chart' not in config");
      }
    }

    // validate charts
    if (!is_array($config['charts'])) throw new Exception("associative array expected as config.charts");
    foreach ($config['charts'] as $chart) {
      if (!is_array($chart)) throw new Exception("associative array expected as chart");
      if (!is_array(@$chart['options'])) throw new Exception("associative array expected as chart.options");
    }

    // validate project
    if (!is_array(@$config['project']['tabs'])) throw new Exception("array expected as project.tabs");
    foreach ($config['project']['tabs'] as $tab) {
      if (!isset($config['tabs'][$tab])) throw new Exception("tab '$tab' not in config");
    }

    // TODO: check more properties
  }

  // extract texts from configuration for translation
  public static function getTranslatableTexts($config) {
    $lang = array();
    foreach ($config as $k => $v) {
      if ($k === 'en') return $v;
      if (is_array($v)) {
        $v = self::getTranslatableTexts($v);
        if (!is_array($v) || count($v)) $lang[$k] = $v;
      }
    }
    return $lang;
  }

  // update texts in configuration from translation
  public static function updateTranslations($config, $lang) {
    if (isset($config['en'])) {
      $config['en'] = (string)$lang;
      return $config;
    }
    if (!is_array($config) || !is_array($lang)) {
      return $config;
    }
    foreach ($config as $k => &$v) {
      if (isset($lang[$k])) {
        $v = self::updateTranslations($v, $lang[$k]);
      }
    }
    return $config;
  }

  public function getURL() {
    return $this->url;
  }

  public function setURL($url) {
    if (!self::validURL($url)) throw new Exception("invalid url: $url");
    $this->url = $url;
  }

  public function getLanguages() {
    if (!isset($this->languages)) {
      $this->languages = $this->collection->findLanguages($this->url);
    }
    return $this->languages;
  }

  public function getConfig() {
    if (!isset($this->config)) {
      $s = $this->repo->readFile($this->collection->getDirectory('config').'/'.$this->url.'.json');
      if ($s === NULL) throw new Exception("project $this->url does not exist");
      $config = Json::decode($s);
      self::validateConfig($config);
      unset($config['project']['url']);
      $s = $this->repo->readFile($this->collection->getDirectory('lang').'/'.$this->url.'/lang_en.json');
      if ($s !== NULL) {
        $lang = Json::decode($s);
        $config = self::updateTranslations($config, $lang);
      }
      $this->config = $config;
    }
    $config = $this->config;
    $config['project']['url'] = $this->url;
    return $config;
  }

  public function getType() {
    if (isset($this->getConfig()['project']['type'])) {
      $type = $this->getConfig()['project']['type'];
    } else {
      $type = null;
    }
    unset($this->config);
    return $type;
  }

  public function getOwner() {
    if (isset($this->getConfig()['project']['owner'])) {
      $owner = $this->getConfig()['project']['owner'];
    } else {
      $owner = null;
    }
    unset($this->config);
    return $owner;
  }
    
  public function getTitle() {
    $title = $this->getConfig()['project']['title']['en'];
    unset($this->config);
    return $title;
  }

  // load default configuration with new title
  public function loadDefaultConfig($title,$user,$defaultFile) {
    $config = Json::decode(file_get_contents($defaultFile));
    $config['project']['title']['en'] = $title;
    $url = preg_replace(
      array('/[^a-z0-9]/', '/^-+/', '/-+$/'),
      array('-',           '',      ''),
      strtolower($title));
    $config['project']['url'] = $url;
    $config['project']['owner'] = $user;
    $this->setConfig($config);
  }

  public function setConfig($config) {
    // add missing chart.options
    if (is_array(@$config['charts'])) {
      foreach ($config['charts'] as &$chart) {
        if (!isset($chart['options'])) {
          $chart['options'] = array();
        }
      }
    }

    self::validateConfig($config);
    $this->setURL($config['project']['url']);
    unset($config['project']['url']);

    // remove deleted objects and unused charts
    $config['tabs'] = array_filter($config['tabs'], function($k) { return @$k['deleted'] !== TRUE; });
    $usedCharts = array_reduce($config['tabs'], function($a, $t) { return array_merge($a, $t['charts']); }, array());
    foreach ($config['charts'] as $chartID => &$chart) {
      if (!in_array($chartID, $usedCharts)) {
        $chart['deleted'] = TRUE;
      }
    }
    $config['charts'] = array_filter($config['charts'], function($k) { return @$k['deleted'] !== TRUE; });

    $this->config = $config;
  }

  public function save($new = FALSE) {
    // fetch before committing in case of errors
    $config = $this->getConfig();
    $lang = self::getTranslatableTexts($config);

    $configFile = $this->collection->getDirectory('config').'/'.$this->url.'.json';
    $langFile = $this->collection->getDirectory('lang').'/'.$this->url.'/lang_en.json';
    $head = $this->repo->getLastCommit();
    $version = $this->repo->getFileID($configFile, $head);
    if ($new && $version !== NULL) {
      throw new Exception("project '$this->url' already exists");
    }
    if (!$new && $version === NULL) {
      throw new Exception("project '$this->url' does not exist");
    }
    $commitID = $this->repo->commit($configFile, $head, Json::encode($config));
    $this->previousCommit = $commitID;
    $commitID = $this->repo->commit($langFile, $commitID, Json::encode($lang));
//    $commitID = $this->repo->commit($langFile, $commitID, "<?php\n\n\$langWizard = ".preg_replace('/ $/m', '', var_export($lang, TRUE)).";\n");
    return $commitID;
  }

  public function getPreviousCommit() {
    return $this->previousCommit;
  }
}
