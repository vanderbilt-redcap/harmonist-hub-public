<?php

namespace Vanderbilt\HarmonistHubPublicExternalModule;

$show_donuts_single = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'hub_stats_consortium_select', $settings, "chart");
$manuscripts_data = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'pub_data', $settings);
$abstracts_data = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'abs_data', $settings);
$activity_data = ProjectData::getCheckboxValuesAsArray($module, $pidsArray['SETTINGS'], 'activity_data', $settings);

// Instantiate Metrics class
$metrics = new Metrics($module, $pidsArray['PROJECTS'], $defaultValues);

/*** Consortium Productivity - DONUTS DATA ***/
// CONCEPTS BY STATUS DONUT
$conceptsDonutData = $metrics->getConceptsByStatusDonutData();

$concepts_labels = $conceptsDonutData['labels'];
$concepts_values = $conceptsDonutData['values'];
$concepts_colors = $conceptsDonutData['colors'];

// CONCEPTS BY WORKING GROUP DONUT
$conceptsWGDonutData = $metrics->getConceptsByWorkingGroupDonutData();

$conceptswg_labels = $conceptsWGDonutData['labels'];
$conceptswg_values = $conceptsWGDonutData['values'];
$conceptswg_colors = $conceptsWGDonutData['colors'];
$conceptswg_short_label = $conceptsWGDonutData['shortLabel'];
$conceptswg_short_label_index = $conceptsWGDonutData['shortLabelIndex'];

// REVIEW REQUESTS DONUT
$requestData = $metrics->getRequestsDonutData();

$requests_labels = $requestData['labels'];
$requests_values = $requestData['values'];
$requests_colors = $requestData['colors'];

/*** Regional and MR (publications & abstracts) - BAR GRAPH & TABLE ***/
$regionalmrdata = [];

// Iterate over concept types
foreach ($metrics::CONCEPT_TYPE as $output_type => $type) {
    // Use Metrics class method to get regional and MR data
    ${"regionalmrdata_" . $type} = $metrics->getRegionalAndMR(
        $pidsArray['EXTRAOUTPUTS'],
        $type,
        $regionalmrdata,
        $settings['oldestyear_rmr_' . $type],
        $output_type,
        ${$type . "_data"}
    );

    // Call the function to get RMR table data (assuming this function is still standalone)
    ${"data_" . $type} = $metrics->getDataRMRTable(${"regionalmrdata_" . $type}['outputs']);
}

// Define color schemes
$regionalmrpubs_color_manuscripts = ['#f5a549', '#d1691f'];
$regionalmrpubs_color_abstracts = ['#6ddc9c', '#3c9d68'];

/*** Multi-regional Activity by Year - LINE GRAPH ***/
//Initialize concept years
$yearsData = $metrics->initializeConceptYears($settings['oldestyear_concepts']);
$years_label_concepts = $yearsData['labels'];
$concept_years = $yearsData['data'];

//Populate concept years with concepts data
$concept_years = $metrics->populateConceptYears($concept_years);

//Populate MR data requests
$concept_years = $metrics->populateMRDataRequests($concept_years);

//Extract visualization data
$visualizationData = $metrics->extractVisualizationData($concept_years);
$iedea_concepts = $visualizationData['concepts'];
$iedea_manuscripts = $visualizationData['manuscripts'];
$iedea_abstracts = $visualizationData['abstracts'];
$iedea_mrdatarequests = $visualizationData['mrdatarequests'];

// Define sections and titles for visualization or further processing
$array_sections_all = ['concepts', 'conceptswg', 'requests'];
$array_sections_title_all = [
    'concepts by status',
    'concepts by Working Group',
    'Hub Review Requests'
];

