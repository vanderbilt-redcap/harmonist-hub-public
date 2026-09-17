<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

use REDCap;

class ProjectInstaller
{
    private $module;
    private $projectId;
    private $hubProjectName;
    private $userPermission;
    private $pidHome;
    private $desProjectId;
    private $isHubUpdates = false;

    public function __construct($module, $projectId)
    {
        $this->module = $module;
        $this->projectId = $projectId;
        $this->hubProjectName = $this->module->getProjectSetting('hub-projectname');
        $this->userPermission = $this->module->getProjectSetting('user-permission', $projectId);
        $this->pidHome = null;
        $this->desProjectId = null;
    }

    /**
     * Main function to install projects.
     */
    public function installProjects($specificProjectsArray = null)
    {
        $record = 1;
        if (!empty($specificProjectsArray)) {
            $record = $this->module->framework->addAutoNumberedRecord($this->projectId);
            $this->isHubUpdates = true;
        }

        // Cache frequently used arrays locally
        $projectsArray = $specificProjectsArray ?? REDCapProjectData::getConstants();
        $titles = REDCapProjectData::getTitles();
        $customRecordLabels = REDCapProjectData::getCustomRecordLabels();
        $showArray = REDCapProjectData::getShow();

        foreach ($projectsArray as $index => $name) {
            // Create project and its data dictionary
            $projectTitle = $this->hubProjectName . " Hub: " . $titles[$index];
            $projectIdNew = $this->createProject($index, $name, $record, $projectTitle, $customRecordLabels, $showArray);

            // Handle specific project types
            $this->handleSpecialProjects($name, $projectIdNew);

            // Add repeatable instruments
            $this->addRepeatableInstrument($index, $projectIdNew);

            // Enable external modules and hooks
            $this->enableHooksAndModules($index, $projectIdNew, $this->projectId);

            // Add user permissions
            $this->addUserPermissions($projectIdNew);

            // Create next record
            $record++;

            // Create surveys
            $record = $this->createSurveys($index, $projectIdNew, $record);

            // Install DES for DATAMODELMETADATA
            if ($name === "DATAMODELMETADATA") {
                $this->desProjectId = $this->installDataModelBrowser($record);
                $record++;
            }
        }

        // Set user permissions as read-only on the main project
        $this->setMainProjectPermissions();

        // Clear cache and set up the HOME project
        $this->clearCacheAndSetupHomeProject();

        // Upload SQL fields to projects
        $this->uploadSQLFieldsToProjects();
    }

    /**
     * Installs the Data Model Browser External Module (EM).
     */
    public function installDataModelBrowser($record): int
    {
        $projectsArrayDes = REDCapProjectData::getProjectsDESArray();
        $projectsArrayModuleDes = REDCapProjectData::getProjectsModuleDESArray($this->hubProjectName);
        $pidsArray = REDCapManagement::getPIDsArray($this->projectId);
        $desMapperProjectId = null;

        foreach ($projectsArrayDes as $name => $projectTitleSuffix) {
            // Build the project title
            $projectTitle = $this->hubProjectName . " Hub: " . $projectTitleSuffix;

            // Create or update the project
            $projectIdNew = $this->createOrUpdateDESProject($name, $projectTitle, $pidsArray);

            if ($name === "MAP") {
                // Handle specific logic for the DES Mapper
                $desMapperProjectId = $projectIdNew;
                $this->activateDataModelBrowserEM($projectIdNew, $projectsArrayModuleDes);
                $this->setCustomLabels($projectIdNew, "[project_constant]: [project_id]");
            } else {
                // Handle logic for other DES-related projects
                $this->mapToDESProject($desMapperProjectId, $projectIdNew, $pidsArray);
            }
        }

        // Map DES Mapper PID to Harmonist Mapper
        $extraConstants = REDCapProjectData::getExtraConstantsArray();
        $mapperConstant = $extraConstants[0] ?? "DES_MAPPER";
        REDCapManagement::addMapRecord($this->module, $pidsArray['PROJECTS'], $record, $desMapperProjectId, $mapperConstant, 0);

        return $desMapperProjectId;
    }

