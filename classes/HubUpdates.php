<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

use Form;
use MetaData;
use REDCap;

include_once(__DIR__ . "/REDCapManagement.php");
include_once(__DIR__ . "/../simplediff-modified/simplediff.php");

class HubUpdates
{
    const CHANGED = 'changed';
    const ADDED = 'added';
    const REMOVED = 'removed';
    const VARIABLE_CRITICALITY_CRITICAL = "critical";
    const VARIABLE_CRITICALITY_MEDIUM = "medium";
    const VARIABLE_CRITICALITY_LOW = "low";
    const VARIABLE_CRITICALITY_TOTAL = "TOTAL";
    const VARIABLE_CRITICALITY_CRITICAL_ICON_EMAIL = "!";
    const VARIABLE_CRITICALITY_MEDIUM_ICON_EMAIL = "=";
    const VARIABLE_CRITICALITY_LOW_ICON_EMAIL = "&#9660;";

    public static function compareDataDictionary($module, $pidsArray, $option = ''): array
    {
        $allItems = [];
        $constants_array = REDCapManagement::getProjectsConstantsArray();
        $projects_array_sql = REDCapManagement::getProjectsSQLFieldsArray($pidsArray);
        foreach ($constants_array as $constant) {
            $path = $module->getModulePath() . "csv/" . $constant . ".csv";
            $old = REDCap::getDataDictionary($pidsArray[$constant], 'array', false);
            $new = $module->dataDictionaryCSVToMetadataArray($path);
            $possiblyChanged = [];
            $removed = [];
            $added = [];
            $changed = [];

            if (is_array($old) && is_array($new)) {
                $removed = array_diff_key($old, $new);
                $added = array_diff_key($new, $old);
                $possiblyChanged = array_intersect_key($new, $old);
            }

            if (!empty($added)) {
                foreach ($added as $key => $value) {
                    foreach ($value as $fieldType => $dataValue) {
                        if ($fieldType == "select_choices_or_calculations" && strtolower(
                                $value['field_type']
                            ) == "sql" && $value['select_choices_or_calculations'] != "") {
                            $sql['sql'] = self::removeExtraWhiteSpaces($value[$fieldType]);
                            $sql['changed'] = false;
                            $sql = self::changeSQLDataTable("", $sql);
                            $sql = self::checkSQLRegisteredPids(
                                $projects_array_sql[$pidsArray[$constant]][$key]['query'] ?? null,
                                $sql
                            );

                            if ($sql['changed']) {
                                $added[$key][$fieldType] = $sql['sql'];
                            }
                        }
                    }
                }
            }
            if (!empty($possiblyChanged)) {
                foreach ($possiblyChanged as $key => $value) {
                    if ($old[$key] != $value) {
                        $hasValueChanged = false;
                        $hasSQLChanged = false;
                        foreach ($value as $fieldType => $dataValue) {
                            if (trim($dataValue) != trim($old[$key][$fieldType])) {
                                //check if they have enetered the choices with a space between the '|' separator
                                if ($fieldType == "select_choices_or_calculations" && strtolower(
                                        $value['field_type']
                                    ) != "sql") {
                                    $choicesOld = self::parseArray($old[$key][$fieldType]);
                                    $choices = self::parseArray($value[$fieldType]);
                                    $possiblyChangedChoicesValues = array_diff($choicesOld, $choices);
                                    $possiblyChangedChoicesKey = array_diff_key($choicesOld, $choices);

                                    if (!empty($possiblyChangedChoicesValues) && !empty($possiblyChangedChoicesKey)) {
                                        $hasValueChanged = true;
                                    }
                                } else {
                                    if ($fieldType == "select_choices_or_calculations" && strtolower(
                                            $value['field_type']
                                        ) == "sql") {
                                        $sql['sql'] = self::removeExtraWhiteSpaces(
                                            $possiblyChanged[$key][$fieldType]
                                        );
                                        $sql['changed'] = false;
                                        $sql = self::changeSQLDataTable($old[$key][$fieldType], $sql);
                                        $sqlCompare = self::compareSQL($old[$key][$fieldType], $sql);
                                        $sql = self::checkSQLRegisteredPids(
                                            $projects_array_sql[$pidsArray[$constant]][$key]['query'] ?? null,
                                            $sqlCompare,
                                            $old[$key][$fieldType]
                                        );

                                        //If the data dictionary SQL differs from the Registered Pids
                                        if($old[$key][$fieldType] != $sql['sql']) {
                                            $sql['changed'] =  true;
                                        }

                                        if ($sql['changed']) {
                                            $hasValueChanged = true;
                                            $hasSQLChanged = true;
                                            $value[$fieldType] = $sql['sql'];
                                        }
                                    } else {
                                        $hasValueChanged = true;
                                    }
                                }
                            }
                        }
                        if ($hasValueChanged) {
                            if ($old[$key]['field_type'] == 'sql' && !$hasSQLChanged) {
                                #Add original SQL values as the new one has different PIDs and it detects them as changes
                                $value['select_choices_or_calculations'] = $old[$key]['select_choices_or_calculations'];
                            }
                            $changed[$key] = $value;
                        }
                    }else if($option == 'resolved'){
                        #If the variable values are right now the same. Remove from resolved list
                        self::removeFromResolvedList($module, $constant, $key);
                    }else if($value['field_type'] === 'sql'){
                        #If the pids are the ones in the templates, we need to update them for the correct ones
                        $new['sql'] = $value['select_choices_or_calculations'];
                        $sql = self::checkSQLRegisteredPids(
                            $projects_array_sql[$pidsArray[$constant]][$key]['query'],
                            $new,
                            $old[$key]['select_choices_or_calculations']
                        );
                        if($sql['sql'] != $old[$key]['select_choices_or_calculations']){
                            $value['select_choices_or_calculations'] = $sql['sql'];
                            $changed[$key] = $value;
                        }
                    }
                }
                $result = [];
                $result = self::custom_array_merge($module, $constant, $result, $changed, self::CHANGED, $option);
                $result = self::custom_array_merge($module, $constant, $result, $added, self::ADDED, $option);
                $result = self::custom_array_merge($module, $constant, $result, $removed, self::REMOVED, $option);
                if (!empty($result)) {
                    $allItems[$constant] = $result;
                }
            }
        }
        return $allItems;
    }

    public static function compareSQL($sqlOld, $sqlNew): array
    {
        $originalPid = null;
        $newPid = null;
        foreach (['/project_id\s=\s(\d+)/', '/project_id=(\d+)/', '/\[data-table:(.*?)\]/'] as $pattern) {
            preg_match_all($pattern, $sqlOld, $matchOld);
            preg_match_all($pattern, $sqlNew['sql'], $matchNew);

            if(is_numeric(arrayKeyExistsReturnValue($matchOld,[1, 0]))){
                $originalPid = $matchOld[1][0];
            }
            if(is_numeric(arrayKeyExistsReturnValue($matchNew,[1, 0]))){
                $newPid = $matchNew[1][0];
            }

            //Change pids in Admins SQL (NEW) to match the old and check for changes again
            if(array_key_exists(0 , $matchOld)) {
                foreach ($matchOld[0] as $index => $slqPid) {
                    if (!empty($matchNew[0][$index])) {
                        $pattern_replace = "/" . $matchNew[0][$index] . "/";
                        if ($pattern == '/\[data-table:(.*?)\]/') {
                            $pattern_replace = "/\[data-table:" . $matchNew[1][$index] . "\]/";
                        }
                        $sqlNew['sql'] = preg_replace($pattern_replace, $slqPid, $sqlNew['sql'], 1);
                        #We ensure we are making the changes but keeping the original PID
                        if ($originalPid != $newPid && $originalPid != null && $newPid != null) {
                            $sqlNew['sql'] = str_replace($newPid, $originalPid, $sqlNew['sql']);
                        }
                    }
                }
            }
        }
        //Compare if the newly changed SQL is the same as the old if not return sql as it has changed
        if ($sqlNew['sql'] != $sqlOld && !empty($sqlNew['sql'])) {
            $sqlNew['changed'] = true;
        }
        return $sqlNew;
    }

