<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once(__DIR__ . "/classes/HubREDCapUsers.php");
require_once(__DIR__ . "/classes/ProjectInstaller.php");

if (!defined('APP_PATH_WEBROOT_ALL')) {
    $appPathWebRootAll = APP_PATH_WEBROOT;
    if ($appPathWebRootAll[0] === '/') {
        $appPathWebRootAll = substr($appPathWebRootAll, 1);
    }
    define('APP_PATH_WEBROOT_ALL', APP_PATH_WEBROOT_FULL . $appPathWebRootAll);
}

try {
    if (!isset($_REQUEST['pid']) || !is_numeric($_REQUEST['pid'])) {
        throw new \Exception('Invalid or missing project ID.');
    }
    $projectId = (int) $_REQUEST['pid'];

    // Set PID MAPPER
    $module->setProjectSetting('hub-mapper', $projectId);
    $module->setPIDMapperProject($projectId);

    //SET EM CONFIG DATA
    $cookieKeyCrypt = SecurityHandler::generateHexKey();
    $module->setProjectSetting('cookie-key', $cookieKeyCrypt, $projectId);

    // Initialize and run the Project Installer
    $installer = new ProjectInstaller($module, $projectId);
    $installer->installProjects();

    echo json_encode([
                         'status' => 'success',
                     ]);
} catch (\Exception $e) {
    // Log the error and return a failure response
    error_log("Error in HarmonistHubPublicExternalModule Installation Process: " . $e->getMessage());
    echo json_encode([
                         'status' => 'error',
                         'message' => $e->getMessage(),
                     ]);
}
?>
