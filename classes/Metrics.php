<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use REDCap;

class Metrics extends Model
{
    protected $conceptsData = [];
    const CONCEPT_TYPE = [1 => 'manuscripts', 2 => 'abstracts'];

    public function __construct(HarmonistHubPublicExternalModule $module, $projectId, $defaultValues)
    {
        parent::__construct($module, $projectId, $defaultValues);
    }

    public function getConceptsByStatusDonutData()
    {
        // Count concepts
        $active_concepts = $this->getActiveConcepts();
        $inactive_complete_concepts = $this->getInactiveCompleteConcepts();
        $inactive_discontinued_concepts = $this->getInactiveDiscontinuedConcepts();

        // Return the data as an array
        return [
            'labels' => [0 => "Active", 1 => "Inactive\nComplete", 2 => "Inactive\nDiscontinued"],
            'values' => [0 => $active_concepts, 1 => $inactive_complete_concepts, 2 => $inactive_discontinued_concepts],
            'colors' => [0 => "#1ad1ff", 1 => "#5cb85c", 2 => "#f0ad4e"]
        ];
    }

    public function getConceptsByWorkingGroupDonutData()
    {
        // Get working group links
        $wg_link = $this->getWGLink();

        // Fetch all concepts
        $concepts = $this->getConcepts();

        // Get concept counts by working group
        $array_wg = $this->getArrayWG($concepts, $wg_link);

        // Get WG short labels
        $wg_short_labels = $this->getWGShortLabels($wg_link);
        $conceptswg_short_label = $wg_short_labels['labels'];
        $conceptswg_short_label_index = $wg_short_labels['index'];

        // Generate WG values, colors, and labels
        $wg_data = $this->getConceptsWGData($wg_link, $concepts);

        // Return the data as an array
        return [
            'labels' => $wg_data['labels'],
            'values' => $wg_data['values'],
            'colors' => $wg_data['colors'],
            'shortLabel' => $conceptswg_short_label,
            'shortLabelIndex' => $conceptswg_short_label_index,
        ];
    }

    public function getRequestsDonutData()
    {
        // Fetch data from REDCap for the Resource Manager project
        $RecordSetRM = \REDCap::getData([
                                            'project_id' => $this->getPidsArray()['RMANAGER'],
                                            'return_format' => 'array',
                                            'filterLogic' => "[approval_y] = '1'"
                                        ]);

        // Extract and sort request data based on the 'due_d' column
        $request = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetRM, $this->getPidsArray()['RMANAGER']);
        ArrayFunctions::array_sort_by_column($request, 'due_d');

        // Retrieve labels for the 'request_type' field
        $requests_labels = $this->module->getChoiceLabels('request_type', $this->getPidsArray()['RMANAGER']);

        // Count occurrences of each 'request_type'
        $requests_values = array_count_values(array_column($request, 'request_type'));

        // Add missing keys from labels to ensure all labels are represented
        foreach ($requests_labels as $keyLabel => $requestsLabel) {
            if (!isset($requests_values[$keyLabel])) {
                $requests_values[$keyLabel] = 0;
            }
        }

        // Remove hidden choices from labels and values
        $hidden_choices = $this->defaultValues->getHideChoice($this->getPidsArray()['RMANAGER'])[$this->getPidsArray()['RMANAGER']]['request_type'];
        foreach ($hidden_choices as $value) {
            if (isset($requests_labels[$value])) {
                unset($requests_labels[$value]);
            }
            if (isset($requests_values[$value])) {
                unset($requests_values[$value]);
            }
        }

        // Sort keys and prepare values and labels for output
        ksort($requests_values);
        ksort($requests_labels);

        $requests_values = array_values($requests_values);
        $requests_labels = array_values($requests_labels);

        // Define colors for requests
        $requests_colors = [
            0 => "#337ab7",
            1 => "#00b386",
            2 => "#f0ad4e",
            3 => "#ff9966",
            4 => "#5bc0de",
            5 => "#777"
        ];

