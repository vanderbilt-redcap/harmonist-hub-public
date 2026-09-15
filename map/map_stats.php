<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
require_once dirname(dirname(__FILE__))."/projects.php";

// Load and cache external files
$iso2Path = $module->getSafePath('map/countryCodeConverter/iso2.json');
$namesPath = $module->getSafePath('map/countryCodeConverter/names.json');
$codes = json_decode(file_get_contents($iso2Path), true);
$names = json_decode(file_get_contents($namesPath), true);

// Create ISO3-to-name mapping
$iso3_to_name = array_map(fn($iso3) => $names[$iso3] ?? '', $codes);

// Fetch regions where [showregion_y] = 1
$regionstbl = $module->escape(\REDCap::getData(
    $pidsArray['REGIONS'],
    'json-array',
    null,
    null,
    null,
    null,
    false,
    false,
    false,
    "[showregion_y] = 1"
));

// Sort regions by 'region_code'
ArrayFunctions::array_sort_by_column($regionstbl, 'region_code');

// Prepare region-based data
$regionCountryArray = [];
$colorCountryArray = [];
$totalAreasByRegionZoom = [];
$totalLegend = [];
foreach ($regionstbl as $region) {
    if ($region['region_tbl_option'] == 0) {
        $regionCode = $region['region_code'];
        $regionCountryArray[$regionCode] = $region['region_legend'];
        $colorCountryArray[$regionCode] = $region['region_color'];

        // Build legend
        $totalLegend[] = [
            'color' => $region['region_color'],
            'title' => $region['region_legend']
        ];

        // Zoom and location data
        $totalAreasByRegionZoom[$regionCode] = [
            'latitude' => $region['region_latitude'],
            'longitude' => $region['region_longitude'],
            'zoom' => $region['region_zoom']
        ];
    }
}

// Initialize arrays
$totalLocations = [];
$totalAreas = [];
$totalAreasByRegion = array_fill_keys(array_column($regionstbl, 'region_code'), []);

// Fetch TBLCENTERREVISED data
$RecordSetTableTBLC = $module->escape(\REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null));

// SVG Icon Path (kept for reference; we are using circles in amCharts 5 right now)
$icon = "M 200 175 A 25 25 0 0 0 182.322 217.678 M 200 175 A 25 25 0 1 0 217.678 217.678 M 200 175 A 25 25 0 0 1 217.678 217.678";

// Process TBLCENTERREVISED data
foreach ($RecordSetTableTBLC as $data) {
    $regionCode = $data['region'];
    $countryCodeIso3 = $codes[$data['country']] ?? null;

    // Validate and process records
    if (
        isset($totalAreasByRegion[$regionCode], $countryCodeIso3) &&
        !empty($data['geocode_lat']) &&
        !empty($data['geocode_lon']) &&
        (empty($data['drop_center']) || !in_array($data['drop_center'], $data))
    ) {
        // Map Locations
        $totalLocations[] = [
            'latitude' => $data['geocode_lat'],
            'longitude' => $data['geocode_lon'],
            'svgPath' => $icon,
            'scale' => '0.15',
            'color' => '#4d4d4d',
            'rollOverColor' => '#000000',
            'backgroundColor' => '#000000',
            'rollOverScale' => '1.5',
            // IMPORTANT: use \n (not <br/>) so amCharts 5 tooltip does not show HTML tags
            'description' => $data['program'] . "\nClinic: " . $data['adultped'],
            'title' => $data['name'],
            'zoomLevel' => '5',
            'alpha' => '0.7'
        ];

        // Map Areas
        $color = $colorCountryArray[$regionCode];
        $adjustedColor = adjustColorLightenDarken($color, "30");
        $area = [
            'id' => $countryCodeIso3,
            'region' => $regionCode,
            'color' => $color,
            'title' => $regionCountryArray[$regionCode] . ' - ' . ($iso3_to_name[$countryCodeIso3] ?? ''),
            'selectedColor' => $adjustedColor,
            'rollOverColor' => $adjustedColor
            // If you want old group-hover behavior, add: 'groups' => ['someGroup']
        ];

        $totalAreas[] = $area;
        $totalAreasByRegion[$regionCode][] = $area;
    }
}

// Encode data into JSON for use in JavaScript
$jsonLocationData = json_encode($totalLocations);
$jsonAreaData = json_encode($totalAreas);
$jsonTotalAreasByRegionData = json_encode($totalAreasByRegion);
$totalAreasByRegionZoomJson = json_encode($totalAreasByRegionZoom);
$jsonLegendData = json_encode($totalLegend);
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        var jsonLocationData = <?= $jsonLocationData ?>;
        var jsonAreaDataAll = <?= $jsonAreaData ?>;
        var jsonAreaData = <?= $jsonTotalAreasByRegionData ?>;
        var jsonLegendData = <?= $jsonLegendData ?>;
        var totalAreasByRegionZoom = <?= $totalAreasByRegionZoomJson ?>;

        // amCharts 5 chart handle (set in map_regioncolor.js)
        window.__am5chart = null;

        // region zoom: amCharts 5 uses a different zoom scale than amCharts 3,
        // so we apply a multiplier to make it "zoom in enough".
        function setDataset(regionCode) {
            const chart = window.__am5chart;
            if (!chart) return;

            // if cleared, return to current Home
            if (!regionCode) {
                chart.goHome();
                return;
            }

            const z = totalAreasByRegionZoom && totalAreasByRegionZoom[regionCode];
            if (!z) return;

            const factor = 3; // tune
            const homePoint = { longitude: Number(z.longitude), latitude: Number(z.latitude) };
            const homeZoom  = Math.max(1, Number(z.zoom) * factor);

            // zoom to the selected region
            chart.zoomToGeoPoint(homePoint, homeZoom, true);

            // make Home match this selection
            chart.set("homeGeoPoint", homePoint);
            chart.set("homeZoomLevel", homeZoom);

            // track current selection (optional)
            window.initialRegionCode = regionCode;
        }
    </script>

    <!-- amCharts 5 CDN -->
    <script src="<?=$module->getUrl("map/index.js")?>"></script>
    <script src="<?=$module->getUrl("map/map.js")?>"></script>
    <script src="<?=$module->getUrl("map/worldLow.js")?>"></script>
    <script src="<?=$module->getUrl("map/Animated.js")?>"></script>
    <script src="<?=$module->getUrl("map/exporting.js")?>"></script>
    <script src="<?=$module->getUrl("map/html2canvas.min.js")?>"></script>

    <!-- Your amCharts 5 map code -->
    <script type="text/javascript" src="<?= $module->getUrl('map/map_regioncolor.js'); ?>"></script>

    <style>
        /* amCharts 5 does not use .amChartsLegend class; legend is configured in JS */
    </style>
</head>
<body>

<div id="mapwrap" style="width:800px; margin:auto; margin-bottom:20px;">
    <div id="chartdiv" style="width:800px; height:400px;"></div>
    <div id="legenddiv" style="width:800px; height:100px;"></div>
</div>

</body>
</html>
