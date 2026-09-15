<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use REDCap;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

/**
 * The Routes class is responsible for handling page routing and preparing
 * the necessary data for Twig templates based on the current route (option)
 * and user context. It centralizes the logic for determining the appropriate
 * template to render, validating access rights, and preparing page-specific
 * data. This class focuses on integrating user roles, project settings, and
 * routing mappings, while delegating lower-level data fetching and business
 * logic to helper classes like PageData and HubData.
 *
 * Key Responsibilities:
 * - Determine the correct Twig template to render based on the current route.
 * - Validate user access and authorization, including token verification.
 * - Prepare and structure page-specific data, including headers and user info.
 * - Manage route-to-template mappings and conditional access logic.
 *
 * Key Methods:
 * - `getTemplate()`: Determines the appropriate Twig template based on the route.
 * - `preparePageData()`: Prepares page-specific data by delegating to PageData.
 * - `prepareHeaderData()`: Collects and prepares data for the page header.
 * - `hasValidToken()`: Verifies the validity of the user's session token.
 *
 * Notes:
 * - In Scope: Routing logic, user authorization checks, and data preparation
 *   for Twig templates based on the requested route.
 */
class Routes extends Model
{
    private $settings;
    private $option;
    private $isAdmin;
    private $token;
    private $currentUser;
    private $pageData;
    private $errorMessage = null;
    private $headerData = null;
    private $isAuthorizedAndHasRights;
    private $hubData;

    public function __construct(HarmonistHubPublicExternalModule $module, $projectId, $defaultValues, $token, $settings, $option)
    {
        parent::__construct($module, $projectId, $defaultValues);
        $this->settings = $settings;
        $this->option = $option;
        $this->token = $token;
        $this->isAuthorizedAndHasRights = $this->module->checkAuthorization($this->module);
        $this->headerData = $this->isHeaderRequired() ? $this->prepareHeaderData() : [];

        // Initialize PageData for preparing data
        $this->pageData = new PageData($module, $projectId, $settings, $defaultValues, $this->currentUser);
    }

