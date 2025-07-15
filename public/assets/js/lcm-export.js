jQuery(function ($) {
  // Collect filter values per screen
  function gatherFilters(screen) {
    const f = {};

    if (screen === "leads") {
      f.client = $("#lcm-filter-client").val() || "";
      f.date_from = $("#lcm-filter-date-from").val() || "";
      f.date_to = $("#lcm-filter-date-to").val() || "";
      f.adname = $("#lcm-filter-adname").val() || "";
      f.adset = $("#lcm-filter-adset").val() || "";
      f.day = $("#lcm-filter-day").val() || "";
      f.client_type = $("#lcm-filter-client-type").val() || "";
      f.source = $("#lcm-filter-source").val() || "";
      f.attempt_type = $("#lcm-filter-attempt-type").val() || "";
      f.attempt_status = $("#lcm-filter-attempt-status").val() || "";
      f.store_visit_status = $("#lcm-filter-store-visit-status").val() || "";
      f.occasion = $("#lcm-filter-occasion").val() || "";
      f.city = $("#lcm-filter-city").val() || "";
      f.text = $("#lcm-filter-text").val() || "";
      f.budget = $("#lcm-filter-budget").val() || "";
      f.product = $("#lcm-filter-product").val() || "";
    } else if (screen === "campaigns") {
      f.client = $("#lcm-filter-client").val() || "";
      f.date_from = $("#lcm-filter-date-from").val() || "";
      f.date_to = $("#lcm-filter-date-to").val() || "";
      f.month = $("#lcm-filter-month-camp").val() || "";
      f.location = $("#lcm-filter-location-camp").val() || "";
      f.store = $("#lcm-filter-store-camp").val() || "";
      f.connected = $("#lcm-filter-connected-camp").val() || "";
    } else if (screen === "daily" || screen === "campaign-detail") {
      f.month = $("#camp-month").val() || "";
      f.from = $("#camp-from").val() || "";
      f.to = $("#camp-to").val() || "";
      // if you ever want to pass campaign_id too:
      // f.campaign_id = LCM.campaign_id;
    }

    return f;
  }

  // Main export handler
  $(".lcm-export-btn").on("click", function () {
    const $btn = $(this).prop("disabled", true);
    const screen = $btn.data("export-screen"); // 'leads','campaigns','daily'
    const filters = gatherFilters(screen);

    // show or reset progress UI
    let $progress = $btn.siblings(".lcm-export-progress");
    if (!$progress.length) {
      $progress = $(
        '<div class="lcm-export-progress"><div class="bar">0%</div></div>'
      ).insertAfter($btn);
    }
    $progress.find(".bar").css("width", "0%").text("0%");

    // kick off export job
    $.post(
      LCMExport.ajax_url,
      {
        action: "lcm_start_export",
        export_type: screen,
        filters: filters,
      },
      function (res) {
        const jobId = res.data.job_id;
        const interval = setInterval(function () {
          $.post(
            LCMExport.ajax_url,
            {
              action: "lcm_get_export_status",
              job_id: jobId,
            },
            function (res2) {
              const d = res2.data;
              const pct = d.total
                ? Math.floor((100 * d.processed) / d.total)
                : 0;
              $progress
                .find(".bar")
                .css("width", pct + "%")
                .text(pct + "%");

              if (d.status === "completed") {
                clearInterval(interval);
                $btn.prop("disabled", false);
                $("<a>")
                  .attr("href", d.file_path)
                  .attr("download", "")
                  .addClass("btn btn-sm btn-success mt-2")
                  .text("Download CSV")
                  .insertAfter($btn);
              }
            }
          );
        }, 3000);
      }
    );
  });
});