    public static function removeExtraWhiteSpaces($sql): string
    {
        $sql = preg_replace('/\s+/', ' ', $sql);
        return $sql;
    }
    public static function changeSQLDataTable($sqlOld, $sqlNew): array
    {
        $sql_redcap_data = "";
        if (str_contains($sqlOld, 'redcap_data')) {
            $sql_redcap_data = $sqlOld;
        } else {
            if (str_contains($sqlNew['sql'], 'redcap_data')) {
                $sql_redcap_data = $sqlNew['sql'];
            } else {
                if (empty($sqlOld)) {
                    //A new SQL has been added, check if it needs to be readjusted
                    $sql_redcap_data = $sqlNew['sql'];
                }
            }
        }
        $sql_redcap_data_compare = $sql_redcap_data;

        $lastPos = 0;
        while (($lastPos = strpos($sql_redcap_data, 'redcap_data', $lastPos)) !== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen('redcap_data');
        }
        $redcap_data_ocurrences = substr_count($sql_redcap_data, 'redcap_data');
        for ($i = 0; $i < $redcap_data_ocurrences; $i++) {
            $rest = substr($sql_redcap_data, $positions[$i], strlen($sql_redcap_data));
            $pos_pid_1 = (strpos($rest, 'project_id=')) ?? null;
            $pos_pid_2 = (strpos($rest, 'project_id = ')) ?? null;
            $table_replace = "";
            if ($pos_pid_1 != null && $pos_pid_2 != null) {
                if ($pos_pid_1 <= $pos_pid_2 && $pos_pid_1 != null) {
                    preg_match_all('/project_id=(\d+)/', $rest, $matchNew);
                    $slqPid = $matchNew[1][0] ?? '';
                    if ($slqPid !== '') {
                        $table_replace = "[data-table:" . $slqPid . "]";
                    }
                } else {
                    preg_match_all('/project_id\s=\s(\d+)/', $rest, $matchNew);
                    $slqPid = $matchNew[1][0] ?? '';
                    if ($slqPid !== '') {
                        $table_replace = "[data-table:" . $slqPid . "]";
                    }
                }
            } else {
                if ($pos_pid_1 != null) {
                    preg_match_all('/project_id=(\d+)/', $rest, $matchNew);
                    $slqPid = $matchNew[1][0] ?? '';
                    if ($slqPid !== '') {
                        $table_replace = "[data-table:" . $slqPid . "]";
                    }
                } else {
                    if ($pos_pid_2 != null) {
                        preg_match_all('/project_id\s=\s(\d+)/', $rest, $matchNew);
                        $$slqPid = $matchNew[1][0] ?? '';
                        if ($slqPid !== '') {
                            $table_replace = "[data-table:" . $slqPid . "]";
                        }
                    }
                }
            }
            if ($table_replace != "") {
                if (str_contains($sql_redcap_data, 'redcap_data')) {
                    $sql_redcap_data = preg_replace("/redcap_data/", $table_replace, $sql_redcap_data, 1);
                }
            }
        }

        if ($sql_redcap_data != $sql_redcap_data_compare) {
            if (str_contains($sqlOld, 'redcap_data') || empty($sqlOld)) {
                $sqlNew['changed'] = true;
            }
            $sqlNew['sql'] = $sql_redcap_data;
        }
        return $sqlNew;
    }

    public static function checkSQLRegisteredPids($sqlData, $sqlNew, $sqlOld = ""): array
    {
        $sqlNew['sql'] = self::removeExtraWhiteSpaces($sqlNew['sql']);
        $sqlData = self::removeExtraWhiteSpaces($sqlData);
        if(!empty($sqlData)) {
            $found = false;
            foreach ([
                         '/project_id=(\d+)/',             // Matches `project_id=123` (no spaces)
                         '/project_id\s=\s(\d+)/',         // Matches `project_id = 123` (spaces around the equals sign)
                         '/project_id\s*=\s*\'\'/',        // Matches `project_id=''` (empty quotes, optional spaces from Data Dictionary SQL Sanitizer)
                         '/project_id\s*=\s*\'(.*?)\'/',   // Matches `project_id='some_value'` (single quotes, optional spaces)
                         '/project_id\s*=\s*"(.*?)"/',     // Matches `project_id="some_value"` (double quotes, optional spaces)
                         '/\[data-table:(.*?)\]/',          // Matches `[data-table:some_value]`
                         '/data-table\s*=\s*\'\'/'          // Matches `data-table=''` (empty single quotes, optional spaces from Data Dictionary SQL Sanitizer)
                     ] as $pattern) {
                preg_match_all($pattern, $sqlNew['sql'], $matchNew);
                $slqPidNew = arrayKeyExistsReturnValue($matchNew, [1, 0]);
                preg_match_all($pattern, $sqlData, $matchRegistered);
                $slqPidRegistered = arrayKeyExistsReturnValue($matchRegistered, [1, 0]);
                if ($slqPidNew != $slqPidRegistered && !empty($slqPidNew) && !empty($slqPidRegistered)) {
                    $found = true;
                    $sqlNew['sql'] = str_replace("[data-table:" . $slqPidNew . "]", "[data-table:" . $slqPidRegistered . "]", $sqlNew['sql']);
                    $projectIdValueInSQLNew = str_replace($slqPidRegistered, $slqPidNew, $matchRegistered[0][0]);
                    $sqlNew['sql'] = str_replace($projectIdValueInSQLNew, $matchRegistered[0][0], $sqlNew['sql']);
                }
            }
            #Make sure that the SQL is like the template in code and not on the CSV
            if(!$found && !empty($sqlOld)) {
                $sqlOld = self::removeExtraWhiteSpaces($sqlOld);
                if($sqlData == $sqlOld) {
                    $sqlNew['sql'] = $sqlOld;
                    $sqlNew['changed'] = false;
                }else{
                    #The SQL has changed
                    $sqlNew['sql'] = $sqlData;
                    $sqlNew['changed'] = true;
                }
            }elseif (($found && empty($sqlOld)) || ($found && $sqlOld != $sqlData)) {
                #It's a newly added SQL and pids need to be changed
                #Or the pid is different from what it should
                $sqlNew['changed'] = true;
                if($sqlOld == "") {
                    #We ensure we take the SQL from the code and not from the Template
                    $sqlNew['sql'] = $sqlData;
                }
            }
        }
        return $sqlNew;
    }

    public static function getListOfChanges($checked_values): array
    {
        $hub_updates_list = explode(",", $checked_values);
        $update_list = [];
        foreach ($hub_updates_list as $updates) {
            $hub_updates = explode("-", $updates);
            if (!array_key_exists($hub_updates[0], $update_list)) {
                $update_list[$hub_updates[0]] = [];
            }
            if (!array_key_exists($hub_updates[2], $update_list[$hub_updates[0]])) {
                $update_list[$hub_updates[0]][$hub_updates[2]] = [];
            }
            array_push($update_list[$hub_updates[0]][$hub_updates[2]], $hub_updates[1]);
        }
        return $update_list;
    }

    public static function updateDataDictionary($module, $pidsArray, $checked_values): void
    {
        $update_list = self::getListOfChanges($checked_values);
        $constants_array = REDCapManagement::getProjectsConstantsArray();
        foreach ($constants_array as $constant) {
            if (array_key_exists($constant, $update_list)) {
                $path = $module->getModulePath() . "csv/" . $constant . ".csv";
                $old = REDCap::getDataDictionary($pidsArray[$constant], 'array', false);
                $new = $module->dataDictionaryCSVToMetadataArray($path);

                self::saveFieldData(
                    $module,
                    $update_list[$constant],
                    $old,
                    $new,
                    $pidsArray[$constant],
                    $constant,
                    $pidsArray[$constant]
                );
            }
        }
    }

