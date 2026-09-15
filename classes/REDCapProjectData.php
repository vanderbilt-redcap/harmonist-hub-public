<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

use REDCap;

include_once(__DIR__ . "/REDCapManagement.php");

class REDCapProjectData
{
    private static $constants;
    private static $titles;
    private static $repeatable;
    private static $surveys;
    private static $show;
    private static $customRecordLabels;
    private static $hooks;
    private static $surveysHash;
    private static $moduleEmailAlerts;
    private static $moduleGetPMID;
    private static $extraConstants;
    private static $projectsDESArray;
    private static $DESMapOtherConstants;

    public static function getConstants()
    {
        if (self::$constants === null) {
            self::$constants = REDCapManagement::getProjectsConstantsArray();
        }
        return self::$constants;
    }

    public static function getTitles()
    {
        if (self::$titles === null) {
            self::$titles = REDCapManagement::getProjectsTitlesArray();
        }
        return self::$titles;
    }

    public static function getRepeatable()
    {
        if (self::$repeatable === null) {
            self::$repeatable = REDCapManagement::getProjectsRepeatableArray();
        }
        return self::$repeatable;
    }

    public static function getSurveys()
    {
        if (self::$surveys === null) {
            self::$surveys = REDCapManagement::getProjectsSurveysArray();
        }
        return self::$surveys;
    }

    public static function getShow()
    {
        if (self::$show === null) {
            self::$show = REDCapManagement::getProjectsShowArray();
        }
        return self::$show;
    }

    public static function getCustomRecordLabels()
    {
        if (self::$customRecordLabels === null) {
            self::$customRecordLabels = REDCapManagement::getCustomRecordLabelArray();
        }
        return self::$customRecordLabels;
    }

    public static function getHooks()
    {
        if (self::$hooks === null) {
            self::$hooks = REDCapManagement::getProjectsHooksArray();
        }
        return self::$hooks;
    }

    public static function getSurveysHash()
    {
        if (self::$surveysHash === null) {
            self::$surveysHash = REDCapManagement::getProjectsSurveyHashArray();
        }
        return self::$surveysHash;
    }

    public static function getModuleEmailAlerts($module = null, $hubProjectName = null)
    {
        if (self::$moduleEmailAlerts === null) {
            self::$moduleEmailAlerts = REDCapManagement::getProjectsModuleEmailAlertsArray($module, $hubProjectName);
        }
        return self::$moduleEmailAlerts;
    }

    public static function getModuleGetPMID()
    {
        if (self::$moduleGetPMID === null) {
            self::$moduleGetPMID = REDCapManagement::getProjectsModuleGetPMIDArray();
        }
        return self::$moduleGetPMID;
    }

    public static function getExtraConstantsArray()
    {
        if (self::$extraConstants === null) {
            self::$extraConstants = REDCapManagement::getExtraConstantsArray();
        }
        return self::$extraConstants;
    }

    public static function getProjectsDESArray()
    {
        if (self::$projectsDESArray === null) {
            self::$projectsDESArray = REDCapManagement::getProjectsDESArray();
        }
        return self::$projectsDESArray;
    }

    public static function getProjectsModuleDESArray($hubProjectName)
    {
        if (self::$projectsDESArray === null) {
            self::$projectsDESArray = REDCapManagement::getProjectsModuleDESArray($hubProjectName);
        }
        return self::$projectsDESArray;
    }

    public static function getDESMapOtherConstantsArray()
    {
        if (self::$DESMapOtherConstants === null) {
            self::$DESMapOtherConstants = REDCapManagement::getDESMapOtherConstantsArray();
        }
        return self::$DESMapOtherConstants;
    }
}
?>