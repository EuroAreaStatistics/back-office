<?php

// add manually for each server
require_once ('mainConfig.php');

$domain = $_SERVER["SERVER_NAME"];

//set resources path from $liveURL
$staticURL = $liveURL.'/02resources';
$vendorsURL = $liveURL.'/resources';

//set special API URLs

if (!function_exists('dataURL')) {
    /**
     * Builds URL for a project data file
     *
     * @param string $name Name of data file, relative to data folder
     * @param string $project Optional name of project, defaults to $GLOBALS['project']
     * @return string
    */
    function dataURL($name, $project = NULL, $path = NULL) {
        if ($project === NULL) {
             if (!isset($GLOBALS['project'])) throw new Exception('$project not set');
             $project = $GLOBALS['project'];
        }

        if ($path === 'json') {
            return sprintf('%s/projects/%s/dataJSON/%s/%s', $GLOBALS['baseURL'], $GLOBALS['themeURL'], $project, $name);
        } else {
            return sprintf('%s/projects/%s/projectConfig/%s/data/%s', $GLOBALS['baseURL'], $GLOBALS['themeURL'], $project, $name);
        }
    }
}

if (!function_exists('dataFile')) {
    /**
     * Builds absolute file name for a project data file
     *
     * @param string $name Name of data file, relative to data folder
     * @param string $project Optional name of project, defaults to $GLOBALS['project']
     * @return string
    */
    function dataFile($name, $project = NULL, $path = NULL) {
        if ($project === NULL) {
             if (!isset($GLOBALS['project'])) throw new Exception('$project not set');
             $project = $GLOBALS['project'];
        }

        if ($path === 'json') {
            return sprintf('%s/02projects/%s/dataJSON/%s/%s', dirname(__FILE__), $GLOBALS['themeURL'], $project, $name);
        } if ($path === 'langProject') {
            return sprintf('%s/02projects/%s/lang/projects/%s/lang/%s', dirname(__FILE__), $GLOBALS['themeURL'], $project, $name);
        } if ($path === 'centroides') {
            return sprintf('%s/02/langmain/%s', dirname(__FILE__), $name);
        } if ($path === 'main') {
            return sprintf('%s/02/%s/%s', dirname(__FILE__), $project, $name);
        } else {
            return sprintf('%s/02projects/%s/projectConfig/%s/data/%s', dirname(__FILE__), $GLOBALS['themeURL'], $project, $name);
        }
    }
}

if (!function_exists('mainFile')) {
    /**
     * Builds absolute file name for a project data file
     *
     * @param string $name Name of data file, relative to data folder
     * @param string $project Optional name of project, defaults to $GLOBALS['project']
     * @return string
    */

    function mainFile($name, $type) {

        $path = array (
            'generalTerms'      => '02/langmain/langMain',
            'landingTerms'      => '02projects/'.$GLOBALS['themeURL'].'/all/langIndex',
            'themeTerms'        => '02projects/'.$GLOBALS['themeURL'].'/lang/themes',
            'groups'            => '02projects/'.$GLOBALS['themeURL'].'/all',
            'themeTemplates'    => '02projects/'.$GLOBALS['themeURL'].'/all',
            'csvCentroides'     => '02/langmain',
            'config'            => 'config',
        );          

        return sprintf('%s/%s/%s', dirname(__FILE__), $path[$type], $name);

    }
    
}


