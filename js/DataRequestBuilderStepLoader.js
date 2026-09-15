class DataRequestBuilderStepLoader {
    constructor(url, loadAjax, pdfGoToUrl) {
        this.url = url;
        this.loadAjax = loadAjax;
        this.pdfGoToUrl = pdfGoToUrl;
    }

    request(data, urlOverride = null) {
        return $.ajax({
            type: "POST",
            url: urlOverride ?? this.url,
            data,
            error: this.handleAjaxError,
        }).then(result => this.parseAjaxResult(result));
    }

    // Main method to load AJAX steps
    loadSteps(data, step) {
        if (step === '0' && $('[name=optradio]:checked').val() === '1') {
            this.handleInitialStep();
        }

        this.request(data).then((jsonAjax) => {
            if (!jsonAjax) return;
            this.processAjaxResponse(jsonAjax, step);
            this.finalizeStep(step, jsonAjax);
        });

        this.resetPreviewUI(step);
    }

    generatePDF(recordId, redcapCsrfToken) {
        return this.request({ id: recordId, redcap_csrf_token: redcapCsrfToken }, this.pdfSaveUrl).then((jsonAjax) => {
            if (!jsonAjax) return;

            $('#record_id').val($('#selectSOP_' + $('[name=optradio]:checked').val()).val());
            window.location = this.pdfGoToUrl + "&record=" + recordId;
        });
    }

    // Helper Methods

    handleInitialStep() {
        this.resetData();
        $('.step2_collapse').removeClass('in');
    }

    handleAjaxError(xhr) {
        alert(xhr.responseText);
    }

    parseAjaxResult(result) {
        try {
            return jQuery.parseJSON(result);
        } catch (error) {
            alert("Error parsing AJAX result:", error);
            return null;
        }
    }

    processAjaxResponse(jsonAjax, step) {
        let optradio = 0;

        for (const [key, value] of Object.entries(jsonAjax)) {
            if (value == null || value === "") continue;

            // capture optradio once
            if (key === "optradio") optradio = this.handleOptradio(value);

            this.dispatchAjaxKey({ jsonAjax, step: step, key, value });
        }

        this.checkResearchContacts();

        if (optradio > 0 && optradio !== step + 2) {
            this.checkStep(optradio);
        }
    }

    dispatchAjaxKey({ jsonAjax, step, key, value }) {
        const handler =
            // dynamic keys first
            (key.startsWith("dataformat_prefer___") && (() => this.updatePreferredFormat(key, value))) ||
            // step-specific handlers
            (step === 0 ? this.step3Handlers({ jsonAjax, step })[key] : this.otherStepHandlers({ jsonAjax, step })[key]) ||
            // fallback
            (() => this.updateField(key, value));

        handler();
    }

    mapKeys(keys, makeHandler) {
        return Object.fromEntries(
            keys.map(key => [key, makeHandler(key)])
        );
    }

    step3Handlers({ jsonAjax, step }) {
        return {
            sop_tablefields: () => {
                this.updateTables(jsonAjax, step);
            },

            ...this.mapKeys(
                ["sop_creator_email", "sop_creator2_email", "sop_datacontact_email"],
                (key) => () => this.updateEmailPreview(key, jsonAjax[key])
            ),

            ...this.mapKeys(
                ["sop_creator", "sop_creator2"],
                (key) => () => this.updateContactPreview(key, jsonAjax[key], "Research Contact(s)")
            ),

            sop_datacontact: () =>
                this.updateContactPreview("sop_datacontact", jsonAjax.sop_datacontact, "Data Contact"),

            sop_concept_title: () => this.updateConceptTitle("sop_concept_title", jsonAjax.sop_concept_title),

            ...this.mapKeys(
                ["save_option", "selectConcept", "saveOption", "sop_concept_id", "sop_data_transfer_request", "sop_due_d", "sop_hubuser", "sopCreator_region", "sop_due_d_preview"],
                (key) => () => this.updateInputField(key, jsonAjax[key])
            ),

            ...this.mapKeys(
                ["sop_inclusion", "sop_exclusion"],
                (key) => () => this.updateTinyMCE(key, jsonAjax[key])
            ),

            sop_notes: () => this.updateTinyMCEWithHeader("sop_notes", jsonAjax.sop_notes, "General notes: &nbsp;"),
            dataformat_notes: () =>
                this.updateTinyMCEWithHeader("dataformat_notes", jsonAjax.dataformat_notes, "File format notes:"),

            sop_downloaders: () => this.updateDownloadersList(jsonAjax.sop_downloaders),

            dataformat_prefer_text: () => this.updatePreferredFormatText("dataformat_prefer_text", jsonAjax.dataformat_prefer_text),
            sop_downloaders_dummy___1: () => this.updateCheckbox("sop_downloaders_dummy___1", jsonAjax.sop_downloaders_dummy___1),

            concept_id: () => $('[name=step_concept_id]').html(jsonAjax.concept_id + ":"),
            record_id: () => $('[name=step_sop]').html("Data Request #" + jsonAjax.record_id)
        };
    }

    otherStepHandlers({ jsonAjax, step }) {
        return {
            sop_name: () => {
                if (step === 1) {
                    $('#selectSOP_' + $('[name=optradio]:checked').val() + ' option:selected').text(jsonAjax.sop_name);
                }
            },

            concept_id_select: () => {
                if (step === 1) {
                    $('#selectSOP_' + $('[name=optradio]:checked').val() + ' option:selected').attr('concept_id', jsonAjax.concept_id_select);
                }
            },

            sop_concept_id: () => {
                if (step === 1) {
                    $('#selectSOP_' + $('[name=optradio]:checked').val() + ' option:selected').attr('concept', jsonAjax.sop_concept_id);
                }
                // Always update the preview and input field
                this.updateInputField('sop_concept_id', jsonAjax.sop_concept_id);
            },

            ...this.step3Handlers({ jsonAjax, step })
        };
    }

    handleOptradio(optradioValue) {
        $('#optradio_' + optradioValue).prop('checked', true);
        $('#setup_show_option_' + optradioValue).show();
        $('#setup_show_all_option').show();
        return optradioValue;
    }

    updateEmailPreview(key, value) {
        $('[preview=' + key + "]").text(value);
        $('#' + key).attr('href', "mailto:" + value);
    }

    updateContactPreview(key, value, title) {
        $('[preview=' + key + "]").text(value);
        $('#' + key).attr('href', "mailto:" + value);
        if (value !== "" && value !== "Select Name") {
            const k = String(key ?? "");
            const previewTitle = k.includes("datacontact")
                ? "sop_datacontact_title"
                : "sop_creator_title";
            $('[preview=' + previewTitle + ']').text(title);
        }
        this.updateInputField(key, value)
    }

    updateConceptTitle(key, value) {
        $('[preview=' + key + "]").html("<strong>"+value+"</strong>");
    }

    updateInputField(key, value) {
        $('#' + key).val(value);
        $('[preview=' + key + "]").html(value);
        if(key == "sop_due_d_preview"){
            $('[preview=' + key + "]").css({
                color: '#449d44'
            });
        }
    }

    updateField(key, value) {
        $('#' + key).val(value);
        $('#' + key).text(value);
        $('[preview=' + key + "]").val(value);
        $('[preview=' + key + "]").text(value);
    }

    updateTinyMCE(key, value) {
        tinymce.get(key).setContent(value);
        tinymce.get(key).setContent(tinymce.get(key).getContent({ format: 'text' }));
        $('[name=' + key + ']').val(value);
        $('[preview=' + key + "]").html(tinymce.get(key).getContent());
        tinymce.init({ selector: '#' + key });
    }

    updateTinyMCEWithHeader(key, value, headerText) {
        this.updateTinyMCE(key, value);
        $('[preview=' + key + "_header]").html("<strong>"+headerText+"</strong>");
    }

    updatePreferredFormatText(key, value) {
        $('[preview=' + key + "]").html("<p></p>" + value);
    }

    updateCheckbox(key, value) {
        if (value[0] === "1") {
            $('#' + key).prop('checked', true);
        }
    }

    finalizeStep(step, jsonAjax) {
        if (step === 0) {
            step = 2;
        }
        this.checkStep(parseInt(step) + 1);
        this.checkPeopleRegionDragAndDrop();
        $(".deprecated").hide();

        // Navigate to the next tab after data is loaded
        this.navigateToNextStep(step);
    }

    navigateToNextStep(step) {
        // Find the current step's tab pane and navigate to the next tab
        const currentPaneId = 'step' + step;
        const currentLi = document.querySelector(`.wizard .nav-tabs li a[href="#${currentPaneId}"]`)?.parentElement;
        const nextTab = currentLi ? currentLi.nextElementSibling : null;

        if (nextTab) {
            nextTab.classList.remove('disabled');
            const nextTabLink = nextTab.querySelector('a[data-bs-toggle="tab"]');
            nextTabLink.click();
            scrollToTop();
        }
    }

    resetPreviewUI(step) {
        $('#PreviewTable tr').removeClass('rowSelected');
        $('.preview').removeClass('rowSelected');
        $('.panel-heading').removeClass('rowSelected');
        $('.rowSelected div').removeClass('rowSelected');

        if (step === '1' && $('[name=optradio]:checked').val() === '1') {
            this.resetDownloadersList();
        }
    }

    resetDownloadersList() {
        $("#sortable2 li").appendTo('#sortable1');
        $('#sortable1').sortable('option', 'receive')(null, { item: $("#sortable2 li") });
    }

    resetData() {
        // Clear input fields
        $('input').val('');
        $('textarea').val('');
        $('select').prop('selectedIndex', 0);

        // Clear TinyMCE editors (if used)
        tinymce.editors.forEach(editor => editor.setContent(''));

        // Clear preview elements
        $('.preview').text('');
        $('.preview').val('');
    }

    updateTables(data, step){
        const fields = data.sop_tablefields;
        this.resetTablePreviewVisibility();   // always reset first (also handles empty selection)
        if (fields !== "") {
            this.updateTableFieldsChecks(fields);
            this.updateTableFields(fields);
            this.enableStep2ContinueButton(step);
        }
    }

    resetTablePreviewVisibility() {
        const $preview = $('#step4');
        $preview.find('.card.preview').hide();
        $preview.find('.card-header[record_id]').hide();
        $preview.find('tr[record_id]').hide();
        $preview.find('[parent_table_header]').hide();
        // requested-tables list <li record_id=...>
        $preview.find('ol li[record_id]').hide();
    }

    updateTableFields(data) {
        if (typeof data !== 'string') {
            alert("Invalid data passed to updateTableFields. Expected a string, but received:", data);
            return;
        }

        const sop_tablefields = data.split(',');

        sop_tablefields.forEach(field => {
            field = field.trim();
            if (field === "") return;

            // Show the specific variable row / list item (exact token match)
            const $matches = $(`#step4 [record_id="${field}"]`);
            $matches.show();

            // Show the enclosing card + card-header so the table is visible
            $matches.closest('.card.preview').show();
            $matches.closest('.card.preview').find('.card-header').show();

            // Show the parent table header rows (thead <tr>, text_top, etc.)
            const tableId = field.split("_")[0];
            $(`#step4 [parent_table_header="${tableId}"]`).show();

            // Show the requested-tables <li> for this table (keyed to <tableId>_1)
            $(`#step4 ol li[record_id^="${tableId}_"]`).show();
        });
    }

    enableStep2ContinueButton(step){
        // A template was loaded and the table has values, enable continue button
        if(step == '1' && $('[name=optradio]:checked').val() == '2') {
            $('#save_continue_2').prop('disabled', false);
        }
    }

    updateTableFieldsChecks(data) {
        // Clean table data
        $('.desTable input[type="checkbox"]').prop('checked', false); // Uncheck all checkboxes
        $('.desTable tr').removeClass('rowSelected'); // Remove 'rowSelected' class from all rows

        // Collapse all tables when loading content
        $('.collapse').removeClass('in');

        // Split the input string into individual table fields
        const sop_tablefields = data.split(',');

        // Iterate over each table field
        sop_tablefields.forEach(field => {
            if (field.trim() !== "") { // Ensure the field is not empty
                // Check the corresponding checkbox
                $(`#record_id_${field}`).prop('checked', true);

                // Add 'rowSelected' class to the corresponding row in the table
                $(`.desTable [record_id=${field}]`).addClass('rowSelected');

                // Extract the table identifier (everything before the underscore)
                const table = field.split('_')[0];

                // Update the counter label for the table
                this.checkTableCounter(table);
            }
        });
    }

    checkTableCounter(table){
        // Count checked checkboxes for the specific table
        const count = $(`[chk_name='chk_table_${table}']:checked`).length;

        // Count deprecated rows for the specific table
        const deprecated = $(`.deprecated[parent_table=${table}]`).length;

        // Count total rows for the table (excluding the header row)
        const select_all = $(`[parent_table=${table}]`).length - 1;

        // Handle deprecated rows and "select all" checkbox
        if (count >= select_all && deprecated > 0) {
            const adjustedCount = count - deprecated;
            $(`[name="chkAll_${table}"]`).prop('checked', true); // Check the "select all" checkbox
            $(`#counter_${table}`).text(adjustedCount); // Update the counter with the adjusted count
        } else if (count >= select_all) {
            $(`[name="chkAll_${table}"]`).prop('checked', true); // Check the "select all" checkbox
            $(`#counter_${table}`).text(count); // Update the counter
        } else {
            $(`[name="chkAll_${table}"]`).prop('checked', false); // Uncheck the "select all" checkbox
            $(`#counter_${table}`).text(count > 0 ? count : ""); // Update the counter or clear it if count is 0
        }
    }

    updateDownloadersList(data) {
        // Clean list data: Move all items back to #sortable1
        const sortable1 = $("#sortable1");
        const sortable2 = $("#sortable2");
        const allItems = sortable2.find("li").detach(); // Detach items for better performance
        sortable1.append(allItems);

        // Trigger the 'receive' event for #sortable1
        sortable1.sortable('option', 'receive')(null, { item: allItems });

        // Parse and process the downloaders list
        const downloaders = data.split(",").map(id => id.trim()); // Trim IDs to handle any extra whitespace
        downloaders.forEach(id => {
            const item = $("#" + id);
            if (item.length) { // Check if the element exists
                sortable2.append(item); // Move the item to #sortable2

                // Trigger the 'receive' event for #sortable2
                sortable2.sortable('option', 'receive')(null, { item });
            }
        });
    }

    updatePreferredFormat(key, value) {
        if(value == "1"){
            $('#'+key).prop('checked',true);
        }
    }

    checkStep(step) {
        // Example: Show/hide elements based on step
        $(`.step`).hide(); // Hide all steps
        $(`#step_${step}`).show(); // Show the current step
    }

    checkPeopleRegionDragAndDrop() {
        // Example: Initialize sortable lists for drag-and-drop
        $('#sortable1, #sortable2').sortable({
            connectWith: '.connectedSortable',
            update: function(event, ui) {
                console.log("Drag-and-drop updated:", ui.item.text());
            }
        }).disableSelection();
    }

    resetMenuButton(step){
        $('#title_step_'+step+' a')
            .removeAttr('onclick') // Removes the onclick="return false;"
            .css('pointer-events', 'auto') // Enables pointer events
            .css('cursor', 'pointer') // Sets the cursor style to pointer
            .removeClass('disabled'); // Removes the disabled class if present
    }

    disableStep1Options(){
        $('[name="optradio"]').prop('disabled', true);
        $('#templateSelect').prop('disabled', true);
    }

    checkResearchContacts() {
        if($('[preview="sop_creator_name"]').text() == "") {
            $('[preview="resarch_contacts_title"]').text("");
        }
    }
}
