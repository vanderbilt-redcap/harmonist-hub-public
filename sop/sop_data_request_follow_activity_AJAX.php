<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

$userid = $_REQUEST['userid'];
$option = $_REQUEST['option'];
$record = $_REQUEST['record'];

$RecordSetRM = \REDCap::getData($pidsArray['SOP'], 'array', array('record_id' => $record));
$request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['SOP'])[0];
$follow_activity = $request['follow_activity'];
$array_userid = explode(',',$follow_activity);

$Proj = new \Project($pidsArray['SOP']);
$event_id_RM = $Proj->firstEventId;
$recordRM = array();
if($option == "0"){
    #UNFOLLOW
    if (($key = array_search($userid, $array_userid)) !== false) {
        unset($array_userid[$key]);
        $string_userid = implode(",",$array_userid);
        $recordRM[$record][$event_id_RM]["follow_activity"] = $string_userid;
    }
    $button = '<button onclick="follow_activity(\'1\',\'' . $userid . '\',\'' . $record . '\',\'' . $module->getUrl('sop/sop_data_request_follow_activity_AJAX.php', true) . '\',\'' . $module->getCSRFToken() . '\')" class="btn btn-secondary actionbutton"><i class="fa fa-solid fa-plus"></i> <span class="d-none d-sm-inline">Follow Activity</span></button>';
}else if($option == "1"){
    #FOLLOW
    array_push($array_userid,$userid);
    array_push($array_userid,$request['sop_hubuser']);
    array_push($array_userid,$request['sop_creator']);
    array_push($array_userid,$request['sop_creator2']);
    array_push($array_userid,$request['sop_datacontact']);
    $array_userid = array_filter(array_unique($array_userid));
    $string_userid = implode(",",$array_userid);
    $recordRM[$record][$event_id_RM]["follow_activity"] = $string_userid;

    $button = '<button onclick="follow_activity(\'0\',\'' . $userid . '\',\'' . $record . '\',\'' . $module->getUrl('sop/sop_data_request_follow_activity_AJAX.php', true) . '\',\'' . $module->getCSRFToken() . '\')" class="btn btn-primary actionbutton"><i class="fa fa-check-square"></i> <span class="d-none d-sm-inline">Following</span></button>';
}
$results = \Records::saveData($pidsArray['SOP'], 'array', $recordRM,'overwrite');
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

echo json_encode($button);
?>