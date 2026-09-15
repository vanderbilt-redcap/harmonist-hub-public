<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$record = (int)$_REQUEST['record'];
$Proj = new \Project($pidsArray["PROJECTSSTUDIES"]);
$event_id = $Proj->firstEventId;
unset($Proj);

$studyData = arrayKeyExistsReturnValue($module->escape(\REDCap::getData($pidsArray["PROJECTSSTUDIES"], 'array', ['record_id' => $record])),[$record]);
$study = $studyData[$event_id];
$studyRepeat = $studyData['repeat_instances'][$event_id];
unset($studyData);
if(!empty($study)){
    $study_type = $module->getChoiceLabels('study_type', $pidsArray['PROJECTSSTUDIES']);
    $study_project = $module->getChoiceLabels('study_project', $pidsArray['PROJECTSSTUDIES']);
    $study_status = $module->getChoiceLabels('study_status', $pidsArray['PROJECTSSTUDIES']);
    $status = '<span class="badge rounded-pill bg-success">'.$study_status[$study['study_status']].'</span>';

    $projectTypeTitle = empty($study['study_project']) ? "Project" : arrayKeyExistsReturnValue($study_project, [$study['study_project']]);

    $concept_id = "<em>None</em>";
    if($study['study_concept'] != ""){
        $params = [
            'project_id' => $pidsArray['HARMONIST'],
            'return_format' => 'array',
            'records' => [$study['study_concept']],
            'fields' => ['concept_id']
        ];
        $RecordSetConcepts = \REDCap::getData($params);
        $url = $module->getUrl("index.php").'&NOAUTH&option=ttl&record='.$study['study_concept'];
        $concept_id = '<a href="<?=$url?>">'.arrayKeyExistsReturnValue($RecordSetConcepts,[0,'concept_id']).'</a>';
    }

    $wg = "<em>None</em>";
    if($study['study_wg'] != ""){
        $params = [
            'project_id' => $pidsArray['GROUPS'],
            'return_format' => 'array',
            'records' => [$study['study_wg']],
            'fields' => ['group_name']
        ];
        $RecordSetGroups = \REDCap::getData($params);
        $wg = arrayKeyExistsReturnValue($RecordSetGroups,[0,'group_name']);
    }

    if($study['study_ed'] == ""){
        $attributes_date = $study['study_sd']." to <em>(ongoing)</em>";
    }else{
        $attributes_date = $study['study_sd']." to ".$study['study_ed'];
    }

    #Table Data
    $metrics_labels = [1 => $study['study_label1'],2 => $study['study_label2'],3 => $study['study_label3'],4 => $study['study_label4'],5 => $study['study_label5']];
    $metrics_values = [1 => $study['study_value1'],2 => $study['study_value2'],3 => $study['study_value3'],4 => $study['study_value4'],5 => $study['study_value5']];
    $attributes_labels = [1 => "Dates:",2 => "Study Concept:",3 => "Primary Working Group:",4 => "Status:"];
    $attributes_values= [1 => $attributes_date,2 => $concept_id,3 => $wg,4 => $status];
    ?>
    <div class="container">
        <!-- Back to Projects Link -->
        <div class="backTo">
            <a href="<?=$module->getUrl('index.php',true).'&option=std'?>" class="text-decoration-none">&lt; Back to Projects</a>
        </div>

        <!-- Study Information Section -->
        <div class="optionSelect mt-4">
            <div class="d-flex align-items-center">
                <h3 class="concepts-title-title mb-0"><?=$projectTypeTitle?>: <?=$study['study_name']?></h3>
                <span class="badge bg-primary ms-3"><?=arrayKeyExistsReturnValue($study_type, [$study['study_type']])?></span>
            </div>
            <p class="hub-title concepts-title-title fw-normal"><?=$study['study_fullname']?></p>
        </div>
    </div>
    <div class="row study_data" style="padding: 0;">
        <div class="col-lg-7 col-md-12">
            <div class="card panel_study">
                <div class="card-body d-flex" style="min-height: 238px;">
                    <div class="col-md-6">
                        <div class="panel_study_header"><i class="fa fa-user"></i> Contacts</div>
                        <div class="panel_study_content">
                            <?php
                            $no_contacts = true;
                            for ($i = 1; $i < 6; $i++) {
                                if ($study['study_contactname_' . $i] != "") {
                                    $no_contacts = false;
                                    ?>
                                    <div>
                                        <?php
                                        $name = $study['study_contactname_' . $i];
                                        $role = "";
                                        if ($study['study_contactrole_' . $i] != "") {
                                            $role = ' (' . $study['study_contactrole_' . $i] . ')';
                                        }
                                        $tooltip = 'data-bs-toggle="tooltip" title="' . $name . $role . '" data-bs-placement="top" class="custom-tooltip"';
                                        if ($study['study_contactemail_' . $i] != "") {
                                            if (strlen($name) > 31) {
                                                $contact = '<a href="mailto:' . $study['study_contactemail_' . $i] . '">' . substr($name, 0, 31) . '</a>' . '<a href="#" ' . $tooltip . '>...</a>';
                                            } else if ((strlen($name) < 31 && strlen($role) > 31) || (strlen($name) < 31 && strlen($name . $role) > 31)) {
                                                $contact = '<a href="mailto:' . $study['study_contactemail_' . $i] . '">' . $name . '</a>' . substr($role, 0, 31 - strlen($name)) . '<a href="#" ' . $tooltip . '>...</a>';
                                            } else if (strlen($name . $role) < 31) {
                                                $contact = '<a href="mailto:' . $study['study_contactemail_' . $i] . '">' . $name . '</a>' . $role;
                                            }
                                        } else {
                                            if (strlen($name . $role) > 31) {
                                                $contact = substr($name . $role, 0, 31) . '</a>' . '<a href="#" ' . $tooltip . '>...</a>';
                                            } else {
                                                $contact = $name . $role;
                                            }
                                        }
                                        echo $contact;
                                        ?>
                                    </div>
                                    <?php
                                }
                            }
                            if ($no_contacts) {
                                ?><div><em>None specified</em></div><?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel_study_header"><i class="fa fa-book"></i> Attributes</div>
                        <div class="panel_study_content">
                            <?php
                            if (!array_filter($attributes_values)) {
                                ?><div><em>None specified</em></div><?php
                            } else {
                                for ($i = 1; $i < 5; $i++) {
                                    if ($attributes_values[$i] != "") { ?>
                                        <div>
                                            <?=$attributes_labels[$i]?> <?=$attributes_values[$i]?>
                                        </div>
                                        <?php
                                    } else {
                                        ?><div><?=$attributes_labels[$i]?> <em>None</em></div><?php
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Files -->
        <div class="col-lg-5 col-md-12">
            <div class="card panel_study">
                <div class="card-body" style="min-height: 239px; color: #31708f; background-color: #d9edf7; border-color: #bce8f1; border-radius: 4px;">
                    <div class="panel_study_header"><i class="fa fa-arrow-up"></i> Top Files</div>
                    <div class="panel_study_content">
                        <?php
                        $maxFiles = 4;
                        for ($i = 1; $i < $maxFiles + 1; $i++) {
                            if (!empty($study["topfile" . $i])) {
                                $instance = explode(": ", $study["topfile" . $i])[0];
                                $link = getOtherFilesLink($module, $studyRepeat['study_documents'][$instance]["studyfile_file"], $record, $current_user['record_id'], $secret_key, $secret_iv, $studyRepeat['study_documents'][$instance]["studyfile_desc"]);
                                ?><div><?=$link?></div><?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container study_data">
        <p><strong>Summary: </strong><?=empty($study['study_summary']) ? "<em>None</em>" : $study['study_summary']?></p>
    </div>
    <?php if (!empty($study['study_inclusion'])) { ?>
        <div class="container study_data">
            <p><strong>Inclusion: </strong><?=empty($study['study_inclusion']) ? "<em>None</em>" : $study['study_inclusion']?></p>
        </div>
    <?php } ?>
    <div class="container study_data">
        <p><strong>Participating Research Groups: </strong><?=empty($study['study_groups']) ? "<em>None</em>" : $study['study_groups']?></p>
    </div>
    <div class="container study_data">
        <p><strong>Data Considerations</strong></p>
    </div>
    <div class="container study_data" style="padding-bottom: 20px;">
        <?php
        if ($study['study_datareqs'] == "") {
            ?><p><em>None</em></p><?php
        } else {
            ?><ul class="list-unstyled"><?php
            $study_datareqs = $module->getChoiceLabels('study_datareqs', $pidsArray['PROJECTSSTUDIES']);
            foreach ($study['study_datareqs'] as $index => $value) {
                if ($value == 1) {
                    ?><li><?=$study_datareqs[$index]?></li><?php
                }
            }
            ?></ul><?php
        }
        ?>
    </div>
    <div class="card panel_study">
        <div class="card-header">
            <h6 class="card-title d-flex justify-content-between align-items-center">
                <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse_studyDocuments" role="button" aria-expanded="false" aria-controls="collapse_quicklinks">
                    <span><?=arrayKeyExistsReturnValue($study_project,[$study['study_project']])?> Documents</span>
                    <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="quicklinks-arrow"></i>
                </a>
            </h6>
        </div>
        <div id="collapse_studyDocuments" class="collapse table-no-borders" style="margin-bottom: 0px; border: 0px;">
            <div class="card-body" style="padding: 0px;">
                <table class="table sortable-theme-bootstrap"  data-sortable id="sortable_table">
                    <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $studyfile_category = $module->getChoiceLabels('studyfile_category', $pidsArray['PROJECTSSTUDIES']);
                    foreach ($studyRepeat['study_documents'] as $instance => $studyR) {?>
                        <tr>
                            <td width="30%">
                                <?=getFileLink($module, $pidsArray['PROJECTS'], $studyR["studyfile_file"], '', '', $secret_key, $secret_iv, $current_user['record_id'], "")?>
                            </td>
                            <td width="39%"><?=$studyR['studyfile_desc']?></td>
                            <td width="15% class="d-flex align-items-center">
                                <span class="badge bg-primary"><?=$studyfile_category[$studyR['studyfile_category']]?></span>
                            </td>
                            <td width="10%"><?=$studyR['studyfile_date']?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card panel_study" style="margin-bottom: 60px;">
        <div class="card-header">
            <h6 class="card-title d-flex justify-content-between align-items-center">
                <a class="collapseText d-flex align-items-center w-100 toggle-icon" data-bs-toggle="collapse" href="#collapse_studySites" role="button" aria-expanded="false" aria-controls="collapse_quicklinks">
                    <span><?=$study_project[$study['study_project']]?> Sites</span>
                    <i class="fa fa-chevron-down ms-auto" aria-hidden="true" id="quicklinks-arrow"></i>
                </a>
            </h6>
        </div>
        <div id="collapse_studySites" class="collapse table-no-borders" style="margin-bottom: 0px; border: 0px;">
            <div class="card-body" style="padding: 0px;">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Location</th>
                        <th>Site Name</th>
                        <th>Target</th>
                        <th>Category</th>
                        <th>Last Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $site_status = $module->getChoiceLabels('site_status', $pidsArray['PROJECTSSTUDIES']);
                    foreach ($studyRepeat['participating_sites'] as $index => $site) {
                        ?>
                        <tr>
                            <td width="10%"><?=$site['site_location']?></td>
                            <td width="55%"><?=$site['site_name']?></td>
                            <td width="10%"><?=$site['site_target']?></td>
                            <td width="15%">
                                <span style="vertical-align: super" class="badge bg-primary"><?=$site_status[$site['site_status']]?></span>
                            </td>
                            <td width="10%"><?=$site['site_statusdate']?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php }else{ ?>
    <div class="alert alert-warning fade in col-md-12"><em>Study #<?=$record?> is not available at this time.</em></div>
<?php } ?>