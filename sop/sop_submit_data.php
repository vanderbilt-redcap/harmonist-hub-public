<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
?>
<script language="JavaScript">
    $(document).ready(function() {
        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            let selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
        });

        $("#sortable_table").dataTable( {"pageLength": 50});
        $('#dataUploadForm').submit(function () {
            let data = $('#dataUploadForm').serialize();
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            uploadDataToolkit(data,<?=json_encode($module->getUrl("hub/hub_data_upload_security_AJAX.php")."&NOAUTH")?>);
            return false;
        });

        $('#changeStatus').submit(function () {
            let data = "&status="+$('.dropdown-toggle-custom-status').attr('id');
            data += '&redcap_csrf_token=' + <?=json_encode($module->getCSRFToken())?>;
            data += "&region="+$('#region').val();
            data += "&status_record="+$('#status_record').val();
            data += "&data_response_notes="+encodeURIComponent($('#data_response_notes').val());
            CallAJAXAndRedirect(data,<?=json_encode($module->getUrl('sop/sop_submit_data_change_status_AJAX.php', true))?>,<?=json_encode($module->getUrl("index.php", true) . "&option=upd&message=S")?>);
            return false;
        });

        jQuery('[data-toggle="popover"]').popover({
            html : true,
            content: function() {
                return $(jQuery(this).data('target-selector')).html();
            },
            title: function(){
                return '<span style="padding-top:0px;">'+jQuery(this).data('title')+'<span class="close" style="line-height: 0.5;padding-top:0px;padding-left: 10px">&times;</span></span>';
            }
        }).on('shown.bs.popover', function(e){
            var popover = jQuery(this);
            jQuery(this).parent().find('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });
            $('div.popover .close').on('click', function(e){
                popover.popover('hide');
            });

        });
        //We add this or the second time we click it won't work. It's a bug in bootstrap
        $('[data-toggle="popover"]').on("hidden.bs.popover", function() {
            if($(this).data("bs.popover").inState == undefined){
                //BOOTSTRAP 4
                $(this).data("bs.popover")._activeTrigger.click = false;
            }else{
                //BOOTSTRAP 3
                $(this).data("bs.popover").inState.click = false;
            }
        });

        //To prevent the popover from scrolling up on click
        $("a[rel=popover]")
            .popover()
            .click(function(e) {
                e.preventDefault();
            });
    } );

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

<?php
$RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', null);
$request_dataCall = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'],['sop_active' => '1', 'sop_finalize_y' => [1=>'1']]);
ArrayFunctions::array_sort_by_column($request_dataCall,'sop_due_d');
$open_data_calls = "";
$completed_data_calls = "";
$personRegion = arrayKeyExistsReturnValue($currentUser, ['person_region']);
if(!empty($request_dataCall)) {
    foreach ($request_dataCall as $sop) {
        if ($sop['sop_closed_y'] != "1") {
            $sopDataResponseStatus = arrayKeyExistsReturnValue($sop, ['data_response_status',$personRegion]);
            if($sopDataResponseStatus == "0" || $sopDataResponseStatus == "1" || $sopDataResponseStatus == ""){
                $open_data_calls .= getDataCallRow($module, $pidsArray,$sop,$isAdmin,$currentUser,$secret_key,$secret_iv,$settings['vote_grid'],'s');
            }else{
                $completed_data_calls .= getDataCallRow($module, $pidsArray,$sop,$isAdmin,$currentUser,$secret_key,$secret_iv,$settings['vote_grid'],'s');
            }
        }
    }
}

if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){
    ?><div class="alert alert-success col-12" id="succMsgContainer">
        Your Data Request status has been successfully modified.
    </div><?php
}
?>

<div class="backTo">
    <a href="<?=$module->getUrl('index.php', true).'&option=dat'?>">< Back to Data</a>
</div>

<div class="optionSelect">
    <h3>Check and Submit Data</h3>
    <?=filter_tags($settings['hub_check_submit_text'])?>
</div>

