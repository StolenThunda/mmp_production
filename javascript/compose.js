$(document).ready(function() {
    // hijacks default 'view email' button for SweetAlert2 action!
    $('a.m-link').bind('click', (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        rel = e.target.rel;
        sweetAlertbyID(`.${rel}`);
    });
    $('button[name=btnReset]').hide();
    $('input[name=recipient]').prop('readonly', true);

    if (isAPIAvailable()) {
        $("input[name='file_recipient']").change((evt) => {
            // clear prev results
            $('#list').html('');
            $('#csv_content').html('');

            parseData(evt.target.files[0]);
        });
        $('input[name=recipient]').change(countEmails);
        $("select[name='mailtype']").change(messageType);
        $('button[name=show_csv]').bind('click', (e) => {
            var val = $('#csv_recipient').val();
            parseData(val.trim());
        });
        $('button[name=btnReset]').bind('click', () => {
            resetRecipients(true)
        });
    }
});


function resetRecipients(all) {
    if (all) {
        // remove any pasted csv data 
        $('#csv_recipient').val('');
        // remove file info
        var file_recip = $('input[name=file_recipient');
        file_recip.wrap('<form>').closest('form').get(0).reset();
        file_recip.unwrap();
    }
    // remove any parsed emails, errors and hidden form vals
    $('input[name=recipient] , [name=csv_object], [name=mailKey]').val('');
    $('#csv_errors').html('');

    // update label
    countEmails();

    // reset table
    var parent = $('#csv_content_wrapper').parent();
    parent.empty();
    var table = $("<table id='csv_content' class='fixed_header'></table>");
    parent.wrapInner(table);

    // remove placeholders
    $('#placeholder').remove();

    $('button[name=btnReset]').hide();
}

function messageType() {
    if ($("select[name='mailtype']").val() === 'html') {
        $("textarea[name='plaintext_alt']").parents('fieldset').eq(0).slideDown();
    } else {
        $("textarea[name='plaintext_alt']").parents('fieldset').eq(0).slideUp();
    }
}

function countEmails() {
    var email_string = $('input[name=recipient]').val();
    var emails = email_string.split(',');
    var count = emails[0] === '' ? 0 : emails.length;

    var label = $('input[name=recipient]').parent().prev().find('label');
    var origText = label.text();
    // preserve  original label just append count string
    if (origText.includes('Count')) {
        var idx = origText.indexOf(' (Count: ');
        if (idx > -1) origText = origText.substr(0, idx);
    }
    var countText = count > 0 ? ` (Count: ${count})` : '';
    label.text(origText + countText);
}

function getEmails(data) {
    resetRecipients();
    var csvObj;
    if (typeof data === 'undefined') {
        data = $('input[name=csv_recipient]').val();
        if (data === '') {
            Swal.fire({
                title: 'No Data',
                type: 'error',
                text: 'No csv data provided!'
            });
            return;
        }
        data = parseData(data);
    }

    if (data.errors.length > 0) {
        showPapaErrors(data.errors);
        return;
    }
    csvObj = validate_csv(data);
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
    var errs = [];
    var errDetail = [];
    var header_valid, email_column;
    var first_row_values = Object.values(data.objData[0]); // should begin data
    // has an email column?
    var has_email_column = first_row_values.filter((word) => isValidEmailAddress(word.trim())).length > 0;

    // validate required columns
    var required_columns = validateRequiredKeys(data.header, first_row_values);
    if (required_columns.errors.length > 0) errDetail = errDetail.concat(required_columns.errors);
    header_valid = required_columns.validHeader;
    email_column = required_columns.email_column;

    // generate email list and generate converted data to obj with tokenized keys
    var emails = [];
    var tokenized_obj = [];
    if (email_column) {
        data.objData.forEach((row, idx) => {
            var newRow = {};
            var token_key, current_csv;
            var current_email = row[email_column.original];
            if (!isValidEmailAddress(current_email)) {
                console.log('Error on line: ' + (idx + 1));
                var errMsg = 'Column: "' + email_column.original + '" does not contain valid email!!!'
                if (!errs.includes(errMsg.toUpperCase())) errs.unshift(errMsg.toUpperCase());
            }
            emails.push(current_email);
            for (var itm in row) {
                token_key = required_columns.headerKeyMap[itm];
                newRow[token_key] = row[itm];
            }
            tokenized_obj.unshift(newRow);
        });
    }

    if (!email_column) errs.unshift('Cannot determine email column');
    if (!header_valid) errs.unshift('Missing Header Row');
    if (has_email_column && !header_valid) {
        errs.unshift('Email column has invalid header');
    } else if (!has_email_column) {
        errs.unshift('No Email Column found');
    }
    if (!required_columns) errs.unshift('Required Columns: email, first_name, last_name');
    if (errs.length === 0) {
        $('#csv_recipient').val('');
        return {
            mailkey: required_columns.email_column.val,
            headers: Object.values(required_columns.headerKeyMap),
            data_string: JSON.stringify(tokenized_obj),
            data: tokenized_obj,
            recipient_count: emails.length,
            email_list: emails.join(',')
        };
    } else {
        var current_csv = $('#csv_recipient').val().split(/\n+/g);
        // current_csv[0] = current_csv[0].replace(itm, token_key);
        current_csv.unshift(errs[0].toUpperCase());
        let new_csv = current_csv.join('\n');
        $('#csv_recipient').val(new_csv);
        displayCSVErrors(errs, errDetail);
    }
}

