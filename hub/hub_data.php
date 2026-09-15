<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
$personRegion = $hubData->getPersonRegion();

$RecordSetHome = \REDCap::getData($pidsArray['HOME'], 'array', null);
$homepage = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetHome,$pidsArray['HOME'])[0];
$homepage_links_sectionorder = $module->getChoiceLabels('links_sectionicon', $pidsArray['HOME']);
$expire_date = date('Y-m-d', strtotime(date('Y-m-d') ."-".$settings['recentdataactivity_dur']." days"));

$comments_sevenDaysYoung = \REDCap::getData([
                                                'project_id' => $pidsArray['SOPCOMMENTS'],
                                                'return_format' => 'json-array',
                                                'filterLogic' => "datediff ([responsecomplete_ts], '".$expire_date."', \"d\", true) <= 0"
                                            ]);
ArrayFunctions::array_sort_by_column($comments_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$dataUpload_sevenDaysYoung = \REDCap::getData([
                                                  'project_id' => $pidsArray['DATAUPLOAD'],
                                                  'return_format' => 'json-array',
                                                  'filterLogic' => "datediff ([responsecomplete_ts], '".$expire_date."', \"d\", true) <= 0"
                                              ]);
ArrayFunctions::array_sort_by_column($dataUpload_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$dataDownload_sevenDaysYoung = \REDCap::getData([
                                                    'project_id' => $pidsArray['DATADOWNLOAD'],
                                                    'return_format' => 'json-array',
                                                    'filterLogic' => "datediff ([responsecomplete_ts], '".$expire_date."', \"d\", true) <= 0"
                                                ]);
ArrayFunctions::array_sort_by_column($dataDownload_sevenDaysYoung, 'responsecomplete_ts',SORT_DESC);

$all_data_recent_activity = array_merge($comments_sevenDaysYoung, $dataUpload_sevenDaysYoung);
$all_data_recent_activity = array_merge($all_data_recent_activity, $dataDownload_sevenDaysYoung);

ArrayFunctions::array_sort_by_column($all_data_recent_activity, 'responsecomplete_ts',SORT_DESC);
$number_of_recentactivity = $settings['number_recentdataactivity'];

$number_uploads = count(\REDCap::getData([
                                    'project_id' => $pidsArray['DATAUPLOAD'],
                                    'return_format' => 'json-array',
                                    'fields' => ['record_id']
                                ]));
$number_downloads = count(\REDCap::getData([
                                       'project_id' => $pidsArray['DATADOWNLOAD'],
                                       'return_format' => 'json-array',
                                       'fields' => ['record_id']
                                   ]));

$TBLCenter = \REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null);

$region_tbl_percent = getTBLCenterUpdatePercentRegions($TBLCenter, $personRegion['region_code'], $settings['pastlastreview_dur']);

$news_type = $module->getChoiceLabels('news_type', $pidsArray['NEWITEMS']);
$newItems = \REDCap::getData([
                                                    'project_id' => $pidsArray['NEWITEMS'],
                                                    'return_format' => 'json-array',
                                                    'filterLogic' => "[news_category] = '1'"
                                                ]);
ArrayFunctions::array_sort_by_column($newItems, 'news_d',SORT_DESC);
$exploreDataToken = json_encode("&code=".getCrypt($currentUser['record_id'],'e',$secret_key,$secret_iv) . '&redcap_csrf_token=' . $module->getCSRFToken());
?>
<div class="optionSelectData">
    <h3>Data Hub</h3>
    <p class="hub-title"><?=filter_tags($settings['hub_data_hub_text'])?></p>
</div>

<div class="row">
    <div class="col-md-3 d-flex align-items-stretch">
        <div class="card text-center d-flex flex-column">
            <div class="card-body d-flex flex-column bg-light">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <i class="fa fa-2x fa-fw fa-map me-2 text-explore fs-1" aria-hidden="true"></i>
                    <span class="text-start text-data"><strong>Explore</strong> the different types of <?=$settings['hub_name']?> data</span>
                </div>
                <a onclick='javascript:exploreDataToken(<?=$exploreDataToken;?>,<?=json_encode($module->getUrl("sop/sop_explore_data_AJAX.php", true));?>,<?=json_encode($module->getUrl('index.php',true));?>)' class="btn btn-data btn-explore mt-auto">Explore Data</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-stretch">
        <div class="card text-center d-flex flex-column bg-light">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <i class="fa fa-2x fa-fw fa-bullhorn me-2 text-request fs-1" aria-hidden="true"></i>
                    <span class="text-start text-data"><strong>Request</strong> <?=$settings['hub_name']?> data for your approved concept</span>
                </div>
                <a href="<?=$module->getUrl("index.php", true)."&option=smn";?>" class="btn btn-data btn-request mt-auto">Create Data Request</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-stretch">
        <div class="card text-center d-flex flex-column bg-light">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <i class="fa fa-2x fa-fw fa-cloud-upload me-2 text-submit fs-1" aria-hidden="true"></i>
                    <span class="text-start text-data"><strong>Check and submit</strong> data for an active data call</span>
                </div>
                <a href="<?=$module->getUrl("index.php", true)."&option=upd";?>" class="btn btn-data btn-submit mt-auto">View Data Calls <span class="badge bg-data-calls"><?=fetchNumberOfOpenDataCalls($pidsArray['SOP'], $currentUser['person_region']);?></span></a>
            </div>
        </div>
    </div>
    <?php
    if($settings['deactivate_datadown___1'] != "1") {
        if ($currentUser['allowgetdata_y___1'] != "1") {
            $modal = 'modal-data-download-no-permissions';
        } else if ($currentUser['redcap_name'] == '') {
            $modal = 'modal-data-download-denied';
        } else {
            $modal = 'modal-data-download-confirmation';
        }
        ?>
        <div class="col-md-3 d-flex align-items-stretch">
            <div class="card text-center d-flex flex-column bg-light">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <i class="fa fa-2x fa-fw fa-arrow-down me-2 text-retrieve fs-1" aria-hidden="true"></i>
                        <span class="text-start text-data"><strong>Retrieve</strong> data uploaded for your project</span>
                    </div>
                    <a href="#" onclick="$('#<?=$modal?>').modal('show');" class="btn btn-data btn-retrieve mt-auto">Download Data</a>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php
if($settings['deactivate_datadown___1'] != "1"){
    $downloadUrl = preg_replace('/pid=(\d+)/', "pid=".$pidsArray['DATADOWNLOADUSERS'],$module->getUrl('index.php').'&option=dnd');
    $downloadUrl .= "&redcap_csrf_token=" . $module->getCSRFToken();
    // Persist token to cookie so it survives the NOAUTH redirect
    $token = $_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$module->getSecurityHandler()->getTokenSessionName()] ?? "";
    if (!empty($token)) {
        $module->getSecurityHandler()->setSessionDataFromToken($token);
    }
    ?>
    <div class="d-none d-sm-block mb-4"></div>
    <div class="modal fade" id="modal-data-download-confirmation" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <span>Are you sure you want to download data?</span>
                    <br>
                    <span class="text-danger">You will need to log in to Vanderbilt REDCap.</span>
                    <input type="hidden" id="assoc_concept" name="assoc_concept">
                    <input type="hidden" id="user" name="user">
                </div>
                <div class="modal-footer">
                    <a href="<?=$downloadUrl;?>" class="btn btn-success" id="btnModalRescheduleForm">Continue</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-data-download-denied" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <span>Your account is not yet associated with a REDCap Downloader account. If you are expecting to download datasets for an approved concept, please contact
                    <a href="mailto:<?=$settings['hub_contact_email']?>"><?=$settings['hub_contact_email']?></a>
                </span>
                </div>
                <input type="hidden" id="assoc_concept" name="assoc_concept">
                <input type="hidden" id="user" name="user">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-data-download-no-permissions" tabindex="-1" aria-labelledby="Codes" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <span>You do not have permission to access <?=$settings['hub_name']?> data downloads.<br>For inquiries, contact
                    <a href="mailto:<?=$settings['hub_contact_email']?>"><?=$settings['hub_contact_email']?></a>
                </span>
                </div>
                <input type="hidden" id="assoc_concept" name="assoc_concept">
                <input type="hidden" id="user" name="user">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<script>
    $(document).ready(function() {
        var showChar = 110;
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
    });
</script>
<div class="row">
    <div class="col-sm-9">
        <div class="card">
            <div class="card-header" style="border: none;">
                <h6 class="card-title">
                    Data News
                    <a href="<?=$module->getUrl('index.php', true).'&option=dna&type=1'?>" style="float: right; padding-right: 10px;">View more</a>
                </h6>
            </div>
            <div id="collapse3" class="table-responsive collapse show  table-no-borders" aria-expanded="true">
                <table class="table table_requests sortable-theme-bootstrap" data-sortable id="deadlinesAndEvents">
                    <?php
                    if (!empty($newItems)) {
                        $i = 1;
                        $newItems = $module->escape($newItems);
                        foreach ($newItems as $event) {
                            if ($i <= 5) {
                                echo "<tr>";
                                echo "<td class='media' style='width: 755px;'>" .
                                    "<span class='label news-label' title='" . $news_type[$event['news_type']] . "'><i class='fa " . $event['news_type'] . "'></i></span>" .
                                    "<div style='float:left; padding-left: 10px; width:95%;'>" .
                                    "<span>" . getPeopleName($pidsArray['PEOPLE'], $event['news_person'], 'email') . " on " . $event['news_d'] . "</span>" .
                                    "<div><strong>" . $event['news_title'] . "</strong></div>" .
                                    "</div>";
                                echo "<div class='comment more' style='display: inline-block;'>" . filter_tags($event['news']) . " ";
                                if ($event['news_file'] != "" && $event['news_file2'] == "") {
                                    echo "<div style='padding-top: 10px; padding-bottom: 10px'>" . getFileLink($module, $pidsArray['PROJECTS'], $event['news_file'], '', '', $secret_key, $secret_iv, $currentUser['record_id'], "") . "</div> ";
                                } else if ($event['news_file'] != "" && $event['news_file2'] != "") {
                                    echo "<div style='padding-top: 10px;'>" . getFileLink($module, $pidsArray['PROJECTS'], $event['news_file'], '', '', $secret_key, $secret_iv, $currentUser['record_id'], "") . "</div> ";
                                    echo "<div style='padding-bottom: 10px'>" . getFileLink($module, $pidsArray['PROJECTS'], $event['news_file2'], '', '', $secret_key, $secret_iv, $currentUser['record_id'], "") . "</div> ";
                                }
                                echo "</div>";

                                echo "</td>" .
                                    "</tr>";
                            }
                            $i++;
                        }
                    } else { ?>
                        <tbody>
                        <tr>
                            <td style="padding-left: 15px;"><span><em>No data news to display</em></span></td>
                        </tr>
                        </tbody>
                    <?php } ?>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title">
                    Recent Data Activity
                    <a href="<?=$module->getUrl('index.php', true).'&option=sra'?>" style="float: right; padding-right: 10px;">View more</a>
                </h6>
            </div>
            <ul class="list-group list-group-flush">
                <?php
                if (!empty($all_data_recent_activity)) {
                    $i = 0;
                    foreach ($all_data_recent_activity as $recent_activity) {
                        if ($i < $number_of_recentactivity) {
                            $time = getDateForHumans($recent_activity['responsecomplete_ts']);
                            if (arrayKeyExistsReturnValue($recent_activity, ['comments']) != '') {
                                echo '<li class="list-group-item">';

                                $people = arrayKeyExistsReturnValue(\REDCap::getData([
                                                               'project_id' => $pidsArray['PEOPLE'],
                                                               'return_format' => 'json-array',
                                                               'records' => [$recent_activity['response_person']],
                                                               'fields' => ['firstname', 'lastname']
                                                           ]),[0]);
                                $name = htmlspecialchars(trim($people['firstname'] . ' ' . $people['lastname']), ENT_QUOTES);

                                $RecordSetSOP = \REDCap::getData([
                                                                     'project_id' => $pidsArray['SOP'],
                                                                     'return_format' => 'array',
                                                                     'records' => [$recent_activity['sop_id']]
                                                                 ]);
                                $sop = $module->escape(ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $pidsArray['SOP'])[0]);
                                $sop_concept_id = arrayKeyExistsReturnValue($sop,['sop_concept_id']);
                                $sop_name = arrayKeyExistsReturnValue($sop,['sop_name']);
                                $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $sop_concept_id, "");

                                $title = substr($sop_name, 0, 50) . '...';

                                if(!empty($recent_activity)) {
                                    if (array_key_exists(
                                            'author_revision_y',
                                            $recent_activity
                                        ) && $recent_activity['author_revision_y'] == '1') {
                                        echo '<i class="fa fa-fw fa-file-text-o text-success" aria-hidden="true"></i>' .
                                            '<span class="time"> ' . $time . '</span> ' .
                                            '<strong>' . $name . '</strong> submitted a <strong>revision</strong> for ' . $assoc_concept . ', <a href="' . $module->getUrl(
                                                'index.php',
                                                true
                                            ) . '&option=sop&record=' . $recent_activity['sop_id'] . '" target="_blank">' . $title . '</a>';
                                    } else {
                                        $text = '<span class="time"> ' . $time . '</span> <strong>' . $name . '</strong> submited a ';
                                        if (($recent_activity['comments'] ?? '') != '') {
                                            $icon = '<i class="fa fa-fw fa-comment-o text-info" aria-hidden="true"></i>';
                                        }
                                        if (($recent_activity['pi_vote'] ?? '') != '') {
                                            $icon = '<i class="fa fa-fw fa-check text-info" aria-hidden="true"></i>';
                                        }

                                        if (($recent_activity['comments'] ?? '') != '' && ($recent_activity['revised_file'] ?? '') != '') {
                                            $text .= '<strong>comment and file</strong>';
                                        } else {
                                            if (($recent_activity['comments'] ?? '')!= '') {
                                                $text .= '<strong>comment</strong>';
                                            } else {
                                                if (($recent_activity['revised_file'] ?? '') != '') {
                                                    $text .= '<strong>file</strong>';
                                                }
                                            }
                                        }

                                        echo $icon . $text . ' for ' . $assoc_concept . ', <a href="' . $module->getUrl(
                                                'index.php',
                                                true
                                            ) . '&option=sop&record=' . $recent_activity['sop_id'] . '" target="_blank">' . $title . '</a>';
                                    }
                                }
                                echo '</li>';
                                $i++;
                            } else if (arrayKeyExistsReturnValue($recent_activity, ['download_id']) != "") {
                                echo '<li class="list-group-item">';

                                $people = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                         'project_id' => $pidsArray['PEOPLE'],
                                                                                         'return_format' => 'json-array',
                                                                                         'records' => [$recent_activity['downloader_id']],
                                                                                         'fields' => ['firstname', 'lastname']
                                                                                     ]),[0]);
                                $name = htmlspecialchars(trim($people['firstname'] . ' ' . $people['lastname']));

                                $data_upload_region = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                         'project_id' => $pidsArray['DATAUPLOAD'],
                                                                                         'return_format' => 'json-array',
                                                                                         'records' => [$recent_activity['download_id']],
                                                                                         'fields' => ['data_upload_region']
                                                                                     ]),[0,'data_upload_region']);
                                $region_code = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                         'project_id' => $pidsArray['REGIONS'],
                                                                                         'return_format' => 'json-array',
                                                                                         'records' => [$data_upload_region],
                                                                                         'fields' => ['region_code']
                                                                                     ]),[0,'region_code']);

                                $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $recent_activity['downloader_assoc_concept'], "");

                                $icon = '<i class="fa fa-fw fa-arrow-down text-info" aria-hidden="true"></i>';

                                echo filter_tags($icon . ' <span class="time"> ' . $time . '</span><strong>' . $name . '</strong> downloaded ' . $region_code . ' data for ' . $assoc_concept . '.');
                                echo '</li>';
                                $i++;
                            } else if ($recent_activity['data_assoc_request'] != "") {
                                echo '<li class="list-group-item">';

                                $people = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                         'project_id' => $pidsArray['PEOPLE'],
                                                                                         'return_format' => 'json-array',
                                                                                         'records' => [$recent_activity['data_upload_person']],
                                                                                         'fields' => ['firstname', 'lastname']
                                                                                     ]),[0]);
                                $name = htmlspecialchars(trim($people['firstname'] . ' ' . $people['lastname']), ENT_QUOTES);
                                $region_code = arrayKeyExistsReturnValue(\REDCap::getData([
                                                                                              'project_id' => $pidsArray['REGIONS'],
                                                                                              'return_format' => 'json-array',
                                                                                              'records' => [$recent_activity['data_upload_region']],
                                                                                              'fields' => ['region_code']
                                                                                          ]),[0,'region_code']);
                                $assoc_concept = getReqAssocConceptLink($module, $pidsArray, $recent_activity['data_assoc_concept'], "");

                                $icon = '<i class="fa fa-fw fa-arrow-up text-info" aria-hidden="true"></i>';

                                echo filter_tags($icon . ' <span class="time"> ' . $time . '</span><strong>' . $name . '</strong> uploaded ' . $region_code . ' data for ' . $assoc_concept . '.');
                                echo '</li>';
                                $i++;
                            }
                        } else {
                            break;
                        }
                    }
                    if ($i === 0) {
                        echo '<div class="list-group-item"><em>No data activity in the last '.$settings['recentdataactivity_dur'].' days.</em></div>';
                    }
                } else { ?>
                    <li class="list-group-item"><em>No recent data activity in last <?=$settings['recentdataactivity_dur']?> days.</em></li>
                <?php } ?>
            </ul>
        </div>
        <div class="d-none d-sm-block mb-5"></div>
    </div>
    <div class="col-sm-3">
        <div class="list-group">
            <a href="<?=$module->getUrl('index.php', true).'&option=lgd'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <span class="fw-bold">Data Log</span>
                        <span class="badge bg-primary ms-2"><i class="fa fa-arrow-down"></i> <?=$number_downloads;?></span>
                        <span class="badge bg-primary ms-2"><i class="fa fa-arrow-up"></i> <?=$number_uploads;?></span>
                    </h6>
                </div>
                <div class="pt-2">
                    <p class="mb-1">Track uploads and downloads of <?=$settings['hub_name']?> patient-level datasets.</p>
                </div>
            </a>

            <?php if ($routes->canAccessToolkit()) { ?>
                <a href="https://redcap.vumc.org/external_modules/?prefix=harmonist-hub-public&page=index&pid=203280&NOAUTH&option=tlk" target="_blank" class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <span class="fw-bold">Harmonist Data Toolkit</span>
                            <i class="fa fa-external-link"></i>
                        </h6>
                    </div>
                    <div class="pt-2">
                        <p class="mb-1">Go to the Toolkit webpage (without a data submission request).</p>
                    </div>
                </a>
            <?php } ?>

            <a href="<?=APP_PATH_WEBROOT_FULL."external_modules/?prefix=data-model-browser&page=browser&NOAUTH=&pid=".$pidsArray['DES']?>" target="_blank" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <span class="fw-bold">iedeades.org</span>
                        <i class="fa fa-external-link"></i>
                    </h6>
                </div>
                <div class="pt-2">
                    <p class="mb-1">Browse the <?=$settings['hub_name']?> Data Exchange Standard.</p>
                </div>
            </a>

            <?php if ($settings['deactivate_tblcenter___1'] != "1") { ?>
                <a href="<?=$module->getUrl('index.php', true).'&option=tbl'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <span class="fw-bold pe-2">tblCENTER</span>
                            <?php if ($personRegion['showregion_y'] == '1') {
                                echo getTBLCenterUpdatePercentLabel($region_tbl_percent);
                            } ?>
                        </h6>
                    </div>
                    <div class="pt-2">
                        <p class="mb-1">View and maintain the list of active <?=$settings['hub_name']?> sites.</p>
                    </div>
                </a>
            <?php } ?>

            <?php if ($settings['deactivate_datametrics___1'] != "1" || $isAdmin) { ?>
                <a href="<?=$module->getUrl('index.php', true).'&option=mth'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex">
                        <div class="me-3">
                            <h6 class="fw-bold mb-3">Hub Stats</h6>
                            <?=$settings['hub_name']?> file activity by category.
                        </div>
                        <div>
                            <canvas id="IedeaChart" class="canvas_statistics" width="100" height="100"></canvas>
                        </div>
                    </div>
                </a>
            <?php } ?>

            <a href="<?=$module->getUrl('index.php', true).'&option=ofs'?>" class="list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">
                        <span class="fw-bold">Document Library</span>
                    </h6>
                </div>
                <div class="pt-2">
                    <p class="mb-1">Archive of extra files for <?=$settings['hub_name']?> projects, meetings, and governance.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php

