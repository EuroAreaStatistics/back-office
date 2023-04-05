// javascript functions to load and configure tinyMCE editor

var unsavedChanges = false;
var idle = false;
var AUTOSAVETIMEOUT = 60000; // 10 minutes
var autoSaveTimeoutID;

var matches = window.location.href.match(/[?&]mode=([^&]+)/);
var mode = 'lang';
if (matches !== null) {
  mode = matches[1];
}

// set preview URL to snapshots for simpleChart on OECD theme
var snapshotsURL;
if (baseURL == '//edit.compareyourcountry.org') {
  snapshotsURL = '//snapshots.compareyourcountry.org';
} else {
  snapshotsURL = baseURL;
}

var settings = {
  selector: "div.editable",
  inline: true,
  toolbar: "undo redo | nonbreaking | italic bold | link",
  menubar: false,
  plugins: [ "link", "nonbreaking" ],
  entity_encoding: "raw",
  browser_spellcheck: true,
  nonbreaking_force_tab: false,
  valid_styles: {
    "*": ""
  },
  extended_valid_elements: 'span[style]',
  setup: function(ed) {
    ed.on('change', function(e) {
      $('#submitButton').prop('value', $('#submitButton').data('change'));
      $('#submitButton').css('background-color', 'red');
      unsavedChanges = true;
      idle = false;
      if (lang != 'en') {
        var tooLong = $(this.getContent()).text().length > this.getBody().getAttribute('data-maxlength');
        $(document.getElementById("long/" + this.id)).toggle(tooLong);
      }
    });
  }
};

tinymce.init(settings);

var matches = window.location.href.match(/[?&]project=([^&]+)/);
var project = 'none';
if (matches !== null) {
  project = matches[1];
}

var matches1 = window.location.href.match(/[?&]lg=([^&]+)/);
var lang = 'en';
if (matches1 !== null) {
  lang = matches1[1];
}

var matches2 = window.location.href.match(/[?&]cr=([^&]+)/);
var country = 'aus';
if (matches2 !== null) {
  country = matches2[1];
}

var matches3 = window.location.href.match(/[?&]mode=([^&]+)/);
var mode = 'lang';
if (matches3 !== null) {
  mode = matches3[1];
}

$(function() {
  var separator = '|';

  // @memo: autoSaveTimeoutID = window.setTimeout(autoSave, AUTOSAVETIMEOUT);

  if (lang != 'en') {
    $("div.editable").each(function() {
      var id = $(this).attr('id');
      var maxLength = Math.ceil($(document.getElementById("ref/" + id)).text().length * 1.30);
      var tooLong = $(this).text().length > maxLength;
      $(this).attr('data-maxlength', maxLength);
      $(document.getElementById("long/" + id)).toggle(tooLong);
    });
  }
  $( "#submitForm" ).submit(function( event ) {
    var form = $(this);
    unsavedChanges = false;
    idle = false;
    window.clearTimeout(autoSaveTimeoutID);
    tinymce.editors.forEach(function(ed) {
      var k = ed.id.split(separator).map(function(p, i) {
        return i ? '[' + p + ']' : p;
      }).join('');
      var v = ed.getContent();
      ed.isNotDirty = false;
      $('<input type="hidden">').attr('name', k).attr('value', v).appendTo(form);
    });
  });

  $('#selectLang').change(updateLanguage);
  $('#selectLang').val('start');

  function updateLanguage() {
    var value = $(this).val();
    location = 'editmain.php?project=' + project + '&lg=' + value + '&cr=' + country + '&mode=' + mode + '';
  }

  $('#selectCountry').change(updateCountry);
  $('#selectCountry').val('start');

  function updateCountry() {
    var value = $(this).val();
    var country = $(this).val();
    location = 'editmain.php?project=' + project + '&lg=' + lang + '&cr=' + value + '&mode=' + mode + '';
  }

  $('#homeButton').click(home);

  function home() {
    if (mode == 'wizard') {
      location = '../wizard/index.php';
    } else {
      location = 'index.php';
    }
  }

  $('.htmlUpload').change(uploadHTML);

  function uploadHTML() {
    if (window.FileReader) {
      var f = new FileReader();
      f.onload = uploadFile;
      f.readAsText($(this).find('input')[0].files[0]);
    } else {
      $(this).parent('form').submit();
    }
  }

  function uploadFile(e) {
    $(e.target.result).find('tr').each(function() {
      var key = 'v' + separator + $(this).find('th').html();
      var value = $(this).find('td').html();
      if (key in tinymce.editors) {
        var oldValue = tinymce.editors[key].getContent();
        if (value != oldValue) {
          tinymce.editors[key].setContent(value);
          if (tinymce.editors[key].getContent() != oldValue) {
            tinymce.editors[key].fire('change');
          }
        }
      }
    });
  }

  $('.htmlButton').click(downloadHTML);

  function downloadHTML() {
    var ref = $(this).attr('data-ref');
    var doc = '<!DOCTYPE html>\n';
    doc += '<html><head><meta charset="utf-8"></head><body>';
    var header = $('.navText');
    if (ref) {
      header = header.clone();
      header.find('span').first().text('English');
    }
    doc += '<h1>' + header.text().trim() + '</h1><table>\n';
    tinymce.editors.forEach(function(ed) {
      var divs = ed.getElement().parentElement.parentElement.querySelectorAll('div');
      var txt = ref ? ('<p>' + divs[1].innerHTML + '</p>') : ed.getContent();
      doc += '<tr><th valign=top align=left>' + divs[0].textContent + '</th><td>' + txt + '</td></tr>\n';
    });
    doc += '</table></body></html>';
    var name = project + '-' + (ref ? 'en' : lang) + '.html';
    saveFile('text/html', 'utf-8', name) (doc.replace(/\xa0/g,'&nbsp;'));
  }

  function saveFile(type, charset, name) {
    if (window.navigator.msSaveOrOpenBlob) {
      return function(data) {
        blob = new Blob([data], { type: type + ';' + charset });
        window.navigator.msSaveOrOpenBlob(blob, name);
      };
    }
    if ('download' in document.createElement('a')) {
      return function(data) {
        var link = $('<a style="display:none">')
          .attr('href', 'data:' + type + ';' + charset + ',' + encodeURIComponent(data))
          .attr('download', name)
          .appendTo('body');
        link[0].click();
        link.remove();
      };
    }
    return function(data) {
      var iframe = $('<iframe style="display:none">')
          .appendTo('body');
      var doc = iframe[0].contentDocument || iframe[0].contentWindow.document;
      doc.open(type, 'replace');
      doc.charset = charset;
      doc.write(data);
      doc.close();
      doc.execCommand('SaveAs', true, name);
    };
  }

  $('#previewButton').click(preview);

  function preview() {
    if (project == 'none') {
      window.open ($(this).attr('data-url') + '?lg=' + lang, '_blank');
    } else {
      if (project.substring(0, 2) == 's-') {
        window.open (snapshotsURL + '/snapshotspreview?project=' + project + '&lg=' + lang, '_blank');
      } else {
        window.open ($(this).attr('data-url') + project + '?lg=' + lang, '_blank');
      }
    }
  }

  if (project == 'langmain') {
    $('#previewButton').hide();
  }
});

$(window).bind("beforeunload", function() {
  if (unsavedChanges) {
    return "Changes not saved";
  }
});
