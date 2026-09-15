<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use REDCap;

/**
 * The PageData class is responsible for managing and preparing data for pages
 * served through the Hub's `index.php`. It acts as a bridge between backend
 * data and front-end rendering, ensuring that all necessary information is
 * structured and ready to be passed to Twig templates.
 * The class focuses on aggregating,sanitizing, and structuring page-specific
 * data, while delegating database queries and complex business logic to
 * external classes and services.
 *
 * Key Responsibilities:
 * - Collect and format data for specific pages (e.g., login, homepage).
 * - Filter, sanitize, and structure input to ensure compatibility and security.
 * - Compute additional derived data (e.g., metrics, graphs, summaries) for
 *   enhanced front-end features.
 * - Integrate data from settings, user context, and external project resources.
 *
 * Key Methods:
 * - `prepareLoginData()`: Prepares data for the login page, such as hub-specific
 *   settings, CSRF tokens, and user options.
 * - `prepareHomepageData()`: Aggregates and organizes data for the homepage,
 *   including open requests, metrics, recent activity, deadlines, and graph data.
 *
 * Notes:
 * - In Scope: Data preparation and formatting for Twig templates; integration
 *   of module settings, user-specific information, and project data.
 */
class PageData extends Model
{
    private $settings;
    private $currentUser;

    public function __construct($module, $projectId, $settings, $defaultValues, $currentUser)
    {
        parent::__construct($module, $projectId, $defaultValues);
        $this->settings = $settings;
        $this->currentUser = $currentUser;
    }

    public function prepareLoginData()
    {
        $option = filter_input(INPUT_GET, 'option', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
        $record = filter_input(INPUT_GET, 'record', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
        $redcap_csrf_token = $this->module->getCSRFToken();

        return [
            'hub_project_name' => filter_tags($this->module->getProjectSetting('hub-projectname')),
            'hub_login_text' => filter_tags($this->settings['hub_login_text']),
            'hub_login_blue_text' => filter_tags($this->settings['hub_login_blue_text']),
            'option' => $option,
            'record' => $record,
            'url_get_link' => $this->module->getUrl('hub/hub_getLink.php', true),
            'redcap_csrf_token' => $redcap_csrf_token
        ];
    }

    public function prepareHomepageData()
    {
        // Fetch homepage data
        $RecordSetHome = \REDCap::getData($this->getPidsArray()['HOME'], 'array', null);
        $homepage = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetHome, $this->getPidsArray()['HOME'])[0];
        $homepage_links_sectionorder = $this->module->getChoiceLabels('links_sectionicon', $this->getPidsArray()['HOME']);

        // Prepare requests
        $hubData = new HubData($this->module, $this->module->getSecurityHandler()->getTokenSessionName(), null, $this->getPidsArray());
        $requests = $hubData->getAllRequests();
        $requestType = $this->module->getChoiceLabels('request_type', $this->getPidsArray()['RMANAGER']);
        $instance = $this->currentUser['person_region'];

        // Open requests and metrics
        $openRequests = [];
        $homeMetrics = [];
        if(!empty($requests)) {
            foreach ($requests as $req) {
                if (!hideRequestForNonVoters(arrayKeyExistsReturnValue($this->settings,['pastrequest_dur']), $req, arrayKeyExistsReturnValue($this->currentUser,['voteregion_y'])) && showOpenRequest($req, $instance)) {
                    $openRequests[$req['request_type']] = ($openRequests[$req['request_type']] ?? 0) + 1;
                }
                $homeMetrics[$req['request_type']] = ($homeMetrics[$req['request_type']] ?? 0) + 1;
            }
        }

        // Prepare "My Requests" HTML
        $user_req_header = getRequestHeader($hubData, $this->settings['vote_grid'], '1', 'home');
        $myRequests = [];
        if(!empty($requests)) {
            foreach ($requests as $req) {
                if (count($myRequests) >= 10) {
                    break;
                }
                $rowData = getHomeRequestHTML(
                    $this->module,
                    $hubData,
                    $this->getPidsArray(),
                    $req,
                    [],
                    $requestType,
                    0,
                    $this->settings['vote_visibility'],
                    $this->settings['vote_grid'],
                    $this->settings['pastrequest_dur'],
                    'home'
                );
                if(!empty($rowData)){
                    $myRequests[] = [
                        'html' => $rowData
                    ];
                }
            }
        }

        if(empty($myRequests)) {
            $myRequests[] = [
                'html' => '<tr><td class="ps-3"><em>No active requests</em></td></tr>',
            ];
        }

        // Prepare "My Concepts" HTML
        $RecordSetConcepts = \REDCap::getData([
            'project_id' => $this->getPidsArray()['HARMONIST'],
            'return_format' => 'array',
            'fields' => ['record_id','concept_id', 'concept_title'],
            'filterLogic' => "[active_y] = 'Y' AND ([contact_link] = '".$this->currentUser['record_id']."' 
            OR [contact2_link] = '".$this->currentUser['record_id']."')"
        ]);
        $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts, $this->getPidsArray()['HARMONIST']);
        ArrayFunctions::array_sort_by_column($concepts, 'concept_id', SORT_DESC);
        $myConcepts = [];
        if(!empty($concepts)) {
            foreach ($concepts as $concept) {
                if (count($myConcepts) >= 10) {
                    break;
                }
                $rowData = "<tr><td><strong>".$concept['concept_id']."</strong> | <a href='".$this->module->getUrl('index.php', true) . '&option=ttl&record=' . $concept['record_id'] ."' target='_blank' title='".$this->module->escape($concept['concept_title'])."'>".substr($concept['concept_title'], 0, 120) . (strlen($concept['concept_title']) > 120 ? '...' : '') ."</a></td></tr>";
                if(!empty($rowData)){
                    $myConcepts[] = [
                        'html' => $rowData
                    ];
                }
            }
        }