    /**
     * Updates the DES mapper project with the new project details.
     * @param int $desProjectId The ID of the existing DES MAPPER project to be updated.
     * @param int $projectIdNew The new project ID to be mapped to the DES MAP project.
     * @param string $name The constant name the DES project.
     */
    public function updateDESMapProject($desProjectId, $projectIdNew, $name): void
    {
        // Determine the record number based on the project name
        $desRecord = $this->getDESRecordNumber($name);

        // Retrieve event ID
        $eventId = $this->getEventIdFromProject($desProjectId);

        // Prepare update record data
        $updateRecord = [
            $desRecord => [
                $eventId => ['project_id' => $projectIdNew]
            ]
        ];

        // Save the updated record to the DES mapper project
        $this->saveToDESMapper($desProjectId, $updateRecord);
    }

    /**
     * Create a new project and import the data dictionary.
     */
    private function createProject($index, $name, &$record, $projectTitle, $customRecordLabels, $showArray)
    {
        // Create the new project and import the data dictionary
        $projectIdNew = $this->module->createProjectAndImportDataDictionary($name, $projectTitle);

        //If someone is updating missing projects add it to the logs
        if($this->isHubUpdates){
            $action = "Project installed with Hub Updates";
            $dataChanges = "$name\nPID: $projectIdNew\nProject title: $projectTitle";
            if(!empty(USERID)){
                $action .= " by ".USERID;
            }
            \REDCap::logEvent($action, $dataChanges, null, null, null, $this->projectId);
        }

        // If record is null, generate an auto-numbered record
        if ($record === null) {
            $record = $this->module->framework->addAutoNumberedRecord($this->projectId);
        }

        if ($projectIdNew) {
            REDCapManagement::addMapRecord(
                $this->module,
                $this->projectId,
                $record,
                $projectIdNew,
                $name,
                $showArray[$index]
            );
        }

        // Update the custom record label, if provided
        if (!empty($customRecordLabels[$index])) {
            $this->module->query(
                "UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?",
                [$customRecordLabels[$index], $projectIdNew]
            );
        }

        return $projectIdNew;
    }

    /**
     * Handle specific logic for special projects.
     */
    private function handleSpecialProjects($name, $projectIdNew)
    {
        switch ($name) {
            case 'SETTINGS':
                // Configure SETTINGS project
                $this->hydrateProjectSettings($name, $projectIdNew);
                break;

            case 'HOME':
                // Set the HOME project ID
                $this->pidHome = $projectIdNew;
                break;

            case 'JSONCOPY':
            case 'FILELIBRARY':
                // Update DES mapping for JSONCOPY or FILELIBRARY
                if (!empty($this->desProjectId) && $this->desProjectId !== $projectIdNew) {
                    $this->updateDESMapProject($this->desProjectId, $projectIdNew, $name);
                }
                break;

            default:
                // Do nothing for other cases
                break;
        }
    }

    /**
     * Configure settings for the SETTINGS project.
     */
    private function hydrateProjectSettings($name, $projectIdNew): void
    {
        $hubProfile = $this->module->getProjectSetting('hub-profile');
        // For now hardcode to solo
        $hubProfile = "solo";

        // Add the first record to the project
        $eventId = $this->getEventIdForProject($projectIdNew);
        $this->module->addProjectToList($projectIdNew, $eventId, 1, 'record_id', 1);

        // Configure project settings based on hub profile
        switch ($hubProfile) {
            case 'solo':
                $this->module->addProjectToList($projectIdNew, $eventId, 1, 'deactivate_datahub', 1);
                $this->module->addProjectToList($projectIdNew, $eventId, 1, 'deactivate_datadown', 1);
                $this->module->addProjectToList($projectIdNew, $eventId, 1, 'deactivate_tblcenter', 1);
                $this->module->addProjectToList($projectIdNew, $eventId, 1, 'deactivate_toolkit', 1);
                break;

            case 'basic':
                $this->module->addProjectToList($projectIdNew, $eventId, 1, 'deactivate_datadown', 1);
                $this->module->addProjectToList($projectIdNew, $eventId, 1, 'deactivate_toolkit', 1);
                break;

            case 'all':
                $this->notifyAdminForToolkitSetup($projectIdNew);
                break;
        }

        // Install default values as they get delete with creating the new record
        $this->installDefault($projectIdNew, $eventId, 1);
    }

