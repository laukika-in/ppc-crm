jQuery(function ($) {
  const CID = LCMTracker.campaign_id;

  function loadRows() {
    $.get(
      LCMTracker.ajax_url,
      {
        action: "lcm_get_daily_tracker_rows",
        campaign_id: CID,
        nonce: LCMTracker.nonce,
      },
      function (res) {
        if (!res.success || !res.data.length) {
          $("#tracker-body").html(
            '<tr><td colspan="5">No tracker data found.</td></tr>'
          );
          return;
        }

        const rows = res.data
          .map(
            (r) => `
        <tr data-date=\"${r.track_date}\">
          <td>${r.track_date}</td>
          <td><input class=\"form-control form-control-sm reach\" type=\"number\" value=\"${r.reach}\"/></td>
          <td><input class=\"form-control form-control-sm imp\" type=\"number\" value=\"${r.impressions}\"/></td>
          <td><input class=\"form-control form-control-sm amt\" type=\"number\" step=\"0.01\" value=\"${r.amount_spent}\"/></td>
          <td><button class=\"btn btn-sm btn-success save-row\">Save</button></td>
        </tr>
      `
          )
          .join("");

        $("#tracker-body").html(rows);
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
        }
      }
    );
  });

  loadRows();
});
