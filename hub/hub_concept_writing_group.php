<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use DateTime;
use Project;
use REDCap;

$recordId = htmlentities($_REQUEST['record'], ENT_QUOTES);
$concept = $module->getConceptModel()->fetchConcept($recordId, $settings['authorship_limit']);
if ($concept != null) {
    $writingGroupMember = new WritingGroupModel($module, $pid, $concept);
    $writingGroupMemberList = $writingGroupMember->fetchAllWritingGroup();
    $canUserEdit = $concept->canUserEdit($currentUser['record_id'], $currentUser['harmonist_perms___3']);
    $docName = $writingGroupMember->fetchWritingGroupFileName($settings['hub_name']);
}
$harmonistPermEditConcept = ($currentUser['harmonist_perms___3'] == 1) ? true : false;
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Variables
        const docName = <?= json_encode($docName) ?>;
        const canEdit = <?= json_encode($canUserEdit) ?>;
        const harmonistPermEditConcept = <?= json_encode($harmonistPermEditConcept) ?>;
        const columns = [0, 1, 2, 3];

        // DataTable initialization
        const tableElement = document.getElementById('sortable_table');
        const table = $(tableElement).DataTable({
            pageLength: 50,
            dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            order: [[2, 'asc']],
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Excel',
                    title: docName,
                    exportOptions: {
                        columns: columns
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    exportOptions: {
                        columns: columns,
                        stripHtml: false
                    }
                }
            ]
        });

        // Append buttons to the options wrapper
        table.buttons().containers().appendTo('#options_wrapper');

        // Hide the columns that we use only as filters
        if (!canEdit && !harmonistPermEditConcept) {
            const columnActions = table.column(4);
            columnActions.visible(false);
        }

        // Customize the filter and buttons styling
        document.querySelector('#sortable_table_filter').style.cssText = 'float: left; padding-left: 90px; padding-top: 5px;';
        document.querySelector('.dt-buttons').style.cssText = 'float: left;';

        // Filter data based on roles
        const selectRoles = document.getElementById('selectRoles');
        selectRoles.addEventListener('change', function () {
            table.draw();
        });

        // Custom filter function for roles
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const roles = selectRoles.value;
            const columnRoles = data[2];

            if (roles !== '' && roles === columnRoles) {
                return true;
            } else if (roles === '') {
                return true;
            }

            return false;
        });

        // Modal cleanup on hide
        const editModal = document.getElementById('hub_edit_writing_group');
        editModal.addEventListener('hidden.bs.modal', function () {
            const iframe = document.getElementById('redcap-edit-frame');
            if (iframe) {
                iframe.src = ''; // Clean up iframe src
            }
        });
    });
