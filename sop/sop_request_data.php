<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

?>
<script language="JavaScript">
    $(document).ready(function() {
        $('#makePrivate').submit(function () {
            $('#sop-make-private-confirmation').modal('hide');
            let data = $('#deleteDataRequest').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            CallAJAXAndShowMessage(data,<?=json_encode($module->getUrl("sop/sop_make_private.php", true))?>, "X",window.location.href);
            return false;
        });
        $('#deleteDataRequest').submit(function () {
            let data = $('#deleteDataRequest').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            CallAJAXAndRedirect(data,<?=json_encode($module->getUrl('sop/sop_delete_data_request.php', true))?>,<?=json_encode($module->getUrl("index.php", true)."&option=smn&message=D")?>);
            return false;
        });
    } );
</script>
<div class="container">
    <?php
    if (array_key_exists('message', $_REQUEST) && $_REQUEST['message'] != '') {
        if ($_REQUEST['message'] == 'P') {?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Data Request has been made public and appears in the orange box below.</div>
            <?php
        } elseif ($_REQUEST['message'] == 'X') {?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Data Request has been made private.</div>
            <?php
        }elseif ($_REQUEST['message'] == 'D') {?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Data Request has been deleted.</div>
            <?php
        }
    }
    ?>
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true).'&option=dat'?>">&lt; Back to Data</a>
    </div>

    <div>
        <h3>Request Data</h3>
        <?=filter_tags(str_replace('reqdatalink', $module->escape(APP_PATH_WEBROOT_FULL.'surveys/?s='.($pidsArray['DATARELEASEREQUEST'] ?? '')), $settings['hub_req_data_text']))?>
        <div>
            <div class="d-flex justify-content-center mb-3">
                <a href="<?=$module->getUrl('index.php', true).'&option=ss1'?>" class="btn btn-success">Create New Data Request</a>
            </div>
        </div>
        <?=filter_tags($settings['hub_req_data_text_after'])?>
    </div>

    <div>
        <div class="card panel-info-sop">
            <div class="card-header d-flex justify-content-between align-items-center panel-heading-sop">
                <h6 class="card-title mb-0">
                    Public Drafts for Review
                </h6>
                <a data-bs-toggle="collapse" href="#collapse" aria-expanded="true" class="text-decoration-none toggle-icon">
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </a>
            </div>
            <div id="collapse" class="table-responsive collapse show table-no-borders">
                <table class="table sortable-theme-bootstrap sop_discuss" data-sortable>
                    <?php
                    $RecordSetSOP = \REDCap::getData([
                                                         'project_id' => $pidsArray['SOP'],
                                                         'return_format' => 'array',
                                                         'filterLogic' => "[sop_status] = '0' AND [sop_active] = '1' AND [sop_visibility] = '2'"
                                                     ]);
                    $sop_drafts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP']);
                    ArrayFunctions::array_sort_by_column($sop_drafts, 'sop_updated_dt', SORT_DESC);
                    if (!empty($sop_drafts)) {?>
                        <colgroup>
                            <col><col><col>
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Data Request Details</th>
                            <th class="sorted_class">Data Contact</th>
                            <th class="sorted_class">Updated On</th>
                            <th class="sorting_disabled" data-sortable="false">Actions</th>
                        </tr>
                        </thead>
                        <?php
                        $harmonist_perm = ($currentUser['harmonist_perms___1'] == 1) ? true : false;

                        $data = "";
                        foreach ($sop_drafts as $draft) {
                            $data .= getDataCallRow($module, $pidsArray, $draft, $isAdmin, $currentUser, $secret_key, $secret_iv, 0, 'p', $harmonist_perm);
                        }
                        echo $data;
                    } else {?>
                        <tbody>
                        <tr>
                            <td colspan="4"><span><em>No public drafts for review available</em></span></td>
                        </tr>
                        </tbody>
                    <?php }?>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    My Drafts
                </h6>
                <a data-bs-toggle="collapse" href="#collapse2" aria-expanded="true" class="text-decoration-none toggle-icon">
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </a>
            </div>
            <div id="collapse2" class="table-responsive collapse show table-no-borders" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                    <?php
                   $RecordSetSOP = \REDCap::getData([
                                                         'project_id' => $pidsArray['SOP'],
                                                         'return_format' => 'array',
                                                         'filterLogic' => "[sop_hubuser] = '".$currentUser['record_id']."' AND [sop_active] = '1' AND [sop_status] = '0'"
                                                     ]);
                    $sop_drafts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP']);
                    ArrayFunctions::array_sort_by_column($sop_drafts, 'sop_updated_dt', SORT_DESC);
                    if (!empty($sop_drafts)) {?>
                        <colgroup>
                            <col><col><col><col><col><col>
                        </colgroup>
                        <thead>
                        <tr>
                            <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Data Request Details</th>
                            <th class="sorted_class">Created On</th>
                            <th class="sorted_class">Updated On</th>
                            <th class="sorting_disabled" data-sortable="false">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $data = "";
                        foreach ($sop_drafts as $draft) {
                            $data .= getDataCallRow($module, $pidsArray, $draft, $isAdmin, $currentUser, $secret_key, $secret_iv, 0, 'm', $harmonist_perm);
                        }
                        echo $data;
                    } else {?>
                        <tbody>
                        <tr>
                            <td colspan="4"><span><em>No drafts available</em></span></td>
                        </tr>
                        </tbody>
                    <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sop-make-public" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Route</h5>
                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame-make-public" name="redcap-edit-frame-make-public" message="P" src="" style="border: none; height: 810px; width: 100%;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sop-make-private-confirmation" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <form class="form-horizontal" action="" method="post" id="makePrivate">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make Private</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>Are you sure you want to <strong>make this draft Data Request PRIVATE?</strong></div>
                    <div>This will move the draft data request from PUBLIC status to PRIVATE status. It will still be accessible to the original creator of the data request, but will not appear on everyone’s dashboard.</div>
                </div>
                <input type="hidden" id="record" name="record">
                <div class="modal-footer">
                    <button type="submit" form="makePrivate" class="btn btn-success" id="btnModalRescheduleForm">Continue</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL DELETE-->
<div class="modal fade" id="admin-modal-delete" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <form class="form-horizontal" action="" method="post" id="deleteDataRequest">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <span>Are you sure you want to delete this Data Request?</span>
                    <input type="hidden" value="" id="index_modal_delete" name="index_modal_delete">
                </div>
                <div class="modal-footer">
                    <button type="submit" form="deleteDataRequest" class="btn btn-danger" id="btnModalDeleteForm">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </form>
</div>