    private function getEventIdForProject($projectId)
    {
        $query = $this->module->query(
            "SELECT b.event_id FROM redcap_events_arms a 
         LEFT JOIN redcap_events_metadata b ON a.arm_id = b.arm_id 
         WHERE a.project_id = ?",
            [$projectId]
        );
        $result = $query->fetch_assoc();
        return $result['event_id'] ?? null;
    }

    private function notifyAdminForToolkitSetup($projectIdNew)
    {
        $subject = "Data Toolkit activation request for " . $this->hubProjectName . " Hub";
        $message = "<div>Dear Administrator,</div><br/>
                    <div>A new request has been enabled to activate the Data Toolkit for <strong>" . $this->hubProjectName . " Hub</strong> (<em>PID " . $projectIdNew . "</em>)</div>";
        sendEmail(REDCapManagement::DEFAULT_EMAIL_ADDRESS, "noreply.harmonist@vumc.org", "noreply.harmonist@vumc.org", $subject, $message);
    }

    private function installDefault($projectId, $eventId, $record)
    {
        $defaultValues = new ProjectData;
        $defaultVars = $defaultValues->getDefaultValues($projectId);

        // Add each default variable and value to the project
        foreach ($defaultVars as $variable => $value) {
            $this->module->addProjectToList($projectId, $eventId, $record, $variable, $value);
        }
    }

    /**
     * Add repeatable instruments.
     */
    private function addRepeatableInstrument($index, $projectIdNew)
    {
        $repeatable = REDCapProjectData::getRepeatable();
        REDCapManagement::addRepeatableInstrument(
            $this->module,
            $repeatable[$index],
            $projectIdNew
        );
    }

    /**
     * Enable hooks and external modules.
     */
    private function enableHooksAndModules($index, $projectIdNew, $pid)
    {
        // Retrieve hooks and modules data using arrayKeyExistsReturnValue
        $hooksData = arrayKeyExistsReturnValue(REDCapProjectData::getHooks(), [$index]);
        $emailAlertsData = arrayKeyExistsReturnValue(REDCapProjectData::getModuleEmailAlerts($this->module, $this->hubProjectName), [$index]);
        $pmidData = arrayKeyExistsReturnValue(REDCapProjectData::getModuleGetPMID(), [$index]);

        //Enable Harmonist Hub module in other projects
        if ($hooksData == '1') {
            $this->enableModuleIfRequired($projectIdNew, "harmonist-hub-public", true);
            $this->module->enableModule($projectIdNew, "harmonist-hub-public");
            $this->module->setProjectSetting('hub-mapper', $pid, $projectIdNew);
        }

        // Enable "vanderbilt_emailTrigger" module if emailAlertsData exists
        $this->enableModuleIfRequired($projectIdNew, "vanderbilt_emailTrigger", $emailAlertsData);

        // Enable "get-pmid-details" module if pmidData exists
        $this->enableModuleIfRequired($projectIdNew, "get-pmid-details", $pmidData);
    }

    private function enableModuleIfRequired($projectId, $moduleName, $moduleData)
    {
        if ($moduleData) {
            if (is_array($moduleData)) {
                REDCapManagement::enableAnotherModule($this->module, $projectId, $moduleName, $moduleData);
                \REDCap::logEvent("Module enabled from Project Installer", $moduleName." external module has been enabled", null, null, null, $projectId);
            }
        }
    }

