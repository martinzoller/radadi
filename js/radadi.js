/*
radadi - Race Data Display
This program allows the user to display start lists and result lists of orienteering races on any client devices that support HTML5.
The result lists can be updated automatically through a periodic data export from the OE2010 event software.

Copyright (C) 2016 Martin Zoller

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses>.

See the README file for more information.


*/
var timestamp = 0; // Last read version of CSV file
var curpage = 0;
var hasData = false;
var hasUpdate = false;
var newData;
var displaytime;
var pagecount = 0;



var endpoint;
if (isTeam) {
  endpoint = "/api/v2/show_teams.php";
} else {
  endpoint = "/api/v2/show.php";

}

// Long-polling function
function longPoll() {
  var queryString = {
    'timestamp': timestamp
  };

  $.ajax({
    type: 'GET',
    // url: 'http://' + location.host + '/api/v1/server.php',
    //url: 'http://' + location.host + '/api/v2/show.php',
    url: 'http://' + location.host + endpoint,

    async: true,
    /* If set to non-async, browser shows page as "Loading.."*/
    cache: false,
    data: queryString,
    dataType: "json",
    timeout: 40000,
    success: function (data) {
      timestamp = data.timestamp;
      newData = data;
      if (hasData) {
        hasUpdate = true;
      } else {
        updateList();
        hasData = true;
      }
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      showErr('Error retrieving data', textStatus + " (" + errorThrown + ")");
    },
    complete: function () {
      $('#errmsg').hide();
      setTimeout(longPoll, 10000); /* Limit the frequency in case we get immediate reply */
    }
  });
}