</script>
<div class="container">
    <?php
    if (isset($_REQUEST['message'])) {
        echo '<div class="alert alert-success col-md-12" id="succMsgContainer">' . $module->getMessageHandler(
            )->fetchMessage('writingGroup', $_REQUEST['message']) . '</div>';
    }
    ?>
    <div class="backTo">
        <a href="<?= $module->getUrl('index.php',true) . '&option=ttl&record=' . $recordId ?>">< Back to Concept</a>
    </div>
    <?php
    if ($concept != "" && $concept != null) { ?>
        <h3 class="concepts-title-title"><?= $concept->getConceptId() . ": Writing Group" ?></h3>
        <?php
        if ($isAdmin) {
            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['HARMONIST'], $recordId, "concept_sheet", "");
            $survey_link = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape(
                    $passthru_link['hash']
                ) . "&modal=modal";

            $gotoredcap = htmlentities(
                APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $pidsArray['HARMONIST'] . "&arm=1&id=" . $recordId,
                ENT_QUOTES
            );
        }?>
        <div>
            <div class="d-flex justify-content-between align-items-center">
                <!-- Title -->
                <p class="hub-title concepts-title-title mb-0 text-start" style="font-weight: normal; flex-grow: 1;">
                    <?= $concept->getConceptTitle() ?>
                </p>

                <?php
                if ($isAdmin) { ?>
                <!-- Admin Button -->
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Admin <span class="dropdown-toggle-icon"></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= $gotoredcap ?>" target="_blank">Go to REDCap</a></li>
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#hub-modal-writing-groups">
                                Edit Writing Groups
                            </a>
                        </li>
                    </ul>
                </div>
                <?php } ?>
            </div>
        </div>

        <?php
        if ($isAdmin) { ?>
            <!-- MODAL EDIT WRITING GROUPS-->
            <div class="modal fade" id="hub-modal-writing-groups" tabindex="-1" aria-labelledby="edit_title">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Writing Groups</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body table-no-borders">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Writing Group Members</th>
                                    <th>Last Edit</th>
                                    <th>Edit</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $params = [
                                    'project_id' => $pidsArray['REGIONS'],
                                    'return_format' => 'json-array'
                                ];
                                $regions = $module->escape(REDCap::getData($params));
                                ArrayFunctions::array_sort_by_column($regions, 'region_code');
                                $dataRow = '';
                                foreach ($regions as $region) {
                                    $instance = $region['record_id'];
                                    $Proj = new Project($pidsArray['HARMONIST']);
                                    $event_id = $Proj->firstEventId;
                                    # If there are no instances, create them
                                    if (empty($concept->getGmemberRole())) {
                                        $array_repeat_instances = [];
                                        $params = [
                                            'gmember_role' => $region['record_id'],
                                            'writing_group_by_research_group_complete' => '1'
                                        ];
                                        $array_repeat_instances[$recordId]['repeat_instances'][$event_id]['writing_group_by_research_group'][$instance] = $params;
                                        $results = REDCap::saveData(
                                            $pidsArray['HARMONIST'],
                                            'array',
                                            $array_repeat_instances,
                                            'overwrite',
                                            'YMD',
                                            'flat',
                                            '',
                                            true,
                                            true,
                                            true,
                                            false,
                                            true,
                                            [],
                                            true,
                                            false,
                                            1,
                                            false,
                                            ''
                                        );
                                        REDCap::logEvent(
                                            "Create Writing Group Instance\nConcept Sheet",
                                            $region['region_name'] . " (" . $region['region_code'] . ")",
                                            null,
                                            $recordId,
                                            $event_id,
                                            $pidsArray['HARMONIST']
                                        );
                                    }
                                    if ($region["showregion_y"] == "1") {
                                        $logTable = REDCap::getLogEventTable($pidsArray['HARMONIST']);
                                        $params = [
                                            $pidsArray['HARMONIST'],
                                            $pidsArray['HARMONIST'],
                                            '%[instance = ' . $instance . ']%',
                                            'Update record'
                                        ];
                                        $q = $module->query(
                                            "SELECT ts FROM " . $logTable . " WHERE project_id = ? AND ts = ( SELECT MAX(ts) FROM redcap_log_event WHERE project_id = ? AND data_values LIKE ? AND description = ? );",
                                            $params
                                        );
                                        $row = $module->escape($q->fetch_assoc());
                                        $completion_time = "";
                                        if (!empty($row)) {
                                            $dateTime = DateTime::createFromFormat('YmdHis', $row['ts']);
                                            $completion_time = $dateTime->format('Y-m-d H:i:s');
                                        }

                                        $passthruLink = $module->resetSurveyAndGetCodes(
                                            $pidsArray['HARMONIST'],
                                            $concept->getRecordId(),
                                            "writing_group_by_research_group",
                                            "",
                                            $instance
                                        );
                                        $surveyLink = APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $module->escape(
                                                $passthruLink['hash']
                                            );
                                        $researchGroupName = $region['region_code'] . '/' . $region['region_name'];
                                        $dataRow .= '<tr>' .
                                            '<td>' . $researchGroupName . '</td>' .
                                            '<td>' . $writingGroupMember->getTotalWritingGroupMemberByResearch(
                                            )[$instance] . '</td>' .
                                            '<td>' . $completion_time . '</td>' .
                                            '<td><button class="btn btn-outline-secondary open-codesModal" onclick="document.getElementById(\'edit_title\').innerHTML = \'' . $researchGroupName . '\'; editIframeModal(\'hubEditWritingGroupByResearchGroup\', \'redcap-edit-frame-wgrg\', \'' . $surveyLink . '\', \'\', \'P\');"><i class="fa fa-pencil"></button></td>' .
                                            '</tr>';
                                    }
                                }
                                echo $dataRow;
                                ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL EDIT WRITING GROUP BY RESEARCH-->
            <div class="modal fade" id="hubEditWritingGroupByResearchGroup" tabindex="-1" aria-labelledby="Codes">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="edit_title">Edit</h5>
                            <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <iframe class="commentsform" id="redcap-edit-frame-wgrg" message="" name="redcap-edit-frame-wgrg" src=""
                                    style="border: none; height: 810px; width: 100%;"></iframe>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        } ?>

        <table class="table table_requests sortable-theme-bootstrap" data-sortable>
            <div class="row request mb-3">
                <div class="col-lg-2 col-md-3 col-sm-12"><strong>Working Group:</strong></div>
                <div class="col-lg-6 col-md-5 col-sm-12"><?= $concept->getWorkingGroup(); ?></div>
                <div class="col-lg-4 col-md-4 col-sm-12"><strong>Start Date:</strong> <?= $concept->getStartDate(); ?></div>
            </div>
            <div class="row request mb-3">
                <div class="col-lg-2 col-md-3 col-sm-12"><strong>Contact:</strong></div>
                <div class="col-lg-6 col-md-5 col-sm-12"><?= $concept->getContact(); ?></div>
                <div class="col-lg-4 col-md-4 col-sm-12"><strong>Status:</strong> <?= $concept->getStatus(); ?></div>
            </div>
        </table>

        <!-- Add New Member -->
        <div class="d-flex justify-content-end mb-3">
            <?php if ($canUserEdit) { ?>
                <a href="#" data-bs-toggle="modal" data-bs-target="#hub_new_writing_group_member" class="btn btn-success btn-md">
                    <span class="fa fa-plus"></span> Member
                </a>
            <?php } ?>
        </div>

        <!-- Options Menu -->
        <div class="optionSelect conceptSheets_optionMenu">
            <div id="options_wrapper" class="float-start"></div>
            <div class="d-flex justify-content-end align-items-center">
                <div class="me-3 mt-2">
                    Roles:
                </div>
                <div>
                    <select class="form-select" name="selectRoles" id="selectRoles">
                        <option value="">Select All</option>
                        <?php
                        $cmemberRole = $module->getChoiceLabels('cmember_role', $pidsArray['HARMONIST']);
                        $params = [
                            'project_id' => $pidsArray['REGIONS'],
                            'return_format' => 'json-array',
                            'filterLogic' => "[showregion_y] = 1",
                            'events' => ['region_name']
                        ];
                        $regions = REDCap::getData($params);
                        foreach ($regions as $region) {
                            array_push($cmemberRole, $region['region_name']);
                        }
                        sort($cmemberRole);
                        foreach ($cmemberRole as $text) {
                            echo "<option value='" . htmlspecialchars($text, ENT_QUOTES) . "'>" . htmlspecialchars(
                                    $text,
                                    ENT_QUOTES
                                ) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-no-borders">
            <table class="table table_requests table-hover concepts-table sortable-theme-bootstrap" data-sortable
                   id="sortable_table">
                <thead>
                <tr>
                    <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Name</th>
                    <th class="sorted_class">Email</th>
                    <th class="sorted_class">Role</th>
                    <th class="sorted_class">Order</th>
                    <?php if ($canUserEdit || $harmonistPermEditConcept) { ?>
                        <th class="sorted_class">Actions</th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($writingGroupMemberList as $writingGroupMember) {
                    $edit = "";
                    if (($harmonistPermEditConcept && $writingGroupMember->getRoleId() == $currentUser["person_region"]) || $canUserEdit) {
                        $edit = '<a href="#" class="btn btn-outline-secondary open-codesModal" onclick="editIframeModal(\'hub_edit_writing_group\',\'redcap-edit-frame\',\'' . $writingGroupMember->getEditLink() . '\');"><em class="fa fa-pencil"></em></a>';
                    }
                    echo "<tr>
                    <td style='width: 25%'>" . $writingGroupMember->getName() . "</td>
                    <td style='width: 30%'><a href='mailto:" . $writingGroupMember->getEmail() . "'>" . $writingGroupMember->getEmail() . "</a></td>
                    <td style='width: 15%'>" . $writingGroupMember->getRole() . "</td>
                    <td style='width: 15%'>" . $writingGroupMember->getOrder() . "</td>";
                    if ($canUserEdit || $harmonistPermEditConcept) {
                        echo "<td style='width: 5%'>" . $edit . "</td>";
                    }
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <!-- MODAL WRITING GROUP-->
        <div class="modal fade" id="hub_edit_writing_group" tabindex="-1" aria-labelledby="edit_title">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit_title">Edit Member</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <iframe id="redcap-edit-frame" message="U" name="redcap-edit-frame" src=""
                                style="border: none; height: 810px; width: 100%;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL NEW WRITING GROUP MEMBER-->
        <div class="modal fade" id="hub_new_writing_group_member" tabindex="-1" aria-labelledby="edit_title">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="edit_title">New Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <iframe id="redcap-new-frame" name="redcap-new-frame" message="N"
                                src="<?= $module->getSurveyLinkNewInstance(
                                    "writing_group_core",
                                    $recordId,
                                    $pidsArray['HARMONIST']
                                ); ?>"
                                style="border: none; height: 810px; width: 100%;"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php
    } else { ?>
        <div class="alert alert-warning col-12" role="alert">
            <em>Concept #<?= $recordId ?> is not available at this time.</em>
        </div>
    <?php
    } ?>

