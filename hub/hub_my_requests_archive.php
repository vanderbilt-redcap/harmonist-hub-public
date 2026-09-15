<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$back_button = '<a href="'.$module->getUrl('index.php', true).'">< Back to Home</a>';

$person_name = "";
if($_REQUEST['type'] != ""){
    if($_REQUEST['type'] == 'h'){
        $person_name = $userFullName;
        $back_button = '<a href="'.$module->getUrl('index.php', true).'">< Back to Home</a>';
    }else if($_REQUEST['type'] == 'r'){
        $back_button = '<a href="'.$module->getUrl('index.php', true).'&option=hub'.'">< Back to Requests</a>';
    }else if($_REQUEST['type'] == 'a'){
        $back_button = '<a href="'.$module->getUrl('index.php', true).'&option=adm'.'">< Back to Admin</a>';
    }
}
?>
<script>
    $(document).ready(function() {
        var person_name = <?=json_encode($person_name)?>;
        Sortable.init();
        $('#table_archive').dataTable( {"pageLength": 50,"order": [0, "desc"],"oSearch": {"sSearch": person_name}});

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: left;padding-left: 170px;padding-top: 5px;' );

        //when any of the filters is called upon change datatable data
        $('#selectFinal, #selectReqType').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );
    } );

    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var final = $('#selectFinal option:selected').val();
            var type = $('#selectReqType option:selected').val();
            var column_final = data[data.length-1];
            var column_type = data[1];
            var typePosition = column_type.search(type);
            var finalPosition = column_final.search(final);

            if(final != 'Select All' && finalPosition >= 0){
                if(type != 'Select All' && typePosition >= 0){
                    return true;
                }else if(type == 'Select All'){
                    return true;
                }
            }else if(final == 'Select All'){
                if(type != 'Select All' && ctypePosition >= 0){
                    return true;
                }else if(type == 'Select All'){
                    return true;
                }
            }

            return false;
        }
    );
</script>
<div class="container">
    <div class="backTo">
        <?=$back_button?>
    </div>
    <h3>Requests Archive</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_req_archive_text'])?></p>
    <br>
    <?php if($isAdmin){?>
        <div class="text-end">
            <p><a href="<?=$module->getUrl('index.php', true).'&option=mrr&type=a'?>">View Rejected & Deactivated Requests</a></p>
        </div>
    <?php }?>
    <div class="optionSelect conceptSheets_optionMenu">
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap">
            <!-- Options Wrapper -->
            <div id="options_wrapper" class="d-inline-block me-auto"></div>

            <!-- Final Status Dropdown -->
            <div class="d-flex align-items-center me-4 flex-nowrap">
                <label for="selectFinal" class="me-2 mb-0 text-nowrap">Final status:</label>
                <select class="form-select form-select-sm" name="selectFinal" id="selectFinal">
                    <option value="">Select All</option>
                    <option value="None">Not finalized</option>
                    <option value="Approved">Approved</option>
                    <option value="Not Approved">Not Approved</option>
                </select>
            </div>

            <!-- Request Type Dropdown -->
            <div class="d-flex align-items-center flex-nowrap">
                <label for="selectReqType" class="me-2 mb-0 text-nowrap">Request type:</label>
                <select class="form-select form-select-sm" name="selectReqType" id="selectReqType">
                    <option value="">Select All</option>
                    <?php
                    $request_type_label = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
                    $hideChoicedRManagerRequestType = arrayKeyExistsReturnValue($defaultValues->getHideChoice($pidsArray['RMANAGER']), [$pidsArray['RMANAGER'], 'request_type']);
                    if (!empty($request_type_label) && is_array($hideChoicedRManagerRequestType) || empty($hideChoicedRManagerRequestType)) {
                        foreach ($request_type_label as $value => $label) {
                            if ((is_array($hideChoicedRManagerRequestType) && !in_array($value, $hideChoicedRManagerRequestType)) || empty($hideChoicedRManagerRequestType)) {
                                echo "<option value='" . $label . "'>" . $label . "</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div>
        <table class="table table-hover sortable-theme-bootstrap" data-sortable id="table_archive">
            <?php
            $requests = $hubData->getAllRequests();
            if(!empty($requests)) {
                $commentDetails = $hubData->getCommentDetails();

                $user_req_header = getRequestHeader($hubData, $settings['vote_grid'], '1','archive');

                $requests_counter = 0;
                $user_req_body = '';
                foreach ($requests as $req) {
                    $user_req_body .= getHomeRequestHTML($module, $hubData, $pidsArray, $req, $commentDetails, $request_type_label, 0, $settings['vote_visibility'], $settings['vote_grid'],'none','archive');
                    if($user_req_body != ""){
                        $requests_counter++;
                    }
                }
                if($requests_counter > 0) {
                    echo $user_req_header . $user_req_body;
                }else{?>
                    <tbody>
                    <tr>
                        <td><span><em>No requests available</em></span></td>
                    </tr>
                    </tbody>
                <?php }
            }else{?>
                <tbody>
                <tr>
                    <td><span><em>No requests available</em></span></td>
                </tr>
                </tbody>
            <?php }?>
        </table>
    </div>
</div>
