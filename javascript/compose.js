$(document).ready(function() {

    // hijacks default 'view email' button for SweetAlert2 action!
    $('a.m-link').bind('click', (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        rel = e.target.rel;
        sweetAlertbyID(`.${rel}`);
    });
    $("#csv_recipient").wrap('<div id="csv_recipient_wrapper" ></div>');
    $("button[name=btnReset]").hide();
    if (isAPIAvailable()) {
        $("input[name='file_recipient']").change((evt) => {
            parseData(evt.target.files[0]);
        });
    }
    $("input[name=recipient]").prop("readonly", true);
    $("input[name=recipient]").change(countEmails);
    $("select[name='mailtype']").change(messageType);
    $("[name$=linenum], [name=btnReset]").bind('click', (e) => {
        resetRecipients(true);
    });
    $("button[name=convert_csv]").bind('click', (e) => {
        var val = $("#csv_recipient").val();
        parseData(val.trim());
    });
    var $target = $(".yes_no");
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
                var attributeValue = $(mutation.target).prop(mutation.attributeName);
                if (attributeValue.indexOf("off") >= 0) {
                    TLN.remove_line_numbers('csv_recipient');
                } else {
                    TLN.append_line_numbers('csv_recipient');
                }
            }
        });
    });
    if ($target[0]) {
        observer.observe($target[0], {
            attributes: true
        });
    }
});

function resetRecipients(all) {
    if (all) {
        $("#csv_recipient").val('');
        // reset upload
        var file_recip = $('input[name=file_recipient');
        file_recip.wrap('<form>').closest('form').get(0).reset();
        file_recip.unwrap();
        $('.yes_no').addClass('off');
        $('.yes_no').removeClass('on');
    }
    // reset emails and errors
    $('input[name=recipient').val("");
    $('#csv_errors').html("");

    // reset recipient label 
    countEmails();

    $('#placeholder').remove();
    // reset table
    var parent = $('#csv_content_wrapper').parent();
    parent.empty();
    var table = $("<table id='csv_content' class='fixed_header'></table>");
    parent.wrapInner(table);

    $("button[name=btnReset]").hide();
}

function messageType() {
    if ($("select[name='mailtype']").val() === "html") {
        $("textarea[name='plaintext_alt']").parents("fieldset").eq(0).slideDown();
    } else {
        $("textarea[name='plaintext_alt']").parents("fieldset").eq(0).slideUp();
    }
}

function countEmails() {
    var email_string = $("input[name=recipient]").val();
    var emails = email_string.split(',');
    var count = (emails[0] === "") ? 0 : emails.length;

    var label = $('input[name=recipient]').parent().prev().find('label');
    var origText = label.text();
    // preserve  original label just append count string
    if (origText.includes('Count')) {
        var idx = origText.indexOf(' (Count: ');
        if (idx > -1) origText = origText.substr(0, idx);
    }
    var countText = (count > 0) ? ` (Count: ${count})` : "";
    label.text(origText + countText);
}

function getEmails(data) {
    resetRecipients();
    var csvObj;
    if (typeof data === 'undefined') {
        data = $("#csv_recipient").val();
        if (data === "") {
            Swal.fire({
                title: "No Data",
                type: 'error',
                text: 'No csv data provided!'
            });
            return;
        } else {
            parseData(data);
        }
    }

    // if (data.errors.length > 0) {
    //     showPapaErrors(data.errors);
    //     // return;
    // }
    csvObj = validate_csv(data);
    $("button[name=btnReset]").show();
    if (csvObj) {
        $('input[name="csv_object"]').val(csvObj.data_string);
        $("input[name='recipient_count']").val(csvObj.recipient_count);
        $("input[name='mailKey']").val(csvObj.mailkey);
        $('input[name=recipient]').val(csvObj.email_list);
        console.log(csvObj.data);
        showPlaceholders(csvObj.headers);
        countEmails();
        return true;
    }
    return false;
}