#Escape All Data
$requests_values = $module->escape($requests_values);
$requests_labels = array_values($module->escape($requests_labels));
$requests_colors = $module->escape($requests_colors);
$array_sections_all = $module->escape($array_sections_all);
$array_sections_title_all = $module->escape($array_sections_title_all);
$concepts_values = $module->escape($concepts_values);
$concepts_labels = $module->escape($concepts_labels);
$concepts_colors = $module->escape($concepts_colors);
$conceptswg_values = $module->escape($conceptswg_values);
$conceptswg_labels = $module->escape($conceptswg_labels);
$conceptswg_colors = $module->escape($conceptswg_colors);
$conceptswg_short_label_index = $module->escape($conceptswg_short_label_index);
$conceptswg_short_label = $module->escape($conceptswg_short_label);
$conceptsleadregion_values = $module->escape($conceptsleadregion_values);
$conceptsleadregion_labels = $module->escape($conceptsleadregion_labels);
$regionalmrdata_manuscripts = $module->escape($regionalmrdata_manuscripts);
$regionalmrpubs_color_abstracts = $module->escape($regionalmrpubs_color_abstracts);
$years_label_concepts = $module->escape($years_label_concepts);
$iedea_concepts = $module->escape($iedea_concepts);
$iedea_manuscripts = $module->escape($iedea_manuscripts);
$iedea_abstracts = $module->escape($iedea_abstracts);
$iedea_mrdatarequests = $module->escape($iedea_mrdatarequests);
?>
<script>
    $(document).ready(function() {
        var showChar = 200;
        var ellipsestext = "...";
        var moretext = "more";
        var lesstext = "less";
        $('.more').each(function() {
            var content = $(this).html();

            if(content.length > showChar) {

                var snippetContent = content.substr(0, showChar);
                var allContent = content.substr(showChar, content.length - showChar);

                var html = snippetContent + '<span class="moreellipses">' + ellipsestext+ '&nbsp;</span><span class="morecontent"><span>' + allContent + '</span>&nbsp;&nbsp;<a href="" class="morelink">' + moretext + '</a></span>';

                $(this).html(html);
            }

        });

        $(".morelink").click(function(){
            if($(this).hasClass("less")) {
                $(this).removeClass("less");
                $(this).html(moretext);
            } else {
                $(this).addClass("less");
                $(this).html(lesstext);
            }
            $(this).parent().prev().toggle();
            $(this).prev().toggle();
            return false;
        });
    });
    function createBase64Chart(chart,url){
        // var url = donuts_chart.toBase64Image();
        var url_base64 = document.getElementById(chart).toDataURL('image/png');
        var url_base64 = save64Img(chart.toBase64Image());
    }
    $(function () {
        var show_donuts = <?=json_encode($settings['hub_stats_section1_y'])?>;
        var show_donuts_single = <?=json_encode($show_donuts_single)?>;
        var show_publications = <?=json_encode($settings['hub_stats_section2_y'])?>;
        var show_manuscripts_single = <?=json_encode($manuscripts_data)?>;
        var show_manuscripts_single_label1 = <?=json_encode(($settings['pub_data_label1'] ?: $defaultValuesSettings['pub_data_label1']))?>;
        var show_manuscripts_single_label2 = <?=json_encode(($settings['pub_data_label2'] ?: $defaultValuesSettings['pub_data_label2']))?>;
        var show_abstracts = <?=json_encode($settings['hub_stats_section3_y'])?>;
        var show_abstracts_single = <?=json_encode($abstracts_data)?>;
        var show_abstracts_single_label1 = <?=json_encode(($settings['abs_data_label'] ?: $defaultValuesSettings['abs_data_label']))?>;
        var show_abstracts_single_label2 = <?=json_encode(($settings['abs_data_label2'] ?: $defaultValuesSettings['abs_data_label2']))?>;
        var show_activity = <?=json_encode($settings['hub_stats_section4_y'])?>;
        var show_activity_single = <?=json_encode($activity_data)?>;

        var url = <?=json_encode($module->getUrl('index.php').'&NOAUTH&option=cpt')?>;

        var array_sections_all = <?=json_encode($array_sections_all)?>;
        var array_sections_title_all = <?=json_encode($array_sections_title_all)?>;

        //Consortium Productivity
        var conceptsleadregion_values = <?=json_encode($conceptsleadregion_values)?>;
        var conceptsleadregion_labels = <?=json_encode($conceptsleadregion_labels)?>;
        var conceptsleadregion_colors = <?=json_encode($conceptswg_colors)?>;

        //Multiregional and Regional Publications
        var concept_type = <?=json_encode($metrics::CONCEPT_TYPE)?>;
        var regionalmrpubs_mrw_manuscripts = <?=json_encode($regionalmrdata_manuscripts['mrw'])?>;
        var regionalmrpubs_r_manuscripts = <?=json_encode($regionalmrdata_manuscripts['r'])?>;
        var regionalmrpubs_color_manuscripts = <?=json_encode(array_values($regionalmrpubs_color_manuscripts))?>;

        //Multiregional and Regional Abstracts
        var regionalmrpubs_mrw_abstracts = <?=json_encode($regionalmrdata_abstracts['mrw'])?>;
        var regionalmrpubs_r_abstracts = <?=json_encode($regionalmrdata_abstracts['r'])?>;
        var regionalmrpubs_color_abstracts = <?=json_encode(array_values($regionalmrpubs_color_abstracts))?>;

        //Multi-regional Activity by Year
        const multiregionalActivityByYear = {
            concepts:<?=json_encode($iedea_concepts)?>,
            manuscripts:<?=json_encode($iedea_manuscripts)?>,
            abstracts:<?=json_encode($iedea_abstracts)?>,
            mrdatarequests:<?=json_encode($iedea_mrdatarequests)?>
        }
        var years_label_concepts = <?=json_encode($years_label_concepts)?>;

        const sectionsAll = {
            concepts:{
                values: <?=json_encode($concepts_values)?>,
                labels: <?=json_encode($concepts_labels)?>,
                colors: <?=json_encode($concepts_colors)?>
            },
            conceptswg:{
                values: <?=json_encode($conceptswg_values)?>,
                labels: <?=json_encode($conceptswg_labels)?>,
                colors: <?=json_encode($conceptswg_colors)?>,
                short_label: <?=json_encode($conceptswg_short_label)?>,
                short_label_index: <?=json_encode($conceptswg_short_label_index)?>
            },
            requests:{
                values: <?=json_encode($requests_values)?>,
                labels: <?=json_encode($requests_labels)?>,
                colors: <?=json_encode($requests_colors)?>
            },
            manuscripts:{
                years_label: <?=json_encode($regionalmrdata_abstracts['years'])?>,
                single: <?=json_encode($manuscripts_data)?>,
                label1: <?=json_encode(($settings['pub_data_label1'] ?: $defaultValuesSettings['pub_data_label1']))?>,
                label2: <?=json_encode(($settings['pub_data_label2'] ?: $defaultValuesSettings['pub_data_label2']))?>,
                regionalmrpubs_mr: <?=json_encode(array_values($regionalmrdata_manuscripts['mr']))?>,
                regionalmrpubs_outputs: <?=json_encode($regionalmrdata_manuscripts['outputsAll'])?>,
                regionalmrpubs_color: <?=json_encode(array_values($regionalmrpubs_color_manuscripts))?>
            },
            abstracts:{
                years_label: <?=json_encode($regionalmrdata_manuscripts['years'])?>,
                single: <?=json_encode($abstracts_data)?>,
                label1: <?=json_encode(($settings['abs_data_label'] ?: $defaultValuesSettings['abs_data_label']))?>,
                label2: <?=json_encode(($settings['abs_data_label2'] ?: $defaultValuesSettings['abs_data_label2']))?>,
                regionalmrpubs_mr: <?=json_encode(array_values($regionalmrdata_abstracts['mr']))?>,
                regionalmrpubs_outputs: <?=json_encode($regionalmrdata_abstracts['outputsAll'])?>,
                regionalmrpubs_color: <?=json_encode(array_values($regionalmrpubs_color_abstracts))?>
            }
        }
        //DONUTS
        if(show_donuts == "1") {
            Object.keys(array_sections_all).forEach(function (section) {
                if(show_donuts_single[(parseInt(section))] == '1') {
                    var ctx = $("#" + array_sections_all[section] + "Chart");
                    if (array_sections_all[section] == 'conceptswg') {
                        const customTooltips = function (context) {
                            const { chart, tooltip } = context; // Extract chart and tooltip objects

                            // Tooltip Element
                            let tooltipEl = document.getElementById('chartjs-tooltip');

                            // Create the tooltip element if it doesn't exist
                            if (!tooltipEl) {
                                tooltipEl = document.createElement('div');
                                tooltipEl.id = 'chartjs-tooltip';
                                tooltipEl.style.position = 'absolute';
                                tooltipEl.style.pointerEvents = 'auto'; // Allow interaction
                                tooltipEl.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
                                tooltipEl.style.color = '#fff';
                                tooltipEl.style.borderRadius = '5px';
                                tooltipEl.style.padding = '10px';
                                tooltipEl.style.zIndex = '1000';
                                tooltipEl.style.transition = 'all 0.1s ease';
                                tooltipEl.style.display = 'none'; // Initially hidden
                                document.body.appendChild(tooltipEl);
                            }

                            // If no tooltip is visible, do nothing unless hovering over a section
                            if (tooltip.opacity === 0) {
                                return;
                            }

                            // Show the tooltip
                            tooltipEl.style.opacity = 1;
                            tooltipEl.style.pointerEvents = 'auto';
                            tooltipEl.style.display = 'block'; // Make it visible

                            // Set tooltip content (label with a link and color square)
                            if (tooltip.body) {
                                const title = tooltip.title || [];
                                const bodyLines = tooltip.body.map(item => item.lines);

                                let innerHtml = '<div style="position: relative; text-align: left;">';

                                // Add the `x` button in the top-right corner
                                innerHtml += `<button class="closeWG">x</button>`;

                                // Add the labels with the clickable links and color squares
                                title.forEach((titleItem, i) => {
                                    const colors = tooltip.labelColors[i]; // Get the color for the hovered section
                                    const style = `background:${colors.backgroundColor}; width:12px; height:12px; display:inline-block; margin-right:5px; border-radius:2px;`; // Square styling

                                    // Link logic from the first example
                                    const label = titleItem;
                                    let labelIndex = "";
                                    let labelLong = "";
                                    Object.keys(sectionsAll[array_sections_all[section]]['short_label_index']).forEach(function (typeId) {
                                        if (sectionsAll[array_sections_all[section]]['short_label_index'][typeId] == label) {
                                            if (typeId == "") {
                                                labelLong = "No WG";
                                            } else {
                                                Object.keys(sectionsAll[array_sections_all[section]]['short_label']).forEach(function (index) {
                                                    if (sectionsAll[array_sections_all[section]]['short_label'][index] == label) {
                                                        labelLong = sectionsAll[array_sections_all[section]]['labels'][index];
                                                    }
                                                });

                                            }
                                            labelIndex = typeId;
                                        }
                                    });
                                    const custom_url = url + '&type=' + labelIndex; // Build dynamic URL
                                    // Add the label and link
                                    innerHtml += `<div class="linkWGWrapper">
                            <span style="${style}"></span>
                            <a href="${custom_url}" target="_blank" class="linkWG">${labelLong}</a>
                          </div>`;
                                });
                                innerHtml += '</div>';

                                tooltipEl.innerHTML = innerHtml;

                                // Attach the event listener to the close button
                                const closeButton = tooltipEl.querySelector('.closeWG');
                                if (closeButton) {
                                    closeButton.addEventListener('click', () => {
                                        tooltipEl.style.display = 'none'; // Hide the tooltip completely
                                    });
                                }
                            }

                            // Position the tooltip
                            const position = chart.canvas.getBoundingClientRect();
                            tooltipEl.style.left = position.left + window.scrollX + tooltip.caretX + 'px';
                            tooltipEl.style.top = position.top + window.scrollY + tooltip.caretY + 'px';
                        };

                        // Function to hide the tooltip when clicking the close button
                        const hideTooltip = () => {
                            const tooltipEl = document.getElementById('chartjs-tooltip');
                            if (tooltipEl) {
                                tooltipEl.style.display = 'none'; // Hide the tooltip completely
                            }
                        };

                        var config = {
                            type: 'doughnut',
                            data: {
                                labels: sectionsAll[array_sections_all[section]]["short_label"],
                                datasets: [{
                                    backgroundColor: sectionsAll[array_sections_all[section]]["colors"],
                                    data: sectionsAll[array_sections_all[section]]["values"]
                                }]
                            },
                            options: {
                                responsive: false, // Ensure chart does not resize automatically
                                maintainAspectRatio: false, // Prevent aspect ratio enforcement
                                layout: {
                                    padding: 60 // Add padding around the chart to avoid clipping
                                },
                                plugins: {
                                    datalabels: {
                                        color: '#000',
                                        formatter: function (value, context) {
                                            // Customize label content (e.g., show percentage or value)
                                            const label = context.chart.data.labels[context.dataIndex];
                                            return `${label}`;
                                        },
                                        font: {
                                            size: 8,
                                            style: 'normal'
                                        },
                                        anchor: 'end', // Positioning: Center anchor
                                        align: 'end', // Positioning: Center alignment
                                        padding: 5, // Add padding around the labels
                                        clamp: true, // Prevent labels from being cut off
                                        position: 'outside' // Position the labels outside the chart
                                    },
                                    legend: {
                                        display: false // Disable the legend
                                    },
                                    tooltip: {
                                        enabled: false, // Disable default Chart.js tooltips
                                        external: customTooltips // Use external custom tooltip handler
                                    }
                                },
                                animation: {
                                    onComplete: function () {
                                        // Export the chart as an image after rendering
                                        document.querySelector('#down' + array_sections_all[section])
                                            .setAttribute('href', this.toBase64Image());
                                    }
                                }
                            },
                            plugins: [ChartDataLabels] // Register the datalabels plugin
                        };

                        // Create the chart
                        var donuts_chart = new Chart(ctx, config);
                    } else {
                        var config = {
                            type: 'doughnut',
                            data: {
                                labels: sectionsAll[array_sections_all[section]]["labels"],
                                datasets: [{
                                    backgroundColor: sectionsAll[array_sections_all[section]]["colors"],
                                    data: sectionsAll[array_sections_all[section]]["values"]
                                }]
                            },
                            options: {
                                responsive: false, // Ensure chart does not resize automatically
                                maintainAspectRatio: false, // Prevent aspect ratio enforcement
                                layout: {
                                    padding: 60 // Add padding around the chart to avoid clipping
                                },
                                plugins: {
                                    title: {
                                        display: false,
                                        position: "top",
                                        text: array_sections_title_all[section].toUpperCase(),
                                        font: {
                                            size: 18, // Font size for title
                                            weight: "bold", // Font weight
                                            family: "'Arial', sans-serif" // Font family
                                        },
                                        color: "#111" // Title color
                                    },
                                    datalabels: {
                                        labels: {
                                            index: {
                                                color: '#000',
                                                font: {
                                                    size: 8,
                                                },
                                                formatter: (val, ctx) => ctx.chart.data.labels[ctx.dataIndex],
                                                align: 'end',
                                                anchor: 'end',
                                            },
                                            value: {
                                                color: '#fff',
                                                padding: 0,
                                                align: 'center',
                                            },
                                        },
                                    },
                                    legend: {
                                        display: false // Hide the legend
                                    },
                                    tooltip: {
                                        mode: 'dataset'
                                    }
                                },
                                animation: {
                                    onComplete: function () {
                                        document.querySelector('#down' + array_sections_all[section]).setAttribute('href', this.toBase64Image());
                                    }
                                }
                            },
                            plugins: [ChartDataLabels] // Register the datalabels plugin
                        };
                        var donuts_chart = new Chart(ctx, config);
                    }
                }
                Chart.defaults.defaultFontStyle = 'bold';
            });
        }

        //MULTIREGIONAL & REGIONAL PUBLICATIONS / ABSTRACTS
        Object.keys(concept_type).forEach(function (section) {
            if((show_publications == '1' && section == '1') || (show_abstracts == '1' && section == "2")) {
                var dataset = [];
                if(sectionsAll[concept_type[section]]["single"]['0'] == '1'){
                    dataset.push(
                        {
                            label: sectionsAll[concept_type[section]]["label1"],
                            data: sectionsAll[concept_type[section]]["regionalmrpubs_mr"],
                            backgroundColor: sectionsAll[concept_type[section]]["regionalmrpubs_color"][0],
                            borderWidth: 0
                        }
                    );
                }
                if(sectionsAll[concept_type[section]]["single"]['1'] == '1'){
                    dataset.push(
                        {
                            label: sectionsAll[concept_type[section]]["label2"],
                            data: sectionsAll[concept_type[section]]["regionalmrpubs_outputs"],
                            backgroundColor: sectionsAll[concept_type[section]]["regionalmrpubs_color"][1],
                            borderWidth: 0
                        }
                    );
                }
                var ctxPubs = $("#" + concept_type[section] + "Chart");
                var configdataTimelineChart = {
                    type: 'bar', // Define the chart type as 'bar'
                    data: {
                        labels: sectionsAll[concept_type[section]]["years_label"], // X-axis labels
                        datasets: [] // Ensure to populate this with your data
                    },
                    options: {
                        responsive: true, // Make the chart responsive
                        plugins: {
                            legend: {
                                display: true, // Display legend
                                onHover: function (event, legendItem) {
                                    document.getElementById(concept_type[section] + "Chart").style.cursor = 'pointer';
                                },
                                onClick: function (e, legendItem) {
                                    var index = legendItem.datasetIndex;
                                    var ci = this.chart;
                                    var alreadyHidden = (ci.getDatasetMeta(index).hidden === null) ? false : ci.getDatasetMeta(index).hidden;

                                    ci.data.datasets.forEach(function (e, i) {
                                        var meta = ci.getDatasetMeta(i);
                                        if (i !== index) {
                                            if (!alreadyHidden) {
                                                meta.hidden = meta.hidden === null ? !meta.hidden : null;
                                            } else if (meta.hidden === null) {
                                                meta.hidden = true;
                                            }
                                        } else if (i === index) {
                                            meta.hidden = null;
                                        }
                                    });

                                    ci.update();
                                }
                            },
                            tooltip: {
                                mode: 'index', // Tooltip mode
                                intersect: false // Allow tooltips to show even when bars don’t intersect
                            }
                        },
                        animation: {
                            onComplete: function () {
                                document.querySelector('#down' + concept_type[section])
                                    .setAttribute('href', this.toBase64Image());
                            }
                        },
                        scales: {
                            x: { // X-axis configuration
                                stacked: true // Enable stacked bars on the X-axis
                            },
                            y: { // Y-axis configuration
                                stacked: true, // Enable stacked bars on the Y-axis
                                ticks: {
                                    stepSize: 10, // Set the step size for Y-axis ticks
                                    beginAtZero: true // Start Y-axis at zero
                                }
                            }
                        }
                    }
                };

                // Create the chart
                var communication_chart = new Chart(ctxPubs, configdataTimelineChart);

                Object.keys(dataset).forEach(function (index) {
                    communication_chart.data.datasets.push(dataset[index]);
                    communication_chart.update();
                });
            }
        });

        //MULTIREGIONAL ACTIVITY BY YEAR
        if(show_activity == '1') {
            var dataset = [];
            var activity_labels = ['New concepts','Manuscripts','Abstracts','MR Data Requests'];
            var activity_color = ['#337ab7','#ffa64d','#00b386','#bf80ff'];
            var activity_data = ['concepts','manuscripts','abstracts','mrdatarequests'];
            Object.keys(activity_data).forEach(function (index) {
                if (show_activity_single[parseInt(index)] == '1') {
                    dataset.push(
                        {
                            label: activity_labels[index],
                            data: multiregionalActivityByYear[activity_data[index]],
                            backgroundColor: activity_color[index],
                            borderColor: activity_color[index],
                            fill: false
                        }
                    );
                }
            });
            var ctx_iedea = $("#IedeaChart");
            var config_iedea = {
                type: 'line',
                data: {
                    labels: years_label_concepts,
                    datasets: []
                },
                options: {
                    responsive: true, // Makes the chart responsive
                    elements: {
                        line: {
                            tension: 0 // Disables bezier curves for straight lines
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index', // Group tooltips by index (shows tooltips for all datasets at the same X position)
                            intersect: false // Allows tooltips to show even if the cursor isn't directly over a point
                        }
                    },
                    animation: {
                        onComplete: function () {
                            // Export chart as an image
                            document.querySelector('#downmultiregionalyear')
                                .setAttribute('href', this.toBase64Image());
                        }
                    }
                }
            }

            var iedea_chart = new Chart(ctx_iedea, config_iedea);

            Object.keys(dataset).forEach(function (index) {
                iedea_chart.data.datasets.push(dataset[index]);
                iedea_chart.update();
            });
        }
    });
