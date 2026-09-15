function startDDProjects(redcap_csrf_token){
    $('#installbtn').prop('disabled', true);
    $.ajax({
        url: startDDProjects_url,
        data: "&pid="+pid+"&redcap_csrf_token="+redcap_csrf_token,
        type: 'POST',
        success: function(returnData) {
            var data = JSON.parse(returnData);
            if (data.status == 'success') {
                $('#create_spinner').removeClass('fa fa-spinner fa-spin');
                window.location = getMessageLetterUrl(indexPage_url, "D");
            }
        }
    });
}

$(document).ready(function()
{
    $('input[type=checkbox]').each(function (i) {
        addRemoveSelectedClass($(this).is(':checked'),$(this).val());
    });
});

/**
 * function that adds a letter in the url to display a message
 * @param letter
 * @returns {string}
 */
function getMessageLetterUrl(url, letter){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }

    if(url.match(/(&message=)([A-Z]{1})/)){
        url = url.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
    }else{
        url = url + "&message="+letter;
    }
    return url;
}

/**
 * Function to add/remove the selected class an to un/select the doubles at the same time
 * @param id
 */
function checkselectDoubles(id){
    var rowname = $("#name_"+id).text();
    var checked = $('#record_id_'+id).is(':checked');
    if (!checked) {
        $('#record_id_' + id).prop("checked", true);
        $('.desTable [record_id="' + id + '"]').addClass('rowSelected');
        $('.step_4_table [record_id="'+id+'"]').removeClass("hidden");
    } else {
        $('#record_id_' + id).prop("checked", false);
        $('.desTable [record_id="' + id + '"]').removeClass('rowSelected');
        $('.step_4_table [record_id="'+id+'"]').addClass("hidden");
    }
    checked = $('#record_id_'+id).is(':checked');
    var table_id = $('#record _id_'+id).closest('tr').attr("parent_table");

    //to un/select the doubles at the same time
    putRemove_double_selectClass('D_A','D',checked, id, rowname);

    //Add/Remove class to selected item
    addRemoveSelectedClass(checked,id);

    //to remove loaded text
    $('#num_selected'+table_id).text("");

    //Check or uncheck the 'Select All' checkbox
    var table = id.split('_')[0];
    if (($('[parent_table='+table+']').length-1) == $('[chk_name=chk_table_'+table+']:checked').length) {
        $('[name=chkAll_'+table+']').prop("checked", true);
    }else{
        $('[name=chkAll_'+table+']').prop("checked", false);
    }
}

function checkselect(id){
    var checked = $('#'+id).is(':checked');
    if (!checked) {
        $('#' + id).prop("checked", true);
        $('[row="' + id + '"]').addClass('rowSelected');
    } else {
        $('#' + id).prop("checked", false);
        $('[row="' + id + '"]').removeClass('rowSelected');
    }

    //Update the counter label
    var table = id.split('-')[0];
    checkTableCounter(table);

    //Check or uncheck the 'Select All' checkbox
    var constant = id.split('-')[0];
    if ($('[parent_table='+constant+']').length == $('[chk_name=chk_table_'+constant+']:checked').length) {
        $('#ckb_'+constant).prop("checked", true);
    }else{
        $('#ckb_'+constant).prop("checked", false);
    }

}

function getIcon(status){
    var icon = "fa-pencil-alt";
    if(status == "changed"){
        icon = "fa-pencil-alt";
    }else if(status == "added"){
        icon = "fa-plus";
    }else if(status == "removed"){
        icon = "fa-minus";
    }

    var icon_legend = '<a href="#" data-toggle="tooltip" title="'+status+'" data-placement="top" class="custom-tooltip" style="vertical-align: -2px;"><span class="label '+status+'" title="'+status+'"><i class="fas '+icon+'" aria-hidden="true"></i></span></a>';
    return icon_legend;
}

/**
 * Function that loads the SOP table
 * @param data, data we send to the ajax
 * @param url, url of the ajax file
 * @param loadAJAX, where we load our content
 */
