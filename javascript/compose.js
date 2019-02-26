<<<<<<< Updated upstream
$(document).ready(function() {
    setUI();
    slide();

    if (isAPIAvailable()) {
        $("input[name='file_recipient']").change(handleFileSelect);
    }
    $("#recipient_type").change(slide);
    $("#csv_recipient").change(getEmails);
    $("#recipient").change(countEmails);
    $("select[name='mailtype']").change(messageType);
});

function messageType() {
    if ($("select[name='mailtype']").val() === "html") {
        $("textarea[name='plaintext_alt']").parents("fieldset").eq(0).slideDown();
    } else {
        $("textarea[name='plaintext_alt']").parents("fieldset").eq(0).slideUp();
    }
}

function countEmails() {
    var email_string = $("#recipient").val();
    var emails = email_string.split(', ');
    var count = (emails[0] === "") ? 0 : emails.length;

    var label = $(".primary_recip > .field-instruct > label");
    var origText = label.text();
    // preserve  original label just append count string
    if (origText.includes('Count')) {
        var idx = origText.indexOf(' (Recip');
        if (idx > -1) origText = origText.substr(0, idx);
    }
    var countText = ` (Recipient Count: ${count})`;
    label.text(origText + countText);
}

function getEmails() {
    var raw = $("#csv_recipient").val();
    var csvObj = validData($.csv.toObjects(raw));
    if (csvObj) {
        $('input[name="csv_object"]').val(csvObj.data_string);
        $("input[name='recipient_count']").val(csvObj.recipient_count);
        $('#recipient').val(csvObj.email_list);
        $("#recipient_type").val('recipient').change();
        showPlaceholders(csvObj.headers);
        countEmails();
        slide();
    }
}

function validData(data) {
    var errs = [];
    var first_row = Object.keys(data[0]); // should be column header
    var test_row = Object.keys(data[1]); // should begin data

    // has an email header column?
    var key = getMailKey(first_row);

    // has an email column?
    var email_column = first_row.filter(word => isValidEmailAddress(word));
    if (!key) errs.push("Missing Email Column");
    if (email_column.length > 0) errs.push("No Email Column found");

    // generate email list 
    if (key) {
        var emails = [];
        data.forEach(element => {
            emails.push(element[key]);
        });
    }
    if (errs.length === 0) {
        return {
            'mailkey': key,
            'headers': tokenizeKeys(first_row),
            'data_string': JSON.stringify(data),
            'data': data,
            'recipient_count': emails.length,
            'email_list': emails.join(', ')
        };
    } else {
        var msg = errs.join('<br/>');
        debugger;

        msg = "<ul class='sidebar'>";
        errs.forEach(element => {
            msg += `<li>${element}</li>`;
        });
        msg += '</ul>';
        swal({
            title: 'Invalid Data',
            'html': msg,
            'icon': 'error'
        });
    }
}

function getMailKey(data) {
    var retVal = false;
    var keyColumn = ['mail', 'email', 'address'];
    var keys = [];
    keys.push(data.find((k) => {
        k = k.trim()
        var isEmail = isValidEmailAddress(k);
        var inArray = $.inArray(k, keyColumn);
        return (inArray >= 0 && !isEmail);
    }));
    return (typeof keys[0] === 'undefined') ? retVal : keys[0];
}

function isValidEmailAddress(emailAddress) {
    var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
    return pattern.test(emailAddress);
}

function tokenizeKeys(data) {
    var newData = [];
    data.forEach(key => {
        var newRow = {};
        var lowerKey = key.toLowerCase();
        var newKey = lowerKey.replace(' ', '_');
        newRow = "{{" + newKey + "}}";
        newData.push(newRow);
    });
    return newData;
}

function showPlaceholders(headers) {
    var el_exists = document.getElementById('placeholder');
    if (el_exists) {
        $('#placeholder').remove();
    }
    $('.sidebar').after("<table id='placeholder'><caption>Placeholders</caption></table>");
    headers.forEach(el => {
        var test = $('<button/>', {
            class: 'placeholder icon-envelope',
            text: el,
            click: function() {
                var plain = $('textarea[name=\'plaintext_alt\']');
                var msg = $('textarea[name=\'message\']');
                var message = ($('textarea[name=\'plaintext_alt\']').is(':visible')) ? plain : msg;
                message.val(message.val() + $(this).text());
                message.focus();
            }
        }).wrap('<tr><td align="center"></td></tr>').closest('tr');

        $("#placeholder").append(test);
    });

}