</script>
<div class="container">
    <div class="backTo">
        <a href="<?=$module->getUrl('index.php', true)?>">< Back to Home</a>
    </div>
</div>
<div class="container">
    <h3><?=$settings['hub_name']?> Metrics</h3>
    <p class="hub-title"><?=filter_tags(($settings['hub_statistics_text'] == "") ? $defaultValuesSettings['hub_statistics_text'] : $settings['hub_statistics_text'])?></p>
</div>

<!-- DONUTS -->
<?php if ($settings['hub_stats_section1_y'] == '1') {?>
<div class="container" style="padding-top: 60px">
    <h4><?=($settings['hub_stats_consortium_title'] == "") ? $defaultValuesSettings['hub_stats_consortium_title'] : $settings['hub_stats_consortium_title']?></h4>
    <p class="hub-title"><?=filter_tags(($settings['hub_stats_consortium'] == "") ? $defaultValuesSettings['hub_stats_consortium'] : $settings['hub_stats_consortium'])?></p>
</div>
<div class="container">
    <?php foreach ($array_sections_title_all as $index => $section) {
    	if ($index <= 2 && $settings['hub_stats_consortium_select___'.($index + 1)] == '1') {?>
        <div class="canvas_title"><?=$section?>
            <a href="#" download="<?=$array_sections_all[$index].".png"?>" class="fa fa-download" style="color:#8c8c8c;padding-left:10px;" id="<?="down".$array_sections_all[$index]?>" name="<?="down".$array_sections_all[$index]?>"></a>
        </div>
    <?php }
    	}?>
</div>
<div class="container">
    <?php foreach ($array_sections_all as $index => $section) {
    	$id = $section."Chart";
    	if ($settings['hub_stats_consortium_select___'.($index + 1)] == '1') {
    		if ($section == 'conceptswg') {
    			$idtool = $section."tooltip";
    			?><div id="<?=$idtool?>"></div><?php
    		}
    		?>
            <canvas id="<?=$id?>" class="canvas_statistics" width="360px" height="360px"></canvas>
    <?php }
    	}?>
</div>
<?php } ?>