function validate_csv(data) {
    var errObjs = [];
    var errs = [];
    var errDetail = [];
    var header_valid, email_column;
    var first_row_values = Object.values(data.data[0]); // should begin data
    // email column in file?
    var file_contains_emails = (first_row_values.filter(word => isValidEmailAddress(word)).length > 0);

    // validate required columns
    var required_columns = validateRequiredKeys(data.headers);
    if (required_columns.errors.length > 0) errDetail = errDetail.concat(required_columns.errors);
    header_valid = required_columns.validHeader;
    email_column = required_columns.email_column;

    // generate email list and generate converted data to obj with tokenized keys  
    var emails = [];
    var tokenized_obj = [];
    data.data.forEach(row => {
        var newRow = {};
        var token_key, current_csv;
        var current_email = row[email_column.original];
        if (email_column) {
            if (current_email && isValidEmailAddress(current_email)) {
                emails.push(current_email.trim());
            } else {
                var tmp = "Column (" + email_column.original + "): does not contain email data (" + current_email + ")";
                if (!errs.includes(tmp)) {
                    errs.unshift(tmp);
                }
            }
        }
        for (var itm in row) {
            token_key = required_columns.headerKeyMap[itm];
            newRow[token_key] = row[itm];
        }
        tokenized_obj.unshift(newRow);
    });

    if (!email_column || !file_contains_emails) errs.unshift("Valid Email Column Header Not Found");
    if (file_contains_emails && !header_valid) {
        errs.unshift('Email column is mislabeled');
    }
    if (!required_columns) errs.unshift("Required Columns: email, first_name, last_name");
    if (!required_columns) errs.unshift("Required Columns: email, first_name, last_name");
    if (errs.length === 0) {
        return {
            'mailkey': required_columns.email_column.val,
            'headers': Object.values(required_columns.headerKeyMap),
            'data_string': JSON.stringify(tokenized_obj),
            'data': tokenized_obj,
            'rawdata': data.data,
            'recipient_count': emails.length,
            'validated_data': required_columns,
            'email_list': emails.join(',')
        };
    } else {
        displayCSVErrors(errs, errDetail);
        var current_csv = $("#csv_recipient").val().split(/\n+/g);
        current_csv.unshift(errs[0].toUpperCase());
        let new_csv = current_csv.join('\n');
        $("#csv_recipient").val(new_csv);
        return null;
    }
}

function intersection(d, regX) {
    var dataArray = d;
    var foundColumn = [];
    dataArray.forEach((csvColumn, idx) => {
        var testColumn = csvColumn.toLowerCase().trim();
        if (regX.test(testColumn)) {
            foundColumn.push({
                original: csvColumn.trim(),
                val: tokenizeKey(testColumn),
                idx: idx
            });
        }
    });
    return foundColumn;
}

function validateRequiredKeys(data) {
    var validHeaders = {
        email_column: ['mail', 'email', 'address', 'e-mail'],
        first: ['first', 'given', 'forename'],
        last: ['last', 'surname']
    };
    var regexify = (arr) => {
        return new RegExp(arr.join("|"), "i");
    };
    var header_has_no_email_data = (data.filter(word => isValidEmailAddress(word)).length === 0);
    var email_column = intersection(data, regexify(validHeaders.email_column));
    var first = intersection(data, regexify(validHeaders.first));
    var last = intersection(data, regexify(validHeaders.last));
    var reqKeys = {
        'email_column': email_column.length > 0 ? email_column[0] : false,
        'first': first.length > 0 ? first[0] : "",
        'last': last.length > 0 ? last[0] : "",
        'headerKeyMap': (header_has_no_email_data) ? genKeyMap(data) : data,
        'errors': []
    };
    var invalidColumns = Object.keys(reqKeys).filter(k => {
        var isNotSet = (reqKeys[k] === "" || reqKeys[k] === false);
        if (isNotSet)
            reqKeys.errors.push(`Acceptable Values for ${k}: ${validHeaders[k]}`);
    });
    reqKeys.validHeader = ((invalidColumns.length === 0) && header_has_no_email_data);
    return reqKeys;
}

