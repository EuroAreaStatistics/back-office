<?php

/**
 * Serves config files to API client.
 *
*/

require_once (__DIR__.'/buildConfigFile.php');
require_once (__DIR__.'/PostProcessor.php');

$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';

define('ONE_OFF_DIR', __DIR__.'/../02projects/'.$themeURL.'/wizardProjects');
define('NOTES_DATA_DIR', __DIR__.'/../02projects/'.$themeURL.'/wizardProjects');
define('EMBED_DIR', __DIR__.'/../embedProjects/'.$themeURL);

serve($path,$projectsWizard,$projectsWizardApps,$projectsConstructionWizard,$urls);

function defineAccess ($access) {
  global $themeURL;
  if (isset($_ENV['CODE_PREVIEW']) && $access === $_ENV['CODE_PREVIEW']) {
    define('PROJECTS_DIR', __DIR__.'/../02projects/'.$themeURL.'/wizard-edit-repo/wizardProjects');
    define('ACCESS_RIGHT', 'all');
  } else {
    define('PROJECTS_DIR', __DIR__.'/../02projects/'.$themeURL.'/wizardProjects');
    define('ACCESS_RIGHT', 'restricted');
  };
}


function serve($path,$projectsWizard,$projectsWizardApps,$projectsConstructionWizard,$urls) {
  $result = NULL;
  if (!preg_match('#^[a-z0-9/_-]*$#i', $path)) {
    header("HTTP/1.1 404 Not Found");
  }
  $parts = explode('/', trim($path, '/'));
  $accessCode = array_shift($parts);
  $access = defineAccess($accessCode);

  if ($parts[0] != 'projects' && ACCESS_RIGHT == 'restricted' && !in_array($parts[1], $projectsWizard)) {
    header("HTTP/1.1 404 Not Found");
    exit;
  }

  $op = array_shift($parts);
  switch ($op) {
  case 'projects':
    $result = getProjects($projectsWizard,$projectsWizardApps,$projectsConstructionWizard,$urls);
    break;
  case 'project':
    $result = call_user_func_array('getProject', $parts);
    break;
  case 'note':
    $result = call_user_func_array('getNote', $parts);
    break;
  case 'data':
    $result = call_user_func_array('getData', $parts);
    break;
  case 'embed':
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $parts[] = json_decode(file_get_contents('php://input'), TRUE);
      $result = call_user_func_array('createEmbed', $parts);
    }
    break;
  }
  if ($result === NULL) {
    header("HTTP/1.1 404 Not Found");
  //} else if (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== FALSE) {
  //  echo "<!DOCTYPE html>\n<pre>";
  //  echo htmlspecialchars(json_encode($result,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  } else {
    header("Content-type: application/json");
    header("Access-Control-Allow-Origin: *");
    echo json_encode($result,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }
}


function getProjects($projectsWizard,$projectsWizardApps,$projectsConstructionWizard,$urls) {

  $projects = array();
  $projectLanguages = json_decode(file_get_contents(PROJECTS_DIR . '/projectLanguages.json'), TRUE);
  foreach ((array)@glob(PROJECTS_DIR . '/config/*.json') as $file) {
    $project = basename($file, '.json');
    if (!preg_match('/^[a-z0-9-]*$/', $project)) {
      continue;
    }
    if (ACCESS_RIGHT == 'snapshots' && !(substr($project,0,2) == 's-' || substr($project,0,3) == 'sl-')) {
      continue;
    }
    if (ACCESS_RIGHT == 'restricted' && !in_array($project, $projectsWizard)) {
      continue;
    }
    $projects[$project] = array(
      'languages' => isset($projectLanguages[$project]) ? $projectLanguages[$project] : array(),
    );
    $mtime = filemtime($file);
    foreach ((array)@glob(NOTES_DATA_DIR . "/notes/$project/*/*.json") as $note) {
      $mtime = max($mtime, filemtime($note));
    }
    foreach ((array)@glob(NOTES_DATA_DIR . "/data/$project/*.json") as $data) {
      $mtime = max($mtime, filemtime($data));
    }
    foreach ((array)@glob(PROJECTS_DIR . "/lang/$project/lang_*.*") as $data) {
      $mtime = max($mtime, filemtime($data));
    }
    $mtime = max($mtime, @filemtime(ONE_OFF_DIR . "/oneOffSettings/$project.php"));
    $projects[$project]['lastModified'] = $mtime;
  }
  foreach ($projectsWizardApps as $project => $path) {
    $projects[$project]['wizardApp'] = $path;
  }
  foreach ($projectsConstructionWizard as $project => $text) {
    $projects[$project]['underConstruction'] = $text;
  }
  $result = array('projects' => $projects);
  if (isset($urls)) {
    $result['urls'] = $urls;
  } else {
    $result['urls'] = array();
  }
  foreach ((array)@glob(EMBED_DIR.'/*.json') as $embed) {
    $settings = @json_decode(@file_get_contents($embed), TRUE);
    if (isset($settings['project'])) {
      $p = array(
        'wizardProject' => $settings['project'],
        'page' => $settings['page'],
        'template' => $settings['template'],
      );
      if (isset($settings['charts'])) {
        $p['charts'] = implode(' ', $settings['charts']);
      }
      $result['urls']['/e-' . basename($embed, '.json')] = '/03/wizard?'.http_build_query($p);
    }
  }
  return $result;
}


