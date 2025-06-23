// File: assets/js/campaign-detail.js

jQuery(document).ready(function ($) {
  // Step 1: Fetch tracker data and populate rows
  let trackerData = {};

  $.get(
    LCM.ajax_url,
    {
      action: "lcm_get_daily_tracker_rows",
      nonce: LCM.nonce,
      campaign_id: LCM.campaign_id,
    },
    function (response) {
      if (response.success) {
        const tracker = response.data;
        for (const date in tracker) {
          const row = $(`tr[data-date="${date}"]`);
          if (!row.length) continue;
          const data = tracker[date];
          row.attr("data-row-id", data.id);
          row.find(".reach-input").val(data.reach);
          row.find(".impressions-input").val(data.impressions);
          row.find(".spent-input").val(data.amount_spent);
        }
      }
    }
  );

  // Step 2: Handle Save button click
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
      campaign_id: campaign_id,
      date: date,
      reach: reach,
      impressions: impressions,
      amount_spent: spent,
    })
      .done(function (response) {
        if (response.success) {
          alert("Saved successfully");
        } else {
          alert("Save failed");
          console.error(response);
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