function loadAjax(data, url, loadAJAX){
    $('#errMsgContainer').hide();
    $('#succMsgContainer').hide();
    $('#warnMsgContainer').hide();
    if(data != '') {
        $.ajax({
            type: "POST",
            url: url,
            data:data
            ,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
                jsonAjax = jQuery.parseJSON(result);

                if(jsonAjax.html != '' && jsonAjax.html != undefined) {
                    $("#" + loadAJAX).html(jsonAjax.html);
                }

                if(jsonAjax.number_updates != '' && jsonAjax.number_updates != undefined && jsonAjax.number_updates != "0"){
                    $('#succMsgContainer').show();
                    $('#succMsgContainer').html(' <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a> <strong>Success!</strong> '+jsonAjax.number_updates+' NEW Latest update/s were saved.');
                }

                if(jsonAjax.variablesInfo != '' && jsonAjax.variablesInfo != undefined){
                    var value = jsonAjax.variablesInfo;
                    $.each(jsonAjax.variablesInfo, function (i, object) {;
                        if(object.display == "none"){
                            $("#"+i+"_row").hide();
                        }else{
                            $("#"+i+"_row").show();
                        };
                    });
                }

                //If table sortable add function
                if(jsonAjax.sortable == "true"){
                    $("#"+loadAJAX+"_table").tablesorter();
                }

                //Error Messages (Successful, Warning and Error)
                if(jsonAjax.succmessage != '' && jsonAjax.succmessage != undefined ){
                    $('#succMsgContainer').show();
                    $('#succMsgContainer').html(jsonAjax.succmessage);
                }else if(jsonAjax.warnmessage != '' && jsonAjax.warnmessage != undefined ){
                    $('#warnMsgContainer').show();
                    $('#warnMsgContainer').html(jsonAjax.warnmessage);
                }else if(jsonAjax.errmessage != '' && jsonAjax.errmessage != undefined ){
                    $('#errMsgContainer').show();
                    $('#errMsgContainer').html(jsonAjax.errmessage);
                }

                $('.divModalLoading').hide();
            }
        });
    }
}

/**
 * Function to Add/Remove class to selected item
 * @param checked, checked status
 * @param index, actual row
 */
function addRemoveSelectedClass(checked,index){
    if(checked) {
        $('.desTable [record_id="'+index+'"]').addClass('rowSelected');
    } else {
        $('.desTable [record_id="'+index+'"]').removeClass('rowSelected');
    }
}

/**
 * Function to un/select the doubles at the same time
 * @param char1, string to check
 * @param char2, string to check
 * @param checked, checked status
 * @param index, actual row
 * @param rowname, row name
 */
function putRemove_double_selectClass(char1, char2, checked, index, rowname){
    if(rowname.indexOf(char1) >= 0){
        var name = rowname.replace(char1,char2);
        var ant = parseInt(index.substr(index.length - 1)) - 1;
        index = index.replace(/.$/, ant);
    }else{
        var next = parseInt(index.substr(index.length - 1)) + 1;
        index = index.replace(/.$/, next);
        if($("#name_"+index).text().indexOf(char1) >= 0){
            var name = $("#name_"+index).text();
        }
    }
    if($("#name_"+index).text() == name) {
        if (checked) {
            $('#record_id_' + index).prop("checked", true);
            $('.desTable [record_id="' + index + '"]').addClass('rowSelected');
            $('.step_4_table [record_id="'+index+'"]').removeClass("hidden");
        } else {
            $('#record_id_' + index).prop("checked", false);
            $('.desTable [record_id="' + index + '"]').removeClass('rowSelected');
            $('.step_4_table [record_id="'+index+'"]').addClass("hidden");
        }
    }

    //we update the counter label
    var table = index.split('_')[0];
    checkTableCounter(table);
}

function checkTableCounter(table){
    var count = $("[chk_name='chk_table_"+table+"']:checked").length;
    var deprecated = $(".deprecated[parent_table="+table+"] ").length;
    var select_all = $("[parent_table="+table+"]").length-1;

    if(count >= select_all && deprecated > 0){
        count = count-deprecated;
        $('[name="chkAll_'+table+'"]').attr('checked',true);
    }else if(count >= select_all){
        $('[name="chkAll_'+table+'"]').attr('checked',true);
    }

    if(count>0){
        $("#counter_"+table).text(count);
    }else{
        $("#counter_"+table).text("");
    }
}

/**
 * Function that checks all checkboxes by table function
 * @param id, the attribute identification number
 */
