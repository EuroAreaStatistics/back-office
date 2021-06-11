<?php
require_once(dirname(__FILE__).'/../BaseURL.php');

require_once(dirname(__FILE__).'/../02projects/'.$themeURL.'/urlMapperConfig.php');
require_once(dirname(__FILE__).'/EditPHP.php');
require_once(dirname(__FILE__).'/ProjectUpdates.php');
require_once(dirname(__FILE__).'/authorizeEdit.php');
require_once(dirname(__FILE__).'/editorFunctions.php');

$language = (isset($_REQUEST['lg']) && preg_match('/^[a-z]{2}$/',$_REQUEST['lg'])) ? $_REQUEST['lg'] : 'en';
$project = (isset($_REQUEST['project'])&& preg_match('/^[a-z0-9-]*$/',$_REQUEST['project'])) ? $_REQUEST['project'] : 'none';
$mode = (isset($_REQUEST['mode'])&& preg_match('/^[a-z]*$/',$_REQUEST['mode'])) ? $_REQUEST['mode'] : 'lang';
$country = (isset($_REQUEST['cr'])&& preg_match('/^[a-z]{3}$/',$_REQUEST['cr'])) ? $_REQUEST['cr'] : 'aus';

require_once(__DIR__.'/config/editLanguages.php');
require(__DIR__.'/../countryNames/getCountryNames.php');
$lang_countries         =  getCountryNames ('en',$themeURL);


// add missing languages from editLanguages.php
$LangName = $editLanguages[$themeURL];
asort($LangName);

function cleanEditorFields(&$text, $k) {
  global $ConfigEdit, $mode;
  $allowed = $ConfigEdit['allowedTags'][$mode];
  if (get_magic_quotes_gpc()) {
    $text = stripslashes($text);
  }
  // remove inline styles
  $text = preg_replace('/(<\w+)\s+style="[^"]*"/i', '$1', $text);
  $text = preg_replace('/(<\w+)\s+style=\'[^\']*\'/i', '$1', $text);
  // remove empty lines and paragraphs
  $text = preg_replace('/<p>\s*<\/p>/iu', '', $text);
  $text = preg_replace('/<br\s*\/>\s*<br\s*\/>/iu', '<br/>', $text);

  if (strpos($allowed, '<p>') === FALSE) {
    // separate paragraphs with line breaks
    $text = preg_replace('/<\/p>\s*<p>/i', '<br/>', $text);
  }
 // strip all tags except those mentioned in the second argument
  $text = strip_tags($text, $allowed);
}

function parseUpload($name) {
  $separator = '|';
  $v = array();
  if (isset($_FILES[$name]) && $_FILES[$name]['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES[$name]['tmp_name'])) {
    $doc = new DOMDocument();
    if ($doc->loadHTMLFile($_FILES[$name]['tmp_name'])) {
      foreach ($doc->getElementsByTagName('tr') as $row) {
        $key = $row->getElementsByTagName('th')->item(0)->nodeValue;
        $val = "";
        foreach ($row->getElementsByTagName('td')->item(0)->childNodes as $node) {
          $val .= $doc->saveHTML($node);
        }
        $d =& $v;
        foreach (explode($separator, $key) as $k) {
          $d =& $d[$k];
        }
        $d = $val;
      }
    }
  }
  if (!is_array($v) || !count($v)) {
    exit("Error parsing uploaded file");
  } else {
    return $v;
  }
}

if (!canEdit($mode,$language,$project)) {
    header("Location: 404.php"); die();
}

$data = array('status' => array(),
              'errors' => array());

$data['project'] = projectName($project);
$data['language'] = $LangName[$language];
$data['code'] = $language;
$data['country'] = $lang_countries[$country];
$data['previewURL'] = rtrim($previewURL, '/').'/';
$data['vendorsURL'] = $vendorsURL;
$data['favicon'] = "$staticURL/img/$themeURL/favicon.png";

$updated = new ProjectUpdates($themeURL, $ConfigEdit['languages']);
$upToDate = $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['v']) && isset($_POST['saveUpdate']);
$lastRef = NULL;

