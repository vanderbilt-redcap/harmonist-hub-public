<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$fileLibrary = \REDCap::getData($pidsArray['FILELIBRARY'], 'json-array');

$file_tags = $module->escape($module->getChoiceLabels('file_tags', $pidsArray['FILELIBRARY']));
$upload_type = $module->escape($module->getChoiceLabels('upload_type', $pidsArray['FILELIBRARY']));

?>
<script>
    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var category = $('#selectCategory option:selected').val();
            var column_category = data[2];

            if(category != 'Select All' && column_category == category ){
                return true;
            }else if(category == 'Select All'){
                return true;
            }
            return false;
        }
    );

    $(document).ready(function() {
        Sortable.init();

        var table = $('#table_archive').DataTable({
            "pageLength": 50,
            dom: "<'row'<'col-sm-3'l><'col-sm-4'f><'col-sm-5'p>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            "order": [[4, "desc"]]
        });

        $('#selectCategory').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );

        $('#table_archive_filter').appendTo( '#options_wrapper' );
        $('#table_archive_filter').attr( 'style','float: right;padding-right: 190px;padding-top: 5px;' );
    });
</script>

<div class="container">
    <?php
    if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'U')){?>
        <div class="alert alert-success col-12" id="succMsgContainer">If you've made any changes, they have been saved.</div><?php
    }else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'S')){?>
        <div class="alert alert-success col-12" id="succMsgContainer">New Library File added successfully.</div><?php
    }
    ?>
</div>
<div class="container">
    <div class="backTo">
        <?php
        if($_REQUEST['type'] == "home") {
            ?><a href="<?=$module->getUrl('index.php', true)?>">&lt; Back to Home</a><?php
        }else{
            ?><a href="<?=$module->getUrl('index.php', true).'&option=dat'?>">&lt; Back to Data</a><?php
        }

        ?>
    </div>
    <h3>Document Library</h3>
    <p class="hub-title"><?=$settings['hub_doc_librabry_text']?></p>
    <br>
    <div class="text-center">
        <a href="#" onclick="$('#redcap-new-file-frame').attr('src','<?=$module->escape(APP_PATH_WEBROOT_FULL."/surveys/?s=".$pidsArray['SURVEYFILELIBRARY'])?>');$('#sop_add_library_file').modal('show');" class="btn btn-success btn-md"><i class="fa fa-plus"></i> Library File</a>
    </div>
    <br>
    <br>
</div>
<div class="container pb-3">
    <span>Tags: </span>
    <?php
    foreach ($file_tags as $value=>$tag){
        echo '<button class="btn btn-outline-secondary btn-sm" href="#" onclick="selectTag('.$value.')" type="button" id="tag_'.$value.'"><span>'.$tag.'</span></button> ';
    }
    ?>
</div>
<div class="container">
    <div class="optionSelect conceptSheets_optionMenu float-start" id="options_wrapper">
        <div class="float-end">
            <div class="float-start ps-4 mt-2">
                Category:
            </div>
            <div class="float-start ps-2">
                <select class="form-select" name="selectCategory" id="selectCategory">
                    <option value="Select All">Select All</option>
                    <?php
                        foreach ($upload_type as $category){
                            echo "<option value='".$category."'>".$category."</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div>
        <table class="table sortable-theme-bootstrap" data-sortable id="table_archive">
            <?php
            if (!empty($fileLibrary)) { ?>
                <thead>
                <tr>
                    <th class="sorted_class">Title</th>
                    <th class="sorted_class">Description</th>
                    <th class="sorted_class">Category</th>
                    <th class="sorted_class">Person</th>
                    <th class="sorted_class">
                        <span class="d-block">Upload</span>
                        <span>Date</span>
                    </th>
                    <?php if ($isAdmin) { ?>
                        <th class="sorted_class"><i class="fa fa-cog"></i></th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($fileLibrary as $filel) {
                    if ($filel['hidden_y'][1] != "1") {
                        $tags = '';
                        foreach ($file_tags as $tagindex => $label) {
                            if ($filel['file_tags___' . $tagindex] == '1') {
                                $tags .= "<li class='docLibrary tag badge bg-primary'>" . $label . "</li>";
                            }
                        }

                        $people = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                 'project_id' => $pidsArray['PEOPLE'],
                                                                                 'return_format' => 'json-array',
                                                                                 'records' => [$filel['file_uploader']],
                                                                                 'fields' => ['firstname', 'lastname', 'email']
                                                                             ]), [0]);
                        $name = trim($people['firstname'] . ' ' . $people['lastname']);

                        $file_pdf = (!is_numeric($filel['file'])) ? $filel['file_title'] : getOtherFilesLink($module, $filel['file'], $filel['record_id'], $currentUser['record_id'], $secret_key, $secret_iv, $filel['file_title']);

                        echo '<tr><td style="width: 250px">' . $file_pdf . '</td>' .
                            '<td style="width: 450px"><div>' . htmlspecialchars($filel['file_description'], ENT_QUOTES) . '</div><div class="pt-2">' . filter_tags($tags) . '</div></td>' .
                            '<td style="width: 100px">' . htmlspecialchars($upload_type[$filel['upload_type']], ENT_QUOTES) . '</td>' .
                            '<td style="width: 150px"><a href="mailto:' . $module->escape($people['email']) . '">' . htmlspecialchars($name, ENT_QUOTES) . '</a></td>' .
                            '<td style="width: 150px;">' . htmlspecialchars($filel['upload_dt'], ENT_QUOTES) . '</td>';

                        if ($isAdmin) {
                            $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['FILELIBRARY'], $filel['record_id'], "file_information", "");
                            $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);

                            $edit = '<a href="#" class="btn btn-outline-secondary open-codesModal" onclick="editIframeModal(\'sop_other_files_modal\',\'redcap-edit-frame\',\'' . $survey_link . '\');"><em class="fa fa-pencil"></em></a>';
                            echo '<td style="width: 55px">' . $edit . '</td>';
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

<!-- MODAL EDIT COMMENT-->
<div class="modal fade" id="sop_other_files_modal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus"></i> Edit File Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded_file">
                <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" message="U" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ADD LIBRARY-->
<div class="modal fade" id="sop_add_library_file" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus"></i> Add Library File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded_file">
                <iframe class="commentsform" id="redcap-new-file-frame" name="redcap-new-file-frame" message="S" src="" style="border: none;height: 810px;width: 100%;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

