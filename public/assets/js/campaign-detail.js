jQuery(document).ready(function ($) {
  // Step 1: Populate data
  const campaignId = LCM.campaign_id;

  // 1) initialize flatpickr
  flatpickr("#month", {
    dateFormat: "Y-m",
    allowInput: true,
    onChange: reload,
  });
  flatpickr("#from, #to", {
    dateFormat: "Y-m-d",
    allowInput: true,
    onChange: reload,
  });

  // 2) cache selectors
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
  const $tbody = $("table.lcm-table tbody");

  // 3) reload function
  function reload() {
    const month = $("#month").val(),
      from = $("#from").val(),
      to = $("#to").val();

    $.getJSON(LCM.ajax_url, {
      action: "lcm_get_campaign_leads_json",
      nonce: LCM.nonce,
      campaign_id: campaignId,
      month,
      from,
      to,
    })
      .done((res) => {
        if (!res.success) return alert(res.data || "Error loading data");

        const d = res.data,
          days = d.days; // array of {date, leads}

        // 4) render summary
        $summary.total.text(d.total);
        $summary.connected.text(d.by_type.reduce((sum, t) => sum + t.qty, 0));
        // find each type in d.by_type:
        $summary.relevant.text(
          (
            d.by_type.find((t) => t.attempt_type === "Connected:Relevant") || {
              qty: 0,
            }
          ).qty
        );
        $summary.notRelevant.text(
          (
            d.by_type.find(
              (t) => t.attempt_type === "Connected:Not Relevant"
            ) || { qty: 0 }
          ).qty
        );
        $summary.notConnected.text(
          (
            d.by_type.find((t) => t.attempt_type === "Not Connected") || {
              qty: 0,
            }
          ).qty
        );
        $summary.scheduled.text(d.scheduled);
        $summary.store.text(d.visit);
        // N/A = total – (connected + notConnected)
        const connected = parseInt($summary.connected.text(), 10),
          notCon = parseInt($summary.notConnected.text(), 10),
          na = d.total - (connected + notCon);
        $summary.na.text(na);

        // 5) render table body
        let html = "";
        days.forEach((r) => {
          const track = d.tracker?.[r.date] || {};
          const rel = (
            d.by_type.find((t) => t.attempt_type === "Connected:Relevant") || {
              qty: 0,
            }
          ).qty;
          // …you can compute not_relevant, not_connected etc here as in PHP…
          html += `
          <tr data-date="${r.date}" data-row-id="${track.id || 0}">
            <td>${r.date}</td>
            <td>${r.leads}</td>
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
            <td>${connected}</td>
            <td>${rel}</td>
            <td>${/* not_relevant */ ""}</td>
            <td>${/* not_connected */ ""}</td>
            <td>${na}</td>
            <td>${d.scheduled}</td>
            <td>${d.visit}</td>
          </tr>
        `;
        });
        $tbody.html(html);
      })
      .fail(() => alert("Server error"));
  }

  // 6) kick off the first load
  reload();

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