switch ($mode) {
  case "lang":
    $editPHP = new EditPHP();
    try {
      $repo = dirname(__FILE__) . "/../02projects/".$themeURL."/wizard-edit-repo";
      $refFile = "lang/projects/$project/lang/lang_en.json";
      $editFile = "lang/projects/$project/lang/lang_$language.json";
      if (!$upToDate) $lastRef = $updated->getLastUpdate($project, $language);
      $editPHP->init($repo, $editFile, $refFile, $lastRef);

      if ($_SERVER['REQUEST_METHOD']=='POST') {
      // save edits
        $var = isset($_FILES['upload']) ? parseUpload('upload') : $_POST['v'];
        if (!is_array($var)) {
          throw new Exception('Error during update');
        }
        array_walk_recursive($var, 'cleanEditorFields');
        $diffs = $editPHP->diff($var);
        $editPHP->save($var);
        $body = "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])\n";
        foreach ($diffs as $diff) {
          $body .=
            "\n" .
            "Key: $diff[keyName]\n" .
            ($language != "en" ? "Reference: $diff[reference]\n" : "") .
            "Old: $diff[srcTranslation]\n" .
            "New: $diff[dstTranslation]\n";
        }
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $headers  = implode("\r\n", array(
          'MIME-Version: 1.0',
          'Content-type: text/plain; charset=utf-8',
          'Content-Transfer-Encoding: base64',
        ));
        mail($ConfigEdit['email'], "New translation for $project ($language)", base64_encode($body), $headers);
        $data['status'][] = "";
        $updated->updateProject($project, $language, $diffs, $upToDate);
      }
      $editPHP->load();
    } catch (Exception $e) {
      error_log($e);
      $data['errors'][] = "ERROR: " . $e->getMessage();
    }
    $data['fields'] = $editPHP->buildTable('v', $language != "en");
    $data['langOptions'] = array(array('value' => 'start', 'text' => 'change language'));
    foreach ($LangName as $k => $v) {
        $data['langOptions'][] = array('value' => $k, 'text' => $v);      
    }
    $data['updateButton'] = $lastRef != NULL;
    include __DIR__.'/templates/editor.php';
    break;

  case "wizard":
    $editPHP = new EditPHP();
    try {
      $repo = dirname(__FILE__) . "/../02projects/".$themeURL."/wizard-edit-repo";
      $refFile = "wizardProjects/lang/$project/lang_en.json";
      $editFile = "wizardProjects/lang/$project/lang_$language.json";
      if (!$upToDate) $lastRef = $updated->getLastUpdate($project, $language);
      $editPHP->init($repo, $editFile, $refFile, $lastRef);

      if ($_SERVER['REQUEST_METHOD']=='POST') {
      // save edits
        $var = isset($_FILES['upload']) ? parseUpload('upload') : $_POST['v'];
        if (!is_array($var)) {
          throw new Exception('Error during update');
        }
        array_walk_recursive($var, 'cleanEditorFields');
        $diffs = $editPHP->diff($var);
        $editPHP->save($var);
        $body = "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])\n";
        foreach ($diffs as $diff) {
          $body .=
            "\n" .
            "Key: $diff[keyName]\n" .
            ($language != "en" ? "Reference: $diff[reference]\n" : "") .
            "Old: $diff[srcTranslation]\n" .
            "New: $diff[dstTranslation]\n";
        }
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $headers  = implode("\r\n", array(
          'MIME-Version: 1.0',
          'Content-type: text/plain; charset=utf-8',
          'Content-Transfer-Encoding: base64',
        ));
        mail($ConfigEdit['email'], "New translation for wizard $project ($language)", base64_encode($body), $headers);
        $updated->updateProject($project, $language, $diffs, $upToDate);
        $data['status'][] = "";
      }
      $editPHP->load();
    } catch (Exception $e) {
      error_log($e);
      $data['errors'][] = "ERROR: " . $e->getMessage();
    }
    $data['fields'] = $editPHP->buildTable('v');
    $data['langOptions'] = array(array('value' => 'start', 'text' => 'change language'));
    foreach ($LangName as $k => $v) {
        if ($k == 'en') continue;
        $data['langOptions'][] = array('value' => $k, 'text' => $v);
    }
    $data['updateButton'] = $lastRef != NULL;
    include __DIR__.'/templates/editor.php';
    break;


  case "langtheme":
    $data['project'] = "$themeURL language files";
    $editPHP = new EditPHP();
    try {
      $repo = dirname(__FILE__) . "/../02projects/".$themeURL."/wizard-edit-repo";
      $refFile = "lang/themes/langTheme_en.json";
      $editFile = "lang/themes/langTheme_$language.json";
      if (!$upToDate) $lastRef = $updated->getLastUpdate('_langtheme', $language);
      $editPHP->init($repo, $editFile, $refFile, $lastRef);

      if ($_SERVER['REQUEST_METHOD']=='POST') {
      // save edits
        $var = isset($_FILES['upload']) ? parseUpload('upload') : $_POST['v'];
        if (!is_array($var)) {
          throw new Exception('Error during update');
        }
        array_walk_recursive($var, 'cleanEditorFields');
        $diffs = $editPHP->diff($var);
        $editPHP->save($var);
        @mail($ConfigEdit['email'], "New translation for langtheme ($language)", "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])");
        $data['status'][] = "";
        $updated->updateProject('_langtheme', $language, $diffs, $upToDate);
      }
      $editPHP->load();
    } catch (Exception $e) {
      $data['errors'][] = "ERROR: " . $e->getMessage();
    }
    $data['fields'] = $editPHP->buildTable('v', $language != "en");
    $data['langOptions'] = array(array('value' => 'start', 'text' => 'change language'));
    foreach ($LangName as $k => $v) {
        $data['langOptions'][] = array('value' => $k, 'text' => $v);
    }
    $data['updateButton'] = $lastRef != NULL;
    include __DIR__.'/templates/editor.php';
    break;

  case "langmain":
    $data['project'] = "general language files";
    $editPHP = new EditPHP();
    try {
      $repo = dirname(__FILE__) . "/../02projects/".$themeURL."/wizard-edit-repo";
      $refFile = "lang/langMain/langmain_en.json";
      $editFile = "lang/langMain/langmain_$language.json";
      if (!$upToDate) $lastRef = $updated->getLastUpdate('_langmain', $language);
      $editPHP->init($repo, $editFile, $refFile, $lastRef);

      if ($_SERVER['REQUEST_METHOD']=='POST') {
      // save edits
        $var = isset($_FILES['upload']) ? parseUpload('upload') : $_POST['v'];
        if (!is_array($var)) {
          throw new Exception('Error during update');
        }
        array_walk_recursive($var, 'cleanEditorFields');
        $diffs = $editPHP->diff($var);
        $commit = $editPHP->save($var);
        @mail($ConfigEdit['email'], "New translation for langmain ($language)", "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])");
        $updated->updateProject('_langmain', $language, $diffs, $upToDate);
        $data['status'][] = "";
      }
      $editPHP->load();
    } catch (Exception $e) {
      $data['errors'][] = "ERROR: " . $e->getMessage();
    }
    $data['fields'] = $editPHP->buildTable('v', $language != "en");
    $data['langOptions'] = array(array('value' => 'start', 'text' => 'change language'));
    foreach ($LangName as $k => $v) {
        $data['langOptions'][] = array('value' => $k, 'text' => $v);      
    }
    $data['updateButton'] = $lastRef != NULL;
    include __DIR__.'/templates/editor.php';
    break;

  default:
    die("Unknown mode");
}

?>
