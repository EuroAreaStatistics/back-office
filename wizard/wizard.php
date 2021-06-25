<?php

define('WIZARD_FORM', 1);

require_once __DIR__.'/../BaseURL.php';
require_once __DIR__.'/../02projects/'.$themeURL.'/urlMapperConfig.php';
require(mainFile('templateList.php','config'));
require(mainFile('wizardMode.php','config'));
require_once __DIR__.'/classes/WizardProjects.php';
require_once __DIR__.'/classes/Json.php';
require_once __DIR__.'/classes/FileRepository.php';
require_once __DIR__.'/classes/ProjectUpdates.php';

if (file_exists(mainFile('themes/'.$themeURL.'/templateList.php','config'))) {
  require_once(mainFile('themes/'.$themeURL.'/templateList.php','config'));
  $templateList = array_replace_recursive($templateList, $templateListTheme);
}

$mode = (isset($_REQUEST['mode'])&& preg_match('/^[a-z]*$/',$_REQUEST['mode'])) ? $_REQUEST['mode'] : 'standard';
$project = (isset($_GET['project'])&& preg_match('/^[a-z0-9-]*$/',$_GET['project'])) ? $_GET['project'] : NULL;
$country = (isset($_REQUEST['cr'])&& preg_match('/^[a-z]*$/',$_REQUEST['cr'])) ? $_REQUEST['cr'] : NULL;

$countyCodeMaps = array();
$countyCodeMaps[0] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries_en.json'),TRUE);
$countyCodeMaps[1] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries_iso2.json'),TRUE);
$countyCodeMaps[2] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/special.json'),TRUE);
$countyCodeMap = array('default' => array_merge($countyCodeMaps[0],$countyCodeMaps[1],$countyCodeMaps[2]));
$countyCodeMap["BWA"] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries/BWA.json'),TRUE);;
$countyCodeMap["GMB"] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries/GMB.json'),TRUE);;
$countyCodeMap["MWI"] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries/MWI.json'),TRUE);;
$countyCodeMap["NGA"] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries/NGA.json'),TRUE);;
$countyCodeMap["PISA"] = json_decode(file_get_contents(__DIR__.'/countryCodeMap/countries/PISA.json'),TRUE);;

define('EMBED_DIR', __DIR__.'/../embedProjects/'.$themeURL);

$language = (isset($_REQUEST['lg']) && preg_match('/^[a-z]{2}$/',$_REQUEST['lg'])) ? $_REQUEST['lg'] : 'en';
if ($language != 'en') {
  header("Location: $baseURL/edit/editmain.php?mode=wizard&lg=$language&project=".$config['project']['url']);
  exit;
}
$config = isset($_REQUEST['config']) ? Json::decode($_REQUEST['config']) : NULL;

$repo = new FileRepository(__DIR__.'/../02projects/'.$themeURL.'/wizard-edit-repo');
$projects = new WizardProjects($repo);

switch (@$_REQUEST['action']) {
case 'save':
  saveProject($config);
  header('Content-type: application/json');
  echo Json::encode(array('ok' => 'project saved'));
  exit;
default:
  if (isset($project) && $project != null)  {
    $config = loadProject($project);
    $config = setEmbeds($config);
  } else {
    header("Location: index.php");
    exit;
  }
}

// print exception as plain text or JSON error and exit
function onException($e) {
  header($_SERVER["SERVER_PROTOCOL"].' 500 Internal Server Error');
  if (preg_match('@^application/json([,;]|$)@', $_SERVER['HTTP_ACCEPT'])) {
    header('Content-type: application/json');
    echo Json::encode(array('error' => $e->getMessage()));
  } else {
    header('Content-type: text/plain');
    echo $e->getMessage(),"\n";
  }
  exit;
}


// check for embeds of a project and set flag 'embedded'
function setEmbeds($config) {
  foreach ((array)@glob(EMBED_DIR.'/*.json') as $embed) {
    $settings = @json_decode(@file_get_contents($embed), TRUE);
    if (isset($settings['project'])
         && $settings['project'] === $config['project']['url']) {
      foreach ((array)@$settings['charts'] as $id) {
        if (isset($config['charts'][$id])) {
          $config['charts'][$id]['embedded'] = TRUE;
        }
      }
    }
  }
  return $config;
}

