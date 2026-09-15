<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once dirname(dirname(__FILE__))."/projects.php";

$concepts = $module->getConceptModel()->fetchAllConcepts();

$publicationsData = [];
$conceptsData = [];
$abstractsData = [];
$maxPublications = 0;
$maxAbstracts = 0;

if(!empty($concepts)){

    $allContacts = \REDCap::getData([
                                      'project_id' => $pidsArray['PEOPLE'],
                                      'return_format' => 'json-array',
                                      'fields' => ['record_id', 'firstname', 'lastname']
                                  ]);
    $groupData = \REDCap::getData([
                                        'project_id' => $pidsArray['GROUP'],
                                        'return_format' => 'json-array',
                                        'fields' => ['record_id', 'group_name']
                                    ]);
    $projectStatus = $module->getChoiceLabels('project_status', $pidsArray['HARMONIST']);
    $adminStatus = $module->getChoiceLabels('admin_status', $pidsArray['HARMONIST']);

    foreach ($concepts as $concept){
        //Prepare Working Group
        $groupName = [];

        for ($i = 1; $i < 5; $i++) {
            $wgLink = $concept->getWgLinkByIndex($i);
            if (!empty($wgLink)) {
                foreach ($groupData as $group) {
                    if($group['record_id'] == $wgLink && !empty($group['group_name'])){
                        $groupName[] = htmlspecialchars($group['group_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            }
        }

        //Prepare Approval Date
        $ecApproval = !empty($concept->getEcApprovalD())
            ? date("F d, Y", strtotime($concept->getEcApprovalD()))
            : "";

        //Project Lead
        $projectLead = "";
        if (!empty($concept->getContactLink()) || !empty($concept->getContact2Link())) {
            foreach ($allContacts as $index => $person) {
                if (!empty($concept->getContactLink())){
                    if($person['record_id'] == $concept->getContactLink()){
                        $leadName = $person['firstname'] . " " . $person['lastname'];
                        $projectLead .= htmlspecialchars($leadName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ", ";
                    }
                }
                if (!empty($concept->getContact2Link())){
                    if($person['record_id'] == $concept->getContact2Link()){
                        $leadName = $person['firstname'] . " " . $person['lastname'];
                        $projectLead .= htmlspecialchars($leadName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ", ";
                    }
                }
            }
        }

        // Remove trailing " , " from author strings
        $projectLead = rtrim($projectLead, ",  ");

        // Prepare Most Recent Status Update & Status
        $adminUpdateMostRecent = '';
        $status = '';

        $adminDates   = is_array($concept->getAdminupdateD()) ? $concept->getAdminupdateD() : [];
        $projectDates = is_array($concept->getUpdateD())      ? $concept->getUpdateD()      : [];

        $e = static fn(string $v): string =>
        html_entity_decode(html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $findLatest = static function (array $dateMap): array {
            // returns [timestamp|null, key|null]
            $latestTimestamp = null;
            $latestKey = null;

            foreach ($dateMap as $key => $dateValue) {
                $timestamp = strtotime((string) $dateValue);
                if ($timestamp === false) {
                    continue;
                }

                if ($latestTimestamp === null || $timestamp > $latestTimestamp) {
                    $latestTimestamp = $timestamp;
                    $latestKey = $key;
                }
            }

            return [$latestTimestamp, $latestKey];
        };

        [$adminTs, $adminKey]     = $findLatest($adminDates);
        [$projectTs, $projectKey] = $findLatest($projectDates);

        if ($adminTs !== null || $projectTs !== null) {
            $pickProject = $projectTs !== null && ($adminTs === null || $projectTs > $adminTs);

            if ($pickProject) {
                $adminUpdateMostRecent = $e($concept->getProjectUpdate()[$projectKey] ?? '');
                $status = $e($projectStatus[$concept->getProjectStatus()[$projectKey] ?? null] ?? '');
            } else {
                $adminUpdateMostRecent = $e($concept->getAdminUpdate()[$adminKey] ?? '');
                $status = $e($adminStatus[$concept->getAdminStatus()[$adminKey] ?? null] ?? '');
            }
        }

        //Prepare Publications & Abstracts
        $outputTitleArray[$concept->getConceptId()]['publications'] = [];
        $outputTitleArray[$concept->getConceptId()]['abstracts'] = [];
        if (!empty($concept->getOutputTitle())) {
            foreach ($concept->getOutputTitle() as $index => $outputTitle) {
                if ($concept->getOutputType()[$index] == '1') {
                    $outputTitleArray[$concept->getConceptId()]['publications'][] = $outputTitle;
                } else if ($concept->getOutputType()[$index] == '2'){
                    $outputTitleArray[$concept->getConceptId()]['abstracts'][] = $outputTitle;
                }
            }
        }

        // Update maximum counts
        $maxPublications = max($maxPublications, count($outputTitleArray[$concept->getConceptId()]['publications']));
        $maxAbstracts = max($maxAbstracts, count($outputTitleArray[$concept->getConceptId()]['abstracts']));

        #CONCEPTS
        // Build concepts tracker array and sanitize data
        $conceptsTrackerAux = [
            ExcelFunctions::sanitizeExcelData($concept->getConceptId()),
            ExcelFunctions::sanitizeExcelData(implode(", ", $groupName)),
            ExcelFunctions::sanitizeExcelData($concept->getConceptTitle()),
            ExcelFunctions::sanitizeExcelData($concept->getStartYear()),
            ExcelFunctions::sanitizeExcelData($projectLead),
            ExcelFunctions::sanitizeExcelData($ecApproval),
            html_entity_decode(ExcelFunctions::sanitizeExcelData($adminUpdateMostRecent), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            html_entity_decode(ExcelFunctions::sanitizeExcelData($status), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ExcelFunctions::sanitizeExcelData($concept->getActiveY())
        ];
        // Append to multi-register concepts array
        $conceptsData[] = $conceptsTrackerAux;

        #PUBLICATIONS
        if (!empty($concept->getOutputYear())) {
            $numberPublications = count($concept->getOutputYear());
            $countPublications = 0;

            foreach ($concept->getOutputYear() as $id => $year) {
                $conceptId = $concept->getConceptId();

                // Build publication tracker array
                $publicaionsTrackerAux = [
                    $conceptId,
                    $concept->getConceptTitle() ?? "",
                    $year,
                    $concept->getOutputAuthors()[$id] ?? "",
                    $concept->getOutputCitation()[$id] ?? "",
                    $concept->getOutputPmcid()[$id] ?? ""
                ];

                // Append to publications tracker
                $publicationsData[] = $publicaionsTrackerAux;
            }
        }

        #ABSTRACTS
        if (!empty($concept->getOutputType())) {
            foreach ($concept->getOutputType() as $id => $abstract) {
                if ($abstract === "2") {
                    // Build abstract tracker array
                    $abstractsTrackerAux = [
                        $concept->getConceptId() ?? "",
                        $concept->getOutputTitle()[$id] ?? "",
                        $concept->getOutputVenue()[$id] ?? "",
                        $concept->getOutputYear()[$id] ?? "",
                        $concept->getOutputAuthors()[$id] ?? ""
                    ];

                    // Append to abstracts tracker
                    $abstractsData[] = $abstractsTrackerAux;
                }
            }
        }
    }
}


foreach ($conceptsData as $key => $singleConceptData) {
    $conceptID = $singleConceptData[0]; // Extract concept ID

    // Check if the concept exists in $outputTitleArray
    if (isset($outputTitleArray[$conceptID])) {
        $outputDataType = $outputTitleArray[$conceptID]; // Get publications and abstracts for this concept

        // Initialize counts
        $countPublications = isset($outputDataType['publications']) ? count($outputDataType['publications']) : 0;
        $countAbstracts = isset($outputDataType['abstracts']) ? count($outputDataType['abstracts']) : 0;

        // Add publications to the concept data
        if (!empty($outputDataType['publications'])) {
            $singleConceptData = array_merge($singleConceptData, $outputDataType['publications']);
        }

        // Fill remaining publication slots with empty values (if needed)
        if ($countPublications < $maxPublications) {
            $singleConceptData = array_merge($singleConceptData, array_fill(0, $maxPublications - $countPublications, ""));
        }

        // Add abstracts to the concept data
        if (!empty($outputDataType['abstracts'])) {
            $singleConceptData = array_merge($singleConceptData, $outputDataType['abstracts']);
        }

        // Fill remaining abstract slots with empty values (if needed)
        if ($countAbstracts < $maxAbstracts) {
            $singleConceptData = array_merge($singleConceptData, array_fill(0, $maxAbstracts - $countAbstracts, ""));
        }

        // Update the concept in the main array
        $conceptsData[$key] = $singleConceptData;
    }
}

ArrayFunctions::array_sort_by_column($conceptsData,0);
ArrayFunctions::array_sort_by_column($publicationsData,2);
ArrayFunctions::array_sort_by_column($abstractsData,2);

#EXEL SHEET
$filename = $settings['hub_name']." Hub Concept Tracker - " . date("F Y") . ".xlsx";

$styleArray = array(
    'font'  => array(
        'size'  => 10,
        'name'  => 'Calibri'
    ),
    'alignment' => array(
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    ));

$spreadsheet = new Spreadsheet();
$spreadsheet->getDefaultStyle()->applyFromArray($styleArray);
$sheet = $spreadsheet->getActiveSheet();

#CONCEPTS
// SECTION HEADERS CONFIGURATION
$sectionHeaders = [
    "Concept ID", "Working Group", "Concept Title", "Start Year", "Project Lead",
    "Approval Date", "Most Recent Status Update", "Status", "Active?"
];

// SECTION HEADERS WIDTH
$sectionHeadersWidth = [
    14, 20, 30, 10, 15, 30, 30, 30, 10
];

// SECTION CENTERED CONFIGURATION
$sectionCentered = [
    0, 0, 0, 1, 0, 0, 0, 0, 1
];

// Add publication headers dynamically
for ($i = 1; $i <= $maxPublications; $i++) {
    $sectionHeaders[] = "Publication " . $i; // Add "Publication1", "Publication2", ...
    $sectionHeadersWidth [] = 20; // Add Width
    $sectionCentered [] = 0; // Add Section centered
}

// Add abstract headers dynamically
for ($i = 1; $i <= $maxAbstracts; $i++) {
    $sectionHeaders[] = "Abstract " . $i; // Add "Abstract1", "Abstract2", ...
    $sectionHeadersWidth [] = 20; // Add Width
    $sectionCentered [] = 0; // Add Section centered
}


// SECTION HEADERS LETTERS
$sectionHeadersLetters = ExcelFunctions::generateExcelColumnLetters(count($sectionHeaders));


// INITIALIZE ROW NUMBER
$rowNumber = 1;

// APPLY HEADERS TO SHEET
$sheet = ExcelFunctions::getExcelHeaders(
    $sheet,
    $sectionHeaders,
    $sectionHeadersLetters,
    $sectionHeadersWidth,
    $rowNumber
);

// Get the last column letter dynamically
$lastColumnLetter = end($sectionHeadersLetters); // Last column letter based on headers size

// APPLY AUTO FILTER TO SHEET
$sheet->setAutoFilter("A1:{$lastColumnLetter}1"); // Dynamically set auto filter range

// INCREMENT ROW NUMBER
$rowNumber++;

// POPULATE DATA INTO SHEET
$sheet = ExcelFunctions::getExcelData(
    $sheet,
    $conceptsData,
    $sectionHeaders,
    $sectionHeadersLetters,
    $sectionCentered,
    $rowNumber,
    "1"
);

#Rename sheet
$sheet->setTitle('Concepts');

#PUBLICATIONS
$pSheet = $spreadsheet->createSheet(1);
$pSheet->setTitle('Publications');

#SECTION HEADERS
// SECTION HEADERS CONFIGURATION
$sectionHeaders = [
    "Concept ID", "Title", "Year Published", "Full Author List",
    "Citation", "PMCID"
];

// SECTION HEADERS LETTERS
$sectionHeadersLetters = range('A', 'F'); // Automatically generates 'A' to 'F'

// SECTION HEADERS WIDTH
$sectionHeadersWidth = [
    14, 30, 10, 30, 20, 14
];

// SECTION CENTERED CONFIGURATION
$sectionCentered = [
    0, 0, 1, 0, 0, 0
];

// INITIALIZE ROW NUMBER
$rowNumber = 1;

// APPLY HEADERS TO SHEET
$sheet = ExcelFunctions::getExcelHeaders(
    $pSheet,
    $sectionHeaders,
    $sectionHeadersLetters,
    $sectionHeadersWidth,
    $rowNumber
);

// APPLY AUTO FILTER TO SHEET
$sheet->setAutoFilter('A1:F1');

// SET ROW HEIGHT
$sheet->getRowDimension($rowNumber)->setRowHeight(40);

// INCREMENT ROW NUMBER
$rowNumber++;

// POPULATE DATA INTO SHEET
$sheet = ExcelFunctions::getExcelData(
    $pSheet,
    $publicationsData,
    $sectionHeaders,
    $sectionHeadersLetters,
    $sectionCentered,
    $rowNumber,
    "1"
);

#CONF ABSTRACTS
$mcaSheet = $spreadsheet->createSheet(2);
$mcaSheet->setTitle('Abstracts');

// SECTION HEADERS CONFIGURATION
$sectionHeaders = [
    "Concept ID", "Title", "Conference", "Conference Year", "Full Author List"
];

// SECTION HEADERS LETTERS
$sectionHeadersLetters = range('A', 'E'); // Automatically generates 'A' to 'E'

// SECTION HEADERS WIDTH
$sectionHeadersWidth = [
    14, 40, 15, 10, 40
];

// SECTION CENTERED CONFIGURATION
$sectionCentered = [
    0, 0, 1, 0, 0
];

// INITIALIZE ROW NUMBER
$rowNumber = 1;

// APPLY HEADERS TO SHEET
$sheet = ExcelFunctions::getExcelHeaders(
    $mcaSheet,
    $sectionHeaders,
    $sectionHeadersLetters,
    $sectionHeadersWidth,
    $rowNumber
);

// APPLY AUTO FILTER TO SHEET
$sheet->setAutoFilter('A1:E1');

// INCREMENT ROW NUMBER
$rowNumber++;

// POPULATE DATA INTO SHEET
$sheet = ExcelFunctions::getExcelData(
    $mcaSheet,
    $abstractsData,
    $sectionHeaders,
    $sectionHeadersLetters,
    $sectionCentered,
    $rowNumber,
    "1"
);

// Explicitly set the first sheet as active
$spreadsheet->setActiveSheetIndex(0);

//Download the file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$filename.'"');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit; // Ensure script ends after output
?>

