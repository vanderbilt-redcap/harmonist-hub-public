<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
?>
<script>
    $(document).ready(function() {
        Sortable.init();
        $('#sortable_table').dataTable( {"pageLength": 50,"order": [0, "desc"]});
    } );
</script>
<div class="container">
    <div class="backTo mb-3">
        <a href="<?=$module->getUrl('index.php', true).'&option=upd'?>">< Back to Submit Data</a>
    </div>
    <h3>Data Call Archive</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_datacall_archive'])?></p>
    <div class="mt-4"></div>
    <div class="table-responsive">
        <table class="table table_requests sortable-theme-bootstrap" data-sortable id="sortable_table">
            <?php
            $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null);
            $request_dataCall_arc = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'], ['sop_active' => '1', 'sop_finalize_y' => [1 => '1']]);
            ArrayFunctions::array_sort_by_column($request_dataCall_arc, 'sop_due_d', SORT_DESC);
            if (!empty($request_dataCall_arc)) {
                echo getDataCallHeader($hubData, $pidsArray['REGIONS'], $currentUser['person_region'], 1);
                foreach ($request_dataCall_arc as $sop) {
                    echo getDataCallRow($module, $pidsArray, $sop, $isAdmin, $currentUser, $secret_key, $secret_iv, 1, 'a');
                }
            } else { ?>
                <tbody>
                <tr>
                    <td><span><em>No archived Data Calls to display.</em></span></td>
                </tr>
                </tbody>
            <?php } ?>
        </table>
        <div class="modal fade" id="modal-data-upload-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
            <form class="form-horizontal" action="" method="post" id='dataUploadForm'>
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Upload Data</h4>
                            <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <span>Are you ready to submit data for concept <span id="data-submit-concept" style="font-weight: bold"></span>?</span>
                            <br>
                            <span>This will redirect you to the Data Toolkit.</span>
                        </div>
                        <input type="hidden" id="assoc_concept" name="assoc_concept">
                        <input type="hidden" id="user" name="user">
                        <input type="hidden" id="upload_record" name="upload_record">
                        <div class="modal-footer">
                            <button type="submit" form="dataUploadForm" class="btn btn-default btn-success" id='btnModalRescheduleForm'>Continue</button>
                            <a class="btn btn-default btn-cance;" data-dismiss="modal">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>