    public function hasValidToken()
    {
        return !empty($_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$this->module->getSecurityHandler()->getTokenSessionName()]) &&
            $this->module->getSecurityHandler()->isTokenCorrect($_SESSION[SecurityHandler::SESSION_TOKEN_STRING][$this->module->getSecurityHandler()->getTokenSessionName()]);
    }

    public function getTemplate(): ?string
    {
        $mapping = $this->getPageMappings();
        if($this->hasValidToken()) {
            return !empty($mapping[$this->option]) ? $mapping[$this->option] : 'hub/home.html.twig';
        }
        return 'hub/login.html.twig';
    }

    public function preparePageData(): array
    {
        if(empty($this->option) && $this->hasValidToken()) {

        }else {
            if (!$this->isAuthorizedAndHasRights || $this->isInactiveUser() || $this->option == 'sout') {
                // If the user is unauthorized, show the login page
                // If the user is inactive, show the login page
            } else {
                // If none of the above, show an error message and the login page
                $this->errorMessage = "<strong>This Access Link has expired. </strong> <br />Please request a new Access Link below.";
            }
            if(!$this->hasValidToken() && $this->module->getSecurityHandler()->doesTokenExistInUrl()){
                $this->errorMessage = "<strong>This Access Link has expired. </strong> <br />Please request a new Access Link below.";
            }
            return $this->pageData->prepareLoginData();
        }
        // Delegate to PageData based on the option/route
        switch ($this->option) {
            case '':
                return $this->pageData->prepareHomepageData();
            default:
                return [];
        }
    }

    public function isHeaderRequired()
    {
        return !(array_key_exists(SecurityHandler::SESSION_OPTION_STRING, $_REQUEST) && $this->option === 'dfq');
    }

    public function prepareHeaderData() {
        $indexUrl = $this->module->getUrl('index.php');

        if ($this->module->getSecurityHandler()->isAuthorizedPage() && $this->isAuthorizedAndHasRights) {
            $this->token = $this->module->getSecurityHandler()->getTokenSession();
            $indexUrl = preg_replace('/pid=(\d+)/', "pid=" . $this->getPidsArray()['PROJECTS'], $this->module->getUrl('index.php'));
        }

        // Fetch user and region information
        $this->hubData = new HubData($this->module, $this->module->getSecurityHandler()->getTokenSessionName(), $this->token, $this->getPidsArray());
        $this->currentUser = $this->currentUser ?? $this->hubData->getCurrentUser();
        $personRegion = $this->hubData->getPersonRegion();
        $personRegionCode = $personRegion ? arrayKeyExistsReturnValue($personRegion, ['region_code']) : "";

        // Fetch requests manually (due to issues with $this->hubData->getAllRequests())
        $requestParams = [
            'project_id' => $this->getPidsArray()['RMANAGER'],
            'return_format' => 'array',
            'filterType' => 'RECORD',
            'fields' => [
                "requestopen_ts", "approval_y", "finalize_y", "region_response_status", "request_id", "contact_region",
                "assoc_concept", "mr_temporary", "contact_email", "request_title", "request_type", "finalconcept_doc",
                "finalconcept_pdf", "author_doc", "workflowcomplete_d", "contact_name", "due_d"
            ],
        ];
        $recordSetRM = \REDCap::getData($requestParams);
        $requests = ProjectData::getProjectInfoArrayRepeatingInstruments($recordSetRM, $this->getPidsArray()['RMANAGER'], ['approval_y' => 1]);

        // Retrieve project settings
        $hubProjectName = $this->settings['hub_name'] ?? "";
        $pastRequestDuration = arrayKeyExistsReturnValue($this->settings, ['pastrequest_dur']);

        // User-specific data
        $userFullName = arrayKeyExistsReturnValue($this->currentUser, ['firstname']) . ' ' . arrayKeyExistsReturnValue($this->currentUser, ['lastname']);
        $this->isAdmin = arrayKeyExistsReturnValue($this->currentUser, ['is_admin']);
        $voteRegion = arrayKeyExistsReturnValue($personRegion, ['voteregion_y']);
        $currentUserRegion = arrayKeyExistsReturnValue($this->currentUser, ['person_region']);
        $numberOfOpenRequests = $this->module->escape(numberOfOpenRequest($requests, $currentUserRegion, $voteRegion, $pastRequestDuration));

        // Admin-specific request handling
        $requestAdmin = [];
        $numberOfAdminRequests = 0;
        if ($this->isAdmin) {
            $requestAdmin = ProjectData::getProjectInfoArrayRepeatingInstruments($recordSetRM, $this->getPidsArray()['RMANAGER']);
            ArrayFunctions::array_sort_by_column($requestAdmin, 'requestopen_ts');
            $numberOfAdminRequests = $this->module->escape(numberOfAdminRequest($requestAdmin));
        }

        // Hide/Show Projects Tab
        $deactivateProjects = (
            arrayKeyExistsReturnValue($this->settings, ['deactivate_projects_opt'])  == "0" || // Hide for all users
            (arrayKeyExistsReturnValue($this->settings, ['deactivate_projects_opt']) == "1" && arrayKeyExistsReturnValue($this->currentUser, ['harmonistadmin_y']) !== '1') // Hide for non-admin users
        ) ? true : false;

        // Generate the logo URL
        $logoUrl = !empty($this->settings['hub_logo']) ? getFile($this->module, $this->settings['hub_logo'], 'src') : null;

        return [
            'index_url' => $indexUrl,
            'token' => $this->token,
            'settings' => $this->settings,
            'hub_project_name' => $hubProjectName,
            'person_region_code' => $personRegionCode,
            'user_full_name' => $userFullName,
            'number_of_open_requests' => $numberOfOpenRequests,
            'deactivate_projects' => $deactivateProjects,
            'is_admin' => $this->isAdmin,
            'number_of_admin_requests' => $numberOfAdminRequests,
            'logo_url' => $logoUrl,
            'request_admin' => $requestAdmin,
            'option' => $this->option
        ];
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
    public function getHeaderData()
    {
        return $this->headerData;
    }

    public function getHubData()
    {
        return $this->hubData;
    }

    public function getisAdmin()
    {
        return $this->isAdmin;
    }

    public function canAccessDatahub()
    {
//        return $this->settings['deactivate_datahub___1'] != "1";
        return false;
    }

    public function canAccessTableCenter()
    {
        return $this->canAccessDatahub() && $this->settings['deactivate_tblcenter___1'] != "1";
    }

    public function canAccessToolkit()
    {
        return $this->settings['deactivate_toolkit___1'] != "1";
    }

    public function canAccessWritingGroup()
    {
        return $this->settings['writinggroup_opt'] == "2" || ($this->settings['writinggroup_opt'] == "1" && $this->isAdmin);
    }

    public function canAccessDataRequestBuilder($sop)
    {
        // If record key does NOT exist in request, return true
        if (!array_key_exists('record', $_REQUEST)) {
            return true;
        }

        // Validate input
        if (empty($sop) || !is_array($sop)) {
            return false;
        }

        // If SOP visibility is set to public (2), everyone can access
        if (($sop['sop_visibility'] ?? null) == '2') {
            return true;
        }

        // Check if SOP is active and in draft status
        $isActiveDraft = ($sop['sop_active'] ?? null) == '1' && ($sop['sop_status'] ?? null) == '0';

        // Check if current user is the SOP creator, data contact, or secondary creator
        $isCreatorOrContact =
            ($sop['sop_creator'] ?? null) == ($this->currentUser['record_id'] ?? null) ||
            ($sop['sop_creator2'] ?? null) == ($this->currentUser['record_id'] ?? null) ||
            ($sop['sop_datacontact'] ?? null) == ($this->currentUser['record_id'] ?? null) ||
            ($sop['sop_hubuser'] ?? null) == ($this->currentUser['record_id'] ?? null);

        // Grant access if SOP is active/draft AND user is authorized
        return $isActiveDraft && ($this->isAdmin || $isCreatorOrContact);
    }

    private function getPageMappings()
    {
        return [
            'dnd' => ($this->canAccessDatahub() && $this->isAuthorizedAndHasRights) ? 'sop_retrieve_data.php' : null,
            'lge' => ($this->canAccessDatahub() && $this->isAuthorizedAndHasRights) ? 'sop_data_activity_log_delete.php' : null,
            'log' => 'hub/hub_changelog.php',
            'smn' => $this->canAccessDatahub() ? 'sop/sop_request_data.php' : null,
            'sra' => $this->canAccessDatahub() ? 'sop/sop_recent_activity.php' : null,
            'tbl' => $this->canAccessTableCenter() ? 'sop/sop_table_center.php' : null,
            'ofs' => $this->canAccessDatahub() ? 'sop/sop_document_library.php' : null,
            'sop' => $this->canAccessDatahub() ? 'sop/sop_data_request_title.php' : null,
            'dna' => $this->canAccessDatahub() ? 'sop/sop_news_archive.php' : null,
            'ss1' => $this->canAccessDatahub() ? 'sop/sop_steps_menu.php' : null,
            'ss5' => $this->canAccessDatahub() ? 'sop/sop_step_5.php' : null,
            'spr' => $this->canAccessDatahub() ? 'sop/sop_make_public_request_review.php' : null,
            'lgd' => $this->canAccessDatahub() ? 'sop/sop_data_activity_log.php' : null,
            'cpt' => 'hub/hub_concepts.php',
            'ttl' => 'hub/hub_concept_title.php',
            'cwg' => $this->canAccessWritingGroup() ? 'hub/hub_concept_writing_group.php' : null,
            'hub' => empty($_REQUEST['record']) ? 'hub/hub_requests.php' : 'hub/hub_request_title.php',
            'adm' => $this->isAdmin ? 'hub/hub_admin.php' : 'hub/hub_error_page.php',
            'usr' => 'hub/hub_users.php',
            'mra' => 'hub/hub_my_requests_archive.php',
            'mrr' => 'hub/hub_my_requests_archive_rejected.php',
            'hra' => 'hub/hub_recent_activity.php',
            'upd' => $this->canAccessDatahub() ? 'sop/sop_submit_data.php' : null,
            'dat' => $this->canAccessDatahub() ? 'hub/hub_data.php' : null,
            'pdc' => $this->canAccessDatahub() ? 'sop/sop_data_call_archive.php' : null,
            'out' => 'hub/hub_publications.php',
            'mts' => (!$this->deactivateMetrics() || $this->isAdmin) ? 'hub/hub_metrics_stats.php' : null,
            'mth' => (!$this->deactivateDataMetrics() || $this->isAdmin) ? 'sop/sop_metrics_stats.php' : null,
            'faq' => 'faq/hub_faq.php',
            'pro' => 'hub/hub_profile.php',
            'bug' => 'hub/hub_report_bug.php',
            'unf' => 'hub/hub_request_title.php',
            'und' => $this->canAccessDatahub() ? 'sop/sop_data_request_title.php' : null,
            'cal' => $this->isCalendarActive() ? 'hub/hub_calendar.php' : null,
            'abt' => 'hub/hub_about.php',
            'dab' => $this->canAccessToolkit() ? 'sop/sop_explore_data.php' : null,
            'tlk' => $this->canAccessToolkit() ? 'sop/sop_go_to_toolkit.php' : null,
            'std' => !$this->deactivateProjects() ? 'hub/hub_projects.php' : null,
            'sts' => !$this->deactivateProjects() ? 'hub/hub_projects_title.php' : null
        ];
    }

    private function isInactiveUser()
    {
        return isset($this->currentUser) && arrayKeyExistsReturnValue($this->currentUser, ['active_y']) == "0";
    }

    private function isCalendarActive()
    {
        return arrayKeyExistsReturnValue($this->settings, ['calendar_active',1]) == "1";
    }

    private function deactivateMetrics()
    {
        return arrayKeyExistsReturnValue($this->settings, ['deactivate_metrics___1']) == "1";
    }

    private function deactivateDataMetrics()
    {
        return arrayKeyExistsReturnValue($this->settings, ['deactivate_datametrics___1']) == "1";
    }

    private function deactivateProjects(): bool
    {
        $deactivateProjects = arrayKeyExistsReturnValue($this->settings, ['deactivate_projects_opt']);
        if ($deactivateProjects == "0") {
            // Hide for all users
            return true;
        } elseif ($deactivateProjects == "1" && arrayKeyExistsReturnValue($this->currentUser, ['harmonistadmin_y']) != '1') {
            // Hide for non-admin users
            return true;
        } elseif ($deactivateProjects == "2") {
            // Show for everyone
            return false;
        }
        return false;
    }
}
