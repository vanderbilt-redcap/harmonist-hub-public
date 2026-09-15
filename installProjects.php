<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

if(!defined('APP_PATH_WEBROOT_ALL')) {
    if (APP_PATH_WEBROOT[0] == '/') {
        $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
    }
    define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $APP_PATH_WEBROOT_ALL);
}

$hub_projectname = $module->getProjectSetting('hub-projectname');
$hub_profile = $module->getProjectSetting('hub-profile');
$pid = (int)$_GET['pid'];
?>
<!DOCTYPE html>
<html lang="en">
<div style="padding:12px 16px;" class="alert alert-danger">
    <strong>⚠️ This is the Harmonist Hub Public version for portability testing and not for final release.<br/>⚠️ Do not use this repo for a live consortium installation of the Hub. The final release will be under a different repo name.</strong>
</div>

<?php
if(($hub_projectname == '' || $hub_profile == '') || (array_key_exists('message',$_REQUEST) && $_REQUEST['message']=='D')){ ?>
<head>
    <?php include_once("head_scripts.php");?>
    <script>
        var startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
        var indexPage_url = <?=json_encode($module->getUrl('index.php', true))?>;
        var pid = <?=json_encode($pid)?>;
    </script>
</head>
<body>
<?php }
if($hub_projectname == '' || $hub_profile == '')
{
    echo '  <div class="container mt-5">  
                <div class="alert alert-danger row">
                    <div class="col-10">
                        To start the installation you need to fill up the fields in the <a href="'.$module->escape(APP_PATH_WEBROOT_FULL."external_modules/manager/project.php?pid=".$pid).'" target="_blank">External Modules configuration settings</a>.
                    </div>
                 </div>
            </div>';
} else {
#User rights
$isAdmin = false;
if(defined('USERID'))
{
    $UserRights = \REDCap::getUserRights(USERID)[USERID];
    if ($UserRights['user_rights'] == '1') {
        $isAdmin = true;
    }
}

$dd_array = \REDCap::getDataDictionary('array');
$data_array = \REDCap::getData($_GET['pid'], 'array');
if (count($dd_array) == 1 && $isAdmin && !array_key_exists('project_constant', $dd_array) && !array_key_exists('project_id', $dd_array) || count($data_array) == 0)
{
# Required companion external modules (prefix => info)
$requiredModules = [
        'vanderbilt_emailTrigger_v0.0.0' => [
                'name' => 'Email Alerts External Module',
                'url'  => 'https://github.com/vanderbilt-redcap/email-alerts-module',
                'desc' => 'Sends automated email notifications and alerts used by the Harmonist Hub workflows.',
        ],
        'data-model-browser_v0.0.0' => [
                'name' => 'Data Model Browser External Module',
                'url'  => 'https://github.com/vanderbilt-redcap/data-model-browser',
                'desc' => 'Provides the data model browsing capabilities required by the Harmonist Hub.',
        ],
        'get-pmid-details_v0.0.0' => [
                'name' => 'Get PMID External Module',
                'url'  => 'https://github.com/vanderbilt-redcap/get-pmid-details',
                'desc' => 'Retrieves publication details from PubMed (PMID/PMCID) for the publications features.',
        ],
];

# Determine which required modules are missing
$missingModules = [];
foreach ($requiredModules as $modulePrefix => $moduleInfo) {
    # isModuleEnabled() expects the prefix without the version suffix
    $prefixNoVersion = preg_replace('/_v[0-9]+\.[0-9]+\.[0-9]+$/', '', $modulePrefix);
    if (!$module->isModuleEnabled($prefixNoVersion)) {
        $missingModules[$modulePrefix] = $moduleInfo;
    }
}
?>
<head>
    <?php include_once("head_scripts.php");?>
    <script>
        let startDDProjects_url = <?=json_encode($module->getUrl('startDDProjects.php'))?>;
        let indexPage_url = <?=json_encode($module->getUrl('index.php', true))?>;
        let pid = <?=json_encode($pid)?>;
    </script>
</head>
<body>
<?php
# Show an informational message about the companion external modules.
# If any are missing, they must be enabled before the install button can be clicked.
$modulesListHtml = '';
foreach ($requiredModules as $modulePrefix => $moduleInfo) {
    $isMissing = array_key_exists($modulePrefix, $missingModules);
    $statusIcon = $isMissing
            ? '<i class="fa fa-times-circle text-danger" aria-hidden="true"></i> <span class="text-danger">Not installed</span>'
            : '<i class="fa fa-check-circle text-success" aria-hidden="true"></i> <span class="text-success">Installed</span>';
    $modulesListHtml .= '<li class="mb-2">'
            . '<a href="' . $module->escape($moduleInfo['url']) . '" target="_blank">' . $module->escape($moduleInfo['name']) . '</a> '
            . '&mdash; ' . $module->escape($moduleInfo['desc']) . '<br/>' . $statusIcon
            . '</li>';
}

if (count($missingModules) > 0) {
    echo '  <div class="container mt-5">
                            <div class="row alert alert-danger">
                                <strong>Required external modules are missing.</strong>
                                <br/>Before you can create the data dictionary and related projects, the following external modules must be enabled on this REDCap instance:
                                <ul class="mt-2 mb-0">' . $modulesListHtml . '</ul>
                            </div>
                        </div>';
} else {
    echo '  <div class="container mt-5">
                            <div class="row alert alert-info">
                                <strong>The Harmonist Hub relies on the following external modules:</strong>
                                <ul class="mt-2 mb-0">' . $modulesListHtml . '</ul>
                            </div>
                        </div>';
}

# Disable the install button if any required module is missing
$installDisabled = count($missingModules) > 0 ? ' disabled' : '';
$onClickAttr = count($missingModules) > 0
        ? ''
        : 'onclick="startDDProjects('."'".$module->getCSRFToken()."'".');$(\'#save_continue_4_spinner\').addClass(\'fa fa-spinner fa-spin\');"';

echo '  <div class="container mt-2">
                                    <div class="alert alert-warning row">
                                        <div class="col-10"><span class="float-start">
                                            The data dictionary for <strong>' . \REDCap::getProjectTitle() . '</strong> is empty.
                                            <br/>Click the button to create the data dictionary and all related projects.</span>
                                        </div>
                                        <div class="col-2"><button id="installbtn"' . $installDisabled . ' ' . $onClickAttr . ' class="btn btn-primary float-end"><span id="save_continue_4_spinner"></span> Create Projects & Data Dictionary</button></div>
                                    </div>
                                </div>';
}
}
?>
</body>
</html>