function checkAll(id) {
    if ($("[name='chkAll_" + id + "']").not('.deprecated').prop("checked")) {
        $("[chk_name='chk_table_" + id + "']").not('.deprecated').prop("checked", true);
        $('[parent_table="' + id + '"]').not('.deprecated').addClass("rowSelected");
    } else {
        $("[chk_name='chk_table_" + id + "']").not('.deprecated').prop("checked", false);
        $('[parent_table="' + id + '"]').not('.deprecated').removeClass("rowSelected");
    }
    //to remove loaded text
    $('#num_selected' + id).text("");

    //we update the counter label
    checkTableCounter(id);
}

/**
 * Function that checks is select All is selected or not to un/mark all checkboxes by table function
 * @param id, the attribute identification number
 */
function checkAllText(id) {
    if($("[name='chkAll_"+id+"']").not(':hidden').prop("checked")) {
        $("[name='chkAll_"+id+"']").not(':hidden').prop("checked", false);
    } else {
        $("[name='chkAll_"+id+"']").not(':hidden').prop("checked", true);
    }
    checkAll(id);
}

/**
 * Function that validates if an email is in the correct format
 * @param email
 * @returns {boolean}
 */
function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

/**
 * Function to add parameters to the URL and redirect
 * @param url, the current URL
 * @param parameter, the new parameter
 */
function addURL(url, parameter)
{
    window.location  = url+parameter;
}

/**
 * Function for the drop down menu on mobile
 */
$('ul.dropdown-menu [data-toggle=dropdown]').on('click', function(event) {
    // Avoid following the href location when clicking
    event.preventDefault();
    // Avoid having the menu to close when clicking
    event.stopPropagation();
    // If a menu is already open we close it
    $('ul.dropdown-menu [data-toggle=dropdown]').parent().removeClass('open');
    // opening the one you clicked on
    $(this).parent().addClass('open');
});

/**
 * On logout we destroy the session
 * @param goToUrl
 */
function destroySession(goToUrl) {
    location.href = goToUrl;
}

/**
 * Function that loads the iframe url in a modal and opens it
 * @param id
 * @param idframe
 * @param survey_link
 */
function editIframeModal(id, idframe, survey_link, modalTitle = "", modalMessage = ""){
    $("#"+idframe).prop('src',survey_link);
    if(modalTitle != ""){
        $('#'+id+" .modal-title").text(modalTitle);
    }
    if(modalMessage != ""){
        $('#'+idframe).attr("message",modalMessage);
    }
    $('#'+id).modal('show');
}

/**
 * Function that Activates/Deactivates continue button depending on which step we are
 * @param step, number of step we are in
 */
function checkStep(step){
    if(step == '1'){
        var urlParams = new URLSearchParams(window.location.search);
        var urlStep = urlParams.get('step');

        if ($('#selectConcept').val() !== "" && $('#selectConcept').val() !== "0") {
            if (urlStep === '3') {
                // Edit mode: only require selectConcept to have a value
                $('#save_continue_'+step).prop('disabled', false);
            } else if ($('[name=optradio]:checked').val() !== undefined) {
                if($('[name=optradio]:checked').val() == '2'){
                    if($('#templateSelect').val() != ""){
                        $('#save_continue_'+step).prop('disabled', false);
                        $('#template_option').val($('#templateSelect').val());
                    }else{
                        $('#save_continue_'+step).prop('disabled', true);
                    }
                }else{
                    $('#save_continue_'+step).prop('disabled', false);
                }
            } else {
                $('#save_continue_'+step).prop('disabled', true);
            }
        }else{
            $('#save_continue_'+step).prop('disabled', true);
        }
    }else if(step == '2'){
        if($('input[type=checkbox]:checked').length > 0){
            $('#save_continue_'+step).prop('disabled', false);
        }else{
            $('#save_continue_'+step).prop('disabled', true);
        }
    }else if(step == '3'){
        if($('#sop_datacontact').val() != "Select Name" && $('#sop_due_d').val() !== "" && $('#sop_due_d').val() !== "0" && $('#sop_due_d').val().length == 10 && (($('#sortable2 > li:visible').length !== "" && ($('#sortable2 > li:visible').length > 0 || $( "#sortable2" ).sortable( "toArray" ).length > 0)) || $("#sop_downloaders_dummy___1").is(':checked'))){
            $('#save_continue_'+step).prop('disabled', false);
        }else{
            $('#save_continue_'+step).prop('disabled', true);
        }
    }
}

