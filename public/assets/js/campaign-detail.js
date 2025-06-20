jQuery(document).ready(function ($) {
  const $table = $("#daily-detail-table tbody");

  function fetchData() {
    const month = $("#filter-month").val();
    const from = $("#filter-from").val();
    const to = $("#filter-to").val();

    $.post(
      ajaxurl,
      {
        action: "lcm_get_daily_tracker",
        nonce: LCM_AJAX.nonce,
        campaign_id: LCM_CAMPAIGN_ID,
        month,
        from,
        to,
      },
      function (response) {
        if (!response.success) {
          $table.html('<tr><td colspan="13">Error loading data.</td></tr>');
          return;
        }

        const data = response.data;
        if (data.length === 0) {
          $table.html(
            '<tr><td colspan="13">No data found for selected filter.</td></tr>'
          );
          return;
        }

        $table.empty();
        data.forEach((row) => {
          const tr = $("<tr>");
          tr.append(`<td>${row.date}</td>`);
          tr.append(`<td>${row.total_leads}</td>`);
          tr.append(
            `<td><input type="number" class="form-control form-control-sm input-inline reach" data-date="${
              row.date
            }" value="${row.reach || ""}"></td>`
          );
          tr.append(
            `<td><input type="number" class="form-control form-control-sm input-inline impressions" data-date="${
              row.date
            }" value="${row.impressions || ""}"></td>`
          );
          tr.append(
            `<td><input type="number" class="form-control form-control-sm input-inline spent" data-date="${
              row.date
            }" value="${row.amount_spent || ""}"></td>`
          );
          tr.append(`<td>${row.connected}</td>`);
          tr.append(`<td>${row.not_connected}</td>`);
          tr.append(`<td>${row.relevant}</td>`);
          tr.append(`<td>${row.not_relevant}</td>`);
          tr.append(`<td>${row.not_available}</td>`);
          tr.append(`<td>${row.scheduled_store_visit}</td>`);
          tr.append(`<td>${row.store_visit}</td>`);
          tr.append(
            `<td><button class="btn btn-sm btn-success save-row" data-date="${row.date}">Save</button></td>`
          );
          $table.append(tr);
        });
      }
    );
  }

  fetchData();

  $("#apply-filters").on("click", fetchData);

  $table.on("click", ".save-row", function () {
    const date = $(this).data("date");
    const reach = $(`.reach[data-date="${date}"]`).val();
    const impressions = $(`.impressions[data-date="${date}"]`).val();
    const spent = $(`.spent[data-date="${date}"]`).val();

    $.post(
      ajaxurl,
      {
        action: "lcm_save_daily_tracker_row",
        nonce: LCM_AJAX.nonce,
        campaign_id: LCM_CAMPAIGN_ID,
        date,
        reach,
        impressions,
        amount_spent: spent,
      },
      function (response) {
        if (response.success) {
          alert("Saved");
          fetchData();
        } else {
          alert("Save failed");
        }
      }
    );
  });
});