function slide() {
    var defaults = {
        'recipient': '#recipient',
        'csv_recipient': '#csv_recipient',
        'file_recipient': "input[name='file_recipient']"
    };

    var selected = $("#recipient_type").val();
    const keys = Object.keys(defaults);
    for (const key of keys) {
        if (key === selected) {
            $(defaults[key]).parents("fieldset").eq(0).slideDown();
        } else {
            $(defaults[key]).parents("fieldset").eq(0).slideUp();
        }
    }
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
        document.writeln(warning);
        return false;
    }
}

function setUI() {
    //$('select').dropdown();
}

function handleFileSelect(evt) {
    // clear prev results
    $("#list").html("");
    $("#csv_content").html("");

    var files = evt.target.files; // FileList object
    var file = files[0];

    // read the file metadata
    var output = '';
    output += '<span style="font-weight:bold;">' + escape(file.name) + '</span><br />\n';
    output += ' - FileType: ' + (file.type || 'n/a') + '<br />\n';
    output += ' - FileSize: ' + file.size + ' bytes<br />\n';
    output += ' - LastModified: ' + (file.lastModifiedDate ? file.lastModifiedDate.toLocaleDateString() : 'n/a') + '<br />\n';

    // read the file contents
    printTable(file);

    // post the results
    $('#list').append(output);
}

function printTable(file) {
    var reader = new FileReader();
    reader.readAsText(file);
    reader.onload = function(event) {
        var csv = event.target.result;
        var data = $.csv.toArrays(csv);
        $("#csv_recipient").val(csv);
        $("#recipient").selectedIndex = 0;
        slide();
        getEmails();
        var html = '';
        for (var row in data) {
            html += '<tr>\r\n';
            for (var item in data[row]) {
                html += '<td>' + data[row][item] + '</td>\r\n';
            }
            html += '</tr>\r\n';
        }
        $('#csv_content').html(html);
    };
    reader.onerror = function() {
        alert('Unable to read ' + file.fileName);
    };
=======
$(document).ready(function() {
    setUI();
    slide();

    if (isAPIAvailable()) {
        $("input[name='file_recipient']").change(handleFileSelect);
    }
    $("#recipient_type").change(slide);
    $("#csv_recipient").change(getEmails);
    $("#recipient").change(countEmails);
    $("select[name='mailtype']").change(messageType);
});

function messageType() {
    if ($("select[name='mailtype']").val() === "html") {
        $("textarea[name='plaintext_alt']").parents("fieldset").eq(0).slideDown();
    } else {
        $("textarea[name='plaintext_alt']").parents("fieldset").eq(0).slideUp();
    }
}

function countEmails() {
    var email_string = $("#recipient").val();
    var emails = email_string.split(',');
    var count = (emails[0] === "") ? 0 : emails.length;

    var label = $(".primary_recip > .field-instruct > label");
    var origText = label.text();
    // preserve  original label just append count string
    if (origText.includes('Count')) {
        var idx = origText.indexOf(' (Recip');
        if (idx > -1) origText = origText.substr(0, idx);
    }
    var countText = ` (Recipient Count: ${count})`;
    label.text(origText + countText);
}

function getEmails() {
    var raw = $("#csv_recipient").val();
    var csvObj = validData($.csv.toObjects(raw));
    console.log(csvObj);
    if (csvObj) {
        $('input[name="csv_object"]').val(csvObj.data_string);
        $("input[name='recipient_count']").val(csvObj.recipient_count);
        $("input[name='mailKey']").val(csvObj.mailkey);
        $("input[name='formatted_emails']").val(csvObj.formatted.join(','));
        $('#recipient').val(csvObj.email_list);
        $("#recipient_type").val('recipient').change();
        showPlaceholders(csvObj.headers);
        countEmails();
        slide();
    }
}

function validData(data) {
    var errs = [];
    var first_row = Object.keys(data[0]); // should be column header
    var test_row = Object.keys(data[1]); // should begin data

    // has an email header column?
    var key = getMailKey(first_row);

    // has an email column?
    var invalid_email_column = first_row.filter(word => isValidEmailAddress(word));
    var email_column = test_row.filter(word => isValidEmailAddress(word));

    // generate email list and generate converted data to obj with tokenized keys  
    var emails = [];
    var formatted = [];
    var tokenized_obj = [];
    if (key) {
        data.forEach(row => {
            emails.push(row[key]);
            formatted.push(`${row.first_name} ${row.last_name} <${row[key]}>`);
            var newRow = {};
            for (var itm in row) {
                debugger;
                var token_key = `{{${itm}}}`;
                newRow[token_key] = row[itm];
            }
            tokenized_obj.push(newRow);
        });
    }

    if (!key) errs.push("Cannot determine email column");
    if (invalid_email_column.length > 0) errs.push("Missing Header Row");
    if (email_column.length > 0) errs.push("No Email Column found");

    if (errs.length === 0) {
        return {
            'mailkey': `{{${key}}}`,
            'headers': tokenizeKeys(first_row),
            'data_string': JSON.stringify(tokenized_obj),
            'data': data,
            'recipient_count': emails.length,
            'formatted': formatted,
            'email_list': emails.join(',')
        };
    } else {
        var msg = $("<ul />");
        errs.forEach(element => {
            var itm = $('<li/>', {
                text: elment,
            });
            msg.append(itm);
        });
        Swal.fire({
            'title': 'Invalid Data',
            'type': 'error',
            'html': msg
        });
    }
}

function getMailKey(data) {
    var retVal = false;
    var keyColumn = ['mail', 'email', 'address'];
    var keys = [];
    keys.push(data.find((k) => {
        k = k.trim();
        var isEmail = isValidEmailAddress(k);
        var inArray = $.inArray(k, keyColumn);
        return (inArray >= 0 && !isEmail);
    }));
    return (typeof keys[0] === 'undefined') ? retVal : keys[0];
}

function isValidEmailAddress(emailAddress) {
    var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
    return pattern.test(emailAddress);
}

function tokenizeKeys(data) {
    var newData = [];
    data.forEach(key => {
        var newRow = {};
        var lowerKey = key.toLowerCase();
        var newKey = lowerKey.replace(' ', '_');
        newRow = "{{" + newKey + "}}";
        newData.push(newRow);
    });
    return newData;
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
                message.val(message.val() + $(this).text());
                message.focus();
            }
        }).wrap('<tr><td align="center"></td></tr>').closest('tr');

        $("#placeholder").append(test);
    });

}