/**
 * Modal to confirm we want to change the concept from the one loaded as they are different
 * @param option
 */
function changeConcept(option){
    var message = "This draft is currently associated with <strong>"+$('#selectSOP_'+option+' option:selected').attr('concept_id')+"</strong>. Are you sure you want to change it to <strong>"+$('#selectConcept option:selected').attr('concept')+"</strong>?";
    message += "<br/>The <em>Data Request name</em> will be automatically changed.";
    message += "<br/>The <em>name and email of the research contact</em> will be automatically changed.";

    $('#sop-change-concept-question').html(message);
    $('#sop-change-concept-modal').modal('show');
}

/**
 * STEP 2 check if we are missing any required variables and show a warning.
 * Required variables are auto-checked; if a required variable is also part of a
 * double pair (checkselectDoubles), its double is checked and saved too.
 * @returns {string} comma-separated list of added variable ids
 */
function check_required_variables(){
    var str = $('#parent_table_record_id_array').val();
    var string_record_id = str.substring(0, str.length - 1);
    var record_id_array = string_record_id.split(',');

    var variables_required_added_array = "";
    var variables_required_added_array_id = "";
    var tables_required_added_array = "";
    var any_table_required = false;
    var variablesByTable = {};

    for (var i = 0; i < record_id_array.length; i++) {
        var index = i + 1;

        // Does this table have at least one variable checked?
        var any_var_checked = false;
        $("[parent_table='" + index + "']").each(function () {
            var id = $(this).attr("record_id");
            if ($("#record_id_" + id).is(":checked")) {
                any_var_checked = true;
            }
        });

        if (any_var_checked) {
            var any_var_required = false;
            var tableName = $('#table_' + index).text();   // e.g. "tblBAS"

            $("[parent_table='" + index + "']").each(function () {
                var id = $(this).attr("record_id");

                if ($("#record_id_" + id).attr("variable_required") == "Y" &&
                    $("#record_id_" + id).is(":checked") == false) {

                    // Force-check the required variable
                    $('#record_id_' + id).prop("checked", true);
                    any_var_required = true;
                    (variablesByTable[tableName] = variablesByTable[tableName] || []).push("[" + $("#name_" + id).text() + "]");
                    variables_required_added_array_id += id + ",";

                    // A required variable may also be part of a double pair.
                    // Its double must be checked too, and included in the saved values.
                    var rowname = $("#name_" + id).text();
                    var doubleId = getDoubleId('D_A', 'D', id, rowname);
                    if (doubleId && $("#record_id_" + doubleId).length &&
                        $("#record_id_" + doubleId).is(":checked") == false) {
                        // Checks the double + syncs row classes / step-4 visibility
                        putRemove_double_selectClass('D_A', 'D', true, id, rowname);
                        variables_required_added_array_id += doubleId + ",";
                        (variablesByTable[tableName] = variablesByTable[tableName] || []).push("[" + $("#name_" + doubleId).text() + "]");
                    }
                }
            });

            if (any_var_required) {
                any_table_required = true;
                tables_required_added_array += $('#table_' + index).text() + ", ";
            }
        }
    }

    // Remove the last comma and space
    tables_required_added_array = tables_required_added_array.substring(0, tables_required_added_array.length - 2);

    if (any_table_required) {
        $('#warnMsgContainer').removeClass('d-none').addClass('show');

        var warnMessage = ' <strong>Warning!</strong> Required variables on tables <strong>[' + tables_required_added_array + ']</strong> were added.';

        // One line per table: "<strong>tblBAS:</strong> [PATIENT], [BIRTH_D], ..."
        Object.keys(variablesByTable).forEach(function (tableName) {
            warnMessage += '<br/><strong>' + tableName + ':</strong> ' + variablesByTable[tableName].join(', ');
        });

        $('#warnMsgContainerText').html(warnMessage);

        variables_required_added_array_id = variables_required_added_array_id.substring(0, variables_required_added_array_id.length - 1);
    } else {
        $('#warnMsgContainer').hide();
    }

    return variables_required_added_array_id;
}

/**
 * Returns the "double" record_id for a given variable id, using the same
 * D_A/D naming + index±1 logic as putRemove_double_selectClass.
 * Returns "" if there is no matching double.
 */
