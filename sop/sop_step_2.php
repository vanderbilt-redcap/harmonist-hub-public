<?PHP
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$dataTable = generateTableArray($module, $pidsArray['DATAMODEL']);
$first_table = 0;

$type_status = $module->getChoiceLabels('available_status', $pidsArray['DATAMODEL']);
$type_label = [0 => 'label-available-few', 1 => 'label-available-some', 2 => 'label-available-most', 3 => 'label-available-all', 99 => 'label-unknown'];
$type_icon = [0 => 'fa-circle-o', 1 => 'fa-adjust', 2 => 'fa-circle', 99 => 'fa-question'];

$tr_class = ($indexSubSet > 0) ? '' : 'in';

// Pre-fetch all code list records
$allCodeListRefs = [];
foreach ($dataTable as $data) {
    if (!empty($data['record_id']) && !empty($data['code_list_ref']) && is_array($data['code_list_ref'])) {
        foreach ($data['code_list_ref'] as $ref) {
            if (!empty($ref)) {
                $allCodeListRefs[$ref] = true;
            }
        }
    }
}
$codeListCache = [];
if (!empty($allCodeListRefs)) {
    $codeListRecords = \REDCap::getData([
            'project_id' => $pidsArray['CODELIST'],
            'return_format' => 'json-array',
            'records' => array_keys($allCodeListRefs)
    ]);
    foreach ($codeListRecords as $clRecord) {
        $codeListCache[$clRecord['record_id']] = $clRecord;
    }
}

// Track modals to render OUTSIDE the table
$renderedModals = [];
$modalsToRender = [];
$record_id_array = '';
?>
<script>
    $(document).ready(function () {
        $('.table_requests').removeClass('rowSelected');
    });
