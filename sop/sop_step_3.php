<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
?>
<script>
    $(document).ready(function () {
        $(".datepicker_aux").datepicker({
            showOn: "button",
            buttonImage: <?=json_encode(DATEICON)?>,
            buttonImageOnly: true,
            buttonText: "Select date",
            dateFormat: "yy-mm-dd",
            onSelect: function(dateText) {
                checkStep(3);
            }
        });

        $('#researchContact_name').change(function(){
            $.ajax({
                type: "POST",
                url: "sop/load_email_AJAX.php",
                data: "&id="+$(this).val(),
                error: function (xhr, status, error) {
                    alert(xhr.responseText);
                },
                success: function (result) {
                    jsonAjax = jQuery.parseJSON(result);
                    $('#researchContact_email').val(jsonAjax);
                }
            });
        });

        // Defer TinyMCE initialization until Step 3 tab is first shown
        var tinymceInitialized = false;
        function initTinyMCEEditors() {
            if (tinymceInitialized) return;
            tinymceInitialized = true;

            var editorConfig = {
                license_key: 'gpl',
                promotion: false,
                height: 200,
                menubar: false,
                branding: false,
                elementpath: false,
                plugins: 'autolink lists link image searchreplace code fullscreen table directionality hr',
                toolbar1: 'fontfamily blocks fontsize bold italic underline strikethrough forecolor backcolor',
                toolbar2: 'align bullist numlist outdent indent table pre hr link fullscreen searchreplace removeformat undo redo code',
                contextmenu: "copy paste | link image inserttable | cell row column deletetable"
            };

            tinymce.init(Object.assign({}, editorConfig, {selector: '#sop_inclusion'}));
            tinymce.init(Object.assign({}, editorConfig, {selector: '#sop_exclusion'}));
            tinymce.init(Object.assign({}, editorConfig, {selector: '#sop_notes'}));
            tinymce.init(Object.assign({}, editorConfig, {selector: '#dataformat_notes'}));
        }

        // Initialize TinyMCE when Step 3 tab is shown
        $('a[href="#step3"]').on('shown.bs.tab', function () {
            initTinyMCEEditors();
        });

        // Also initialize if Step 3 is already active on page load (e.g. editing a draft)
        if ($('#step3').hasClass('active')) {
            initTinyMCEEditors();
        }
    });
</script>
<div id="loader" style=""></div>
<script>
    $(document).ready(function () {
        $('#loader').hide();
    });
</script>
<?php
$record = htmlentities($_REQUEST['record'] ?? '', ENT_QUOTES);
$url = json_encode($module->getUrl('sop/sop_step_1_save_AJAX.php', true));
if($record != ''){?>
    <script>
        $(document).ready(function () {
            var record = <?=json_encode($record)?>;
            var redcap_csrf_token = <?=json_encode($module->getCSRFToken())?>;

            loadNextStep(
                <?=json_encode($module->getUrl('sop/sop_step_1_save_AJAX.php', true))?>,
                {
                    save_option: record,
                    option: 0,
                    id: record,
                    redcap_csrf_token: redcap_csrf_token
                },
                '0'
            );

            $('#save_continue_1').prop('disabled',false);
            $('#save_continue_2').prop('disabled',false);
            $('#save_continue_3').prop('disabled',false);

            $('#sortable1 li').on('keydown', function(e){
                if(e.keyCode == 39){
                    $(this).appendTo('#sortable2');
                }
            });
            $('#sortable1 li').on('keydown', function(e){
                if(e.keyCode == 37){
                    $(this).appendTo('#sortable1');
                }
            });
        });
    </script>
<?php }
$people = \REDCap::getData([
                               'project_id' => $pidsArray['PEOPLE'],
                               'return_format' => 'json-array',
                               'fields' => ['record_id', 'firstname', 'lastname', 'email']
                           ]);
