<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

include_once(__DIR__ . "/../autoload.php");

use REDCap;

class DataRequestBuilder extends Model
{

    public function __construct($projectId, $module)
    {
        parent::__construct($module,$projectId);
    }

    /**
     * Create a new SOP record.
     *
     * @param array $sopData
     * @param string $concept_id
     * @param string $concept_title
     * @param string $sop_created_dt
     * @param string $selectConcept
     * @return array [$recordId, $arraySOP]
     */
    public function createNewDataRequest(array $sopData, string $concept_id, string $concept_title, string $sop_created_dt, ?string $selectConcept = "") : array
    {
        $Proj = new \Project($this->getPidsArray()['SOP']);
        $event_id = $Proj->firstEventId;
        $recordId = $this->module->addAutoNumberedRecord($this->getPidsArray()['SOP']);
        $sop_name = "{$recordId}. Data Request for {$concept_id}, {$concept_title}";

        // Initialize SOP data
        $arraySOP = [
            $recordId => [
                $event_id => array_merge($sopData, [
                    'sop_status' => "0", // DRAFT
                    'sop_visibility' => "1",
                    'sop_active' => "1",
                    'sop_name' => $sop_name,
                    'sop_created_dt' => $sop_created_dt,
                    'sop_updated_dt' => $sop_created_dt,
                ])
            ]
        ];

        // Update concept if provided
        if (!empty($selectConcept)) {
            $arraySOP[$recordId][$event_id]['sop_concept_id'] = $selectConcept;
        }

        return [$recordId, $arraySOP];
    }

    /**
     * Load template SOP data.
     *
     * @param int $recordId
     * @param string $concept_id
     * @param string $concept_title
     * @param string $sop_created_dt
     * @return array [$recordId, $arraySOP]
     */
    public function createDataRequestFromTemplate(int $recordId, string $concept_id, string $concept_title, string $sop_created_dt, int $hubUser) : array
    {
        $RecordSetSOP = \REDCap::getData([
            'project_id' => $this->getPidsArray()['SOP'],
            'return_format' => 'array',
            'records' => $recordId
        ]);

        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $this->getPidsArray()['SOP'])[0];