function getProject($project, $language) {
  if (!preg_match('/^[a-z0-9-]*$/', $project) || !preg_match('/^[a-z]{2}$/', $language)) {
    return NULL;
  }
  if (ACCESS_RIGHT == 'snapshots' && !(substr($project,0,2) == 's-' || substr($project,0,3) == 'sl-')) {
    return NULL;
  }
  $file = glob(PROJECTS_DIR . "/config/$project.json");
  if (!count($file)) {
    return NULL;
  }
  $file = $file[0];
  $json = file_get_contents($file);
  $config = buildConfigFile($json, $project, $language);
  $config = PostProcessor::process($config);
  return $config;
}


function getNote($project, $language, $country) {
  $file = NOTES_DATA_DIR . "/notes/$project/$language/$country.json";
  $default = NOTES_DATA_DIR . "/notes/$project/$language/default.json";
  if (file_exists($file)) {
    return json_decode(file_get_contents($file), TRUE);    
  } else if (file_exists($default)) {
    return json_decode(file_get_contents($default), TRUE);    
  } else {
    return null;
  }    
}


function getData($project, $indicator) {
  $file = NOTES_DATA_DIR . "/data/$project/$indicator.json";
  if (!file_exists($file)) {
    return NULL;
  }
  return json_decode(file_get_contents($file), TRUE);
}


function checkEmbedSettings($settings) {
  $allowed = array('project', 'page', 'lg', 'cr', 'charts', 'template');
  if (count(array_diff(array_keys($settings), $allowed)) > 0) {
    return "unknown project setting";
  }
  if (isset($settings['cr'])) {
    if (!is_array($settings['cr'])) {
      $settings['cr'] = array($settings['cr']);
    }
    foreach ($settings['cr'] as $country) {
      if (!preg_match('/^[A-Za-z0-9]*$/', $country)) {
        return "invalid country code";
      }
    }
  }
  $config = getProject($settings['project'], 'en');
  if ($config === NULL) {
    return "could not load project";
  }
  if (isset($settings['lg'])) {
    if (!($settings['lg'] === 'en' ||
          in_array($settings['lg'], $config['languages']))) {
      return "project not available in language";
    }
  }
  if (isset($settings['page'])) {
    if (!(is_int($settings['page']) &&
          $settings['page'] < count($config['project']['tabs']))) {
      return "project page unknown";
    }
  }
  $tabId = $config['project']['tabs'][$settings['page']];
  $tab = $config['tabs'][$tabId];
  if (isset($settings['template'])) {
    if (!($settings['template'] === (string)$tab['template'] ||
          (isset($tab['altTemplate']) && in_array($settings['template'], $tab['altTemplate'])))) {
      return "template not available";
    }
  }
  if (isset($settings['charts'])) {
    if (!(is_array($settings['charts']) &&
          count($settings['charts']) > 0 &&
          count(array_diff($settings['charts'], $tab['charts'])) == 0)) {
      return "chart not available";
    }
  }
  return TRUE;
}

function createEmbed($project, $settings) {
  global $baseURL;

  if (!(is_array($settings) && count($settings)>0)) {
    return array('error' => 'empty settings');
  }
  $settings['project'] = $project;
  $error = checkEmbedSettings($settings);
  if ($error !== TRUE) {
    return array('error' => $error);
  }
  if (!file_exists(EMBED_DIR.'/')) {
    mkdir(EMBED_DIR, 0777, TRUE);
  }
  $f = FALSE;
  for ($i=0; $i<5; $i++) {
    $url = rtrim(base64_encode(time()), '=');
    $f = @fopen(EMBED_DIR."/$url.json", 'x');
    if ($f !== FALSE) {
      break;
    }
    sleep(1);
  }
  if ($f === FALSE) {
    return array('error' => 'could not create unique URL');
  }
  // limit file size
  $maxBytes = 10*1024;
  $r = fwrite($f, json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $maxBytes);
  fclose($f);
  if ($r === FALSE) {
    unlink(EMBED_DIR."/$url.json");
    return array('error' => 'could not write embed file');
  }
  if ($r == $maxBytes) {
    unlink(EMBED_DIR."/$url.json");
    return array('error' => 'embed file too large');
  }
  return array('url' => '/e-' . $url);
}
