<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$TBLCenter = $module->escape(\REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null));

$regionstbl = $module->escape(\REDCap::getData([
                             'project_id' => $pidsArray['REGIONS'],
                             'return_format' => 'json-array',
                             'filterLogic' => "[showregion_y] = 1"
                         ]));
ArrayFunctions::array_sort_by_column($regionstbl, 'region_code');

$region_array = getTBLCenterUpdatePercentRegions($TBLCenter, $regionstbl, $settings['pastlastreview_dur']);

$harmonist_perm = ($currentUser['harmonist_perms___8'] == 1) ? true : false;

$regions_all = \REDCap::getData([
                                    'project_id' => $pidsArray['REGIONS'],
                                    'return_format' => 'json-array',
                                    'record' => ['record_id','region_tbl_option']
                                ]);

$personRegion = $hubData->getPersonRegion();
$map_region = $personRegion['region_code'];

foreach($regions_all as $region){
    if($region['record_id'] == $currentUser['person_region'] && ($region['region_tbl_option'] != "0" || !array_key_exists('region_tbl_option', $region))){
        $map_region = "";
    }
}

?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var column_region = data[4];

            if(region != 'Select All' && column_region == region ){
                return true;
            }else if(region == 'Select All'){
                return true;
            }

            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();
        $('#table_archive').dataTable(
            {
                pageLength: 50,
                order: [0, "asc"],
                initComplete: function() {
                    // Add Bootstrap classes to style the input
                    $('.dataTables_filter input').addClass('form-control');

                    // Fix alignment without breaking functionality
                    $('.dataTables_filter').addClass('d-flex align-items-center gap-2');
                    $('.dataTables_filter label').addClass('d-flex align-items-center gap-2 m-0');
                }
            }
        );
        setDataset(<?=json_encode($map_region)?>);

        //when any of the filters is called upon change datatable data
        $('#selectRegion').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
            setDataset($('#selectRegion option:selected').attr('region_code'));
        } );

        $('#table_archive_filter').appendTo( '.search_filter' );
        $('#table_archive_filter').attr( 'style','float: left;padding-left: 20px;padding-top: 5px;' );
    } );
</script>
<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST)){
        if($_REQUEST['message'] == 'C') {
           echo '<div class="alert alert-success col-12" id="succMsgContainer">The center has been successfully updated.</div>';
        }else if($_REQUEST['message'] == 'N') {
           echo '<div class="alert alert-success col-12 " id="succMsgContainer">The center has been successfully created.</div>';
        }
    }
    ?>

    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true).'&option=dat'?>">< Back to Data</a>
    </div>
</div>
<div class="container">
    <h3>tblCENTER</h3>
    <p><?=filter_tags($settings['hub_tbl_center_text'])?></p>
</div>
<div class="container pt-4">
    <?php include(dirname(dirname(__FILE__)).'/map/map_stats.php');?>
