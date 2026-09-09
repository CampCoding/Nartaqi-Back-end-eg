<?php

namespace App\Http\Controllers;

class JsonArrayFromUnitsFile
{
    /**
     * Build a MySQL JSON_ARRAY(...) from a JSON file like units_data_from_sheet.json
     *
     * Usage (example in tinker):
     * >>> echo App\\Http\\Controllers\\JsonArrayFromUnitsFile::buildSqlJsonArray('C:/Users/ZDevoloper/Downloads/units_data_from_sheet.json');
     */
    public static function buildSqlJsonArray(string $absolutePath): string
    {
        if (! is_file($absolutePath)) {
            return '/* File not found: ' . addslashes($absolutePath) . ' */';
        }

        $raw = file_get_contents($absolutePath);
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return '/* Invalid JSON payload */';
        }

        $fragments = [];
        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }

            // normalize keys to lower-case; ensure consistent ordering
            $row = array_change_key_case($row, CASE_LOWER);
            $ordered = [
                'unit_id' => (string)($row['unit_id'] ?? ''),
                'administrative_expenses' => (float)($row['administrative_expenses'] ?? 0),
                'legal_expenses' => (float)($row['legal_expenses'] ?? 0),
                'maintenance_expenses' => (float)($row['maintenance_expenses'] ?? 0),
                'cleaning' => (float)($row['cleaning'] ?? 0),
                'car_wash' => (float)($row['car_wash'] ?? 0),
                'plant_maintenance' => (float)($row['plant_maintenance'] ?? 0),
                'private_security' => (float)($row['private_security'] ?? 0),
                'building_cleaning' => (float)($row['building_cleaning'] ?? 0),
                'private_swimming_pool' => (float)($row['private_swimming_pool'] ?? 0),
                'room_usage' => (float)($row['room_usage'] ?? 0),
                'camera_fund' => (float)($row['camera_fund'] ?? 0),
                'violations' => (float)($row['violations'] ?? 0),
                'association_fines' => (float)($row['association_fines'] ?? 0),
                'value_of_garages' => (float)($row['value_of_garages'] ?? 0),
                'golf_development_fund' => (float)($row['golf_development_fund'] ?? 0),
                'road_paving_fund' => (float)($row['road_paving_fund'] ?? 0),
                'painting_fund' => (float)($row['painting_fund'] ?? 0),
                'swimming_pool_construction_fund_a_v1' => (float)($row['swimming_pool_construction_fund_a_v1'] ?? 0),
                'fine_for_swimming_pool_construction' => (float)($row['fine_for_swimming_pool_construction'] ?? 0),
                'administrative_expenses_for_paving' => (float)($row['administrative_expenses_for_paving'] ?? 0),
                'administrative_expenses_for_painting' => (float)($row['administrative_expenses_for_painting'] ?? 0),
                'general_paving_2025' => (float)($row['general_paving_2025'] ?? 0),
                'painting_2025' => (float)($row['painting_2025'] ?? 0),
                'palmyra_fund' => (float)($row['palmyra_fund'] ?? 0),
                'palmyra_stations_fund' => (float)($row['palmyra_stations_fund'] ?? 0),
                'celia_fund' => (float)($row['celia_fund'] ?? 0),
                'development_fund_1_right' => (float)($row['development_fund_1_right'] ?? 0),
                'camera_fund_e' => (float)($row['camera_fund_e'] ?? 0),
                'investors_swimming_pool_fund' => (float)($row['investors_swimming_pool_fund'] ?? 0),
                'golf_irrigation_fund' => (float)($row['golf_irrigation_fund'] ?? 0),
            ];

            $pairs = [];
            foreach ($ordered as $k => $v) {
                $val = is_string($v) ? "'" . addslashes($v) . "'" : (is_nan($v) ? 0 : $v);
                $pairs[] = "'" . $k . "', " . $val;
            }
            $fragments[] = 'JSON_OBJECT(' . implode(', ', $pairs) . ')';
        }

        if (empty($fragments)) {
            return 'JSON_ARRAY()';
        }

        return "JSON_ARRAY(\n  " . implode(",\n  ", $fragments) . "\n)";
    }
}


