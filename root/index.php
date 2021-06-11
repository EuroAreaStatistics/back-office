<?php
require_once __DIR__.'/../BaseURL.php';
require_once __DIR__.'/../02projects/'.$themeURL.'/liveProjects.php';

// return 404 error page
function error404() {
  header("HTTP/1.1 404 Not Found");
  echo "404 Not found";
  exit;
}

// use a function to avoid setting global variables
function __urlMapper() {
    // read $urls[] from config file
    global $urls, $projectsWizard, $projectsConstructionWizard ;

    if (isset($_SERVER['PATH_INFO'])) $prefix = $_SERVER['PATH_INFO'];
    else $prefix = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');

    // lookup current URL in $urls[]
    // and redirect browser to new url

    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// remove prefix
    if (substr($url, 0, strlen($prefix)) == $prefix) $url = substr($url, strlen($prefix));
// remove trailing slash
    if ($url != '/') $url = rtrim($url, '/');

//serve wizard files through API
    if (preg_match('#^/api/v1(/.*|$)#', $url, $matches)) {
      $file = realpath(__DIR__ . '/../apiServer/path.php');
      if (!file_exists($file)) return error404();
      $path = $matches[1];
      $_REQUEST['path'] = $path;
      chdir(dirname($file));
      return $file;
    } else {
      error404();
    }

}

require_once __urlMapper();