</div>
<div class="container">
    <?php if($isAdmin || $harmonist_perm){?>
        <div class="optionSelect">
            <div class="d-flex justify-content-center" style="width: 30%; margin: 0 auto;">
                <div class="d-inline-block">
                    <a href="#" onclick="$('#sop_new_center').modal('show');" class="btn btn-success btn-md"><i class="fa fa-plus"></i> Center</a>
                </div>
                <div class="d-inline-block ms-2">
                    <a href="<?= APP_PATH_WEBROOT_ALL . "DataEntry/record_status_dashboard.php?pid=" . $pidsArray['TBLCENTERREVISED'] ?>" target="_blank" class="btn btn-secondary btn-md">Go to REDCap</a>
                </div>
            </div>

            <!-- MODAL NEW CONCEPT-->
            <div class="modal fade" id="sop_new_center" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">New Center</h5>
                            <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" value="0" id="comment_loaded_center">
                            <iframe class="commentsform" id="redcap-new-center" name="redcap-new-center" message="N" src="<?=$module->escape(APP_PATH_WEBROOT_FULL."surveys/?s=".$pidsArray['SURVEYTBLCENTERREVISED'])?>" style="border: none;height: 810px;width: 100%;"></iframe>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="optionSelect conceptSheets_optionMenu" id="options_wrapper">
        <div class="d-inline-block">
            <?php
            if($personRegion['showregion_y'] == '1') {
                echo '<ul class="list-inline ps-2 pt-3">Your region: <li class="d-inline-block"><span class="pe-2">' . htmlspecialchars($map_region, ENT_QUOTES) . '</span>' . filter_tags(getTBLCenterUpdatePercentLabel($region_array[$map_region])) . '</li></ul>';
            }
            ?>
        </div>
        <div class="search_filter d-inline-flex align-items-center gap-2"></div>
        <div class="float-end">
            <div class="d-inline-block me-3 mt-1">
                Region:
            </div>
            <div class="d-inline-block ms-2 pt-2">
                <select class="form-select" name="selectRegion" id="selectRegion">
                    <option value="" region_code="">Select All</option>
                    <?php
                    if (!empty($regionstbl)) {
                        foreach ($regionstbl as $region){
                            $region = $module->escape($region);
                            if($region['region_code'] == $map_region){
                                echo "<option value='".$region['record_id']."' region_code='".$region['region_code']."' selected>".$region['region_code']."</option>";
                            }else{
                                echo "<option value='".$region['record_id']."' region_code='".$region['region_code']."'>".$region['region_code']."</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container pb-3 ps-3">
    <ul class="list-inline">Other regions:
        <?php
        foreach ($region_array as $pregion => $percent){
            if($map_region != $pregion){
                echo '<li class="d-inline-block me-3"><span class="pe-2">'.htmlspecialchars($pregion, ENT_QUOTES).'</span>'.getTBLCenterUpdatePercentLabel($percent).'</li>';
            }
        }
        ?>
    </ul>
</div>
<div class="container">
    <div class="card border-0 card-default-archive">
        <div class="table-responsive table-archive">
            <table class="table table_requests sortable-theme-bootstrap text-wrap table-borderless" data-sortable id="table_archive">
                <?php
                if(!empty($TBLCenter)) {?>
                    <colgroup>
                        <col>
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
                        <th class="sorted_class">Center</th>
                        <th class="sorted_class">Name</th>
                        <th class="sorted_class">Program</th>
                        <th class="sorted_class">Country</th>
                        <th class="sorted_class">Region</th>
                        <th class="sorted_class">
                            <span class="d-block">Database</span>
                            <span>Close Date</span>
                        </th>
                        <th class="sorted_class">
                            <span class="d-block">Last</span>
                            <span>Update</span>
                        </th>
                        <th class="sorting_disabled" data-sortable="false">Missing Fields</th>
                        <?php if ($harmonist_perm || $isAdmin) {?>
                            <th class="sorting_disabled" data-sortable="false">Actions</th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($TBLCenter as $center) {
                        if($center['drop_center'] == '' || !in_array($center['drop_center'], $center)) {
                            $center = $module->escape($center);
                            $buttons = "";
                            if (($harmonist_perm && $center['region'] == $map_region) || $isAdmin) {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['TBLCENTERREVISED'], $center['record_id'], "tblcenter", "");
                                $survey_link =  $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=".$passthru_link['hash']);
                                $buttons = '<div><a href="#" onclick="editIframeModal(\'update_center\',\'redcap-upload-center\',\'' . $survey_link . '\')" class="btn btn-outline-secondary"><i class="fa fa-pencil"></i></a></div>';
                            }

                            $last_update = $center['last_reviewed_d'];
                            if (strtotime($center['last_reviewed_d']) < strtotime(date('Y-m-d', strtotime("-" . $settings['pastlastreview_dur'] . " day")))) {
                                $last_update = "<span class='text-danger'>" . $center['last_reviewed_d'] . "</span>";
                            }

                            $missing_fields = searchTBLMissingFields($center);

                            echo '<tr>
                                    <td>' . $center['center'] . '</td>' .
                                '<td>' . filter_tags($center['name']) . '</td>' .
                                '<td>' . $center['program'] . '</td>' .
                                '<td>' . $center['country'] . '</td>' .
                                '<td>' . $center['region'] . '</td>' .
                                '<td class="text-nowrap">' . $center['close_d'] . '</td>' .
                                '<td class="text-nowrap">' . filter_tags($last_update) . '</td>' .
                                '<td>' . htmlspecialchars($missing_fields, ENT_QUOTES) . '</td>';

                            if ($harmonist_perm || $isAdmin) {
                                echo '<td>' . $buttons . '</td>';
                            }

                            echo '</tr>';
                        }
                    }
                    ?>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<!-- MODAL UPDATE-->
<div class="modal fade" id="update_center" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Center</h5>
                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded_center">
                <iframe class="commentsform" id="redcap-upload-center" name="redcap-upload-center" message="C" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
