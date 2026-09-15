<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$record_id = htmlentities($_REQUEST['record'],ENT_QUOTES);
if($record_id != ""){
    $RecordSetSOP = \REDCap::getData($pidsArray['SOP'], 'array', ["record_id" => $record_id]);
    $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP,$pidsArray['SOP'])[0];
}

$harmonist_perm = ($currentUser['harmonist_perms___1'] == 1) ? true : false;

if($routes->canAccessDataRequestBuilder($sop)){
    ?>
    <script>
        let originalCSRF = <?=json_encode($module->getCSRFToken())?>;
        let redcap_csrf_token = '&redcap_csrf_token=' + originalCSRF;
        let redcapCsrfToken = originalCSRF;

        // Function to scroll the page to the top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth' // Smooth scrolling effect
            });
        }
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach((tooltipTriggerEl) => {
                bootstrap.Tooltip.getInstance(tooltipTriggerEl)?.dispose();
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Ensure tabs load correctly
            const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabs.forEach((tab) => {
                tab.addEventListener('click', (event) => {
                    event.preventDefault();

                    // Remove active classes from all tabs and panes
                    tabs.forEach((t) => {
                        t.classList.remove('active');
                        t.parentElement.classList.remove('active'); // Also update <li>
                    });
                    document.querySelectorAll('.tab-pane').forEach((pane) => pane.classList.remove('show', 'active'));

                    // Add active classes to the clicked tab and corresponding pane
                    tab.classList.add('active');
                    tab.parentElement.classList.add('active'); // Also update <li>
                    const targetPane = document.querySelector(tab.getAttribute('href'));
                    targetPane.classList.add('show', 'active');

                    // Scroll to the top of the page when switching tabs
                    scrollToTop();
                });
            });

            // Tab show event: Update the active class on the parent <li> element
            document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function (e) {
                    const target = e.target; // The clicked <a> element
                    const targetParent = target.parentElement; // The parent <li> of the clicked <a>

                    // Remove active class from all <li> elements
                    document.querySelectorAll('.wizard .nav-tabs li').forEach(function (li) {
                        li.classList.remove('active');
                    });

                    // Add active class to the clicked tab's parent <li>
                    targetParent.classList.add('active');

                    // Scroll to the top of the page when a tab is shown
                    scrollToTop();
                });
            });

            // Next Step Button Click Event
            document.querySelectorAll('.next-step').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    // Find the tab pane this button belongs to
                    const currentPane = btn.closest('.tab-pane');
                    const currentPaneId = currentPane ? currentPane.id : null;

                    // Find the corresponding li by matching the href
                    const currentLi = currentPaneId
                        ? document.querySelector(`.wizard .nav-tabs li a[href="#${currentPaneId}"]`)?.parentElement
                        : document.querySelector('.wizard .nav-tabs li.active');

                    const nextTab = currentLi ? currentLi.nextElementSibling : null;

                    if (nextTab) {
                        nextTab.classList.remove('disabled'); // Enable the next tab

                        const nextTabLink = nextTab.querySelector('a[data-bs-toggle="tab"]');
                        nextTabLink.click();

                        // Scroll to the top when moving to the next step
                        scrollToTop();
                    }
                });
            });

            // Previous Step Button Click Event
            document.querySelectorAll('.prev-step').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const activeTab = document.querySelector('.wizard .nav-tabs li.active');
                    const prevTab = activeTab.previousElementSibling;

                    if (prevTab) {
                        const prevTabLink = prevTab.querySelector('a[data-bs-toggle="tab"]');
                        prevTabLink.click();

                        // Scroll to the top when moving to the previous step
                        scrollToTop();
                    }
                });
            });
        });

        var selConcept = "";
        $(document).ready(function () {
            $('#save_and_stay').css('margin-top', '-60px')
            $('#save_and_stay').addClass('pr-0')
            //STEPS FORM
            var sButton = null;
            var $form = $('#form_steps_menu');
            var $submitButtons = $form.find('.saveAndContinue');
            $('#form_steps_menu').submit(function (event) {

                if (null === sButton) {
                    sButton = $submitButtons[0];
                }

                let id = getOption();

                if(sButton.name == 'save_continue_0' || sButton.name == 'save_continue_1'){
                    let id = getOption();
                    let sop_hubuser = <?=json_encode($currentUser['record_id'])?>;

                    let saveoption = $('#save_option').val();

                    loadNextStep(
                        <?=json_encode($module->getUrl('sop/sop_step_1_save_AJAX.php', true))?>,
                        {
                            save_option: saveoption,
                            template_option: $('#template_option').val(),
                            selectConcept: $('#selectConcept').val(),
                            option: $('[name=optradio]:checked').val(),
                            sop_hubuser: sop_hubuser,
                            id: id,
                            redcap_csrf_token: redcapCsrfToken
                        },
                        '1'
                    );

                }else if(sButton.name == 'save_continue_2'){
                    var checked_values = [];

                    // Enforce required variables first (this checks them + their doubles in the DOM)
                    check_required_variables();

                    // Now collect ALL checked boxes as individual tokens (includes required + doubles)
                    $("input[name='tablefields[]']:checked").each(function() {
                        checked_values.push($(this).val());
                    });

                    loadNextStep(
                        <?=json_encode($module->getUrl('sop/sop_step_2_save_AJAX.php', true))?>,
                        {
                            checked_values: checked_values,
                            id: id,
                            redcap_csrf_token: redcapCsrfToken
                        },
                        '2'
                    );

                }else if(sButton.name == 'save_continue_3' || sButton.name == 'save_and_stay') {
                    deleteFile($('#selectSOP_'+$('[name=optradio]:checked').val()).val());
                    if ($("[name=sop_extrapdf]").val() != ""){
                        saveFilesIfTheyExist('sop/save-file.php', this, redcap_csrf_token);
                    }

                    var checked_values = [];
                    $("input[name='dataformat_prefer[]']:checked").each(function() {
                        checked_values.push($(this).val());
                    });

                    //Transform form data to object
                    var formObj = $('#form_steps_menu').serializeArray().reduce(function(acc, cur) {
                        acc[cur.name] = cur.value;
                        return acc;
                    }, {});

                    loadNextStep(
                        <?=json_encode($module->getUrl('sop/sop_step_3_save_AJAX.php', true))?>,
                        $.extend(formObj, {
                            dataformat_prefer: checked_values,
                            sop_inclusion: tinyMCE.get('sop_inclusion').getContent(),
                            sop_exclusion: tinyMCE.get('sop_exclusion').getContent(),
                            sop_notes: tinyMCE.get('sop_notes').getContent(),
                            dataformat_notes: tinyMCE.get('dataformat_notes').getContent(),
                            downloaders: $("#sortable2").sortable("toArray"),
                            id: id,
                            redcap_csrf_token: redcapCsrfToken
                        }),
                        '3'
                    );

                    if(sButton.name == 'save_and_stay'){
                        $('#modal-save-and-stay').modal('show');
                        //After 25 seconds hide message
                        setTimeout(function(){ $('#modal-save-and-stay').modal('hide'); }, 25000);
                    }

                }else if(sButton.name == 'save_continue_4') {
                    $('#previous_4').css('right','230px');
                    $('#save_continue_4_spinner').addClass('fa fa-spinner fa-spin');

                    const loader = new DataRequestBuilderStepLoader(<?=json_encode($module->getUrl('sop/sop_step_4_save_AJAX.php', true))?>, true, <?=json_encode($module->getUrl('index.php', true).'&&option=ss5')?>);
                    loader.generatePDF($('#save_option').val(), redcapCsrfToken);
                }
                return false;
            });

            $submitButtons.click(function(event) {
                sButton = this;
            });

            //STEP 3
            $( "#sortable1, #sortable2" ).sortable({
                connectWith: ".connectedSortable",
                cursor: "move",
                dropOnEmpty: true
            }).disableSelection();

            $( ".connectedSortable" ).sortable({
                receive: function( event, ui ) {
                    checkStep(3);
                }
            });

            function saveFilesIfTheyExist(url, files, redcap_csrf_token) {
                var formData = new FormData(files);

                // Append the CSRF token to the FormData
                formData.append("redcap_csrf_token", redcap_csrf_token);

                $.ajax({
                    url: url,
                    type: "POST",
                    data:  formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(returnData){
                        if (returnData.status != 'success') {
                            alert(returnData.status+" One or more of the files could not be saved."+JSON.stringify(returnData));
                        }else{
                            getFileFieldElement(returnData.edoc)
                        }
                    }
                });
            }
        });

        function nextTab(elem) {
            $(elem).next().find('a[data-toggle="tab"]').click();
            $('html,body').scrollTop(0);
        }
        function prevTab(elem) {
            $(elem).prev().find('a[data-toggle="tab"]').click();
            $('html,body').scrollTop(0);
        }

        function getOption(){
            let id = $('#selectSOP_'+$('[name=optradio]:checked').val()).val();
            if (id == undefined || id == ''){
                id = $('#save_option').val();
            }
            if (id == undefined || id == ''){
                id = <?=json_encode($record_id)?>;
            }
            return id;
        }

        function loadNextStep(url, data, step){
            const loader = new DataRequestBuilderStepLoader(url, true);
            loader.loadSteps(data, step);
            loader.resetMenuButton((Number(step) || 0) + 1);
            loader.disableStep1Options();
        }
    </script>
    <?php
    $step = htmlentities($_REQUEST['step']);

    // Set default values for active classes
    $step1Active = '';
    $step2Active = '';
    $step3Active = '';
    $step4Active = '';

    $step1Active = 'active show'; // Default to Step 1 if no step is specified
    $step1DisableBtns = ($step != '3') ? 'onclick="return false;" style="pointer-events: none; cursor: not-allowed;"' : ''; // Disable all menu buttons
    if($step == '3') {
        $step3Active = 'active show'; // Make Step 3 active by default

        echo '<script>$(document).ready(function () {'.
            'var step = '.json_encode($step).';'.
            '$("#title_step_1").removeClass("active");'.
            '$("#title_step_1").removeClass("disabled");'.
            '$("#title_step_2").removeClass("disabled");'.
            '$("#title_step_3").removeClass("disabled");'.
            '$("#title_step_4").removeClass("disabled");'.
            '$("#step1").removeClass("active");'.
            '$("#step"+step).addClass("active");'.
            '$("#title_step_"+step).addClass("active");});'.
            '</script>';
    }
    ?>
    <div class="container">
        <div class="row">
            <section>
                <div class="wizard">
                    <div class="wizard-inner position-relative">
                        <div class="connecting-line position-absolute w-100 top-50 start-20 translate-middle"></div>
                        <ul class="nav nav-tabs justify-content-center" role="tablist">
                            <!-- Step 1 -->
                            <li role="presentation" class="nav-item <?php echo $step1Active; ?>" id="title_step_1">
                                <a href="#step1" class="nav-link <?php echo $step1Active; ?>" data-bs-toggle="tab" aria-controls="step1" role="tab" title="Step 1: Setup">
                                <span class="round-tab d-flex align-items-center justify-content-center">
                                    <i class="fa fa-solid fa-cog"></i>
                                </span>
                                </a>
                            </li>
                            <!-- Step 2 -->
                            <li role="presentation" class="nav-item <?php echo $step2Active; ?>" id="title_step_2">
                                <a href="#step2" class="nav-link <?php echo $step2Active; ?>" <?php echo $step1DisableBtns; ?> data-bs-toggle="tab" aria-controls="step2" role="tab" title="Step 2: Choose Variables">
                                <span class="round-tab d-flex align-items-center justify-content-center">
                                    <i class="fa fa-solid fa-hand-pointer-o"></i>
                                </span>
                                </a>
                            </li>
                            <!-- Step 3 -->
                            <li role="presentation" class="nav-item <?php echo $step3Active; ?>" id="title_step_3">
                                <a href="#step3" class="nav-link <?php echo $step3Active; ?>" <?php echo $step1DisableBtns; ?> data-bs-toggle="tab" aria-controls="step3" role="tab" title="Step 3: Add Details">
                                <span class="round-tab d-flex align-items-center justify-content-center">
                                    <i class="fa fa-solid fa-pencil"></i>
                                </span>
                                </a>
                            </li>
                            <!-- Step 4 -->
                            <li role="presentation" class="nav-item <?php echo $step4Active; ?>" id="title_step_4">
                                <a href="#step4" class="nav-link <?php echo $step4Active; ?>" <?php echo $step1DisableBtns; ?> data-bs-toggle="tab" aria-controls="step4" role="tab" title="Step 4: Preview Data Request">
                                <span class="round-tab d-flex align-items-center justify-content-center">
                                    <i class="fa fa-solid fa-check"></i>
                                </span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <form method="POST" action="" id="form_steps_menu">
                        <div class="tab-content mt-4">
                            <!-- Step 1 -->
                            <div class="tab-pane fade <?php echo $step1Active; ?>" role="tabpanel" id="step1">
                                <h3><span class="text-primary fw-bold">STEP 1:</span>&nbsp;&nbsp;Setup</h3>
                                <div><?= filter_tags($settings['hub_step1']) ?></div>
                                <?php include('sop_step_1.php'); ?>
                                <ul class="list-inline text-end">
                                    <li><button type="submit" class="btn btn-primary saveAndContinue" disabled id="save_continue_1" name="save_continue_1">Save and continue</button></li>
                                </ul>
                            </div>

                            <!-- Step 2 -->
                            <div class="tab-pane fade <?php echo $step2Active; ?>" role="tabpanel" id="step2">
                                <h3><span class="text-primary fw-bold">STEP 2:</span>&nbsp;&nbsp;Choose Variables</h3>
                                <p><em><span class="fa fa-pencil"></span> <span name="step_concept_id" class="fw-bold"></span> <span name="step_sop"></span></em></p>
                                <div><?= filter_tags($settings['hub_step2']) ?></div>
                                <?php include('sop_step_2.php'); ?>
                                <ul class="list-inline text-end">
                                    <li class="list-inline-item">
                                        <button type="button" class="btn btn-secondary me-0 prev-step" id="previous_2">Previous</button>
                                    </li>
                                    <li class="list-inline-item">
                                        <button type="submit" class="btn btn-primary saveAndContinue" disabled id="save_continue_2" name="save_continue_2">Save and continue</button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Step 3 -->
                            <div class="tab-pane fade <?php echo $step3Active; ?>" role="tabpanel" id="step3">
                                <div class="alert alert-warning fade show d-none" id="warnMsgContainer">
                                    <a href="#" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Close"></a>
                                    <span id="warnMsgContainerText"></span>
                                </div>
                                <h3><span class="text-primary fw-bold">STEP 3:</span>&nbsp;&nbsp;Add Details</h3>
                                <p><em><span class="fa fa-pencil"></span> <span name="step_concept_id" class="fw-bold"></span> <span name="step_sop"></span></em></p>
                                <div><?= filter_tags($settings['hub_step3']) ?></div>
                                <?php include('sop_step_3.php'); ?>
                                <ul class="list-inline text-end">
                                    <li class="list-inline-item">
                                        <button type="button" class="btn btn-secondary me-0 prev-step" id="previous_3">Previous</button>
                                    </li>
                                    <li class="list-inline-item">
                                        <button type="submit" class="btn btn-primary saveAndContinue" disabled id="save_continue_3" name="save_continue_3">Save and continue</button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Step 4 -->
                            <div class="tab-pane fade <?php echo $step4Active; ?>" role="tabpanel" id="step4">
                                <h3><span class="text-primary fw-bold">STEP 4:</span>&nbsp;&nbsp;Preview Data Request</h3>
                                <div><?= filter_tags($settings['hub_step4']) ?></div>
                                <?php include('sop_step_4.php'); ?>
                                <ul class="list-inline text-end">
                                    <li class="list-inline-item">
                                        <button type="button" class="btn btn-secondary me-0 prev-step" id="previous_4">Previous</button>
                                    </li>
                                    <li class="list-inline-item">
                                        <button type="submit" class="btn btn-primary next-step saveAndContinue" id="save_continue_4" name="save_continue_4"><span id="save_continue_4_spinner"></span> Save and create PDF</button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="modal-save-and-stay" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Data Saved</h5>
                    <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <span>Your information has been successfully saved.</span>
                </div>
            </div>
        </div>
    </div>
    <?php
}else{
    ?>
    <div class="alert alert-warning fade show w-100">
        <em>Data Request #<?=$record_id?> is not available at this time.</em>
    </div>
    <?php
} ?>