    /**
     * Add user permissions.
     */
    private function addUserPermissions($projectIdNew)
    {
        $userRoles = HubREDCapUsers::getAllRoles($this->module, $projectIdNew);
        $roleId = $userRoles[HubREDCapUsers::HUB_ROLE_USER];

        foreach ($this->userPermission as $user) {
            if ($user !== null && $user !== USERID) {
                $this->addUserToProject($projectIdNew, $user, $roleId);
            }
        }
    }

    private function addUserToProject($projectId, $username, $roleId)
    {
        HubREDCapUsers::addUserToProject(
            $this->module,
            $projectId,
            $username,
            $roleId,
            "Harmonist Installation Process",
            $this->projectId,
            HubREDCapUsers::HUB_ROLE_USER
        );
    }

    /**
     * Create surveys for the project.
     */
    private function createSurveys($index, $projectIdNew, $record)
    {
        $surveys = REDCapProjectData::getSurveys();
        $surveysHash = REDCapProjectData::getSurveysHash();

        if (array_key_exists($index, $surveys)) {
            $themeId = ProjectData::getThemeId($this->module);
            $this->module->query("UPDATE redcap_projects SET surveys_enabled = ? WHERE project_id = ?", ["1", $projectIdNew]);

            foreach ($surveys[$index] as $survey) {
                $surveyId = $this->createSurvey($projectIdNew, $survey, $themeId);

                if ($index != 1 && array_key_exists($index, $surveysHash) && $survey === $surveysHash[$index]['instrument']) {
                    $hash = $this->generateSurveyHash($surveyId, $projectIdNew);
                    REDCapManagement::addMapRecord(
                        $this->module,
                        $this->projectId,
                        $record,
                        $hash,
                        $surveysHash[$index]['constant'],
                        0
                    );
                    $record++;
                }
            }
        }

        return $record;
    }


    private function createSurvey($projectId, $formName, $themeId)
    {
        $surveyTitle = ucwords(str_replace("_", " ", $formName));
        $this->module->query(
            "INSERT INTO redcap_surveys (project_id, form_name, survey_enabled, save_and_return, save_and_return_code_bypass, edit_completed_response, title, theme) VALUES (?,?,?,?,?,?,?,?)",
            [$projectId, $formName, 1, 1, 1, 1, $surveyTitle, $themeId]
        );
        return db_insert_id();
    }

    private function generateSurveyHash($surveyId, $projectId)
    {
        $hash = $this->module->generateUniqueRandomSurveyHash();
        $Proj = new \Project($projectId);
        $eventId = $Proj->firstEventId;

        $this->module->query(
            "INSERT INTO redcap_surveys_participants (survey_id, hash, event_id) VALUES (?,?,?)",
            [$surveyId, $hash, $eventId]
        );

        return $hash;
    }

    /**
     * Set user permissions for the main project.
     */
    private function setMainProjectPermissions()
    {
        $fieldsRights = "data_entry";
        $instrumentNames = \REDCap::getInstrumentNames(null, $this->projectId);
        $dataEntry = "[" . implode(',2][', array_keys($instrumentNames)) . ",2]";

        foreach ($this->userPermission as $user) {
            if ($user !== null && $user !== USERID) {
                $this->module->query(
                    "UPDATE redcap_user_rights SET " . $fieldsRights . " = ? WHERE project_id = ? AND username = ?",
                    [$dataEntry, $this->projectId, $user]
                );
            }
        }
    }