        return $this->createNewDataRequest(
            [
                'sop_hubuser' => $hubUser,
                'sop_concept_id' => $concept_id,
                'sop_tablefields' => $sop['sop_tablefields'],
                'sop_downloaders' => $sop['sop_downloaders'],
                'sop_inclusion' => $sop['sop_inclusion'],
                'sop_exclusion' => $sop['sop_exclusion'],
                'sop_notes' => $sop['sop_notes'],
                'sop_creator' => $sop['sop_creator'],
                'sop_creator2' => $sop['sop_creator2'],
                'sop_datacontact' => $sop['sop_datacontact'],
                'sop_extrapdf' => $sop['sop_extrapdf'],
                'sop_finalpdf' => $sop['sop_finalpdf'],
                'dataformat_prefer' => $sop['dataformat_prefer'],
                'dataformat_notes' => $sop['dataformat_notes']
            ],
            $concept_id,
            $concept_title,
            $sop_created_dt
        );
    }

    /**
     * Load draft SOP data.
     *
     * @param string $recordId
     * @param string $concept_id
     * @param string $concept_title
     * @param string $sop_created_dt
     * @param string $selectConcept
     * @return array SOP data array
     */
    public function loadDataRequestDraft(int $recordId, string $sop_created_dt) : array
    {
        $RecordSetSOP = \REDCap::getData([
            'project_id' => $this->getPidsArray()['SOP'],
            'return_format' => 'array',
            'records' => $recordId
        ]);
        $sop = ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $this->getPidsArray()['SOP'])[0];

        $concept = $this->getConceptData($this->getPidsArray()['HARMONIST'], (int)$sop['sop_concept_id']);

        if (!empty($sop['sop_concept_id'])) {
            return [
                $concept,
                $recordId => [
                    $this->getFirstEventId() => [
                        'sop_concept_id' => $sop['sop_concept_id'],
                        'sop_name' => "{$recordId}. Data Request for {$concept['sop_concept_id']}, {$concept['sop_concept_title']}",
                        'sop_updated_dt' => $sop_created_dt,
                        'sop_due_d' => $sop['sop_due_d']
                    ]
                ]
            ];
        }

        return [];
    }

    public function getSopData(int $record, int $eventId): array
    {
        // Fetch SOP data
        $RecordSetSOP = \REDCap::getData([
            'project_id'     => $this->getPidsArray()['SOP'],
            'return_format'  => 'array',
            'records'        => [$record],
            'filterType'     => 'RECORD'
        ]);
        $data = $this->replaceSymbolsForPDF(
            ProjectData::getProjectInfoArrayRepeatingInstruments($RecordSetSOP, $this->getPidsArray()['SOP'])[0] ?? []
        );

        // If response status does not exist for a region, create it
        $this->checkRegionDataResponseStatus($record, $eventId);

        // Fetch concept details from the HARMONIST project
        $concept = $this->getConceptData((int)$this->getPidsArray()['HARMONIST'], (int)$data['sop_concept_id']);
        $data['sop_concept_id']    = $concept['sop_concept_id'] ?? '';
        $data['sop_concept_title'] = $concept['sop_concept_title'] ?? '';

        // Fetch People Information (after $data is populated)
        $this->preloadPersonInfo([(int)($data['sop_creator'] ?? 0), (int)($data['sop_creator2'] ?? 0), (int)($data['sop_datacontact'] ?? 0)]);
        [$data['sop_creator_name'],     $data['sop_creator_email']]     = $this->getPersonInfo((int)($data['sop_creator'] ?? 0), 'sop_creator');
        [$data['sop_creator2_name'],    $data['sop_creator2_email']]    = $this->getPersonInfo((int)($data['sop_creator2'] ?? 0), 'sop_creator2');
        [$data['sop_datacontact_name'], $data['sop_datacontact_email']] = $this->getPersonInfo((int)($data['sop_datacontact'] ?? 0), 'sop_datacontact');

        return $data;
    }

    public function getConceptData(int $projectId, int $conceptId) : array
    {
        $data = [];
        if($conceptId !== '') {
            $RecordSetConcepts = \REDCap::getData([
                'project_id' => $projectId,
                'return_format' => 'array',
                'records' => $conceptId,
                'fields' => ['concept_id', 'concept_title']
            ]);

            $concepts = ProjectData::getProjectInfoArrayRepeatingInstruments(
                $RecordSetConcepts,
                $projectId
            )[0] ?? [];

            $data['sop_concept_id'] = $concepts['concept_id'] ?? '';
            $data['sop_concept_title'] = $concepts['concept_title'] ?? '';
            $data['selectConcept'] = $conceptId;
        }

        return $data;
    }

    /**
     * Get formatted data for STEP4.
     */
    public function getDataFormatText(array $data, int $projectId, HarmonistHubExternalModule $module) : string
    {
        $dataformat_prefer_text = '';
        $dataformat_prefer = $module->getChoiceLabels('dataformat_prefer', $projectId);
        if (!empty($data['dataformat_prefer'])) {
            //Old REDCap code
            foreach ($data['dataformat_prefer'] as $index => $dataf) {
                if($dataf == '1'){
                    $dataformat_prefer_text .= $dataformat_prefer[$index] . ', ';
                }
            }
        }else{
            foreach ($dataformat_prefer as $index => $dataf) {
                if (($data['dataformat_prefer___'.$index] ?? '') == '1') {
                    $dataformat_prefer_text .= $dataformat_prefer[$index] . ', ';
                }
            }
        }

        return rtrim($dataformat_prefer_text, ', ');
    }

    /**
     * Fetch person information (name, email) based on record ID.
     */
    /**
     * Internal cache for person lookups to avoid repeated queries.
     */
    private array $personCache = [];

    /**
     * Pre-fetch multiple people in a single query and store in cache.
     * Call this before multiple getPersonInfo() calls to batch them.
     *
     * @param array $recordIds Array of person record IDs to pre-fetch
     */
    public function preloadPersonInfo(array $recordIds): void
    {
        $ids = array_filter(array_unique(array_map('intval', $recordIds)), fn($id) => $id > 0 && !isset($this->personCache[$id]));
        if (empty($ids)) {
            return;
        }

        $people = \REDCap::getData([
            'project_id' => $this->getPidsArray()['PEOPLE'],
            'return_format' => 'json-array',
            'records' => array_values($ids),
            'fields' => ['record_id', 'firstname', 'lastname', 'email']
        ]);

        foreach ($people as $person) {
            $this->personCache[(int)$person['record_id']] = $person;
        }
    }

    /**
     * Fetch person information (name, email) based on record ID.
     * Uses internal cache — call preloadPersonInfo() first for batch efficiency.
     */
    public function getPersonInfo(?int $recordId, string $key) : array
    {
        if (empty($recordId)) {
            return [];
        }

        // Check cache first
        if (!isset($this->personCache[$recordId])) {
            // Fallback: single fetch if not preloaded
            $person = \REDCap::getData([
                'project_id' => $this->getPidsArray()['PEOPLE'],
                'return_format' => 'json-array',
                'records' => $recordId,
                'fields' => ['record_id', 'firstname', 'lastname', 'email']
            ])[0] ?? [];
            $this->personCache[$recordId] = $person;
        }

        $person = $this->personCache[$recordId];
        $name  = trim(($person['firstname'] ?? '') . ' ' . ($person['lastname'] ?? ''));
        $email = $person['email'] ?? '';

        return [$name, $email];
    }

    public function replaceSymbolsForPDF(mixed $sopData): mixed
    {
        // Map once; no need for nested loops
        $mapForArrayStrings = [
            '&ge;' => '&gt;=',
            '&le;' => '&lt;=',
        ];

        $mapForScalarStrings = [
            '>' => '&gt;',
            '<' => '&lt;',
        ];

        // Replace only text (skip checkboxes/complex structures)
        if (is_array($sopData)) {
            $changed = $sopData;

            foreach ($changed as $i => $value) {
                if (is_string($value)) {
                    $changed[$i] = strtr($value, $mapForArrayStrings);
                }
            }

            return $changed;
        }

        // Case scenario for Code Lists
        if (is_string($sopData)) {
            return strtr($sopData, $mapForScalarStrings);
        }

        return $sopData;
    }

    public function checkRegionDataResponseStatus($recordId, $eventId): void
    {
        $regions = \REDCap::getData([
            'project_id' => $this->getPidsArray()['SOP'],
            'return_format' => 'json-array'
        ]);

        foreach ($regions as $region) {
            $instance = $region['record_id'] ?? '';

            if (empty($sop["data_response_status"][$instance])) {
                $repeat = [
                    $recordId => [
                        'repeat_instances' => [
                            $eventId => [
                                'region_participation_status' => [
                                    $instance => [
                                        'data_response_status' => "0",
                                        'data_region' => $instance,
                                        'region_participation_status_complete' => "1",
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                $params = [
                    'project_id' => $this->getPidsArray()['SOP'],
                    'dataFormat' => 'array',
                    'data' => $repeat,
                    'overwriteBehavior' => "overwrite",
                    'dateFormat' => "YMD",
                    'type' => "flat"
                ];
                $results = \REDCap::saveData($params);
            }
        }
    }

    public function parseSopTablefields(string $s): array
    {
        $s = trim($s);
        if ($s === '') return [];

        $out = [];
        foreach (explode(',', $s) as $token) {
            $token = trim($token);
            if ($token === '') continue;

            // split only once: "30_7" => ["30","7"]
            [$recordId, $varIdx] = array_pad(explode('_', $token, 2), 2, null);
            if ($recordId === null || $varIdx === null || $recordId === '' || $varIdx === '') continue;

            $out[$recordId][] = $varIdx; // keep as string; cast to int if indexes are numeric
        }

        // dedupe per record
        foreach ($out as $rid => $list) {
            $out[$rid] = array_values(array_unique($list));
        }

        return $out;
    }

    public function toHTML(?string $v): string {
        return htmlspecialchars((string)$v ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * @return array{0: string, 1: string, 2?: string}
     */
    public function preparePdfHtml(HarmonistHubExternalModule $module, int $record, int $eventId, array $settings, bool $zipFile = false, bool $preview = false): array
    {
        #FETCH SOP DATA
        $data = [];
        if($record != null){
            $data = $this->getSopData($record, $eventId);
        }


        #TABLE DATA
        [$requestedTables, $tableHtml] = $this->prepareRequestedTables($module, $record, $eventId, $data, $preview, $preview ? true : $zipFile);

        #FIRST PAGE
        $first_page = $this->pdfFirstPage($settings, $data, $preview);

        #SECOND PAGE
        $second_page = $this->pdfSecondPage($module, (int)$this->getPidsArray()['SOP'], $data, $preview);

        $second_page .= "<p><span style='font-size: 12pt'>".$requestedTables."</span></p>";

        $pdfHtmlStart = '';
        $pdfHtmlEnd = '';
        $conceptId = $data['sop_concept_id'] ?? '';
        if(!$preview){
            $page_num = '<style>.footer .page-number:after { content: counter(page); } .footer { position: fixed; bottom: 0px;color:grey }a{text-decoration: none;}</style>';
            $pdfHtmlStart = "<html>"
                ."<body style='font-family:\"Calibri\";font-size:10pt;'>".$page_num
                ."<div class='mainPDF'><span left: 0px;>".$conceptId."</span></div>"
                ."<div class='footer' style='left: 600px;'><span class='page-number'>Page </span></div>";
            $pdfHtmlEnd = "</body></html>";
        }

        $img = getFile($module, $settings['hub_logo_pdf'],$preview ? 'src' : 'pdf');

        $filename = $conceptId . "_DataRequest_" . date("Y-m-d_hi", time());

        $pdfClass = $preview ? '' : 'mainPDF';


        $pdfClassSafe = $this->safeClassList($pdfClass, [
            'pdf-preview', 'pdf-container', 'my-pdf', 'mb-3', 'mt-3', 'mainPDF', 'preview'
        ]);

        $html = $pdfHtmlStart
            . "<div class='{$pdfClassSafe}' style='text-align:center;width:100%;'><img src='".$img."' style='width:200px;padding-bottom: 30px;' alt='Logo'></div>"
            . "<div class='{$pdfClassSafe}'  style='text-align:center;width:100%;' id='page_html_style'>".$first_page."<div style='page-break-before: always;'></div></div>"
            . "<div class='{$pdfClassSafe}'>".$second_page."<div style='page-break-before: always;'></div></div>"
            . "<p><span style='font-size:16pt'><strong>6. Requested DES Tables</strong></span></p>"
            . $tableHtml
            . $pdfHtmlEnd;

        #HTML PURIFIER
        $cleanHtml = $this->purifyHTML($html);

        return $zipFile
            ? [$filename, $cleanHtml, $data['sop_finalpdf'] ?? '']
            : [$filename, $cleanHtml];

    }

    public function prepareRequestedTables(HarmonistHubExternalModule $module, int $record, int $eventId, array $data, bool $preview, bool $zipFile): array
    {
        // Build requested tables
        $dataTable = generateTableArray($module, $this->getPidsArray()['DATAMODEL']);
        $sopTablefieldsRaw = (string)($data['sop_tablefields'] ?? '');

        if (!empty($dataTable) && $sopTablefieldsRaw !== '' && !$preview) {
            // HTML outputs (use the raw string exactly as before)
            $tableHtml = generateTablesHTML_pdf($module, $this->getPidsArray(), $dataTable, $sopTablefieldsRaw);
            $requestedTables = generateRequestedTablesList_pdf($dataTable, $sopTablefieldsRaw);

            if(!$zipFile) {
                // Efficient selection lookup
                $selectedByRecord = $this->parseSopTablefields($sopTablefieldsRaw);

                foreach ($dataTable as $row) {
                    $rid = (string)($row['record_id'] ?? '');
                    if ($rid === '' || empty($selectedByRecord[$rid])) {
                        continue;
                    }

                    $tableName = $row['table_name'] ?? '';
                    if ($tableName === '') {
                        continue;
                    }

                    foreach ($selectedByRecord[$rid] as $varIdx) {
                        if (isset($row['variable_name'][$varIdx])) {
                            $jsonTableFields[$tableName][] = $row['variable_name'][$varIdx];
                        }
                    }
                }

                // Deduplicate variable lists per table
                foreach ($jsonTableFields as $name => $vars) {
                    $jsonTableFields[$name] = array_values(array_unique($vars));
                }

                $this->updateJSONAndFollowActivity($record, $eventId, $data, $jsonTableFields);
            }
        }else if($preview) {
            $tableHtml = generateTablesHTML_steps($this->getPidsArray(), $dataTable);
            $requestedTables = generateRequestedTablesList($dataTable);
        }

        return [$requestedTables ?? "",$tableHtml ?? ""];
    }

    public function updateJSONAndFollowActivity(int $record, int $eventId, array $data, array $jsonTableFields): void
    {

        // Update SOP fields (timestamp + shiny_json)
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $update = [
            $record => [
                $eventId => [
                    'sop_updated_dt' => $now,
                ]
            ]
        ];

        if (!empty($jsonTableFields)) {
            $update[$record][$eventId]['shiny_json'] = json_encode($jsonTableFields);
        }

        $followActivity = (string)($data['follow_activity'] ?? '');
        $arrayUserId = array_filter(array_map('trim', explode(',', $followActivity)));

        $arrayUserId[] = $currentUser['record_id'] ?? null;
        $arrayUserId[] = $data['sop_creator'] ?? null;
        $arrayUserId[] = $data['sop_creator2'] ?? null;
        $arrayUserId[] = $data['sop_datacontact'] ?? null;

        $arrayUserId = array_values(array_filter(array_unique($arrayUserId), fn($v) => $v !== null && $v !== ''));
        $update[$record][$eventId]['follow_activity'] = implode(",", $arrayUserId);

        // Save SOP updates
        $params = [
            'project_id' => $this->getPidsArray()['SOP'],
            'dataFormat' => 'array',
            'data' => $update,
            'overwriteBehavior' => "overwrite",
            'dateFormat' => "YMD",
            'type' => "flat"
        ];
        $results = \REDCap::saveData($params);
        \Records::addRecordToRecordListCache($this->getPidsArray()['SOP'], $record, 1);
    }

    public function pdfFirstPage(array $settings, array $data, bool $preview): string
    {
        $sop_due_d = '';
        if (!empty($data['sop_due_d'] ?? '')) {
            $sop_due_d = (new \DateTime($data['sop_due_d']))->format('d F Y');
        }

        $out = "<tr><td align='center'>";
        $out .= $this->sectionTitleBlock(($settings['hub_name_long'] ?? '') . " (" . ($settings['hub_name'] ?? '') . ")", 'hub_name', 16);
        $out .= $this->sectionTitleBlock("DATA TRANSFER REQUEST – ".($data['sop_concept_id'] ?? ''), 'sop_data_transfer_request', 16);
        $out .= "<br/><br/>";
        $out .= $this->sectionTitleBlock(($data['sop_concept_title'] ?? ''), 'sop_concept_title', 16);
        $out .= "<br/><br/>";
        $out .= $this->sectionTitleBlock("<span style='color:#449d44'>Data Due: ".$sop_due_d."</span>", 'sop_due_d_preview', 14);
        $out .= "<br/>";
        if(!$preview && ($data['sop_creator_name'] == '' && $data['sop_creator2'] == '')) {
            //If we don't have contacts, don't add them to the PDF
        }else{
            $researchContacts = array_values(array_filter([
                [
                    'var'   => 'sop_creator',
                    'name'  => $data['sop_creator_name'] ?? '',
                    'email' => $data['sop_creator_email'] ?? '',
                    'org' => $data['sop_creator_org'] ?? '',
                ],
                $this->isSelectedPerson($data['sop_creator2'] ?? null, $preview)
                    ? [
                    'var'   => 'sop_creator2',
                    'name'  => $data['sop_creator2_name'] ?? '',
                    'email' => $data['sop_creator2_email'] ?? '',
                    'org' => $data['sop_creator2_org'] ?? '',
                ]
                    : null,
            ], fn($p) => $p !== null));

            $out .= $this->personBlock( 'Research Contact(s)', $researchContacts, 'resarch_contacts_title');
        }

        if ($this->isSelectedPerson($data['sop_datacontact'] ?? null, $preview)) {
            $out .= $this->personBlock('Data Contact', [[
                'var'   => 'sop_datacontact',
                'name'  => $data['sop_datacontact_name'] ?? '',
                'email' => $data['sop_datacontact_email'] ?? '',
                'org' => $data['sop_datacontact_og'] ?? '',
            ]], 'data_contacts_title');
        }
        $out .= "<span style='font-size: 12pt'>";
        $out .= "<br/><br/><p><span style='font-size: 11pt;color:#999;'>Data Request Version: ".date('d F Y')."</span></p><br/>";
        $out .= "</span></td></tr></table>";

        return $out;
    }

    public function pdfSecondPage(HarmonistHubExternalModule $module, int $project_id, ?array $data, bool $preview = false): string
    {
        $introText = "This document provides guidance on the preparation of data files for the transfer of data for the IeDEA Concept ";

        $intro = $preview
            ? $introText . '<span class="content" preview="sop_concept_id"></span>: <strong><span class="content" preview="sop_concept_title"></span></strong>.'
            : $introText . ($data['sop_concept_id'] ?? '') . ': <strong>' . ($data['sop_concept_title'] ?? '') . '</strong>.';

        $out  = $this->sectionBlockNumbered("1. Introduction", 'introduction', $data ?? [], $preview, $intro);
        $out .= $this->sectionBlockNumbered("2. Inclusion Criteria", 'sop_inclusion', $data ?? [], $preview);
        $out .= $this->sectionBlockNumbered("3. Exclusion Criteria", 'sop_exclusion', $data ?? [], $preview);
        $out .= $this->sectionBlockNumbered("4. Data Submission Notes", '', [], $preview);

        $data['dataformat_prefer_text'] = $this->getDataFormatText($data, $project_id, $module);
        $out .= $this->sectionBlock("Preferred file format", 'dataformat_prefer_text', $data ?? [], $preview);
        $out .= $this->sectionBlock("File format notes", 'dataformat_notes', $data ?? [], $preview);
        $out .= $this->sectionBlock("General notes", 'sop_notes', $data ?? [], $preview);

        return $out;
    }

    public function isSelectedPerson(?string $value, bool $preview = false): bool {
        if($preview){
            return true;
        }

        $v = trim((string)$value);
        return $v !== '' && strcasecmp($v, 'Select Name') !== 0;
    }

    public function personBlock(string $title, array $persons, string $titleVar): string
    {
        $persons = array_values(array_filter($persons, function ($p) {
            if (!is_array($p)) return false;
            return trim((string)($p['var'] ?? '')) !== '' || trim((string)($p['name'] ?? '')) !== '' || trim((string)($p['email'] ?? '')) !== '' || trim((string)($p['org'] ?? '')) !== '';
        }));

        if (empty($persons)) return '';

        $out = "<p><span style='border-bottom: 1px solid;font-weight: bold;font-size: 14pt' preview='{$titleVar}'>"
            . $this->toHTML($title)
            . "</span>";

        foreach ($persons as $p) {
            $name  = (string)($p['name'] ?? '');
            $email = (string)($p['email'] ?? '');
            $org = (string)($p['org'] ?? '');
            $pv    = (string)($p['var'] ?? 'person');

            if (trim($pv) !== '') {
                $out .= "<div preview='{$pv}_name'>" . $this->toHTML($name) . "</div>";
                $out .= "<div preview='{$pv}_org'>" . $this->toHTML($org) . "</div>";
                $out .= "<div><a id='{$pv}_email' href='mailto:" . $this->toHTML($email) . "' style='text-decoration:none'><span preview='{$pv}_email'>"
                    . $this->toHTML($email)
                    . "</span></a></div><br/>";
            }
        }

        return $out;
    }

    public function sectionTitleBlock(string $data, int $id, string $fontSize): string
    {
        $out = "";

        $out .= "<p><span style='font-size: " . $fontSize . "pt'><strong><span preview='" . $id . "'>" . $data . "</span></strong></span></p>";

        return $out;
    }

    public function sectionBlockNumbered(string $title, string $varName, array $data, bool $preview, ?string $subtitleOverride = null): string
    {
        if ($subtitleOverride !== null) {
            $subtitle = $preview
                ? $subtitleOverride // contains preview spans already
                : $subtitleOverride;
        } else {
            $subtitle = $preview
                ? "<span class=\"content\" preview=\"{$varName}\"></span>"
                : (string)($data[$varName] ?? '');
        }

        return sprintf(
            '<p><span style="font-size: 16pt"><strong>%s</strong></span></p>
                    <p><span style="font-size: 12pt">%s</span></p>',
            $title,
            $subtitle
        );
    }

    public function sectionBlock(string $title, string $varName, array $data, bool $preview): string
    {
        $out = "";

        $subtitle = $preview
            ? "<span class=\"content\" preview=\"{$varName}\"></span>"
            : (string)($data[$varName] ?? '');

        if (trim(($subtitle ?? '')) !== '') {
            $out .= "<p><span style='font-size: 12pt'><strong>" . $title . ":&nbsp;</strong></p><p>" . $subtitle ?? '' . "</span></p>";
        }

        return $out;
    }

    public static function purifyHTML(string $html): string{
        ini_set('sys_temp_dir', EDOC_PATH);
        putenv('TMPDIR=' . EDOC_PATH);

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', EDOC_PATH);

        // Allow HTML you actually output
        $config->set('HTML.Allowed', implode(',', [
            'html','body','style',
            'div[class|style|align|preview|record_id|parent_table_header|id]',
            'span[class|style|align|preview|record_id|id]',
            'p[class|style|align]','br',
            'b','strong','i','em','u',
            'ul','ol',
            'li[style|record_id]',
            'a[href|title|target|rel|class|style|name]',
            'img[src|alt|style|width|height]',
            'table[class|style|parent_table|id]',
            'thead','tbody','tfoot',
            'tr[style|record_id|parent_table_header]',
            'th[class|style|align]','td[class|style|align]',
        ]));

        // Allow CSS properties
        $config->set('CSS.AllowedProperties', [
            'width','height',
            'padding','padding-bottom',
            'border','border-width','border-style','border-color',
            'border-collapse',
            'display',
            'background-color',
            'text-decoration',
            'font-family','font-size','font-weight','font-style','color',
            'text-align',
        ]);

        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'data' => true,   // needed for data:image/... base64 logo
        ]);

        $config->set('CSS.AllowTricky', true);
        $config->set('CSS.Trusted', true); // needed for many rule-based declarations in style blocks

        // Extend definition (this finalizes config)
        // Allow your custom attribute
        $def = $config->getHTMLDefinition(true);
        $def->addAttribute('span', 'preview', 'Text');
        $def->addAttribute('div', 'preview', 'Text');
        $def->addAttribute('div', 'record_id', 'Text');
        $def->addAttribute('div', 'parent_table', 'Text');
        $def->addAttribute('div', 'parent_table_header', 'Text');
        $def->addAttribute('tr',  'record_id', 'Text');
        $def->addAttribute('tr',  'parent_table_header', 'Text');
        $def->addAttribute('span','record_id', 'Text');
        $def->addAttribute('table', 'parent_table', 'Text');
        $def->addAttribute('table', 'id', 'Text');
        $def->addAttribute('li', 'record_id', 'Text');

        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }

    public function safeClassList(string $classes, array $allowed): string {
        $out = [];
        foreach (preg_split('/\s+/', trim($classes)) as $c) {
            if ($c !== '' && in_array($c, $allowed, true)) $out[] = $c;
        }
        return implode(' ', $out);
    }

    /**
     * Get the first event ID for the SOP project.
     *
     * @return int
     */
    private function getFirstEventId() : int
    {
        $Proj = new \Project($this->getPidsArray()['SOP']);
        return $Proj->firstEventId;
    }
}
?>

