// schreibt existierende Daten aus JSON Projekten in HTML template
// Eventhandler fuer Aenderungen im Formular
// Formular an Server schicken
// CSV import
// Validierung der Formulareintraege
//
// requires SDMX library

function runWizard(options) {
    var baseURL = options.baseURL;
    var previewURL = options.previewURL;
    var countryCodeMap = options.countryCodeMap;
    var lang = options.lang;
    var data = options.data;
    var mode = options.mode;


//set preview URL to snapshots for simpleChart and slider on OECD theme
    var snapshotsURL;
    if (baseURL == '//edit.compareyourcountry.org') {
      snapshotsURL = '//snapshots.compareyourcountry.org';
    } else {
      snapshotsURL = baseURL;
    }

// maximum number of tabs per project
    if (mode == 'slide' || mode == 'complex') {
        var MAX_TABS = 100;
    } else {
        var MAX_TABS = 4;
    }



// generate unique timestamps
    var clientID = (function(prefix) {
          var time = 0;
          return function(prefix) {
               if (prefix == null) prefix = "x";
               var newtime;
               while ((newtime = (new Date()).getTime()) == time) ;
               time = newtime;
               return prefix + time;
          };
     })();

// models
     function Page(lang, config) {
         var self = this;
         self.tabs = {};
         self.charts = {};
         self.embedded = false;

         config = config || {};

         $.each(config.charts, function(id, data) {
             if (data.embedded) {
               self.embedded = true;
             }
             data.id = id;
             var chart = new Chart(lang, data);
             self.charts[chart.id] = chart;
         });
         $.each(config.tabs, function(id, data) {
             data.id = id;
             var tab = new Tab(lang, data);
             $.each(data.charts, function (j, id) { tab.addChart(self.charts[id]); });
             self.tabs[tab.id] = tab;
         });
         self.project = new Project(lang, config.project);
         $.each(config.project.tabs, function (j, id) { self.project.addTab(self.tabs[id]); });

         self.addProjectTab = function(data) {
             var tab = new Tab(lang, data);
             self.tabs[tab.id] = tab;
             self.project.addTab(tab);
             if (tab.type == 'flow') {
                 self.addTabChart(tab, {type: 'default'});
                 self.addTabChart(tab, {type: 'flow'});
             }
             return tab;
         };

         self.addTabChart = function(tab, data) {
             var chart = new Chart(lang, data);
             self.charts[chart.id] = chart;
             tab.addChart(chart);
             return chart;
         };

         self.toJSON = function() {
             var data = {project: self.project, charts: self.charts, tabs: self.tabs};
             return data;
         };
     }

     function Project(lang, config) {
         var self = this;
         self.tabs = [];

         config = config || {};
         self.id = (config.id || clientID("p")) +"";
         self.titles = config.title || {};
         self.title = self.titles[lang] || "";
         self.options = config.options || {};
         self.url = config.url;
         self.owner = config.owner;

         // convert translatable options into strings
         $.each(self.options, function(k, v) {
             if (v != null && v[lang] != null) {
                 self.options[k] = v[lang];
             }
         });

         self.addTab = function(obj) {
             self.tabs.push(obj);
         };

         self.removeTab = function(obj) {
             self.tabs = self.tabs.filter(function(v) { return v !== obj; });
         };

         self.swapTabs = function(obj1, obj2) {
             self.tabs = self.tabs.map(function(v) {
                 if (v === obj1) return obj2;
                 if (v === obj2) return obj1;
                 return v;
             });
         };

         self.getErrors = function(field) {
             var errors = [];
             if (field == null || field == 'title') {
                 if (self.title == '') {
                     errors.push('title is required');
                 }
             }
             if (field == null || field == 'options') {
                 var required = [];
                 required.forEach(function (k) {
                     if (!(k in self.options) || self.options.k == '') {
                         errors.push(k+' is required');
                     }
                 });
             }
             if (field == null || field == 'tabs') {
                 self.tabs.forEach(function(tab,i) {
                    tab.getErrors().forEach(function(e) {
                       errors.push("tab "+(i+1)+": "+e);
                    });
                 });
             }
             return errors;
         };

         self.toJSON = function() {
             self.titles[lang] = self.title;
             if (self.url == null) {
                 self.url = self.titles['en']
                                .toLowerCase()
                                .replace(/[^a-z0-9]/g, "-")
                                .replace(/^-+/, "")
                                .replace(/-+$/, "");
             }

             // make all options translatable
             var options = $.extend({}, self.options);
             $.each(options, function(k,v) {
                 if (v != null) {
                     options[k] = {};
                     options[k][lang] = v;
                 }
             });

             return {
                 id: self.id,
                 url: self.url,
                 owner: self.owner,
                 title: self.titles,
                 options: options,
                 tabs: self.tabs.map(function (x) { return x.id; }),
             };
         };
     }

     function Tab(lang, config) {
         var self = this;
         self.charts = [];

//parameter tab read (php)                                     
         config = config || {};
         self.id = (config.id || clientID("t")) + "";
         self.titles = config.title || {};
         self.title = self.titles[lang] || "";
         self.teasers = config.teaser || {};
         self.teaser = self.teasers[lang] || "";
         self.template = config.template || 1;
         self.syncScale = config.syncScale || false;
         self.titleDisplay = config.titleDisplay || false;
         self.region = config.region || 'default';
         self.type = config.type || 'default';
         self.labels = config.labels || {};

         self.addChart = function(obj) {
             self.charts.push(obj);
         };

         self.removeChart = function(obj) {
             self.charts = self.charts.filter(function(v) { return v !== obj; });
         };

         self.swapCharts = function(obj1, obj2) {
             self.charts = self.charts.map(function(v) {
                 if (v === obj1) return obj2;
                 if (v === obj2) return obj1;
                 return v;
             });
         };

         self.getErrors = function(field) {
             var errors = [];
             if (mode != 'simple') {
                 if (field == null || field == 'title') {
                     if (self.title == '') {
                         errors.push('title is required');
                     }
                 }
             }
             if (field == null || field == 'charts') {
                 if (self.charts.length < 1) errors.push('at least 1 chart required');
                 self.charts.forEach(function(chart,i) {
                    chart.getErrors().forEach(function(e) {
                       errors.push("chart "+(i+1)+": "+e);
                    });
                 });
             }
             return errors;
         };

         self.generateLabels = function() {
           var labels = {};
           self.charts.forEach(function (chart) {
             if (chart.data == null) return;
             chart.data.keys.forEach(function (keys) {
               keys.forEach(function (key) {
                 if (!/^[0-9]{4}(-Q[1-4]|-[01][0-9])?$/.test(key) && getISOcode(key) == null ) {
                   labels[key] = self.labels[key] || {"en": key};
                 }
               });
             });
           });
           return labels;
         };

//parameter tab write (php)                                     
         self.toJSON = function() {
             self.titles[lang] = self.title;
             self.teasers[lang] = self.teaser;
             return {
                 title: self.titles,
                 teaser: self.teasers,
                 template: self.template,
                 syncScale: self.syncScale,
                 titleDisplay: self.titleDisplay,
                 region: self.region,
                 type: self.type,
                 deleted: self.deleted,
                 charts: self.charts.map(function (x) { return x.id; }),
                 labels: self.generateLabels(),
             };
         };

         // return ISO3 code for country which is either
         // - already an ISO3 code
         // - an ISO2 code
         // - an English country name
         function getISOcode(country) {
             country = country.toUpperCase().trim().replace("*", "");
             var code = null;
             $.each(countryCodeMap[self.region], function(key, value) {
                 if (key.toUpperCase() == country) {
                     code = value;
                     return false;
                 } else if (value.toUpperCase() == country) {
                     code = value;
                     return false;
                 }
             });
             return code;
         }

     }

     function Chart(lang, config) {
         var self = this;
         var CSVErrors = [];
         var CSVWarnings = [];

//parameter chart read (php)                                     
         config = config || {};
         self.id = (config.id || clientID("c")) + "";
         self.titles = config.title || {};
         self.title = self.titles[lang] || "";
         self.data = config.data;
         self.definitions = config.definition || {};
         self.definition = self.definitions[lang] || "";
         self.options = config.options || {};
         if (self.options instanceof Array) self.options = {};
         self.options.tooltipUnit = self.options.tooltipUnit || '';
         self.region = config.region || 'default';
         self.type = config.type || 'default';
         self.embedded = !!config.embedded;

         self.getWarnings = function() {
             return CSVWarnings.slice();
         };

         self.getErrors = function(field) {
             var errors = [];
             if (field == null || field == 'CSV') errors = CSVErrors.slice();
             if (field == null || field == 'title') {
                 if (self.title == '') {
                     errors.push('title is required');
                 }
             }
             return errors;
         }

         // return ISO3 code for country which is either
         // - already an ISO3 code
         // - an ISO2 code
         // - an English country name
         function getISOcode(country) {
             country = country.toUpperCase().trim().replace("*", "");
             var code = null;
             $.each(countryCodeMap[self.region], function(key, value) {
                 if (key.toUpperCase() == country) {
                     code = value;
                     return false;
                 } else if (value.toUpperCase() == country) {
                     code = value;
                     return false;
                 }
             });
             return code;
         }


         // return ISO3 code for country which is either
         // - already an ISO3 code
         // - an ISO2 code
         // - an English country name
         function getCountryISOcode(label) {
            country = label.toUpperCase().trim().replace("*", "");
             var code = null;
             var separator = '|';
             $.each(countryCodeMap[self.region], function(key, value) {
                 if (key.toUpperCase() == country) {
                     code = value;
                     return false;
                 } else if (value.toUpperCase() == country) {
                     code = value;
                     return false;
                 }
             });
             if (code == null && label.indexOf(separator) == -1) {
                code = label;
             }
             return code;
         }


         self.loadData = function(data, callback, delimiter) {
             function onSuccess(data, warnings) {
                 $("body").css("cursor", "auto");
                 self.data = data;
                 CSVWarnings = warnings;
                 callback();
             }

             function onError(e) {
                 $("body").css("cursor", "auto");
                 CSVErrors.push(e);
                 callback();
             }

             CSVErrors = [];
             CSVWarnings = [];
             if (!data.length) {
                 onError('empty file');
             } else if (/^\s*https?:\/\//.test(data)) {
                 $("body").css("cursor", "wait");
                 var url = data.trim();
                 loadSDMX({
                     url: url,
                     success: onSuccess,
                     error: onError,
                     getISOcode: getISOcode,
                     getCountryISOcode: getCountryISOcode,
                     type: self.type
                 });
             } else {
                 self.loadCSV(data, callback, delimiter,self.type);
                 // self.loadMultiCSV(data, callback, delimiter,self.type);
             }
         };


         self.loadDataMulti = function(data, callback, delimiter) {
             function onSuccess(data, warnings) {
                 $("body").css("cursor", "auto");
                 self.data = data;
                 CSVWarnings = warnings;
                 callback();
             }

             function onError(e) {
                 $("body").css("cursor", "auto");
                 CSVErrors.push(e);
                 callback();
             }

             CSVErrors = [];
             CSVWarnings = [];
             if (!data.length) {
                 onError('empty file');
             } else {
                 self.loadMultiCSV(data, callback, delimiter,self.type);
             }
         };


         self.loadCSV = function(data, callback, delimiter, type) {
             var reCell = /^[+-]?(\d+\.?\d*|\d*\.\d+)$/;

             function onParsed(err, output) {
                 try {
                     if (err) throw Error('syntax error in CSV file');
                     self.data = { dimensions: ['LOCATION', 'YEAR'], keys: [[], []], data: [] };
                     self.data.keys[1] = output.shift().slice(1);
                     var errors = [];
                     $.each(output, function(i, items) {
                         function parseItem(item) {
                             if (item == '..') return null;
                             if (item == 'm') return null;
                             if (!reCell.test(item)) {
                                 errors.push('invalid cell in line '+(i+2)+': '+item);
                             }
                             return parseFloat(item);
                         }

                         // remove spaces between cells
                         items = items.map(function(c) {
                               return c.trim();
                         });

                         var key = items.shift();
                         if (key != "") {
            // match country codes to ISO3 list
                            if (getISOcode(key) == null) {
                                CSVWarnings.push('"'+key+'" series will not be displayed on maps');
                            }

            // all labels codes allowed
                            var iso = getCountryISOcode(key);

                            self.data.keys[0].push(iso);
                            if (items.length != self.data.keys[1].length) {
                                errors.push('wrong number of columns in line '+(i+2));
                            }
                            self.data.data.push(items.map(parseItem));
                          }
                      });

                     // remove null (invalid) keys
                     self.data.keys[0] = self.data.keys[0].filter(function(key, idx) {
                         if (key != null) return true;
                         self.data.data[idx] = null;
                         return false;
                     });
                     self.data.data = self.data.data.filter(function(x) { return (x != null); });

                     if (!self.data.keys[0].length) throw new Error("no data");
                     if (errors.length) throw new Error(errors.join("\n"));
                 } catch (e) {
                     self.data = null;
                     CSVErrors.push(e.message);
                 }
                 callback();
             }

             CSVErrors = [];
             delimiter = delimiter || ',';
             if (!data.length) onParsed(new Error("empty file"));
             else {
                 Papa.DefaultDelimiter = delimiter;
                 var o = Papa.parse(data, {skipEmptyLines: true});
                 onParsed(0, o.data);
             }
         }

         self.loadMultiCSV = function(data, callback, delimiter, type) {
             var reCell = /^[+-]?(\d+\.?\d*|\d*\.\d+)$/;

             function onParsed(err, output) {
                 try {
                     if (err) throw Error('syntax error in CSV file');
                     self.data = {};
                     self.data.dimensions = output.shift();
                     self.data.dimensions.pop(); // remove value column
                     self.data.keys = self.data.dimensions.map(function() { return []; });
                     var errors = [];

                     // collect keys
                     $.each(output, function(i, items) {
                         if (items.length != self.data.dimensions.length+1) {
                             errors.push('wrong number of columns in line '+(i+2));
                         }

                         // remove spaces between cells
                         items = items.map(function(c) {
                               return c.trim();
                         });

                         for (var j=0; j<items.length-1; j++) {
                             var key = items[j];
                             var keyIdx = self.data.keys[j].indexOf(key);
                             if (keyIdx == -1) {
                                 self.data.keys[j].push(key);
                             }
                          }
                     });

                     // initialize data array
                     function buildEmptyArray(s) {
                         if (s.length == 0) return null;
                         var d = [];
                         for (var i=0; i<s[0]; i++) {
                             d.push(buildEmptyArray(s.slice(1)));
                         }
                         return d;
                     }
                     var shape = self.data.keys.map(function(k) { return k.length; });
                     self.data.data = buildEmptyArray(shape);

                     $.each(output, function(i, items) {
                         function parseItem(item) {
                             if (item == '..') return null;
                             if (!reCell.test(item)) {
                                 errors.push('invalid cell in line '+(i+2)+': '+item);
                             }
                             return parseFloat(item);
                         }

                         // remove spaces between cells
                         items = items.map(function(c) {
                               return c.trim();
                         });

                         var d = self.data.data;
                         for (var j=0; j<items.length-1; j++) {
                             var key = items[j];
                             var keyIdx = self.data.keys[j].indexOf(key);
                             if (j == items.length-2) {
                                 var value = parseItem(items[j+1]);
                                 d[keyIdx] = value;
                             } else {
                                 d = d[keyIdx];
                             }
                          }
                     });

                     if (!self.data.keys[0].length) throw new Error("no data");
                     if (errors.length) throw new Error(errors.join("\n"));
                 } catch (e) {
                     self.data = null;
                     CSVErrors.push(e.message);
                 }
                 callback();
             }

             CSVErrors = [];
             delimiter = delimiter || ',';
             if (!data.length) onParsed(new Error("empty file"));
             else {
                 Papa.DefaultDelimiter = delimiter;
                 var o = Papa.parse(data, {skipEmptyLines: true});
                 onParsed(0, o.data);
             }
         }

//parameter chart write (php, all chart options one array)                                                        
         self.toJSON = function() {
             self.titles[lang] = self.title;
             self.definitions[lang] = self.definition;
             return {
                 title: self.titles,
                 data: self.data,
                 definition: self.definitions,
                 options: self.options,
                 region: self.region,
                 type: self.type,
                 deleted: self.deleted,
             };
         };
     }

// views

     function ProjectPage(config, changed) {
         var self = this;
         var tabs = [];



         self.model = config.model;

         self.addTab = function(tab) {
             var form = new TabForm({model: tab, template: $('.tab-template')});
             if (tabs.length) tabs[tabs.length-1].form.element.find('.tabRight').prop('disabled', false);
             else form.element.find('.tabLeft').prop('disabled', true);
             form.element.find('.tabRight').prop('disabled', true);
             form.element.find('.tabLeft').click(function() {
                 $.each(tabs,function(i, t) {
                     if (tab.id == t.id) {
                         self.swapTab(i-1, i);
                         return false;
                     }
                 });
             });
             form.element.find('.tabRight').click(function() {
                 $.each(tabs,function(i, t) {
                     if (tab.id == t.id) {
                         self.swapTab(i, i+1);
                         return false;
                     }
                 });
             });
             form.element.find('.editTabs').click(function() {
                 $.each(tabs,function(i, t) {
                     if (tab.id == t.id) {
                         self.selectTab(i);
                         return false;
                     }
                 });
             });
             form.element.find('.deleteTabs').click(function() {
                 if (!window.confirm('Do you really want to delete this tab?')) return;
                 $.each(tabs,function(i, t) {
                     if (tab.id == t.id) {
                         self.removeTab(i);
                         window.view.hasChanged();
                         return false;
                     }
                 });
             });
             form.element.insertBefore(form.template);
             var content = new TabContent({model: tab, template: $('.tab-content-template')});
             content.element.hide();
             content.element.insertBefore(content.template);
             content.element.find('.addChart').click(function() {
                 var chart = self.model.addTabChart(tab, {region: tab.region, type: tab.type});
                 content.addChart(chart);
                 window.view.hasChanged();
             });
             tabs.push({form: form, content: content, id: tab.id});
             if (tabs.length >= MAX_TABS) {
                $(".new-tab").hide();
                $(".tabs .text").removeClass('col-sm-4').addClass('col-sm-12');
             }
         };

         self.removeTab = function(i) {
           var tab = tabs.splice(i, 1)[0];
           if (tab == null) return;
           self.model.project.removeTab(tab.form.model);
           tab.form.model.deleted = true;
           tab.form.element.remove();
           tab.content.element.remove();
           if (self.selectedTab == i) {
             self.selectedTab = null;
             self.selectTab(0);
           } else if (self.selectedTab > i) {
             self.selectedTab--;
             self.selectTab(self.selectedTab);
           }
           if (tabs.length < MAX_TABS) {
                $(".tabs .text").removeClass('col-sm-12').addClass('col-sm-4');
                $(".tabs .new-tab").show();
            }
           if (tabs.length) {
               tabs[0].form.element.find('.tabLeft').prop('disabled', true);
               tabs[tabs.length-1].form.element.find('.tabRight').prop('disabled', true);
           }
         };

         self.swapTab = function(i,j) {
           var tab1 = tabs[i];
           var tab2 = tabs[j];
           if (tab1 == null) return;
           if (tab2 == null) return;
           tabs[i] = tab2;
           tabs[j] = tab1;
           self.model.project.swapTabs(tab1.form.model, tab2.form.model);
           tab1.form.element.find('.tabLeft').prop('disabled', false);
           tab1.form.element.find('.tabRight').prop('disabled', false);
           tab2.form.element.find('.tabLeft').prop('disabled', false);
           tab2.form.element.find('.tabRight').prop('disabled', false);
           tabs[0].form.element.find('.tabLeft').prop('disabled', true);
           tabs[tabs.length-1].form.element.find('.tabRight').prop('disabled', true);
           tab2.form.element.insertBefore(tab1.form.element.first());
           window.view.hasChanged();
         };

         self.selectedTab = null;
         self.selectTab = function(i) {
            if (self.selectedTab != null) {
                tabs[self.selectedTab].content.element.hide();
                tabs[self.selectedTab].form.element.find('.editTabs').text('edit')
                                                                     .removeClass('activeTab');
                self.selectedTab = null;
            }
            var tab = tabs[i];
            if (tab != null) {
                tab.content.showChart();

                tab.form.element.find('.editTabs').text('active')
                                                  .addClass('activeTab');
                self.selectedTab = i;
            }
         };

         if (self.model.project.titles['en'] != '') {
             $('.project-title').attr('placeholder', self.model.project.titles['en']);
         }
         $('.project-title').val(self.model.project.title)
                            .change(function() { self.model.project.title = $(this).val(); });
         $.each(self.model.project.tabs, function(i, tab) { self.addTab(tab); });
         self.selectTab(0);

         $('.addTab').change(function(ev){
            if (tabs.length >= MAX_TABS) return;
            var opt = $(this).val();
            if (opt == '') return;
            $(this).val('');
            var config = {};
            if (opt == 'default') {
              config['type'] = 'default';
            } else if (opt == 'flow') {
              config['type'] = 'flow';
            } else {
              config['type'] = 'regional';
              config['region'] = opt;
            }
            var tab = self.model.addProjectTab(config);
            self.addTab(tab);
            window.view.hasChanged();
            self.selectTab(tabs.length-1);
         });

         var saveButton = $('.saveProject');
         var previewStandard = $('.preview');
         var extractChart = $('.extract');
         var previewSimple = $('.previewSimple');
         var previewSlider = $('.previewSlider');
         var controlsTranslate = $('.controlsTranslate button');

         self.checkErrors = function() {
            var errors = self.model.project.getErrors();
            if (errors.length) {
                saveButton.prop('disabled', true);
                $('#saveErrors').text('Errors:\n  '+errors.join('\n  '));
                return true;
            } else {
                saveButton.prop('disabled', !changed);
                $('#saveErrors').text('');
                return false;
            }
         };

         saveButton.click(function(){
            saveProject ();
         });

         function saveProject () {
            if (self.checkErrors()) return;
            $.ajax({
               type: "POST",
               dataType: "json",
               data: {config: JSON.stringify(self.model),
                      action: 'save'}
            }).done(function() {
              changed = false;
              saveButton.removeClass('btn-danger')
                        .addClass('btn-info')
                        .prop('disabled', true);
              window.alert('project has been saved');
              previewSimple.prop('disabled', false);
              previewSlider.prop('disabled', false);
              previewStandard.prop('disabled', false);
              extractChart.prop('disabled', false);
              controlsTranslate.prop('disabled', false);
            }).fail(function(jqXHR, textStatus, errorThrown) {
              var error = errorThrown || textStatus || 'unknown AJAX error';
              if (jqXHR.getResponseHeader('Content-type')==='application/json') {
                try {
                  error = JSON.parse(jqXHR.responseText).error;
                } catch (e) {} // ignore errors
              }
              $('#saveErrors').text('Error: '+error);
              saveButton.removeClass('btn-info')
                        .addClass('btn-danger');
            });
         };


         saveButton.prop('disabled', !changed);

         self.hasChanged = function() {
            changed = true;
            saveButton.prop('disabled', false);
            previewSimple.prop('disabled', true);
            previewSlider.prop('disabled', true);
            previewStandard.prop('disabled', true);
            extractChart.prop('disabled', true);
            controlsTranslate.prop('disabled', true);
            self.checkErrors();
         };

         window.onbeforeunload = function() {
            if (changed) return "document has been modified, please save before leaving this page";
         };

         $('body').change(self.hasChanged);
         $('body').on('fileuploadadd', self.hasChanged);

         previewStandard.click(function(){
            window.open(previewURL+"/"+project);
         });

         extractChart.click(function(){
            var win = window.open(previewURL+"/snapshotspreview?template=sbar&project="+project+"&lg="+lang, '_blank');
         });

         previewSlider.click(function(){
            window.open(previewURL+"/"+project);
         });
         
         previewSimple.click(function(){
            var win = window.open(previewURL+"/snapshotspreview?template=sbar&project="+project+"&lg="+lang, '_blank');
         });


         controlsTranslate.click(function(){
            var language = $(this).attr('value');
            var win = window.open(baseURL+"/edit/editmain.php?mode=wizard&project="+project+"&lg="+language, '_blank');
         });


         new ProjectConfig({model: self.model.project, template: $('#projectConfig')});
         $('.projectConfig').click(function(){
             $('#projectConfig').toggle();
         });

         $('.chartEmbedded').toggle(self.model.embedded);
     }

     function ProjectConfig(config) {
         var self = this;

         self.model = config.model;
         self.template = config.template;

         self.template.find('*').off('.ProjectConfig');
         self.template.find('formErrors').text('');

         self.template.find('.dataSource').val(self.model.options.dataSource)
                                          .on('change.ProjectConfig', function() {self.model.options.dataSource = $(this).val() });
         self.template.find('.dataSourceURL').val(self.model.options.dataSourceURL)
                                          .on('change.ProjectConfig', function() {self.model.options.dataSourceURL = $(this).val() });
         self.template.find('.relatedPublication').val(self.model.options.relatedPublication)
                                          .on('change.ProjectConfig', function() {self.model.options.relatedPublication = $(this).val() });
         self.template.find('.publicationThumbnail').val(self.model.options.publicationThumbnail)
                                          .on('change.ProjectConfig', function() {self.model.options.publicationThumbnail = $(this).val() });
         self.template.find('.publicationWebpage').val(self.model.options.publicationWebpage)
                                          .on('change.ProjectConfig', function() {self.model.options.publicationWebpage = $(this).val() });
     }

     function TabForm(config) {
         var self = this;

         self.model = config.model;
         self.template = config.template;

         self.element = self.template.children().clone();
         self.element.find('.content-ID').text(self.model.id);
         if (self.model.titles['en'] != '') {
             self.element.find('.tab-title').attr('placeholder', self.model.titles['en']);
         }
         self.element.find('.tab-title').val(self.model.title)
                                        .change(function() { self.model.title = $(this).val(); });
     }

     function TabContent(config) {
         var self = this;
         var charts = [];

         self.model = config.model;
         self.template = config.template;

         self.element = self.template.children().clone().wrapAll('<div>').parent();

         // workaround to show placeholder in textarea in firefox
         self.element.find('textarea').val('');

         if (self.model.teasers['en'] != '') {
             self.element.find('.tab-teaser').attr('placeholder', self.model.teasers['en']);
         }
         self.element.find('.tab-teaser').val(self.model.teaser)
                                         .change(function() { self.model.teaser = $(this).val(); });

//new parameter global tab (php)                                                                                        
         var detailsForm = self.element.find('.tab-details');
         detailsForm.off('.TabDetails');
         detailsForm.find('*').off('.TabDetails');
         detailsForm.find('img').removeClass('activeTemplate');
         detailsForm.find('a[data-tpl="'+self.model.template+'"] img').addClass('activeTemplate');
         detailsForm.find('.syncScale').prop('checked',self.model.syncScale).on('change.TabDetails',function() { self.model.syncScale = $(this).prop('checked'); } );
         detailsForm.find('.titleDisplay').prop('checked',self.model.titleDisplay).on('change.TabDetails',function() { self.model.titleDisplay = $(this).prop('checked'); } );
         detailsForm.on('click.TabDetails', 'a', function(){
                self.model.template = $(this).data('tpl');
                detailsForm.find('img').removeClass('activeTemplate');
                $(this).find('img').addClass('activeTemplate');
                window.view.hasChanged();
                return false;
          });

         self.element.find('.templatesLink').click(function(){
             detailsForm.show();
         });

         self.showChart = function() {
             self.element.show();
             detailsForm.hide();
         };

         self.addChart = function(chart) {
             var form = new ChartEdit({model: chart, template: $('.chart-edit-template')});
             if (self.model.type=='flow') {
                form.element.find('.chartLeft').hide();
                form.element.find('.chartRight').hide();
                form.element.find('.textProjectIndicator').text('left: global volume | right: flow data');
                self.element.find('.addChart').hide();
             }
             if (charts.length) charts[charts.length-1].form.element.find('.chartRight').prop('disabled', false);
             else form.element.find('.chartLeft').prop('disabled', true);
             form.element.find('.chartRight').prop('disabled', true);
             form.element.find('.chartLeft').click(function() {
                 $.each(charts,function(i, t) {
                     if (chart.id == t.id) {
                         self.swapChart(i-1, i);
                         return false;
                     }
                 });
             });
             form.element.find('.chartRight').click(function() {
                 $.each(charts,function(i, t) {
                     if (chart.id == t.id) {
                         self.swapChart(i, i+1);
                         return false;
                     }
                 });
             });
             form.element.find('.deleteCharts').click(function() {
                 if (!window.confirm('Do you really want to delete this chart?')) return;
                 $.each(charts,function(i, t) {
                     if (chart.id == t.id) {
                         self.removeChart(i);
                         window.view.hasChanged();
                         return false;
                     }
                 });
             });
             form.element.insertBefore(self.element.find('.tab-charts'));
             charts.push({form: form, id: chart.id});
         };

         self.removeChart = function(i) {
           var chart = charts.splice(i, 1)[0];
           if (chart == null) return;
           self.model.removeChart(chart.form.model);
           chart.form.model.deleted = true;
           chart.form.element.remove();
           if (charts.length) {
               charts[0].form.element.find('.chartLeft').prop('disabled', true);
               charts[charts.length-1].form.element.find('.chartRight').prop('disabled', true);
           }
         };

         self.swapChart = function(i,j) {
           var chart1 = charts[i];
           var chart2 = charts[j];
           if (chart1 == null) return;
           if (chart2 == null) return;
           charts[i] = chart2;
           charts[j] = chart1;
           self.model.swapCharts(chart1.form.model, chart2.form.model);
           chart1.form.element.find('.chartLeft').prop('disabled', false);
           chart1.form.element.find('.chartRight').prop('disabled', false);
           chart2.form.element.find('.chartLeft').prop('disabled', false);
           chart2.form.element.find('.chartRight').prop('disabled', false);
           charts[0].form.element.find('.chartLeft').prop('disabled', true);
           charts[charts.length-1].form.element.find('.chartRight').prop('disabled', true);
           chart1.form.element.insertAfter(chart2.form.element);
           window.view.hasChanged();
         };

         $.each(self.model.charts, function(i, chart) { self.addChart(chart); });
     }

     function ChartEdit(config) {
         var self = this;

         self.model = config.model;
         self.template = config.template;

         self.element = self.template.children().clone();
         self.element.find('.content-ID').text(self.model.id);
         if (self.model.titles['en'] != '') {
             self.element.find('.content-title').attr('placeholder', self.model.titles['en']);
         }
         self.element.find('.content-title').val(self.model.title)
                                            .change(function() { self.model.title = $(this).val(); });
         if (self.model.definitions['en'] != '') {
             self.element.find('.content-definition').attr('placeholder', self.model.definitions['en']);
         }
         self.element.find('.content-definition').val(self.model.definition)
                                                 .change(function() { self.model.definition = $(this).val(); });
         self.element.find('.details-tooltip-unit').val(self.model.options.tooltipUnit)
                                                   .change(function() { self.model.options.tooltipUnit = $(this).val() });
         self.element.find('.detailsLink').click(function(){
             new ChartDetails({model: self.model, template: $('#indicatorDetails0')});
         });
     }

//chart parameters js (read and write on form)            
     function ChartDetails(config) {
          var self = this;

          self.model = config.model;
          self.template = config.template;
          var csvField = self.template.find('.add-csv-chart');
          var csvFieldMulti = self.template.find('.add-csv-chart-multi');

          self.dataAsArray = function() {
             var maxRows = 100;
             var maxCols = 50;
             if (!self.model.data) return [];
             var t = [self.model.data.keys[1].slice(0, maxCols)];
             t[0].unshift('');
             for (var i=0; i<maxRows && i<self.model.data.keys[0].length; i++) {
                var v = [self.model.data.keys[0][i]];
                for (var j=0; j<maxCols && j<self.model.data.keys[1].length; j++) {
                   v.push(self.model.data.data[i][j]);
                }
                t.push(v);
             }
             return t;
          };

          self.tableData = function() {
             var t = self.dataAsArray();
             return $('<table/>').append(t.map(function(r,i) {
                 return $('<tr/>').append(r.map(function(c,j) {
                     return $((i && j) ? '<td/>' : '<th/>')
                         .text((c == null) ? '..' : c);
                 }));
             }));
          };

          self.showErrorsOrData = function() {
              var errors = self.model.getErrors('CSV');
              var warnings = self.model.getWarnings();
              var txt = "";
              if (errors.length) {
                  txt += 'Errors: '+errors.join(' ');
              }
              if (warnings.length) {
                  txt += 'Warnings: '+warnings.join('\n');
              }
              self.template.find('.formErrors').text(txt);
              if (self.model.data == null) {
                  self.template.find('.json-data').empty();
                  $('#dataModel').show();
                  $('#dataModelTitle').text('Use this model to input your data:')
                  self.template.find('.dataRefresh').hide();

              } else {
                  self.template.find('.json-data').empty().append(self.tableData());
                  $('#dataModel').hide();
                  $('#dataModelTitle').text('Data uploaded for this chart:')
                  if (self.model.data.url == null) {
                      self.template.find('.dataRefresh').hide();
                  } else {
                      self.template.find('.dataRefresh').show();
                      self.template.find('.dataRefreshUrl').val(self.model.data.url)
                         .on('change.ChartDetails', function() { self.model.data.url = $(this).val(); } );
                      self.template.find('.dataRefreshDate').text(self.model.data.fetchDate);
                  }

              }
          }

          self.template.find('*').off('.ChartDetails');
          self.showErrorsOrData();
          self.template.find('.details-title').text(self.model.title);
          var vs = self.template.find('.details-values');

          vs.find('.details-tooltip-decimals').val(self.model.options.tooltipDecimals)
                                   .on('change.ChartDetails', function() { self.model.options.tooltipDecimals = $(this).val() });
          vs.find('.details-tooltip-human').prop('checked',self.model.options.tooltipHuman)
                                   .on('change.ChartDetails',function() { self.model.options.tooltipHuman = $(this).prop('checked'); } );
          vs.find('.details-minimum').val(self.model.options.minimum)
                                   .on('change.ChartDetails', function() {
                                        if ($(this).val() == '') {self.model.options.minimum = null }
                                        else {self.model.options.minimum = $(this).val() }
                                   });
          vs.find('.details-maximum').val(self.model.options.maximum)
                                   .on('change.ChartDetails', function() {
                                        if ($(this).val() == '') {self.model.options.maximum = null }
                                        else {self.model.options.maximum = $(this).val() }
                                   });

          if (self.model.data == null) {
               csvField.attr('placeholder', 'Paste chart data from Excel table here.');
               $('#dataModel').show();
               $('#dataModelTitle').text('Use this model to input your data:')
          } else {
               csvField.attr('placeholder', 'Paste chart data from Excel table here to update the dataset for this chart. You can also paste the "SDMX DATA URL" from .Stat into this field.');
               csvFieldMulti.attr('placeholder', 'Paste vector format chart data from csv here.');
               $('#dataModel').hide();
               $('#dataModelTitle').text('Data uploaded for this chart:')
          }
          csvField.val('')
                  .on('change.ChartDetails', function() {
             self.model.loadData($(this).val(), self.showErrorsOrData, "\t");
          });
          csvFieldMulti.val('')
                  .on('change.ChartDetails', function() {
             self.model.loadDataMulti($(this).val(), self.showErrorsOrData, "\t");
          });
          self.template.find('.json-data').empty().append(self.tableData());
          self.template.find('.refreshData').on('click.ChartDetails', function() {
             self.model.loadData(self.model.data.url, self.showErrorsOrData, "\t");
             window.view.hasChanged();
          });
          var upload = self.template.find('.csv-upload');
          upload.fileupload({
               dataType: 'json',
               autoUpload: false,
               dropZone: upload,
          }).on('fileuploadadd.ChartDetails', function (e, data) {
               $.each(data.files, function (index, file) {
                    loadImage.readFile(file, function(e) {
                         self.model.loadData(e.target.result, self.showErrorsOrData);
                    }, 'readAsText');
               });
          });
          self.template.show();
     }


// initilalize page
     $('body').on('click', '.resourceContainer .closeButtonAbout', function(){
         $(this).closest('.resourceContainer').hide();
     });

     if (lang != 'en') $('.en-only').hide();
     window.view = new ProjectPage({model: new Page(lang, data)}, false);
}
