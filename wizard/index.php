<?php
require_once __DIR__.'/../BaseURL.php';
require_once __DIR__.'/../02projects/'.$themeURL.'/urlMapperConfig.php';
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <title>CYC Wizard Home</title>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="<?= $baseURL ?>/wizard/css/index.css">
  </head>

  <body>
    <header>
        <div id='pageTitle'>Compare your country - Chart Wizard</div>
        <div class='navText'>Select the type of project you would like to edit or create</div>
    </header>
    <div class='select-wrapper'>
      <div class='select-item'>
        <h2>Standard CYC project</h2>
        <ul>
          <li>standard CYC collection of indicators/charts</li>
          <li>collection can be split into different tabs</li>
        </ul>
        <a href='<?= $baseURL ?>/wizard/lists.php'>Select</a>
      </div>
      <div class='select-item'>
        <h2>Simple Chart</h2>
        <ul>
          <li>simple, one indicator chart</li>
          <li>can be used on blogs, social media and OECD home page</li>
        </ul>
        <a href='<?= $baseURL ?>/wizard/lists.php?mode=simple'>Select</a>
      </div>
      <div class='select-item'>
        <h2>Complex Project</h2>
        <ul>
          <li>CYC collection of indicators/charts</li>
          <li>map layers present text summaries for individual countries</li>
        </ul>
        <a href='<?= $baseURL ?>/wizard/lists.php?mode=complex'>Select</a>
      </div>
      <div class='select-item'>
        <h2>Slide shows</h2>
        <ul>
          <li>Collection of short data stories</li>
        </ul>
        <a href='<?= $baseURL ?>/wizard/lists.php?mode=slide'>Select</a>
      </div>
    </div>
  </body>
</html>
