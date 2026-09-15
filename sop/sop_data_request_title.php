<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$record = htmlentities($_REQUEST['record'],ENT_QUOTES);

$RecordSetSOP = \REDCap::getData([
                                    'project_id' => $pidsArray['SOP'],
                                    'return_format' => 'array',
                                    'records' => $record
                                ]);
$sop = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0]);

if($sop !="") {
    $sop_status = $module->getChoiceLabels('sop_status', $pidsArray['SOP']);
    $sop_visibility = $module->getChoiceLabels('sop_visibility', $pidsArray['SOP']);
    $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);

    $concept_id = arrayKeyExistsReturnValue(\REDCap::getData([
                                         'project_id' => $pidsArray['HARMONIST'],
                                         'return_format' => 'json-array',
                                         'records' => $sop['sop_concept_id'],
                                       'fields' => ['concept_id']
                                     ]), [0, 'concept_id']);
    $concept = getReqAssocConceptLink($module, $pidsArray, $sop['sop_concept_id'], 1);

   $people = arrayKeyExistsReturnValue(\REDCap::getData([
                                                             'project_id' => $pidsArray['PEOPLE'],
                                                             'return_format' => 'json-array',
                                                             'records' => $sop['sop_creator'],
                                                             'fields' => ['firstname','lastname','email']
                                                         ]), [0]);
    $research_contact = "<em>None</em>";
    if($people['firstname'] != ''){
        $research_contact = $people['firstname'] . ' ' . $people['lastname']." (<a href='mailto:".$people['email']."'>".$people['email']."</a>)";
    }

    $peopleDC = arrayKeyExistsReturnValue(\REDCap::getData([
                                                               'project_id' => $pidsArray['PEOPLE'],
                                                               'return_format' => 'json-array',
                                                               'records' => $sop['sop_datacontact'],
                                                               'fields' => ['firstname','lastname','email']
                                                           ]), [0]);
    $data_contact = "<em>None</em>";
    if($peopleDC != ''){
        $data_contact = $peopleDC['firstname'] . ' ' . $peopleDC['lastname']." (<a href='mailto:".$peopleDC['email']."'>".$peopleDC['email']."</a>)";
    }

    $array_dates = getNumberOfDaysLeftButtonHTML($sop['sop_due_d'], '', '', '1');

    $statusBadge = 'badge-draft';
    if ($sop['sop_status'] == '1') {
        $statusBadge = 'badge-final';
    }

    $visibility = 'badge-private';
    if ($sop['sop_visibility'] == '2') {
        $visibility = 'badge-public';
    } else {
        $statusBadge = 'hidden';
    }

    $date = new \DateTime($sop['sop_updated_dt']);
    $sop_updated_dt = $date->format('d F Y');

    if ($_REQUEST['option'] == 'und' && $record != '') {
        $userid = $currentUser['record_id'];
        $record_id = $record;

        $Proj = new \Project($pidsArray['SOP']);
        $event_id = $Proj->firstEventId;
        $recordSaveDU = array();

        $RecordSetFollowAct = \REDCap::getData([
                                       'project_id' => $pidsArray['SOP'],
                                       'return_format' => 'json-array',
                                       'records' => $record_id
                                   ]);
        $follow_activity = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetFollowAct,$pidsArray['SOP'])[0]['follow_activity'];
        $array_userid = explode(',', $follow_activity);

        #UNFOLLOW
        if (($key = array_search($userid, $array_userid)) !== false) {
            unset($array_userid[$key]);
            $string_userid = implode(",", $array_userid);
            $recordSaveDU[$record_id][$event_id]['follow_activity'] = $string_userid;
            $results = \Records::saveData($pidsArray['SOP'], 'array', $recordSaveDU,'overwrite', 'YMD', 'flat', '', true, true, true, false, true, array(), true, false);
            \Records::addRecordToRecordListCache($pidsArray['SOP'], $record_id,1);
        }
    }
}

$harmonist_perm = ($currentUser['harmonist_perms___1'] == 1) ? true : false;
?>
<script>
    $(document).ready(function() {
        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
        });
        $('#deleteDataRequest').submit(function () {
            let data = $('#deleteDataRequest').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            CallAJAXAndRedirect(data, <?=json_encode($module->getUrl('sop/sop_delete_data_request.php', true))?>,<?=json_encode($module->getUrl("index.php", true) . "&option=smn&message=D")?>);
            return false;
        });
        $('#makePrivate').submit(function () {
            $('#sop-make-private-confirmation').modal('hide');
            let data = $('#makePrivate').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            CallAJAXAndShowMessage(data,<?=json_encode($module->getUrl('sop/sop_make_private.php', true))?>, "X",window.location.href);
            return false;
        });
        $('#dataUploadForm').submit(function () {
            var data = $('#dataUploadForm').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            uploadDataToolkit(data,<?=json_encode($module->getUrl("hub/hub_data_upload_security_AJAX.php", true))?>);
            return false;
        });
        $('#changeStatus').submit(function () {
            let data = "&status="+$('.dropdown-toggle-custom-status').attr('id');
            data += "&region="+$('#region').val();
            data += "&status_record="+$('#status_record').val();
            data += "&data_response_notes="+encodeURIComponent($('#data_response_notes').val());
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            CallAJAXAndRedirect(data,<?=json_encode($module->getUrl('sop/sop_submit_data_change_status_AJAX.php', true))?>,<?=json_encode($module->getUrl("index.php", true) . "&option=sop&record=".$record."&message=D")?>);
            return false;
        });
    });
