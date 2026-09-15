<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
?>
<script language="JavaScript">
    $(document).ready(function() {
        $('#dataUploadForm').submit(function () {
            var data = $('#dataUploadForm').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            uploadDataToolkit(data,<?=json_encode($module->getUrl("hub/hub_data_upload_security_AJAX.php", true))?>);
            return false;
        });
    });
</script>
<div class="container">
    <div class="optionSelect">
        <h3>Data Toolkit</h3>
        <p class="hub-title"><?=$settings['toolkit_redirect_text']?></p>
    </div>
    <div class="text-center">
        <a href="#" onclick="confirmDataUpload('',<?=$currentUser['record_id']?>,'','');" class="btn btn-success btn-lg">Go to Toolkit</a>
    </div>

    <div class="modal fade" id="modal-data-upload-confirmation" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <form class="row g-3" action="" method="post" id="dataUploadForm">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Access Toolkit</h5>
                        <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <span>Are you ready to upload data without a concept?</span>
                        <br>
                        <span>This will redirect you to the Data Toolkit for data quality checks and reports.</span>
                    </div>
                    <input type="hidden" id="assoc_concept" name="assoc_concept">
                    <input type="hidden" id="user" name="user">
                    <input type="hidden" id="upload_record" name="upload_record">
                    <div class="modal-footer">
                        <button type="submit" form="dataUploadForm" class="btn btn-success" id="btnModalRescheduleForm">Continue</button>
                        <a class="btn btn-secondary btn-cancel" data-bs-dismiss="modal">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>