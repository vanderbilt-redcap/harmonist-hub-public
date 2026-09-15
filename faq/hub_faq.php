<?php
namespace Vanderbilt\HarmonistHubPublicExternalModule;

$faqs = \REDCap::getData([
                             'project_id' => $pidsArray['FAQ'],
                             'return_format' => 'json-array',
                             'filterLogic' => "[help_show_y] = '1'"
                         ]);
$help_category = $module->getChoiceLabels('help_category', $pidsArray['FAQ']);

?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        (function($) {
            var $form = $('#filter-form');
            var $helpBlock = $("#filter-help-block");

            // Watch for user typing to refresh the filter
            $('#filter').on('input', function() {
                var filter = $(this).val();
                $form.removeClass("has-success has-error");

                if (filter == "") {
                    $helpBlock.text("No filter applied.");
                    $('.searchable .accordion-item').show();
                    $('.faqHeader').show();
                } else {
                    // Close any open panels
                    $('.collapse.show').removeClass('show');

                    // Hide questions, will show result later
                    $('.searchable .accordion-item').hide();

                    var regex = new RegExp(filter, 'i');

                    var filterResult = $('.searchable .accordion-item').filter(function() {
                        return regex.test($(this).text());
                    });

                    $('.faqHeader').hide();
                    console.log(filterResult);
                    if (filterResult) {
                        if (filterResult.length != 0) {
                            $form.addClass("has-success");
                            $helpBlock.text(filterResult.length + " question(s) found.");
                            filterResult.show();
                        } else {
                            $form.addClass("has-error").removeClass("has-success");
                            $helpBlock.text("No questions found.");
                        }

                    } else {
                        $form.addClass("has-error").removeClass("has-success");
                        $helpBlock.text("No questions found.");
                    }
                }
            });
        }($));
    });

    // Disable the enter key for the filter input
    $('.noEnterSubmit').keypress(function(e) {
        if (e.which == 13) e.preventDefault();
    });
</script>

<div class="container">
    <h3 class="fw-bold">FAQ</h3>
    <p class="hub-title">
        This page lists frequently asked questions about the <?=$module->escape($settings['hub_name'])?> Hub.
        To submit a new question for the list, contact
        <a href="mailto:<?=$settings['hub_contact_email']?>"><?=$settings['hub_contact_email']?></a>
    </p>
</div>

<!-- Filter Form -->
<div class="container">
    <div class="mb-3" id="filter-form">
        <label for="filter" class="form-label">
            Search for a Question
        </label>
        <input id="filter" type="text" class="form-control noEnterSubmit" placeholder="Enter a keyword or phrase" />
        <small>
            <span id="filter-help-block" class="form-text">
                No filter applied.
            </span>
        </small>
    </div>
</div>

<div class="container">
    <div class="accordion searchable" id="accordion">
        <?php
        if (!empty($faqs)) {
            foreach ($help_category as $category_id => $category_value) {
                $category_count = 0;
                foreach ($faqs as $faq) {
                    if ($faq['help_category'] == $category_id) {
                        if ($category_count == 0) {
                            echo '<div class="faqHeader">' . $help_category[$faq['help_category']] . '</div>';
                        }
                        $category_count++;
                        $collapse_id = "category_" . $category_id . "_question_" . $category_count;

                        echo '<div class="accordion-item">
                                <h2 class="accordion-header" id="heading_' . $collapse_id . '">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . $collapse_id . '" aria-expanded="false" aria-controls="' . $collapse_id . '"> <!-- Replaced "accordion-toggle" with "accordion-button" -->
                                        ' . filter_tags($faq['help_question']) . '
                                    </button>
                                </h2>
                                <div id="' . $collapse_id . '" class="accordion-collapse collapse" aria-labelledby="heading_' . $collapse_id . '" data-bs-parent="#accordion"> <!-- Replaced "panel-collapse collapse" -->
                                    <div class="accordion-body">
                                        <div>' . filter_tags($faq['help_answer']) . '</div>';

                        if ($faq['help_image'] != '') {
                            $q = $module->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id = ?", [$faq['help_image']]);
                            while ($row = $q->fetch_assoc()) {
                                echo '</br><div><img src="' . $module->getUrl('downloadFile.php', true) . '&code=' . getCrypt("sname=" . $row['stored_name'] . "&file=" . urlencode($row['doc_name']), 'e', $secret_key, $secret_iv) . '" style="display: block; margin: 0 auto;" alt="Image"></div>';
                            }
                        }

                        if ($faq['help_videoformat'] == '1') {
                            echo '</br><div><iframe class="commentsform" id="redcap-video-frame" name="redcap-video-frame" src="' . filter_tags($faq['help_videolink']) . '" width="520" height="345" frameborder="0" allowfullscreen style="display: block; margin: 0 auto;"></iframe></div>';
                        } else {
                            echo '</br><div class="help_embedcode">' . filter_tags($faq['help_embedcode']) . '</div>';
                        }

                        echo '</div>
                            </div>
                        </div>';
                    }
                }
            }
        }
        ?>
    </div>
</div>
