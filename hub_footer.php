<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;
use Vanderbilt\HarmonistHubPublicExternalModule\ProjectData;

// Gather footer data
$aboutData = \REDCap::getData($pidsArray['ABOUT'], 'json-array', null);
$aboutTitle = "";
if (array_key_exists(0, $aboutData) && is_array($aboutData[0]) && array_key_exists('about_title', $aboutData[0])) {
    $aboutTitle = $aboutData[0]['about_title'];
}
$versionsByPrefix = $module->getEnabledModules($_GET['pid']);

// Render the footer template
echo $module->getTwig()->render('footer.html.twig', [
    'indexUrl' => $indexUrl,
    'versionsByPrefix' => $versionsByPrefix,
    'aboutTitle' => $aboutTitle,
    'settings' => $settings,
]);

?>