    public static function updateSQLField($new, $old, $variable, $sqlData): array{
        $sql = [];
        $sql['sql'] = $new[$variable]['select_choices_or_calculations'];
        $sql['changed'] = false;
        $sql = self::changeSQLDataTable($old[$variable]['select_choices_or_calculations'], $sql);
        $sql = self::compareSQL($old[$variable]['select_choices_or_calculations'], $sql);
        $sql = self::checkSQLRegisteredPids($sqlData, $sql, $old[$variable]['select_choices_or_calculations']);

        //If the data dictionary SQL differs from the Registered Pids
        if($old[$variable]['select_choices_or_calculations'] != $sql['sql'] || $sql['changed'] != $new[$variable]['select_choices_or_calculations']) {
            $sql['changed'] =  true;
        }

        if ($sql['changed']) {
            $new[$variable]['select_choices_or_calculations'] = $sql['sql'];
        }
        return $new;
    }

    public static function saveFieldData(
        $module,
        $update_list,
        $old,
        $new,
        $project_id,
        $constant,
        $project_id_map
    ): void {
        $projects_array = REDCapManagement::getProjectsConstantsArray();
        $projects_array_repeatable = REDCapManagement::getProjectsRepeatableArray();
        $projects_array_surveys = REDCapManagement::getProjectsSurveysArray();
        $hub_mapper = $module->getProjectSetting('hub-mapper');
        $pidsArray = REDCapManagement::getPIDsArray($hub_mapper);
        $projects_array_sql = REDCapManagement::getProjectsSQLFieldsArray($pidsArray);
        $save_data = $old;
        foreach ($update_list as $status => $statusData) {
            foreach ($statusData as $index => $variable) {
                if ($status == self::CHANGED) {
                    if ($new[$variable]['field_type'] == "sql") {
                        if ($old[$variable]['field_type'] != "sql") {
                            $old[$variable]['select_choices_or_calculations'] = $new[$variable]['select_choices_or_calculations'];
                        }
                        //Update SQL with new redcap_data tables and pids SQL
                        $new = self::updateSQLField($new, $old, $variable, $projects_array_sql[$pidsArray[$constant]][$variable]['query']);
                    }
                    $save_data[$variable] = $new[$variable];

                    #Log Data
                    $newChanges = json_encode(array_diff_assoc($new[$variable], $old[$variable]), JSON_PRETTY_PRINT);
                    $oldChanges = json_encode(array_diff_assoc($old[$variable], $new[$variable]), JSON_PRETTY_PRINT);
                    REDCap::logEvent(
                        "Hub Updates: CHANGED [" . $variable . "] on " . $constant . " (PID #" . $project_id . ")",
                        "*OLD:\n" . $oldChanges . "\n\n*NEW:\n" . $newChanges,
                        null,
                        null,
                        null,
                        $project_id_map
                    );
                } else {
                    if ($status == self::ADDED) {
                        if ($new[$variable]['field_type'] == "sql") {
                            //Update SQL with new redcap_data tables and pids SQL
                            $new = self::updateSQLField($new, $old, $variable, $projects_array_sql[$pidsArray[$constant]][$variable]['query']);
                        }
                        $next_field_name = self::getNextFieldName($variable, $new, $old);
                        $save_data_aux = [];
                        $data = "";
                        $var_found = false;
                        foreach ($save_data as $varname => $value) {
                            if ($varname == $next_field_name) {
                                $save_data_aux[$variable] = $new[$variable];
                                #Log Data
                                $data = json_encode($save_data_aux[$variable], JSON_PRETTY_PRINT);
                                $var_found = true;
                            }
                            $save_data_aux[$varname] = $save_data[$varname];
                        }
                        #If variable not found, its in a new instrument
                        if (!$var_found && is_array(
                                $new[$variable]
                            ) && !empty($new[$variable]) && empty($old[$variable])) {
                            $save_data_aux[$variable] = $new[$variable];
                            #Log Data
                            $data = json_encode($save_data_aux[$variable], JSON_PRETTY_PRINT);

                            #Add Repeatable Instrument and Surveys if any
                            $index = array_search($constant, $projects_array);
                            REDCapManagement::addRepeatableInstrument(
                                $module,
                                $projects_array_repeatable[$index],
                                $project_id
                            );
                            REDCapManagement::createSurveys($module, $projects_array_surveys, $index, $project_id);
                            REDCap::logEvent(
                                "Hub Updates: ADDED New Istrument " . $save_data_aux[$variable]['form_name'] . "  on  " . $constant . " (PID #" . $project_id . ")",
                                $save_data_aux[$variable]['form_name'],
                                null,
                                null,
                                null,
                                $project_id_map
                            );
                        }
                        $save_data = $save_data_aux;
                        REDCap::logEvent(
                            "Hub Updates: ADDED [" . $variable . "]  on  " . $constant . " (PID #" . $project_id . ")",
                            $data,
                            null,
                            null,
                            null,
                            $project_id_map
                        );
                    } else {
                        if ($status == self::REMOVED) {
                            REDCap::logEvent(
                                "Hub Updates: REMOVED [" . $variable . "]  on  " . $constant . " (PID #" . $project_id . ")",
                                json_encode($save_data[$variable], JSON_PRETTY_PRINT),
                                null,
                                null,
                                null,
                                $project_id_map
                            );
                            unset($save_data[$variable]);
                        }
                    }
                }
            }
        }
        $save_data = self:: getOrderedDataByInstrument($save_data);
        $save_data = MetaData::convertFlatMetadataToDDarray($save_data);
        $sql_errors = MetaData::save_metadata($save_data, false, false, $project_id);
    }

    public static function getNextFieldName($variable, $new, $old): string
    {
        $new_var_list = array_keys($new);
        $new_var_list_index = array_search($variable, $new_var_list);
        $next_field_name = '';
        for ($i = $new_var_list_index; $i <= count($new_var_list); $i++) {
            if (array_key_exists(
                    $new_var_list[$i],
                    $new
                ) && $variable != $new_var_list[$i] && isset($old[$new_var_list[$i]])) {
                $next_field_name = $new_var_list[$i];
                break;
            }
        }
        return $next_field_name;
    }

    public static function getOrderedDataByInstrument($save_data): array
    {
        $save_data_ordered = [];
        $form_name = [];
        foreach ($save_data as $varname => $value) {
            if (!in_array($value['form_name'], $form_name)) {
                $form_name[] = $value['form_name'];
            }
        }
        foreach ($form_name as $form) {
            foreach ($save_data as $varname => $value) {
                if ($form == $value['form_name']) {
                    $save_data_ordered[$varname] = $save_data[$varname];
                }
            }
        }
        return $save_data_ordered;
    }

    public static function getResolvedList($module, $status = '', $pidsArray = null): array
    {
        $hub_updates_resolved_list = $module->getProjectSetting('hub-updates-resolved-list');
        $hub_updates_resolved_list = explode(",", $hub_updates_resolved_list);
        if ($status == 'resolved') {
            $resolvedDateSaved = empty($module->getProjectSetting('hub-updates-resolved-list-last-updated')) ? array() : $module->getProjectSetting('hub-updates-resolved-list-last-updated');
            $hub_updates_resolved_list_final = $hub_updates_resolved_list;
        }
        $resolved_list = [];
        $saveResolvedListAgain = false;
        foreach ($hub_updates_resolved_list as $keyResolved => $resolved) {
            if (!empty($resolved)) {
                $hub_updates_resolved = explode("-", $resolved);
                if (!array_key_exists($hub_updates_resolved[0], $resolved_list)) {
                    $resolved_list[$hub_updates_resolved[0]] = [];
                }
                $saveData = true;
                if ($status == 'resolved') {
                    $aux = [
                        'field_name' => $hub_updates_resolved[1],
                        'field_status' => $hub_updates_resolved[2],
                        'field_type' => $hub_updates_resolved[3]
                    ];
                    $constant = arrayKeyExistsReturnValue($hub_updates_resolved, [0]);
                    $variable = arrayKeyExistsReturnValue($hub_updates_resolved, [1]);
                    $resolvedDate = arrayKeyExistsReturnValue($resolvedDateSaved, [$constant, $variable, "date"]);
                    $dateTemplateLastUpdated = date("F d Y H:i:s", filemtime($module->getModulePath() . "csv/" . $constant . ".csv"));
                    $citicality = arrayKeyExistsReturnValue(self::getProjectCriticalityByDataDictionary($pidsArray[$constant]),[$variable]);

                    #IF it's a critical/medium variable and has been recently update it, remove it from the ignore list
                    if ($citicality != null && $citicality != "low" && $resolvedDate != null && strtotime(
                            $dateTemplateLastUpdated
                        ) > strtotime($resolvedDate)) {
                        $saveData = false;
                        $saveResolvedListAgain = true;
                        unset($hub_updates_resolved_list_final[$keyResolved]);
                    }
                } else {
                    $aux = ['field_name' => $hub_updates_resolved[1], 'field_type' => $hub_updates_resolved[2]];
                }
                if($saveData) {
                    array_push($resolved_list[$hub_updates_resolved[0]], $aux);
                }
            }
        }
        if($saveResolvedListAgain){
            $result = trim(implode(",", $hub_updates_resolved_list_final),",");
            $module->setProjectSetting('hub-updates-resolved-list',$result);
        }
        return $resolved_list;
    }

