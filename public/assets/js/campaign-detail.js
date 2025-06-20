jQuery(document).ready(function ($) {
  function loadTrackerRows() {
    $.post(
      LCMTracker.ajax_url,
      {
        action: "lcm_get_daily_tracker",
        campaign_id: LCMTracker.campaign_id,
        nonce: LCMTracker.nonce,
      },
      function (res) {
        if (res.success && res.data.length > 0) {
          let rows = "";
          res.data.forEach((row) => {
            rows += `<tr data-date="${row.track_date}">
            <td>${row.track_date}</td>
            <td><input type="number" class="form-control form-control-sm reach" value="${
              row.reach || ""
            }"></td>
            <td><input type="number" class="form-control form-control-sm imp" value="${
              row.impressions || ""
            }"></td>
            <td><input type="number" class="form-control form-control-sm amt" value="${
              row.amount_spent || ""
            }"></td>
            <td><button class="btn btn-primary btn-sm save-row">Save</button></td>
          </tr>`;
          });
          $("#tracker-body").html(rows);
        } else {
          $("#tracker-body").html(
            '<tr><td colspan="5">No data available</td></tr>'
          );
        }
      }
    );
  }

  $("#tracker-body").on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const track_date = $tr.data("date");
    const reach = $tr.find(".reach").val();
    const imp = $tr.find(".imp").val();
    const amt = $tr.find(".amt").val();

    $.post(
      LCMTracker.ajax_url,
      {
        action: "lcm_save_daily_tracker_row",
        campaign_id: LCMTracker.campaign_id,
        track_date,
        reach,
        impressions: imp,
        amount_spent: amt,
        nonce: LCMTracker.nonce,
      },
      function (res) {
        if (res.success) {
          alert("Saved!");
          loadTrackerRows();
        }
      }
    );
  });

  loadTrackerRows();
});