// Process JSON startlist/results data
function updateList() {

  var classifier = ['OK', 'Nicht gestartet', 'Abgebr.', 'Fehlst.', 'Disq.', 'ot', 'unknown', 'np'];
  var evcfg = newData['eventconfig'];
  var clcfg = newData['clientconfig'];
  var ip = newData['remote_ip'];
  var tmstmp = newData['timestamp'];
  var prevclass = '';
  var evenrow = false;
  var colcount = 0;
  var colwidth = Math.floor(100 / clcfg['columns']); //percent
  var has_heading = false;
  var newpage = false;

  // Helper for class headers
  function classheader(classname, dist, controls, newpage) {
    // Continuing previous class?
    var cont = (newpage && pagecount > 1) ? ' <i>(Fortsetzung)</i>' : '';
    var html = '<div class="classheader"><h2><span class="classname">' + classname + "</span>" + cont;
    if (typeof dist !== 'undefined') {
      html += ' <span class="coursedata">' + dist + ' km</span>';
    }
    html += '</h2></div>';
    return html;
  }

  // Helper for column div ID
  function curcol() {
    return '#radacol' + colcount;
  }

  // Creates column div for current col
  function makecol() {

    // New page
    if (colcount % clcfg['columns'] == 0) {
      // Hide prev page
      // Apparently it's hard to measure the height when hidden, that's why we hide them only after they're ready
      $('#radapage' + pagecount).hide();

      if (!has_heading) {
        // Set this helper variable when a new class header is needed
        // (new page that doesn't already have a class header)
        newpage = true;
      }

      pagecount++;
      $('#radaspace').append('<div class="radapage" id="radapage' + pagecount + '"></div>');
    }

    colcount++;
    // Move the last (too long) item from the previous column
    $('#radapage' + pagecount).append('<div class="radacol" id="radacol' + colcount + '" style="width:' + colwidth + '%;left:' + ((colcount - 1) % clcfg['columns']) * colwidth + '%"></div>');
  }


  // Helper, return a zero character if number has a single digit
  function fillzero(x) {
    return (x > 9) ? '' : '0';
  }

  // Convert a hh:mm:ss time string to seconds
  function time_to_sec(time) {
    var timearr = time.split(':');
    return 3600 * parseInt(timearr[0]) + 60 * parseInt(timearr[1]) + parseInt(timearr[2]);
  }

  // Create a hh:mm:ss time string from seconds
  function sec_to_time(sec) {
    var h = Math.floor(sec / 3600);
    var m = Math.floor((sec % 3600) / 60);
    var s = sec % 60;
    return fillzero(h) + h + ':' + fillzero(m) + m + ':' + fillzero(s) + s;
  }

  /**********************************/
  /* START                          */
  /**********************************/

  //window.clearTimeout(flipPage);

  $('#eventname').text(evcfg['eventname']);
  var subtitle = (clcfg['type'] == 'startlist' ? 'Startliste' : 'Vorl. Ergebnisse') + ' - ' + evcfg['stagename'];
  $('#subtitle').text(subtitle);
  console.log(ip);
  $('#ip').text(ip);


  $('#radaspace').empty();
  pagecount = 0;
  makecol();

  $.each(newData['list'], function (no, line) {

    var listhtml = '';

    if (line['highlight']) {

      highlightClass = "highlight";
    } else {
      highlightClass = "";
    }

    // Class heading before previous entry if new page has started
    if (newpage) {
      $(curcol()).prepend(classheader(line['short'], line['km'], line['controls'], true));
      newpage = false;
    }

    // Class heading (we assume the data is sorted by classes)
    if (line['short'] != prevclass && prevclass != '') {
      listhtml += '<div class="gap"></div>';
      // Enclosing <div> to move the class heading automatically if the first line is moved to the next column
      listhtml += '<div>' + classheader(line['short'], line['km'], line['controls'], false);
      evenrow = false;
      has_heading = true;
    } else {
      listhtml += '<div>';
      has_heading = false;
    }
    prevclass = line['short'];

    // Differences between start list and results
    var stno_pl;
    var tsec;
    var tstr;
    if (clcfg['type'] == 'startlist') {
      stno_pl = '<td class="col_stno">' + line['Stno'];
      // Format start time, adding zero time (moment.js doesn't really help for this)
      tsec = time_to_sec(evcfg['zerotime']) + time_to_sec(line['Start']);
      tstr = sec_to_time(tsec);
    } else {
      stno_pl = '<td class="col_place ' + highlightClass + '">' + line['place'] + (line['place'] != '' ? '.' : '');
      tstr = (line['classifier'] == 0 ? line['time'] : classifier[line['classifier']]);
      tstr = (tstr.substring(0, 2) == "0:" ? tstr.substring(2) : tstr);

      if(isTeam) {
        tstr_overall = (line['classifierOverall'] == 0 ? line['time'] : classifier[line['classifierOverall']]);
      } else {
        tstr_overall = (line['classifierOverall'] == 0 ? line['timeOverall'] : classifier[line['classifierOverall']]);

      }
      if (tstr_overall) {
        tstr_overall = (tstr_overall.substring(0, 2) == "0:" ? tstr_overall.substring(2) : tstr_overall);
      }


    }

     // For simplicity, use a complete table tag for each row, that way we can treat them independently
     listhtml += '<table class="radalist"><tr class="' + (evenrow ? 'even' : 'odd') + '">';
     listhtml += stno_pl + '</td><td class="col_nat ' + highlightClass + '"><div class="flag">';
     if (line['nat'] != '' && typeof line['nat'] !== 'undefined') {
       listhtml += '<img src="img/nat/' + line['nat'] + '.svg" alt="' + line['nat'] + '" />';
     }

    if (isTeam) {
     
      listhtml += '</div></td><td class="col_name ' + highlightClass + '">' + line['team'] + '</td>';
      //listhtml += '<td class="col_yob">'+line['yb']+'</td>';
      listhtml += '<td class="col_club ' + highlightClass + '">' + line['name'] + '</td>';
      listhtml += '<td class="col_time ' + highlightClass + '">' + tstr + '</td>';
      listhtml += '<td class="col_after ' + highlightClass + '">' + line['after'] + '</td>';


      if (evcfg['stagename'] > 1) {
        listhtml += '<td class="col_place_overall ' + highlightClass + '">' + line['placeOverall'] + (line['placeOverall'] != '' ? '.' : '') + '</td>';

        listhtml += '<td class="col_time_overall ' + highlightClass + '">' + tstr_overall + '</td>';
      }
      listhtml += '</tr></table></div>';
    } else {
     
       listhtml += '</div></td><td class="col_name ' + highlightClass + '">' + line['name'] + '</td>';
       //listhtml += '<td class="col_yob">'+line['yb']+'</td>';
       listhtml += '<td class="col_club ' + highlightClass + '">' + line['team'] + '</td>';
       listhtml += '<td class="col_time ' + highlightClass + '">' + tstr + '</td>';
       listhtml += '<td class="col_after ' + highlightClass + '">' + line['after'] + '</td>';
 
 
       if (evcfg['stagename'] > 1) {
         listhtml += '<td class="col_place_overall ' + highlightClass + '">' + line['placeOverall'] + (line['placeOverall'] != '' ? '.' : '') + '</td>';
 
         listhtml += '<td class="col_time_overall ' + highlightClass + '">' + tstr_overall + '</td>';
       }
       listhtml += '</tr></table></div>';
    }



    evenrow = !evenrow;
    $(curcol()).append(listhtml);

    // Start new column if needed
    var space = $(curcol()).parent().height() - $(curcol()).innerHeight();
    // Apparently (and counter-intuitively) innerHeight includes padding but height does not.
    // We could also use outerHeight which includes border too (border is zero)
    if (space < 0) {
      var temp = $(curcol()).children().last().outerHTML();
      $(curcol()).children().last().remove();
      makecol();
      $(curcol()).append(temp);
    }

  });

  // Pagination: Start flipping pages
  if (clcfg['paginate']) {

    // No scrollbars
    $('body').css('overflow:hidden');

    // We're on the last page, now flip to first
    curpage = pagecount;
    displaytime = clcfg['displaytime'];

    if (!hasData) {
      flipPage();
    }

    // All on one page: Set scrollbars
  } else {
    $('body').css('overflow:scroll');
    //$()
  }




}


