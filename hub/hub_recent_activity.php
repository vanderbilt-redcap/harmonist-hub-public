<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

$comments = \REDCap::getData($pidsArray['COMMENTSVOTES'], 'json-array', null);
ArrayFunctions::array_sort_by_column($comments, 'responsecomplete_ts', SORT_DESC);

$region_vote_icon_text = ["1" => "text-approved", "0" => "text-error", "9" => "text-default"];

$person_record = $_REQUEST['record'];
if ($person_record != "") {
	$person_name = getPeopleName($pidsArray['PEOPLE'], $person_record);
}
?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var activity = $('#selectActivity option:selected').text();
            var column_region = data[4];
            var column_activity = data[3];

            if(region != 'Select All' && column_region == region ){
                if(activity != 'Select All' && column_activity.match(activity) != null){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }
            }else if(region == 'Select All'){
                if(activity != 'Select All' && column_activity.match(activity) != null){
                    return true;
                }else if(activity == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();
        var person_name = <?=json_encode($person_name)?>;
        if(person_name != "" && person_name != null){
            $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"],"oSearch": {"sSearch": person_name}});
        }else{
            $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"]});
        }

        //when any of the filters is called upon change datatable data
        $('#selectRegion, #selectActivity').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );
</script>
<div class="container">
    <div class="backTo mb-3">
        <a href="<?=$module->getUrl('index.php', true)?>" class="text-decoration-none">
            &lt; Back to Home
        </a>
    </div>
    <h3>Recent Activity</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_recent_act_text'])?></p>
</div>
<div class="container">
    <div class="optionSelect conceptSheets_optionMenu d-flex justify-content-end align-items-center flex-wrap">
        <div class="d-flex align-items-center me-3">
            <div class="me-3">Group:</div>
            <div>
                <select class="form-select" name="selectRegion" id="selectRegion">
                    <option value="">Select All</option>
                    <?php
                    $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null, ['record_id','region_code']);
                    ArrayFunctions::array_sort_by_column($regions, 'region_code');
                    if (!empty($regions)) {
                        $regions = $module->escape($regions);
                        foreach ($regions as $region) {
                            echo "<option value='".$region['record_id']."'>".$region['region_code']."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="me-3">Activity:</div>
            <div>
                <select class="form-select" name="selectActivity" id="selectActivity">
                    <option value="">Select All</option>
                    <option value="comment">comment</option>
                    <option value="vote">vote</option>
                    <option value="revision">revision</option>
                    <option value="file">file</option>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <table class="table table-hover sortable-theme-bootstrap" data-sortable id="table_archive">
        <?php if (!empty($comments)) { ?>
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
            <tr>
                <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Date</th>
                <th class="sorted_class">Concept</th>
                <th class="sorted_class">Name</th>
                <th class="sorted_class">Activity</th>
                <th class="sorted_class">Group</th>
                <th class="sorted_class">
                    <span style="display:block">Request</span>
                    <span>Title</span>
                </th>
                <th class="sorted_class">File</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($comments as $comment) {
                if ($comment['author_revision_y'] == '1' || $comment['pi_vote'] != '' || $comment['comments'] != '') {
                    $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', ['record_id' => $comment['response_person']], ['firstname', 'lastname', 'person_region'])[0];
                    $name = trim($people['firstname'] . ' ' . $people['lastname']);

                    $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', ['record_id' => $people['person_region']], null, ['region_code'], null, false, false, false, "[showregion_y] = 1")[0]['region_code'];
                    $requestComment = $module->escape(\REDCap::getData($pidsArray['RMANAGER'], 'json-array', ['request_id' => $comment['request_id']])[0]);

                    $comment_time = "";
                    if (!empty($comment['responsecomplete_ts'])) {
                        $dateComment = new \DateTime($comment['responsecomplete_ts']);
                        $dateComment->modify("+1 hours");
                        $comment_time = $dateComment->format("Y-m-d H:i:s");
                    }

                    echo '<tr><td width="150px">' . htmlspecialchars($comment_time, ENT_QUOTES) . '</td>';

                    $concept_id = "<em>None</em>";
                    if (!empty($requestComment['assoc_concept'])) {
                        $concept = $module->escape(\REDCap::getData($pidsArray['HARMONIST'], 'json-array', ['record_id' => $requestComment['assoc_concept']], ['record_id', 'concept_id'])[0]);
                        $concept_id = '<a href="' . $module->getUrl('index.php',true) . '&option=ttl&record=' . $concept['record_id'] . '">' . htmlspecialchars($concept['concept_id'], ENT_QUOTES) . '</a>';
                    } elseif ($requestComment['mr_temporary'] != "") {
                        $concept_id = htmlspecialchars($requestComment['mr_temporary'], ENT_QUOTES);
                    }
                    echo '<td width="50px">' . filter_tags($concept_id, ENT_QUOTES) . '</td>' .
                        '<td width="160px">' . htmlspecialchars($name, ENT_QUOTES) . '</td>';

                    echo '<td width="160px">';
                    if ($comment['author_revision_y'] == '1') {
                        echo 'submitted a <strong>revision</strong></td>';
                    } else {
                        $text = 'submitted a ';
                        if ($comment['comments'] != '' && $comment['pi_vote'] != '' && $comment['revised_file'] != '') {
                            $text .= '<strong>comment, vote, and file</strong>';
                        } elseif ($comment['comments'] != '' && $comment['pi_vote'] != '') {
                            $text .= '<strong>comment and vote</strong>';
                        } elseif ($comment['comments'] != '' && $comment['revised_file'] != '') {
                            $text .= '<strong>comment and file</strong>';
                        } elseif ($comment['pi_vote'] != '' && $comment['revised_file'] != '') {
                            $text .= '<strong>vote and file</strong>';
                        } elseif ($comment['comments'] != '') {
                            $text .= '<strong>comment</strong>';
                        } elseif ($comment['revised_file'] != '') {
                            $text .= '<strong>file</strong>';
                        } elseif ($comment['pi_vote'] != '') {
                            $text .= '<strong>vote</strong>';
                        }
                        echo $text . '</td>';
                    }

                    echo '<td width="65px">' . htmlspecialchars($region_code, ENT_QUOTES) . '</td>' .
                        '<td width="450px">';
                    $request = \REDCap::getData($pidsArray['RMANAGER'], 'json-array', ['request_id' => $comment['request_id']], ['region_response_status'])[0];
                    $instance = $currentUser['person_region'];

                    $comment_vote = "";
                    if ($settings['vote_visibility'] == "" || $settings['vote_visibility'] == "1") {
                        if ($request['region_response_status'][$instance] == 2) {
                            $comment_vote .= '<div class="mb-2"><span class="badge bg-info" title="Complete"><i class="fa fa-check" aria-hidden="true"></i></span> <span class="text-info">Complete</span></div>';
                        }
                    } else {
                        if ($request['region_response_status'][$instance] == "2" && $comment['pi_vote'] != '') {
                            if ($comment['pi_vote'] == "1") {
                                $comment_vote = '<div class="mb-2"><span class="badge bg-success" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span> <span class="text-success">Approved</span></div>';
                            } elseif ($comment['pi_vote'] == "0") {
                                $comment_vote = '<div class="mb-2"><span class="badge bg-danger" title="Not Approved"><i class="fa fa-times" aria-hidden="true"></i></span> <span class="text-danger">Not Approved</span></div>';
                            } elseif ($comment['pi_vote'] == "9") {
                                $comment_vote = '<div class="mb-2"><span class="badge bg-secondary" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <span class="text-secondary">Abstained</span></div>';
                            } else {
                                $comment_vote = '<div class="mb-2"><span class="badge bg-secondary" title="Abstained"><i class="fa fa-ban" aria-hidden="true"></i></span> <span class="text-secondary">Abstained</span></div>';
                            }
                        }
                    }

                    echo $comment_vote . '<a href="' . $module->getUrl('index.php',true) . '&option=hub&record=' . $requestComment['request_id'] . '" target="_blank">' . htmlspecialchars($requestComment['request_title'], ENT_QUOTES) . '</a></td>';

                    if ($comment['revised_file'] != '') {
                        echo '<td>' . getFileLink($module, $pidsArray['PROJECTS'], $comment['revised_file'], '1', '', $secret_key, $secret_iv, $currentUser['record_id'], "") . '</td>';
                    } else {
                        echo '<td></td>';
                    }
                    echo '</tr>';
                }
            } ?>
            </tbody>
        <?php } ?>
    </table>
</div>
