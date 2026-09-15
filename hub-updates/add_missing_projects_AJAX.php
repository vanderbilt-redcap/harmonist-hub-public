<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

include_once(__DIR__ . "/../projects.php");

$option = $_REQUEST['option'];

try {
    if($option == "add") {
        $missingProjectsArray = ProjectData::checkIfMissingProjects($pidsArray);

        if (is_array($missingProjectsArray)) {
            $missingProjectsArray = array_map('htmlspecialchars', $missingProjectsArray); // Sanitize data
        }

        $projectId = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : null;

        if (!$projectId) {
            throw new \Exception("Project ID (pid) is missing or invalid.");
        }

        // Leave permissions up the the module so non admins can install the projects
        $module->disableUserBasedSettingPermissions();

        // Create ProjectInstaller and install projects
        $installer = new ProjectInstaller($module, $projectId);
        $installer->installProjects($missingProjectsArray);

        // Redirect with success message
        $message = "&message=M";
    }elseif ($option == "dismiss") {
        $module->setProjectSetting('hub-updates-show-missing-msg', "false");
    }
    header("location:" . $module->getUrl('hub-updates/index.php') . $message);
    exit;
}catch (\Exception $e) {
    $message = "&message=Error during project installation: " . urlencode($e->getMessage());
    header("Location: " . $module->getUrl('hub-updates/index.php') . $message);
    exit; // Ensure the script stops execution
}
?>
