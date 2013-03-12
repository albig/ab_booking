$(document).ready(function() {
    $('input').filter('.datepicker').datepicker({
      changeMonth: false,
      changeYear: false,
      showWeek: true,
      minDate: "0d",
      maxDate: "+1y",
      showOn: 'button',
      buttonImage: '/typo3conf/ext/ab_booking/ext_icon.gif',
      buttonImageOnly: true,
  });
});