        // Return the data as an array
        return [
            'labels' => $requests_labels,
            'values' => $requests_values,
            'colors' => $requests_colors
        ];
    }

    public function getConceptsData(): array
    {
        if (empty($this->conceptsData)) {
            // Fetch concepts data
            $RecordSetConcepts = \REDCap::getData($this->getPidsArray()['HARMONIST'], 'array', null);
            $this->conceptsData = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetConcepts, $this->getPidsArray()['HARMONIST'], '');
        }
        return $this->conceptsData;
    }

    /**
     * Generate concept counts, colors, and labels for working groups.
     */
    public function getConceptsWGData($wg_link, $concepts)
    {
        // Initialize the working group counts
        $array_wg = array_fill_keys(array_values($wg_link), 0); // Fill with all WG names as keys
        $array_wg['No WG'] = 0; // Explicitly include "No WG"

        // Calculate counts for each working group
        foreach ($concepts as $concept) {
            // Check if the `wg_link` is valid
            if (isset($wg_link[$concept['wg_link']])) {
                $wg_name = $wg_link[$concept['wg_link']];
                $array_wg[$wg_name]++;
            } else {
                // Count as "No WG" if wg_link is empty or invalid
                $array_wg['No WG']++;
            }
        }

        // Generate values, labels, and colors
        $conceptswg_labels = array_keys($array_wg);   // Labels are the keys
        $conceptswg_values = array_values($array_wg); // Values are the counts
        $conceptswg_colors = $this->generateColors(count($array_wg)); // Generate colors dynamically

        return [
            'values' => $conceptswg_values,
            'colors' => $conceptswg_colors,
            'labels' => $conceptswg_labels
        ];
    }

    /**
     * Generate colors for working groups based on their count.
     */
    private function generateColors($count)
    {
        $colors = [];
        $initial_hsl = 75; // Starting HSL percentage
        $final_color = '#8c8c8c'; // Final fallback color
        $color_step = ($count > 1) ? ($initial_hsl / ($count - 1)) : 0; // Calculate step dynamically

        for ($i = 0; $i < $count; $i++) {
            if ($i < $count - 1) { // Ensure the last entry gets the fallback color
                $colors[] = "hsl(210,50%," . max(0, $initial_hsl) . "%)"; // Limit HSL to 0 or above
                $initial_hsl -= $color_step;
            } else {
                $colors[] = $final_color; // Final color for the last entry
            }
        }

        return $colors;
    }

    function getRegionalAndMR($pidExtraOutputs, $type, $regionalMRData, $startYear, $outputType, $dataCheck) {
        $currentYear = date("Y");
        $regionalMRData = $this->initializeRegionalMRData($regionalMRData);
        $conceptOutputsByYear = [];
        $yearsLabelRegionalPubs = [];

        if (!empty($startYear)) {
            for ($year = $startYear; $year <= $currentYear; $year++) {
                $yearsLabelRegionalPubs[] = $year;

                // Fetch data once and reuse
                $extraOutputsSingleRegion = $this->getProjectData($pidExtraOutputs, $year, $outputType, '1');
                $extraOutputsMultipleRegion = $this->getProjectData($pidExtraOutputs, $year, $outputType, '2');
                $allExtraOutputs = $this->getProjectData($pidExtraOutputs, $year, $outputType);

                // Update counts for single and multiple region outputs
                $regionalMRData['r'][] = count($extraOutputsSingleRegion);
                $regionalMRData['mrw'][] = count($extraOutputsMultipleRegion);
                $regionalMRData['outputsAll'][] = count($allExtraOutputs);

                $regionalMRData['mr'][$year] = 0;

                // Process concept outputs if enabled
                if ($dataCheck[0] == "1") {
                    $this->processConceptOutputs($this->getConceptsData(), $year, $type, $regionalMRData, $conceptOutputsByYear);
                }

                // Process extra outputs if enabled
                if ($dataCheck[1] == "1") {
                    $this->processExtraOutputs($extraOutputsSingleRegion, $year, $type, $conceptOutputsByYear);
                }

                // Process multiple region outputs
                foreach ($extraOutputsMultipleRegion as $output) {
                    $venue = trim($output['output_venue']);
                    $this->incrementVenueCount($conceptOutputsByYear, $year, $venue);
                }

                // Ensure 'None' exists if no outputs for the year
                if (!isset($conceptOutputsByYear[$year])) {
                    $conceptOutputsByYear[$year]['None'] = 0;
                }
            }

            // Sort outputs by year in descending order
            krsort($conceptOutputsByYear);

            // Prepare final data structure
            $regionalMRData['mr'] = array_values($regionalMRData['mr']);
            $regionalMRData['outputs'] = $conceptOutputsByYear;
            $regionalMRData['years'] = $yearsLabelRegionalPubs;
        }

        return $regionalMRData;
    }

    /**
     * Generate RMR table data for a specific type (manuscripts or abstracts).
     */
    public function getDataRMRTable($conceptOutputsByYear)
    {
        $tableContent = "";
        $totalOutputs = 0;
        $venueTotals = [];

        foreach ($conceptOutputsByYear as $year => $venues) {
            $tableContent .= "<tr><td style='text-align: center'>{$year}</td>";
            $yearTotalOutputs = 0;
            $venueDetails = "";

            // Sort venues by total outputs in descending order
            arsort($venues);

            foreach ($venues as $venue => $total) {
                $yearTotalOutputs += $total;

                if ($venue === "None") {
                    $venueDetails = "<i>None</i>";
                } elseif ($total > 0) {
                    $venueDetails .= "{$venue} ({$total}), ";

                    // Update venue totals
                    if (!isset($venueTotals[$venue])) {
                        $venueTotals[$venue] = 0;
                    }
                    $venueTotals[$venue] += $total;
                }

                $totalOutputs += $total;
            }

            // Add year total and venue details to table row
            $tableContent .= "<td style='text-align: center'>{$yearTotalOutputs}</td><td>" . rtrim($venueDetails, ', ') . "</td></tr>";
        }

        // Sort total venues by count in descending order
        arsort($venueTotals);

        // Generate venue summary list
        $venueSummary = "";
        foreach ($venueTotals as $venue => $total) {
            if ($total > 0) {
                $venueSummary .= "{$venue} ({$total}), ";
            }
        }

        // Add final summary row to the table
        $tableContent .= "<tr class='bg-info'>
                            <td style='text-align: center'>Total</td>
                            <td style='text-align: center'>{$totalOutputs}</td>
                            <td>" . rtrim($venueSummary, ", ") . "</td>
                          </tr>";

        // Return the data as an array
        return [
            'content' => $tableContent,
            'total' => $totalOutputs
        ];
    }

    /**
     * Initialize the regional MR data structure.
     */
    function initializeRegionalMRData($regionalMRData) {
        return [
            'r' => [],
            'mr' => [],
            'mrw' => [],
            'outputsAll' => [],
            'outputs' => [],
            'years' => []
        ];
    }

    /**
     * Fetch project data for the given parameters.
     */
    function getProjectData($pid, $year, $outputType, $region = null) {
        $regionCondition = $region ? "AND [producedby_region] = '$region'" : "";
        $recordSet = \REDCap::getData($pid, 'array', null, null, null, null, false, false, false, "[output_year] = '$year' AND [output_type] = '$outputType' $regionCondition");
        return ProjectData::getProjectInfoArrayRepeatingInstruments($recordSet, $pid);
    }

    /**
     * Process concept outputs and update counts.
     */
    function processConceptOutputs($conceptsData, $year, $type, &$regionalMRData, &$conceptOutputsByYear) {
        foreach ($conceptsData as $concept) {
            if (is_array($concept['output_year'])) {
                foreach ($concept['output_year'] as $index => $outputYear) {
                    if ($outputYear == $year) {
                        $outputType = $concept['output_type'][$index] ?? '';
                        if ($this->shouldCountOutput($outputType, $type)) {
                            $regionalMRData['mr'][$year] += 1;

                            $venue = trim($concept['output_venue'][$index] ?? '');
                            $this->incrementVenueCount($conceptOutputsByYear, $year, $venue);
                        }
                    }
                }
            }
        }
    }

    /**
     * Process extra outputs and update counts.
     */
    function processExtraOutputs($extraOutputs, $year, $type, &$conceptOutputsByYear) {
        foreach ($extraOutputs as $output) {
            if ($output['output_year'] == $year) {
                $venue = trim($output['output_venue']);
                $this->incrementVenueCount($conceptOutputsByYear, $year, $venue);
            }
        }
    }

    /**
     * Increment the venue count for a given year and venue.
     */
    function incrementVenueCount(&$conceptOutputsByYear, $year, $venue) {
        if ($venue === "") {
            $venue = "<em>Unknown</em>";
        }

        if (!isset($conceptOutputsByYear[$year])) {
            $conceptOutputsByYear[$year] = [];
        }

        if (!isset($conceptOutputsByYear[$year][$venue])) {
            $conceptOutputsByYear[$year][$venue] = 0;
        }

        $conceptOutputsByYear[$year][$venue] += 1;
    }

    /**
     * Check if the output type should be counted based on the given type.
     */
    function shouldCountOutput($outputType, $type) {
        return ($outputType === '' || $outputType === '1') && $type === 'manuscripts'
            || $outputType === '2' && $type === 'abstracts';
    }

    /**
     * Generate concept year labels and initialize data structures.
     */
    public function initializeConceptYears($oldestYear)
    {
        $currentYear = date("Y");
        $years_label_concepts = [];
        $concept_years = [];

        for ($year = $oldestYear; $year <= $currentYear; $year++) {
            $years_label_concepts[] = $year;
            $concept_years[$year] = [
                'concepts' => 0,
                'abstracts' => 0,
                'manuscripts' => 0,
                'mrdatarequests' => 0
            ];
        }

        return [
            'labels' => $years_label_concepts,
            'data' => $concept_years
        ];
    }

    /**
     * Populate concept data by year.
     */
    public function populateConceptYears($concept_years)
    {
        foreach ($this->getConceptsData() as $concept) {
            foreach ($concept_years as $year => &$data) {
                // Count concepts by start year
                if ($concept['start_year'] == $year) {
                    $data['concepts']++;
                }

                // Count abstracts and manuscripts by output year
                if (is_array($concept['output_year'])) {
                    foreach ($concept['output_year'] as $index => $output) {
                        if ($output == $year) {
                            if ($concept['output_type'][$index] == '' || $concept['output_type'][$index] == '1') {
                                $data['manuscripts']++;
                            } elseif ($concept['output_type'][$index] == '2') {
                                $data['abstracts']++;
                            }
                        }
                    }
                }
            }
        }

        return $concept_years;
    }

    /**
     * Populate MR data requests by year.
     */
    public function populateMRDataRequests($concept_years)
    {
        $RecordSetSOP = \REDCap::getData([
                                             'project_id' => $this->getPidsArray()['SOP'],
                                             'return_format' => 'array',
                                             'filterLogic' => "[sop_final_d] <> ''"
                                         ]);

        $sopData = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $this->getPidsArray()['SOP']);

        foreach ($sopData as $sop) {
            $sop_year = date("Y", strtotime($sop['sop_final_d']));
            if (isset($concept_years[$sop_year])) {
                $concept_years[$sop_year]['mrdatarequests']++;
            }
        }

        return $concept_years;
    }

    /**
     * Extract data arrays for visualization (concepts, manuscripts, abstracts, MR data requests).
     */
    public function extractVisualizationData($concept_years)
    {
        $iedea_concepts = [];
        $iedea_manuscripts = [];
        $iedea_abstracts = [];
        $iedea_mrdatarequests = [];

        foreach ($concept_years as $year => $data) {
            $iedea_concepts[] = $data['concepts'];
            $iedea_manuscripts[] = $data['manuscripts'];
            $iedea_abstracts[] = $data['abstracts'];
            $iedea_mrdatarequests[] = $data['mrdatarequests'];
        }

        return [
            'concepts' => $iedea_concepts,
            'manuscripts' => $iedea_manuscripts,
            'abstracts' => $iedea_abstracts,
            'mrdatarequests' => $iedea_mrdatarequests
        ];
    }

    /**
     * Get working group links from GROUP project.
     */
    private function getWGLink()
    {
        $wgArray = \REDCap::getData([
                                        'project_id' => $this->getPidsArray()['GROUP'],
                                        'return_format' => 'json-array',
                                        'fields' => ['record_id', 'group_name', 'group_abbr']
                                    ]);

        $wgLinks = [];
        foreach ($wgArray as $wg) {
            $wgLinks[$wg['record_id']] = $wg['group_name'] . ' (' . $wg['group_abbr'] . ')';
        }

        return $wgLinks;
    }

    /**
     * Fetch concepts from HARMONIST project.
     */
    private function getConcepts($filterLogic = null)
    {
        $params = [
            'project_id' => $this->getPidsArray()['HARMONIST'],
            'return_format' => 'array'
        ];

        if ($filterLogic) {
            $params['filterLogic'] = $filterLogic;
        }

        $recordSet = \REDCap::getData($params);
        return ProjectData::getProjectInfoArrayRepeatingInstruments($recordSet, $this->getPidsArray()['HARMONIST']);
    }

    /**
     * Count active concepts.
     */
    private function getActiveConcepts()
    {
        $filterLogic = "[active_y]='Y'";
        return count($this->getConcepts($filterLogic));
    }

    /**
     * Count inactive complete concepts.
     */
    private function getInactiveCompleteConcepts()
    {
        $filterLogic = "[active_y] = 'N' AND [concept_outcome] = '1'";
        return count($this->getConcepts($filterLogic));
    }

    /**
     * Count inactive discontinued concepts.
     */
    private function getInactiveDiscontinuedConcepts()
    {
        $filterLogic = "[active_y] = 'N' AND [concept_outcome] = '2'";
        return count($this->getConcepts($filterLogic));
    }

    /**
     * Get concept counts by working group.
     */
    private function getArrayWG($concepts, $wg_link)
    {
        $array_wg = array_fill_keys(array_keys($wg_link), 0);
        $other = 0;

        foreach ($concepts as $concept) {
            $wg = $concept['wg_link'] ?? '';
            if ($wg === '') {
                $other++;
            } elseif (isset($wg_link[$wg])) {
                $array_wg[$wg_link[$wg]]++;
            }
        }

        $array_wg['No WG'] = $other;
        return $array_wg;
    }

    /**
     * Generate short labels for working groups.
     */
    private function getWGShortLabels($wg_link)
    {
        $conceptswg_short_label = [];
        $conceptswg_short_label_index = [];

        foreach ($wg_link as $code => $text) {
            preg_match('#\((.*?)\)#', $text, $match);
            $conceptswg_short_label[] = $match[1] ?? 'Unknown';
            $conceptswg_short_label_index[$code] = $match[1] ?? 'Unknown';
        }

        $conceptswg_short_label_index[''] = 'No WG';
        $conceptswg_short_label[] = 'No WG';

        return ['labels' => $conceptswg_short_label, 'index' => $conceptswg_short_label_index];
    }
}
?>
