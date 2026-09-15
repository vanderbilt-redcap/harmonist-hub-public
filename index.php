<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

include_once("projects.php");

if (APP_PATH_WEBROOT[0] == '/') {
    $APP_PATH_WEBROOT_ALL = substr(APP_PATH_WEBROOT, 1);
}
if(!defined('APP_PATH_WEBROOT_ALL')) {
    define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $APP_PATH_WEBROOT_ALL);
}

// Retrieve project settings
$hubProjectName = $module->getProjectSetting('hub-projectname');
$hubProfile = $module->getProjectSetting('hub-profile') ?? "all";

// Retrieve project id and route
$pid = $module->getSecurityHandler()->getProjectId();
$option = $module->getSecurityHandler()->getRequestOption();

// Register global variables in Twig: constants and pid
$module->getTwig()->addGlobal('pid', $pid);

// Check user authorization and rights
$isAuthorizedAndHasRights = $module->checkAuthorization($module);

// Check if the required config variables are set
if (!$isAuthorizedAndHasRights && (empty($hubProjectName) || empty($hubProfile))) {
    return;
}

// Handle installation success message
if (!empty($_REQUEST['message']) && $_REQUEST['message'] === 'DD') {
    echo $module->getTwig()->render('base_success_message.html.twig', [
        'pid' => $pid,
        'template_message' => 'index_intallation_success_message.html.twig'
    ]);
    //Exit after the message as we don't need anything else
    return;
}

// Fetch data dictionary and project data
$ddArray = \REDCap::getDataDictionary('array');
$dataArray = \REDCap::getData($_GET['pid'], 'array');

// If The MAP Data Dictionary does not have any values, don't show the Hub
if (
    count($ddArray) === 1 &&
    HubREDCapUsers::checkUserAdminRights() &&
    !array_key_exists('project_constant', $ddArray) &&
    !array_key_exists('project_id', $ddArray) &&
    count($dataArray) === 0
) {
    return;
}

// Retrieve settings and pids
$pidsArray = $module->getSecurityHandler()->getPidsArray();
$settings = $module->getSecurityHandler()->getSettingsData();

// Register global variables in Twig: settings and pidsArray
$module->getTwig()->addGlobal('settings', $settings);
$module->getTwig()->addGlobal('pidsArray', $pidsArray);

// Retrieve favicon and security token
$favicon = "";
if(!empty($settings['hub_logo_favicon'])){
    $favicon = getFile($module, $settings['hub_logo_favicon'], 'favicon', $secret_key, $secret_iv);
}

$token = $module->getSecurityHandler()->getTokenSession();
\Safe\error_log(\Safe\json_encode($token));
// Initialize Routes class
$routes = new Routes(
    $module, $pid, $defaultValues, $token, $settings, $option
);

// Prepare data for rendering
$templateToRender = $routes->getTemplate();
$headerData = $routes->getHeaderData();
$hubData = $routes->getHubData();
$isAdmin = $routes->getIsAdmin();
$pageData = $routes->preparePageData();
$requestAdmin = $headerData['request_admin'];

//Register Admin as Twig global after Header data is set
$module->getTwig()->addGlobal('isAdmin', $routes->getisAdmin());

// Define success messages
$successMessage = "";
if (isset($_REQUEST['message'])) {
    $successMessages = [
        'U' => 'The announcement has been successfully updated.',
        'E' => 'Deadlines and Events have been successfully updated.',
    ];
    $successMessage = htmlspecialchars($successMessages[$_REQUEST['message']] ?? '');
}


// Footer Data
$versionsByPrefix = $module->getEnabledModules($_GET['pid']);
$aboutTitle = arrayKeyExistsReturnValue(\REDCap::getData($pidsArray['ABOUT'], 'json-array', null), [0,'about_title']);

// Render the template
if (pathinfo($templateToRender, PATHINFO_EXTENSION) === 'twig') {
    // If the template is a Twig template, render it using Twig
    echo $module->getTwig()->render('index.html.twig', array_merge($headerData, $pageData, [
        'template_to_render' => $templateToRender,
        'error_message' => $routes->getErrorMessage(),
        'success_message' => $successMessage,
        'has_valid_token' => $routes->hasValidToken(),
        'is_authorized_and_has_rights' => $isAuthorizedAndHasRights,
        'routes' => $routes,
        'favicon' => $favicon,
        'versions_by_prefix' => $versionsByPrefix,
        'about_title' => $aboutTitle
    ]));
} else {
    // If the template is not a Twig template, include it as a PHP file
    $templatePath = __DIR__ . '/' . $templateToRender;
    if (file_exists($templatePath)) {
        echo '<!DOCTYPE html>
            <html lang="en">';

        //Add html and header scripts
        echo $module->getTwig()->render('html_head.html.twig', [
            'favicon' => $favicon
        ]);

        // Add header if requred
        if($routes->isHeaderRequired()){
            $indexUrl = $headerData['index_url'];
            $currentUser = $hubData->getCurrentUser();
            echo $module->getTwig()->render('nav.html.twig', $headerData);
        }

        //Add PHP Templates
        echo '<div class="container-lg mx-auto" style="min-height: 900px;"><body>';
        include($templatePath);
        echo '</div></body></html>';

        if($routes->isHeaderRequired()){
            echo $module->getTwig()->render('footer.html.twig', array_merge($headerData, [
                'versions_by_prefix' => $versionsByPrefix,
                'about_title' => $aboutTitle
            ]
            ));
        }
    } else {
        // Handle the case where the file does not exist
        echo "Error: Template file '$templateToRender' not found.";
    }
}
?>
