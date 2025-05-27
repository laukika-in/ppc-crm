jQuery(function ($) {
  window.LCM_initFlatpickr = function ($tr) {
    $tr.find('input[type="date"]').flatpickr({
      altFormat: "d-M-Y", // what user sees
      dateFormat: "Y-m-d", // what gets into the <input> value
      allowInput: true,
    });
    $tr.find(".flatpickr-date").flatpickr({
      altFormat: "d-M-Y", // what user sees
      dateFormat: "Y-m-d", // what gets into the <input> value
      allowInput: true,
    });
    $tr.find('input[type="time"]').flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K", // 01:30 PM
      time_24hr: false,
      allowInput: true,
    });
    $tr.find(".flatpickr-time").flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K",
      allowInput: true,
    });
  };
});
