<?php

class EditPHP {

  private $repo, $editFile, $refLang, $oldRefLang = array(), $lang;
  private $separator = '|';

  private function walkTable($key, $refLang, $lang, $oldRefLang = array(), $skipEmpty = FALSE) {
    $a = array();
    $keys = array_keys($refLang);
    sort($keys);
    foreach ($keys as $k) {
      $ref = $refLang[$k];
      $curKey = array_merge($key, array($k));
      if (is_array($ref)) {
        $a = array_merge ($a, $this->walkTable($curKey, $ref, isset($lang[$k])?$lang[$k]:array(), $oldRefLang, $skipEmpty));
      } else if (!$skipEmpty || $ref !== "") {
        $text = isset($lang[$k])?$lang[$k]:"";
        $keyName = implode($this->separator, array_slice($curKey, 1));
        $data = array('id' => implode($this->separator, $curKey),
                      'keyName' => $keyName,
                      'reference' => $ref,
                      'translation' => $text);
        if (isset($oldRefLang[$keyName])) {
          if (preg_replace('/  +/', ' ', $oldRefLang[$keyName]) != preg_replace('/ +/', ' ', $ref)) $data['oldReference'] = $oldRefLang[$keyName];
        } else if (count($this->oldRefLang)) {
          $data['oldReference'] = '';
        }
        $a[] = $data;
      }
    }
    return $a;
  }

  public function buildTable($key, $skipEmpty = FALSE) {
    return $this->walkTable(array($key), $this->refLang, $this->lang, $this->oldRefLang, $skipEmpty);
  }

  public function init($repo, $editFile, $refFile, $oldRefLang = NULL) {
    $this->repo = $repo;
    $this->editFile = $editFile;

    // read reference language
    $this->refLang = $this->getVersion($refFile);

    // read previous reference language
    $this->oldRefLang = (array)$oldRefLang;
  }

  public function save($lang) {
  // save edits
    $text = json_encode($lang, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    $path = $this->repo . '/' . $this->editFile;
    @mkdir(dirname($path), 0777, TRUE);
    $tmp = fopen("$path.tmp", 'xb');
    if ($tmp === FALSE) {
      throw new Exception('could not create temporary file');
    }
    $len = fwrite($tmp, $text);
    if ($len === FALSE || $len !== strlen($text)) {
      throw new Exception('could not save file');
    }
    if (!fclose($tmp)) {
      throw new Exception('could not close temporary file');
    }
    if (!rename("$path.tmp", $path)) {
      throw new Exception('could not rename temporary file');
    }
  }

  public function diff($current) {
  // generate diff between current and previous version
    $previous = $this->getVersion($this->editFile);
    $src = $this->walkTable(array("src"), $this->refLang, $previous);
    $dst = $this->walkTable(array("dst"), $this->refLang, $current);
    $diff = array_filter(array_map(function ($s, $d) {
      if ($s['translation'] != $d['translation']) return array(
          'keyName' => $s['keyName'],
          'reference' => $s['reference'],
          'srcTranslation' => $s['translation'],
          'dstTranslation' => $d['translation'],
      );
    }, $src, $dst));
    usort($diff, function($a, $b) { return strcmp($a['keyName'], $b['keyName']); });
    return $diff;
  }

  public function getVersion($file) {
    $path = $this->repo . '/' . $file;
    return json_decode(file_get_contents($path), TRUE);
  }

  public function load() {
    // read current language
    $this->lang = $this->getVersion($this->editFile);
  }
}
