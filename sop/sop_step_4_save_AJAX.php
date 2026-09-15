<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

require_once dirname(dirname(__FILE__)) . "/projects.php";

use Dompdf\Dompdf;

$dataRequestBuilder = new DataRequestBuilder($pidsArray['PROJECTS'], $module);

$record = (int)$dataRequestBuilder->toHTML($_REQUEST['id']);

$Proj = new \Project($pidsArray['SOP']);
$eventId = (int)$Proj->firstEventId;

[$filename, $html_pdf] = $dataRequestBuilder->preparePdfHtml($module, $record, $eventId, $settings);
$filename = $filename.".pdf";

// Render + store PDF on DataBase
$reportHash = $filename;
$storedName = md5($reportHash);
$filePath = EDOC_PATH.$storedName;

try {
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html_pdf);
    $dompdf->setPaper('A4', 'portrait');

    $options = $dompdf->getOptions();
    $options->setChroot(EDOC_PATH);
    $dompdf->setOptions($options);

    $dompdf->render();

    file_put_contents($filePath, $dompdf->output());

    //Save document on DB
    $docId = \REDCap::storeFile($filePath, $pidsArray['SOP'], $filename);

} finally {
    if (file_exists($filePath)) unlink($filePath);
}

//Add document DB ID to project
$arraySOP = [
    $record => [
        $eventId => [
            'sop_finalpdf' => $docId
        ],
    ],
];

$results = \Records::saveData($pidsArray['SOP'], 'array', $arraySOP,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
\Records::addRecordToRecordListCache($pidsArray['SOP'], $record,1);

echo json_encode('success');
?>

