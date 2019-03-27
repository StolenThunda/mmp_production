$(document).ready(function() {
    setUI();

    if (isAPIAvailable()) {
        $("input[name='file_recipient']").change(handleFileSelect);
    }
    $("#csv_recipient").bind('input', (e) => {
        var val = e.currentTarget.value;
        var result = Papa.parse(val.trim(), {
            header: true
        });
        printTable(result);
        getEmails(result);
        $("#csv_recipient").val("");
    });
    $("input[name=recipient]").change(countEmails);
    $("select[name='mailtype']").change(messageType);
    $("button[name=btnReset]").bind('click', (e) => {
        var file_recip = $('input[name=file_recipient');
        var recip = $('input[name=recipient');
        file_recip.wrap('<form>').closest('form').get(0).reset();
        file_recip.unwrap();
        recip.val("");
        countEmails();
        $('#csv_recipient').val("");
        // debugger;
        var parent = $('#csv_content_wrapper').parent();
        // var div = $("<div class='add-mrg-top'></div>");
        parent.empty();
        var table = $("<table id='csv_content' class='fixed_header'></table>");
        parent.wrapInner(table);

        $("button[name=btnReset]").hide();
    });
});


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
        }
        data = Papa.parse(data, {
            header: true,
            error: () => {
                showPapaErrors(data.errors);
                return;
            }
        });

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
        console.log(csvObj.rawdata);
        console.log(csvObj.data);
        showPlaceholders(csvObj.headers);
        countEmails();
        $("button[name=btnReset]").show();
    }
}

function showPapaErrors(errorArray) {
    // errorArray = [...new Set(errorArray.errors.map(x => x.message))];
    var title = "";
    var msg = $("<div style='color:red' />");
    errorArray.forEach(element => {
        title += element.type + "<br />";
        var itm = $('<pre />', {
            text: element.message
        });
        msg.append(itm);
    });

    Swal.fire({
        'title': title,
        'type': 'error',
        'html': msg
    });
}

function validate_csv(data) {
    var errs = [];
    var errDetail = [];
    var header_valid, email_column;
    var header_row = data.meta.fields; // should be column header
    var first_row_values = Object.values(data.data[0]); // should begin data
    // has an email column?
    var has_email_column = (first_row_values.filter(word => isValidEmailAddress(word)).length > 0);

    // validate required columns
    var required_columns = validateRequiredKeys(header_row);
    if (required_columns.errors.length > 0) errDetail = errDetail.concat(required_columns.errors);
    header_valid = required_columns.validHeader;
    email_column = required_columns.email_column;

    // generate email list and generate converted data to obj with tokenized keys  
    var emails = [];
    var tokenized_obj = [];
    if (email_column) {
        data.data.forEach(row => {
            var newRow = {};
            var token_key, current_csv;
            var current_email = row[email_column.original];
            emails.push(current_email.trim());
            for (var itm in row) {
                token_key = required_columns.headerKeyMap[itm];
                current_csv = $('#csv_recipient').val().split(/\n+/g);
                current_csv[0] = current_csv[0].replace(itm, token_key);
                let new_csv = current_csv.join('\n');
                $('#csv_recipient').val(new_csv);
                newRow[token_key] = row[itm];
            }
            tokenized_obj.unshift(newRow);
        });
    }

    if (!email_column) errs.unshift("Cannot determine email column");
    if (!header_valid) errs.unshift("Invalid Header Row");
    if (has_email_column && !header_valid) {
        errs.unshift('Email column has invalid header');
    } else if (!has_email_column) {
        errs.unshift("No Email Column found");
    }
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
    }
}

function intersection(d, regX) {
    // var dataArray = d.map(val => val.toLowerCase().trim());
    var dataArray = d;
    var foundColumn = [];
    dataArray.forEach(csvColumn => {
        var testColumn = csvColumn.toLowerCase().trim();
        if (regX.test(testColumn)) {
            foundColumn.push({
                original: csvColumn.trim(),
                val: tokenizeKey(testColumn)
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
    $('#list').prepend(msg);
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

function setUI() {
    // hijacks default 'view email' button for SweetAlert2 action!
    $('a.m-link').bind('click', (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        rel = e.target.rel;
        sweetAlertbyID(`.${rel}`);
    });
    $("button[name=btnReset]").hide();

    $("input[name=recipient]").prop("readonly", true);
}

function parseFile(file) {
    Papa.parse(file, {
        header: true,
        transformHeader: tokenizeKeys,
        error: (e, file) => {
            showPapaErrors(data.errors);
            return;
        },
        complete: (results, file) => {
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
            // printTable(results.data);
            printTable(results);

            var reader = new FileReader();
            reader.readAsText(file);
            reader.onload = function(event) {
                var csv = event.target.result;
                $("#csv_recipient").val(csv);
                getEmails(results);
                $("#csv_recipient").val("");
            };
            reader.onerror = function() {
                Swal.fire('Unable to read ' + file.fileName);
            };
            console.log("Parsing Complete:", results, file);
        }
    });
}

function handleFileSelect(evt) {
    // clear prev results
    $("#list").html("");
    $("#csv_content").html("");

    parseFile(evt.target.files[0]);
}

function printTable(data) {
    // var dataCopy = data.concat();
    // var header = dataCopy.shift();
    var header = data.meta.fields;
    var cols = [];
    header.forEach(v => {
        cols.push({
            title: v
        });
    });
    // var table = $('#csv_content');
    // var thead = $('<thead/>');
    // var tr = $('<tr/>');
    // Object.keys(header).forEach(itm => {
    //     var th = $('<th>')
    //         .addClass('csv_table')
    //         .text(itm);
    //     tr.append(th);
    // });
    // table.append(thead.append(tr));


    // var tbody = $('<tbody/>');
    // data.forEach(record => {
    //     var tr = $('<tr/>');
    //     Object.values(record).forEach(val => {
    //         var td = $('<td>')
    //             .text(val);
    //         tr.append(td);
    //     });
    //     tbody.append(tr);
    // });
    // table.append(tbody);

    // format data
    var dtData = [];
    data.data.forEach(val => {
        dtData.push(Object.values(val));
    });
    // debugger;
    $('#csv_content')
        .addClass('fixed_header display')
        .DataTable({
            "initComplete": function() {
                var api = this.api();
                api.$('td').click(function() {
                    api.search(this.innerHTML).draw();
                });
            },
            columns: cols,
            data: dtData,
            "paging": false,
            "ordering": false,
        });
    $("button[name=btnReset]").show();

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
    })
}