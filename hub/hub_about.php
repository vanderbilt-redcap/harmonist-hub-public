<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

$RecordSetAbout = \REDCap::getData($pidsArray['ABOUT'], 'array', null);
$about = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetAbout, $pidsArray['ABOUT'])[0];
?>

<div class="container">
    <h3><?=filter_tags($about['about_title'])?> - About Us Page</h3>
    <?=filter_tags($about['about_text'])?>
    <div>
        <div class="alert alert-success col-12 fade show border-success d-none" id="succMsgContainer">
            Your edits have been saved.
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php
        foreach ($about['about_firstname'] as $id => $member) {
            $degree = '';
            if ($about['about_degree'][$id] != '') {
                $degree = ', '.$about['about_degree'][$id];
            }

            echo '<div class="col-6 col-md-2">'.
                '<div class="card d-flex justify-content-center align-items-center">'.
                '<img src="'.$module->escape(getFile($module, $about['about_photo'][$id], 'src')).'" alt="'.htmlspecialchars($about['about_firstname'][$id].' '.$about['about_lastname'][$id], ENT_QUOTES).'" class="about_portrait card-img-top">'.
                '<div class="card-body text-center caption">'.
                '<h5 class="card-title" style="min-height: 60px;">'.htmlspecialchars($about['about_firstname'][$id].' '.$about['about_lastname'][$id].$degree, ENT_QUOTES).'</h5>'.
                '<p class="card-text">'.htmlspecialchars($about['about_project_title'][$id], ENT_QUOTES).'</p>'.
                '</div>'.
                '</div>'.
                '</div>';
        } ?>
    </div>
</div>