    /**
     * Clear cache and set up the HOME project.
     */
    private function clearCacheAndSetupHomeProject()
    {
        // Clear the project cache to ensure the latest updates are pulled from the database
        $this->module->clearProjectCache();

        if (!empty($this->pidHome)) {
            $Proj = new \Project($this->pidHome);
            $eventId = $Proj->firstEventId;

            // Create the first record in the home project
            $this->module->addProjectToList($this->pidHome, $eventId, 1, 'record_id', 1);

            $params = [
                'project_id' => $this->projectId,
                'return_format' => 'json-array',
                'fields' => 'project_id',
                'filterLogic' => "[project_constant]='REQUESTLINK'"
            ];
            $requestLinkPid = arrayKeyExistsReturnValue(\REDCap::getData($params), [0, 'project_id']);

            $params = [
                'project_id' => $this->projectId,
                'return_format' => 'json-array',
                'fields' => 'project_id',
                'filterLogic' => "[project_constant]='SURVEYPERSONINFO'"
            ];
            $surveyPersonInfoPid = arrayKeyExistsReturnValue(\REDCap::getData($params), [0, 'project_id']);

            // Set up the "Hub Actions" section
            $arrayRepeatInstances = [];
            $hubActions = [
                'links_sectionhead' => "Hub Actions",
                'links_sectionorder' => '1',
                'links_sectionicon' => '1',
                'links_text1' => 'Create EC request',
                'links_link1' => APP_PATH_WEBROOT_FULL . 'surveys/?s=' . $requestLinkPid,
                'links_text2' => 'Add Hub user',
                'links_link2' => APP_PATH_WEBROOT_FULL . 'surveys/?s=' . $surveyPersonInfoPid,
            ];
            $arrayRepeatInstances[1]['repeat_instances'][$eventId]['quick_links_section'][1] = $hubActions;

            // Save the data for "Hub Actions"
            $params = [
                'project_id' => $this->pidHome,
                'dataFormat' => 'array',
                'data' => $arrayRepeatInstances,
                'overwriteBehavior' => "overwrite",
                'dateFormat' => "YMD",
                'type' => "flat"
            ];
            $results = \REDCap::saveData($params);

            // Set up the "Harmonist" section
            $harmonistLinks = [
                'links_sectionhead' => "Harmonist",
                'links_sectionorder' => '5',
                'links_sectionicon' => '6',
                'links_text1' => 'About us',
                'links_link1' => $this->module->getUrl('index.php') . '&NOAUTH&option=abt',
                'links_text2' => 'Report a bug',
                'links_link2' => $this->module->getUrl('index.php') . '&NOAUTH&option=bug',
                'links_stay2' => ["1" => "1"],
            ];
            $arrayRepeatInstances[1]['repeat_instances'][$eventId]['quick_links_section'][2] = $harmonistLinks;

            // Save the data for "Harmonist" section
            $params = [
                'project_id' => $this->pidHome,
                'dataFormat' => 'array',
                'data' => $arrayRepeatInstances,
                'overwriteBehavior' => "overwrite",
                'dateFormat' => "YMD",
                'type' => "flat"
            ];
            $results = \REDCap::saveData($params);
        }
    }

    /**
     * Upload SQL fields to projects.
     */
    private function uploadSQLFieldsToProjects()
    {
        // Get all project IDs
        $pidsArray = REDCapManagement::getPIDsArray($this->projectId);

        // Clear the project cache
        $this->module->clearProjectCache();

        // Get the SQL fields for the projects
        $projectsArraySQL = REDCapManagement::getProjectsSQLFieldsArray($pidsArray);

        // Iterate over each project and apply the SQL updates
        foreach ($projectsArraySQL as $projectId => $projects) {
            foreach ($projects as $varId => $options) {
                foreach ($options as $optionId => $value) {
                    if ($optionId === 'query') {
                        $this->module->query(
                            "UPDATE redcap_metadata SET element_enum = ? WHERE project_id = ? AND field_name = ?",
                            [$value, $projectId, $varId]
                        );
                    }
                    if ($optionId === 'autocomplete' && $value == '1') {
                        $this->module->query(
                            "UPDATE redcap_metadata SET element_validation_type = ? WHERE project_id = ? AND field_name = ?",
                            ["autocomplete", $projectId, $varId]
                        );
                    }
                    if ($optionId === 'label' && $value !== "") {
                        $this->module->query(
                            "UPDATE redcap_metadata SET element_label = ? WHERE project_id = ? AND field_name = ?",
                            [$value, $projectId, $varId]
                        );
                    }
                }
            }
        }

        // Clear the project cache again after updates
        $this->module->clearProjectCache();
    }

