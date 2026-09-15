<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

require_once dirname(dirname(__FILE__))."/projects.php";

$record = htmlentities($_REQUEST['id'],ENT_QUOTES);
$Proj = new \Project($pidsArray['SOP']);
$event_id = $Proj->firstEventId;

$sop_inclusion = $module->escape(($_REQUEST['sop_inclusion'] == "") ? "<i>None</i>" : $_REQUEST['sop_inclusion']);
$sop_exclusion = $module->escape(($_REQUEST['sop_exclusion'] == "") ? "<i>None</i>" : $_REQUEST['sop_exclusion']);
$sop_notes = $module->escape(($_REQUEST['sop_notes'] == "") ? "<i>None</i>" : $_REQUEST['sop_notes']);
$dataformat_notes = ($_REQUEST['dataformat_notes'] == "") ? "<i>None</i>" : $_REQUEST['dataformat_notes'];
$dataformat_prefer = $_REQUEST['dataformat_prefer'] ?? [];

$downloaders = $_REQUEST['downloaders'] ?? [];
if (!is_array($downloaders)) {
    $downloaders = [$downloaders];
}
$downloaders = array_map('strval', $downloaders);

$dataRequestBuilder = new DataRequestBuilder($pidsArray['PROJECTS'], $module);

// Build save payload once (avoid repeated $arraySOP[$record][$event_id])
$payload = [
    'sop_downloaders'         => htmlspecialchars(implode(',', $downloaders), ENT_QUOTES, 'UTF-8'),
    'sop_downloaders_dummy___1' => isset($_REQUEST['sop_downloaders_dummy']) ? '1' : '0',
    'sop_inclusion'           => $sop_inclusion,
    'sop_exclusion'           => $sop_exclusion,
    'sop_notes'               => $sop_notes,
    'dataformat_notes'        => $dataformat_notes,
    'sop_due_d'               => htmlspecialchars($_REQUEST['sop_due_d'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_creator'             => htmlspecialchars($_REQUEST['sop_creator'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_creator_org'         => htmlspecialchars($_REQUEST['sop_creator_org'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_creator2'            => htmlspecialchars($_REQUEST['sop_creator2'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_creator2_org'        => htmlspecialchars($_REQUEST['sop_creator2_org'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_datacontact'         => htmlspecialchars($_REQUEST['sop_datacontact'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_datacontact_org'     => htmlspecialchars($_REQUEST['sop_datacontact_org'] ?? '', ENT_QUOTES, 'UTF-8'),
    'sop_updated_dt'          => (new \DateTime())->format('Y-m-d H:i:s')
];

$arraySOP = [
    $record => [
        $event_id => $payload
    ]
];

if (!is_array($dataformat_prefer)) {
    $dataformat_prefer = explode(',', (string)$dataformat_prefer);
}
$dataformat_prefer = array_values(array_unique(array_filter(array_map('trim', $dataformat_prefer), 'strlen')));

// Initialize dataformat_prefer checkboxes to 0, then set selected to 1
$dataformat_prefer_labels = $module->escape($module->getChoiceLabels('dataformat_prefer', $pidsArray['SOP']));
foreach ($dataformat_prefer_labels as $idx => $_label) {
    $arraySOP[$record][$event_id]['dataformat_prefer___' . $idx] = '0';
}

// Mark selected as 1
foreach ($dataformat_prefer as $idx) {
    $arraySOP[$record][$event_id]['dataformat_prefer___' . $idx] = '1';
}

// Save
$params = [
    'project_id' => $pidsArray['SOP'],
    'dataFormat' => 'array',
    'data' => $arraySOP,
    'overwriteBehavior' => "overwrite",
    'dateFormat' => "YMD",
    'type' => "flat"
];
$results = \REDCap::saveData($params);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record, 1);

// Reload + build response
$RecordSetSOP = \REDCap::getData([
                                     'project_id' => $pidsArray['SOP'],
                                     'return_format' => 'array',
                                     'records' => $record
                                 ]);
$data = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'])[0];

$data['sop_version_date']  = "Data Request Version: " . date('d F Y');
$data['sop_inclusion']     = filter_tags($sop_inclusion);
$data['sop_exclusion']     = filter_tags($sop_exclusion);
$data['sop_notes']         = filter_tags($sop_notes);
$data['dataformat_notes']  = filter_tags($dataformat_notes);
$data['sop_tablefields']  = $data['sop_tablefields'];

// Due date preview formatting (guard against invalid dates)
$dueRaw = $_REQUEST['sop_due_d'] ?? '';
try {
    $data['sop_due_d_preview'] = "Data Due: ".(new \DateTime((string)$dueRaw))->format('d F Y');
} catch (\Exception $e) {
    $data['sop_due_d_preview'] = '';
}

// Fetch concept details from the HARMONIST project
$concept = $dataRequestBuilder->getConceptData((int)$pidsArray['HARMONIST'], (int)$data['sop_concept_id']);
$data['sop_concept_id']    = $concept['sop_concept_id'] ?? '';
$data['sop_concept_title'] = $concept['sop_concept_title'] ?? '';
$data['sop_data_transfer_request'] = "DATA TRANSFER REQUEST – ".($concept['sop_concept_id'] ?? '');

// Concept lookup
if (!empty($data['sop_concept_id'])) {
    $RecordSetConcepts = \REDCap::getData([
                                              'project_id' => $pidsArray['HARMONIST'],
                                              'return_format' => 'array',
                                              'records' => $data['sop_concept_id']
                                          ]);
    $concept = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts, $pidsArray['HARMONIST'])[0] ?? [];
    $data['sop_concept_title'] = $concept['concept_title'] ?? '';
}

// Fetch People Information
$dataRequestBuilder->preloadPersonInfo([(int)$data['sop_creator'], (int)$data['sop_creator2'], (int)$data['sop_datacontact']]);
[$data['sop_creator_name'], $data['sop_creator_email']] = $dataRequestBuilder->getPersonInfo((int)$data['sop_creator'], 'sop_creator');
[$data['sop_creator2_name'], $data['sop_creator2_email']] = $dataRequestBuilder->getPersonInfo((int)$data['sop_creator2'], 'sop_creator2');
[$data['sop_datacontact_name'], $data['sop_datacontact_email']] = $dataRequestBuilder->getPersonInfo((int)$data['sop_datacontact'], 'sop_datacontact');

$data['dataformat_prefer_text'] = $dataRequestBuilder->getDataFormatText($data, (int)$pidsArray['SOP'], $module);

// Output JSON
echo json_encode($module->escape($data));
?>