function genKeyMap(data) {
    var obj = {};
    Object.values(data).forEach(key => {
        obj[key] = tokenizeKey(key);
    });
    return obj;
}

function isValidEmailAddress(emailAddress) {
    var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
    return pattern.test(emailAddress.trim());
}

function tokenizeKeys(data) {
    var newData = [];
    data.forEach(key => {
        newData.push(tokenizeKey(key));
    });
    return newData;
}

function tokenizeKey(key) {
    return "{{" + key.trim().toLowerCase().replace(' ', '_') + "}}";
}

function showPlaceholders(headers) {
    var el_exists = document.getElementById('placeholder');
    if (el_exists) {
        $('#placeholder').remove();
    }
    $('.sidebar').after("<table id='placeholder'><caption>Placeholders</caption></table>");
    headers.forEach(el => {
        var test = $('<button/>', {
            class: 'btn placeholder',
            text: el,
            click: function() {
                var plain = $('textarea[name=\'plaintext_alt\']');
                var msg = $('textarea[name=\'message\']');
                var message = ($('textarea[name=\'plaintext_alt\']').is(':visible')) ? plain : msg;

                // Insert text into textarea at cursor position and replace selected text
                var cursorPosStart = message.prop('selectionStart');
                var cursorPosEnd = message.prop('selectionEnd');
                var v = message.val();
                var textBefore = v.substring(0, cursorPosStart);
                var textAfter = v.substring(cursorPosEnd, v.length);
                message.val(textBefore + $(this).text() + textAfter);
                message.focus();
            }
        }).wrap('<tr><td align="center"></td></tr>').closest('tr');

        $("#placeholder").append(test);
    });

}

function displayCSVErrors(errs, errDetail) {
    var title = "";
    var msg = $("<ul style='color:red' />");
    var detail = $("<ul style='white-space: pre-wrap; color:red' />");
    errs.forEach(element => {
        title += element + "<br />";
        var itm = $('<li />', {
            text: element,
        });
        msg.append(itm);
    });
    errDetail.forEach(element => {
        var dt = $("<dt/>", {
            text: element
        });
        detail.append(dt);
        msg.append(detail);
    });
    $('#csv_errors').prepend(msg);
    Swal.fire({
        'title': title,
        'type': 'error',
        'html': detail
    });

}


function isAPIAvailable() {
    // Check for the various File API support.
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        // Great success! All the File APIs are supported.
        console.log("Great success! All the File APIs are supported.\n CSV uploads enabled!");
        return true;
    } else {
        // source: File API availability - http://caniuse.com/#feat=fileapi
        // source: <output> availability - http://html5doctor.com/the-output-element/
        var warning = 'The HTML5 APIs used in this form are only available in the following browsers:<br />';
        warning += ' - Google Chrome: 13.0 or later<br />'; // 6.0 File API & 13.0 <output>
        warning += ' - Mozilla Firefox: 6.0 or later<br />'; // 3.6 File API & 6.0 <output>
        warning += ' - Internet Explorer: Not supported (partial support expected in 10.0)<br />'; // 10.0 File API & 10.0 <output>
        warning += ' - Safari: Not supported<br />'; // ? File API & 5.1 <output>
        warning += ' - Opera: Not supported'; // ? File API & 9.2 <output>
        document.body.innerHTML = warning;
        return false;
    }
}