// Flip list page
function flipPage() {
  //$('#radapage'+curpage).fadeOut(800);
  //$('#radapage'+curpage).hide();
  curpage++;

  // Check if another page exists, otherwise go to first page
  if (!$('#radapage' + curpage).length) {
    if (hasUpdate) {
      hasUpdate = false;
      updateList();
    }
    curpage = 1;
  }

  for (var i = 1; i <= pagecount; i++) {
    // TODO bug workaround
    $('#radapage' + i).hide();
  }

  //$('#radapage'+curpage).fadeIn(800);
  $('#radapage' + curpage).show();
  $('#page').text(curpage + " / " + pagecount);
  window.setTimeout(flipPage, 1000 * displaytime);
  $('#timestamp').text(timeSince("Aktualisiert vor ", timestamp * 1000, ".", "LIVE"));

  /*
  let progressbar = $('#progressbar');
  let max = progressbar.attr('max');
  let time = displaytime * 950 / max;
  progressbar.val(0);
  let value = progressbar.val();

  const loading = () => {
    value += 1;
    progressbar.val(value);

    $('.progress-value').html(value + '%');

    if (value == max) {
      clearInterval(animate);
    }
  };
  

  const animate = setInterval(() => loading(), time);
  */
};

// Show error pop-up
function showErr(title, msg) {
  $('#errmsg_title').text(title);
  $('#errmsg_text').text(msg);
  $('#errmsg').show();
}


var timeSince = function (prefix, date, postfix = "", fallbackIfZero = "") {
  if (typeof date !== 'object') {
    date = new Date(date);
  }

  var seconds = Math.floor((new Date() - date) / 1000);
  var intervalType;

  var interval = Math.floor(seconds / 31536000);
  if (interval >= 1) {
    intervalType = 'Jahr';
    intervalTypePl = "jahren";

  } else {
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) {
      intervalType = 'Monat';
      intervalTypePl = "Monaten";

    } else {
      interval = Math.floor(seconds / 86400);
      if (interval >= 1) {
        intervalType = 'Tag';
        intervalTypePl = "Tagen";

      } else {
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) {
          intervalType = "Stunde";
          intervalTypePl = "Stunden";
        } else {
          interval = Math.floor(seconds / 60);
          intervalType = "Minute";
          intervalTypePl = "Minuten";
          if (interval < 1) {
            interval = 0
          }
        }
      }
    }
  }

  if (interval > 1 || interval === 0) {
    intervalType = intervalTypePl;
  }
  console.log(interval);

  if (fallbackIfZero == "" || interval > 1) {
    return prefix + interval + ' ' + intervalType + postfix;
  } else {
    return fallbackIfZero
  }
};


// initialize jQuery
$(document).ready(function () {

  // Plugin to access an element's HTML including element itself
  jQuery.fn.outerHTML = function () {
    return jQuery('<div />').append(this.eq(0).clone()).html();
  };

  longPoll();
});
