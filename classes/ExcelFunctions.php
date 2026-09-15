<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;


class ExcelFunctions
{
    public static function getExcelHeaders($sheet,$headers,$letters,$width,$row_number){
        foreach ($headers as $index=>$header) {
            $sheet->setCellValue($letters[$index] . $row_number, $header);
            $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('4db8ff');
            $sheet->getStyle($letters[$index].$row_number)->getFont()->setBold( true );
            $sheet->getStyle($letters[$index].$row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
            $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setHorizontal('center');
            $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setWrapText(true);

            $sheet->getColumnDimension($letters[$index])->setAutoSize(false);
            $sheet->getColumnDimension($letters[$index])->setWidth($width[$index]);
        }
        return $sheet;
    }

    public static function getExcelData($sheet,$data_array,$headers,$letters,$section_centered,$row_number,$option){
        $active_n_found = false;
        foreach ($data_array as $row => $data) {
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($letters[$index].$row_number, $data[$index]);
                $sheet->getStyle($letters[$index].$row_number)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setWrapText(true);
                if($section_centered[$index] == "1"){
                    $sheet->getStyle($letters[$index].$row_number)->getAlignment()->setHorizontal('center');
                }
                if($option == "1"){
                    if ($index == "11" && $data[$index] == "N") {
                        $active_n_found = true;
                        $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('e6e6e6');
                    }
                }
            }
            if( $active_n_found && $option == '1'){
                foreach ($headers as $index=>$header) {
                    $sheet->getStyle($letters[$index] . $row_number)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle($letters[$index].$row_number)->getFill()->getStartColor()->setARGB('e6e6e6');
                }
                $active_n_found = false;
            }
            $row_number++;
        }
        return $sheet;
    }

    // SECTION HEADERS LETTERS
    public static function generateExcelColumnLetters($size) {
        $letters = [];
        for ($i = 0; $i < $size; $i++) {
            $letters[] = self::excelColumnLetter($i + 1); // Generate column letters dynamically
        }
        return $letters;
    }

    // Helper function to generate Excel column letters (e.g., A, B, ..., Z, AA, AB, ...)
    public static function excelColumnLetter($number) {
        $letter = '';
        while ($number > 0) {
            $number--; // Adjust for 0-based index
            $letter = chr($number % 26 + 65) . $letter;
            $number = intval($number / 26);
        }
        return $letter;
    }

    public static function sanitizeExcelData($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}