<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use Project;
use REDCap;

class Messages
{
    public static function getHubUpdatesMessage($letter)
    {
        // If $letter has more than one character, return it as the message itself.
        if (strlen($letter) > 1) {
            return $letter;
        }

        // Define the mapping of single-character keys to messages.
        $message = [
            'S' => "The Data Dictionary has been successfully updated.",
            'R' => "The variables have been successfully <strong>added</strong> to the resolved list.",
            'U' => "The variables have been successfully <strong>removed</strong> from the resolved list.",
            'L' => "The Data Dictionary has been successfully updated.",
            'T' => "<strong>" . ProjectData::HUB_SURVEY_THEME_NAME . "</strong> has been successfully updated. Check the logs to see which surveys have been updated.",
            'V' => "The surveys have been successfully created. Check the logs to see which instruments have been updated.",
            'E' => "The module and settings have been enabled on the projects.",
            'D' => "The Data Model Browser has been successfully added.",
            'M' => "The missing projects have been successfully added."
        ];

        // Return the corresponding message if $letter exists in the mapping.
        return $message[$letter] ?? null;
    }
}

?>