<!-- PUBLICATIONS -->
<?php if ($settings['hub_stats_section2_y'] == '1') {?>
<div class="container pt-5">
    <h4>
        <?=($settings['hub_stats_pubs_title'] == "") ? $defaultValuesSettings['hub_stats_pubs_title'] : $settings['hub_stats_pubs_title']?>
        <a href="#" download="mr_r_publications.png" class="fa fa-download text-muted ms-2" id="downmanuscripts" name="downmanuscripts"></a>
    </h4>
    <p class="hub-title">
        <?=filter_tags(($settings['hub_stats_rmr_publications'] == "") ? $defaultValuesSettings['hub_stats_rmr_publications'] : $settings['hub_stats_rmr_publications'])?>
    </p>
</div>
<div class="container">
    <canvas id="manuscriptsChart" class="canvas_statistics" width="1100" height="310"></canvas>
</div>
<div class="mb-4"></div>
    <?php if ($settings['hub_stats_section2a_y'] == '1') {?>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title d-flex align-items-center">
                    <!-- Collapse Text -->
                    <a class="collapseText d-flex align-items-center flex-grow-1"
                       data-bs-toggle="collapse"
                       href="#collapse_manuscripts"
                       role="button"
                       aria-expanded="true"
                       aria-controls="collapse_manuscripts">
                        <?=($settings['hub_stats_pubs2_title'] == "") ? $defaultValuesSettings['hub_stats_pubs2_title'] : $settings['hub_stats_pubs2_title']?>
                    </a>

                    <!-- Badge -->
                    <span class="badge bg-primary ms-2"><?=$data_manuscripts['total']?></span>

                    <!-- Chevron Icon -->
                    <a class="collapseText toggle-icon ms-2 position-absolute end-0 pe-3"
                       data-bs-toggle="collapse"
                       href="#collapse_manuscripts"
                       role="button"
                       aria-expanded="true"
                       aria-controls="collapse_manuscripts">
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                    </a>
                </h6>
            </div>
            <div id="collapse_manuscripts" class="collapse table-no-borders">
                <table class="table sortable-theme-bootstrap" data-sortable id="sortable_table">
                    <thead>
                    <tr>
                        <th width="150px" class="text-center">Year</th>
                        <th width="150px" class="text-center">Total</th>
                        <th>Journal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php echo $data_manuscripts['content']; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="ps-3">
        <i class="fa fa-info-circle" aria-hidden="true"></i>
        <em>
            Table shows publications between <?=$settings['oldestyear_rmr_manuscripts']?> and the current year. Publications with no year listed are not shown.
        </em>
    </div>
    <?php } ?>
<?php } ?>