function intersection(d, regX) {
    // var dataArray = d.map(val => val.toLowerCase().trim());
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

function validateRequiredKeys(data, row) {
    var validHeaders = {
        email_column: [
            'mail',
            'email',
            'address',
            'e-mail'
        ],
        first: [
            'first',
            'given',
            'forename'
        ],
        last: [
            'last',
            'surname'
        ]
    };
    var regexify = (arr) => {
        return new RegExp(arr.join('|'), 'i');
    };
    var header_has_no_email_data = data.filter((word) => isValidEmailAddress(word.trim())).length === 0;
    var email_column = intersection(data, regexify(validHeaders.email_column));
    var first = intersection(data, regexify(validHeaders.first));
    var last = intersection(data, regexify(validHeaders.last));
    var reqKeys = {
        email_column: email_column.length > 0 ? email_column[0] : false,
        first: first.length > 0 ? first[0] : '',
        last: last.length > 0 ? last[0] : '',
        headerKeyMap: header_has_no_email_data ? genKeyMap(data) : data,
        errors: []
    };
    var invalidColumns = Object.keys(reqKeys).filter((k) => {
        var isNotSet = reqKeys[k] === '' || reqKeys[k] === false;
        if (isNotSet) reqKeys.errors.push(`Acceptable Values for ${k}: ${validHeaders[k]}`);
    });
    reqKeys.validHeader = invalidColumns.length === 0 && header_has_no_email_data;
    return reqKeys;
}

function genKeyMap(data) {
    var obj = {};
    Object.values(data).forEach((key) => {
        key = key.trim();
        obj[key] = tokenizeKey(key);
    });
    return obj;
}

function isValidEmailAddress(emailAddress) {
    var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
    return pattern.test(emailAddress);
}

function tokenizeKeys(data) {
    var newData = [];
    data.forEach((key) => {
        newData.push(tokenizeKey(key));
    });
    return newData;
}

function tokenizeKey(key) {
    return '{{' + key.trim().toLowerCase().replace(' ', '_') + '}}';
}

function showPlaceholders(headers) {
    var el_exists = document.getElementById('placeholder');
    if (el_exists) {
        $('#placeholder').remove();
    }
    $('.sidebar').after("<table id='placeholder'><caption>Placeholders</caption></table>");
    headers.forEach((el) => {
        var test = $('<button/>', {
                class: 'btn placeholder',
                text: el,
                click: function() {
                    var plain = $("textarea[name='plaintext_alt']");
                    var msg = $("textarea[name='message']");
                    var message = $("textarea[name='plaintext_alt']").is(':visible') ? plain : msg;

                    // Insert text into textarea at cursor position and replace selected text
                    var cursorPosStart = message.prop('selectionStart');
                    var cursorPosEnd = message.prop('selectionEnd');
                    var v = message.val();
                    var textBefore = v.substring(0, cursorPosStart);
                    var textAfter = v.substring(cursorPosEnd, v.length);
                    message.val(textBefore + $(this).text() + textAfter);
                    message.focus();
                }
            })
            .wrap('<tr><td align="center"></td></tr>')
            .closest('tr');

        $('#placeholder').append(test);
    });
}

function displayCSVErrors(errs, errDetail) {
    var title = '';
    var msg = $("<ul style='color:red' />");
    var detail = $("<ul style='white-space: pre-wrap; color:red' />");
    errs.forEach((element) => {
        title += element + '<br />';
        var itm = $('<li />', {
            text: element
        });
        msg.append(itm);
    });
    errDetail.forEach((element) => {
        var dt = $('<dt/>', {
            text: element
        });
        detail.append(dt);
        msg.append(detail);
    });
    $('#csv_errors').html(msg);
    Swal.fire({
        title: title,
        type: 'error',
        html: detail
    });
}

function isAPIAvailable() {
    // Check for the various File API support.
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        // Great success! All the File APIs are supported.
        console.log('Great success! All the File APIs are supported.\n CSV uploads enabled!');
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
    debugger;
    return Papa.parse(str, {
        header: true,
        skipEmptyLines: 'greedy',
        error: (err, file, inputElem, reason) => {
            showPapaErrors(data.errors);
            return;
        },
        complete: (result, file) => {
            var data = {};
            if (result.data.length < 1) {
                Swal.fire({
                    title: 'No Data',
                    type: 'error',
                    text: 'No csv data provided!'
                });
                return;
            }
            console.log(`File: ${file}`);
            data.header = result.meta.fields;
            data.errors = result.errors;
            data.objData = trimObj(result.data);

            if (result.meta.fields.length > 0) {
                data.cols = [];
                data.header.forEach((field) => {
                    data.cols.push({
                        title: field
                    });
                });
            } else {
                var test = Object.values(result.data[0]);
                data.cols = test.fill('');
            }
            // format table data
            var dtData = [];
            data.objData.forEach((val) => {
                dtData.push(Object.values(val));
            });
            data.arrData = dtData;

            var csv = (event.target.result) ? event.target.result : $('#csv_recipient').val();
            $('#csv_recipient').val(csv);
            if (file) {
                // read the file metadata
                var output = '';
                output += '<span style="font-weight:bold;">' + escape(file.name) + '</span><br />\n';
                output += ' - FileType: ' + (file.type || 'n/a') + '<br />\n';
                output += ' - FileSize: ' + file.size + ' bytes<br />\n';
                output +=
                    ' - LastModified: ' +
                    (file.lastModifiedDate ? file.lastModifiedDate.toLocaleDateString() : 'n/a') +
                    '<br />\n';

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
                reader.onerror = function() {
                    Swal.fire('Unable to read ' + file.fileName);
                };
            }
            if (getEmails(data)) initTable(data);
            console.log('Parsing Complete:', result, file);
        }
    });
}

function initTable(data) {
    $('button[name=btnReset]').show();
    return $('#csv_content').addClass('fixed_header display').DataTable({
        retrieve: true,
        dom: '<"top"i>rt<"bottom"flp><"clear">',
        initComplete: function() {
            var api = this.api();
            api.$('td').click(function() {
                api.search(this.innerHTML).draw();
            });
        },
        columns: data.cols,
        data: data.arrData,
        paging: false,
        ordering: false
    });
}

function showPapaErrors(errorArray) {
    // errorArray = [...new Set(errorArray.errors.map(x => x.message))];
    var title = '';
    var errMsg = '';
    var msg = $("<div style='color:red' />");
    var msgKey = {
        FieldMismatch: 'Missing data in one or more of your columns'
    };
    var keyed = (element) => {
        if (Object.keys(msgKey).includes(element)) {
            return msgKey[element];
        } else {
            return element;
        }
    };
    var eTypes = {};
    errorArray.forEach((element) => {
        //consolidate errors for message
        if (!Object.keys(eTypes).includes(element.type)) {
            eTypes[element.type] = {}
        }
        if (!Object.keys(eTypes[element.type]).includes(element.code)) {
            eTypes[element.type][element.code] = {
                affected: []
            }
        }
        eTypes[element.type][element.code].affected.push(element.row + 1);
    });
    var msg = $("<ul class='errType' />");
    Object.keys(eTypes).forEach((type) => {
        var li = $('<li />');
        li.append(keyed(type))
        var subul = $('<ul />');

        Object.keys(eTypes[type]).forEach((code) => {
            var subli = $("<li class='errCode'/>")
                .text(code + "(on rows) : \n" + eTypes[type][code].affected.join(', '))
                .appendTo(subul);
        })
        li.append(subul).appendTo(msg)
    });
    $('#csv_errors').html(msg);
    Swal.fire({
        title: `Error <br> ${title}`,
        type: 'error',
        html: msg
    });
}

function trimObj(obj) {
    if (!Array.isArray(obj) && typeof obj != 'object') return obj;
    return Object.keys(obj).reduce(function(acc, key) {
        acc[key.trim()] = typeof obj[key] == 'string' ? obj[key].trim() : trimObj(obj[key]);
        return acc;
    }, Array.isArray(obj) ? [] : {});
}

function sweetAlertbyID(id) {
    var html = $(id).html();
    var title = $($.parseHTML(html)).find('h1').text();
    var info = $($.parseHTML(html)).find('.txt-wrap').html();
    Swal.fire(title, info, 'info');
}

function dumpHiddenVals() {
    var msg = $('<table/>');
    $('input[type="hidden"]').each(function() {
        var val = $(this).val();
        val = val.length > 100 ? val.substring(0, 100) + '...' : val;
        console.log($(this).attr('name') + ': ' + $(this).val());
        msg.append('<tr><td>' + $(this).attr('name') + '</td><td>' + val + '</td></tr>');
    });
    swal.fire({
        title: 'HIDDEN VALS',
        type: 'info',
        html: msg,
        width: '80%'
    });
}