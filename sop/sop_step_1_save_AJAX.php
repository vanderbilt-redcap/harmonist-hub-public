<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

require_once dirname(dirname(__FILE__))."/projects.php";

// Sanitize and validate inputs
$sopConceptId = htmlspecialchars($_REQUEST['selectConcept'] ?? '', ENT_QUOTES, 'UTF-8');
$option = htmlspecialchars($_REQUEST['option'] ?? '', ENT_QUOTES, 'UTF-8');
$recordId = (int) htmlspecialchars($_REQUEST['id'] ?? '', ENT_QUOTES, 'UTF-8');
$saveOption = (int) htmlspecialchars($_REQUEST['save_option'] ?? '', ENT_QUOTES, 'UTF-8');
$templateOption = (int) htmlspecialchars($_REQUEST['template_option'] ?? '', ENT_QUOTES, 'UTF-8');
$sopHubuser = (int) htmlspecialchars($_REQUEST['sop_hubuser'] ?? '', ENT_QUOTES, 'UTF-8');

// Generate the current timestamp
$sop_created_dt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

$dataRequestBuilder = new DataRequestBuilder($pidsArray['PROJECTS'], $module);

// Fetch concept details from the HARMONIST project
$concept = $dataRequestBuilder->getConceptData((int)$pidsArray['HARMONIST'], (int)$sopConceptId);

if ($option === "1" && empty($saveOption)) {
    // Create a new Data Request
    [$recordId, $arraySOP] = $dataRequestBuilder->createNewDataRequest(
        ['sop_hubuser' => $sopHubuser, 'sop_concept_id' => $sopConceptId],
        $concept['sop_concept_id'],
        $concept['sop_concept_title'],
        $sop_created_dt
    );
    $saveOption = $recordId;

} elseif ($option === "2" && empty($saveOption) && !empty($templateOption)) {
    // Create a new Data Request from a Template
    [$recordId, $arraySOP] = $dataRequestBuilder->createDataRequestFromTemplate($templateOption, $sopConceptId, $concept['sop_concept_title'], $sop_created_dt, $sopHubuser);
    $saveOption = $recordId;
} else {
    // Load a Data Request Draft
    $draftResult = $dataRequestBuilder->loadDataRequestDraft($recordId, $sop_created_dt);

    if (!empty($draftResult)) {
        $concept = $draftResult[0];
        // The SOP array is keyed by $recordId, not by sequential index
        unset($draftResult[0]);
        $arraySOP = $draftResult;

        // Update concept if the user changed it
        if (!empty($sopConceptId)) {
            $concept = $dataRequestBuilder->getConceptData((int)$pidsArray['HARMONIST'], (int)$sopConceptId);
            $Proj = new \Project($pidsArray['SOP']);
            $eventId = $Proj->firstEventId;
            $arraySOP[$recordId][$eventId]['sop_concept_id'] = $sopConceptId;
            $arraySOP[$recordId][$eventId]['sop_name'] = "{$recordId}. Data Request for {$concept['sop_concept_id']}, {$concept['sop_concept_title']}";
        }
    }
}

if(!empty($arraySOP)) {
    // Save Data
    $params = [
        'project_id' => $pidsArray['SOP'],
        'dataFormat' => 'array',
        'data' => $arraySOP,
        'overwriteBehavior' => "overwrite",
        'dateFormat' => "YMD",
        'type' => "flat"
    ];
    $results = \REDCap::saveData($params);
    \Records::addRecordToRecordListCache($pidsArray['SOP'], $recordId, 1);
}

// Fetch saved data
$data = \REDCap::getData([
                             'project_id' => $pidsArray['SOP'],
                             'return_format' => 'json-array',
                             'records' => $recordId
                         ])[0] ?? [];

// Prepare additional data
$data['save_option'] = $saveOption;

$date = new \DateTime($data['sop_due_d'] ?? '');
$data['sop_due_d_preview'] = $date->format('d F Y');

$data['sop_concept_id'] = $concept['sop_concept_id'];
$data['sop_concept_title'] = $concept['sop_concept_title'];
$data['sop_data_transfer_request'] = "DATA TRANSFER REQUEST – " . ($concept['sop_concept_id'] ?? '');
$data['selectConcept'] = $concept['selectConcept'];

$data['record_id'] = $recordId;

// Load information for STEP 4
$data['dataformat_prefer_text'] = $dataRequestBuilder->getDataFormatText($data, (int)$pidsArray['SOP'], $module);

// Fetch People Information
// Fetch People Information
$dataRequestBuilder->preloadPersonInfo([(int)$data['sop_creator'], (int)$data['sop_creator2'], (int)$data['sop_datacontact']]);
[$data['sop_creator_name'], $data['sop_creator_email']] = $dataRequestBuilder->getPersonInfo((int)$data['sop_creator'], 'sop_creator');
[$data['sop_creator2_name'], $data['sop_creator2_email']] = $dataRequestBuilder->getPersonInfo((int)$data['sop_creator2'], 'sop_creator2');
[$data['sop_datacontact_name'], $data['sop_datacontact_email']] = $dataRequestBuilder->getPersonInfo((int)$data['sop_datacontact'], 'sop_datacontact');

if ($data['sop_hubuser'] != "") {
    $sop_hubuser_region = \REDCap::getData([
                                               'project_id' => $pidsArray['PEOPLE'],
                                               'return_format' => 'json-array',
                                               'records' => $data['sop_hubuser'],
                                               'fields' => ['person_region']
                                           ])[0] ?? '';
    $data['sopCreator_region'] = $sop_hubuser_region['person_region'];
}

// Load Discuss Data
if ($sopConceptId === "" && $option === "1" && $saveOption !== "") {
    $data['optradio'] = '3';
    $data['sop_discuss'] = $data['record_id'];
}

// Return JSON response
echo json_encode($module->escape($data));
?>

