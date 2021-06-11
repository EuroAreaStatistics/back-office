    <div id='main'>
      <form class="form-horizontal" role="form" action="index-tabs.html">

          <div class="form-group errorWrapper">
               <div class="chartEmbedded" style="display:none">Warning: some charts have been embedded, please contact an administrator before changing this project!</div>
               <div class="col-sm-12 formErrors" id="saveErrors">
               </div>
          </div>

          <div class="form-group header">
               <div class="col-sm-12">
                  <span class="textProjectConfig">Project title & metadata</span>
               </div>
               <div class="col-sm-8">
                    <input type="text" class="form-control project-title" value="TITLE">
               </div>
               <div class="col-sm-2">
                  <button type="button" class="btn btn-default projectConfig en-only" title='Add data source and related publication'>add metadata</button>
               </div>
                <div id="projectConfig" style="display: none;">
                  <div class="col-sm-8">
                    Data source text:
                    <input class="form-control dataSource" type="text" placeholder="add data source text">
                  </div>
                  <div class="col-sm-8">
                    Data source URL (http://www.sourceURL.org):
                    <input class="form-control dataSourceURL" type="text" placeholder="add data source URL">
                  </div>
                  <div class="col-sm-8 relatedPublicationWrapper">
                    Related publication:
                    <input class="form-control relatedPublication" type="text" placeholder="add publication name">
                  </div>
                  <div class="col-sm-8 publicationThumbnailWrapper">
                    Publication thumbnail:
                    <input class="form-control publicationThumbnail" type="text" placeholder="add image link">
                  </div>
                  <div class="col-sm-8 publicationWebpageWrapper">
                    Publication webpage:
                    <input class="form-control publicationWebpage" type="text" placeholder="add oecd.org publication page" style="margin-bottom: 50px">
                  </div>
               </div>
          </div>


          <div class="form-group tabs">
            <div class="text col-sm-4">Tab names and selction for active tabs</div>
            <div class="new-tab en-only col-sm-8">
              <div class="col-sm-2">
                <select class="addTab" title="Add a tab to your project">
                  <option value="" selected>add tab</option>
                  <option value="default">Country/year data</option>
<?php if ($mode == 'standard') : ?>
                  <option value="flow">Flow data - BETA</option>
<?php endif ?>
                  <!--<option value="freeLabels">free labels - TEST</option>-->
                  <!--<option value="MWI">regional data - Malawi - TEST</option>-->
                  <!--<option value="NGA">regional data - Nigeria - TEST</option>-->
                </select>
              </div>
            </div>
            <div class="tab-template area" style="display: none;">
              <div class="input-area col-sm-3">
                    <input type="text" class="form-control tab-title" value="TITLE">
              </div>
              <div class="button-area col-sm-9">
                    <button type="button" class="btn btn-default editTabs col-sm-2" title='Activate this tab for editing'>edit</button>
                    <button type="button" class="btn btn-default deleteTabs en-only"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>
                    <button type="button" class="btn btn-default tabLeft"><span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span></button>
                    <button type="button" class="btn btn-default tabRight"><span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span></button>
                    (ID: <span class="content-ID"></span>)
              </div>
            </div>

          </div>
    
          <div class="tab-content-template" style="display: none;">
              <div class="form-group tabContentWrapper">
                  <div class="col-sm-12 tabTeaser">
                      Tab teaser (short summary of tab findings) and default view/template for active tab
                  </div>
                  <div class="col-sm-8 tabTeaser">
                      <textarea class="form-control tab-teaser" rows="3" placeholder="Teaser text to appear above the chart area (optional).">TEASER</textarea>
                  </div>
                  <div class="col-sm-2">
                      <button type="button" class="btn btn-default templatesLink en-only" title='Select default display template and edit tab settings'>select template / config tab</button>
                  </div>
              </div>     
              <div class="form-group addChartWrapper">
                   <div class="col-sm-3 tab-charts en-only">
                        <button type="button" class="btn btn-default addChart" title='Add a chart to the active tab'>add chart</button>
                   </div>
              </div>

            <div class="resourceContainer form-group tab-details">
              <div class="resourceContent">
                  <div class="sharebutton closeButtonAbout">update and close</div>
                  <div class="form-group tabConfig">
                      <table class="col-sm-8">
                        <tr>
                          <td>Synchronize y axis across all charts:</td>
                          <td><input class="form-control syncScale" type="checkbox"></td>
                        </tr>
                        <tr>
                          <td>Add option to select indicator from dropdown menu above charts:</td>
                          <td><input class="form-control titleDisplay" type="checkbox"></td>
                        </tr>
                      </table>
                  </div>
<?php
foreach($templateList as $k=>$v) :
if(!in_array($k,$wizardMode[$mode])) {continue;}
?>
                  <div class='chartTemplates col-sm-1 form-group'>
                      <a href="#" data-tpl="<?=$k?>" class="tempateImage">
                        <img class='imageTemplate' src='img/<?=$v['image']?>'>
                      </a>
                      <div class="tempateText">
                        <span><b><?=$v['name']?>:</b></span>
                        <span><?=$v['dev']?></span>
                      </div>
                  </div>
<?php endforeach ?>
              </div>
            </div>

          </div>


          <div class="chart-edit-template form-group" style="display: none;">
            <div class="col-sm-3 chart-block">
                  <div  class="textProjectIndicator">Indicator on active tab<br>(ID: <span class="content-ID"></span>)</div>
                  <input type="text" class="form-control content-title" value="CHART" placeholder="Indicator Title" title="Indicator Title">
                  <input class="form-control details-tooltip-unit" type="text" placeholder="Indicator Unit ('percent'->'%', 'Euro'->'EUR')" title="Indicator Unit ('percent'->'%', 'Euro'->'EUR')">
                  <textarea class="form-control content-definition" rows="7" placeholder="Indicator Definition" title="Indicator Definition"></textarea>
                  <div class="en-only chartButtons">
                     <button type="button" class="btn btn-default chartLeft"><span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span></button>
                     <button type="button" class="btn btn-default chartRight"><span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></button>
                     <button type="button" class="btn btn-default detailsLink"><span>Data&amp;Config</span></button>
                     <button type="button" class="btn btn-default deleteCharts"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>
                  </div>
            </div>
          </div>



          <div id="indicatorDetails0" class="resourceContainer form-group" style="display: none;">
            <div class="resourceContent">
              <div class="sharebutton closeButtonAbout">update and close</div>
               <h4>Config details for chart:</h4>
               <h5 class="details-title">CHART</h5>
               <div class="details-values">
                    <h5>Chart data</h5>
                    <div class="form-group dataInput">
                      <div class=" col-sm-5 tab-charts csv-upload">
                          <textarea class="form-control add-csv-chart" rows="11" placeholder="VALUE"></textarea>
                          <textarea class="form-control add-csv-chart-multi" rows="11" placeholder="VALUE"></textarea>
                          <!-- The file input field used as target for the file upload widget -->
                          <input style="display: none;" type="file" name="files[]" multiple>
                      </div>
                      <div class="col-sm-1"></div>
                      <div class="col-sm-5" >
                          <span class="formErrors"></span>
                          <h5 id='dataModelTitle'>Use this model to add your data:</h5>

                          <div id="dataModel">
                            <div id="dataImage">
                              <img class='imageData' src='img/DataModel1.png' title='Chart wizard data model'/>
                            </div>
                            <ul>
                              <li>First column = country names (use ISO3 in upper case)</li>
                              <li>Add a column for each year (bar charts will display latest available year for each country)</li>
                              <li>OECDavg = OECD, EuroArea = EUR</li>
                              <li>Missing values = .. (two dots)</li>
                              <li>Use plain numbers for % (23.2%=>23.2)</li>
                            </ul>
                          </div>

                          <div class="json-data" ></div>
                      </div>
                    </div>
                    <div class="form-group dataRefresh">
                        <div class="col-sm-12">SDMX Data URL: </div>
                        <textarea class="dataRefreshUrl col-sm-12"  rows=6 ></textarea>
                        <div class="col-sm-9">Updated: <span class="dataRefreshDate"></span></div>
                        <div class="col-sm-2">
                            <button type="button" class="btn btn-default refreshData">Refresh data</button>
                        </div>
                    </div>
                    <div class="form-group decimals">
                        <div class="col-sm-5">
                            <h5>Number of decimals displayed on tooltip:</h5>
                            <input class="form-control details-tooltip-decimals" type="text" placeholder="add number of decimals">
                        </div>
                    </div>
                    <div class="form-group humanReadable">
                        <div class="col-sm-8">
                            <h5>Display human readable numbers on tooltip:</h5>
                        </div>
                        <div class="col-sm-1">
                            <input class="form-control details-tooltip-human" type="checkbox">
                        </div>
                    </div>
                    <div class="form-group minMax">
                        <div class="col-sm-5">
                            <h5>Set y-axis minimum (optional):</h5>
                            <input class="form-control details-minimum" type="text" placeholder="add value">
                        </div>
                        <div class="col-sm-1">
                        </div>
                        <div class="col-sm-5">
                            <h5>Set y-axis maximum (optional):</h5>
                            <input class="form-control details-maximum" type="text" placeholder="add value">
                        </div>
                    </div>
               </div>
            </div>
          </div>

        </form>
      </div>
