// File: assets/js/campaign-detail.js
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
});
