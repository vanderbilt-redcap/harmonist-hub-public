<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$type = htmlentities($_REQUEST['type'],ENT_QUOTES);

$news_type = $module->getChoiceLabels('news_type', $pidsArray['NEWITEMS']);
$news_category = $module->getChoiceLabels('news_category', $pidsArray['NEWITEMS']);
$newItems = \REDCap::getData($pidsArray['NEWITEMS'], 'json-array');
ArrayFunctions::array_sort_by_column($newItems, 'news_d',SORT_DESC);
$news_icon_color = ['fa-newspaper-o'=>'#ffbf80',	'fa-bullhorn'=>'#ccc','fa-calendar-o'=>'#ff8080','fa-bell-o'=>'#dff028',
    'fa-list-ol'=>'#b3d9ff','fa-file-o'=>'#a3a3c2','fa-trophy'=>'#9999ff','fa-exclamation-triangle'=>'#a3c2c2'];

$harmonist_perm_news= ($currentUser['harmonist_perms___9'] == 1) ? true : false;

if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'N')){
    ?>
    <div class="container">
        <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;"
             id="succMsgContainer">Your News Item has been successfully saved.
        </div>
    </div>
    <?php
}
?>
<script>
    window.onbeforeunload = null;

    //To filter the data
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            var type = $('#default-select-value').text().trim();
            var column_type = data[1];
            var category = $('#selectCat option:selected').val();
            var column_cat = data[2];

            if(type != 'Select All' && column_type == type ){
                if(category != '' && column_cat == category ){
                    return true
                }else if(category == ''){
                    return true;
                }
            }else if(type == 'Select All'){
                if(category != '' && column_cat == category ){
                    return true
                }else if(category == ''){
                    return true;
                }
            }

            return false;
        }
    );
    $(document).ready(function() {
        var type = <?=json_encode($type)?>;
        if(type != ""){
            $('#selectCat').val(type);
        }

        var loadConceptsAJAX_table = $('#table_archive').DataTable({"pageLength": 50,"order": [0, "desc"]});
        var column_publication = loadConceptsAJAX_table.column(1);
        column_publication.visible(false);
        var column_publication = loadConceptsAJAX_table.column(2);
        column_publication.visible(false);

        //when any of the filters is called upon change datatable data
        $('#default-select-value,#selectCat').change( function() {
            var table = $('#table_archive').DataTable();
            table.draw();
        } );

        //To change the text on select
        $(".dropdown-menu-custom li").click(function(){
            var selText = $(this).html();
            $(this).parents('.dropdown').find('.dropdown-toggle').html(selText+' <span class="caret" style="float: right;margin-top:8px"></span>');
            //when any of the filters is called upon change datatable data
            var table = $('#table_archive').DataTable();
            table.draw();
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

        //More/Less links
        var showChar = 510;
        var ellipsestext = "...";
        var moretext = "more";
        var lesstext = "less";
        $('.more').each(function() {
            var content = $(this).html();

            if(content.length > showChar) {

                var snippetContent = content.substr(0, showChar-1);
                var allContent = content.substr(showChar-1, content.length - showChar);

                var html = snippetContent + '<span class="moreellipses">' + ellipsestext+ '&nbsp;</span><span class="morecontent"><span>' + allContent + '</span>&nbsp;&nbsp;<a href="" class="morelink">' + moretext + '</a></span>';

                $(this).html(html);
            }

        });

        $(".morelink").click(function(){
            if($(this).hasClass("less")) {
                $(this).removeClass("less");
                $(this).html(moretext);
            } else {
                $(this).addClass("less");
                $(this).html(lesstext);
            }
            $(this).parent().prev().toggle();
            $(this).prev().toggle();
            return false;
        });


    } );
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true).'&option=dat'?>" class="text-decoration-none">< Back to Data</a>
    </div>
    <h3>News Archive</h3>
    <p class="hub-title"><?=$settings['hub_news_archive_text']?></p>
    <br>
    <div>
        <div class="mx-auto " style="width: 200px;">
            <?php if($isAdmin || $harmonist_perm_news){?>
                <a href="#" onclick="editIframeModal('hub_add_news','redcap-add-news','<?=APP_PATH_WEBROOT_FULL."surveys/?s=".$module->escape($pidsArray['SURVEYNEWS'])."&news_person=".$module->escape($currentUser['record_id'])?>');" class="btn btn-success btn-md">
                    <i class="fa fa-plus"></i> Add News
                </a>

                <!-- MODAL ADD NEWS-->
                <div class="modal fade" id="hub_add_news" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add News</h5>
                                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" value="0" id="comment_loaded_new">
                                <iframe class="commentsform" id="redcap-add-news" name="redcap-add-news" message="N" src="" style="border: none; height: 810px; width: 100%;"></iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="mt-5 conceptSheets_optionMenu">
        <div class="d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center pe-4">
                <div class="pe-3 mt-2">
                    Type:
                </div>
                <div>
                    <?php
                    $status_type = $module->getChoiceLabels('data_response_status', $pidsArray['SOP']);
                    $selected = ' <a href="#" data-bs-toggle="dropdown" class="dropdown-toggle form-control output_select btn-group d-flex align-items-center justify-content-between w-100" id="default-select-value" style="width: 250px;">
                            <span class="status-text ms-2">Select All</span>
                          </a>';
                    foreach ($news_type as $index => $status) {
                        $menu .= '<li class="dropdown-item" onclick="updateDropdown(this)">
                            <span class="fa-label status fa fa-fw '.htmlspecialchars($index, ENT_QUOTES).'" style="padding: 3px;border-radius:3px;font-size: 13px;height: 20px;" aria-hidden="true"></span>
                            <span class="status-text ms-2">'.htmlspecialchars($status, ENT_QUOTES).'</span>
                          </li>';
                    }
                    ?>
                    <ul class="nav" id="data_status" name="data_status">
                        <li class="menu-item dropdown">
                            <?=$selected?>
                            <ul class="dropdown-menu output-dropdown-menu dropdown-menu-custom" style="width:250px;">
                                <li class="dropdown-item" onclick="updateDropdown(this)">
                                    <span class="status-text ms-2">Select All</span>
                                </li>
                                <?=$menu?>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="pe-3 mt-2">
                    Category:
                </div>
                <div>
                    <select class="form-select" name="selectCat" id="selectCat">
                        <option value="">Select All</option>
                        <?php
                        if (!empty($news_category)) {
                            foreach ($news_category as $index => $value) {
                                echo "<option value='".htmlspecialchars($index, ENT_QUOTES)."'>".htmlspecialchars($value, ENT_QUOTES)."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function updateDropdown(element) {
        const dropdownButton = document.getElementById('default-select-value');
        const selectedIcon = element.querySelector('.fa-label'); // Find the icon (if it exists)
        const selectedText = element.querySelector('.status-text').textContent; // Get the text

        // Update the dropdown button text
        dropdownButton.querySelector('.status-text').textContent = " " + selectedText;

        // Remove the icon if "Select All" is chosen
        if (!selectedIcon) {
            dropdownButton.innerHTML = `<span class="status-text ms-2">${selectedText}</span>`;
        } else {
            const iconHTML = selectedIcon.outerHTML;
            dropdownButton.innerHTML = `${iconHTML}<span class="status-text ms-2">${selectedText}</span>`;
        }
    }
</script>
<div class="container mt-4">
    <div>
        <div class="table-archive overflow-hidden">
            <table class="table table_requests sortable-theme-bootstrap" data-sortable id="table_archive">
                <?php if (!empty($newItems)) { ?>
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead class="thead-light">
                    <tr>
                        <th class="sorted_class" data-sorted="true" data-sorted-direction="descending">Date</th>
                        <th class="sorted_class">Type Text</th>
                        <th class="sorted_class">Category</th>
                        <th class="sorted_class">Posted by</th>
                        <th class="sorted_class">News</th>
                        <th class="sorted_class">Files</th>
                        <?php if ($isAdmin || $harmonist_perm_news) { ?>
                            <th class="sorted_class text-center"><em class="fa fa-cog"></em></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($newItems as $news) {
                        $personRegionNews = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', ['record_id' => $news['news_person']], ['person_region'])[0]['person_region'];
                        $regionCodeNews = \REDCap::getData($pidsArray['REGIONS'], 'json-array', ['record_id' => $personRegionNews], ['region_code'])[0]['region_code'];
                        echo '<tr>'.
                            '<td style="width: 8%;">'.htmlspecialchars($news['news_d'], ENT_QUOTES).'</td>'.
                            '<td>'.htmlspecialchars($news_type[$news['news_type']], ENT_QUOTES).'</td>'.
                            '<td>'.htmlspecialchars($news['news_category'], ENT_QUOTES).'</td>'.
                            '<td style="width: 13%;">'.getPeopleName($pidsArray['PEOPLE'], $news['news_person'], 'email').' ('.htmlspecialchars($regionCodeNews, ENT_QUOTES).')</td>'.
                            '<td style="width: 60%;">'.
                            '<div><span class="badge news-label-tiny" style="color:#000;margin-right: 5px;" title="'.htmlspecialchars($news_type[$news['news_type']], ENT_QUOTES).'"><i class="fa '.htmlspecialchars($news['news_type'], ENT_QUOTES).'"></i></span></div>'.
                            '<div class="mb-2"><strong>'.htmlspecialchars($news['news_title'], ENT_QUOTES).'</strong></div>'.
                            '<div class="more">'.filter_tags($news['news'], ENT_QUOTES).' '.'</div></td>'.
                            '<td style="width: 15%; word-break: break-word;">'.
                            '<div>'.getFileLink($module, $pidsArray['PROJECTS'], $news['news_file'], '', '', $secret_key, $secret_iv, $currentUser['record_id'], "").'</div>'.
                            '<div>'.getFileLink($module, $pidsArray['PROJECTS'], $news['news_file2'], '', '', $secret_key, $secret_iv, $currentUser['record_id'], "").' </div>'.
                            '</td>';
                        if ($isAdmin || $harmonist_perm_news) {
                            $edit = "";
                            if ($isAdmin || $news['news_person'] == $currentUser['record_id']) {
                                $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['NEWITEMS'], $news['record_id'], "news_item", "");
                                $survey_link = $module->escape(APP_PATH_WEBROOT_FULL."/surveys/?s=".$passthru_link['hash']);

                                $edit .= '<a class="btn btn-outline-secondary open-codesModal" onclick="editIframeModal(\'hub_edit_news\',\'redcap-edit-frame\',\''.$survey_link.'\');"><em class="fa fa-pencil"></em></a>';
                            }
                            echo '<td>'.$edit.'</td>';
                        }
                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                <?php } ?>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EDIT NEWS-->
<div class="modal fade" id="hub_edit_news" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comments and Votes</h5>
                <button type="button" class="btn-close closeCustomModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" value="0" id="comment_loaded">
                <iframe class="commentsform" id="redcap-edit-frame" name="redcap-edit-frame" message="N" src="" style="border: none; height: 810px; width: 100%;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>