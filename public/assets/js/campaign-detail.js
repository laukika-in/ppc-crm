jQuery(document).ready(function ($) {
  const campaignId = LCM.campaign_id;

  const $month = $("#month");
  const $from = $("#lcm-filter-date-from");
  const $to = $("#lcm-filter-date-to");
  const $tbody = $("table.lcm-table tbody");

  const $summary = {
    total: $("strong:contains('Total Leads')").next(),
    connected: $("strong:contains('Connected')").next(),
    relevant: $("strong:contains('Relevant')").next(),
    notRelevant: $("strong:contains('Not Relevant')").next(),
    notConnected: $("strong:contains('Not Connected')").next(),
    na: $("strong:contains('N/A')").next(),
    scheduled: $("strong:contains('Scheduled Visit')").next(),
    store: $("strong:contains('Store Visit')").next(),
  };

  // --- Initialize flatpickr ---
  flatpickr($month[0], {
    dateFormat: "Y-m",
    allowInput: true,
    plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: "Y-m" })],
    onChange: reload,
  });

  flatpickr([$from[0], $to[0]], {
    dateFormat: "Y-m-d",
    allowInput: true,
    onChange: reload,
  });

  function reload() {
    $.getJSON(LCM.ajax_url, {
      action: "lcm_get_campaign_detail_rows",
      nonce: LCM.nonce,
      campaign_id: campaignId,
      month: $month.val(),
      from: $from.val(),
      to: $to.val(),
    })
      .done((res) => {
        if (!res.success) return alert(res.data || "Error loading data");
        const d = res.data;

        $summary.total.text(d.total_leads);
        $summary.connected.text(d.connected);
        $summary.relevant.text(d.relevant);
        $summary.notRelevant.text(d.not_relevant);
        $summary.notConnected.text(d.not_connected);
        $summary.na.text(d.na);
        $summary.scheduled.text(d.scheduled_visit);
        $summary.store.text(d.store_visit);

        let html = "";
        d.rows.forEach((r) => {
          const track = d.tracker[r.date] || {};
          html += `
          <tr data-date="${r.date}" data-row-id="${track.id || 0}">
            <td>${r.date}</td>
            <td>${r.total_leads}</td>
            <td><input class="form-control form-control-sm reach-input" value="${
              track.reach || ""
            }"></td>
            <td><input class="form-control form-control-sm impressions-input" value="${
              track.impressions || ""
            }"></td>
            <td><input class="form-control form-control-sm spent-input" value="${
              track.amount_spent || ""
            }"></td>
            <td>
              <button class="btn btn-sm btn-outline-secondary edit-tracker">✏️</button>
              <button class="btn btn-sm btn-secondary cancel-tracker d-none">❌</button>
              <button class="btn btn-sm btn-success save-daily-tracker d-none">💾</button>
              <a class="btn btn-sm btn-primary" href="${site_url(
                "/lead-data"
              )}?date_from=${r.date}&date_to=${r.date}">View Leads</a>
            </td>
            <td>${r.connected}</td>
            <td>${r.relevant}</td>
            <td>${r.not_relevant}</td>
            <td>${r.not_connected}</td>
            <td>${r.na}</td>
            <td>${r.scheduled_visit}</td>
            <td>${r.store_visit}</td>
          </tr>
        `;
        });

        $tbody.html(html);
      })
      .fail(() => alert("Server error"));
  }

  reload(); // initial load

  // --- Edit tracker row ---
  $(document).on("click", ".edit-tracker", function () {
    const $row = $(this).closest("tr");
    $row.find("input").prop("disabled", false);
    $row.find(".edit-tracker").addClass("d-none");
    $row.find(".save-daily-tracker, .cancel-tracker").removeClass("d-none");
    $row.addClass("table-warning shadow-sm");
  });

  // --- Cancel edit ---
  $(document).on("click", ".cancel-tracker", function () {
    const $row = $(this).closest("tr");
    const original = {
      reach: $row.find(".reach-input").attr("value"),
      impressions: $row.find(".impressions-input").attr("value"),
      spent: $row.find(".spent-input").attr("value"),
    };
    $row.find(".reach-input").val(original.reach).prop("disabled", true);
    $row
      .find(".impressions-input")
      .val(original.impressions)
      .prop("disabled", true);
    $row.find(".spent-input").val(original.spent).prop("disabled", true);
    $row.find(".edit-tracker").removeClass("d-none");
    $row.find(".save-daily-tracker, .cancel-tracker").addClass("d-none");
    $row.removeClass("table-warning shadow-sm");
  });

  // --- Save tracker row ---
// Save Tracker Row
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
        // Update cells and refresh table totals
        $row.find(".reach-input").val(reach);
        $row.find(".impressions-input").val(impressions);
        $row.find(".spent-input").val(spent);

        // Visuals
        $row.removeClass("table-warning shadow-sm");
        $row.find(".save-daily-tracker").addClass("d-none");
        $row.find(".cancel-tracker").addClass("d-none");
        $row.find(".edit-tracker").removeClass("d-none");

        // Reload totals
        reload();
      } else {
        alert("Save failed");
      }
    })
    .fail(function () {
      alert("Server error");
    });
});

// Cancel Tracker Edit
$(document).on("click", ".cancel-tracker", function () {
  const $row = $(this).closest("tr");

  // Restore old values (text input stays)
  const oldReach = $row.find(".reach-input").data("old") || 0;
  const oldImpr = $row.find(".impressions-input").data("old") || 0;
  const oldSpent = $row.find(".spent-input").data("old") || 0;

  $row.find(".reach-input").val(oldReach);
  $row.find(".impressions-input").val(oldImpr);
  $row.find(".spent-input").val(oldSpent);

  $row.removeClass("table-warning shadow-sm");
  $row.find(".save-daily-tracker").addClass("d-none");
  $row.find(".cancel-tracker").addClass("d-none");
  $row.find(".edit-tracker").removeClass("d-none");
});

// Enable Edit Mode
$(document).on("click", ".edit-tracker", function () {
  const $row = $(this).closest("tr");

  // Store original values
  $row.find(".reach-input").data("old", $row.find(".reach-input").val());
  $row.find(".impressions-input").data("old", $row.find(".impressions-input").val());
  $row.find(".spent-input").data("old", $row.find(".spent-input").val());

  $row.addClass("table-warning shadow-sm");
  $row.find(".edit-tracker").addClass("d-none");
  $row.find(".save-daily-tracker").removeClass("d-none");
  $row.find(".cancel-tracker").removeClass("d-none");
});

});