</script>
<div class="container col-12 py-3">
    <div class="accordion" id="dataAccordion">
        <?php foreach ($dataTable as $data):
            if (empty($data['record_id'])) continue;

            $record_id_array .= $data['record_id'] . ',';

            $table_draft = '';
            $table_draft_text = '';
            if (array_key_exists('table_status', $data)) {
                $table_draft = ($data['table_status'] === '0') ? 'des_draft_header' : 'bg-light';
                $table_draft_text = ($data['table_status'] === '0') ? '<span class="text-danger fst-italic"> (DRAFT)</span>' : '';
            }

            $first_table++;
            $tableRecordId = htmlspecialchars($data['record_id'], ENT_QUOTES);
            $tableName = htmlspecialchars($data['table_name'], ENT_QUOTES);
            ?>
            <div class="accordion-item">
                <h6 class="accordion-header d-flex align-items-center p-2 <?= $table_draft ?>" id="heading<?= $tableRecordId ?>" style="font-size: 0.95rem;">
                    <div class="flex-grow-1 d-flex align-items-center">
                        <span class="me-2">
                            <span id="table_<?= $tableRecordId ?>" class="table_name badge label-as-badge-square des-<?= htmlspecialchars($data['table_category'], ENT_QUOTES) ?>"><?= $tableName ?></span><?= $table_draft_text ?>
                        </span>
                        <span class="badge bg-primary rounded-pill me-3" id="counter_<?= $tableRecordId ?>"></span>
                        <div class="d-flex align-items-center">
                            <input type="checkbox" id="ckb_<?= $tableName ?>" name="chkAll_<?= $tableRecordId ?>"
                                   onclick="event.stopPropagation(); checkAll('<?= $tableRecordId ?>'); check_required_variables(); checkStep(2)"
                                   class="form-check-input">
                            <span class="ms-2" onclick="event.stopPropagation(); checkAllText('<?= $tableRecordId ?>'); check_required_variables(); checkStep(2)">Select All</span>
                        </div>
                    </div>
                    <a class="collapseText d-flex align-items-center ms-auto pe-2 toggle-icon bg-light text-decoration-none <?= $table_draft ?>" data-bs-toggle="collapse" href="#collapse<?= $tableRecordId ?>" role="button" aria-expanded="false" aria-controls="collapse<?= $tableRecordId ?>">
                        <i class="fa fa-chevron-down ms-auto" aria-hidden="true"></i>
                    </a>
                </h6>
                <div id="collapse<?= $tableRecordId ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $tableRecordId ?>" data-bs-parent="#dataAccordion">
                    <div class="accordion-body p-0">
                        <table class="table desTable sopTable table-hover" id="desTable_<?= $tableRecordId ?>">
                            <colgroup>
                                <col style="width: 6%;"><col style="width: 14%;"><col style="width: 10%;"><col style="width: 20%;"><col style="width: 50%;">
                            </colgroup>
                            <thead>
                            <tr>
                                <th class="sorting_disabled border-0" data-sortable="false">Select</th>
                                <th class="sorting_disabled border-0" data-sortable="false">Field</th>
                                <th class="sorting_disabled border-0" data-sortable="false">Availability</th>
                                <th class="sorting_disabled border-0" data-sortable="false">Format</th>
                                <th class="sorting_disabled border-0" data-sortable="false">Description</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data['variable_order'] as $id => $value):
                                if (($data['variable_status'][$id] ?? '') === '2') continue;

                                $variable_status_attr = '';
                                $variable_display = '';
                                $variable_text = '';
                                if (($data['variable_status'][$id] ?? '') === '0') {
                                    $variable_status_attr = "class='des_draft'";
                                    $variable_text = "<span class='text-danger fw-bold'>DRAFT</span><br/>";
                                }

                                $record_varname = htmlspecialchars(empty($id) ? $data['record_id'] . '_1' : $data['record_id'] . '_' . $id, ENT_QUOTES);
                                $name = htmlspecialchars($data['variable_name'][$id], ENT_QUOTES);
                                $variable_required = (isset($data['variable_required'][$id][0]) && $data['variable_required'][$id][0] == 1) ? 'Y' : 'N';

                                $availStatus = !empty($data['available_status'][$id]) ? $data['available_status'][$id] : 99;
                                $type_text = htmlspecialchars($type_status[$availStatus], ENT_QUOTES);
                                $type_color = htmlspecialchars($type_label[$availStatus], ENT_QUOTES);
                                ?>
                                <tr record_id="<?= $record_varname ?>" <?= $variable_status_attr ?> parent_table="<?= $tableRecordId ?>" class="<?= $variable_display ?>" onclick="checkselectDoubles('<?= $record_varname ?>'); checkStep(2)">
                                    <td>
                                        <input value="<?= $record_varname ?>" id="record_id_<?= $record_varname ?>" onclick="checkselectDoubles('<?= $record_varname ?>'); check_required_variables(); checkStep(2)" chk_name="chk_table_<?= $tableRecordId ?>" class="form-check-input auto-submit" type="checkbox" name="tablefields[]" variable_required="<?= $variable_required ?>">
                                    </td>
                                    <td id="name_<?= $record_varname ?>"><?= trim($name) ?></td>
                                    <td><span class="badge <?= $type_color ?>" style="font-size: 12px;"><?= $type_text ?></span></td>
                                    <td>
                                        <?php
                                        $dataFormat = $dataTable['data_format_label'][$data['data_format'][$id]] ?? '';

                                        if (($data['has_codes'][$id] ?? '0') === '0') {
                                            echo htmlspecialchars($dataFormat, ENT_QUOTES);
                                            if (!empty($data['code_text'][$id])) {
                                                echo "<br/>" . htmlspecialchars($data['code_text'][$id], ENT_QUOTES);
                                            }
                                        } elseif (($data['has_codes'][$id] ?? '') === '1' && !empty($data['code_list_ref'][$id])) {
                                            $codeformat = $codeListCache[$data['code_list_ref'][$id]] ?? [];

                                            if (($codeformat['code_format'] ?? '') === '1') {
                                                $codeOptions = empty($codeformat['code_list']) ? $data['code_text'][$id] : explode(" | ", $codeformat['code_list']);
                                                if (!empty($codeOptions[0])) {
                                                    $dataFormat .= "<div class='ps-3'>";
                                                }
                                                foreach ($codeOptions as $option) {
                                                    $dataFormat .= htmlspecialchars($option, ENT_QUOTES) . "<br/>";
                                                }
                                                if (!empty($codeOptions[0])) {
                                                    $dataFormat .= "</div>";
                                                }
                                                echo $dataFormat;

                                            } elseif (($codeformat['code_format'] ?? '') === '3') {
                                                echo 'Numeric<br/>';
                                                if (!empty($codeformat['code_file'])) {
                                                    // Build a valid, unique HTML id for the modal target
                                                    $modalId = 'codesModal_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codeformat['code_file']);
                                                    $modalIdEsc = htmlspecialchars($modalId, ENT_QUOTES);
                                                    ?>
                                                    <a href="#<?= $modalIdEsc ?>"
                                                       class="btn btn-link"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#<?= $modalIdEsc ?>"
                                                       onclick="event.stopPropagation();">See Code List</a>
                                                    <?php
                                                    if (!isset($renderedModals[$codeformat['code_file']])) {
                                                        $renderedModals[$codeformat['code_file']] = true;
                                                        // pass the sanitized id along to the modal
                                                        $codeformat['modal_id'] = $modalId;
                                                        $modalsToRender[] = $codeformat;
                                                    }
                                                }
                                            } else {
                                                echo htmlspecialchars($dataFormat, ENT_QUOTES);
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?= $variable_text . ($data['description'][$id] ?? '') ?>
                                        <?php if (!empty($data['description_extra'][$id])): ?>
                                            <br/><em><?= $data['description_extra'][$id] ?></em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
// Render all modals OUTSIDE the accordion/table structure
foreach ($modalsToRender as $codeformat) {
    include __DIR__ . "/codes_modal.php";
}
?>

<input type="hidden" value="<?= htmlspecialchars($record_id_array, ENT_QUOTES) ?>" name="parent_table_record_id_array" id="parent_table_record_id_array">