<?php


require_once(dirname(__FILE__).'/../BaseURL.php');
require_once(dirname(__FILE__).'/../02projects/'.$themeURL.'/urlMapperConfig.php');
require_once(dirname(__FILE__).'/ProjectUpdates.php');
require_once(dirname(__FILE__).'/authorizeEdit.php');
require_once(dirname(__FILE__).'/editorFunctions.php');

$updated = new ProjectUpdates($themeURL, $ConfigEdit['languages']);

$data = array();

if (canEdit('langtheme',NULL)) {
  $data['langtheme'] = array(
    'name' => "$themeURL language files",
    'langs' => array(),
    'updated' => $updated->getDate('_langtheme', 'Y-m-d'),
  );
  foreach ($ConfigEdit['languages'] as $language) {
    if (!canEdit('langtheme',$language)) continue;
    $data['langtheme']['langs'][] = array(
      'code' => $language,
      'live' => in_array($language, $shareLanguagesProjects['langtheme']),
      'needsUpdate' => $updated->isOutOfDate('_langtheme', $language),
    );
  }
}

if (canEdit('langmain',NULL)) {
  $data['langmain'] = array(
    'name' => "general language files",
    'langs' => array(),
    'updated' => $updated->getDate('_langmain', 'Y-m-d'),
  );
  foreach ($ConfigEdit['languages'] as $language) {
    if (!canEdit('langmain',$language)) continue;
    $data['langmain']['langs'][] = array(
      'code' => $language,
      'live' => in_array($language, $shareLanguagesProjects['langtheme']),
      'needsUpdate' => $updated->isOutOfDate('_langmain', $language),
    );
  }
}

$data['lang'] = array();
foreach ($editProjects as $project) {
  if (!canEdit('lang', NULL, $project)) continue;
  $dataLang = array(
    'url' => $project,
    'name' => projectName($project),
    'langs' => array(),
    'updated' => $updated->getDate($project, 'Y-m-d'),
  );
  if (in_array($project, $projectsWizard)) {
    $dataLang['mode'] = 'wizard';
  }
  foreach ($ConfigEdit['languages'] as $language) {
    if (!canEdit('lang',$language, $project)) continue;
    $dataLang['langs'][] = array(
      'code' => $language,
      'live' => in_array($language, $shareLanguagesProjects[$project]),
      'needsUpdate' => $updated->isOutOfDate($project, $language),
    );
  }
  $data['lang'][] = $dataLang;
}
if (!count($data['lang'])) unset($data['lang']);

?>
<!DOCTYPE html>
<html>
  <head>
    <title>CYC Editor Home</title>
    <meta charset="utf-8">
    <link rel="icon" type="image/gif" href="<?= $staticURL ?>/img/<?= $themeURL ?>/favicon.png">
    <link rel="stylesheet" type="text/css" href="editor.css">
    <script src='<?= $vendorsURL ?>/jquery/jquery.min.js'></script>
  </head>
    
  <body>
  
    <header>
    
        <div id='pageTitle'>Compare your country - Translations</div>
        <div class='navText'>Select the project and the language version you would like to edit</div>
    
    </header>
 

    
    <div id='table-wrapper'>

<?php  if (isset($data['langtheme']) || isset($data['langmain'])):   ?>

      <div class='table-headline'>Central language files</div>

  
      <table>
  <?php foreach (array('langtheme', 'langmain') as $mode): ?>
    <?php if (isset($data[$mode])): ?>
          <tr>
              <td><?= $data[$mode]['name'] ?></td>
      <?php foreach ($data[$mode]['langs'] as $language): ?>
              <td class="<?= $language['live'] ? 'live' : '' ?> <?= $language['needsUpdate'] ? 'needsUpdate' : ''?>"><a href='editmain.php?mode=<?= $mode ?>&amp;lg=<?= $language['code'] ?>'><?= $language['code'] ?></a></td>
      <?php endforeach ?>
<?php   if (isset($data[$mode]['updated'])):   ?>
              <td>English version updated (<?= $data[$mode]['updated'] ?>)</td>
<?php   endif ?>

          </tr>
    <?php endif ?>
  <?php endforeach ?>
      </table>
    
<?php  endif  ?>
    
<?php  if (isset($data['lang'])):   ?>
      <div class='table-headline'>Projects and language versions with editable labels and indicator definitions</div>

  
      <table>
          <thead>
            <tr>
              <td>Project URL</td>
              <td>Project Name</td>
              
            </tr>
          </thead>
<?php foreach ($data['lang'] as $project) : ?>
          <tr>
              <td><?= $project['url'] ?></td>
              <td><?= $project['name'] ?></td>
  <?php foreach ($project['langs'] as $language) : ?>
              <td class="<?= $language['live'] ? 'live' : '' ?> <?= $language['needsUpdate'] ? 'needsUpdate' : ''?>"><a href='editmain.php?project=<?= $project['url'] ?>&amp;lg=<?= $language['code'] ?><?= isset($project['mode']) ? '&amp;mode='.$project['mode'] : '' ?>'><?= $language['code'] ?></a></td>
  <?php endforeach ?>        
<?php   if (isset($project['updated'])):   ?>
              <td>English version updated (<?= $project['updated'] ?>)</td>
<?php   endif ?>

          </tr>
<?php endforeach ?>        
      </table>
          
<?php  endif  ?>

    </div>
  
  
  </body>
</html>
