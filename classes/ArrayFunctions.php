<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;


class ArrayFunctions
{
    public static function array_diff_assoc_recursive($array1, $array2) {
        $difference=array();
        foreach($array1 as $key => $value) {
            if( is_array($value) ) {
                if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::array_diff_assoc_recursive($value, $array2[$key]);
                    if( !empty($new_diff) )
                        $difference[$key] = $new_diff;
                }
            } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }

    public static function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key => $row) {
            $sort_col[$key] = $row[$col] ?? null;
        }
        array_multisort($sort_col, $dir, $arr);
    }

    public static function csvToArray($csvFile) {
        $data = [];
        if (($handle = fopen($csvFile, 'r')) !== false) {
            $headers = fgetcsv(stream: $handle, escape: "\\");

            while (($row = fgetcsv(stream: $handle, escape: "\\")) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }
}
?>