function parseData(str) {
    if (str === "") {
        Swal.fire({
            'title': 'No CSV Data Provided',
            'type': 'error',
        });
        return;
    }
    return Papa.parse(str, {
        header: true,
        skipEmptyLines: 'greedy',
        error: (e, file) => {
            showPapaErrors(data.errors);
            return;
        },
        complete: (results, file) => {
            var data = {};
            data.headers = results.meta.fields;
            data.errors = results.errors;
            data.data = results.data;
            data.dtCols = [];
            data.dtData = [];

            data.headers.forEach(col => {
                data.dtCols.push({
                    title: col
                });
            });

            data.data.forEach(itm => {
                data.dtData.push(Object.values(itm));
            });

            data.string = Papa.unparse({
                fields: data.headers,
                data: data.dtData,
            });

            // $("#csv_recipient").val(data.string);

            if (file) {
                // read the file metadata
                var output = '';
                output += '<span style="font-weight:bold;">' + escape(file.name) + '</span><br />\n';
                output += ' - FileType: ' + (file.type || 'n/a') + '<br />\n';
                output += ' - FileSize: ' + file.size + ' bytes<br />\n';
                output += ' - LastModified: ' + (file.lastModifiedDate ? file.lastModifiedDate.toLocaleDateString() : 'n/a') + '<br />\n';

                // post the results
                Swal.fire({
                    position: 'top-end',
                    type: 'success',
                    title: 'File Info',
                    html: output,
                    showConfirmButton: false,
                    timer: 1500
                });

                var reader = new FileReader();
                reader.readAsText(file);
                reader.onload = (evt) => {
                    $("#csv_recipient").val(evt.target.result);
                };
                reader.onerror = function() {
                    Swal.fire('Unable to read ' + file.fileName);
                };
            }
            if (getEmails(data)) {
                initTable(data);
            }
            console.log("Parsing: Processing ", results, file);
            console.log("Parsing Complete:", data);
        }
    });
}


function initTable(data) {
    $("#csv_recipient").val("");
    return $('#csv_content')
        .addClass('fixed_header display')
        .DataTable({
            dom: '<"top"i>rt<"bottom"flp><"clear">',
            initComplete: function() {
                var api = this.api();
                api.$('td').click(function() {
                    api.search(this.innerHTML).draw();
                });
            },
            columns: data.dtCols,
            data: data.dtData,
            defaultContent: '<i>Not set</i>',
            "paging": false,
            "ordering": false,
        });
}

function showPapaErrors(errorArray) {
    var errMsgs = {};
    var ul = $('<ul class="errCode" />');
    // consolidate error array 
    errorArray.forEach(err => {
        if (!Object.keys(errMsgs).includes(err.type)) {
            errMsgs[err.type] = {};
        }
        if (!Object.keys(errMsgs[err.type]).includes(err.code)) {
            errMsgs[err.type][err.code] = {
                affected: []
            };
        }
        errMsgs[err.type][err.code].message = err.message;
        var linenum_offset = 2;
        if (!errMsgs[err.type][err.code].affected.includes(err.row + linenum_offset)) {
            errMsgs[err.type][err.code].affected.push(err.row + linenum_offset);
        }
    });
    Object.keys(errMsgs).forEach(type => {
        var li = $('<li />', {
            text: type,
        });
        var sub_ul = $('<ul />');
        Object.keys(errMsgs[type]).forEach(code => {
            $('<li />')
                .text(errMsgs[type][code].message)
                .append($('<p />', {
                    text: 'Affected Row(s): ' + errMsgs[type][code].affected.join(', ')
                }))
                .appendTo(sub_ul);
        });
        li.append(sub_ul).appendTo(ul);
    });

    ul.appendTo('#csv_errors');
    Swal.fire({
        'title': "Errors",
        'type': 'error',
        'html': Object.keys(errMsgs).join(', ')
    });
    return true;
}

function sweetAlertbyID(id) {
    var html = $(id).html();
    var title = $($.parseHTML(html)).find('h1').text();
    var info = $($.parseHTML(html)).find('.txt-wrap').html();
    Swal.fire(title, info, 'info');
}

