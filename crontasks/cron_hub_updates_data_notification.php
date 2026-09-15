<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
include_once(__DIR__ ."/../projects.php");

$settings = arrayKeyExistsReturnValue(\REDCap::getData($pidsArray['SETTINGS'], 'json-array', null),[0]);
if(!empty($settings['hub_email_critical_vars'])) {
    $allUpdates = arrayKeyExistsReturnValue($this->getProjectSetting('hub-updates',$pidsArray['PROJECTS']),['data']);
    if (!empty($allUpdates)) {
        $url = $this->getUrl("hub-updates/index.php") . "&pid=" . $pidsArray['PROJECTS'];
        $subject = "New Project Updates for " . $settings['hub_name'] . " Hub";
        $message = "<div>Dear Hub Administrator,</div><br/>
                <div>The REDCap data dictionaries for the Harmonist Hub External Module have been updated. In order to ensure the <strong>" . $settings['hub_name'] . " Hub </strong> continues working correctly, please review and apply these updates using the <strong>Hub Updates tool</strong>:" .
            "</br><a href='" . $url . "' target='_blank'>" .$url . "</a></div>
            <br/><div>Please consider installing critical variables as soon as possible, as out-of-date REDCap projects and variables can cause problems for new and existing Hub functionality. </div><br/>
            <br/><div>A summary of the updates is included below:</div><br/>
            <div>";
        $labelCSSStyle = "width: 5px;
                    height: 10px;
                    display: inline;
                    font-size: 75%;
                    font-weight: 700;
                    line-height: 1;
                    color: #fff;
                    text-align: center;
                    white-space: nowrap;
                    vertical-align: baseline;
                    border-radius: 0.25em;";
        $message .= '<div>
                        <div style="float: left">Criticality Level:&nbsp;</div><div style="float: left;padding-right: 5px;"><span style="' . $labelCSSStyle . 'background-color:#cb410b;padding: 0.2em 0.8em 0.3em;">!</span><span>&nbsp;Critical</span></div><div style="float: left;padding-right: 5px;"><span style="' . $labelCSSStyle . 'background-color:#ABA9A7;padding: 0.2em 0.6em 0.3em;">=</span><span>&nbsp;Medium</span></div><div style="float: left;padding-right: 5px;"><span style="' . $labelCSSStyle . 'background-color:#E0DEDC;padding: 0.2em 0.6em 0.3em;">▼</span><span>&nbsp;Low</span></div>
                    </div><br><br>';
        foreach ($allUpdates as $constant => $project_data) {
            $criticalityData = HubUpdates::organizeCriticalityByConstantData($pidsArray[$constant], $project_data);
            if (!empty($criticalityData)) {
                $message .= "<div>";
                $Proj = $this->getProject($pidsArray[$constant]);
                $gotoredcap = htmlentities(
                    APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant],
                    ENT_QUOTES
                );
                $message .= "<div><a href='" . $gotoredcap . "' target='_blank'>" . $Proj->getTitle() . "</a></div>";
                $varsOrderedByCriticality = [];
                foreach ($criticalityData as $variable => $criticality) {
                    if ($variable != HubUpdates::VARIABLE_CRITICALITY_TOTAL) {
                        if (!array_key_exists($criticality, $varsOrderedByCriticality)) {
                            $varsOrderedByCriticality[$criticality] = [];
                        }
                        array_push($varsOrderedByCriticality[$criticality], $variable);
                    }
                }
                foreach ($varsOrderedByCriticality as $criticality => $variableData) {
                    $icon = "";
                    switch ($criticality) {
                        case HubUpdates::VARIABLE_CRITICALITY_CRITICAL:
                            $icon = "!";
                            $color = "background-color:#cb410b;";
                            $padding = "padding: 0.2em 0.8em 0.3em;";
                            break;
                        case HubUpdates::VARIABLE_CRITICALITY_MEDIUM:
                            $icon = "=";
                            $color = "background-color:#ABA9A7;";
                            $padding = "padding: 0.2em 0.6em 0.3em;";
                            break;
                        default:
                            $icon = "▼";
                            $color = "background-color:#E0DEDC;";
                            $padding = "padding: 0.2em 0.6em 0.3em;";
                            break;
                    }
                    $message .= '<div style="padding: 10px;"><span style="' . $labelCSSStyle . $color . $padding . '">' . $icon . '</span><span>&nbsp;' . ucfirst(
                            $criticality
                        ) . ' (' . count($varsOrderedByCriticality[$criticality]) . ')</span></div>';

                    $message .= "<div><ul style='margin-top: 0;'>";
                    foreach ($variableData as $variable) {
                        $message .= "<li>" . $variable . "</li>";
                    }
                    $message .= "</ul></div>";
                    $message .= "</div>";
                }
            }
        }

        sendEmail(
            $settings['hub_email_critical_vars'],
            REDCapManagement::DEFAULT_NO_REPLY_EMAIL_ADDRESS,
            REDCapManagement::DEFAULT_NO_REPLY_EMAIL_ADDRESS,
            $subject,
            $message,
            "Not in database",
            "Project Updates",
            $pidsArray['PROJECTS']
        );
    }
}
?>

