<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
include_once(__DIR__ ."/../projects.php");

$settings = arrayKeyExistsReturnValue(\REDCap::getData($pidsArray['SETTINGS'], 'json-array', null),[0]);

$allUpdates = arrayKeyExistsReturnValue($this->getProjectSetting('hub-updates',$pidsArray['PROJECTS']),['data']);
if (!empty($allUpdates)) {
    $url = $this->getUrl("hub-updates/index.php") . "&pid=" . $pidsArray['PROJECTS'];
    $subject = "New Administrator Project Updates for " . $settings['hub_name'] . " Hub";
    $message = "<div xmlns=\"http://www.w3.org/1999/html\">Dear Hub Administrator,</div><br/>
            <div>The REDCap data dictionaries for the Harmonist Hub External Module have been updated and there are some updates that <strong>can only be executed by a REDCap administrator</strong>. In order to ensure the <strong>" . $settings['hub_name'] . " Hub </strong> continues working correctly, please review and apply these updates using the <strong>Hub Updates tool</strong>:" .
        "</br><a href='" . $url . "' target='_blank'>" .$url . "</a></div>
        <br/><div>A summary of the required REDCap admin updates is included below:</div><br/>
        <div>";

    $sqlVariables = [];
    foreach ($allUpdates as $constant => $project_data) {
        $sqlVariableNames = [];
        foreach ($project_data as $instrument => $instrumentData) {
            if ($instrument === "TOTAL") {
                continue; // Skip the "TOTAL" instrument
            }
        foreach ($instrumentData as $status => $typeData) {
            foreach ($typeData as $variable => $data) {
                if ($data['field_type'] === "sql") {
                    $sqlVariableNames[] = $data['field_name'];
                }
            }
        }

        }
        if (!empty($sqlVariableNames)) {
            $sqlVariables[$constant] = $sqlVariableNames;
        }
    }

    $notifications = HubUpdates::generateAdminAlerts($this, $pidsArray, $settings);
    $message .= "<div>{$notifications}</div>";

    foreach ($sqlVariables as $constant => $sqlVariable) {
        $pid = arrayKeyExistsReturnValue($pidsArray, [$constant]);
        $projectTitle = $this->framework->getProject($pid)->getTitle();
        $gotoredcap = htmlentities(
            APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pid,
            ENT_QUOTES
        );
        $message .= "<div><a href='{$gotoredcap}'>{$projectTitle}</a></div>";
        $message .= "<div><ul>";

        foreach ($sqlVariable as $variable => $variableName) {
            $message .=  "<li>{$variableName}</li>";
        }

        $message .=  "</ul></div>";
    }

    foreach ($adminEmails as $email) {
        sendEmail(
            $email,
            REDCapManagement::DEFAULT_NO_REPLY_EMAIL_ADDRESS,
            REDCapManagement::DEFAULT_NO_REPLY_EMAIL_ADDRESS,
            $subject,
            $message,
            "Not in database",
            "Hub Updates Admin Notification",
            $pidsArray['PROJECTS']
        );

    }

    //Update last sent date
    $this->setProjectSetting('admin-frequency-last-sent', date("Y-m-d"), $project_id);
}
?>

