<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$record_id = htmlentities($_REQUEST['record'],ENT_QUOTES);
$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', ['record_id' => $record_id]);
$sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];

$harmonist_perm = ($currentUser['harmonist_perms___1'] == 1) ? true : false;

?>
<script>
    $(document).ready(function() {
        $('#form_steps_generate_zip').submit(function (e) {
            // Allow normal form submission for file download
            this.submit();
        });

        // Attach an event listener to the "Download ZIP" link
        $('.download-zip-link').click(function (e) {
            e.preventDefault(); // Prevent default link behavior
            $('#form_steps_generate_zip').submit(); // Submit the form
        });
    });
</script>
<div class="me-2"></div>
<?php
if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')) {
    ?>
    <div class="alert alert-success fade show col-md-12" id="succMsgContainer" style="line-height: 2.5em;">
        Data Request successfully finalized.
        <a class="btn btn-success float-end" href="<?= htmlspecialchars($module->getUrl('index.php', true) . '&option=upd') ?>">View Data Request</a>
    </div>
    <?php
}
?>
<div class="container py-2">
    <div class="optionSelect">
        <h3 class="text-success">Steps Complete <i class="fa fa-check" aria-hidden="true"></i></h3>
        <div class="hub-title">
            <p>
                Your Data Request has been generated successfully. You can review and download the PDF below or download a ZIP file with an HTML and a PDF version.
                If you need to make changes,
                <a href="<?= $module->getUrl('index.php', true) . 'option=ss1&record=' . $record_id . '&step=3' ?>">you can go back to edit your data request</a>.
            </p>
            <?php
            echo filter_tags($settings['hub_steps_complete_text']);

            $additionalClasses = ($sop['sop_visibility'] != "2") ? "w-350 mt-3" : "w-450 mt-3 text-center";
            ?>
            <div class="mx-auto <?= $additionalClasses ?>">
                <div class="d-flex justify-content-center align-items-center flex-wrap">
                    <div class="d-inline-block me-3">
                        <?php if ($sop['sop_status'] == "1") { ?>
                            <a href="<?= $module->getUrl('index.php', true) . '&option=upd' ?>" class="btn btn-secondary btn-md">View in Submit Data</a>
                        <?php } else { ?>
                            <a href="<?= $module->getUrl('index.php', true) . '&option=smn' ?>" class="btn btn-secondary btn-md">View in My Drafts</a>
                        <?php } ?>
                    </div>
                    <?php if ($sop['sop_visibility'] != "2") { ?>
                        <div class="d-inline-block">
                            <a href="<?= $module->getUrl('index.php', true) . '&option=spr&record=' . $record_id ?>" class="btn btn-success btn-md">Route for Review</a>
                        </div>
                    <?php } else if ($harmonist_perm || $isAdmin) {
                    $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record_id, "finalization_of_data_request", "");
                    $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);
                    ?>
                    <div class="d-inline-block">
                        <a href="#" onclick="editIframeModal('hub-modal-data-finalize', 'redcap-finalize-frame', '<?= $survey_link ?>');" class="btn btn-primary btn-md">Finalize Data Request</a>
                    </div>
                    <!-- Modal Finalize -->
                    <div class="modal fade" id="hub-modal-data-finalize" tabindex="-1" role="dialog" aria-labelledby="FinalizeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">Finalize Data Request</h4>
                                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded_finalize">
                                    <iframe class="commentsform" id="redcap-finalize-frame" name="redcap-finalize-frame" message="S" src="" style="border: none; height: 500px; width: 100%;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <div class="backTo">
        <a href="<?= $module->getUrl('index.php', true) . '&option=ss1&record=' . $record_id . '&step=3' ?>">&lt; Back to Edit Data Request</a>
    </div>
</div>
<div class="container">
    <div class="card mb-4">
        <div class="card-header">

            <h6 class="card-title d-flex align-items-center">
                <a class="collapseText d-flex align-items-center flex-grow-1" data-bs-toggle="collapse" href="#collapse1" role="button" aria-expanded="true" aria-controls="collapse1">
                    Data Request
                </a>

                <?php
                if (!empty($sop["sop_finalpdf"])) {
                    $pdf_path = $module->getUrl("loadPDF.php", true) . "&edoc=" . $sop["sop_finalpdf"] . "#navpanes=0&scrollbar=0";
                    $fileData = new File( $sop["sop_finalpdf"], $module, $currentUser['record_id'], $secret_key, $secret_iv);
                    ?>
                    <a href="#" onclick="$('#form_steps_generate_zip').submit()"><?=$fileData->getIcon();?> Download ZIP</a>
                <?php } ?>

                <!-- Chevron Icon -->
                <a class="collapseText d-flex align-items-center toggle-icon" data-bs-toggle="collapse" href="#collapse1" role="button" aria-expanded="true" aria-controls="collapse1">
                    <i class="fa fa-chevron-down ms-3" aria-hidden="true" id="comment-arrow"></i>
                </a>

                <form method="POST" action="<?= $module->getUrl('sop/sop_step_5_generate_zip.php', true) ?>" id="form_steps_generate_zip">
                    <input type="hidden" name="record" value="<?= htmlspecialchars($record_id) ?>">
                    <input type="hidden" name="redcap_csrf_token" value="<?= htmlspecialchars($module->getCSRFToken()) ?>">
                </form>
            </h6>
        </div>

        <div id="collapse1" class="table-responsive collapse show" aria-expanded="true">
            <?php if (!empty($sop["sop_finalpdf"])) { ?>
                <iframe class="commentsform" id="redcap-frame" src="<?=$pdf_path?>" style="border: none;width: 100%;height: 500px;"></iframe>
            <?php } else { ?>
                <table class="table table-hover table-bordered table-striped">
                    <tbody>
                    <tr>
                        <td><span><em>No document available</em></span></td>
                    </tr>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>
