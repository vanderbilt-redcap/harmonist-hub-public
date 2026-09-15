<div class="container">
    <h3 class="fw-bold">Profile</h3>
    <p class="hub-title">View and edit your profile information.</p>
</div>

<?php
if (array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] == 'U')) {
    ?>
    <div class="alert alert-success col-12" id="succMsgContainer">
        Your profile information has been successfully updated.
    </div>
    <?php
}
?>

<div class="container">
    <?php
    $passthru_link = $module->resetSurveyAndGetCodes($pidsArray['PEOPLE'], $currentUser['record_id'], "user_profile", "");
    $survey_link = $module->escape(APP_PATH_WEBROOT_FULL . "/surveys/?s=" . $passthru_link['hash']);
    ?>
    <iframe class="commentsform border-0" id="redcap-frame" name="redcap-frame" message="U" src="<?=$survey_link?>" style="height: 980px; width: 100%;"></iframe>
</div>