// load project configuration
// or default configuration for new project
function loadProject($url) {
  global $ConfigEdit;
  global $projects;

  try {
    if (!isset($_SERVER['REMOTE_USER'])) {
      throw new Exception('authentication required');
    }
    $project = $projects->fetch($url);
    $config = $project->getConfig();
    if ($_SERVER['REMOTE_USER'] != $ConfigEdit['admin']
        && $_SERVER['REMOTE_USER'] != $config['project']['owner']) {
      throw new Exception('no permission to load project');
    }
    return $config;
  } catch (Exception $e) {
    onException($e);
  }
}

// save project configuration
function saveProject($config) {
  global $ConfigEdit;
  global $projects;
  global $themeURL;

  try {
    $oldConfig = loadProject($config['project']['url']);
    if (isset($oldConfig['project']['owner'])) {
      $config['project']['owner'] = $oldConfig['project']['owner'];
    } else {
      $config['project']['owner'] = $_SERVER['REMOTE_USER'];
    }
    $project = $projects->update($config);
    $updated = new ProjectUpdates($themeURL, $ConfigEdit['languages']);
    $updated->updateProject($project->getURL(), 'en', $project->getPreviousCommit(), FALSE);
    if (isset($ConfigEdit['email'])) {
      mail($ConfigEdit['email'], "Updated project '".$project->getURL()."'", "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])\n");
    }
  } catch (Exception $e) {
    onException($e);
  }
}



?>
<!DOCTYPE html>
<html lang="en">
 <head>
     <meta charset="utf-8">
     <meta http-equiv="X-UA-Compatible" content="IE=edge">
     <meta name="viewport" content="width=device-width, initial-scale=1">
     <title>CYC Wizard - main</title>
     <link rel="stylesheet" href="<?= $vendorsURL ?>/bootstrap/dist/css/bootstrap.min.css">
     <link rel="stylesheet" href="<?= $vendorsURL ?>/jquery-file-upload/css/jquery.fileupload.css">
     <link rel="stylesheet" href="css/colors_buttons.css">
     <link rel="stylesheet" href="css/custom.css">
     <script src='<?= $vendorsURL ?>/jquery-1.11.3/dist/jquery.min.js'></script>
     <script src='<?= $vendorsURL ?>/bootstrap/dist/js/bootstrap.min.js'></script>
     <script src='<?= $vendorsURL ?>/jquery-ui/jquery-ui.min.js'></script>
     <script src="<?= $vendorsURL ?>/jquery-file-upload/js/jquery.fileupload.js"></script>
     <script src='<?= $vendorsURL ?>/blueimp-load-image/js/load-image.js'></script>
     <script src="<?= $vendorsURL ?>/papa.parse/papaparse.min.js"></script>
     <script src="<?= $vendorsURL ?>/jQuery-ajaxTransport-XDomainRequest/jquery.xdomainrequest.min.js"></script>
     <script src="libs/sdmx.js"></script>

<?php if (WIZARD_FORM==1): ?>
     <script src="libs/wizard-standard.js"></script>
     <script src="libs/wizard-config.js"></script>
<?php endif ?>


     <script>

     var mode = <?= json_encode($mode) ?>;
     var project = <?= json_encode($project) ?>;
     var modetemplates = <?= json_encode($wizardMode[$mode]) ?>;

     $(function() {
          runWizard(<?= Json::encodeJS(array( 'countryCodeMap' => $countyCodeMap,
                                              'lang' => $language,
                                              'data' => $config,
                                              'baseURL' => $baseURL,
                                              'previewURL' => $previewURL,
                                              'mode' => $mode
                                              )) ?>);
       });

     </script>
 </head>
 <body>

    <div id="HeaderSection">
    </div>

    <header>

        <div id='pageTitle'><a href='/wizard'>Compare your country - Chart Wizard</a></div>
        <div class='controls'>
          <button type="button" class="btn btn-info saveProject" disabled="disabled">Save/update</button>
          <button type="button" class="btn btn-info preview">Preview</button>
          <button type="button" class="btn btn-info extract">Extract simple chart</button>
          <button type="button" class="btn btn-info previewSlider">Preview</button>
          <button type="button" class="btn btn-info previewSimple">Select template</button>
          <a id='backList' class="btn btn-info" href='lists.php?mode=<?=$mode?>'>Back to project list</a>
        </div>
        <div class='controlsTranslate'>
          <span>Add translation:</span>
          <button type="button" class="btn btn-info" value="de">DE</button>
          <button type="button" class="btn btn-info" value="es">ES</button>
          <button type="button" class="btn btn-info" value="fr">FR</button>
          <button type="button" class="btn btn-info" value="jp">JP</button>
        </div>

    </header>

<?php 
  if (WIZARD_FORM==1) require 'templates/wizard-form-standard.php';
?>

  </body>
</html>