</script>

<div class="container">
    <?php
    if (array_key_exists('message', $_REQUEST)) {
        if ($_REQUEST['message'] == 'F') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">This Data Call has been marked complete (if "Close Data Call" checkbox was selected).</div>
            <?php
        } elseif ($_REQUEST['message'] == 'S') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">This Data Call has been started (if "Begin Data Call" checkbox was selected).</div>
            <?php
        } elseif ($_REQUEST['message'] == 'C') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Your comment has been successfully added.</div>
            <?php
        } elseif ($_REQUEST['message'] == 'E') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Your comment has been successfully updated.</div>
            <?php
        } elseif ($_REQUEST['message'] == 'D') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">The status has been changed.</div>
            <?php
        } elseif ($_REQUEST['message'] == 'P') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Data Request has been made public.</div>
            <?php
        } elseif ($_REQUEST['message'] == 'X') { ?>
            <div class="alert alert-success col-md-12" id="succMsgContainer">Data Request has been made private.</div>
            <?php
        }
    }
    ?>

    <div class="backTo">
        <?php
        $back_button = '<a href="'.$module->getUrl('index.php', true) . '&option=smn'.'">&lt; Back to Request Data</a>';
        if ($_REQUEST['type'] != "") {
            if ($_REQUEST['type'] == 's') {
                $back_button = '<a href="'.$module->getUrl('index.php', true) . '&option=upd'.'">&lt; Back to Check and Submit Data</a>';
            } elseif ($_REQUEST['type'] == 'r') {
                $back_button = '<a href="'.$module->getUrl('index.php', true) . '&option=dnd'.'">&lt; Back to Retrieve Data</a>';
            }
        }
        echo $back_button;
        ?>
    </div>
    <?php if(($sop['sop_hubuser'] == $currentUser['record_id'] || $isAdmin || $sop['sop_visibility'] == '2') && $sop !="") { ?>
    <div class="card card-info panel-info-sop">
        <div class="card-header panel-heading-sop">
            <h6 class="card-title d-inline-block pt-2 pb-2 px-0 mt-1">
                Data Request #<?= $sop['record_id']; ?> | <?= $concept_id; ?>
            </h6>
            <?php if ($isAdmin || $harmonist_perm) {
                $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['SOP'] . "&arm=1&id=" . $sop['record_id'];
                ?>
                <div class="btn-group float-end d-none d-lg-inline-block mt-1">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Admin <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php
                        if (($sop['sop_status'] == '2' && ($isAdmin || $harmonist_perm)) || $sop['sop_status'] != '2') { ?>
                            <li>
                                <a class="dropdown-item" href='<?=$module->getUrl("index.php", true) . "&option=ss1&record=".$record."&step=3"?>' target="_blank">Edit Data Request</a>
                            </li>
                        <?php }
                        if ($isAdmin || $harmonist_perm) { ?>
                            <li>
                                <a class="dropdown-item" href="#" onclick="$('#modal-copy-data-request').modal('show');">Copy Data Request</a>
                            </li>
                        <?php }
                        echo '<li>
                                <a class="dropdown-item" href="#" onclick="$(\'#hub_view_votes\').modal(\'show\');">Edit Group Status</a>
                              </li>';
                        $status = $sop['data_response_status'][$currentUser['person_region']];
                        $status_icons = getDataCallStatusIcons($status);
                        $status_text = $status_type[$sop['data_response_status'][$currentUser['person_region']]];
                        if ($sop['data_response_status'][$currentUser['person_region']] == "") {
                            $status_text = $status_type[0];
                        }
                        $current_region_status = htmlentities($status_icons . '<span class="status-text"> ' . htmlspecialchars($status_text, ENT_QUOTES) . '</span>');
                        $dataResponseNotes = htmlspecialchars(arrayKeyExistsReturnValue($sop,['data_response_notes',arrayKeyExistsReturnValue($currentUser,['person_region'])]));
                        $regionUpdateTs = arrayKeyExistsReturnValue($sop,['region_update_ts',arrayKeyExistsReturnValue($currentUser,['person_region'])]);
                        echo '<li>
                            <a class="dropdown-item" href="#" onclick="changeStatus(\'' . $current_region_status . '\',\'' . arrayKeyExistsReturnValue($sop,['record_id']) . '\',\'' . arrayKeyExistsReturnValue($currentUser,['person_region']) . '\',\'' . $dataResponseNotes . '\',\'' . $regionUpdateTs . '\',\'modal-data-change-status\')">Change Status</a>
                          </li>';

                        if (($isAdmin || $harmonist_perm) && $sop['sop_visibility'] == "2" && $sop['sop_status'] != "1") {
                            echo '<li><a class="dropdown-item" href="#" onclick="confirmMakePrivate(\'' . $sop['record_id'] . '\')" class="open-codesModal">Revert to Private</a></li>';
                        }
                        if ($sop['sop_status'] != "1") {
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record, "finalization_of_data_request", "");
                            $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);
                            echo '<li><a class="dropdown-item" href="#" onclick="editIframeModal(\'hub-modal-data-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\');">Start Data Call</a></li>';
                        }
                        if ($sop['sop_status'] == "1" && $sop['sop_visibility'] == "2" && $sop['sop_finalize_y'][1] == '1' && empty($sop['sop_closed_y'])) {
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record, "data_call_closure", "");
                            $survey_link_closure = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);
                            echo '<li><a class="dropdown-item" href="#" onclick="editIframeModal(\'hub-modal-data-closure\',\'redcap-closure-frame\',\'' . $survey_link_closure . '\');">Archive Data Call</a></li>';
                        }
                        ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="$('#admin-modal-delete').modal('show');">Delete</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $gotoredcap ?>" target="_blank">Go to REDCap</a></li>
                    </ul>
                </div>

                <!-- MODAL COPY DATA REQUEST-->
                <div class="modal fade" id="modal-copy-data-request" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <form class="form-horizontal" action="" method="post" id="dataDownloadForm">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Copy Data Request</h5>
                                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <span>Are you sure you want to copy this data request?</span>
                                </div>
                                <div class="modal-footer">
                                    <?php
                                    $url = $module->getUrl("sop/sop_copy_data_request_AJAX.php", true)."&id=".$module->escape($sop['record_id']);
                                    $urlgoto = $module->getUrl("index.php", true)."&option=ss1&step=3";
                                    ?>
                                    <a type="button" onclick='copyDataRequestWithLoading(this, "<?=$url?>","<?=$urlgoto?>","<?=$module->getCSRFToken()?>")' class="btn btn-success" id="btnCopyDataRequest">Continue</a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- MODAL FINALIZE-->
                <div class="modal fade" id="hub-modal-data-finalize" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Start Data Request</h5>
                                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded_finalize">
                                <iframe class="commentsform" id="redcap-finalize-frame" message="S" name="redcap-finalize-frame"
                                        src="" style="border: none; height: 500px; width: 100%;"></iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL VIEW STATUS-->
                <div class="modal fade" id="hub_view_votes" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Group Status</h5>
                                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Region</th>
                                        <th>Vote</th>
                                        <th>On</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $regions = \REDCap::getData([
                                                                    'project_id' => $pidsArray['REGIONS'],
                                                                    'return_format' => 'json-array',
                                                                    'filterLogic' => "[showregion_y] = '1'"
                                                                ]);
                                    ArrayFunctions::array_sort_by_column($regions, 'region_code');

                                    $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);
                                    $status_icon_color = array(0 => "text-secondary", 1 => "text-warning", 2 => "text-success", 3 => "text-danger", 4 => "text-secondary", 9 => "text-info");
                                    $status_icon = array(0 => "fa-times", 1 => "fa-wrench", 2 => "fa-check", 3 => "fa-ban", 4 => "fa-times", 9 => "fa-question");

                                    $region_row = '';
                                    foreach ($regions as $region) {
                                        $region_id = (arrayKeyExistsReturnValue($region,['record_id']) == '1') ? "" : arrayKeyExistsReturnValue($region,['record_id']);
                                        $region_time = arrayKeyExistsReturnValue($sop,['region_complete_ts',$region_id]);
                                        if (!empty($region_time)) {
                                            $region_time = date('Y-m-d H:i', strtotime($region_time));
                                            $class = "";
                                            if (strtotime($sop['sop_due_d']) < strtotime($region_time)) {
                                                $class = "text-danger fw-bold";
                                            }
                                        }

                                        $status = $sop['data_response_status'][$region['record_id']];
                                        $status_icons = '<i class="fa ' . $module->escape($status_icon[$status]) . ' ' . $module->escape($status_icon_color[$status]) . ' me-2" aria-hidden="true"></i>';
                                        $status_text = $status_type[$sop['data_response_status'][$region['record_id']]];
                                        if ($sop['data_response_status'][$region['record_id']] == "") {
                                            $status_text = $status_type[0];
                                        }

                                        // Default dropdown display value
                                        $selected = '<span class="d-flex align-items-center">' . $status_icons . '<span class="text-start" id="'.$module->escape($region_id.'_'.$sop['data_response_status'][$region['record_id']]).'">' . htmlspecialchars($status_text, ENT_QUOTES) . '</span></span>';

                                        // Dropdown menu items
                                        $menu = '';
                                        foreach ($status_type as $index => $status) {
                                            $menu .= '<li>';
                                            $menu .= '<a class="dropdown-item d-flex align-items-center" href="#" onclick="updateDropdownMenu(this, \'' . $module->escape($region_id . '_' . $index) . '\')">';
                                            $menu .= '<i class="fa ' . $module->escape($status_icon[$index]) . ' ' . $module->escape($status_icon_color[$index]) . ' me-2" aria-hidden="true" id="' . $module->escape($region_id . '_' . $index) . '"></i>';
                                            $menu .= '<span class="text-start dropdown_votes">' . htmlspecialchars($status, ENT_QUOTES) . '</span>';
                                            $menu .= '</a>';
                                            $menu .= '</li>';
                                        }

                                        $region_row .= '<tr>' .
                                            '<td>' . htmlspecialchars($region['region_code'], ENT_QUOTES) . '/' . htmlspecialchars($region['region_name'], ENT_QUOTES) . '</td>' .
                                            '<td>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-custom form-control d-flex justify-content-between align-items-center" type="button" id="' . $module->escape($region_id.'_'.$sop['data_response_status'][$region['record_id']]) . '" data-bs-toggle="dropdown" aria-expanded="false" style="width: 320px;">
                                                        <span class="d-flex align-items-center">' . $status_icons . htmlspecialchars($status_text, ENT_QUOTES) . '</span>
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdown-' . $region_id . '" style="width: 320px;">
                                                        ' . $menu . '
                                                    </ul>
                                                </div>
                                            </td>' .
                                            '<td><span class="' . $class . '">' . htmlspecialchars($region_time, ENT_QUOTES) . '</span></td>' .
                                            '</tr>';
                                    }
                                    echo $region_row;
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary btn-save" onclick='save_status(<?= json_encode($module->getUrl('sop/sop_data_request_title_admin_status_AJAX.php', true)) ?>, <?= json_encode($currentUser['record_id']) ?>, <?= json_encode($currentUser['person_region']) ?>, <?= json_encode($module->getCSRFToken()) ?>, <?= json_encode($module->escape($sop['record_id'])) ?>)' data-bs-dismiss="modal">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL CHANGE STATUS-->
                <div class="modal fade" id="modal-data-change-status" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <form class="form-horizontal" action="" method="post" id="changeStatus">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Change Status</h5>
                                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">Last update on <i id="region_update_ts"></i></div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">Set my status:</div>
                                        <div>
                                            <?php
                                            $status_type = $module->escape($module->getChoiceLabels('data_response_status', $pidsArray['SOP']));
                                            $status_icon_color = $module->escape([0 => "text-secondary", 1 => "text-warning", 2 => "text-success", 3 => "text-danger", 4 => "text-secondary", 9 => "text-info"]);
                                            $status_icon = $module->escape([0 => "fa-times", 1 => "fa-wrench", 2 => "fa-check", 3 => "fa-ban", 4 => "fa-times", 9 => "fa-question"]);

                                            // Default dropdown display value
                                            $default_status_index = $sop['data_response_status'][$currentUser['person_region']] ?? 0; // Default to index 0 if no value
                                            $selected = '<span class="d-flex align-items-center">' .
                                                '<i class="fa ' . $module->escape($status_icon[$default_status_index]) . ' ' . $module->escape($status_icon_color[$default_status_index]) . ' me-2" aria-hidden="true" id="' . $module->escape($index) . '"></i>' .
                                                '<span class="dropdown_votes">' . htmlspecialchars(getStatusText($status_type, $sop, $currentUser)) . '</span>' .
                                                '</span>';

                                            // Dropdown menu items
                                            $menu = '';
                                            foreach ($status_type as $index => $status) {
                                                $menu .= '<li>';
                                                $menu .= '<a class="dropdown-item d-flex align-items-center py-2" href="#" onclick="updateDropdownMenu(this, \'' . $module->escape($index) . '\')">';
                                                $menu .= '<i class="fa ' . $module->escape($status_icon[$index]) . ' ' . $module->escape($status_icon_color[$index]) . ' me-2" aria-hidden="true"></i>';
                                                $menu .= '<span class="dropdown_votes text-start">' . htmlspecialchars($status, ENT_QUOTES) . '</span>';
                                                $menu .= '</a>';
                                                $menu .= '</li>';
                                            }
                                            ?>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-custom-status form-control d-flex justify-content-between align-items-center" type="button" id="<?=$module->escape(array_key_first($status_type)) ?>" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 320px; height: 40px;">
                                                    <?=filter_tags($selected)?>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="default-select-value" style="min-width: 320px; max-height: 250px; overflow-y: auto;">
                                                    <?=$menu?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4">Status notes (only visible to your own region):</div>
                                    <div>
                                        <textarea class="form-control" id="data_response_notes" name="data_response_notes" rows="4"></textarea>
                                    </div>
                                    <input type="hidden" name="status_record" id="status_record" value="">
                                    <input type="hidden" name="region" id="region" value="">
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" form="changeStatus" class="btn btn-success" id="btnModalRescheduleForm">Save</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal fade" id="hub-modal-data-closure" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Archive Data Call</h5>
                                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded_closure">
                                <iframe class="commentsform" id="redcap-closure-frame" message="F" name="redcap-closure-frame"
                                        src="" style="border: none; height: 500px; width: 100%;"></iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($sop['sop_visibility'] != '2' && !$isAdmin && !$harmonist_perm) { ?>
                <div class="btn-group float-end mt-1">
                    <a href="#" onclick="$('#admin-modal-delete').modal('show');" class="text-decoration-none" title="Delete Data Request">
                        <span class="fa fa-trash fs-3 text-brown"></span>
                    </a>
                </div>
            <?php } ?>

            <!-- MODAL DELETE-->
            <div class="modal fade" id="admin-modal-delete" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                <form class="form-horizontal" action="" method="post" id="deleteDataRequest">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-dark">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete</h5>
                                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <span>Are you sure you want to delete this Data Request?</span>
                                <input type="hidden" value="<?= $record ?>" id="index_modal_delete" name="index_modal_delete">
                            </div>
                            <div class="modal-footer">
                                <button type="submit" form="deleteDataRequest" class="btn btn-danger" id="btnModalDeleteForm">Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="float-end mt-1 pe-2" id="btn_follow">
                <?php
                $follow_option = '1';
                $follow_class = 'btn-outline-secondary'; // Updated for default styling in Bootstrap 5
                $follow_icon_class = 'fa fa-plus-square';
                $follow_text = "Follow Activity";
                if ($sop['follow_activity'] != '' && $_REQUEST['option'] != 'und') {
                    $array_userid = explode(',', $sop['follow_activity']);
                    if (in_array($currentUser['record_id'], $array_userid)) {
                        $follow_option = '0';
                        $follow_class = 'btn-primary'; // Updated for active state
                        $follow_icon_class = 'fa fa-check-square';
                        $follow_text = "Following";
                    }
                }
                ?>
                <button onclick="follow_activity('<?= $module->escape($follow_option) ?>','<?= $module->escape($currentUser['record_id']) ?>','<?= $module->escape($sop['record_id']) ?>','<?= $module->getUrl("sop/sop_data_request_follow_activity_AJAX.php", true) ?>','<?= $module->getCSRFToken() ?>')"
                        class="btn <?= $module->escape($follow_class) ?> actionbutton">
                    <i class="<?= $module->escape($follow_icon_class) ?>"></i>
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($follow_text, ENT_QUOTES) ?></span>
                </button>
            </div>

            <div class="float-end pe-2 mt-1" id="btn_follow">
                <?php
                if ($sop['sop_visibility'] == '1') {
                    $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOP'], $record, "dhwg_review_request", "");
                    $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);

                    echo '<a href="#" onclick="editIframeModal(\'sop-make-public\',\'redcap-edit-frame-make-public\',\'' . $survey_link . '\');" class="btn btn-success open-codesModal"><i class="fa fa-paper-plane" aria-hidden="true"></i> Route for Review</a>';
                }
                ?>
            </div>
        </div>

        <div class="modal fade" id="sop-make-public" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Send for Review</h5>
                        <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded_public">
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

        <div class="card-body">
            <?php
                $showdataCallBanner = false;
                if ($sop['sop_finalize_y'][1] != "" || ($sop['sop_closed_y'] != "" && $sop['sop_closed_y'] != "1")) {
                $message_text = "";
                if ($sop['sop_finalize_y'][1] != "" && $sop['sop_finalize_y'][1] == "1") {
                    $message_text .= "Data Call started";
                    $showdataCallBanner = true;
                    if ($sop['sop_final_d'] != "") {
                        $message_text .= " on " . $sop['sop_final_d'] . ".";
                    } else {
                        $message_text .= ".";
                    }
                }
                if ($sop['sop_closed_y'] != "" && $sop['sop_closed_y'] == "1") {
                    $message_text .= " Data Call archived";
                    $showdataCallBanner = true;
                    if ($sop['sop_closed_d'] != "") {
                        $message_text .= " on " . $sop['sop_closed_d'] . ".";
                    } else {
                        $message_text .= ".";
                    }
                }
                if ($showdataCallBanner) {
                    echo '<div class="alert alert-warning fade show col-12 d-flex justify-content-between align-items-center" id="succMsgContainer_stay">';
                    echo '<div>' . htmlspecialchars($message_text, ENT_QUOTES) . '</div>';
                    echo '<button href="#" onclick="confirmDataUpload(\'' . $module->escape($sop['sop_concept_id']) . '\',\'' . $module->escape($currentUser['record_id']) . '\',\'' . $module->escape($concept_id) . '\',\'' . $module->escape($sop['record_id']) . '\');" class="btn btn-outline-secondary btn-sm">Upload Data</a>';
                    echo '</div>';
                }
                ?>
                <div class="modal fade" id="modal-data-upload-confirmation" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <form class="row g-3" action="" method="post" id="dataUploadForm">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Upload Data</h5>
                                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <span>Are you ready to upload data for concept <span id="data-submit-concept" class="fw-bold"></span>?</span>
                                    <br>
                                    <span>This will redirect you to the Data Toolkit, where you can check and/or submit data.</span>
                                </div>
                                <input type="hidden" id="assoc_concept" name="assoc_concept">
                                <input type="hidden" id="user" name="user">
                                <input type="hidden" id="upload_record" name="upload_record">
                                <div class="modal-footer">
                                    <button type="submit" form="dataUploadForm" class="btn btn-success" id="btnModalRescheduleForm">Continue</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php } ?>
            <div class="row request">
                <div class="col-lg-8 col-md-8 col-sm-12"><strong>Linked Concept:</strong> <?= filter_tags($concept); ?></div>
                <div class="col-lg-4 col-md-4">
                    <strong>Version: </strong>
                    <span><em><?= htmlspecialchars($sop_updated_dt, ENT_QUOTES) ?></em></span>
                    <span class="badge rounded-pill <?= $statusBadge ?>"><?= htmlspecialchars($sop_status[$sop['sop_status']], ENT_QUOTES); ?></span>
                    <span class="badge rounded-pill <?= $visibility ?>"><?= htmlspecialchars($sop_visibility[$sop['sop_visibility']], ENT_QUOTES); ?></span>
                </div>
            </div>
            <div class="row request">
                <div class="col-lg-8 col-md-8 col-sm-12"><strong>Research Contact:</strong> <?= filter_tags($research_contact); ?></div>
                <div class="col-lg-4 col-md-4"><strong>Data Due: </strong>
                    <?= filter_tags($array_dates['text']) ?> <?= filter_tags($array_dates['button']) ?>
                </div>
            </div>
            <div class="row request">
                <div class="col-lg-8 col-md-8 col-sm-12"><strong>Data Contact:</strong> <?= filter_tags($data_contact); ?></div>
                <div class="col-lg-4 col-md-4">
                    <strong>Status: </strong>
                    <?php
                    $status_icons = "";
                    $status_text = "<em>Not started</em>";
                    $dataResponseStatus = arrayKeyExistsReturnValue($sop,['data_response_status',$currentUser['person_region']]);
                    if($dataResponseStatus != "0"){
                        $status_icons = getDataCallStatusIcons($dataResponseStatus);
                        $status_text = htmlspecialchars(getStatusText($status_type, $sop, $currentUser), ENT_QUOTES);
                    }

                    echo $status_icons . '<span class="status-text"> ' . $status_text . '</span>';
                    ?>
                </div>
            </div>
            <div class="row request">
                <div class="col-lg-8 col-md-8 col-sm-12">
                    <div class="d-inline-block"><strong>Data Downloaders: </strong></div>
                    <?php
                    if ($sop['sop_downloaders'] != "") {
                        $downloaders = explode(',', $sop['sop_downloaders']);
                        $downloaders_list = "";
                        $downloadersOrdered = array();
                        foreach ($downloaders as $down) {
                            $down = trim($down);
                            $peopleDown = arrayKeyExistsReturnValue(\REDCap::getData([
                                                            'project_id' => $pidsArray['PEOPLE'],
                                                            'return_format' => 'json-array',
                                                            'records' => $down,
                                                            'fields' => ['firstname', 'lastname', 'email', 'person_region']
                                                        ]), [0]);
                            $region_codeDown = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                         'project_id' => $pidsArray['REGIONS'],
                                                                                         'return_format' => 'json-array',
                                                                                         'records' => $peopleDown['person_region'],
                                                                                         'fields' => ['region_code']
                                                                                     ]), [0, 'region_code']);
                            $downloadersOrdered[$down]['name'] = $peopleDown['firstname'] . " " . $peopleDown['lastname'];
                            $downloadersOrdered[$down]['email'] = $peopleDown['email'];
                            $downloadersOrdered[$down]['region_code'] = "(" . $region_codeDown . ")";
                        }
                        ArrayFunctions::array_sort_by_column($downloadersOrdered, 'name');
                        $count = 0;
                        foreach ($downloadersOrdered as $downO) {
                            $downO = $module->escape($downO);
                            $comma = ",&nbsp;";
                            $count++;
                            if (count($downloadersOrdered) == $count) {
                                $comma = "";
                            }
                            $downloaders_list .= "<div class='d-inline-block'><a href='mailto:" . $downO['email'] . "'>" . $downO['name'] . "</a> " . htmlspecialchars($downO['region_code'], ENT_QUOTES) . $comma . "</div>";
                        }
                    } else {
                        $downloaders_list = '<em>None Assigned</em>';
                    }
                    echo $downloaders_list;
                    ?>
                </div>
                <div class="col-lg-4 col-md-4"></div>
            </div>
            <div class="row request">
                <div class="col-12">
                    <strong>Data Call Notes: </strong><br>
                    <?php
                    if(arrayKeyExists($sop, 'sop_tags')){
                        $selectedTags = array_keys(array_filter($sop['sop_tags']));

                        if(!empty($selectedTags)){
                            $selectedTagsLabel = $module->getChoiceLabels('sop_tags', $pidsArray['SOP']);
                            echo "<div>".implode('', array_map(function ($tag) use ($selectedTagsLabel) {
                                return "<span class='d-inline-block mt-1' style='padding-right: 5px'><span class='badge badge-draft rounded-pill'>" . htmlspecialchars($selectedTagsLabel[$tag]) . "</span></span>";
                            }, $selectedTags))."</div>";
                        }
                    }
                    ?>
                    <?= filter_tags(empty($sop['sop_final_notes']) ? "<em>None</em>" : $sop['sop_final_notes'], ENT_QUOTES); ?>
                </div>
            </div>
            <?php if (!empty($conference_info)) { ?>
                <div class="row request">
                    <div class="col-12">
                        <strong>Conference:</strong> <?= $conference_info; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
        <div class="card">
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
                        <a href="<?=$fileData->getDownloadLink();?>" target="_blank"><?=$fileData->getIcon();?> Download PDF</a>
                    <?php } ?>

                    <!-- Chevron Icon -->
                    <a class="collapseText d-flex align-items-center toggle-icon" data-bs-toggle="collapse" href="#collapse1" role="button" aria-expanded="true" aria-controls="collapse1">
                        <i class="fa fa-chevron-down ms-3" aria-hidden="true" id="comment-arrow"></i>
                    </a>
                </h6>
            </div>

            <div id="collapse1" class="table-responsive collapse show" aria-expanded="true">
                <?php if (!empty($sop["sop_finalpdf"])) { ?>
                    <iframe class="commentsform" id="redcap-frame" src="<?= $pdf_path ?>"
                            style="border: none; width: 100%; height: 500px;" frameborder="0"></iframe>
                <?php } else { ?>
                    <table class="table table-hover table-bordered table-font-size">
                        <tbody>
                        <tr>
                            <td><span><em>No document available</em></span></td>
                        </tr>
                        </tbody>
                    </table>
                <?php } ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0 pe-2">
                    Data Request Uploads
                    <a href="<?= $module->geturl("index.php", true) . "&option=lgd&record=" . $record; ?>" class="float-end">View more</a>
                </h6>
            </div>
            <div id="collapse_dataReqUp" class="collapse show table-no-borders" aria-expanded="true">
                <table class="table table-bordered sortable-theme-bootstrap" data-sortable id="sortable_table">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <?php
                    $uploads = \REDCap::getData([
                                                'project_id' => $pidsArray['DATAUPLOAD'],
                                                'return_format' => 'json-array',
                                                'filterLogic' => "[data_assoc_request] = '" . $record . "'"
                                            ]);
                    ArrayFunctions::array_sort_by_column($uploads, 'responsecomplete_ts', SORT_DESC);
                    if (!empty($uploads)) { ?>

                        <thead>
                        <tr>
                            <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Upload Date</th>
                            <th class="sorted_class">Uploaded By</th>
                            <th class="sorted_class">Notes</th>
                            <th class="sorted_class">Region</th>
                            <th class="sorted_class">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $count = 0;
                        foreach ($uploads as $up) {
                            if ($settings['dataupload_dur'] > $count) {
                                $people = $module->escape(arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                          'project_id' => $pidsArray['PEOPLE'],
                                                                                          'return_format' => 'json-array',
                                                                                          'records' => $up['data_upload_person'],
                                                                                          'fields' => ['firstname', 'lastname', 'email']
                                                                                      ]), [0]));
                                $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a>";

                                $region_code = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                                 'project_id' => $pidsArray['REGIONS'],
                                                                                                 'return_format' => 'json-array',
                                                                                                 'records' => $up['data_upload_region'],
                                                                                                 'fields' => ['region_code']
                                                                                             ]), [0, 'region_code']);

                                $status = '<span class="badge bg-success">Available</span>';
                                if ($up['deleted_y'] == '1') {
                                    $status = '<span class="badge bg-danger">Expired</span>';
                                }

                                echo "<tr>";
                                echo "<td width='200px'>" . htmlspecialchars($up['responsecomplete_ts'], ENT_QUOTES) . "</td>" .
                                    "<td width='250px'>" . $contact_person . "</td>" .
                                    "<td width='500px'>" . htmlspecialchars($up['upload_notes'], ENT_QUOTES) . "</td>" .
                                    "<td width='120px'>" . htmlspecialchars($region_code, ENT_QUOTES) . "</td>" .
                                    "<td width='120px'>" . $status . "</td>";
                                echo "</tr>";
                                $count++;
                            } else {
                                break;
                            }
                        }
                    } else {
                        ?>
                        <tbody>
                        <tr>
                            <td class="ps-3"><span><em>No active data calls.</em></span></td>
                        </tr>
                        </tbody>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <a class="collapseText d-flex align-items-center w-100" data-bs-toggle="collapse" href="#collapseComments" role="button" aria-expanded="true" aria-controls="collapse1">
                        <span>Comments and Questions</span>
                        <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="review-arrow"></i>
                    </a>
                </h6>
            </div>
            <div id="collapseComments" class="collapse show table-no-borders" aria-expanded="true">
                <table class="table table-bordered table-font-size">
                    <?php
                    $comments = \REDCap::getData([
                                                  'project_id' => $pidsArray['SOPCOMMENTS'],
                                                  'return_format' => 'json-array',
                                                  'filterLogic' => "[sop_id] = '" . $record . "'"
                                              ]);
                    krsort($comments);
                    $group_discussion = "";
                    if (!empty($comments)) { ?>
                        <thead>
                        <tr>
                            <th class="comments-table">Name / Time</th>
                            <th class="comments-table">Version</th>
                            <th>Comments</th>
                            <th class="text-center"><em class="fa fa-cog"></em></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($comments as $comment) {
                            $comment = $module->escape($comment);
                            $comment_time = "";
                            if (!empty($comment['responsecomplete_ts'])) {
                                $dateComment = new \DateTime($comment['responsecomplete_ts']);
                                $dateComment->modify("+1 hours");
                                $comment_time = $dateComment->format("Y-m-d H:i:s");
                            }

                            $people = $module->escape(arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                                     'project_id' => $pidsArray['PEOPLE'],
                                                                                                     'return_format' => 'json-array',
                                                                                                     'records' => $comment['response_person'],
                                                                                                     'fields' => ['firstname', 'lastname', 'email']
                                                                                                 ]), [0]));
                            $name = trim($people['firstname'] . ' ' . $people['lastname']);

                            $gd_files = "";
                            if (!empty($comment['revised_file'])) {
                                if (!empty($comment['comments'])) {
                                    $gd_files .= "<br/>";
                                }
                                $gd_files .= getFileLink($module, $pidsArray['PROJECTS'], $comment['revised_file'], '', '', $secret_key, $secret_iv, $currentUser['record_id'], "");
                            }

                            $statusBadge = arrayKeyExistsReturnValue($comment, ['sop_status']) == '1' ? 'badge-draft' : 'badge-final';

                            $group_discussion .= "<tr>" .
                                "<td class='w-20'><a href='mailto:" . $people['email'] . "'>" . $name . "</a> (" . arrayKeyExistsReturnValue($comment, ['response_regioncode']) . ")<br/>" . $comment_time . "</td>" .
                                "<td class='w-3'><span class='badge rounded-pill " . $statusBadge . "'>" . $sop_status[$comment['comment_ver']] . "</span></td>" .
                                "<td class='w-70'>" . nl2br($comment['comments']) . $gd_files . "</td>" .
                                "<td class='w-5 text-center'>";
                            if ($comment['response_person'] == $currentUser['record_id']) {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['SOPCOMMENTS'], $comment['record_id'], "sop_comments", "");
                                $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);

                                $group_discussion .= '<a href="#" class="btn btn-outline-secondary open-codesModal" onclick="editIframeModal(\'hub_comment_and_votes_survey\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';
                            }
                            $group_discussion .= "</td></tr>";
                        }
                        echo $group_discussion;
                    } else {
                    ?>
                    <tbody>
                    <tr>
                        <td class="ps-3"><span><em>No comments and questions.</em></span></td>
                    </tr>
                    </tbody>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL EDIT COMMENT-->
        <div class="modal fade" id="hub_comment_and_votes_survey" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Comments and Votes</h5>
                        <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded">
                        <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" src="" message="E"
                                style="border: none; height: 810px; width: 100%;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    <?php if ($currentUser['harmonist_regperm'] != 1) { ?>
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <a class="collapseText d-flex align-items-center w-100" data-bs-toggle="collapse" href="#collapse_ask" role="button" aria-expanded="true" aria-controls="collapse_ask">
                        <span>Comment / Ask Question</span>
                        <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="review-arrow"></i>
                    </a>
                </h6>
            </div>
            <?php
            $survey_path = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($pidsArray['SURVEYLINKSOP']) . "&sop_id=" . $module->escape($record) . "&response_person=" . $module->escape($currentUser['record_id']) . "&response_region=" . $module->escape($currentUser['person_region']) . "&comment_ver=" . $module->escape($sop['sop_status']);
            ?>
            <div id="collapse_ask" class="collapse show" aria-expanded="true">
                <div class="card-body">
                    <iframe class="commentsform" id="redcap-sop" src="<?= $survey_path ?>" message="C"
                            style="border: none; height: 550px; width: 100%;"></iframe>
                </div>
            </div>
        </div>
        <script>
            $(document).ready(function () {
                Sortable.init();
            });

            $(document).ready(function () {
                $('html,body').scrollTop(0);
                $("html,body").animate({scrollTop: 0}, "slow");
            });

            function updateDropdownMenu(element, newId) {
                const dropdownButton = element.closest('.dropdown').querySelector('button');

                // Update the inner HTML of the button
                dropdownButton.innerHTML = `
                <span class='d-flex align-items-center'>
                    ${element.querySelector('i').outerHTML}
                    <span class='text-start'>${element.querySelector('.dropdown_votes').textContent}</span>
                </span>
                `;

                // Update the button's ID
                dropdownButton.id = newId;
            }
        </script>
    <?php }
   }else{ ?>
        <div class="alert alert-warning col-12" role="alert">
            <em>Data Request #<?= $record ?> is not available at this time.</em>
        </div>
    <?php } ?>
</div>
