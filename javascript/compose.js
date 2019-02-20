$(document).ready(function() {
    // slide();
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
    var data = $.csv.toObjects(raw);

    data = tokenizeKeys(data);

    $('input[name="csv_object"]').val(JSON.stringify(data));
    var headers = Object.keys(data[0]);
    showPlaceholders(headers);

    var emails = [];
    data.forEach(element => {
        emails.push(element['{{email}}']);
    });

    $("input[name='recipient_count']").val(emails.length);
    $('#recipient').val(emails.join(', '));
    countEmails();
}

function tokenizeKeys(data) {
    var newData = [];
    data.forEach(row => {
        var newRow = {};
        for (const key in row) {
            newRow["{{" + key + "}}"] = row[key];
        }
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
        document.writeln('The HTML5 APIs used in this form are only available in the following browsers:<br />');
        // 6.0 File API & 13.0 <output>
        document.writeln(' - Google Chrome: 13.0 or later<br />');
        // 3.6 File API & 6.0 <output>
        document.writeln(' - Mozilla Firefox: 6.0 or later<br />');
        // 10.0 File API & 10.0 <output>
        document.writeln(' - Internet Explorer: Not supported (partial support expected in 10.0)<br />');
        // ? File API & 5.1 <output>
        document.writeln(' - Safari: Not supported<br />');
        // ? File API & 9.2 <output>
        document.writeln(' - Opera: Not supported');
        return false;
    }
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
}