<div class="container">
    <div class="row align-items-center">
        <!-- Legend on the left -->
        <div class="col-md-8">
            <ul class="list-inline d-inline-flex align-items-center mb-0">
                <li class="list-inline-item"><span class="badge bg-light" title="Not Started"><i class="fa-label-legend fa fa-times text-secondary-light" aria-hidden="true"></i></span> <a href="#" data-bs-toggle="tooltip" title="No regional activity on this request." data-bs-placement="top" class="custom-tooltip" style="vertical-align: -2px; cursor: default;">Not started</a></li>
                <li class="list-inline-item"><span class="badge bg-warning" title="Partial Data"><i class="fa-label-legend fa fa-wrench" aria-hidden="true"></i></span> <a href="#" data-bs-toggle="tooltip" title="Your region has submitted some but not all data for this data request." data-bs-placement="top" class="custom-tooltip" style="vertical-align: -2px; cursor: default;">Partial Data</a></li>
                <li class="list-inline-item"><span class="badge bg-success" title="Complete Data"><i class="fa-label-legend fa fa-check" aria-hidden="true"></i></span> <a href="#" data-bs-toggle="tooltip" title="Your region has submitted a full regional dataset for this data request." data-bs-placement="top" class="custom-tooltip" style="vertical-align: -2px; cursor: default;">Complete Data</a></li>
                <li class="list-inline-item"><span class="badge bg-secondary" title="Not Applicable Data"><i class="fa-label-legend fa fa-ban" aria-hidden="true"></i></span> <a href="#" data-bs-toggle="tooltip" title="Your region does not have the requested data for this project." data-bs-placement="top" class="custom-tooltip" style="vertical-align: -2px; cursor: default;">Data Not Available</a></li>
                <li class="list-inline-item"><span class="badge bg-secondary" title="Not Applicable Region"><i class="fa-label-legend fa fa-times" aria-hidden="true"></i></span> <a href="#" data-bs-toggle="tooltip" title="Your region is not one of the requested regions for this project." data-bs-placement="top" class="custom-tooltip" style="vertical-align: -2px; cursor: default;">Region Not Requested</a></li>
                <li class="list-inline-item"><span class="badge bg-other" title="Other Status"><i class="fa-label-legend fa fa-question" aria-hidden="true"></i></span> <a href="#" data-bs-toggle="tooltip" title="Some other data submission or project participation status applies." data-bs-placement="top" class="custom-tooltip" style="vertical-align: -2px; cursor: default;">Other Status</a></li>
            </ul>
        </div>

        <!-- Links on the right -->
        <div class="col-md-4 text-end">
            <p class="mb-0"><a href="<?=$module->getUrl('index.php', true).'&option=lgd&type=upload'?>">View Data Activity Log</a> | <a href="<?=$module->getUrl('index.php', true)."&option=pdc"?>">View Past Data Calls</a></p>
        </div>
    </div>
</div>

<div class="pt-2">
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="card-title mb-0">
                Open Data Calls
            </h6>
        </div>
        <div class="table-responsive table-no-borders">
            <table class="table sortable-theme-bootstrap" data-sortable>
                <?php
                if(!empty($open_data_calls)) {
                    echo getDataCallHeader($hubData, $pidsArray['REGIONS'], $personRegion, $settings['vote_grid']);
                    echo $open_data_calls;
                } else { ?>
                    <tbody>
                    <tr>
                        <td class="ps-3"><span><em>No Open Data Calls available</em></span></td>
                    </tr>
                    </tbody>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header hub_requests_completed">
            <h6 class="card-title mb-0">
                Completed Data Calls
            </h6>
        </div>
        <div class="table-responsive table-no-borders">
            <table class="table sortable-theme-bootstrap" data-sortable>
                <?php
                if(!empty($completed_data_calls)) {
                    echo getDataCallHeader($hubData, $pidsArray['REGIONS'], $personRegion, $settings['vote_grid']);
                    echo $completed_data_calls;
                } else { ?>
                    <tbody>
                    <tr>
                        <td><span><em>No Completed Data Calls available</em></span></td>
                    </tr>
                    </tbody>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-data-upload-confirmation" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <form class="needs-validation" action="" method="post" id="dataUploadForm">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Data</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you ready to upload data for concept <span id="data-submit-concept" class="fw-bold"></span>?</p>
                    <p>This will redirect you to the Data Toolkit, where you can check and/or submit data.</p>
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

<div class="modal fade" id="modal-data-change-status" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <form class="needs-validation" action="" method="post" id="changeStatus">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Status</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">Last update on <i id="region_update_ts"></i></div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3" style="line-height: 30px;">Set my status:</div>
                        <div>
                            <?php
                            $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);
                            $status_icon_color = $module->escape([0 => "text-secondary", 1 => "text-warning", 2 => "text-success", 3 => "text-danger", 4 => "text-secondary", 9 => "text-info"]);
                            $status_icon = $module->escape([0 => "fa-times", 1 => "fa-wrench", 2 => "fa-check", 3 => "fa-ban", 4 => "fa-times", 9 => "fa-question"]);

                            // Default dropdown display value
                            $default_status_index = $sop['data_response_status'][$currentUser['person_region']] ?? 0; // Default to index 0 if no value
                            $selected = '<span class="d-flex align-items-center">' .
                                '<i class="fa bg-secondary-light fa-times text-secondary-light me-2" aria-hidden="true" id="0"></i>' .
                                '<span class="dropdown_votes">Not Started</span>' .
                                '</span>';

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
                                <button class="btn btn-light dropdown-toggle dropdown-toggle-custom-status form-control d-flex justify-content-between align-items-center" type="button" id="<?=$module->escape(array_key_first($status_type)) ?>" data-bs-toggle="dropdown" aria-expanded="false" style="min-width: 320px; height: 40px;">
                                    <?=filter_tags($selected)?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="default-select-value" style="min-width: 320px; max-height: 250px; overflow-y: auto;">
                                    <?=$menu?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">Status notes (only visible to your own region):</div>
                    <div>
                        <textarea class="form-control" id="data_response_notes" name="data_response_notes" style="height: 100px;"></textarea>
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

<div class="modal fade" id="hub_view_votes" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">All Votes</h5>
                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
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
