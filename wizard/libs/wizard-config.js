

$(function() {

  $('.humanReadable').hide();

  if (mode == 'standard') {
    $('.previewSlider').hide();
    $('.previewSimple').hide();
    $('.controlsTranslate').hide();
    $('.add-csv-chart-multi').hide();    
  }

  if (mode == 'slide') {
    $('.preview').hide();
    $('.extract').hide();
    $('.previewSimple').hide();

    $('.content-definition').hide();
    $('.tab-template .input-area').removeClass('col-sm-3');
    $('.tab-template .input-area').addClass('col-sm-7');
    $('.tab-template .button-area').removeClass('col-sm-9');
    $('.tab-template .button-area').addClass('col-sm-5');
    $('.controlsTranslate').hide();
    $('.add-csv-chart-multi').hide();    
  }


  if (mode == 'simple') {
    $('.preview').hide();
    $('.extract').hide();
    $('.previewSlider').hide();

    $('.textProjectConfig').text('Project title (not displayed on chart):');
    $('#projectConfig').show();
    $('.projectConfig').hide();
    $('.relatedPublicationWrapper').hide();
    $('.publicationThumbnailWrapper').hide();
    $('.publicationWebpageWrapper').hide();

    $('.tabs').hide();
    $('.tabConfig').hide();
    $('.tabTeaser').hide();

    $('.addChart').hide();
    $('.chartLeft').hide();
    $('.chartRight').hide();
    $('.deleteCharts').hide();
    $('.textProjectIndicator').text('Chart title and data');

    $('#main .tabContentWrapper').hide();
    $('#main .addChartWrapper').addClass('col-sm-6');

    $('#main .chart-block').removeClass('col-sm-3');
    $('#main .chart-block').addClass('col-sm-10');

    $('#main .header').addClass('col-sm-6');
    $('#main .templatesLink').text('select template');
    $('.add-csv-chart-multi').hide();    
  }

});