#FILE ACTIVITY
$number_uploads = count(\REDCap::getData([
                                             'project_id' => $pidsArray['DATAUPLOAD'],
                                             'return_format' => 'json-array',
                                             'fields' => ['record_id']
                                         ]));
$number_downloads = count(\REDCap::getData([
                                             'project_id' => $pidsArray['DATADOWNLOAD'],
                                             'return_format' => 'json-array',
                                             'fields' => ['record_id']
                                         ]));
$number_deletes = count(\REDCap::getData([
                                             'project_id' => $pidsArray['DATADOWNLOAD'],
                                             'return_format' => 'json-array',
                                             'fields' => ['record_id'],
                                             'filterLogic' => "[deleted_y] = '1' AND [deletion_type] = '2'"
                                         ]));
$number_deletes_auto = count(\REDCap::getData([
                                             'project_id' => $pidsArray['DATADOWNLOAD'],
                                             'return_format' => 'json-array',
                                             'fields' => ['record_id'],
                                             'filterLogic' => "[deleted_y] = '1' AND [deletion_type] = '1'"
                                         ]));

//GRAPH
$fileActivity_values =[0 => $number_uploads,1 => $number_downloads,2 => $number_deletes];
$fileActivity_labels =[0 => "Uploads",1 => "Downloads",2 => "Manual Delete"];
$fileActivity_colors =[0 => "#5cb85c",1 => "#337ab7",2 => "#eb6e60"];
?>
<script>
    $(document).ready(function() {
        Sortable.init();
        $('html,body').scrollTop(0);
        $("html,body").animate({ scrollTop: 0 }, "slow");

        var requests_values = <?=json_encode($fileActivity_values)?>;
        var requests_labels = <?=json_encode($fileActivity_labels)?>;
        var requests_colors = <?=json_encode($fileActivity_colors)?>;

        var  ctx_iedea = $("#IedeaChart");
        var config_iedea = {
            type: 'doughnut',
            data: {
                labels: requests_labels,
                datasets: [{
                    backgroundColor: requests_colors,
                    data: requests_values
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    datalabels: {
                        color: '#fff', // Set label color to white
                        formatter: function(value, context) {
                            return value; // Format the label to display the value
                        },
                        font: {
                            size: 10 // Set font size
                        },
                        anchor: 'center', // Positioning: Center anchor
                        align: 'center', // Positioning: Center alignment
                    },
                    legend: {
                        display: false // This hides the legend
                    }

                }
            },
            plugins: [ChartDataLabels] // Register the datalabels plugin
        }
        var iedea_chart = new Chart(ctx_iedea, config_iedea);
    });
</script>

