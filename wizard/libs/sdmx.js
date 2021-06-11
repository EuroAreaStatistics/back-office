// asynchronously load data via SDMX REST API (2.0 and 2.1)
// calls options.success(data, warnings) on success,
// calls options.error(errmsg) on error
// ISO codes are checked with options.getISOcode(code)
// and translated with options.getCountryISOcode(code)
//
// requires JQuery

function loadSDMX(options) {
    var hash = {};
    var keys = [];
    var dimensions = [];
    var times = [];
    var data = [];
    var warnings = [];
    var jsonData = null;
    var timestamp = null;

    function addKey(key, value) {
        if (!(key in hash)) hash[key] = {};
        hash[key][value] = null;
    }

    function setKeysAndDimensions() {
        var guess = null;
        Object.keys(hash).sort().forEach(function(dim) {
            var k = Object.keys(hash[dim]).sort();
            if (k.length == 1) {
                if (guess == null || options.getISOcode(k[0]) != null) {
                    guess = [dim, k];
                }
                return;
            }
            dimensions.push(dim);
            keys.push(k);
        });
        if (!dimensions.length && guess != null) {
              // guess country key if data is one dimensional
              dimensions.push(guess[0]);
              keys.push(guess[1]);
        }
        if (!dimensions.length) throw new Error("no data");
        if (options.type == 'flow') {
            if (dimensions.length != 2) throw new Error("need 3 dimensions in data", dimensions);
        } else {
            if (dimensions.length != 1) throw new Error("more than 2 dimensions in data", dimensions);
        }

        // remove invalid country keys
        keys = keys.map(function(dim) {
            return dim.filter(function(key) {
                if (options.getISOcode(key) == null) {
                    warnings.push('"'+key+'" series will not be displayed on maps');
                    return true;
                }
                return true;
            });
        });
    }

    // returns unique array with unspecified order
    function unique(a) {
        var r = {};
        a.forEach(function(e) { r[e] = null; });
        return Object.keys(r);
    }

    function setTimes(els) {
        times = unique(els.get()).sort();
    }

    function setValue(key, time, val, mult) {
        var timeIdx = times.indexOf(time);
        val = parseFloat(val);
        if (isNaN(val)) val = null;
        if (val != null) val *= mult;

        var keyIdxs = dimensions.map(function(dim, i) {
          return keys[i].indexOf(key[dim]);
        });
        if (keyIdxs.indexOf(-1) != -1) return;

        var d = data;
        keyIdxs.forEach(function(keyIdx) {
          while (d.length < keyIdx+1) d.push([]);
          d = d[keyIdx];
        });
        while (d.length < timeIdx+1) d.push(null);
        d[timeIdx] = val;
    }

    function setData() {
        keys = keys.map(function(dim) {
            return dim.map(function(key) {
                return options.getCountryISOcode(key);
            });
        });
        keys.push(times);
        jsonData = {
            dimensions: ['LOCATION', 'YEAR'],
            keys: keys,
            data: data,
            url: options.url,
        };
        if (timestamp != null) {
          jsonData.fetchDate = timestamp;
        }
        if (options.type == 'flow') {
            jsonData.dimensions = ['LOCATION', 'PARTNER', 'YEAR'];
         }
    }

    function loadSDMX_2_0(xml) {
        timestamp = $(xml).find('Header Prepared').text() + '.000Z';
        $(xml).find('SeriesKey Value').each(function() {
            addKey($(this).attr('concept'), $(this).attr('value'));
        });
        setKeysAndDimensions();
        setTimes($(xml).find('Time').map(function() { return $(this).text(); }));
        $(xml).find('Series').each(function() {
            var power = $(this).find('Attributes Value[concept="POWERCODE"]').attr('value') | 0;
            var mult = Math.pow(10, power);
            var key = {};
            $(this)
                .find('SeriesKey Value')
                .each(function() { key[$(this).attr('concept')] = $(this).attr('value'); });
            $(this).find('Obs').each(function() {
                var time = $(this).find('Time').text();
                var val = $(this).find('ObsValue').attr('value');
                setValue(key, time, val, mult);
            });
        });
        setData();
    }

    function loadSDMX_2_1(xml) {
        $(xml).find('generic\\:SeriesKey, SeriesKey').find('generic\\:Value, Value').each(function() {
            addKey($(this).attr('id'), $(this).attr('value'));
        });
        setKeysAndDimensions();
        setTimes($(xml).find('generic\\:ObsDimension, ObsDimension').map(function() { return $(this).attr('value'); }));
        $(xml).find('generic\\:Series, Series').each(function() {
            var power = $(this).find('generic\\:Attributes, Attributes').find('generic\\:Value[id="UNIT_MULT"], Value[id="UNIT_MULT"]').attr('value') | 0;
            var mult = Math.pow(10, power);
            var key = {};
            $(this)
                .find('generic\\:SeriesKey, SeriesKey').find('generic\\:Value, Value')
                .each(function() { key[$(this).attr('id')] = $(this).attr('value'); });
            $(this).find('generic\\:Obs, Obs').each(function() {
                var time = $(this).find('generic\\:ObsDimension, ObsDimension').attr('value');
                var val = $(this).find('generic\\:ObsValue, ObsValue').attr('value');
                setValue(key, time, val, mult);
            });
        });
        setData();
    }

    function onLoad(xml, textStatus, jqXHR) {
        if (jqXHR.getResponseHeader('X-Data-Access') === 'private') {
          warnings.push('Data may have been retrieved from an unpublished data set');
        }
        var errmsg = null;
        try {
            var namespace = $(xml).children().get(0).namespaceURI;
            if (namespace == "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/message") {
                loadSDMX_2_1(xml);
            } else if (namespace == "http://www.SDMX.org/resources/SDMXML/schemas/v2_0/message") {
                loadSDMX_2_0(xml);
            } else throw Error("unknown namespace "+namespace);
        } catch (e) {
            errmsg = e.message;
        }
        if (errmsg != null) options.error(errmsg);
        else {
          var lastModified = jqXHR.getResponseHeader('Last-Modified');
          if (lastModified != null) {
            jsonData.fetchDate = new Date(lastModified).toISOString();
          } else if (jsonData.fetchDate == null) {
            jsonData.fetchDate = new Date().toISOString();
          }
          options.success(jsonData, warnings);
        }
    }

    function onError(jqXHR, textStatus, errorThrown) {
        var errmsg = errorThrown || textStatus || 'unknown AJAX error';
        if (jqXHR.getResponseHeader('Content-type')==='application/json') {
            try {
                errmsg = JSON.parse(jqXHR.responseText).error;
            } catch (e) {} // ignore errors
        } else if (errmsg == "error") {
          errmsg = "error fetching URL, see javascript console";
        }
        options.error(errmsg);
    }

    var url = options.url;
    url = url.replace(/^http:/, 'https:');
    $.ajax({
        url: url,
        dataType: "xml",
        success: onLoad,
        error: onError
    });
}