function dumpHiddenVals() {
    var msg = $("<table/>");
    $('input[type="hidden"]').each(function() {
        var val = $(this).val();
        val = (val.length > 100) ? val.substring(0, 100) + '...' : val;
        console.log($(this).attr('name') + ": " + $(this).val());
        msg.append("<tr><td>" + $(this).attr('name') + "</td><td>" + val + "</td></tr>");
    });
    swal.fire({
        title: 'HIDDEN VALS',
        type: 'info',
        html: msg,
        width: '80%',
    });
}

const TLN = {
    eventList: {},
    update_line_numbers: function(ta, el) {
        let lines = ta.value.split("\n").length;
        let child_count = el.children.length;
        let difference = lines - child_count;

        if (difference > 0) {
            let frag = document.createDocumentFragment();
            while (difference > 0) {
                let line_number = document.createElement("span");
                line_number.className = "tln-line";
                frag.appendChild(line_number);
                difference--;
            }
            el.appendChild(frag);
        }
        while (difference < 0) {
            el.removeChild(el.firstChild);
            difference++;
        }
    },
    append_line_numbers: function(id) {
        let ta = document.getElementById(id);
        if (ta === null) {
            return console.warn("[tln.js] Couldn't find textarea of id '" + id + "'");
        }
        if (ta.className.indexOf("tln-active") != -1) {
            return console.log("[tln.js] textarea of id '" + id + "' is already numbered");
        }
        ta.classList.add("tln-active");
        ta.style = {};

        let el = document.createElement("div");
        ta.parentNode.insertBefore(el, ta);
        el.className = "tln-wrapper";
        TLN.update_line_numbers(ta, el);
        TLN.eventList[id] = [];

        const __change_evts = [
            "propertychange", "input", "keydown", "keyup"
        ];
        const __change_hdlr = function(ta, el) {
            return function(e) {
                if ((+ta.scrollLeft == 10 && (e.keyCode == 37 || e.which == 37 ||
                        e.code == "ArrowLeft" || e.key == "ArrowLeft")) ||
                    e.keyCode == 36 || e.which == 36 || e.code == "Home" || e.key == "Home" ||
                    e.keyCode == 13 || e.which == 13 || e.code == "Enter" || e.key == "Enter" ||
                    e.code == "NumpadEnter")
                    ta.scrollLeft = 0;
                TLN.update_line_numbers(ta, el);
            };
        }(ta, el);
        for (let i = __change_evts.length - 1; i >= 0; i--) {
            ta.addEventListener(__change_evts[i], __change_hdlr);
            TLN.eventList[id].push({
                evt: __change_evts[i],
                hdlr: __change_hdlr
            });
        }

        const __scroll_evts = ["change", "mousewheel", "scroll"];
        const __scroll_hdlr = function(ta, el) {
            return function() {
                el.scrollTop = ta.scrollTop;
            };
        }(ta, el);
        for (let i = __scroll_evts.length - 1; i >= 0; i--) {
            ta.addEventListener(__scroll_evts[i], __scroll_hdlr);
            TLN.eventList[id].push({
                evt: __scroll_evts[i],
                hdlr: __scroll_hdlr
            });
        }
    },
    remove_line_numbers: function(id) {
        let ta = document.getElementById(id);
        if (ta === null) {
            return console.warn("[tln.js] Couldn't find textarea of id '" + id + "'");
        }
        if (ta.className.indexOf("tln-active") == -1) {
            return console.warn("[tln.js] textarea of id '" + id + "' isn't numbered");
        }
        ta.classList.remove("tln-active");

        ta.previousSibling.remove();

        if (!TLN.eventList[id]) return;
        for (let i = TLN.eventList[id].length - 1; i >= 0; i--) {
            const evt = TLN.eventList[id][i];
            ta.removeEventListener(evt.evt, evt.hdlr);
        }
        delete TLN.eventList[id];
    }
};