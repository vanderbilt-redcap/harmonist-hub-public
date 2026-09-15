<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubUpdates.php");

$option = $_REQUEST['option'];
$message = "";
if($option == "add"){
    $record = null;
    if(array_key_exists("DES", $pidsArray)){
        $record = REDCapManagement::getDESRecordId($pidsArray['PROJECTS'],$pidsArray['DES']);
    }else{
        $record = $module->framework->addAutoNumberedRecord($pidsArray['PROJECTS']);
    }

    $projectInstaller = new ProjectInstaller($module, $pidsArray['PROJECTS']);
    $desProjectId = $projectInstaller->installDataModelBrowser($record);

    foreach (['JSONCOPY' => $pidsArray['JSONCOPY'], 'FILELIBRARY' => $pidsArray['FILELIBRARY']] as $name => $projectId) {
        $projectInstaller->updateDESMapProject($desProjectId, $projectId, $name);
    }

    $message = "&message=D";
}else if($option == "dismiss"){
    $module->setProjectSetting('hub-updates-show-des-msg', "false");
}

header("location:".$module->getUrl('hub-updates/index.php').$message);
?>