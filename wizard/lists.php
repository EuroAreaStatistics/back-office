<?php

$mode = (isset($_REQUEST['mode'])&& preg_match('/^[a-z]*$/',$_REQUEST['mode'])) ? $_REQUEST['mode'] : 'standard';

require_once __DIR__.'/../BaseURL.php';
require_once __DIR__.'/../02projects/'.$themeURL.'/urlMapperConfig.php';
require_once __DIR__.'/classes/WizardProjects.php';
require_once __DIR__.'/classes/FileRepository.php';
require_once __DIR__.'/classes/ProjectUpdates.php';

if (isset($_SERVER['REMOTE_USER'])) {
  $user = $_SERVER['REMOTE_USER'];
} else {
  die('No user has been set.');
}


$repo = new FileRepository(__DIR__.'/../02projects/'.$themeURL.'/wizard-edit-repo');
$projects = new WizardProjects($repo);
$updated = new ProjectUpdates($themeURL, $ConfigEdit['languages']);

// create new project based on english title
function newProject($title,$user,$mode) {
  global $ConfigEdit;
  global $projects;

  try {
    $project = $projects->addDefault($title,$user,__DIR__.'/default_'.$mode.'.json');
    mail($ConfigEdit['email'], "New project '".$project->getURL()."'", "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])\n");
    return $project->getURL();
  } catch (Exception $e) {
    header($_SERVER["SERVER_PROTOCOL"].' 500 Internal Server Error');
    header('Content-type: text/plain');
    echo $e->getMessage(),"\n";
    exit;
  }
}

// adds language versiosn to live projects
function setLiveProjects($languages) {
  global $ConfigEdit;
  global $projects;

  try {
    $projects->setLanguages($languages);
    mail($ConfigEdit['email'], "Updated live projects", "Changed by $_SERVER[REMOTE_USER] ($_SERVER[REMOTE_ADDR])\n");
  } catch (Exception $e) {
    header($_SERVER["SERVER_PROTOCOL"].' 500 Internal Server Error');
    header('Content-type: text/plain');
    echo $e->getMessage(),"\n";
    exit;
  }
}

switch (@$_REQUEST['action']) {
case 'new':
  $url = newProject($_REQUEST['title'],$_SERVER['REMOTE_USER'],$mode);
  header("Location: $baseURL/wizard/wizard.php?project=$url");
  exit;
case 'newSimple':
  $url = newProject('s-'.uniqid(),$_SERVER['REMOTE_USER'],$mode);
  header("Location: $baseURL/wizard/wizard.php?project=$url&mode=simple");
  exit;
case 'newSlide':
  $url = newProject('sl-'.uniqid(),$_SERVER['REMOTE_USER'],$mode);
  header("Location: $baseURL/wizard/wizard.php?project=$url&mode=slide");
  exit;
case 'setLive':
  setLiveProjects(isset($_REQUEST['lang']) ? $_REQUEST['lang'] : array());
  header("Location: $_SERVER[PHP_SELF]");
  exit;
}

$data = array(
  'projects' => array(),
  'langs' => $ConfigEdit['languages'],
);
foreach ($projects->find() as $project) {
  $p = array(
    'langs' => $project->getLanguages(),
    'updated' => $updated->getDate($project->getURL(), 'Y-m-d'),
    'needsUpdate' => array(),
  );
  foreach ($data['langs'] as $l) {
    $p['needsUpdate'][$l] = $updated->isOutOfDate($project->getURL(), $l);
  }
  try {
    $p['title'] = $project->getTitle();
  } catch (Exception $e) {
    $p['error'] = $e->getMessage();
  }
  $p['type'] = $project->getType();
  $p['owner'] = $project->getOwner();

  if ($user == $ConfigEdit['admin'] || array_key_exists($user, $ConfigCenters) || $p['owner'] == $user) {
    $data['projects'][$project->getURL()] = $p;
  }


}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>CYC Wizard Home</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/index.css">
    <script src='<?= $vendorsURL ?>/jquery-1.11.3/dist/jquery.min.js'></script>
  </head>
    
  <body>

    <form method="POST" id="newForm">
        <input type="hidden" name="action" value="new">
    </form>
    <form method="POST" id="newFormSimple">
        <input type="hidden" name="action" value="newSimple">
    </form>
    <form method="POST" id="newFormSlide">
        <input type="hidden" name="action" value="newSlide">
    </form>
    <form method="POST" id="setLiveForm">
         <input type="hidden" name="action" value="setLive">
    </form>
  
    <header>
    
        <div id='pageTitle'><a href='/wizard'>Compare your country - Chart Wizard</a></div>
        <div class='navText'>Select the wizard project you would like to edit</div>
    
    </header>
     
    <div id='table-wrapper'>

      <div class='table-headline'>Your wizard projects</div>

            <table>
                <tr>