function getDoubleId(char1, char2, index, rowname){
    var name;
    var candidate;
    if (rowname.indexOf(char1) >= 0) {
        name = rowname.replace(char1, char2);
        var ant = parseInt(index.substr(index.length - 1)) - 1;
        candidate = index.replace(/.$/, ant);
    } else {
        var next = parseInt(index.substr(index.length - 1)) + 1;
        candidate = index.replace(/.$/, next);
        if ($("#name_" + candidate).text().indexOf(char1) >= 0) {
            name = $("#name_" + candidate).text();
        }
    }
    if (candidate && $("#name_" + candidate).text() === name) {
        return candidate;
    }
    return "";
}

/**
 * Update table fields checked in Step 3
 * @param tablefields
 */
function update_table_fields_checks(tablefields){
    //Clean table data
    $('.desTable input[type="checkbox"]').prop('checked', false);
    $('.desTable tr').removeClass('rowSelected');

    //Show tables collapsed when loading content
    $('.collapse').removeClass('in');

    var sop_tablefields = tablefields.split(",");
    for (var row in sop_tablefields) {
        if(sop_tablefields[row] != ""){
            $("#record_id_"+sop_tablefields[row]).prop('checked',true);
            $(".desTable [record_id = "+sop_tablefields[row]+"]").addClass('rowSelected');

            var table = sop_tablefields[row].split('_')[0];
            //we update the counter label
            checkTableCounter(table);
        }
    }
}

/**
 * Update table fields in Step 4
 * @param tablefields
 */
function update_table_fields(tablefields){
    var sop_tablefields = tablefields.split(",");
    for (var row in sop_tablefields) {
        if(sop_tablefields[row] != "") {
            $("[record_id =" + sop_tablefields[row] + "]").show();
            var sop_tablefields_parent = sop_tablefields[row].split("_")[0];
            $('[parent_table_header=' + sop_tablefields_parent + ']').show();
        }
    }
}

function  update_preferred_format(key,preferredFormat){
    if(preferredFormat == "1"){
        $('#'+key).prop('checked',true);
    }
}

/**
 * Update connected lists in Step 3
 * @param downloaders_list
 */
function update_downloaders_list(downloaders_list){
    //Clean list data
    $("#sortable2 li").appendTo('#sortable1');
    $('#sortable1').sortable('option', 'receive')(null, { item: $("#sortable2 li") });

    var downloaders = downloaders_list.split(",");
    for (var row in downloaders) {
        $("#"+downloaders[row]).appendTo('#sortable2');
        //Trigger
        $('#sortable2').sortable('option', 'receive')(null, { item: $("#"+downloaders[row]) });
    }

}

/**
 * Show/hide people depending on the region we have selected
 */
function check_people_region_dragAndDrop(){
    var selectedRegion = $('#dropDown_region').val();
    $('#sortable1 li').each(function(){
        if(selectedRegion != ""){
            if($(this).attr('region') != selectedRegion){
                $(this).hide();
            }else{
                $(this).show();
            }
        }else{
            $(this).show();
        }
    });
}

