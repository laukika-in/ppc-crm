jQuery(function ($) {
  window.LCM_initFlatpickr = function ($tr) {
    $tr.find('input[type="date"]').flatpickr({
      altFormat: "d-F-Y", // what user sees
      dateFormat: "Y-m-d", // what gets into the <input> value
      allowInput: true,
    });
    $tr.find(".flatpickr-date").flatpickr({
      altFormat: "d-F-Y", // what user sees
      dateFormat: "Y-m-d", // what gets into the <input> value
      allowInput: true,
    });
    $tr.find('input[type="time"]').flatpickr({
      enableTime: true,
      noCalendar: true,
      altInput: true,
      time_24hr: false,
      altFormat: "h:i K", // shows AM/PM
      dateFormat: "h:i K", // stores 24-hour HH:MM
      allowInput: true,
    });
    $tr.find(".flatpickr-time").flatpickr({
      enableTime: true,
      noCalendar: true,
      altInput: true,
      time_24hr: false,
      altFormat: "h:i K", // shows AM/PM
      dateFormat: "h:i K", // stores 24-hour HH:MM
      allowInput: true,
    });
  };
});
