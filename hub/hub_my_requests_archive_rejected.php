<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$person_name = htmlentities(arrayKeyExistsReturnValue($_REQUEST,['person_name']),ENT_QUOTES);
$request_type_label = $module->getChoiceLabels('request_type', $pidsArray['RMANAGER']);
?>
<script>
    $(document).ready(function() {
        var person_name = <?=json_encode($person_name)?>;

        // Initialize Sortable
        Sortable.init();

        // Initialize DataTable with proper options
        var table = $('#table_archive').DataTable({
            pageLength: 50,
            order: [[0, "desc"]], // Order by the first column in descending order
            dom: '<"row"lf>t<"row"ip>', // Custom layout for Bootstrap 5
        });

        // Move the search filter into the wrapper
        $('#table_archive_filter')
            .appendTo('#options_wrapper') // Append the search box
            .css({
                'float': 'none',        // Remove float styling
                'padding-left': '0',    // Adjust padding
                'padding-top': '0',     // Adjust padding
                'display': 'inline-flex', // Ensure alignment with other elements
                'align-items': 'center'
            });

        // Listen for changes to the "Request Type" dropdown
        $('#selectReqType').on('change', function() {
            table.draw(); // Trigger DataTable to redraw and apply the custom filter
        });

        // Custom filter logic for DataTables
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            // Get the selected "Request Type" value
            var selectedType = $('#selectReqType').val(); // The current value of the dropdown
            var columnType = data[1]; // The second column in the table (index starts at 0)

            // If "Select All" is selected, show all rows
            if (selectedType === '' || selectedType === 'Select All') {
                return true;
            }

            // Otherwise, match the column value with the selected type
            if (columnType && columnType.includes(selectedType)) {
                return true;
            }

            return false; // Exclude rows that don't match the filter
        });
    });
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true).'&option=mra&type=a'?>">&lt; Back to Requests Archive</a>
    </div>
    <h3>Rejected & Deactivated Requests Archive</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_req_arc_rejected_text'])?></p>
    <br>
    <div class="optionSelect conceptSheets_optionMenu">
        <div class="d-flex align-items-center justify-content-between w-100">
            <!-- Options Wrapper -->
            <div id="options_wrapper" class="flex-grow-1"></div>

            <!-- Request Type Dropdown -->
            <div class="d-flex align-items-center">
                <label for="selectReqType" class="me-2 mb-0 text-nowrap">Request type:</label>
                <select class="form-select form-select-sm" name="selectReqType" id="selectReqType">
                    <option value="">Select All</option>
                    <?php
                    foreach ($request_type_label as $reqType){
                        echo "<option value='".$reqType."'>".$reqType."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <table class="table table-hover sortable-theme-bootstrap" data-sortable id="table_archive">
            <?php
            $RecordSetRM = \REDCap::getData($pidsArray['RMANAGER'], 'array',null,null,null,null,false,false,false,"[approval_y] != 1");
            $request_reject = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM,$pidsArray['RMANAGER']);
            $commentDetails = $hubData->getCommentDetails();
            if(!empty($request_reject)) {
                $regions = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null,null,null,null,false,false,false,"[showregion_y] = 1");
                ArrayFunctions::array_sort_by_column($regions, 'region_code');

                $user_req_header = getRequestHeader($hubData, $settings['vote_grid'], '2','archive');

                $requests_counter = 0;
                foreach ($request_reject as $req) {
                    $user_req_body .= getHomeRequestHTML($module, $hubData, $pidsArray, $req, $commentsDetails[$req['request_id']], $request_type_label, 2, $settings['vote_visibility'], $settings['vote_grid'],'none','archive');
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

<!-- MODAL EDIT PROCESS -->
<div class="modal fade" id="hub_process_survey" tabindex="-1" aria-labelledby="hub_process_surveyLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hub_process_surveyLabel">Process</h5>
                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame-admin" name="redcap-edit-frame-admin" src="" style="border: none; height: 810px; width: 100%;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
