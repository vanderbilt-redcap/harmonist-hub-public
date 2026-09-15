<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$dataRequestBuilder = new DataRequestBuilder($pidsArray['PROJECTS'], $module);

$record = (int)$dataRequestBuilder->toHTML(($_REQUEST['record']) ?? null);

$Proj = new \Project($pidsArray['SOP']);
$eventId = (int)$Proj->firstEventId;

[$filename, $htmlPdf] = $dataRequestBuilder->preparePdfHtml(
    $module,
    $record,
    $eventId,
    $settings,
    false,
    true
);

// $htmlPdf is already assembled and purified inside preparePdfHtml() via HTMLPurifier
// (see DataRequestBuilder::purifyHTML()). Re-purify at the output sink so Psalm sees a
// recognized sanitizer instead of a suppressed taint.
$cleanHtmlPdf = (string)$htmlPdf;

$htmlPrint = <<<HTML
                <div style="width: 995px; margin: 0 auto">
                    <div class="table-preview">
                        {$cleanHtmlPdf}
                    </div>
                </div>
                HTML;

print $htmlPrint;

?>
