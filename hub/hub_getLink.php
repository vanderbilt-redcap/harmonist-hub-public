<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

require_once dirname(dirname(__FILE__)) . "/projects.php";

// Log the initial request
$module->log("HUB: " . $pidsArray['PROJECTS'] . " - GET LINK");

$settingsParams = [
    'project_id' => $pidsArray['SETTINGS'],
    'return_format' => 'json-array',
    'fields' => ['hub_name', 'accesslink_dur', 'accesslink_sender_email', 'accesslink_sender_name', 'hub_contact_email'],
];
$settings = arrayKeyExistsReturnValue(\REDCap::getData($settingsParams), [0]);

$result = "";
$currentOption = filter_input(INPUT_POST, 'option', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
$currentRecord = filter_input(INPUT_POST, 'record', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null;
// Define url route options
$options = REDCapManagement::getUrlRoutes();
\Safe\error_log("email: ".$email);
// Validate and process email if provided
if (!empty($email)) {
    $module->log("HUB: " . $pidsArray['PROJECTS'] . " - Link requested for " . $email);

    // Fetch user details
    $peopleParams = [
        'project_id' => $pidsArray['PEOPLE'],
        'return_format' => 'json-array',
        'filterLogic' => "lower([email]) = '" . strtolower($email) . "'",
        'fields' => ['record_id', 'harmonist_regperm', 'active_y', 'email', 'first_ever_login_d'],
    ];
    $people = arrayKeyExistsReturnValue(\REDCap::getData($peopleParams), [0]);

    // Check if the user exists and is active
    if (!empty($people) && strtolower($people['email']) === strtolower($email) && $people['harmonist_regperm'] != '0' && $people['active_y'] == '1') {
        $module->log("HUB: " . $pidsArray['PROJECTS'] . " - Email found in database. Proceeding to send link");

        // Generate token and prepare URL
        $token = getRandomIdentifier(12);
        $sendOption = (!empty($currentOption) && in_array($currentOption, $options)) ? "&option=" . $currentOption : "";
        $sendRecord = (is_numeric($currentRecord)) ? "&record=" . $currentRecord : "";
        $url = $module->getUrl("index.php", true) . "&token=" . $token . $sendOption . $sendRecord;

        // Default to 7 days if access duration is not set
        $accessDuration = $settings['accesslink_dur'] ?: 7;

        // Prepare email message
        $message = $module->getTwig()->render('hub/getLink_link_access_email.html.twig', [
            'hub_name' => $settings['hub_name'],
            'url' => $url,
            'access_duration' => $accessDuration,
        ]);

        // Add environment info to subject (if applicable)
        $environment = (ENVIRONMENT === 'TEST') ? " " . ENVIRONMENT : "";

        // Send the email
        sendEmail(
            strtolower($people['email']),
            $settings['accesslink_sender_email'],
            $settings['accesslink_sender_name'],
            $settings['hub_name'] . " Hub Access Link" . $environment,
            $message,
            $people['record_id'],
            "Review Hub Access Sent",
            $pidsArray['PEOPLE']
        );
        $module->log("HUB: " . $pidsArray['PROJECTS'] . " - Token sent");

        // Prepare data for saving token and expiration details
        $arrayLogin = [
            [
                'record_id' => $people['record_id'],
                'access_token' => $token,
                'token_expiration_d' => date('Y-m-d', strtotime("+$accessDuration days")),
                'last_requested_token_d' => date('Y-m-d H:i:s'),
                'first_ever_login_d' => $people['first_ever_login_d'] ?: date('Y-m-d H:i:s'),
            ]
        ];
        $json = json_encode($arrayLogin);
        $saveParams = [
            'project_id' => $pidsArray['PEOPLE'],
            'dataFormat' => 'json',
            'data' => $json,
            'overwriteBehavior' => "overwrite",
            'dateFormat' => "YMD",
            'type' => "flat",
        ];
        \REDCap::saveData($saveParams);
        \Records::addRecordToRecordListCache($pidsArray['PEOPLE'], $people['record_id'], 1);

        //Delete the session in case we have an old one running
        $module->getSecurityHandler()->logOut();
    } else {
        // Email not found or user not active
        $module->log("HUB: " . $pidsArray['PROJECTS'] . " - Email not found or inactive");

        $message = $module->getTwig()->render('hub/getLink_link_access_email_user_not_active.html.twig', [
            'hub_contact_email' => $settings['hub_contact_email'],
        ]);

        $environment = (ENVIRONMENT === 'TEST') ? " " . ENVIRONMENT : "";
        sendEmail(
            strtolower($email),
            $settings['accesslink_sender_email'],
            $settings['accesslink_sender_name'],
            "Access Denied for " . $settings['hub_name'] . " Hub" . $environment,
            $message,
            "Not in database",
            "Access denied",
            $pidsArray['PEOPLE']
        );
    }
}

echo json_encode($result);

?>

