<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$recordId = htmlentities($_REQUEST['record'], ENT_QUOTES);
$concept = $module->getConceptModel()->fetchConcept($recordId, null, true);

$abstracts_publications_type = $module->getChoiceLabels('output_type', $pidsArray['HARMONIST']);
$abstracts_publications_badge = array("1" => "badge-manuscript", "2" => "badge-abstract", "3" => "badge-poster", "4" => "badge-presentation", "5" => "badge-report", "99" => "badge-other");
$harmonist_perm_edit_concept = ($currentUser['harmonist_perms___3'] == 1) ? true : false;
?>

<script>
    $(document).ready(function() {
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");

        $('#linked_docs').DataTable({
            "order": [0, "desc"],
            "bFilter" : false,
            "bLengthChange": false,
            "bInfo": false,
            "bPaginate": false
        });
    });
</script>
<div class="container">
    <div class="alert alert-success d-none col-12" id="succMsgContainer">
        If you've made any changes, they have been saved.
    </div>
    <?php
    if (isset($_REQUEST['message'])) {
        $message = $module->getMessageHandler()->fetchMessage('concept', $_REQUEST['message']);
        if (!empty($message)) {
            echo '<div class="alert alert-success col-12" id="succMsgContainer">' . $message . '</div>';
        }
    }
    ?>

    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true).'&option=cpt'?>">&lt; Back to Concepts</a>
    </div>
    <?php if($concept != "") {?>
    <h3 class="concepts-title-title"><?=$concept->getConceptId()?></h3>

    <?php if($isAdmin || $harmonist_perm_edit_concept){
        $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $recordId, "concept_sheet", "");
        $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=".$module->escape($passthru_link['hash'])."&modal=modal";

        $gotoredcap = htmlentities(APP_PATH_WEBROOT_ALL."DataEntry/record_home.php?pid=".$pidsArray['HARMONIST']."&arm=1&id=".$recordId,ENT_QUOTES);

        $survey_queue_link = \REDCap::getSurveyQueueLink($recordId);
        ?>
        <div class="btn-group float-end d-none d-sm-inline-block">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                Admin <i class="bi bi-caret-down-fill"></i> <!-- Bootstrap Icon for caret -->
            </button>
            <ul class="dropdown-menu">
                <li><a href="#" class="dropdown-item" onclick="$('#hub_edit_concept').modal('show');">Edit Concept</a></li>
                <?php if ($survey_queue_link != '') { ?>
                    <li><a href="#" class="dropdown-item" onclick="$('#hub_news_pubs').modal('show');">Edit News & Pubs</a></li>
                <?php } ?>
                <li><hr class="dropdown-divider"></li>
                <li><a href="<?=$gotoredcap?>" class="dropdown-item" target="_blank">Go to REDCap</a></li>
            </ul>
        </div>

        <!-- MODAL EDIT CONCEPT -->
        <div class="modal fade" id="hub_edit_concept" tabindex="-1" aria-labelledby="editConceptLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editConceptLabel">Edit Concept</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded">
                        <iframe class="commentsform" id="redcap-concept-frame" message="U" name="redcap-concept-frame" src="<?=$survey_link?>" style="border: none;height: 810px;width: 100%;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function refreshModal(id,link){
                $('#'+id).attr('src', '');
                document.getElementById(id).contentWindow.location.reload(); //Reloads the Iframe
                $('#'+id).attr('src', link);
            }
        </script>

        <!-- MODAL NEWS PUBS -->
        <div class="modal fade" id="hub_news_pubs" tabindex="-1" aria-labelledby="newsPubsLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newsPubsLabel">Edit Concept Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" value="0" id="comment_loaded_newspubs">
                        <iframe class="commentsform" id="redcap-pubs-frame" name="redcap-pubs-frame" src="<?=$survey_queue_link?>" style="border: none; height: 515px; width: 100%;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" onclick="refreshModal('redcap-pubs-frame', '<?=$survey_queue_link?>');">Back to Queue</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <p class="hub-title concepts-title-title" style="font-weight: normal"><?=$concept->getConceptTitle()?></p>

    <table class="table table_requests sortable-theme-bootstrap" data-sortable>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Working Group:</strong></div>
            <div class="col-md-6 col-sm-12"><?=$concept->getWorkingGroup();?> </div>
            <div class="col-md-4"><strong>Start Date: </strong><?=$concept->getStartDate();?> </span></div>
        </div>
        <div class="row request">
            <div class="col-md-2 col-sm-12"><strong>Contact:</strong> </div>
            <div class="col-md-6 col-sm-12"><?=$concept->getContact();?></div>
            <div class="col-md-4"><strong>Status: </strong><?=$concept->getStatus();?></div>
        </div>
        <div class="row request">
            <div class="col-md-2"><strong>Participants:</strong></div>
            <div class="col-md-6">
                <?php
                echo $concept->getParticipants();
                if(array_key_exists('writinggroup_opt', $settings) && ($settings['writinggroup_opt'] == "2" || ($settings['writinggroup_opt'] == "1" && $isAdmin))){
                    echo " (<a href='".$module->getUrl('index.php').'&NOAUTH&option=cwg&record='.$recordId."'>View Writing Group</a>)";
                }
                ?>
            </div>
            <div class="col-md-4" style="display: flex">
                <div>
                    <strong>Tags:&nbsp;</strong>
                </div>
                <div>
                    <?php
                    $noTags = true;
                    $concept_tags = $module->getChoiceLabels('concept_tags', $pidsArray['HARMONIST']);
                    foreach ($concept->getConceptTags() as $tag=>$value){
                        if($value == 1) {
                            $noTags = false;
                            echo '<span  class="badge bg-primary me-1"> ' . $concept_tags[$tag].'</span>';
                        }
                    }
                    if($noTags){
                        echo '<span class="me-1"><em>None</em></span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </table>

    <?php
    if ((!empty($concept) && $concept->getAdminupdateD() != "" && count($concept->getAdminupdateD())>0) || (!empty($concept) && $concept->getUpdateD() != "" && count($concept->getUpdateD())>0)) {
        ?>

        <div>
            <div class="table-responsive table-no-borders">
                <table class="table table-hover no-footer border-bottom-0 border-top-0 dataTable pb-3" data-sortable id="table_projectUpdate">
                    <thead class="table-light">
                    <tr>
                        <th class="archive_grid_dued sorted_class">Date</th>
                        <th class="archive_grid_dued sorted_class">Project Update</th>
                        <th class="archive_grid_dued">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $project_status = $module->getChoiceLabels('project_status', $pidsArray['HARMONIST']);
                    $admin_status = $module->getChoiceLabels('admin_status', $pidsArray['HARMONIST']);

                    if ($concept->getAdminupdateD() == "" && $concept->getUpdateD() == "") {
                        echo '<tr><td colspan="3" class="text-center">No updates available</td></tr>';
                    } else if ($concept->getAdminupdateD() != "" && $concept->getUpdateD() != "") {
                        $adminUpdateD = [];
                        foreach ($concept->getAdminupdateD() as $aindex => $adminupdate) {
                            $adminUpdateD[$aindex . "-admin"] = $adminupdate;
                        }
                        $updateD = [];
                        foreach ($concept->getUpdateD() as $uindex => $update) {
                            $updateD[$uindex . "-project"] = $update;
                        }
                        $allUpdates = array_merge($updateD, $adminUpdateD);
                        # Sort elements by most recent date Admin
                        arsort($allUpdates);
                        foreach ($allUpdates as $index => $value) {
                            $index_data = explode('-', $index);
                            $index0 = arrayKeyExistsReturnValue($index_data,[0]);
                            $index1 = arrayKeyExistsReturnValue($index_data,[1]);
                            $statusVarName = $index1 . "_status";
                            $statusArray = isset($statusVarName) && is_array($statusVarName) ? $statusVarName : [];
                            $statusKey = arrayKeyExistsReturnValue($concept->{"get" . ucfirst($index1) . "Status"}(), [$index0]);
                            echo '<tr>';
                            echo '<td style="width: 10%;">' . htmlspecialchars($value, ENT_QUOTES) . '</td>';
                            echo '<td>' . filter_tags((string) arrayKeyExistsReturnValue($concept->{"get" . ucfirst($index1) . "Update"}(), [$index0])) . '</td>';
                            echo '<td style="width: 25%;">' . htmlspecialchars((string) arrayKeyExistsReturnValue($statusArray, [$statusKey]), ENT_QUOTES) . '</td>';
                            echo '</tr>';
                        }
                    } else if ($concept->getAdminupdateD() != "" && $concept->getUpdateD() == "") {
                        asort($concept->getAdminupdateD());
                        foreach ($concept->getAdminupdateD() as $index => $value) {
                            echo '<tr>';
                            echo '<td style="width: 10%;">' . htmlspecialchars($value, ENT_QUOTES) . '</td>';
                            echo '<td>' . filter_tags(arrayKeyExistsReturnValue($concept->getAdminUpdate(), [$index])) . '</td>';
                            echo '<td style="width: 25%;">' . htmlspecialchars(arrayKeyExistsReturnValue($admin_status, [arrayKeyExistsReturnValue($concept->getAdminStatus(), [$index])]), ENT_QUOTES) . '</td>';
                            echo '</tr>';
                        }
                    } else if ($concept->getAdminupdateD() == "" && $concept->getUpdateD() != "") {
                        asort($concept->getUpdateD());
                        foreach ($concept->getUpdateD() as $index => $value) {
                            echo '<tr>';
                            echo '<td style="width: 10%;">' . htmlspecialchars($value, ENT_QUOTES) . '</td>';
                            echo '<td>' . filter_tags(arrayKeyExistsReturnValue($concept->getProjectUpdate(), [$index])) . '</td>';
                            echo '<td style="width: 25%;">' . htmlspecialchars(arrayKeyExistsReturnValue($project_status, [arrayKeyExistsReturnValue($concept->getProjectStatus(), [$index])]), ENT_QUOTES) . '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } ?>


    <div class="card">
        <div class="card-header">
            <h6 class="card-title d-flex align-items-center">
                <a class="collapseText d-flex align-items-center flex-grow-1" data-bs-toggle="collapse" href="#collapse_concept" role="button" aria-expanded="true" aria-controls="collapse_concept">
                    Concept Sheet
                </a>

                <?php
                if (!empty($concept->getConceptFile())) {
                    $fileData = $concept->createConceptFile($concept->getConceptFile(), $currentUser['record_id'], $secret_key, $secret_iv);
                    ?>
                    <a href="<?=$fileData->getDownloadLink();?>" target="_blank"><?=$fileData->getIcon();?> Download PDF</a>
                <?php } ?>

                <!-- Chevron Icon -->
                <a class="collapseText d-flex align-items-center toggle-icon" data-bs-toggle="collapse" href="#collapse_concept" role="button" aria-expanded="true" aria-controls="collapse_concept">
                    <i class="fa fa-chevron-down ms-3" aria-hidden="true" id="comment-arrow"></i>
                </a>
            </h6>
        </div>
        <div id="collapse_concept" class="card-body p-0 collapse show table-no-borders" aria-expanded="true">
            <?php if (!empty($fileData)) { ?>
                <iframe class="commentsform" id="redcap-frame" src="<?=$fileData->getPdfPath();?>" style="border: none; width: 100%; height: 500px;"></iframe>
            <?php } else { ?>
                <table class="table table-hover table-bordered">
                    <tbody>
                    <tr>
                        <td class="ps-3"><em>No document available</em></td>
                    </tr>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>

    <?php if($routes->canAccessDatahub()){?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title d-flex justify-content-between align-items-center">
                    <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse4" role="button" aria-expanded="true" aria-controls="collapse4">
                        Data Requests for <?=$concept->getConceptId()?>
                        <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="comment-arrow"></i>
                    </a>
                </h6>
            </div>

            <div id="collapse4" class="collapse show table-responsive table-no-borders" style="overflow-y: hidden;">
                <table class="table table-hover sortable-theme-bootstrap" data-sortable>
                    <?php
                    $q = $module->query("SELECT record FROM " . getDataTable($pidsArray['HARMONIST']) . " WHERE field_name = ? AND value IS NOT NULL AND record = ? AND project_id = ?", ['datasop_file', $recordId, $pidsArray['HARMONIST']]);

                    $params = [
                            'project_id' => $pidsArray['SOP'],
                            'return_format' => 'array',
                            'filterLogic' => "[sop_active] = '1' and [sop_visibility] = '2' and [sop_concept_id] = " . $recordId,
                            'filterType' => "RECORD"
                    ];
                    $RecordSetSOP = \REDCap::getData($params);
                    $data_requests = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP']);
                    ArrayFunctions::array_sort_by_column($data_requests, 'sop_updated_dt', SORT_DESC);

                    if (!empty($data_requests) || $q->num_rows > 0) {
                        echo getDataCallConceptsHeader($pidsArray['REGIONS'], $currentUser['person_region'], $settings['vote_grid']);
                        foreach ($data_requests as $sop) {
                            echo getDataCallConceptsRow(
                                    $module,
                                    $pidsArray,
                                    $sop,
                                    $isAdmin,
                                    $currentUser,
                                    $secret_key,
                                    $secret_iv,
                                    $settings['vote_grid'],
                                    '',
                                    ''
                            );
                        }
                        while ($rowConcept = db_fetch_assoc($q)) {
                            $params = [
                                    'project_id' => $pidsArray['SOP'],
                                    'return_format' => 'array',
                                    'filterLogic' => "[sop_concept_id] = " . $rowConcept['record'],
                                    'filterType' => "RECORD"
                            ];
                            $RecordSetSOP = \REDCap::getData($params);
                            $data_requests_old = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP']);
                            if (empty($data_requests_old)) {
                                echo getDataCallConceptsRow($module, $pidsArray, $sop, $isAdmin, $currentUser, $secret_key, $secret_iv, $settings['vote_grid'], $rowConcept['record'], "1");
                            }
                        }
                    } else { ?>
                        <tbody>
                        <tr>
                            <td colspan="3" class="ps-3"><em>No data requests available</em></td>
                        </tr>
                        </tbody>
                    <?php } ?>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title d-flex justify-content-between align-items-center">
                    <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse_dataReqUp" role="button" aria-expanded="true" aria-controls="collapse_dataReqUp">
                        Data Uploads
                        <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="comment-arrow"></i>
                    </a>
                </h6>
            </div>
            <div id="collapse_dataReqUp" class="collapse show">
                <div class="table-responsive table-no-borders">
                    <table class="table sortable-theme-bootstrap" data-sortable id="sortable_table">
                        <colgroup>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                        </colgroup>
                        <?php
                        $params = [
                                'project_id' => $pidsArray['DATAUPLOAD'],
                                'return_format' => 'json-array',
                                'filterLogic' => "[data_assoc_concept] = " . $recordId,
                                'filterType' => "RECORD"
                        ];
                        $uploads = \REDCap::getData($params);
                        if (!empty($uploads)) { ?>
                            <thead class="table-light">
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
                            foreach ($uploads as $up) {
                                $params = [
                                        'project_id' => $pidsArray['PEOPLE'],
                                        'return_format' => 'json-array',
                                        'records' => [$up['data_upload_person']]
                                ];
                                $people = arrayKeyExistsReturnValue(\REDCap::getData($params),[0]);
                                $contact_person = "<a href='mailto:" . $people['email'] . "'>" . $people['firstname'] . " " . $people['lastname'] . "</a>";

                                $params = [
                                        'project_id' => $pidsArray['REGIONS'],
                                        'return_format' => 'json-array',
                                        'records' => [$up['data_upload_region']],
                                        'fields' => ['region_code']
                                ];
                                $region_code = arrayKeyExistsReturnValue(\REDCap::getData($params),[0,'region_code']);

                                $status = '<span class="badge bg-success">Available</span>';
                                if ($up['deleted_y'] == '1') {
                                    $status = '<span class="badge bg-danger">Deleted</span>';
                                }

                                echo "<tr>";
                                echo "<td width='20%'>" . htmlspecialchars($up['responsecomplete_ts'], ENT_QUOTES) . "</td>" .
                                        "<td width='20%'>" . $contact_person . "</td>" .
                                        "<td width='40%'>" . htmlspecialchars($up['upload_notes'], ENT_QUOTES) . "</td>" .
                                        "<td width='10%'>" . htmlspecialchars($region_code, ENT_QUOTES) . "</td>" .
                                        "<td width='10%'>" . filter_tags($status) . "</td>";
                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                        <?php } else { ?>
                            <tbody>
                            <tr>
                                <td colspan="5" class="ps-3"><em>No data uploads.</em></td>
                            </tr>
                            </tbody>
                        <?php } ?>
                    </table>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
    $harmonist_perm = ($currentUser['harmonist_perms___10'] == 1) ? true : false;
    $can_edit_pub = UserEditConditions::canUserEditData($isAdmin, $currentUser['record_id'], $concept->getContactLink(), $concept->getContact2Link(), $harmonist_perm);
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title d-flex align-items-center">
                <!-- Collapse Text -->
                <a class="collapseText d-flex align-items-center flex-grow-1" data-bs-toggle="collapse" href="#collapse_publications" role="button" aria-expanded="true" aria-controls="collapse_publications">
                    Abstracts &amp; Publications
                </a>

                <?php
                if ($can_edit_pub) {
                    $output_link = $module->getSurveyLinkNewInstance("outputs", $recordId, $pidsArray['HARMONIST']);
                    ?>
                    <!-- New Output Button -->
                    <a href="#" onclick="$('#hub_new_output').modal('show');">
                        <span><i class="fa fa-plus"></i> New Output</span>
                    </a>
                    <?php
                }
                ?>

                <!-- Chevron Icon -->
                <a class="collapseText d-flex align-items-center toggle-icon" data-bs-toggle="collapse" href="#collapse_publications" role="button" aria-expanded="true" aria-controls="collapse_publications">
                    <i class="fa fa-chevron-down ms-3" aria-hidden="true" id="comment-arrow"></i>
                </a>

                <?php
                if ($can_edit_pub) {
                    ?>
                    <!-- Modal New Output -->
                    <div class="modal fade" id="hub_new_output" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fa fa-plus"></i> New Output</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded">
                                    <iframe class="commentsform" id="redcap-new-output-frame" name="redcap-new-output-frame" message="O" src="<?=$output_link?>" style="border: none; height: 810px; width: 100%;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </h6>
        </div>

        <div id="collapse_publications" class="collapse show table-responsive table-no-borders">
            <table class="table table-hover sortable-theme-bootstrap" data-sortable id="abstracts">
                <?php
                if (!empty($concept->getOutputType())) {
                    $header = '<colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        </colgroup>';

                    $header .= '<thead class="table-light">' .
                            '<tr>' .
                            '<th class="sorted_class" data-sorted="true" style="width:5%;" data-sorted-direction="descending">Year</th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:20%;"><span style="display:block">Journal /</span><span>Conference</span></th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:40%;">Title and Authors</th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:20%;">Available</th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:10%;">File</th>';
                    if ($isAdmin) {
                        $header .= '<th class="sorted_class" style="width:5%;text-align: center;" data-sorted="false"><i class="fa fa-cog"></i></th>';
                    }
                    echo '</tr></thead>' . $header;

                    echo '<tbody>';

                    // Order by year
                    $output_year = $concept->getOutputYear();
                    if (!empty($output_year) && is_array($output_year))
                        asort($output_year);
                    foreach ($output_year as $index => $value) {
                        echo '<tr><td>' . htmlspecialchars($output_year[$index], ENT_QUOTES) . '</td>' .
                                '<td>' . filter_tags($concept->getOutputVenue()[$index]) . '</td>' .
                                '<td><span class="badge '.$abstracts_publications_badge[$concept->getOutputType()[$index]].'">' . htmlspecialchars($abstracts_publications_type[$concept->getOutputType()[$index]], ENT_QUOTES) . '</span> <strong>' . filter_tags($concept->getOutputTitle()[$index]) . '</strong> </br><span class="abstract_text">' . filter_tags($concept->getOutputAuthors()[$index]) . '</span></td>';

                        $available = '';
                        if (!empty($concept->getOutputCitation()[$index])) {
                            $available = filter_tags($concept->getOutputCitation()[$index]);
                        }
                        if (!empty($concept->getOutputPmcid()[$index])) {
                            $available .= 'PMCID: <a href="https://www.ncbi.nlm.nih.gov/pmc/articles/' . htmlspecialchars($concept->getOutputPmcid()[$index], ENT_QUOTES) . '" target="_blank">' . htmlspecialchars($concept->getOutputPmcid()[$index], ENT_QUOTES) . '<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                        }
                        if (!empty($concept->getOutputUrl()[$index])) {
                            $available .= '&nbsp;<a href="' . htmlspecialchars($concept->getOutputUrl()[$index], ENT_QUOTES) . '" target="_blank">Link<i class="fa fa-fw fa-external-link" aria-hidden="true"></i></a>';
                        }

                        echo '<td>' . $available . '</td>';

                        $file = '';
                        if (arrayKeyExistsReturnValue($concept->getOutputFile(), [$index]) != "") {
                            $file = getFileLink($module, $pidsArray['PROJECTS'], $concept->getOutputFile()[$index], '1', '', $secret_key, $secret_iv, $currentUser['record_id'], "");
                        }
                        echo '<td>' . $file . '</td>';

                        if ($can_edit_pub) {
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $concept->getRecordId(), "outputs", "", $index);
                            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link['hash']);
                            echo '<td><button class="btn btn-outline-secondary open-codesModal" onclick="$(\'#edit_title\').html(\'Edit Publication\');editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\', \'\',\'P\');"><i class="fa fa-pencil"></i></button></td>';
                        }

                        echo '</tr>';
                    }
                    echo '</tbody>';
                } else {
                    echo "<tr><td colspan='6' class='ps-3'><em>No Abstracts & Publications available</em></td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
    <!-- MODAL EDIT CONCEPT -->
    <div class="modal fade" id="hub_edit_pub" tabindex="-1" aria-labelledby="edit_title" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="edit_title">Edit Publication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe class="commentsform" id="redcap-edit-frame" message="" name="redcap-edit-frame" src="" style="border: none; height: 810px; width: 100%;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    $harmonist_perm = ($currentUser['harmonist_perms___10'] == 1) ? true : false;
    $can_edit_linked_doc = UserEditConditions::canUserEditData($isAdmin, $currentUser['record_id'], $concept->getContactLink(), $concept->getContact2Link(), $harmonist_perm);
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title d-flex align-items-center">
                <!-- Collapse Text -->
                <a class="collapseText d-flex align-items-center flex-grow-1" data-bs-toggle="collapse" href="#collapse_linked_documents" role="button" aria-expanded="true" aria-controls="collapse_linked_documents">
                    Linked Documents
                </a>

                <?php
                if ($can_edit_linked_doc) {
                    $linked_doc_link = $module->getSurveyLinkNewInstance("linked_documents", $recordId, $pidsArray['HARMONIST']);
                    ?>
                    <!-- New Output Button -->
                    <a href="#" onclick="$('#hub_new_linked_doc').modal('show');">
                        <span><i class="fa fa-plus"></i> New File</span>
                    </a>
                    <?php
                }
                ?>

                <!-- Chevron Icon -->
                <a class="collapseText d-flex align-items-center toggle-icon" data-bs-toggle="collapse" href="#collapse_linked_documents" role="button" aria-expanded="true" aria-controls="collapse_linked_documents">
                    <i class="fa fa-chevron-down ms-3" aria-hidden="true" id="comment-arrow"></i>
                </a>


                <?php
                if ($can_edit_linked_doc) {
                    ?>
                    <!-- Modal New File -->
                    <div class="modal fade" id="hub_new_linked_doc" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fa fa-plus"></i> New File</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" value="0" id="comment_loaded">
                                    <iframe class="commentsform" id="redcap-new-output-frame" name="redcap-new-output-frame" message="D" src="<?=$linked_doc_link?>" style="border: none; height: 810px; width: 100%;"></iframe>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </h6>
        </div>

        <div id="collapse_linked_documents" class="collapse show table-responsive table-no-borders">
            <table class="table table-hover sortable-theme-bootstrap border-bottom-0" data-sortable id="linked_docs">
                <?php
                if (!empty($concept->getDocTitle())) {
                    $header = '<colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                    </colgroup>';

                    $header .= '<thead class="table-light">' .
                            '<tr>' .
                            '<th class="sorted_class" data-sorted="true" style="width:5%;" data-sorted-direction="descending">Upload Date</th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:20%;">File Title</th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:40%;">Description</th>' .
                            '<th class="sorted_class" data-sorted="false" style="width:20%;">File</th>';
                    if ($isAdmin) {
                        $header .= '<th class="sorted_class" style="width:5%; text-align: center;" data-sorted="false"><i class="fa fa-cog"></i></th>';
                    }
                    echo '</tr></thead>' . $header;

                    echo '<tbody>';
                    foreach ($concept->getDochiddenY() as $linked_doc_instance => $doc_hidden_value) {
                        if ($doc_hidden_value !== "1") {
                            echo '<tr>';

                            $docUploadDt = $concept->getDocuploadDt();
                            $docTitle = $concept->getDocTitle();
                            $docDescription = $concept->getDocDescription();
                            $docFile = $concept->getDocFile();

                            $uploadDtValue = (is_array($docUploadDt) && isset($docUploadDt[$linked_doc_instance])) ? $docUploadDt[$linked_doc_instance] : '';
                            $titleValue = (is_array($docTitle) && isset($docTitle[$linked_doc_instance])) ? $docTitle[$linked_doc_instance] : '';
                            $descriptionValue = (is_array($docDescription) && isset($docDescription[$linked_doc_instance])) ? $docDescription[$linked_doc_instance] : '';
                            $fileValue = (is_array($docFile) && isset($docFile[$linked_doc_instance])) ? $docFile[$linked_doc_instance] : '';

                            echo '<td width="15%">' . htmlspecialchars($uploadDtValue, ENT_QUOTES) . '</td>';
                            echo '<td width="25%">' . htmlspecialchars($titleValue, ENT_QUOTES) . '</td>';
                            echo '<td width="50%">' . htmlspecialchars($descriptionValue, ENT_QUOTES) . '</td>';

                            $file = '';
                            if ($fileValue !== '') {
                                $file = getFileLink($module, $pidsArray['PROJECTS'], $fileValue, '1', '', $secret_key, $secret_iv, $currentUser['record_id'], "");
                            }
                            echo '<td width="5%">' . $file . '</td>';

                            if ($can_edit_linked_doc) {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $concept->getRecordId(), "linked_documents", "", $linked_doc_instance);
                                $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape($passthru_link['hash']);
                                echo '<td><button class="btn btn-outline-secondary open-codesModal" onclick="$(\'#edit_title\').html(\'Edit Linked Document\');editIframeModal(\'hub_edit_pub\',\'redcap-edit-frame\',\'' . $survey_link . '\', \'\',\'L\');"><i class="fa fa-pencil"></i></button></td>';
                            }
                            echo '</tr>';
                        }
                    }
                    echo '</tbody>';
                } else {
                    echo "<tr><td colspan='5' class='ps-3'><em>No Linked Documents available</em></td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header">
            <h6 class="card-title d-flex justify-content-between align-items-center">
                <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse3" role="button" aria-expanded="true" aria-controls="collapse3">
                    Related Requests
                    <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="comment-arrow"></i>
                </a>
            </h6>
        </div>

        <div id="collapse3" class="collapse show table-responsive table-no-borders">
            <table class="table table-hover sortable-theme-bootstrap" data-sortable>
                <?php
                $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array', null);
                $request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $pidsArray['RMANAGER'], array('approval_y' => "1", 'assoc_concept' => $concept->getRecordId()));
                $request_type_label = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
                if (!empty($request)) {
                    echo getArchiveHeader('Status');
                    ?>
                    <tbody>
                    <?php
                    foreach ($request as $req) {
                        echo getArchiveHTML(
                                $module,
                                $pidsArray,
                                $req,
                                $request_type_label,
                                $currentUser['person_region'],
                                $settings['vote_visibility']
                        );
                    }
                    ?>
                    </tbody>
                <?php } else { ?>
                    <tbody>
                    <tr>
                        <td colspan="5" class="ps-3"><em>No related requests available</em></td>
                    </tr>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<?php }else{ ?>
    <div class="alert alert-warning fade in col-md-12"><em>Concept #<?=$recordId?> is not available at this time.</em></div>
<?php } ?>
<div class="modal fade" id="hub_view_votes" tabindex="-1" aria-labelledby="modalVotesTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVotesTitle">All Votes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="allvotes"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

