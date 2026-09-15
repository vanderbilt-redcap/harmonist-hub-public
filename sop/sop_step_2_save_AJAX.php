<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

require_once dirname(__DIR__) . "/projects.php";

// Validate and sanitize inputs
$checkedValues = $_REQUEST['checked_values'] ?? [];
if (!is_array($checkedValues)) {
    $checkedValues = [$checkedValues];
}

// sanitize each value
$checkedValues = array_values(array_filter(array_map(function ($v) {
    $v = is_string($v) ? trim($v) : '';
    $v = strip_tags($v);
    // optional: keep it conservative for IDs/tokens (adjust as needed)
    $v = preg_replace('/[^a-zA-Z0-9_-]/', '', $v);
    return $v;
}, $checkedValues), fn($v) => $v !== ''));

$record = htmlentities($_REQUEST['id'], ENT_QUOTES);

// Initialize the project and event
$Proj = new \Project($pidsArray['SOP']);
$eventId = $Proj->firstEventId;

// Prepare the data array for saving
$date = new \DateTime();
$sopUpdatedDt = $date->format('Y-m-d H:i:s');

$arraySOP = [
    $record => [
        $eventId => [
            'sop_tablefields' => implode(',',$checkedValues),
            'sop_updated_dt' => $sopUpdatedDt,
        ],
    ],
];

// Save the data using REDCap's Records API
$params = [
    'project_id' => $pidsArray['SOP'],
    'dataFormat' => 'array',
    'data' => $arraySOP,
    'overwriteBehavior' => "overwrite",
    'dateFormat' => "YMD",
    'type' => "flat"
];
$saveResults = \REDCap::saveData($params);

if (!$saveResults['errors']) {
    // Update the record cache
    \Records::addRecordToRecordListCache($pidsArray['SOP'], $record, 1);

    // Retrieve updated data from REDCap
    $RecordSetSOP = \REDCap::getData([
                                 'project_id' => $pidsArray['SOP'],
                                 'return_format' => 'array',
                                 'records' => $record
                             ]);
    $data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'])[0];

    // Return the data as JSON
    echo json_encode($module->escape($data));
} else {
    // Handle errors in saving data
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save data', 'details' => $saveResults['errors']]);
}
?>