        if(empty($myConcepts)) {
            $myConcepts[] = [
                'html' => '<tr><td class="ps-3"><em>No active concepts</em></td></tr>',
            ];
        }

        // Prepare Recent Activity
        $comments7DaysYoung = \REDCap::getData($this->getPidsArray()['COMMENTSVOTES'], 'json-array', null);
        ArrayFunctions::array_sort_by_column($comments7DaysYoung, 'responsecomplete_ts', SORT_DESC);

        $recentActivity = [];
        $sevenDaysAgo = strtotime('-7 days');
        $i = 0;

        if(!empty($requests)) {
            foreach ($comments7DaysYoung as $comment) {
                if (strtotime(
                        $comment['responsecomplete_ts']
                    ) >= $sevenDaysAgo && $i < $this->settings['home_number_recentactivity']) {
                    $recentActivity[] = $this->prepareActivityData(
                        $this->module,
                        $hubData,
                        $this->getPidsArray(),
                        $comment
                    );
                    $i++;
                }
            }
        }

        if(empty($recentActivity)) {
            $recentActivity[] = [
                'html' => 'No activity in the last 7 days.',
            ];
        }

        // Prepare deadlines
        $deadlines = [];
        for ($i = 1; $i <= $this->settings['home_number_deadlines']; $i++) {
            if (!empty($homepage['deadline_text' . $i]) || !empty($homepage['deadline_date' . $i])) {
                $arrayDates = getNumberOfDaysLeftButtonHTML($homepage['deadline_date' . $i], '', 'float:right;', '0');
                $deadlines[] = [
                    'date' => $homepage['deadline_date' . $i],
                    'print' => '<tr><td class="ps-3" style="width: 30%">' . $arrayDates['text'] . ' ' . $arrayDates['button'] . '</td><td>' . $homepage['deadline_text' . $i] . '</td></tr>',
                ];
            }
        }

