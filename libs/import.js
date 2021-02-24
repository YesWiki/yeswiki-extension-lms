console.log('coucou import');
$('.btn-import-form').click(function(e) {
  e.preventDefault()

  let url = $('#import_url').val();
  let token = $('#token').val();
  let resultforms = $('#import-forms-result');
  let resultimporttable = $('#import-forms-table');
  let resultimportform = $('#import-forms-form');
  let formtranslations = $('#form-translations').data();

  // expression réguliere pour trouver une url valide
  var rgHttpUrl = /^(http|https):\/\/(([a-zA-Z0-9$\-_.+!*'(),;:&=]|%[0-9a-fA-F]{2})+@)?(((25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])){3})|localhost|([a-zA-Z0-9\-\u00C0-\u017F]+\.)+([a-zA-Z]{2,}))(:[0-9]+)?(\/(([a-zA-Z0-9$\-_.+!*'(),;:@&=]|%[0-9a-fA-F]{2})*(\/([a-zA-Z0-9$\-_.+!*'(),;:@&=]|%[0-9a-fA-F]{2})*)*)?(\?([a-zA-Z0-9$\-_.+!*'(),;:@&=\/?]|%[0-9a-fA-F]{2})*)?(\#([a-zA-Z0-9$\-_.+!*'(),;:@&=\/?]|%[0-9a-fA-F]{2})*)?)?$/;

  if (rgHttpUrl.test(url)) {
    // on formate l url pour acceder au service json de yeswiki
    var taburl = url.split('wakka.php');
    url = taburl[0].replace(/\/+$/g, '') + '/?api/fiche/1201';
    resultforms.html('<div class="alert alert-info"><span class="throbber">' + formtranslations.loading + '...</span> ' + formtranslations.recuperation + ' ' + url + '</div>');
    $.ajax({
      method: 'GET',
      url: url,
      headers: {
        "Authorization": "Bearer " + token,
      },
    }).done(function(data) {
      resultforms.html('');
      var count = 0;
      for (var idform in data) {
        if (data.hasOwnProperty(idform)) {
          count++;
          var trclass = '';
          var existingmessage = '';
          var tablerow = '<tr' + trclass + '><td><label><input type="checkbox" name="imported-form[' + data[idform].bn_id_nature + ']" value="' + JSON.stringify(data[idform]).replace(/"/g, '&quot;') + '"><span></span></label></td><td><strong>' + data[idform].bn_label_nature + '</strong>';
          if (data[idform].bn_description && 0 !== data[idform].bn_description.length) {
            tablerow += '<br>' + data[idform].bn_description;
          }

          tablerow += existingmessage + '</td><td>' + data[idform].bn_id_nature + '</td></tr>';
          resultimporttable.find('tbody').append(tablerow);
        }
      }

      resultimportform.removeClass('hide');
      resultimporttable.DataTable(DATATABLE_OPTIONS);
      resultforms.prepend('<div class="alert alert-success">' + formtranslations.nbformsfound + ' : ' + count + '</div>');
    }).fail(function(jqXHR, textStatus, errorThrown) {
      resultforms.html('<div class="alert alert-danger">' + formtranslations.noanswers + '.</div>');
    });
  } else {
    resultforms.html('<div class="alert alert-danger">' + formtranslations.notvalidurl + ' : ' + url + '</div>');
  }

  return false;
});