function getAttributeValueHtml(s){
    if(typeof s == 'string'){
        s = s.replace(/"/g, '&quot;');
        s = s.replace(/'/g, '&apos;');
    }

    if (typeof s == "undefined") {
        s = "";
    }

    return s;
}

/**
 * Add some HTML to hide or show the file on add or delete file
 * @param value, file id
 */
function getFileFieldElement(value){
    if ((typeof value != "undefined") && (value !== "" && value != null)) {
        var html = '<input type="hidden" name="sop_extrapdf" >';
        html += '<button class="external-modules-configure-modal-delete-file" onclick="hideFile('+value+')">Delete File</button>';
        html += '<span id="sop_extrapdf_name"></span>';

        $.post('sop/get-edoc-name.php?edoc='+value, function(data) {
            $("#sop_extrapdf_name").html(" <em>" + data.doc_name + "</em>");
        });
    } else {
        var html = '<input type="file" name="sop_extrapdf" value="' + getAttributeValueHtml(value) + '">';
    }
    $('#sop_extrapdf_div input[name="sop_extrapdf"]').parent().html(html);
}

/**
 * We show the button to upload a new file
 * @param value, file id
 */
function hideFile(value){
    var html = ' <label class="steps_label">Upload PDF</label>';
    html += '<input type="file" name="sop_extrapdf" value="">';
    html += '<input type="hidden" name="sop_extrapdf" value="'+value+'" class="deletedFile">';
    $('#sop_extrapdf_div input[name="sop_extrapdf"]').parent().html(html);
}

/**
 * Delete a file given it's id
 * @param record
 */
function deleteFile(record, redcap_csrf_token) {
    $('.deletedFile').each(function() {
        $.post('sop/delete-file.php?edoc='+$(this).val()+'&record='+record+redcap_csrf_token, function(data) {
            if (data.status != "success") {
                // failure
                alert("The file was not able to be deleted. "+JSON.stringify(data));
            }
        });

    });
};
/**
 * Copy a data request and redirect to the editor
 * @param record_id
 */
function copyDataRequestWithLoading(btn, url, urlgoto, redcap_csrf_token) {
    // Disable button, add spinner and message
    btn.classList.add('disabled');
    btn.setAttribute('disabled', 'disabled');
    btn.style.pointerEvents = 'none';
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Copying data request, please wait...';

    $.ajax({
        type: "POST",
        url: url,
        data: "&redcap_csrf_token=" + redcap_csrf_token,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
            // Re-enable button on error
            btn.classList.remove('disabled');
            btn.removeAttribute('disabled');
            btn.style.pointerEvents = '';
            btn.innerHTML = 'Continue';
        },
        success: function (result) {
            var record = jQuery.parseJSON(result);
            window.location = urlgoto + "&record=" + record;
        }
    });
}

function deleteSOP(recordid){
    $('#succMsgContainer').hide();
    $('#active_id').val(recordid);
    $('#delete-sop-modal').modal('show');
}

function changeVisibility(recordid,visibility){
    $('#succMsgContainer').hide();
    $('#visibility_id').val(recordid);
    $('#visibility').val(visibility);

    var visibility_text = "public";
    if(visibility == '1'){
        visibility_text = "private";
    }
    $('#visibility_msg').html("Are you sure you want to make this SOP <strong>"+visibility_text+"</strong>?");
    $('#visibility-sop-modal').modal('show');
}

/**
 * Ajax call that after refreshes page and displays a message
 * @param data
 * @constructor
 */
function CallAJAXAndShowMessage(data,url,letter,url_window){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            window.location = getMessageLetterUrl(url_window, letter);
        },
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });
}

function save_votes(record,user,region,pi_level,url,redcap_csrf_token){
    /// Create an array to store the selected values in the format region_id_voteIndex
    const selectedValues = [];

    // Iterate over each dropdown with the class .view-votes
    $('.view-votes').each(function () {
        // Locate the hidden input associated with this dropdown's selected value
        const $selectedInput = $(this).closest('.dropdown').find('input.dropdown_votes[value]');

        // Add the ID of the selected input to the array (if it exists)
        if ($selectedInput.length > 0) {
            selectedValues.push($selectedInput.attr('id')); // Push the ID (region_id_voteIndex)
        }
    });

    var data = "&redcap_csrf_token="+redcap_csrf_token+"&record="+record+"&pi_level="+pi_level+"&region="+region+"&user="+user+"&region_vote_values="+selectedValues;
    $('.dropdown-toggle-custom input').each(function() {
        data +=$(this).attr('id')+",";
    });
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            location.reload();
        }
    });
}

function save_status(url,user,region,redcap_csrf_token, record){
    let data = "&redcap_csrf_token="+redcap_csrf_token+"&region="+region+"&user="+user+"&record_id="+record+"&region_vote_values=";
    $('.dropdown-toggle-custom').each(function() {
        data +=$(this).attr('id')+",";
    });

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            location.reload();
        }
    });
}

function generate_concepts_list(data){
    $.ajax({
        type: "POST",
        url: "harmonist/concepts/generate_concepts_pdf.php",
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {

        }
    });
}

function confirmMakePrivate(record){
    $('#sop-make-private-confirmation').modal('show');
    $('#record').val(record);
}

function confirmDataUpload(concept, user, conceptId, record){
    $('#data-submit-concept').text(conceptId);
    $('#modal-data-upload-confirmation').modal('show');
    $('#upload_record').val(record);
    $('#assoc_concept').val(concept);
    $('#user').val(user);
}

function uploadDataToolkit(data,url){
    //We make the call not asunc to avoid popup blockers
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        async: false,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var tokendt = JSON.parse(result);
            $('#modal-data-upload-confirmation').modal('hide');
            window.open('https://iedeadata.org/iedea-harmonist/?tokendt='+tokendt, '_blank');
        }
    });
}

