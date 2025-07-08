jQuery(document).ready(function ($) {
  // Step 1: Populate data
  const campaignId = LCM.campaign_id;

  // 1) initialize flatpickr
  flatpickr("#month", {
    dateFormat: "Y-m",
    allowInput: true,
    onChange: reload,
  });
  flatpickr("#lcm-filter-date-from, #lcm-filter-date-to", {
    dateFormat: "Y-m-d",
    allowInput: true,
    onChange: reload,
  });

  // Step 2: Edit Mode
  $(document).on("click", ".edit-tracker", function () {
    const $row = $(this).closest("tr");
    $row.find(".tracker-display").addClass("d-none");
    $row.find(".tracker-input").removeClass("d-none");
    $row.find(".edit-tracker").addClass("d-none");
    $row.find(".save-daily-tracker").removeClass("d-none");
    $row.find(".cancel-tracker").removeClass("d-none");
    $row.addClass("table-warning shadow-sm");
  });

  // Step 3: Save Button
  $(document).on("click", ".save-daily-tracker", function () {
    const $row = $(this).closest("tr");
    const rowId = $row.data("row-id");
    const campaign_id = LCM.campaign_id;
    const date = $row.data("date");

    const reach = parseInt($row.find(".reach-input").val()) || 0;
    const impressions = parseInt($row.find(".impressions-input").val()) || 0;
    const spent = parseFloat($row.find(".spent-input").val()) || 0;

    $.post(LCM.ajax_url, {
      action: "lcm_save_daily_tracker",
      nonce: LCM.nonce,
      row_id: rowId,
      campaign_id,
      date,
      reach,
      impressions,
      amount_spent: spent,
    })
      .done(function (response) {
        if (response.success) {
          $row.find(".reach-display").text(reach);
          $row.find(".impressions-display").text(impressions);
          $row.find(".spent-display").text(spent);
          $row.find(".tracker-display").removeClass("d-none");
          $row.find(".tracker-input").addClass("d-none");
          $row.find(".edit-tracker").removeClass("d-none");
          $row.find(".save-daily-tracker").addClass("d-none");
          $row.removeClass("table-warning shadow-sm");
        } else {
          alert("Save failed");
        }
      })
      .fail(function () {
        alert("Server error");
      });
  });
  $(document).on("click", ".cancel-tracker", function () {
    const $row = $(this).closest("tr");
    // Revert inputs to original display values
    $row.find(".reach-input").val($row.find(".reach-display").text());
    $row
      .find(".impressions-input")
      .val($row.find(".impressions-display").text());
    $row.find(".spent-input").val($row.find(".spent-display").text());
    // Toggle back to view mode
    $row.find(".tracker-display").removeClass("d-none");
    $row.find(".tracker-input").addClass("d-none");
    $row.find(".edit-tracker").removeClass("d-none");
    $row.find(".save-daily-tracker, .cancel-tracker").addClass("d-none");
    $row.removeClass("table-warning shadow-sm");
  });
});
