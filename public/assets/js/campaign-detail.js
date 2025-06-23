jQuery(document).ready(function ($) {
  // Step 1: Populate data
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
          row.find(".reach-display").text(data.reach);
          row.find(".impressions-display").text(data.impressions);
          row.find(".spent-display").text(data.amount_spent);
        }
      }
    }
  );

  // Step 2: Edit Mode
  $(document).on("click", ".edit-tracker", function () {
    const $row = $(this).closest("tr");
    $row.find(".tracker-display").addClass("d-none");
    $row.find(".tracker-input").removeClass("d-none");
    $row.find(".edit-tracker").addClass("d-none");
    $row.find(".save-daily-tracker").removeClass("d-none");
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
  $.getJSON(
    CampaignDetail.ajax_url,
    {
      action: "lcm_get_campaign_leads_json",
      nonce: CampaignDetail.nonce,
      campaign_id: CampaignDetail.campaign_id,
    },
    function (res) {
      if (!res.success) {
        console.error(res);
        return;
      }
      // 1) build your per-day table from res.data.days
      renderDailyTable(res.data.days);
      // 2) update your summary widgets:
      $("#total-leads").text(res.data.total);
      const byType = res.data.by_type.reduce((acc, r) => {
        acc[r.attempt_type] = r.qty;
        return acc;
      }, {});
      $("#connected").text(
        (byType["Connected:Relevant"] || 0) +
          (byType["Connected:Not Relevant"] || 0)
      );
      $("#relevant").text(byType["Connected:Relevant"] || 0);
      $("#not-connected").text(byType["Not Connected"] || 0);
      $("#scheduled-visit").text(res.data.scheduled);
      $("#store-visit").text(res.data.visit);
    }
  );
});
