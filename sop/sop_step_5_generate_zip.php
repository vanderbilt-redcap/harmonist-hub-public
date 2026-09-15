<?PHP
namespace Vanderbilt\HarmonistHubPublicExternalModule;

require_once dirname(dirname(__FILE__))."/projects.php";
require_once APP_PATH_DOCROOT.'Classes/Files.php';

$dataRequestBuilder = new DataRequestBuilder($pidsArray['PROJECTS'], $module);

$record = (int)$dataRequestBuilder->toHTML($_REQUEST['record']);

$Proj = new \Project($pidsArray['SOP']);
$eventId = (int)$Proj->firstEventId;

[$filename, $html_print, $sopFinalPdf] = $dataRequestBuilder->preparePdfHtml($module, $record, $eventId, $settings, true);

// Find existing PDF file
$q = $module->query(
    "SELECT stored_name FROM redcap_edocs_metadata WHERE doc_id = ?",
    [(int)($sopFinalPdf ?? 0)]
);

$pdfFile = '';
if ($q) {
    $row = $q->fetch_assoc();
    $pdfFile = (string)($row['stored_name'] ?? '');
}
if ($pdfFile === '') {
    http_response_code(404);
    exit('PDF not found');
}

// Create ZIP (PDF + HTML files)
$zipFile  = $filename . '.zip';

$zipPath = $module->getSafePath(EDOC_PATH . $zipFile, EDOC_PATH);

$zip = new \ZipArchive();
if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit("Error creating ZIP file");
}

// Add the existing PDF
$zip->addFile(
    $module->getSafePath(EDOC_PATH . $pdfFile, EDOC_PATH),
    $filename . '.pdf'
);

// Add generated HTML (do not read a file that doesn't exist)
$zip->addFromString($filename . '.html', $html_print);

$zip->close();

/** Stream ZIP */
if (!is_file($zipPath)) {
    http_response_code(500);
    exit('ZIP creation failed');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
header('Content-Length: ' . filesize($zipPath));
header('Pragma: no-cache');
header('Expires: 0');

while (ob_get_level()) {
    ob_end_clean();
}
readfile($zipPath);
unlink($zipPath);
exit;
?>