    public static function removeFromResolvedList($module, $constant, $variable): void
    {
        $hub_updates_resolved_list = $module->getProjectSetting('hub-updates-resolved-list');
        $resolved_list = arrayKeyExistsReturnValue(self::getResolvedList($module,'resolved'),[$constant]);
        $hub_updates_resolved_list = explode(",", $hub_updates_resolved_list);
        $hub_updates_resolved_list_final = $hub_updates_resolved_list;
        $found = false;
        if(!empty($resolved_list)) {
            foreach ($hub_updates_resolved_list as $key_resolved => $resolved_list) {
                $data = explode("-", $resolved_list);
                if ($data[0] == $constant && $data[1] == $variable) {
                    unset($hub_updates_resolved_list_final[$key_resolved]);
                    $found = true;
                }
            }
            if($found) {
                $result = trim(implode(",", $hub_updates_resolved_list_final), ",");
                $module->setProjectSetting('hub-updates-resolved-list', $result);
            }
        }
    }

    public static function parseArray($choices): array
    {
        $array_to_fill = array();

        $select_choices = $choices;
        $select_array = explode("|", $select_choices);
        foreach ($select_array as $key => $val) {
            $new_choices = explode(",", $val, 2);
            $array_to_fill[trim($new_choices[0])] = trim($new_choices[1]);
        }

        return $array_to_fill;
    }

    public static function custom_array_merge($module, $constant, $result, $data, $type, $option = ''): array
    {
        if (!empty($data)) {
            $resolved_list = self::getResolvedList($module);

            $is_empty = true;
            $total = 0;
            foreach ($data as $key => $value) {
                $resolved_found = false;
                if (arrayKeyExistsReturnValue($resolved_list, [$constant]) != null) {
                    foreach ($resolved_list[$constant] as $key_resolved => $value_resolved) {
                        if ($value_resolved['field_name'] == $key) {
                            $resolved_found = true;
                        }
                    }
                }

                #Save data only
                #if it's NOT in the resolved list, option blank, show as an update
                #if it's in the resoved list, option resolved, show updates
                if (
                    ($option == '' && !$resolved_found)
                    ||
                    ($option == 'resolved' && $resolved_found)
                ) {
                    $is_empty = false;
                    $result[$value['form_name']][$type][$key] = $value;
                    $total++;
                }
            }
            #make sure we have values to save before adding the total legend
            if (!$is_empty && $option == '') {
                if (!array_key_exists('TOTAL', $result)) {
                    $result["TOTAL"] = array();
                    $result["TOTAL"]["total"] = 0;
                }
                $result["TOTAL"][$type] = $total;
                $result["TOTAL"]["total"] += $result["TOTAL"][$type];
            }

            array_merge($result);
        }
        return $result;
    }

    public static function getIcon($status, $option = null): string
    {
        $icon = "fa-pencil-alt";
        $iconPDF = "#";
        $color = "";
        if ($status == self::CHANGED) {
            $icon = "fa-pencil-alt";
            $iconPDF = "#";
        } else {
            if ($status == self::ADDED) {
                $icon = "fa-plus";
                $iconPDF = "+";
            } else {
                if ($status == self::REMOVED) {
                    $icon = "fa-minus";
                    $iconPDF = "-";
                    $color = "style='color:#fff'";
                }
            }
        }

        $icon_legend = '<a href="#" data-toggle="tooltip" title="' . $status . '" data-placement="top" class="custom-tooltip" style="vertical-align: -2px;"><span class="label ' . $status . '" title="' . $status . '"><i class="fas ' . $icon . '" aria-hidden="true"></i></span></a>';
        if ($option == "pdf") {
            $icon_legend = '<span class="label ' . $status . ' labeltext">' . $iconPDF . '</span>';
        }
        return $icon_legend;
    }

    public static function getFieldName($new, $old, $status, $var, $option = ""): string
    {
        if ($status == self::CHANGED) {
            if ($new[$var] !== $old[$var]) {
                $color = "class='mb-2 bg-warning';";
                $col = '<div $color id="bg-warning">' . self::checkTagsExistAndAreClosed($new[$var]) . '</div>';
                $col .= '<div class="text-muted" style="text-decoration: line-through;">' . self::checkTagsExistAndAreClosed(
                        $old[$var]
                    ) . '</div>';
            } else {
                $col = "<div class='mb-2'>" . self::checkTagsExistAndAreClosed($old[$var]) . "</div>";
            }
            if ($var == "field_name" && $old['form_name'] != "" && $new['form_name'] !== $old['form_name']) {
                $col .= "<small class='d-flex' style='font-size:12px;'>Form name: <span class='text-muted' style='text-decoration: line-through;padding-left:5px;'>" . ucwords(
                        str_replace('_', ' ', $old['form_name'])
                    ) . "</span><small>";
            }
            $col .= self::getFieldLabel(
                $new,
                $old,
                self::CHANGED,
                'Show the field ONLY if: ',
                'branching_logic',
                $option
            );
        } else {
            $col = self::checkTextLengthAndSplit($option, $new['field_name']);
            if ($new['branching_logic'] != "") {
                $col .= "<small class='d-flex' style='font-size:12px;'>Show the field ONLY if: " . filter_tags(
                        $new['branching_logic']
                    ) . "</small>";
            }
        }
        return $col;
    }

    public static function getFieldLabel($new, $old, $status, $string, $var, $option = ''): string
    {
        if ($status == self::CHANGED) {
            $col = "";
            if ($option == "pdf") {
                $col .= "<div style='width: 70%'>";
            }

            if ($new[$var] !== $old[$var]) {
                if ($old == "") {
                    $color = "class='mb-2 text-light p-1' style='background-color:#5d9451; font-size:12px;';";
                    $col .= "<div $color>$string " . filter_tags($new[$var]) . "</div>";
                } else {
                    if ($new[$var] == "") {
                        $color = "class='mb-2 p-1 bg-warning' style='font-size:12px;';";
                        $col .= "<small class='mb-2 d-flex text-light p-1' style='background-color:#cb410b; font-size:12px; text-decoration:line-through;'>$string" . filter_tags(
                                $old[$var]
                            ) . "</small>";
                    } else {
                        $color = "class='mb-2 bg-warning p-1' style='font-size:12px;';";
                        $col .= "<div $color>$string " . filter_tags($new[$var]) . "</div>";
                        $col .= "<small class='mb-2 p-1 d-flex' style='font-size:12px; text-decoration:line-through;'>$string " . filter_tags(
                                $old[$var]
                            ) . "</small>";
                    }
                }
            } else {
                if ($old[$var] != "") {
                    $col .= "<small class='d-flex mb-2'><div><i class='text-muted'>$string </i><i class='text-info'> " . filter_tags(
                            $old[$var]
                        ) . "</i></div></small>";
                }
            }
        } else {
            $col = "";

            if ($new['section_header'] != "") {
                $col .= "<div class='mb-2' style='font-size:12px;'>Section Header: " . filter_tags(
                        $new['section_header']
                    ) . "</div>";
            }

            if ($option == "pdf") {
                $col .= strip_tags($new['field_label']);
            } else {
                $col .= $new['field_label'];
            }


            if ($new['field_note'] != "") {
                $col .= "<small class='d-flex'>Field Note: " . filter_tags($new['field_note']) . "</small>";
            }
        }
        return $col;
    }