function slide() {
    var defaults = {
        'recipient': '#recipient',
        'csv_recipient': '#csv_recipient',
        'file_recipient': "input[name='file_recipient']"
    };

    var selected = $("#recipient_type").val();
    const keys = Object.keys(defaults);
    for (const key of keys) {
        if (key === selected) {
            $(defaults[key]).parents("fieldset").eq(0).slideDown();
        } else {
            $(defaults[key]).parents("fieldset").eq(0).slideUp();
        }
    }
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
        document.writeln(warning);
        return false;
    }
}

function setUI() {
    //$('select').dropdown();
}

function handleFileSelect(evt) {
    // clear prev results
    $("#list").html("");
    $("#csv_content").html("");

    var files = evt.target.files; // FileList object
    var file = files[0];

    // read the file metadata
    var output = '';
    output += '<span style="font-weight:bold;">' + escape(file.name) + '</span><br />\n';
    output += ' - FileType: ' + (file.type || 'n/a') + '<br />\n';
    output += ' - FileSize: ' + file.size + ' bytes<br />\n';
    output += ' - LastModified: ' + (file.lastModifiedDate ? file.lastModifiedDate.toLocaleDateString() : 'n/a') + '<br />\n';

    // read the file contents
    printTable(file);

    // post the results
    $('#list').append(output);
}

function printTable(file) {
    var reader = new FileReader();
    reader.readAsText(file);
    reader.onload = function(event) {
        var csv = event.target.result;
        var data = $.csv.toArrays(csv);
        $("#csv_recipient").val(csv);
        $("#recipient").selectedIndex = 0;
        slide();
        getEmails();
        var html = '';
        var len = data.length - 1;
        html += '<tr>\r\n';
        for (var item in data[0]) {
            html += '<th>' + data[0][item] + '</th>\r\n';
        }
        html += '</tr>\r\n';
        for (let i = 0; i < len; i++) {
            const element = data[i];
            html += '<tr>\r\n';
            for (item in element) {
                html += '<td>' + element[item] + '</td>\r\n';
            }
            html += '</tr>\r\n';
        }
        $('#csv_content').html(html);
    };
    reader.onerror = function() {
        Swal.fire('Unable to read ' + file.fileName);
    };
}

function dumpHiddenVals() {
    var msg = $("<table/>");
    $('input[type="hidden"]').each(function() {
        var val = $(this).val();
        var val = (val.length > 100) ? val.substring(0, 100) + '...' : val;
        console.log($(this).attr('name') + ": " + $(this).val());
        msg.append("<tr><td>" + $(this).attr('name') + "</td><td>" + val + "</td></tr>");
    });
    swal.fire({
        title: 'HIDDEN VALS',
        type: 'info',
        html: msg,
        width: '80%',
    });
>>>>>>> Stashed changes
}