function follow_activity(option,userid,record,url, redcap_csrf_token){
    var data = "&option="+option+"&userid="+userid+"&record="+record+"&redcap_csrf_token="+redcap_csrf_token;
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var button = JSON.parse(result);
            $('#btn_follow').html(button);
        }
    });
}

function CallAJAXAndRedirect(data,url,redirect){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            if(redirect != ""){
                window.location = redirect;
            }
        }
    });
}

function checkRow(id){
    var checked = $('#chk_'+id).is(':checked');
    if (!checked) {
        $('#chk_' + id).prop("checked", true);
        $('#sel_'+id).addClass('rowSelected selected');
    } else {
        $('#chk_' + id).prop("checked", false);
        $('#sel_'+id).removeClass('rowSelected selected');
    }
}

function copyStringToClipboard (str) {
    // Create new element
    var el = document.createElement('textarea');
    // Set value (string to be copied)
    el.value = str;
    // Set non-editable to avoid focus and move outside of view
    el.setAttribute('readonly', '');
    el.style = {position: 'absolute', left: '-9999px'};
    document.body.appendChild(el);
    // Select text inside element
    el.select();
    // Copy text to clipboard
    document.execCommand('copy');
    // Remove temporary element
    document.body.removeChild(el);
}

function addDeleteCode(code, url){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }
    if(window.location.href.match(/(&del=)([0-9a-zA-Z]{32})/)){
        url = url.replace( /(&del=)([0-9a-zA-Z]{32})/, "&del="+code );
    }else{
        url = url + "&del="+code;
    }
    return url;
}

/**
 * Function that changes the icon on the retrieve data configuration
 * @param id
 */
function change_icon(id){
    if($('#'+id).attr('symbol') == 0){
        $('#'+id).attr('symbol',1);
        $('#'+id+"_span").removeClass('fa-plus-square');
        $('#'+id+"_span").addClass('fa-minus-square');
    }else{
        $('#'+id).attr('symbol',0);
        $('#'+id+"_span").removeClass('fa-minus-square');
        $('#'+id+"_span").addClass('fa-plus-square');
    }
}

/**
 * Function to set up the user configuration file
 * @param data
 * @param url
 */
function setUpConfiguration(data,url){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            //window.location = redirect;
            var data = JSON.parse(result);
            $('#setup_data').text(data.data);
            $('#setup_data_div').show();
            $('#setup_message').show();
        }
    });
}

/**
 * function to download the user configuration file
 */
function downloadConfiguration(){
    filename = "configuration.php";
    text =  $('#setup_data').text();
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}

/**
 * Metric function to show or display the donuts
 * @param values_array
 * @returns {boolean}
 */
function isDonutEmpty(values_array){
    var returnValue = true;
    Object.keys(values_array).forEach(function (index) {
        if(values_array[index] != "0"){
            returnValue = false;
        }
    });
    return returnValue;
}

function viewAllVotes(request_id, url, redcapCsrfToken){
    $.ajax({
        type: "POST",
        url: url,
        data: "request_id="+request_id+"&redcap_csrf_token="+redcapCsrfToken,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var data = JSON.parse(result);
            $('#allvotes').html(data);
            $('#hub_view_votes').modal('show');
        }
    });
}

function viewMixedVotes(request_id, region_id, url, redcapCsrfToken){
    const params = new URLSearchParams({
        request_id: request_id,
        region_id: region_id,
        redcap_csrf_token: redcapCsrfToken
    });

    $.ajax({
        type: "POST",
        url: url,
        data: params.toString(),
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var data = JSON.parse(result);
            $('#mixedvotes').html(data);
            $('#hub_view_mixed_votes').modal('show');
        }
    });
}

function viewAllVotesData(record_id){
    $.ajax({
        type: "POST",
        url: "sop/sop_view_all_votes_AJAX.php",
        data: "record_id="+record_id,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            var data = JSON.parse(result);
            $('#allvotes').html(data);
            $('#hub_view_votes').modal('show');
        }
    });
}


function deleteDataRequest(value){
    $('#admin-modal-delete').modal('show');
    $('#index_modal_delete').val(value);

}