<!-- ABSTRACTS -->
<?php if ($settings['hub_stats_section3_y'] == '1') {?>
<div class="container pt-5">
    <h4>
        <?=($settings['hub_stats_abs_title'] == "") ? $defaultValuesSettings['hub_stats_abs_title'] : $settings['hub_stats_abs_title']?>
        <a href="#" download="mr_r_abstracts.png" class="fa fa-download text-muted ms-2" id="downabstracts" name="downabstracts"></a>
    </h4>
    <p class="hub-title">
        <?=filter_tags(($settings['hub_stats_rmr_abstratcs'] == "") ? $defaultValuesSettings['hub_stats_rmr_abstratcs'] : $settings['hub_stats_rmr_abstratcs'])?>
    </p>
</div>
<div class="container">
    <canvas id="abstractsChart" class="canvas_statistics" width="1100" height="310"></canvas>
</div>
<div class="mb-4"></div>
<?php if ($settings['hub_stats_section3a_y'] == '1') {?>
        <div class="container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title d-flex align-items-center">
                        <!-- Collapse Text -->
                        <a class="collapseText d-flex align-items-center flex-grow-1"
                           data-bs-toggle="collapse"
                           href="#collapse_abstracts"
                           role="button"
                           aria-expanded="true"
                           aria-controls="collapse_abstracts">
                            <?=($settings['hub_stats_abs2_title'] == "") ? $defaultValuesSettings['hub_stats_abs2_title'] : $settings['hub_stats_abs2_title']?>
                        </a>

                        <!-- Badge -->
                        <span class="badge bg-primary ms-2">
                            <?= $data_abstracts['total'] ?>
                        </span>

                        <!-- Chevron Icon -->
                        <a class="collapseText toggle-icon ms-2 position-absolute end-0 pe-3"
                           data-bs-toggle="collapse"
                           href="#collapse_abstracts"
                           role="button"
                           aria-expanded="true"
                           aria-controls="collapse_abstracts">
                            <i class="fa fa-chevron-down" aria-hidden="true"></i>
                        </a>
                    </h6>
                </div>
                <div id="collapse_abstracts" class="collapse table-no-borders">
                    <table class="table sortable-theme-bootstrap" data-sortable id="sortable_table">
                        <thead>
                        <tr>
                            <th width="150px" class="text-center">Year</th>
                            <th width="150px" class="text-center">Total</th>
                            <th>Conference</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php echo $data_abstracts['content']; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="ps-3">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <em>
                Table shows abstracts between <?=$settings['oldestyear_rmr_abstracts']?> and the current year. Abstracts with no year listed are not shown.
            </em>
        </div>
    <?php } ?>
<?php } ?>

