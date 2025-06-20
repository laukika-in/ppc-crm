// File: assets/js/campaign-detail.js
console.log("✅ campaign-detail.js loaded");

jQuery(document).ready(function ($) {
  // Delegate input changes on Reach/Impressions/Spent fields
  $(document).on("blur", ".editable-field", function () {
    const row = $(this).closest("tr");
    const date = row.data("date");
    const campaign_id = LCM.campaign_id;
    const reach = parseInt(row.find(".reach-field").val()) || 0;
    const impressions = parseInt(row.find(".impressions-field").val()) || 0;
    const amount_spent = parseFloat(row.find(".spent-field").val()) || 0;

    $.ajax({
      url: LCM.ajax_url,
      method: "POST",
      data: {
        action: "lcm_save_daily_metrics",
        nonce: LCM.nonce,
        campaign_id,
        date,
        reach,
        impressions,
        amount_spent,
      },
      success: function (res) {
        if (res.success) {
          console.log("Saved successfully");
        } else {
          alert("Failed to save metrics.");
        }
      },
      error: function () {
        alert("Error during AJAX request.");
      },
    });
  });
  $(document).on("click", ".save-daily-tracker", function () {
    const $row = $(this).closest("tr");
    const rowId = $row.data("row-id");
    const reach = parseInt($row.find(".reach-input").val()) || 0;
    const impressions = parseInt($row.find(".impressions-input").val()) || 0;
    const spent = parseFloat($row.find(".spent-input").val()) || 0;
    const campaign_id = LCM.campaign_id;
    const date = $row.data("date");

    console.log("Saving row ID:", rowId, reach, impressions, spent);

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
});
