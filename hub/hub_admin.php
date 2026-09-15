<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$requestType = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
?>
<script>
    $(document).ready(function() {
        Sortable.init();
    } );
</script>
<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'F')){
        ?>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer" role="alert">
            Your Request has been successfully finalized.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'D')){
        ?>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer" role="alert">
            Your Request documents have been successfully uploaded.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'M')){
        ?>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer" role="alert">
            You have successfully created a new concept.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'C')){
        ?>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer" role="alert">
            A request submission has been checked.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){
        ?>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer" role="alert">
            A deadline has been set.
        </div>
        <?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'P')){
        ?>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;" id="succMsgContainer" role="alert">
            The publications json has been updated.
        </div>
        <?php
    }
    ?>
    <div>
        <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important; display: none;" id="succMsgContainer" role="alert">
            Your edits have been saved.
        </div>
    </div>
    <h3>Admin Page</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_admin_text']);?></p>
    <?php
    $requests_labels = [0 => "Concepts",1 => "Abstracts",2 => "Manuscripts",3 => "Fast Track",4 => "Poster", 5=>"Other"];
    $requests_colors = [0 => "#337ab7",1 => "#00b386",2 => "#f0ad4e",3 => "#ff9966",4 => "#5bc0de",5 => "#777"];
    $abstracts_publications_badge_text = ["1" => "badge-concept-text", "2" => "badge-abstract-text", "3" => "badge-manuscript-text", "4" => "badge-poster-text", "5" => "badge-data-text", "99" => "badge-other-text"];

    ?>

    <div class="col-md-12 float-end">
        <div class="float-end">
        <a href="<?=$module->getUrl("index.php",true)."&option=mts"?>">View Hub Statistics</a> |
        <a href="<?=$module->getUrl("index.php",true)."&option=mra&type=a"?>">View Archived Requests</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="card-title d-flex justify-content-between align-items-center">
                <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse_req_admin" role="button" aria-expanded="true" aria-controls="collapse_req_admin">
                    New Requests for Admin Review
                    <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="comment-arrow"></i>
                </a>
            </h6>
        </div>

        <div id="collapse_req_admin" class="table-responsive collapse show table-no-borders">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                <?php
                $any_request_found = false;
                if($requestAdmin != "") {?>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>

                    <thead>
                    <tr>
                        <th class="request_grid_dued sorted_class ps-3" data-sorted="true" data-sorted-direction="descending" style="width: 15%">Date Submitted</th>
                        <th class="sorted_class" style="width: 15%"><span style="display:block;">Request</span><span>Type</span></th>
                        <th class="sorted_class" style="width: 15%"><span style="display:block;">Submitted</span><span>By</span></th>
                        <th class="request_grid_title sorted_class d-none d-sm-table-cell" style="width: 40%">Title</th>
                        <th class="request_grid_actions" data-sortable="false" style="width: 15%">Actions</th>
                    </tr>
                    </thead>

                    <?php
                    foreach ($requestAdmin as $req) {
                        if ($req['approval_y'] == '' || $req['approval_y'] == null) {
                            $any_request_found = true;
                            $person_region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']), array('region_code'))[0]['region_code'];
                            $region = "";
                            if ($person_region_code != "") {
                                $region = " (" . $person_region_code . ")";
                            }

                            $check_submission_text = ($settings['admintext1'] == "") ? $defaultValuesSettings['admintext1'] : $settings['admintext1'];
                            $set_deadline_text = ($settings['admintext2'] == "") ? $defaultValuesSettings['admintext2'] : $settings['admintext2'];
                            $concept_link = getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "");
                            if ($concept_link == "") {
                                $concept_link = $req['mr_temporary'];
                            }
                            echo '<tr>
                                <td class="ps-3"><span class="nowrap">' . $req['requestopen_ts'] . '</span></td>
                                <td><strong>' . htmlspecialchars($requestType[$req['request_type']], ENT_QUOTES) . '</strong><br>' . filter_tags($concept_link) . '</td>
                                <td><a href="mailto:' . $req['contact_email'] . '">' . $req['contact_name'] . '</a>' . htmlspecialchars($region, ENT_QUOTES) . '</td>
                                <td class="d-none d-sm-table-cell"><a href="' . $module->getUrl('index.php', true) . '&option=hub&record=' . $req['request_id'] . '" target="_blank">' . htmlspecialchars($req['request_title'], ENT_QUOTES) . '</a></td>';

                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "request", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link['hash']) . "&modal=modal";
                            echo '<td><div><a href="#" onclick="editIframeModal(\'hub_process_survey\',\'redcap-edit-frame-admin\',\'' . $survey_link . '\',\'' . $check_submission_text . '\', \'C\');" class="btn btn-link btn-sm actionbutton"><i class="fa fa-eye fa-fw" aria-hidden="true"></i> ' . $check_submission_text . '</a></div>';

                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "admin_review", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link['hash']) . "&modal=modal";
                            echo '<div><a href="#" onclick="editIframeModal(\'hub_process_survey\',\'redcap-edit-frame-admin\',\'' . $survey_link . '\',\'' . $set_deadline_text . '\',\'S\');" class="btn btn-link btn-sm open-codesModal" style="margin-top: 7px;"><i class="fa fa-calendar fa-fw" aria-hidden="true"></i> ' . $set_deadline_text . '</a></div></td>';
                        }
                    }
                    if (!$any_request_found) {
                        ?>
                        <tbody>
                        <tr>
                            <td class="ps-3"><span><em>No requests available</em></span></td>
                        </tr>
                        </tbody>
                        <?php
                    }
                } else { ?>
                    <tbody>
                    <tr>
                        <td class="ps-3"><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="card-title d-flex justify-content-between align-items-center">
                <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse1" role="button" aria-expanded="true" aria-controls="collapse1">
                    Request Finalization Workflow
                    <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="comment-arrow"></i>
                </a>
            </h6>
        </div>

        <div id="collapse1" class="table-responsive collapse show table-no-borders">
            <table class="table table_requests sortable-theme-bootstrap admin-table" data-sortable>
                <?php
                $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[approval_y] = '1' AND [detected_complete(1)] = '1'");
                $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $pidsArray['RMANAGER']);
                ArrayFunctions::array_sort_by_column($requests, "due_d");
                $any_request_found = false;
                if ($requests != "") {
                    echo '<colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                     </colgroup>';

                    echo '<thead>' .
                        '<tr>' .
                        '<th class="request_grid_dued sorted_class ps-3" data-sorted="true" data-sorted-direction="descending" style="">Request</th>' .
                        '<th class="request_grid_title sorted_class d-none d-sm-table-cell" style="">Title</th>' .
                        '<th class="request_grid_actions" data-sortable="false" style=""></th>' .
                        '<th class="request_grid_actions" data-sortable="false" style=""></th>' .
                        '<th class="request_grid_actions" data-sortable="false" style=""></th>' .
                        '</tr></thead>';

                    foreach ($requests as $req) {
                        if (($req['finalize_y'] != "" && ($req['request_type'] != '1' && $req['request_type'] != '5')) || ($req['finalize_y'] == "2" && ($req['request_type'] == '1' || $req['request_type'] == '5')) || ($req['mr_assigned'] != "" && $req['finalconcept_doc'] != "" && $req['finalconcept_pdf'] != "")) {
                            // Do not show request
                        } else {
                            $any_request_found = true;
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "finalization_of_request", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link['hash']) . "&modal=modal";
                            $passthru_link_doc = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "final_docs_request_survey", "");
                            $survey_link_doc = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link_doc['hash']) . "&modal=modal";
                            $passthru_link_mr = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "tracking_number_assignment_survey", "");
                            $survey_link_mr = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link_mr['hash']) . "&modal=modal";

                            $req_type = "";
                            if(!empty( $req['assoc_concept'])){
                                $req_type = "(".getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "") .")";
                            }else if( !empty( $req['mr_temporary'])){
                                $req_type = "(".$req['mr_temporary'] .")";
                            }

                            $array_dates = getNumberOfDaysLeftButtonHTML($req['due_d'], '', 'float:right', '0');

                            $person_region_code = $module->escape(\REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']), array('region_code'))[0]['region_code']);
                            $region = "";
                            if ($person_region_code != "") {
                                $region = " (" . $person_region_code . ")";
                            }

                            $finalize_review_text = ($settings['admintext3'] == "") ? $defaultValuesSettings['admintext3'] : $settings['admintext3'];
                            $request_docs_text = ($settings['admintext4'] == "") ? $defaultValuesSettings['admintext4'] : $settings['admintext4'];
                            $assign_mr_text = ($settings['admintext5'] == "") ? $defaultValuesSettings['admintext5'] : $settings['admintext5'];

                            if ($req['finalize_y'] != "") {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-light btn-sm"><span class="fa fa-check-square text-approved"></span> ' . htmlspecialchars($finalize_review_text, ENT_QUOTES) . '</a>';
                            } else {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-outline-secondary btn-sm"><i class="fa fa-legal fa-fw" aria-hidden="true"></i> ' . htmlspecialchars($finalize_review_text, ENT_QUOTES) . '</a>';
                            }
                            if ($req['request_type'] == '1' || $req['request_type'] == '5') {
                                if ($req['author_doc'] != "") {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-light btn-sm"><span class="fa fa-check-square text-approved"></span> ' . htmlspecialchars($request_docs_text, ENT_QUOTES) . '</a>';
                                } else {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-outline-secondary btn-sm"><i class="fa fa-file fa-fw" aria-hidden="true"></i> ' . htmlspecialchars($request_docs_text, ENT_QUOTES) . '</a>';
                                }
                                if ($req['mr_assigned'] != "" && $req['finalconcept_doc'] != "" && $req['finalconcept_pdf'] != "") {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-light btn-sm"><span class="fa fa-check-square text-approved"></span> ' . htmlspecialchars($assign_mr_text, ENT_QUOTES) . '</a>';
                                } else {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-outline-secondary btn-sm"><i class="fa fa-hashtag fa-fw" aria-hidden="true"></i> ' . htmlspecialchars($assign_mr_text, ENT_QUOTES) . '</a>';
                                }
                            } else {
                                $request_docs = "";
                                $assign_mr = "";
                            }

                            echo '<tr>' .
                                '<td><div><strong><span class="fa fa-user fa-square  ' . htmlspecialchars($abstracts_publications_badge_text[$req['request_type']], ENT_QUOTES) . '"></span> ' . htmlspecialchars($requestType[$req['request_type']], ENT_QUOTES) . '</strong> ' . filter_tags($req_type) . '</div>' .
                                '<div style="padding-top:5px;">by <a href="mailto:' . $req['contact_email'] . '">' . $req['contact_name'] . '</a>' . $region . '</div>' .
                                '<div style="padding-top:5px;">Due on: ' . filter_tags($array_dates['text']) . '</div></td>' .
                                '<td><a href="' . $module->getUrl("index.php",true) . "&option=hub&record=" . $req['request_id'] . '" target="_blank">' . htmlspecialchars($req['request_title'], ENT_QUOTES) . '</a></td>' .
                                '<td><div>' . $finalize_review . '</div></td>' .
                                '<td><div>' . $request_docs . '</div></td>' .
                                '<td><div>' . $assign_mr . '</div></td>' .
                                '</tr>';
                        }
                    }
                    if (!$any_request_found) {
                        ?>
                        <tbody>
                        <tr>
                            <td class="ps-3"><span><em>No requests available</em></span></td>
                        </tr>
                        </tbody>
                        <?php
                    }
                } else { ?>
                    <tbody>
                    <tr>
                        <td class="ps-3"><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="card-title d-flex justify-content-between align-items-center">
                <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse_req_finalized" role="button" aria-expanded="true" aria-controls="collapse_req_finalized">
                    Recently Completed Requests
                    <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="comment-arrow"></i>
                </a>
            </h6>
        </div>

        <div id="collapse_req_finalized" class="table-responsive collapse show table-no-borders">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                <?php
                $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null, null, null, null, false, false, false, "[finalize_y] = '1' AND [approval_y] = '1'");
                $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $pidsArray['RMANAGER']);
                ArrayFunctions::array_sort_by_column($requests, "due_d");
                $any_request_found = false;
                if ($requests != "") {
                    echo '<colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                     </colgroup>';

                    echo '<thead>' .
                        '<tr>' .
                        '<th class="request_grid_dued sorted_class ps-3" data-sorted="true" data-sorted-direction="descending" style="width: 232px;">Request</th>' .
                        '<th class="request_grid_title sorted_class d-none d-sm-table-cell" style="width: 461px;">Title</th>' .
                        '<th class="request_grid_actions" data-sortable="false" style="width: 146px;"></th>' .
                        '<th class="request_grid_actions" data-sortable="false" style="width: 146px;"></th>' .
                        '<th class="request_grid_actions" data-sortable="false" style="width: 146px;"></th>' .
                        '</tr></thead>';

                    $extra_days = ' + ' . $settings['recentfinalreq_expiration'] . " days";

                    foreach ($requests as $req) {
                        $expire_date = date('Y-m-d', strtotime($req['workflowcomplete_d'] . $extra_days));
                        if ($req['workflowcomplete_d'] != "" && strtotime($expire_date) >= strtotime(date('Y-m-d'))) {
                            $any_request_found = true;
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "finalization_of_request", "");
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link['hash']) . "&modal=modal";
                            $passthru_link_doc = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "final_docs_request_survey", "");
                            $survey_link_doc = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link_doc['hash']) . "&modal=modal";
                            $passthru_link_mr = $module->resetSurveyAndGetCodes($pidsArray['RMANAGER'], $req['request_id'], "tracking_number_assignment_survey", "");
                            $survey_link_mr = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link_mr['hash']) . "&modal=modal";

                            $req_type = "";
                            if(!empty( $req['assoc_concept'])){
                                $req_type = "(".getReqAssocConceptLink($module, $pidsArray, $req['assoc_concept'], "") .")";
                            }else if( !empty( $req['mr_temporary'])){
                                $req_type = "(".$req['mr_temporary'] .")";
                            }

                            $person_region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $req['contact_region']), array('region_code'))[0]['region_code'];
                            $region = "";
                            if ($person_region_code != "") {
                                $region = " (" . $person_region_code . ")";
                            }

                            $finalize_review_text = ($settings['admintext3'] == "") ? $defaultValuesSettings['admintext3'] : $settings['admintext3'];
                            $request_docs_text = ($settings['admintext4'] == "") ? $defaultValuesSettings['admintext4'] : $settings['admintext4'];
                            $assign_mr_text = ($settings['admintext5'] == "") ? $defaultValuesSettings['admintext5'] : $settings['admintext5'];

                            if ($req['finalize_y'] != "") {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-light btn-sm"><span class="fa fa-check-square text-approved"></span> ' . htmlspecialchars($finalize_review_text, ENT_QUOTES) . '</a>';
                            } else {
                                $finalize_review = '<a href="#" onclick="editIframeModal(\'hub-modal-finalize\',\'redcap-finalize-frame\',\'' . $survey_link . '\',\'' . $finalize_review_text . '\');" class="btn btn-outline-secondary btn-sm"><i class="fa fa-legal fa-fw" aria-hidden="true"></i> ' . htmlspecialchars($finalize_review_text, ENT_QUOTES) . '</a>';
                            }
                            if ($req['request_type'] == '1' || $req['request_type'] == '5') {
                                if ($req['author_doc'] != "") {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-light btn-sm"><span class="fa fa-check-square text-approved"></span> ' . htmlspecialchars($request_docs_text, ENT_QUOTES) . '</a>';
                                } else {
                                    $request_docs = '<a href="#" onclick="editIframeModal(\'hub-modal-doc\',\'redcap-doc-frame\',\'' . $survey_link_doc . '\',\'' . $request_docs_text . '\');" class="btn btn-outline-secondary btn-sm "><i class="fa fa-file fa-fw" aria-hidden="true"></i> ' . htmlspecialchars($request_docs_text, ENT_QUOTES) . '</a>';
                                }
                                if ($req['mr_assigned'] != "" && $req['finalconcept_doc'] != "" && $req['finalconcept_pdf'] != "") {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-light btn-sm"><span class="fa fa-check-square text-approved"></span> ' . htmlspecialchars($assign_mr_text, ENT_QUOTES) . '</a>';
                                } else {
                                    $assign_mr = '<a href="#" onclick="editIframeModal(\'hub-modal-mr\',\'redcap-mr-frame\',\'' . $survey_link_mr . '\',\'' . $assign_mr_text . '\');" class="btn btn-outline-secondary btn-sm"><i class="fa fa-hashtag fa-fw" aria-hidden="true"></i> ' . htmlspecialchars($assign_mr_text, ENT_QUOTES) . '</a>';
                                }
                            } else {
                                $request_docs = "";
                                $assign_mr = "";
                            }

                            echo '<tr>' .
                                '<td><div><strong><span class="fa fa-user fa-square  ' . htmlspecialchars($abstracts_publications_badge_text[$req['request_type']], ENT_QUOTES) . '"></span> ' . htmlspecialchars($requestType[$req['request_type']], ENT_QUOTES) . '</strong> ' . filter_tags($req_type) . '</div>' .
                                '<div style="padding-top:5px;">by <a href="mailto:' . $req['contact_email'] . '">' . $req['contact_name'] . '</a>' . htmlspecialchars($region, ENT_QUOTES) . '</div>' .
                                '<div style="padding-top:5px;">Completed on: ' . $req['workflowcomplete_d'] . '</div></td>' .
                                '<td><a href="' . $module->getUrl("index.php", true) . "&option=hub&record=" . $req['request_id'] . '" target="_blank">' . htmlspecialchars($req['request_title'], ENT_QUOTES) . '</a></td>' .
                                '<td><div>' . $finalize_review . '</div></td>' .
                                '<td><div>' . $request_docs . '</div></td>' .
                                '<td><div>' . $assign_mr . '</div></td>' .
                                '</tr>';
                        }
                    }
                    if (!$any_request_found) {
                        ?>
                        <tbody>
                        <tr>
                            <td class="ps-3"><span><em>No requests available</em></span></td>
                        </tr>
                        </tbody>
                        <?php
                    }
                } else { ?>
                    <tbody>
                    <tr>
                        <td class="ps-3"><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>

    <!-- MODAL FINALIZE-->
    <div class="modal fade" id="hub-modal-finalize" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Finalize Request</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-finalize-frame" message="F" name="redcap-finalize-frame" src="" style="border: none; height: 810px; width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hub-modal-doc" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Docs</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-doc-frame" message="D" name="redcap-doc-frame" src="" style="border: none; height: 810px; width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hub-modal-mr" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign MR</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-mr-frame" message="M" name="redcap-mr-frame" src="" style="border: none; height: 810px; width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT PROCESS-->
    <div class="modal fade" id="hub_process_survey" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-edit-frame-admin" message="S" name="redcap-edit-frame-admin" src="" style="border: none; height: 810px; width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Update Publications -->
    <div class="modal fade" id="modal-publications-confirmation" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Publications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <span>Are you sure you want to update publications?</span>
                    <br>
                    <span style="color:red;">This will create a new JSON and prevent the cron from automatically running tonight until the next day.</span>
                    <div style="display:none" id="pubsSpinner">
                        <div style="padding-top: 20px;">
                            <div class="alert alert-success">
                                <i class="fa fa-solid fa-spinner fa-spin"></i> Updating... <br/>
                                This process may take some time. Once the file is updated, a <span class="fa fa-solid fa-star"></span> icon will appear on your tab for the first three hours.<br/>
                                You can safely close this window now.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php
                    $redcap_csrf_token = '&redcap_csrf_token=' . $module->getCSRFToken();
                    ?>
                    <a href="#" onclick='runPubsCron("<?=$module->getUrl("hub/hub_admin_update_publications_AJAX.php", true)?>", <?=json_encode($redcap_csrf_token)?>)' class="btn btn-success" id="btndataPubForm">Continue</a>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-0">
        <!-- Update Publications -->
        <div class="col-lg-8 pe-3">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="fa fa-refresh" aria-hidden="true"></i> Update Publications
                        <?php
                        // Check if Publications has been updated within the last 3 hours
                        $updatePublicationsLastUpdate = false;
                        if($module->getProjectSetting('hub-publications-updated') && !empty($module->getProjectSetting('hub-publications-updated-date'))) {
                            $currentDateTime = new \DateTime("now");
                            $givenDateTime = new \DateTime($module->getProjectSetting('hub-publications-updated-date'));

                            // Calculate the difference in hours
                            $interval = $currentDateTime->diff($givenDateTime);

                            // Check if the difference is no more than 3 hours
                            if ($interval->h < 3 && $interval->days == 0) {
                                $updatePublicationsLastUpdate = true;
                            }else{
                                //More than 3h passes, we reset data
                                $module->setProjectSetting('hub-publications-updated', false);
                            }
                        }
                        if($module->getProjectSetting('hub-publications-updated') && $updatePublicationsLastUpdate) {?>
                            <span class="fa fa-solid fa-star text-primary"></span>
                        <?php } ?>
                    </h6>
                </div>
                <div class="table-responsive table-no-borders">
                    <table class="table table_requests sortable-theme-bootstrap" style="font-size: 14px;">
                        <tr>
                            <td class="ps-3">
                                On clicking the button, the publications code will run creating a new JSON file and updating the content.
                                <p><i class="fa fa-refresh" aria-hidden="true"></i> Last update on <span class="fw-bold"><?=$settings['publications_lastupdate']?></span></p>

                            </td>
                            <td>
                                <a href="#" onclick="$('#modal-publications-confirmation').modal('show');" class="btn btn-primary float-end">
                                    <span class="fa fa-refresh"></span> Update
                                </a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Hub Users -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title d-flex justify-content-between align-items-center">
                        Hub Users
                        <a href="<?=$module->getUrl('index.php', true).'&option=usr'?>">View more</a>
                    </h6>
                </div>
                <div class="table-responsive collapse show table-no-borders" id="hubUsersTable">
                    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                        <?php
                        $q = $module->query("SELECT a.record, max(if(a.field_name = ?, a.value, '')) as active_y, max(if(a.field_name = ?, a.value, '')) as last_requested_token_d, max(if(a.field_name = ?, a.value, NULL)) as email, max(if(a.field_name = ?, a.value, NULL)) as person_region, CONCAT_WS(' ', max(if(a.field_name = ?, a.value, NULL)), max(if(a.field_name = ?, a.value, NULL))) as name FROM ".\Vanderbilt\HarmonistHubPublicExternalModule\getDataTable($pidsArray['PEOPLE'])." a INNER JOIN ".\Vanderbilt\HarmonistHubPublicExternalModule\getDataTable($pidsArray['PEOPLE'])." b ON (b.value is not null and b.field_name = ? AND a.record = b.record and a.project_id = b.project_id) WHERE a.project_id = ? GROUP BY a.record ORDER BY b.value DESC LIMIT 15", ['active_y', 'last_requested_token_d', 'email', 'person_region', 'firstname', 'lastname', 'last_requested_token_d', $pidsArray['PEOPLE']]);
                        while ($row = $q->fetch_assoc()) {
                            $logins[] = $row;
                        }
                        if (!empty($logins)) {
                            echo '<thead>' .
                                '<tr>' .
                                '<th class="sorted_class ps-3" data-sorted-direction="descending">Name</th>' .
                                '<th class="sorted_class" data-sorted-direction="descending">Region</th>' .
                                '<th class="sorted_class" data-sorted="true" data-sorted-direction="descending" style="width: 150px;">Last Access Link</th>' .
                                '<th class="sorted_class" data-sorted-direction="descending">Level</th>' .
                                '<th class="sorted_class" data-sortable="false" data-sorted="false">REDCap</th>' .
                                '</tr>' .
                                '</thead>';

                            $harmonist_regperm = $module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']);
                            foreach ($logins as $login) {
                                if ($login['active_y'] != "0") {
                                    $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $login['person_region']), array('region_code'))[0]['region_code'];
                                    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $login['record']))[0];
                                    $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $module->escape($pidsArray['PEOPLE']) . "&arm=1&id=" . $module->escape($login['record']);

                                    echo '<tr><td class="ps-3"><a href="mailto:' . htmlspecialchars($login['email'], ENT_QUOTES) . '">' . htmlspecialchars($login['name'], ENT_QUOTES) . '</a></td>' .
                                        '<td style="text-align: center;">' . htmlspecialchars($region_code, ENT_QUOTES) . '</td>' .
                                        '<td>' . htmlspecialchars($login['last_requested_token_d'], ENT_QUOTES) . '</td>' .
                                        '<td>' . htmlspecialchars($harmonist_regperm[$people['harmonist_regperm']], ENT_QUOTES) . '</td>' .
                                        '<td style="text-align: center;"><a href="' . $gotoredcap . '" target="_blank"> <img src="' . $module->getUrl('img/REDCap_R_logo_transparent.png') . '" style="width: 18px;" alt="REDCap Logo"></a></td>';
                                }
                            }
                        } else { ?>
                            <tbody>
                            <tr>
                                <td><span><em>No logins available</em></span></td>
                            </tr>
                            </tbody>
                        <?php } ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- REDCap Links -->
        <div class="col-lg-4" id="RedcapLinks">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">REDCap Links</h6>
                </div>
                <div id="collapse3" class="table-responsive collapse show table-no-borders" aria-expanded="true">
                    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
                        <?php
                        $projectsY = \REDCap::getData($pidsArray['PROJECTS'], 'json-array', null, null, null, null, false, false, false, "[project_show_y] = '1'");
                        foreach ($projectsY as $project) {
                            $constant = arrayKeyExistsReturnValue($project, ['project_constant']);
                            $iedea_constant = arrayKeyExistsReturnValue($pidsArray, [$constant]);
                            $title = $module->framework->getProject($iedea_constant)->getTitle();
                            echo '<tr>' .
                                '<td class="ps-3"><a href="' . APP_PATH_WEBROOT_ALL . "Design/online_designer.php?pid=" . $iedea_constant . '" target="_blank">' . htmlspecialchars($title, ENT_QUOTES) . '</a></td>' .
                                '</tr>';
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

</script>
<?php
if($settings['session_timeout_popup'] == 2 && $settings['session_timeout_popup'] != ''){
    echo REDCapManagement::renderLogoutModal($module, $settings, 'logout_modal.html.twig');
}
?>

