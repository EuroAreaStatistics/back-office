<?php

// return TRUE if the given user can edit all modes/projects in
// either a single langauge or an array of languages
function canEditLanguage($user, $language) {
    global $ConfigCenters, $ConfigEdit;
    
    if ($user == $ConfigEdit['admin']) return TRUE;
    $center = array_key_exists($user, $ConfigCenters);
    if (!$center) return FALSE;
    if (is_array($language)) {
        if (count(array_intersect($language, $ConfigCenters[$user]))) return TRUE;
    } else {
        if (in_array($language, $ConfigCenters[$user])) return TRUE;        
    }
    return FALSE;
}

// return TRUE if the current user can edit a mode/project in
// either a specified langauge or (if $language==NULL) in some language
// [ $project is ignored, if $mode == 'langtheme' ]
function canEdit($mode, $language, $project = NULL) {
    global $ConfigEdit, $editProjects;
    
    if ($project === 'wizard-preview') return FALSE;
    $user = $_SERVER['REMOTE_USER'];
    switch($mode) {
        case 'wizard':
            //if ($language == NULL) $language = $ConfigEdit['languages'];
            //else if (!in_array($language, $ConfigEdit['languages'])) return FALSE;
            //if (isset($ConfigEdit['projects'][$user]) && in_array($project, $ConfigEdit['projects'][$user])) return TRUE;
            //return canEditLanguage($user, $language);
            return TRUE;
        case 'langmain':
        case 'langtheme':
            if ($language == NULL) $language = $ConfigEdit['languages'];
            else if (!in_array($language, $ConfigEdit['languages'])) return FALSE;
            return canEditLanguage($user, $language);
        case 'lang':
            if (!in_array($project, $editProjects)) return FALSE;
            if ($language == NULL) $language = $ConfigEdit['languages'];
            else if (!in_array($language, $ConfigEdit['languages'])) return FALSE;
            if (isset($ConfigEdit['projects'][$user]) && in_array($project, $ConfigEdit['projects'][$user])) return TRUE;
            return canEditLanguage($user, $language);
    }
    return FALSE;
}
