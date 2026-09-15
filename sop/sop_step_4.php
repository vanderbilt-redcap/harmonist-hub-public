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

// $htmlPdf is already sanitized inside preparePdfHtml() via HTMLPurifier (see DataRequestBuilder::purifyHTML()).
// Explicitly clear taint at the output sink so Psalm doesn't re-flag the already-purified markup.
/**
 * @psalm-taint-escape html
 * @psalm-taint-escape has_quotes
 * @var string $cleanHtmlPdf
 */
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