    public static function getFieldAttributes($value): string
    {
        $col = "";
        global $lang;
        $choices = self::parseArray($value['select_choices_or_calculations']);
        $col .= $value['field_type'];

        if ($value['text_validation_type_or_show_slider_number'] != "") {
            if ($value['text_validation_type_or_show_slider_number'] == 'int') {
                $value['text_validation_type_or_show_slider_number'] = 'integer';
            } elseif ($value['text_validation_type_or_show_slider_number'] == 'float') {
                $value['text_validation_type_or_show_slider_number'] = 'number';
            } elseif (in_array(
                $value['text_validation_type_or_show_slider_number'],
                array('date', 'datetime', 'datetime_seconds')
            )) {
                $value['text_validation_type_or_show_slider_number'] .= '_ymd';
            }
            $col .= " (" . filter_tags($value['text_validation_type_or_show_slider_number']);
            if ($value['text_validation_min'] != "") {
                $col .= ", Min:" . filter_tags($value['text_validation_min']);
            }
            if ($value['text_validation_max'] != "") {
                $col .= ", Max: " . filter_tags($value['text_validation_max']);
            }

            $col .= ")";
        }

        if ($value['required_field'] == 'y') {
            $col .= ", Required";
        }

        if ($value['identifier'] == 'y') {
            $col .= ", Identifier";
        }

        if ($value['field_annotation'] != "") {
            $col .= "<br /> Field Annotation: " . filter_tags($value['field_annotation']);
        }

        if ($value['select_choices_or_calculations'] != "" && $value['field_type'] != "descriptive") {
            if ($value['field_type'] == 'slider') {
                $col .= "<br />{$lang['design_488']} " . implode(
                        ", ",
                        Form::parseSliderLabels(
                            $value['select_choices_or_calculations']
                        )
                    );
            } elseif ($value['field_type'] == 'calc') {
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= '<td>' . filter_tags($value['select_choices_or_calculations']) . '</td>';
                $col .= '</tr>';
                $col .= '</table>';
            } elseif ($value['field_type'] == 'sql') {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder"><tr><td>' . filter_tags(
                        $value['select_choices_or_calculations']
                    ) . '</td></tr></table>';
            } else {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($choices as $val => $label) {
                    $col .= '<tr valign="top">';
                    if ($value['field_type'] == 'checkbox') {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                    } else {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                    }

                    $col .= '<td>' . filter_tags($label) . '</td>';
                    $col .= '</tr>';
                }
                $col .= '</table>';
            }
        }
        return $col;
    }

    public static function getFieldAttributesChanged($new, $old): string
    {
        $col = "";
        $choices = self::parseArray($new['select_choices_or_calculations']);
        $oldChoices = self::parseArray($old['select_choices_or_calculations']);

        if ($new['field_type'] == 'select') {
            $new['field_type'] = 'dropdown';
        } elseif ($new['field_type'] == 'textarea') {
            $new['field_type'] = 'notes';
        }

        if ($new['field_type'] !== $old['field_type']) {
            if ($old['field_type'] == "") {
                $color = "class='mb-2 text-light p-1 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<div $color> " . filter_tags($new['field_type']) . "</div>";
            } else {
                if ($new['field_type'] == "") {
                    $color = "class='mb-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                    $col .= "<small $color>" . filter_tags($old['field_type']) . "</small>";
                } else {
                    $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;background-color:#ffc107 !important;';";
                    $col .= "<div $color>" . filter_tags($new['field_type']) . "</div>";
                    $col .= "<small class='mb-2 p-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'>" . filter_tags(
                            $old['field_type']
                        ) . "</small>";
                }
            }
        } else {
            if ($old['field_type'] != "") {
                $col .= "<div class='d-inline-block mr-1 mb-2'>" . filter_tags($old['field_type']) . "</div>";
            }
        }

        if ($new['text_validation_type_or_show_slider_number'] !== $old['text_validation_type_or_show_slider_number']) {
            if ($old['text_validation_type_or_show_slider_number'] == "") {
                //New item
                $color = "class='mb-2 text-light p-1 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<div $color> (" . filter_tags($new['text_validation_type_or_show_slider_number']);
                if ($new['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($new['text_validation_max']);
                }
                if ($new['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($new['text_validation_max']);
                }
                $col .= ") </div>";
            } elseif ($new['text_validation_type_or_show_slider_number'] == "") {
                //removed
                $color = "class='mb-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<div $color> (" . filter_tags($old['text_validation_type_or_show_slider_number']);
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($old['text_validation_max']);
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($old['text_validation_max']);
                }
                $col .= ") </div>";
            } else {
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;background-color:#ffc107 !important;';";
                $col .= "<div $color> (" . filter_tags($new['text_validation_type_or_show_slider_number']);
                if ($new['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($new['text_validation_max']);
                }
                if ($new['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($new['text_validation_max']);
                }
                $col .= ") </div>";

                $col .= "<div class='ml-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'> (" . filter_tags(
                        $old['text_validation_type_or_show_slider_number']
                    );
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($old['text_validation_max']);
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($old['text_validation_max']);
                }
                $col .= ") </div>";
            }
        } else {
            if ($old['text_validation_type_or_show_slider_number'] != "") {
                $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
                $col .= "<div $color> (" . filter_tags($old['text_validation_type_or_show_slider_number']);
                if ($old['text_validation_min'] != "") {
                    $col .= ", Min:" . filter_tags($old['text_validation_max']);
                }
                if ($old['text_validation_min'] != "") {
                    $col .= ", Max: " . filter_tags($old['text_validation_max']);
                }
                $col .= ") </div>";
            }
        }

        if ($new['required_field'] !== $old['required_field']) {
            if ($old['required_field'] == "") {
                $color = "class='mb-2 text-light p-1 ml-2 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<small $color> Required </small>";
            } elseif ($new['required_field'] == "") {
                $color = "class='mb-2 ml-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color> Required </small>";
            }
        } else {
            if ($old['required_field'] != "") {
                $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
                $col .= "<small $color> Required</small>";
            }
        }

        if ($new['identifier'] !== $old['identifier']) {
            if ($old['identifier'] == "") {
                $color = "class='mb-2 text-light p-1 ml-2 d-inline-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<small $color> Identifier </small>";
            } elseif ($new['identifier'] == "") {
                $color = "class='mb-2 ml-2 d-inline-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color> Identifier </small>";
            }
        } else {
            if ($old['identifier'] != "") {
                $color = 'class="d-inline-block mr-1 mb-2" style="font-size:12px;"';
                $col .= "<small $color> Identifier</small>";
            }
        }

        if ($new['field_annotation'] !== $old['field_annotation']) {
            if ($old['field_annotation'] == "") {
                $color = "class='mb-2 text-light p-1 d-block' style='background-color:#5d9451 !important; font-size:12px;';";
                $col .= "<small $color>Field Annotation: " . filter_tags($new['field_annotation']) . "</small>";
            } elseif ($new['field_annotation'] == "") {
                $color = "class='mb-2 d-block text-light p-1' style='background-color:#cb410b !important; font-size:12px; text-decoration:line-through;';";
                $col .= "<small $color>Field Annotation: " . filter_tags($old['field_annotation']) . "</small>";
            } else {
                $color = "class='mb-2 bg-warning p-1 d-inline-block' style='font-size:12px;background-color:#ffc107 !important;';";
                $col .= "<div $color>" . filter_tags($new['field_annotation']) . "</div>";
                $col .= "<small class='mb-2 p-1 d-inline-block' style='font-size:12px; text-decoration:line-through;'>" . filter_tags(
                        $old['field_annotation']
                    ) . "</small>";
            }
        } else {
            if ($old['field_annotation'] != "") {
                $col .= "<div class='d-inline-block mr-1 mb-2'>" . filter_tags($old['field_annotation']) . "</div>";
            }
        }

        if ($new['select_choices_or_calculations'] !== $old['select_choices_or_calculations']) {
            if ($new['field_type'] == 'calc') {
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td class='bg-warning' style='background-color:#ffc107 !important;'>" . filter_tags(
                        $new['select_choices_or_calculations']
                    ) . "</td>";
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td style='background-color:#cb410b !important; text-decoration:line-through;'>" . filter_tags(
                        $old['select_choices_or_calculations']
                    ) . "</td>";
                $col .= '</tr>';
                $col .= '</table>';
            } elseif ($new['field_type'] == 'sql') {
                $sql_different = printSQLDifferences(
                    $old['select_choices_or_calculations'],
                    $new['select_choices_or_calculations']
                );
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">' .
                    '<tr><td>' . $sql_different['new'] . '</td></tr>' .
                    '<tr><td style="text-decoration: line-through;">' . $sql_different['old'] . '</td></tr>' .
                    '</table>';
            } else {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($choices as $val => $label) {
                    $col .= '<tr valign="top">';
                    $oldValue = $oldChoices[$val];
                    if ('field_type' == 'checkbox') {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                        $col .= '<td>' . filter_tags($new['field_type']) . '</td>';
                    } elseif ($label !== $oldValue) {
                        if ($oldValue == "") {
                            $col .= "<td class='text-light' style='background-color:#5d9451 !important;'>" . filter_tags(
                                    $val
                                ) . "</td>";
                            $col .= "<td class='text-light' style='background-color:#5d9451 !important;'>" . filter_tags(
                                    $label
                                ) . "</td>";
                        } elseif ($label == "") {
                            $col .= "<td class='text-light' style='background-color:#cb410b !important; text-decoration:line-through;'>" . filter_tags(
                                    $val
                                ) . "</td>";
                            $col .= "<td class='text-light' style='background-color:#cb410b !important; text-decoration:line-through;'>" . filter_tags(
                                    $oldValue
                                ) . "</td>";
                        } elseif ($label !== $oldValue) {
                            $col .= "<td class='bg-warning' style='background-color:#ffc107 !important;'>" . filter_tags(
                                    $val
                                ) . "</td>";
                            $col .= "<td class='bg-warning' style='background-color:#ffc107 !important;'>" . filter_tags(
                                    $label
                                ) . "</td>";
                            $col .= "<td class='text-light' style='background-color:#cb410b !important; text-decoration: line-through;'>" . filter_tags(
                                    $oldValue
                                ) . "</td>";
                        }
                    } else {
                        $col .= "<td>" . filter_tags($val) . "</td>";
                        $col .= "<td>" . filter_tags($label) . "</td>";
                    }
                }
                $col .= '</table>';
            }
        } elseif ($old['select_choices_or_calculations'] != "") {
            if ($old['field_type'] == 'calc') {
                $col .= '<table>';
                $col .= '<tr>';
                $col .= '<th> Calculation </th>';
                $col .= '</tr>';
                $col .= '<tr>';
                $col .= "<td>" . filter_tags($old['select_choices_or_calculations']) . "</td>";
                $col .= '</tr>';
                $col .= '</table>';
            } elseif ($old['field_type'] == 'sql') {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder"><tr><td>' . filter_tags(
                        $old['select_choices_or_calculations']
                    ) . '</td></tr></table>';
            } else {
                $col .= '<table border="0" cellpadding="2" cellspacing="0" class="ReportTableWithBorder">';
                foreach ($oldChoices as $val => $label) {
                    $col .= '<tr valign="top">';
                    if ($old['field_type'] == 'checkbox' && $old['select_choices_or_calculations'] != $new['select_choices_or_calculations']) {
                        $col .= '<td>' . filter_tags($val) . '</td>';
                        $col .= '<td>' . filter_tags($label . $old['field_type']) . '</td>';
                    } else {
                        $col .= "<td>" . filter_tags($val) . "</td>";
                        $col .= "<td>" . filter_tags($label) . "</td>";
                    }
                }
                $col .= '</table>';
            }
        }
        return $col;
    }

    public static function hasVariableBeenUpdated($allUpdates, $fieldName): bool
    {
        foreach ($allUpdates as $instrument => $instrumentData) {
            if ($instrument != "TOTAL") {
                foreach ($instrumentData as $status => $typeData) {
                    foreach ($typeData as $variableChanges => $data) {
                        if ($variableChanges == $fieldName) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public static function getTemplateLastUpdatedDate($module, $constant, $resolved_date = null): string
    {
        $dateTemplateLastUpdated = date(
            "F d Y H:i:s",
            filemtime($module->getModulePath() . "csv/" . $constant . ".csv")
        );
        if ($resolved_date != null) {
            if (strtotime($dateTemplateLastUpdated) > strtotime($resolved_date)) {
                #Files has been updated
                return "<span class='hub-update-last-updated-recent-date-badge badge' style='margin-left: 10px'>NEW CHANGES</span>";
            }
        } else {
            if (strtotime($dateTemplateLastUpdated) < strtotime('-30 days')) {
                #If past 30 days show in red
                return "<span class='hub-update-last-updated-past-date'>" . $dateTemplateLastUpdated . "</span>";
            }
            return $dateTemplateLastUpdated;
        }
        return "";
    }

    public static function getPrintData($module, $pidsArray, $constantArray): array
    {
        $printData = [];
        $oldValues = [];
        foreach ($constantArray as $constant => $project_data) {
            $oldValues[$constant] = REDCap::getDataDictionary($pidsArray[$constant], 'array', false);

            $Proj = $module->getProject($pidsArray[$constant]);
            $gotoredcap = htmlentities(
                APP_PATH_WEBROOT_ALL . "Design/data_dictionary_codebook.php?pid=" . $pidsArray[$constant],
                ENT_QUOTES
            );
            $printData[$constant]['title'] = $Proj->getTitle();
            $printData[$constant]['gotoredcap'] = $gotoredcap;
            $printData[$constant]['pid'] = $pidsArray[$constant];
        }

        return [$printData, $oldValues];
    }

    /**
     * Function that checks if the html has all tags closed. This is made so user can see the issues and the PDF can be printed.
     * NOT: return as text
     * YES: return as html
     * @param $html
     * @return string
     */
    public static function checkTagsExistAndAreClosed($html): string
    {
        preg_match_all('#<([a-zA-Z0-9]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];
        preg_match_all('#</([a-zA-Z0-9]+)>#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);

        $tagsClosed = true;
        foreach ($openedtags as $index => $tagO) {
            if (!in_array($tagO, $closedtags)) {
                $tagsClosed = false;
            }
        }

        if ($tagsClosed) {
            return filter_tags($html);
        }

        return htmlspecialchars($html, ENT_QUOTES);
    }

    public static function checkTextLengthAndSplit($option, $text)
    {
        if ($option == "pdf" && strlen($text) > 15) {
            $field_name_length = strlen($text);
            $field_name_part1 = substr($text, 0, 15);
            $field_name_part2 = substr($text, 15, $field_name_length);
            $text = $field_name_part1 . "<br>" . $field_name_part2;
        }
        return $text;
    }

    /**
     * Analyzes the criticality of a given variable based on its annotation and updates the result array.
     *
     * @param array $row An array representing a single row of data from the project's data dictionary, which includes 'field_annotation' and 'field_name'.
     * @param array $projectCriticality The current result array to be updated with the criticality information.
     *
     * @return array The updated result array containing the criticality details for the variables.
     */
    public static function getCriticality($row, $projectCriticality): array{
        if ($row['field_annotation'] !== "" && strpos($row['field_annotation'], "@HARMONIST-VAR-") !== false) {
            $criticality = strtolower(trim(explode("@HARMONIST-VAR-", $row['field_annotation'])[1], '\'"'));
            $projectCriticality[$row['field_name']] = $criticality;

            // Increment the total count for the criticality
            if (!isset($projectCriticality[self::VARIABLE_CRITICALITY_TOTAL][$criticality])) {
                $projectCriticality[self::VARIABLE_CRITICALITY_TOTAL][$criticality] = 0;
            }
            $projectCriticality[self::VARIABLE_CRITICALITY_TOTAL][$criticality]++;
        }
        return $projectCriticality;
    }

    /**
     * Retrieves criticality information from the project's data dictionary.
     *
     * @param int $project_id The ID of the project for which the data dictionary is retrieved.
     *
     * @return array An array containing criticality details derived from the data dictionary.
     */
    public static function getProjectCriticalityByDataDictionary($project_id): array {
        $data_dictionary_settings = REDCap::getDataDictionary($project_id, 'array', false);
        $projectCriticality = [];
        if (is_array($data_dictionary_settings) && !empty($data_dictionary_settings)) {
            foreach ($data_dictionary_settings as $row) {
                $projectCriticality = self::getCriticality($row, $projectCriticality);
            }
        }
        return $projectCriticality;
    }

    /**
     * Organizes criticality details by combining data from the project's data dictionary and additional project data.
     *
     * @param int $project_id The ID of the project for which data is processed.
     * @param array $projectData Additional project data organized by instruments, statuses, and variables.
     *
     * @return array An array containing combined criticality details from the data dictionary and project data.
     */
    public static function organizeCriticalityByConstantData($project_id, $projectData): array {
        $projectCriticality = self::getProjectCriticalityByDataDictionary($project_id);

        if(!empty($projectCriticality)) {
            //Reset total to only show the current vars
            $projectCriticality["TOTAL"] = [];
            foreach ($projectData as $instrument => $instrumentData) {
                foreach ($instrumentData as $status => $typeData) {
                    foreach ($typeData as $variable => $new) {
                        if ($new['field_annotation'] != "") {
                            $projectCriticality = self::getCriticality($new, $projectCriticality);
                        }
                    }
                }
            }
        }

        return $projectCriticality;
    }

    public static function organizeCriticalityByIgnoreList($module, $project_id, $constant, $ignoreData): ?array {
        $path = $module->getModulePath() . "csv/" . $constant . ".csv";
        $new = $module->dataDictionaryCSVToMetadataArray($path);

        foreach ($new as $instrument => $variable) {
            if ($variable['field_annotation'] != "") {
                $projectCriticality = self::getCriticality($variable, $projectCriticality);
            }
        }
        return $projectCriticality;
    }

    /**
     * Generates an icon representation of a variable's criticality for display in HTML or PDF formats.
     *
     * @param array $criticalVariables A nested array containing critical variables and their criticality levels.
     * @param string $variable The name of the variable for which the criticality icon is generated.
     * @param string|null $option Optional. Determines the output format ('pdf' for PDF output, null for HTML).
     *
     * @return string An HTML string representing the criticality icon for the variable.
     */
    public static function getCriticalityIcon($criticalVariables, $variable, $option=null){
        $status = "fa-angle-down";
        $icon = "low";
        $iconPDF = self::VARIABLE_CRITICALITY_LOW_ICON_EMAIL;
        if(!empty($criticalVariables)){
            if(arrayKeyExistsReturnValue($criticalVariables,[$variable]) !== null){
                switch ($criticalVariables[$variable]) {
                    case self::VARIABLE_CRITICALITY_CRITICAL:
                        $status = "fa-triangle-exclamation";
                        $icon = "critical";
                        $iconPDF = self::VARIABLE_CRITICALITY_CRITICAL_ICON_EMAIL;
                        break;
                    case self::VARIABLE_CRITICALITY_MEDIUM:
                        $status = "fa-equals";
                        $icon = "medium";
                        $iconPDF = self::VARIABLE_CRITICALITY_MEDIUM_ICON_EMAIL;
                        break;
                    default:
                        $status = "fa-angle-down";
                        $icon = "low";
                        $iconPDF = self::VARIABLE_CRITICALITY_LOW_ICON_EMAIL;
                        break;
                }

            }
        }
        if ($option == "pdf") {
            return '<span class="label ' . $icon . ' labeltext">' . $iconPDF . '</span> ';
        }else {
            return '<a href="#"  class="label ' . $status . ' criticality" data-toggle="tooltip" title="' . ucfirst(
                    $icon
                ) . " priority" . '" data-placement="top">
                    <span class="label ' . $icon . '" title="' . ucfirst($icon) . '">
                        <i class="fa fa-solid ' . $status . '" aria-hidden="true"></i>
                    </span>
                </a>';
        }
    }

    /**
     * Retrieves the total count of variables marked as critical for a given constant.
     *
     * @param string $constant The project constant identifier for which the critical variable count is retrieved.
     * @param array $criticalVariablesInHubUpdates A nested array containing critical variables organized by constants.
     *
     * @return int The total count of critical variables for the specified constant.
     */
    public static function getCriticalTotalByProject($constant,$criticalVariablesInHubUpdates):int
    {
        $criticalVariablesTotal = 0;
        if(arrayKeyExistsReturnValue($criticalVariablesInHubUpdates,[$constant,self::VARIABLE_CRITICALITY_TOTAL,self::VARIABLE_CRITICALITY_CRITICAL]) !== null){
            $criticalVariablesTotal = $criticalVariablesInHubUpdates[$constant][self::VARIABLE_CRITICALITY_TOTAL][self::VARIABLE_CRITICALITY_CRITICAL];
        }
        return $criticalVariablesTotal;
    }

    public static function displayAlerts($module, $pidsArray, $settings, $ignored = false)
    {
        self::themeAlert($module, $pidsArray, $ignored);
        self::surveysNotActivatedAlert($module, $pidsArray);
        self::repeatingInstrumentsAlert($module, $pidsArray);
        self::moduleNotEnabledAlert($module, $pidsArray);
        self::desAlert($module, $pidsArray, $settings, $ignored);
        self::missingProjectsAlert($module, $pidsArray, $ignored);
        self::showRequiresAdminAlert($module, $settings['hub_name']);
    }

    private static function generateAlertBox($message, $formAction, $buttons, $extraContent = '')
    {
        $alert = '
        <div class="container" style="margin-top: 10px">
            <div class="alert alert-warning col-md-12">
                <div>' . $message . '</div>
                ' . $extraContent;
        if($formAction != ""){
            $alert .= '<form method="POST" action="' . htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') . '" class="" id="action_form">';
        }
        if($buttons != ""){
            $alert .=  implode('', $buttons);
        }
        if($formAction != ""){
            $alert .= '</form>';
        }
        $alert .= '</div>
        </div>
    ';

        return $alert;
    }

    private static function generateButton($name, $value, $label, $class, $extraAttributes = '', $mailtoHref = false)
    {
        $type = "submit";
        $onclick = "";
        if ($mailtoHref) {
            $type = "button";
            $onclick = 'onclick="window.location.href=\'' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '\'"';
            $value = '';
        }
        return '<div class="float-right">
            <button type="'.$type.'" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" 
                    value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" 
                    '.$onclick.'
                    class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" 
                    ' . $extraAttributes . '>' . $label . '</button>
        </div>';
    }

    private static function themeAlert($module, $pidsArray, $ignored)
    {
        if (!ProjectData::checkIfThemeExists($module, $pidsArray)
            && ($module->getProjectSetting('hub-updates-show-theme-msg') === "true" || $module->getProjectSetting('hub-updates-show-theme-msg') === null
                || ($module->getProjectSetting('hub-updates-show-theme-msg') === "false" && $ignored))) {
            $message = 'The survey theme <strong>' . ProjectData::HUB_SURVEY_THEME_NAME . '</strong> does not exist in some of your Hub surveys.';
            $extraContent = '<div>Create and Install will add this theme in all the projects\' surveys.</div>';
            $formAction = $module->getUrl('hub-updates/save_theme_AJAX.php') . '&redcap_csrf_token=' . $module->getCSRFToken();
            if(!$ignored) {
                $buttons[] = self::generateButton('option', 'dismiss', 'Dismiss Message', 'btn btn-danger', 'onclick="select_btn"');
            }
            $buttons[] = self::generateButton('option', 'create', 'Create & Install Theme', 'btn btn-success', 'id="select_btn" style="margin-right: 10px;"');
            echo self::generateAlertBox($message, $formAction, $buttons, $extraContent);
        }
    }

    private static function surveysNotActivatedAlert($module, $pidsArray)
    {
        if (!ProjectData::checkIfSurveysAreActivated($module, $pidsArray)) {
            $message = 'There are surveys not activated on their respective projects.';
            $formAction = $module->getUrl('hub-updates/update_surveys_AJAX.php') . '&redcap_csrf_token=' . $module->getCSRFToken();
            $buttons = [
                self::generateButton('option', 'update', 'Activate Surveys', 'btn btn-success', 'style="margin-right: 10px;"')
            ];
            echo self::generateAlertBox($message, $formAction, $buttons);
        }
    }

    private static function repeatingInstrumentsAlert($module, $pidsArray)
    {
        if (!ProjectData::checkIfMissingRepeatingInstruments($module, $pidsArray)) {
            $message = 'There are repeating instruments not enabled on their respective projects.';
            $formAction = $module->getUrl('hub-updates/update_surveys_AJAX.php') . '&redcap_csrf_token=' . $module->getCSRFToken();
            $buttons = [
                self::generateButton('option', 'update', 'Enable', 'btn btn-success', 'style="margin-right: 10px;"')
            ];
            echo self::generateAlertBox($message, $formAction, $buttons);
        }
    }

    private static function moduleNotEnabledAlert($module, $pidsArray)
    {
        if (!ProjectData::checkIfModuleIsEnabledOnProjects($module, $pidsArray, (int)$_GET['pid'])) {
            $message = 'There are some projects that have the module not enabled or are missing a functionality setting.';
            $formAction = $module->getUrl('hub-updates/enable_module_and_settings_AJAX.php') . '&redcap_csrf_token=' . $module->getCSRFToken();
            $buttons = [
                self::generateButton('option', 'update', 'Enable', 'btn btn-success', 'style="margin-right: 10px;"')
            ];
            echo self::generateAlertBox($message, $formAction, $buttons);
        }
    }

    private static function desAlert($module, $pidsArray, $settings, $ignored)
    {
        if (!ProjectData::checkIfDESIsEnabled($module, $pidsArray, $settings['deactivate_datahub___1']) &&
            ($module->getProjectSetting('hub-updates-show-des-msg') === "true" || $module->getProjectSetting('hub-updates-show-des-msg') === null
                || ($module->getProjectSetting('hub-updates-show-des-msg') === "false" && $ignored))) {
            $message = 'The Data Hub feature of this Hub is activated, but the Data Model Browser sub-component is not installed.';
            $extraContent = '<div>Click <strong>Add</strong> to create any missing REDCap projects and activate the Data Model Browser on your Hub.</div>';
            $formAction = $module->getUrl('hub-updates/add_DES_AJAX.php') . '&redcap_csrf_token=' . $module->getCSRFToken();

            if(!$ignored) {
                $buttons[] = self::generateButton('option', 'dismiss', 'Dismiss Message', 'btn btn-danger', 'onclick="select_btn"');
            }
            $buttons[] = self::generateButton('option', 'add', 'Add', 'btn btn-success', 'id="select_btn" style="margin-right: 10px;"');
            echo self::generateAlertBox($message, $formAction, $buttons, $extraContent);
        }
    }

    private static function missingProjectsAlert($module, $pidsArray, $ignored)
    {
        $missingProjectsArray = ProjectData::checkIfMissingProjects($pidsArray);
        if (!empty($missingProjectsArray)) {
            $message = 'This Hub installation is missing the following projects:';
            $projectTitles = REDCapManagement::getProjectsTitlesArray();
            $extraContent = '<ul>';
            foreach ($missingProjectsArray as $index => $missingProject) {
                $extraContent .= '<li>' . htmlspecialchars($projectTitles[$index], ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $extraContent .= '</ul><br/>';
            $formAction = $module->getUrl(
                    'hub-updates/add_missing_projects_AJAX.php'
                ) . '&redcap_csrf_token=' . $module->getCSRFToken();
            if($module->isSuperUser()) {
                $extraContent .= '<div>Click <strong>Add</strong> to create any missing REDCap projects on your Hub.</div>';
                $buttons = [
                    self::generateButton(
                        'option',
                        'add',
                        'Add',
                        'btn btn-success',
                        'id="select_btn" style="margin-right: 10px;"'
                    )
                ];
                echo self::generateAlertBox($message, $formAction, $buttons, $extraContent);
            }elseif($module->getProjectSetting('hub-updates-show-missing-msg') === "true" || $module->getProjectSetting('hub-updates-show-missing-msg') === null
                || ($module->getProjectSetting('hub-updates-show-missing-msg') === "false" && $ignored)){
                $extraContent .= '<div>An admin is required to add a project on your Hub.</div>';
                if(!$ignored){
                    $buttons = [
                        self::generateButton('option', 'dismiss', 'Dismiss Message', 'btn btn-danger', 'onclick="select_btn"')
                    ];
                }
                echo self::generateAlertBox($message, $formAction, $buttons, $extraContent);
            }
        }
    }

    public static function showRequiresAdminAlert($module, $hubName)
    {
        $allUpdates = $module->getProjectSetting('hub-updates')['data'];
        $foundSqlFieldType = false;
        foreach ($allUpdates as $constant => $project_data){
            foreach ($project_data as $instrument => $instrumentData) {
                if ($instrument != "TOTAL") {
                    foreach ($instrumentData as $status => $typeData) {
                        foreach ($typeData as $variable => $data) {
                            if($data['field_type'] == 'sql' && !$module->isSuperUser() && !$foundSqlFieldType){
                                $message = "There are some updates that can only be executed by a REDCap administrator. Please reach out to ";
                                foreach ($module->getProjectSetting('admin-notifications') as $index => $email){
                                    $message .= "<a href='".$email."'>".$email."</a>";
                                    if ($index === array_key_last($module->getProjectSetting('admin-notifications'))) {
                                        //Nothing
                                    } else {
                                        $message .= ", ";
                                    }
                                }
                                $buttons = [
                                    self::generateButton('option', self::getREDCapAdminMailTo($module, $hubName), '<em class="fa fa-envelope"></em> Send Email', 'btn btn-success', '', true)
                                ];
                                echo self::generateAlertBox($message, "", $buttons, "");
                                $foundSqlFieldType = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function generateAdminAlerts($module, $pidsArray, $settings):string{
        $message = "";
        if (!ProjectData::checkIfDESIsEnabled($module, $pidsArray, $settings['deactivate_datahub___1'])) {
            $message .= '<div>The Data Hub feature of this Hub is activated, but the Data Model Browser sub-component is not installed.</div><br>';
        }

        $missingProjectsArray = ProjectData::checkIfMissingProjects($pidsArray);
        if (!empty($missingProjectsArray)) {
            $message .= '<div>This Hub installation is missing the following projects:';
            $projectTitles = REDCapManagement::getProjectsTitlesArray();
            $$message .= '<ul>';
            foreach ($missingProjectsArray as $index => $missingProject) {
                $message .= '<li>' . htmlspecialchars($projectTitles[$index], ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $message .= '</ul></div><br>';

        }
        return $message;
    }

    public static function canUserEditSQLFields($module, $fieldType){
        if($fieldType != "sql" || ($fieldType == "sql" && $module->isSuperUser())){
            return true;
        }
        return false;
    }

    public static function getAdminEmails($module){
        $adminNotifications = $module->getProjectSetting('admin-notifications');
        return is_array($adminNotifications) ? implode(", ", $adminNotifications) : "";
    }

    public static function getREDCapAdminMailTo($module, $hub_name){
        $subject = "REDCap admin needed to apply changes to ".$hub_name." Hub";
        $hubUpdatesUrl = $module->getUrl("hub-updates/index.php", true);
        $message = "A REDCap admin is needed to apply several changes to the ".$hub_name." Hub.\n\n";
        $message .= "Click here to access ".$hub_name." Hub Updates: " . $hubUpdatesUrl;
        $encodedMessage = rawurlencode($message);
        return "mailto:".self::getAdminEmails($module)."?subject=".$subject."&body=".$encodedMessage;
    }
}

?>