<?php if ($mode=='standard'||$mode=='complex') : ?>
                  <td colspan="<?= count($data['langs'])+2 ?>">New project:
                      <input type="text" name="title" placeholder="Type project name here" form="newForm" required autofocus>
                      <input id='newProject' type="submit" value="CREATE PROJECT" form="newForm">
                  </td>
<?php elseif ($mode=='simple') : ?>
                  <td colspan="<?= count($data['langs'])+2 ?>">
                    <input id='newProject' type="submit" value="CREATE NEW PROJECT" form="newFormSimple">
                  </td>
<?php elseif ($mode=='slide') : ?>
                  <td colspan="<?= count($data['langs'])+2 ?>">
                    <input id='newProject' type="submit" value="CREATE NEW PROJECT" form="newFormSlide">
                  </td>
<?php endif ?>
                </tr>
                <tr>
                    <td>click URL to edit data, basic settings<br>and English version</td>
<?php if ($mode!='simple' && $mode!='slide') :?>
                    <td colspan="<?= count($data['langs']) ?>">Click 'edit' to add/edit language version<br>Tick box when language is ready to be published and hit:<input type="submit" value="UPDATE" form="setLiveForm"></td>
<?php else :?>
                    <td colspan="<?= count($data['langs']) ?>">Click 'edit' to edit/add language version</td>
<?php endif ?>

                </tr>
            </table>
            <table>
              <thead>
                <tr>
                    <td>Project URL</td>
                    <td>Project Name</td>
                    <td>Owner</td>
<?php  for ($i=1; $i<count($data['langs']); $i++) : ?>
                    <td></td>
<?php endfor ?>
                    <!--<td colspan="<?= count($data['langs'])-1 ?>">Language versions</td>-->
                </tr>
              </thead>
              <tbody>
<?php
  foreach ($data['projects'] as $file => $project) :
  if ($mode=='standard' && (substr($file,0,2)=='s-' || substr($file,0,3)=='sl-' )) continue;
  elseif ($mode=='simple' && substr($file,0,2)!='s-') continue;
  elseif ($mode=='slide' && substr($file,0,3)!='sl-') continue;
?>
                <tr>
  <?php if ($mode=='standard') : ?>
                    <td><a href="wizard.php?project=<?= $file ?>"><?= $file ?></a></td>
  <?php elseif ($mode=='simple') : ?>
                    <td><a href="wizard.php?project=<?= $file ?>&mode=simple"><?= $file ?></a></td>
  <?php elseif ($mode=='slide') : ?>
                    <td><a href="wizard.php?project=<?= $file ?>&mode=slide"><?= $file ?></a></td>
  <?php elseif ($mode=='complex') : ?>
                    <td><a href="wizard.php?project=<?= $file ?>&mode=complex"><?= $file ?></a></td>
  <?php endif ?>

  <?php if (isset($project['error'])): ?>
                    <td class="error">Error: <?= htmlspecialchars($project['error']) ?></td>
  <?php else: ?>
                    <td><?= htmlspecialchars($project['title']) ?></td>
  <?php endif ?>
                    <td><?= htmlspecialchars($project['owner']) ?><input name="lang[<?= $file ?>][]" type="hidden" value="" form="setLiveForm"></td>
  <?php foreach ($data['langs'] as $l): if ($l=='en') continue; ?>
                    <td class="<?= $project['needsUpdate'][$l] ? 'needsUpdate' : ''?>">
                      <?= $l ?>
                      <a href='<?= $baseURL?>/edit/editmain.php?mode=wizard&amp;project=<?= $file ?>&amp;lg=<?= $l ?>'>edit</a>
    <?php if ($mode!='simple' && $mode!='slide') :?>
                      <input name="lang[<?= $file ?>][]" type="checkbox" value="<?= $l ?>" form="setLiveForm" <?= @in_array($l, $project['langs']) ? 'checked' : '' ?> />
    <?php endif ?>
                    </td>
  <?php endforeach ?>
<?php   if (isset($project['updated'])):   ?>
              <!--<td>English version updated (<?= $project['updated'] ?>)</td>-->
<?php   endif ?>
                </tr>                    
<?php endforeach ?>
              </tbody>
            </table>
        
    </div>
  </body>
</html>
