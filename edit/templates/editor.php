<!DOCTYPE html>
<html>
<head>
<title>CYC Editor</title>
<meta charset="utf-8">
<link rel="icon" type="image/gif" href="<?= htmlspecialchars($data['favicon']) ?>">
<link rel="stylesheet" type="text/css" href="editor.css">
<script src='<?= htmlspecialchars($data['vendorsURL']) ?>/tinymce-dist/tinymce.min.js'></script>
<script src='<?= htmlspecialchars($data['vendorsURL']) ?>/jquery/jquery.min.js'></script>
<script>
  var baseURL = <?= json_encode($baseURL) ?>;
</script>
<script type="text/javascript" src="editor.js"></script>
</head>

<body>
<header>

    <div id='pageTitle'>Compare your country - Translations</div>
    <div class='navText'>You are editing the <span class='red'><?= htmlspecialchars($data['language']) ?></span> language version of the '<span class='red'><?= htmlspecialchars($data['project']) ?></span>' project

    </div>

    <div id="controlArea">
        <div>
            <button id="homeButton" class="item">Back to project list</button>
        </div>
        <div>
            <?php if (!empty($data['previewURL'])): ?>
            <button id="previewButton" class="item" data-url="<?= htmlspecialchars($data['previewURL']) ?>">Preview saved changes</button>
            <?php endif ?>
        </div>
        <form id="submitForm" method="POST">
            <?php if (!empty($data['updateButton'])): ?>
              <input type="submit" name="saveUpdate" value="Approve translation" id="updateButton" class="item">
            <?php endif ?>
            <input type="submit" value="All changes saved" data-change="Save changes" id="submitButton" class="item">
        </form>
        <div class="item">
        <?php foreach ($data['status'] as $d1): ?> <?= htmlspecialchars($d1) ?> <?php endforeach ?>
        <?php foreach ($data['errors'] as $d1): ?> <?= htmlspecialchars($d1) ?> <?php endforeach ?>
        </div>
    </div>


</header>

<div id='table-wrapper'>
    <table>
        <tr>
            <td><div class="head">Data key</div></td>
            <td><div class="labels head">English reference <button data-ref="1" class="htmlButton item">download</button></div></td>
            <td><div class="labels head">Add/edit <span class='red'><?= htmlspecialchars($data['language']) ?></span> version here <button class="htmlButton item">download</button> <form method="post" enctype="multipart/form-data"><div class="htmlUpload" style="position:relative;overflow:hidden"><button class="item">upload</button><input type="file" name="upload" style="position:absolute;top:0;left:0;opacity:0"></div></form></div></td>
        </tr>

<?php
    foreach ($data['fields'] as $d1):
    if   ($project == 'pisa2015-test' && strpos ( $d1['keyName'] , '|label' ) == TRUE) continue;

?>
    <tr>
        <td><div class=""><?= htmlspecialchars($d1['keyName']) ?></div></td>
        <td>
             <div class="" id="ref/<?= htmlspecialchars($d1['id']) ?>"><?= $d1['reference'] ?></div>
             <?php if (isset($d1['oldReference'])): ?>
              <div class="oldReference">
                <div>OLD:</div>
                <div><?= $d1['oldReference'] ?></div>
             </div>
             <?php endif ?>
        </td>
        <td><div class="editable" lang="<?= htmlspecialchars($data['code']) ?>" id="<?= htmlspecialchars($d1['id']) ?>"><?= $d1['translation'] ?></div><p style="color: red; display: none;" id="long/<?= htmlspecialchars($d1['id']) ?>">Text may be too long!</p></td>
    </tr>
   <?php endforeach ?>

    </table>
</div>


</body></html>
