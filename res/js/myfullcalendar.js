

$(document).ready(function() {

	var storagePid = 73;
	var fegroup = 1;

	/* special fancybox-setup for bookingajax-teasers */
	var mybookings = {
		url: '?eID=ab_booking&uids=1,2,3,5&storagePid=' + storagePid + '&fegroup=' + fegroup,
		className: 'booking'
	}

  $('#calendar').fullCalendar({

	monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
	monthNamesShort: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
	dayNames: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
	dayNamesShort: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
	buttonText: {
	  prev: "<span class='fc-text-arrow'>&lsaquo;</span>",
	  next: "<span class='fc-text-arrow'>&rsaquo;</span>",
	  prevYear: "<span class='fc-text-arrow'>&laquo;</span>",
	  nextYear: "<span class='fc-text-arrow'>&raquo;</span>",
	  today: 'heute',
	  month: 'Monat',
	  week: 'Woche',
	  day: 'Tag'
	},
	events: mybookings,
	allDayText: 'ganztags',
    aspectRatio: 0.7,
    firstDay: 1,
    weekNumbers: true,
    weekMode: 'liquid',
    header: {
      left:   'title',
      center: 'month,agendaWeek,agendaDay',
      right:  'today  prev,next'
    },
    theme: true,
    titleFormat: {
      month: 'MMMM yyyy',
      week: "d. [ MMM] [ yyyy]{ - d. MMM yyyy}",
      day: 'dddd, d.M.yyyy'
    },
    columnFormat: {
      month: 'ddd',
      week: 'ddd d.M.',
      day: 'dddd d.M.yyyy'
    },
    timeFormat: { // for event elements
      // for agendaWeek and agendaDay
      agenda: 'H:mm{ - H:mm}', // 5:00 - 6:30
      '': 'H:mm',
    },
    firstHour: 9,
    minTime: 7,
    maxTime: 22,
    defaultEventMinutes: 60,
    axisFormat: 'H:mm',

    // add event name to title attribute on mouseover
    eventMouseover: function(event, jsEvent, view) {
      if (view.name !== 'agendaDay') {
              $(jsEvent.target).attr('title', event.title);
      }
    },

    loading: function(bool) {
      if (bool) $('#loading').show();
      else {
		$('#loading').hide();
		}
    },
	dayClick: function(date, allDay, jsEvent, view) {

		// in month view...
        if (allDay && $(jsEvent.target).is('div.fc-day-number')) {
                // Clicked on the day number

                $('#calendar')
                    .fullCalendar('changeView', 'agendaDay')
                    .fullCalendar('gotoDate',
                        date.getFullYear(), date.getMonth(), date.getDate());
        } else {

			//~ alert('View: ' + view.name);
			//~ alert('Day: ' + date.getDate());
			//~ alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);

			$('#formTemplate input[type="submit"]').before(' \
			<input type="hidden" name="storagePid" value="' + storagePid + '" /> \
			<input type="hidden" name="fegroup" value="' + fegroup + '" /> \
			');

			$.fancybox({
				'href': '#formTemplate',
				'width': 400,
				overlayOpacity: 0.6,
				autoDimensions: false,
				autoScale: false,
			});
			return false;
        }
    },
    eventClick: function(event, jsEvent, view) {

		$.fancybox({
			'href': '#teaserText' + event.id,
			'width': 400,
			overlayOpacity: 0.6,
			autoDimensions: false,
			autoScale: false,
		});
		return false;
        //~ alert('Event: ' + calEvent.title);
        //~ alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
        //~ alert('View: ' + view.name);

        // change the border color just for fun
        //~ $(this).css('border-color', 'red');

    },
	eventAfterRender: function(event, element) {
		element.find('.fc-event-title').wrap('<a class="bookingajax" href="#teaserText' + event.id + '" title="'+event.title+'"></a>');
		element.find('.fc-event-inner').append('<div style="display:none"><div id="teaserText'+event.id+'"><p>' + event.description + '</p></div></div>');
    },
    });
  });

