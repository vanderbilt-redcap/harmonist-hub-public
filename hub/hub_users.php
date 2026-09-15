<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var region = $('#selectRegion option:selected').text();
            var level = $('#selectActivity option:selected').text();
            var column_region = data[1];
            var column_level= data[3];

            if(region != 'Select All' && column_region == region ){
                if(level != 'Select All' && column_level == level ){
                    return true;
                }else if(level == 'Select All'){
                    return true;
                }
            }else if(region == 'Select All'){
                if(level != 'Select All' && column_level == level ){
                    return true;
                }else if(level == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();
        $('#table_archive').dataTable( {"pageLength": 50});

        //when any of the filters is called upon change datatable data
        $('#selectRegion, #selectActivity').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );
</script>
<div class="container">
    <div class="mt-3">
        <a href="<?=$module->getUrl('index.php', true).'&option=adm'?>">< Back to Admin</a>
    </div>
    <h3 class="mt-4">Hub Users</h3>
    <p class="fs-6">
        All Hub users with "active" status are listed. To change a user's access level, grant or remove permissions, or deactivate a user account, click on the user's REDCap icon to edit the settings in REDCap (requires REDCap login.) To create a new user or to activate an inactive user account,
        <a href="<?= APP_PATH_WEBROOT_ALL.'DataEntry/record_status_dashboard.php?pid='.$pidsArray['PEOPLE']?>" target="_blank">log in to REDCap directly</a>.
    </p>
    <br>
</div>
<div class="container">
    <div class="optionSelect conceptSheets_optionMenu">
        <div class="d-flex justify-content-end align-items-center">
            <div class="me-3 mt-2">
                Group:
            </div>
            <div class="me-4">
                <select class="form-select" name="selectRegion" id="selectRegion">
                    <option value="">Select All</option>
                    <?php
                    $regions = $module->escape(\REDCap::getData($pidsArray['REGIONS'], 'json-array', null));
                    ArrayFunctions::array_sort_by_column($regions, 'region_code');
                    if (!empty($regions)) {
                        foreach ($regions as $region) {
                            echo "<option value='".$region['record_id']."'>".$region['region_code']."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="me-3 mt-2">
                Level:
            </div>
            <div>
                <select class="form-select" name="selectActivity" id="selectActivity">
                    <option value="">Select All</option>
                    <?php
                    $harmonist_regperm = $module->escape($module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']));
                    if (!empty($harmonist_regperm)) {
                        foreach ($harmonist_regperm as $level) {
                            echo "<option value='".$level."'>".$level."</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover sortable-theme-bootstrap" data-sortable id="table_archive">
                <?php
                $logins = $module->escape(\REDCap::getData($pidsArray['PEOPLE'], 'json-array', null, null, null, null, false, false, false, "[active_y] = '1'"));
                if (!empty($logins)) {
                    echo '<thead>' . '
                        <tr>' . '
                            <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Name</th>' . '
                            <th class="sorted_class" data-sorted-direction="descending text-center">Group</th>' . '
                            <th class="sorted_class" data-sorted-direction="descending" style="width: 150px;">Last Access Link</th>' . '
                            <th class="sorted_class" data-sorted-direction="descending">Level</th>' . '
                            <th class="sorted_class" data-sorted-direction="descending">Permissions</th>' . '
                            <th class="sorted_class" data-sortable="false text-center">REDCap</th>' . '
                            <th class="sorted_class" data-sortable="false text-center">Options</th>' . '
                        </tr>' . '
                        </thead>';
                    $harmonist_regperm = $module->getChoiceLabels('harmonist_regperm', $pidsArray['PEOPLE']);
                    $harmonist_perms = $module->getChoiceLabels('harmonist_perms', $pidsArray['PEOPLE']);
                    foreach ($logins as $login) {
                        $region_code = \REDCap::getData($pidsArray['REGIONS'], 'json-array', array('record_id' => $login['person_region']), array('region_code'))[0]['region_code'];
                        $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', array('record_id' => $login['record_id']))[0];

                        $gotoredcap = APP_PATH_WEBROOT_ALL . "DataEntry/record_home.php?pid=" . $module->escape($pidsArray['PEOPLE']) . "&arm=1&id=" . $module->escape($login['record_id']);

                        $harmonist_perm_text = "";
                        if ($people['harmonistadmin_y'] == "1") {
                            $harmonist_perm_text = "<div><strong>Admin</strong></div>";
                        }
                        $found = false;
                        foreach ($harmonist_perms as $index => $h_perm) {
                            if ($people['harmonist_perms___' . $index] == '1') {
                                $found = true;
                                $harmonist_perm_text .= "<div>" . $h_perm . "</div>";
                            }
                        }
                        if (!$found) {
                            $harmonist_perm_text .= "<div><em>None</em></div>";
                        }

                        echo '<tr><td><a href="mailto:' . $login['email'] . '">' . $login['firstname'] . ' ' . $login['lastname'] . '</a></td>' .
                            '<td class="text-center">' . $module->escape($region_code) . '</td>' .
                            '<td>' . $login['last_requested_token_d'] . '</td>' .
                            '<td>' . $module->escape($harmonist_regperm[$people['harmonist_regperm']]) . '</td>' .
                            '<td>' . filter_tags($harmonist_perm_text) . '</td>' .
                            '<td class="text-center"><a href="' . $gotoredcap . '" target="_blank"> <img src="' . $module->getUrl('img/REDCap_R_logo_transparent.png') . '" class="img-fluid" style="width: 18px;" alt="REDCap Logo"></a></td>' .
                            '<td class="text-center" style="width: 150px"><div><a href="' . $module->getUrl('index.php', true) . '&option=hra&record=' . $login['record_id'] . '" class="btn btn-link btn-sm"><i class="fa fa-user fa-fw" aria-hidden="true"></i> View Activity</a></div></td>';

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