<!-- ACTIVITY -->
<?php if ($settings['hub_stats_section4_y'] == '1') {?>
<div class="container pt-5">
    <h4>
        <?=$settings['hub_name']?>
        <?=($settings['hub_stats_activity_title'] == "") ? $defaultValuesSettings['hub_stats_activity_title'] : $settings['hub_stats_activity_title']?>
        <!-- Download icon updated -->
        <a href="#" download="multiregional_activity_year.png" class="fa fa-download text-muted ms-2" id="downmultiregionalyear" name="downmultiregionalyear"></a>
    </h4>
    <p class="hub-title">
        <?=filter_tags(($settings['hub_stats_mr_activity_year'] == "") ? $defaultValuesSettings['hub_stats_mr_activity_year'] : $settings['hub_stats_mr_activity_year'])?>
    </p>
</div>
<div class="container">
    <canvas id="IedeaChart" class="canvas_statistics" width="350" height="100"></canvas>
</div>
<?php } ?>

<!-- MAP -->
<?php if ($settings['hub_stats_section5_y'] == '1') {?>
<div class="container pt-5">
    <h4>
        <?=$settings['hub_name']?>
        <?=filter_tags(($settings['hub_stats_map_title'] == "") ? $defaultValuesSettings['hub_stats_map_title'] : $settings['hub_stats_map_title'])?>
    </h4>
    <p class="hub-title">
        <?=filter_tags(($settings['hub_stats_map'] == "") ? $defaultValuesSettings['hub_stats_map'] : $settings['hub_stats_map'])?>
    </p>
</div>
<div class="container pt-4">
    <?php include(dirname(dirname(__FILE__)).'/map/map_stats.php');?>
</div>
<script>
    $(document).ready(function() {
        setDataset("");
    } );
</script>
<?php } ?>
<?php if ($settings['hub_stats_section5a_y'] == '1') {?>
<div class="container pt-5">
    <h4>
        <?=$settings['hub_name']?>
        <?=filter_tags(($settings['hub_stats_sitelist_title'] == "") ? $defaultValuesSettings['hub_stats_sitelist_title'] : $settings['hub_stats_sitelist_title'])?>
    </h4>
    <p class="hub-title">
        <?=filter_tags(($settings['hub_stats_site_list'] == "") ? $defaultValuesSettings['hub_stats_site_list'] : $settings['hub_stats_site_list'])?>
    </p>
</div>

<div class="container">
    <?php
    $TBLCenter = \REDCap::getData($pidsArray['TBLCENTERREVISED'], 'json-array', null);
	$country = $module->getChoiceLabels('country', $pidsArray['TBLCENTERREVISED']);
	$region_name = $module->getChoiceLabels('region', $pidsArray['TBLCENTERREVISED']);

	$tbl_array = [];
	$regions_ordered = \REDCap::getData($pidsArray['REGIONS'], 'json-array', null, null, null, null, false, false, false, "[showregion_y] = '1'");
	ArrayFunctions::array_sort_by_column($regions_ordered, 'region_code');
	//To order the display
	foreach ($regions_ordered as $region) {
		$tbl_array[$region['region_code']]['country'] = [];
		$tbl_array[$region['region_code']]['center'] = [];
	}

	$tbl_adultped_array = [];
	$tbl_adultped_array['adultstotalcountry'] = [];
	$tbl_adultped_array['pedsstotalcountry'] = [];
	$tbl_array['country'] = [];
	foreach ($TBLCenter as $record) {
		if (($record['drop_center'] == "" || !array_key_exists('drop_center', $record)) && $record['region'] != "") {
			if (!array_key_exists($record['region'], $tbl_array)) {
				$tbl_array[$record['region']] = [];
				$tbl_array[$record['region']]['center'] = [];
				$tbl_array[$record['region']]['country'] = [];
			}
			$tbl_array[$record['region']]['sites'] += 1;
			$tbl_adultped_array['sites'] += 1;

			if ($record['country'] != "") {
				if (!array_key_exists($country[$record['country']], $tbl_array['country'])) {
					$tbl_array['country'][$country[$record['country']]] = 0;
				}
				$tbl_array['country'][$country[$record['country']]] += 1;

				if (!array_key_exists($country[$record['country']], $tbl_array[$record['region']]['country'])) {
					$tbl_array[$record['region']]['country'][$country[$record['country']]] = 0;
				}
				$tbl_array[$record['region']]['country'][$country[$record['country']]] += 1;
			}

			if ($record['center'] != "") {
				if (!array_key_exists($record['center'], $tbl_array[$record['region']]['center'])) {
					$tbl_array[$record['region']]['center'][$record['center']] = 0;
				}
				$tbl_array[$record['region']]['center'][$record['center']] += 1;
			}

			if ($record['adultped'] == 'ADULT') {
				$tbl_array[$record['region']]['adults'] += 1;
				$tbl_adultped_array['adultstotal'] += 1;
				if ($record['country'] != "") {
					if (!array_key_exists($country[$record['country']], $tbl_adultped_array['adultstotalcountry'])) {
						$tbl_adultped_array['adultstotalcountry'][$country[$record['country']]] = 0;
					}
					$tbl_adultped_array['adultstotalcountry'][$country[$record['country']]] += 1;
				}
			} elseif ($record['adultped'] == 'PED') {
				$tbl_array[$record['region']]['peds'] += 1;
				$tbl_adultped_array['pedsstotal'] += 1;
				if ($record['country'] != "") {
					$tbl_adultped_array['pedsstotalcountry'][$country[$record['country']]] += 1;
				}
			} elseif ($record['adultped'] == 'BOTH') {
				$tbl_array[$record['region']]['adults'] += 1;
				$tbl_array[$record['region']]['peds'] += 1;
				$tbl_adultped_array['adultstotal'] += 1;
				$tbl_adultped_array['pedsstotal'] += 1;
				if ($record['country'] != "") {
					$tbl_adultped_array['adultstotalcountry'][$country[$record['country']]] += 1;
					$tbl_adultped_array['pedsstotalcountry'][$country[$record['country']]] += 1;
				}
			}
		}

	}
	ksort($tbl_adultped_array['adultstotalcountry']);
	ksort($tbl_adultped_array['pedsstotalcountry']);
	ksort($tbl_array['country']);

	$consortumcomp = "<tr><td class='bg-site-list'><strong>Adult</strong></td>
                           <td class='bg-site-list' width='120px'>".$tbl_adultped_array['adultstotal']."</td>
                           <td class='bg-site-list'>".count($tbl_adultped_array['adultstotalcountry'])."</td>
                           <td class='bg-site-list' width='419px'><div class='more'>".implode_key_and_value($tbl_adultped_array['adultstotalcountry'])."</div></td></tr>";
	$consortumcomp .= "<tr><td class='bg-site-list'><strong>Pediatric</strong></span></td>
                           <td class='bg-site-list'>".$tbl_adultped_array['pedsstotal']."</td>
                           <td class='bg-site-list'>".count($tbl_adultped_array['pedsstotalcountry'])."</td>
                           <td class='bg-site-list' width='419px'><div class='more'>".implode_key_and_value($tbl_adultped_array['pedsstotalcountry'])."</div></td></tr>";
	$total_countries = 0;
	foreach ($tbl_array as $region => $table) {
		if ($region != 'country') {
			$total_countries += count($tbl_array[$region]['country']);
			ksort($tbl_array[$region]['country']);
			$consortumcomp .= "<tr><td width='120px'><strong>" . $region_name[$region] . "</strong></span></td>
                            <td>" . $tbl_array[$region]['sites'] . "</td>
                            <td>" . count($tbl_array[$region]['country']) . "</td>
                            <td width='419px'><div class='more'>" . implode_key_and_value($tbl_array[$region]['country']) . "</div></td></tr>";
		}
	}
	$consortumcomp_all = "<tr><td class='bg-info'><strong>Total</strong></td>
                           <td class='bg-info' width='120px'>".$tbl_adultped_array['sites']."</td>
                           <td class='bg-info'>".$total_countries."</td>
                           <td class='bg-info' width='419px'><div class='more'>".implode_key_and_value($tbl_array['country'])."</div></td></tr>";

	$consortumcomp = $consortumcomp_all.$consortumcomp;
	?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title d-flex align-items-center">
                <!-- Collapse Text -->
                <a class="collapseText d-flex align-items-center flex-grow-1"
                   data-bs-toggle="collapse"
                   href="#collapse_consortium"
                   role="button"
                   aria-expanded="true"
                   aria-controls="collapse_consortium">
                    <?=$settings['hub_name']?>
                    <?=filter_tags(($settings['hub_stats_sitelist_title'] == "") ? $defaultValuesSettings['hub_stats_sitelist_title'] : $settings['hub_stats_sitelist_title'])?>
                </a>

                <!-- Badge -->
                <span class="badge bg-primary ms-2"><?=$tbl_adultped_array['sites']?></span>

                <!-- Chevron Icon -->
                <a class="collapseText toggle-icon ms-2 position-absolute end-0 pe-3"
                   data-bs-toggle="collapse"
                   href="#collapse_consortium"
                   role="button"
                   aria-expanded="true"
                   aria-controls="collapse_consortium">
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </a>
            </h6>
        </div>
        <div id="collapse_consortium" class="collapse table-no-borders">
            <table class="table sortable-theme-bootstrap" data-sortable id="sortable_table">
                <thead>
                <tr>
                    <th width="120px"></th>
                    <th width="105px"># Sites</th>
                    <th width="105px"># Countries</th>
                    <th width="419px">Countries (# Sites)</th>
                </tr>
                </thead>
                <tbody>
                <?php echo $consortumcomp; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php } ?>
<div style="padding-bottom: 100px"></div>