ArrayFunctions::array_sort_by_column($people,'firstname');
if (!empty($people)) {
    $select_people = "<option value=''>Select Name</option>";
    $select_people_creator = "<option value=''>Select Name</option>";
    foreach ($people as $person){
        $recordId = htmlspecialchars($person['record_id'], ENT_QUOTES, 'UTF-8');
        $firstname = htmlspecialchars($person['firstname'], ENT_QUOTES, 'UTF-8');
        $lastname = htmlspecialchars($person['lastname'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($person['email'], ENT_QUOTES, 'UTF-8');
        $selected = ($currentUser['record_id'] === $person['record_id']) ? 'selected' : '';

        $select_people .= "<option value='{$recordId}'>{$firstname} {$lastname} | {$email}</option>";
        $select_people_creator .= "<option value='{$recordId}' {$selected}>{$firstname} {$lastname} | {$email}</option>";
    }
}

$regions = \REDCap::getData([
                                'project_id' => $pidsArray['REGIONS'],
                                'return_format' => 'json-array',
                                'fields' => ['record_id', 'region_name']
                            ]);
$regions = $module->escape($regions);
?>
<div class="container">
    <div class="col-12 py-4 mx-auto" style="max-width: 70%;">
        <div class="sop_builder_header bg-primary text-white fs-5 fw-semibold mb-3">Text Descriptions</div>

        <div class="mb-4">
            <label class="form-label"><strong>Inclusion criteria</strong> <span class="fw-normal fst-italic">(list variable names if possible)</span></label>
            <textarea class="form-control step-3-rich-text-editor w-100" name="sop_inclusion" id="sop_inclusion"></textarea>
            <input type="hidden" id="sop_inclusion_input">
        </div>

        <div class="mb-4">
            <label class="form-label"><strong>Exclusion criteria</strong> <span class="fw-normal fst-italic">(list variable names if possible)</span></label>
            <textarea class="form-control step-3-rich-text-editor w-100" name="sop_exclusion" id="sop_exclusion"></textarea>
            <input type="hidden" id="sop_exclusion_input">
        </div>

        <div class="mb-4">
            <label class="form-label"><strong>Notes</strong> <span class="fw-normal fst-italic">(describe specific DIS_ID and LAB_ID codes requested and other data preparation instructions)</span></label>
            <textarea class="form-control step-3-rich-text-editor w-100" name="sop_notes" id="sop_notes"></textarea>
            <input type="hidden" id="sop_notes_input">
        </div>

        <div class="mb-4 d-none" id="sop_extrapdf_div">
            <label class="form-label">Upload PDF</label>
            <input type="file" class="form-control" name="sop_extrapdf" value="">
            <span id="sop_extrapdf_name"></span>
        </div>

        <div class="sop_builder_header bg-primary text-white fs-5 fw-semibold mb-3">Study Contacts</div>

        <div class="mb-4">
            <label class="form-label"><strong>Research Contact</strong></label>
            <div class="mb-3">
                <label class="form-label">Name / Email:</label>
                <select class="form-select" name="sop_creator" id="sop_creator">
                    <?php echo $select_people; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Institution:</label>
                <input type="text" class="form-control" id="sop_creator_org" name="sop_creator_org" value="">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label"><strong>Research Contact #2</strong> <em class="fst-italic">(optional)</em></label>
            <div class="mb-3">
                <label class="form-label">Name / Email:</label>
                <select class="form-select" name="sop_creator2" id="sop_creator2">
                    <?php echo $select_people; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Institution:</label>
                <input type="text" class="form-control" id="sop_creator2_org" name="sop_creator2_org" value="">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label"><strong>Data Contact</strong>
                <span class="text-danger fw-bold fst-italic" style="font-size: 0.8rem;">*required</span>
            </label>
            <div class="mb-3">
                <label class="form-label">Name / Email:</label>
                <select class="form-select" name="sop_datacontact" id="sop_datacontact" onchange="checkStep(3);">
                    <?php echo $select_people; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Institution:</label>
                <input type="text" class="form-control" id="sop_datacontact_org" name="sop_datacontact_org" value="">
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label"><strong>Data Request Creator Info</strong></label>
            <div class="mb-3">
                <label class="form-label">Name / Email:</label>
                <select class="form-select" name="sop_hubuser" id="sop_hubuser">
                    <?php echo $select_people_creator; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Region</label>
                <select class="form-select" name="sopCreator_region" id="sopCreator_region" onchange="checkStep(3);" disabled>
                    <?php
                    if (!empty($regions)) {
                        foreach ($regions as $region) {
                            $regionId = htmlspecialchars($region['record_id'], ENT_QUOTES, 'UTF-8');
                            $regionName = htmlspecialchars($region['region_name'], ENT_QUOTES, 'UTF-8');
                            $selected = ($currentUser['person_region'] == $region['record_id']) ? " selected" : "";

                            echo "<option value='" . $regionId . "'" . $selected . ">" . $regionName . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="sop_builder_header bg-primary text-white fs-5 fw-semibold mb-3">Data Format and Access</div>
        <div class="form-group">
            <label class="steps_label"><strong>Due Date</strong> <span class="text-danger fw-bold fst-italic ps-2" style="font-size: 0.625rem;">*required</span></label>
            <div><input type="text" class="datepicker_aux form-control data-form-control text-center" style="max-width: 120px; height: 25px;" name="sop_due_d" id="sop_due_d" autocomplete="off" value="" onkeyup="checkStep(3);"/></div>
        </div>
        <div class="form-group">
            <label><strong>Preferred File Format</strong></label>
            <div class="steps_sub_div">
                <label class="steps_sub_label"></label>
                <div style="display: inline-block">
                    <?php
                    $dataformat_prefer = $module->escape($module->getChoiceLabels('dataformat_prefer', $pidsArray['SOP']));
                    foreach($dataformat_prefer as $dataid => $dataformat){
                        echo '<div><input type="checkbox" id="dataformat_prefer___'.$dataid.'" name="dataformat_prefer[]" value="'.$dataid.'" onkeyup="checkStep(3);"><span class="ps-2">'.$dataformat.'</span></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label><strong>File Format Details</strong></label>
            <textarea class="step-3-rich-text-editor w-90" name="dataformat_notes" id="dataformat_notes"></textarea>
            <input type="hidden" id="fileformat_details_input">
        </div>
        <br/>
        <div class="form-group">
            <label class="me-3 float-start"><strong>Choose your Data Downloaders</strong> <span class="text-danger fw-bold fst-italic ps-2" style="font-size: 0.625rem;">*required</span></label>
            <select class="form-select" style="max-width: 150px;" name="dropDown_region" id="dropDown_region" onchange="check_people_region_dragAndDrop();checkStep(3);">
                <option value="" region="all" selected>All Regions</option>
                <?php
                if (!empty($regions)) {
                    $regions = $module->escape($regions);
                    foreach ($regions as $region){
                        echo "<option value='".$region['record_id']."'>".$region['region_name']. "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-md-12 pt-4">
            <ul id="sortable1" class="connectedSortable list-unstyled" style="max-width: 35%;" role="list">
                <?php
                $people_sop = \REDCap::getData([
                                               'project_id' => $pidsArray['PEOPLE'],
                                               'return_format' => 'json-array',
                                               'fields' => ['record_id', 'firstname', 'lastname', 'person_region'],
                                               'filterLogic' => "[active_y] = '1' AND [redcap_name] <> '' AND [allowgetdata_y(1)] = 1"
                                           ]);
                ArrayFunctions::array_sort_by_column($people_sop,'firstname');
                $people_sop = $module->escape($people_sop);
                foreach ($people_sop as $person){
                    if($currentUser['person_region'] == $person['person_region']){
                        echo ' <li tabindex="0" class="ui-state-default" id="'.$person['record_id'].'" region="'.$person['person_region'].'">'.$person['firstname'].' '.$person['lastname'].'</li>';
                    }else{
                        echo ' <li tabindex="0" class="ui-state-default" id="'.$person['record_id'].'" region="'.$person['person_region'].'">'.$person['firstname'].' '.$person['lastname'].'</li>';
                    }
                }
                ?>
            </ul>
            <span class="sortable_doubleArrow mx-3">Drag and Drop <i class="fa fa-arrows-h sortable_doubleArrow_icon"></i></span>
            <ul id="sortable2" class="connectedSortable list-unstyled" style="max-width: 35%;">
            </ul>
        </div>
        <div class="clearfix"></div>
        <div class="form-group">
            <span class="fst-italic me-2">Not sure about Data Downloaders yet.</span><input type="checkbox" onclick="checkStep(3)" name="sop_downloaders_dummy___1" id="sop_downloaders_dummy___1" class="form-check-input">
        </div>

        <div class="form-group">
            <i>Only Hub users with linked REDCap accounts can download data. To keep your data secure, we use REDCap's two-factor authentication to make sure only authorized people can access the data. If you want to add new Data Downloaders, contact <a href="mailto:stephany.duda@vanderbilt.edu">stephany.duda@vanderbilt.edu</a>. The Harmonist team can revise the list of Data Downloaders with you during the Data Request review process.</i>
        </div>
    </div>
</div>