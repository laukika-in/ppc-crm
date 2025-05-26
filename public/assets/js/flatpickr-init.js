jQuery(function ($) {
  window.LCM_initFlatpickr = function ($tr) {
    $tr.find('input[type="date"]').flatpickr({
      dateFormat: "d-F-Y", // 2025-05-26
      allowInput: true,
    });

    $tr.find('input[type="time"]').flatpickr({
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K", // 01:30 PM
      time_24hr: false,
      allowInput: true,
    });
  };
});