    private function createOrUpdateDESProject($name, $projectTitle, $pidsArray)
    {
        if ($name === "MAP" && isset($pidsArray['DES']) && !ProjectData::isProjectDeleted($this->module, $pidsArray['DES'])) {
            // Update existing DES Mapper project
            $this->module->query("UPDATE redcap_projects SET app_title = ? WHERE project_id = ?", [$projectTitle, $pidsArray['DES']]);
            $path = $this->module->framework->getModulePath() . "csv/PID.csv";
            $this->module->framework->importDataDictionary($pidsArray['DES'], $path);
            return $pidsArray['DES'];
        } else {
            // Create a new DES project
            return $this->module->createProjectAndImportDataDictionary($name . "_DES", $projectTitle);
        }
    }

    private function activateDataModelBrowserEM($projectId, $moduleSettings)
    {
        REDCapManagement::enableAnotherModule($this->module, $projectId, "data-model-browser", $moduleSettings);
    }

    private function setCustomLabels($projectId, $customLabel)
    {
        $this->module->query("UPDATE redcap_projects SET custom_record_label = ? WHERE project_id = ?", [$customLabel, $projectId]);
    }

    private function mapToDESProject($desMapperProjectId, $projectIdNew, $pidsArray)
    {
        $recordDES = 1;

        // Map the SETTINGS project
        REDCapManagement::addMapRecord($this->module, $desMapperProjectId, $recordDES, $projectIdNew, "SETTINGS", 0);

        // Map other constants
        foreach (REDCapProjectData::getDESMapOtherConstantsArray() as $constantName) {
            $recordDES++;
            REDCapManagement::addMapRecord($this->module, $desMapperProjectId, $recordDES, $pidsArray[$constantName] ?? null, $constantName, 0);
        }

        // Map the FILELIBRARY project
        $recordDES++;
        REDCapManagement::addMapRecord($this->module, $desMapperProjectId, $recordDES, $pidsArray['FILELIBRARY'], "FILEREPO", 0);

        // Save settings in the new DES project
        $eventId = $this->module->framework->getEventId($projectIdNew);
        $this->module->addProjectToList($projectIdNew, $eventId, 1, 'record_id', 1);
        $this->module->addProjectToList($projectIdNew, $eventId, 1, 'des_wkname', $this->hubProjectName);
    }

    /**
     * Determines the DES record number based on the project name.
     */
    private function getDESRecordNumber($name): int
    {
        return ($name === "JSONCOPY") ? 5 : 6;
    }

    /**
     * Retrieves the event ID for a given project.
     */
    private function getEventIdFromProject($projectId): int
    {
        $Proj = new \Project($projectId);
        return $Proj->firstEventId;
    }

    /**
     * Saves the updated data to the DES mapper project.
     */
    private function saveToDESMapper($desProjectId, array $updateRecord): void
    {
        $params = [
            'project_id' => $desProjectId,
            'dataFormat' => 'array',
            'data' => $updateRecord,
            'overwriteBehavior' => "overwrite",
            'dateFormat' => "YMD",
            'type' => "flat"
        ];

        $results = \REDCap::saveData($params);

        // Check for errors in saving data
        if (!empty($results['errors'])) {
            $errorMessages = implode(", ", $results['errors']);
            $this->module->log("Error updating DES mapper project ($desProjectId): $errorMessages");
        }
    }
}
