<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
?>
<div class="container">
    <div class="optionSelect">
        <h3 class="mb-3">Projects</h3>
        <p class="hub-title text-secondary"><?=$settings['hub_projects_text']?></p>
    </div>
    <div>
        <table class="table table-hover sortable-theme-bootstrap concepts-table projects-table" data-sortable id="sortable_table">
            <?php
            if(!array_key_exists('PROJECTSSTUDIES', $pidsArray) || (array_key_exists('PROJECTSSTUDIES', $pidsArray) && $pidsArray["PROJECTSSTUDIES"] == "")) {
                ?><tr><td colspan="5" class="text-center"><em>No studies or projects available.</em></td></tr><?php
            }else{
                $RecordSetConceptsActive = $module->escape(\REDCap::getData($pidsArray["PROJECTSSTUDIES"], 'array'));
                $record_ids =  ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConceptsActive,$pidsArray["PROJECTSSTUDIES"]);
                ArrayFunctions::array_sort_by_column($record_ids, 'study_sd',SORT_DESC);

                $study_type = $module->getChoiceLabels('study_type', $pidsArray['PROJECTSSTUDIES']);
                $study_status = $module->getChoiceLabels('study_status', $pidsArray['PROJECTSSTUDIES']);

                if($record_ids == "") {
                    ?><tr><td colspan="5" class="text-center"><em>No studies or projects available.</em></td></tr><?php
                }else{
                    ?>
                    <thead class="table-light">
                    <tr>
                        <th>Start Year</th>
                        <th>Full Name (Short Name)</th>
                        <th>Project Type</th>
                        <th>Main Concept</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($record_ids as $record) {
                        $record = $module->escape($record);
                        $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $record['study_concept']);
                        $status = '<span class="badge bg-success text-light">'.$study_status[$record['study_status']].'</span>';
                        $year = empty($record['study_sd']) ? "" : date("Y",strtotime($record['study_sd']));
                        ?>
                        <tr>
                            <td width="5%"><?=$year?></td>
                            <td width="50%"><a href='<?=$module->getUrl('index.php').htmlentities("&NOAUTH&option=sts&record=".$record['record_id'],ENT_QUOTES)?>'><?=$record['study_fullname']." (".$record['study_name'].")"?></a></td>
                            <td width="20%"><span style="vertical-align: super" class="badge bg-primary text-light"><?=$study_type[$record['study_type']]?></span></td>
                            <td width="10%"><?=$assoc_concept?></td>
                            <td width="15%"><?=$status?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                <?php }
            } ?>
        </table>
    </div>
</div>
<script language="JavaScript">
    $(document).ready( function () {
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");
        Sortable.init();
        //double pagination (top & bottom)
        var table = $('#sortable_table').DataTable({"pageLength": 50,dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>", "order": [0, "desc"]});
    } );
</script>
