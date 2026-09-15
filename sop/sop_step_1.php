<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$harmonist_perm = ($currentUser['harmonist_perms___1'] == 1) ? true : false;

$sopTemplates = \REDCap::getData([
                                     'project_id' => $pidsArray['SOP'],
                                     'return_format' => 'json-array',
                                     'filterLogic' => "[sop_status] = 2",
                                     'fields' => ['record_id', 'sop_name']
                                 ]);
?>

<script>
    $(document).ready(function () {
        // Initially hide the dropdown
        $('#templateDropdown').removeClass('d-flex').addClass('d-none');

        // Listen for changes to the radio buttons
        $('input[name="optradio"]').on('change', function () {
            const selectedOption = $(this).val();

            if (selectedOption === "2") {
                // Show dropdown
                $('#templateDropdown').removeClass('d-none').addClass('d-flex');
            } else {
                // Hide dropdown
                $('#templateDropdown').removeClass('d-flex').addClass('d-none');
            }
        });
    });
</script>
<div class="container">
    <div class="col-12 py-3">
        <div class="d-flex align-items-center">
            <div class="fw-bold ps-3 me-3" style="width: 180px;">
                Select Your Concept:
            </div>
            <div class="w-50">
                <select class="form-select" name="selectConcept" id="selectConcept" onchange="checkStep(1)">
                    <option value="">Select option</option>
                    <?php
                    $RecordSetConceptsActive = \REDCap::getData([
                                                                 'project_id' => $pidsArray['HARMONIST'],
                                                                 'return_format' => 'array',
                                                                 'filterLogic' => "[active_y] = 'Y'"
                                                             ]);
                    $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsActive, $pidsArray['HARMONIST']);
                    ArrayFunctions::array_sort_by_column($concepts, 'concept_id');
                    if (!empty($concepts)) {
                        $concepts = $module->escape($concepts);
                        foreach ($concepts as $concept) {
                            $concept_short_title = strlen($concept['concept_title']) > 120 ? substr($concept['concept_title'], 0, 120) . "..." : $concept['concept_title'];
                            echo "<option value='" . $concept['record_id'] . "' concept='" . $concept['concept_id'] . "'>" . $concept['concept_id'] . " - " . $concept_short_title . "</option>";
                        }
                    }
                    ?>
                </select>
                <span class="text-secondary fst-italic">For test requests, select MR000.</span>
            </div>
        </div>
    </div>
    <div class="col-12 py-3 fw-bold">
        <div class="d-flex align-items-center">
            <div class="ps-3" style="width: 180px;">
                Setup Type:
            </div>
            <div class="ms-2">
                <label class="form-check form-check-inline me-3">
                    <input type="radio" class="form-check-input" name="optradio" id="optradio_1" onclick="checkStep(1)" value="1">
                    Create new data request
                </label>
                <label class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="optradio" id="optradio_2" onclick="checkStep(1)" value="2">
                    Start from template
                </label>
            </div>
        </div>
        <!-- Template Dropdown -->
        <div class="d-flex align-items-center mt-3" id="templateDropdown">
            <div class="fw-bold ps-3 me-3" style="width: 180px;">
            </div>
            <div class="w-50">
                <select id="templateSelect" class="form-select" onchange="checkStep(1)">
                    <option value="" selected>Choose a template...</option>
                    <?php
                    foreach ($sopTemplates as $template) {
                        echo "<option value='" . $module->escape($template['record_id']) . "'>" . $module->escape($template['sop_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <input type="hidden" value="" id="save_option" name="save_option">
        <input type="hidden" value="" id="template_option" name="template_option">
    </div>
</div>
