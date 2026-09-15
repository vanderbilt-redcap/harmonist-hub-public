<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
include_once(__DIR__ ."/../projects.php");
include_once(__DIR__ . "/../classes/HubREDCapUsers.php");

$userNameArray = array_map(function($item) {
    return htmlentities($item, ENT_QUOTES);
}, $_REQUEST['userNameArray']);

$userNameIdArray = array_map(function($item) {
    return htmlentities($item, ENT_QUOTES);
}, $_REQUEST['userNameIdArray']);

$errorNameList = [];
foreach ($userNameArray as $index => $userName) {
    $q = $module->query("select username from redcap_user_information where username = ?", [$userName]);
    if($q->num_rows == 0){
        $errorNameList[$userNameIdArray[$index]] = $userName;
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($errorNameList);
?>