function changeStatus(current_region_status,status,region,notes,region_update_ts,modal){
    $('#'+modal).modal('show');
    $('#region').val(region);
    $('#status_record').val(status);
    $('#data_response_notes').val(notes);
    $('#region_update_ts').html(region_update_ts);

    $("#changeStatus .dropdown-menu-custom li").parents('.dropdown').find('.dropdown-toggle').html(current_region_status+'<span class="caret" style="float: right;margin-top:8px"></span>')
}

function selectTag(value){
    var table = $('#table_archive').DataTable();

    if($('#tag_'+value).hasClass('btn-outline-secondary')){
        $('#tag_'+value).removeClass('btn-outline-secondary');
        $('#tag_'+value).addClass('btn-primary');
    }else{
        $('#tag_'+value).addClass('btn-outline-secondary');
        $('#tag_'+value).removeClass('btn-primary');
    }

    var filter_column_array = new Array();
    $( ".btn-primary" ).each(function() {
        filter_column_array.push($(this).find("span").text());
    });

    var filter_search = "";
    var column = 1;
    if(filter_column_array != undefined){
        Object.keys(filter_column_array).forEach(function (filter) {
            filter_search += "(?=.*"+filter_column_array[filter]+")";
        });
        table.column(column).search(filter_search,true, false).draw();
    }else{
        table.column(column).search("",true, false).draw();
    }
}

function runPubsCron(url, redcap_csrf_token){
    $('#pubsSpinner').show();
    $('#btndataPubForm').hide();
    $('#btndataPubForm').attr('disabled','disabled');
    $.ajax({
        type: "POST",
        url: url,
        data:"isAdmin="+1+redcap_csrf_token,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
        }
    });
}

function installMetadata(fields,url) {
    $("#metadataWarning").removeClass("install-metadata-box-danger");
    $("#metadataWarning").addClass("install-metadata-box-warning");
    $("#metadataWarning").html("<em class='fa fa-spinner fa-spin'></em> Installing...");
    $.post(url, { fields: fields }, function(data) {
        $("#metadataWarning").removeClass("install-metadata-box-warning");
        if (!data.match(/Exception/)) {
            $("#metadataWarning").addClass("install-metadata-box-success");
            $("#metadataWarning").html("<i class='fa fa-check' aria-hidden='true'></i> Installation Complete");
            setTimeout(function() {
                $("#metadataWarning").fadeOut(500);
            }, 3000);
        } else {
            $("#metadataWarning").addClass("install-metadata-box-danger");
            $("#metadataWarning").html("Error in installation! Metadata not updated. "+JSON.stringify(data));
        }
    });
}

function installRepeatingForms(fields,url) {
    $("#formsWarning").removeClass("install-metadata-box-danger");
    $("#formsWarning").addClass("install-metadata-box-warning");
    $("#formsWarning").html("<em class='fa fa-spinner fa-spin'></em> Installing...");
    $.post(url, { fields: fields }, function(data) {
        $("#formsWarning").removeClass("install-metadata-box-warning");
        if (!data.match(/Exception/)) {
            $("#formsWarning").addClass("install-metadata-box-success");
            $("#formsWarning").html("<i class='fa fa-check' aria-hidden='true'></i> Installation Complete");
            setTimeout(function() {
                $("#formsWarning").fadeOut(500);
            }, 3000);
        } else {
            $("#formsWarning").addClass("install-metadata-box-danger");
            $("#formsWarning").html("Error in installation! Repeating Forms not updated. "+JSON.stringify(data));
        }
    });
}

function startUnitTest(url){
    $('#unitTestbtn').prop('disabled',true);
    $('#unitTestMsgContainer').show();
    $.ajax({
        type: "POST",
        url: url,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            paramValue = jQuery.parseJSON(result);
            var Newulr = getParamUrl(window.location.href,paramValue);
            window.location.href = Newulr;
        }
    });
}

function getParamUrl(url, newParam){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }

    if(url.match(/(&test=)/)){
        var oldParam = url.split("&test=")[1];
        url = url.replace( oldParam, newParam );
    }else{
        url = url + "&test="+newParam;
    }
    return url;
}

function exploreDataToken(data,url,url_relocation){
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            let tokendt = JSON.parse(result);
            let url = url_relocation+"&option=dab&tokendab="+tokendt;
            // Open the URL in a new tab
            window.location.href = url;
        }
    });
}