        // Sort deadlines by date
        usort($deadlines, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        // Prepare data for graph
        $requestsValues = array_count_values(array_column($requests, 'request_type'));
        foreach ($requestType as $keyLabel => $requestsLabel) {
            $requestsValues[$keyLabel] = $requestsValues[$keyLabel] ?? 0;
        }

        // Remove hidden request types
        $hiddenChoices = $this->defaultValues->getHideChoice($this->getPidsArray()['RMANAGER']);
        if (!empty($hiddenChoices['request_type'] ?? [])) {
            foreach ($hiddenChoices['request_type'] as $value) {
                unset($requestType[$value], $requestsValues[$value]);
            }
        }

        // Sort request data
        ksort($requestsValues);
        ksort($requestType);

        $requestsValues = array_values($requestsValues);
        $requestsLabels = array_values($requestType);

        // Generate graph colors (cyclically repeat colors if necessary)
        $requestsColors = [
            "#337ab7", "#00b386", "#f0ad4e", "#ff9966", "#5bc0de", "#777",
            "#aa2600", "#bf80ff", "#006238", "#6ddc9c", "#d1691f",
        ];
        while (count($requestsColors) < count($requestsLabels)) {
            $requestsColors = array_merge($requestsColors, $requestsColors);
        }
        $requestsColors = array_slice($requestsColors, 0, count($requestsLabels));

        $surveyLinks = [];
        $surveyLinks['hub/partials/announcements_modal.html.twig'] = $this->fetchSurveyLink($this->getPidsArray()['HOME'],1,"announcements") . "&modal=modal";
        $surveyLinks['hub/partials/deadlines_modal.html.twig'] = $this->fetchSurveyLink($this->getPidsArray()['HOME'],1,"deadlines") . "&modal=modal";

        //Check Anouncements
        $totalAnnouncements = 0;
        if($this->settings['home_number_announcements']  > 0){
            for ($i=1; $i < $this->settings['home_number_announcements'] + 1; $i++) {
                if(!empty($homepage['announce_text'.$i])){
                    $totalAnnouncements += 1;
                }
            }
        }


        return [
            'homepage' => $homepage,
            'requests' => $requests,
            'open_requests' => $openRequests,
            'homeMetrics' => $homeMetrics,
            'my_requests' => $myRequests,
            'my_concepts' => $myConcepts,
            'recent_activity' => $recentActivity,
            'deadlines' => $deadlines,
            'survey_links' => $surveyLinks,
            'requests_values' => array_values($requestsValues),
            'requests_labels' => array_values($requestType),
            'number_of_announcements' => $this->settings['home_number_announcements'],
            'total_announcements' => $totalAnnouncements,
            'current_user' => $this->currentUser,
            'request_type' => $requestType,
            'requests_colors' => $requestsColors,
            'number_of_quicklinks' => 10,
            'homepage_links_sectionorder' => $homepage_links_sectionorder,
            'number_of_open_data_calls' => fetchNumberOfOpenDataCalls($this->getPidsArray()['SOP'], $this->currentUser['person_region'])
        ];
    }

    private function prepareActivityData($module, $hubData, $pidsArray, $comment)
    {
        // Retrieve author data
        $people = \REDCap::getData($pidsArray['PEOPLE'], 'json-array', ['record_id' => $comment['response_person']], ['firstname', 'lastname'])[0];
        $name = trim($people['firstname'] . ' ' . $people['lastname']);

        // Retrieve request data
        $requestComment = \REDCap::getData($pidsArray['RMANAGER'], 'json-array', ['request_id' => $comment['request_id']])[0];
        $time = getDateForHumans($comment['responsecomplete_ts']);
        $title = substr($requestComment['request_title'], 0, 50) . '...';

        // Prepare activity data
        $activityData = [
            'time' => $time,
            'name' => $name,
            'title' => $title,
            'link' => $module->getUrl('index.php') . '&NOAUTH&option=hub&record=' . $requestComment['request_id'],
            'is_revision' => $comment['author_revision_y'] == '1',
            'has_comment' => !empty($comment['comments']),
            'has_vote' => isset($comment['pi_vote']) && $comment['pi_vote'] !== "",
            'has_file' => !empty($comment['revised_file'])
        ];

        return $activityData;
    }
}
?>

