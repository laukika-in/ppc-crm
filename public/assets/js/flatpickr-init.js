jQuery(function ($) {
  window.LCM_initFlatpickr = function ($tr) {
    $tr.find('input[type="date"]').flatpickr({ dateFormat: "Y-m-d" });
    $tr
      .find('input[type="time"]')
      .flatpickr({ enableTime: true, noCalendar: true, dateFormat: "h:i K" });
  };
});
