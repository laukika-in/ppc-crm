jQuery(function ($) {
  const AJAX = LCM.ajax_url;
  const NONCE = LCM.nonce;

  // Mount
  const $mount = $("#lcm-campaign-detail");
  $mount.html(`
    <div class="lcm-detail-filters mb-3">
      <input id="camp-month"    type="month" class="form-control form-control-sm me-2"/>
      <input id="camp-from"     type="date"  class="form-control form-control-sm me-2"/>
      <input id="camp-to"       type="date"  class="form-control form-control-sm"/>
    </div>
    <div class="lcm-summary mb-4"></div>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Date</th><th>Leads</th><th>Reach</th><th>Impr</th>
            <th>Spent</th><th>Connected</th><th>Relevant</th>
            <th>Not Conn</th><th>N/A</th><th>Sched Visit</th><th>Store Visit</th><th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  `);

  // init flatpickr
  $("#camp-month").flatpickr({
    plugins: [new monthSelectPlugin()],
    defaultDate: new Date(),
  });
  $("#camp-from,#camp-to").flatpickr({ dateFormat: "Y-m-d", allowInput: true });

  // state
  let campaignId = new URLSearchParams(window.location.search).get(
    "campaign_id"
  );
  let month = $("#camp-month").val();
  let from = $("#camp-from").val();
  let to = $("#camp-to").val();

  function reload() {
    $.getJSON(
      AJAX,
      {
        action: "lcm_get_campaign_detail_rows",
        nonce: NONCE,
        campaign_id: campaignId,
        month,
        from,
        to,
      },
      (res) => {
        const { summary, rows } = res.data;
        renderSummary(summary);
        renderRows(rows);
      }
    );
  }

  function renderSummary(s) {
    const html = `
      <div class="row">
        <div class="col">Total Leads: ${s.total_leads}</div>
        <div class="col">Connected: ${s.connected}</div>
        <div class="col">Relevant: ${s.relevant}</div>
        <!-- etc… -->
      </div>`;
    $(".lcm-summary").html(html);
  }

  function renderRows(rows) {
    const $tb = $mount.find("tbody").empty();
    rows.forEach((r) => {
      const $tr = $("<tr>").appendTo($tb).data(r);
      $tr.append(`<td>${r.date}</td>`);
      $tr.append(`<td>${r.total_leads}</td>`);
      $tr.append(
        `<td><span class="view-reach">${r.reach}</span><input type="number" class="edit-reach form-control form-control-sm d-none" value="${r.reach}"/></td>`
      );
      // … repeat for impressions & spent …
      $tr.append(`<td>${r.connected_not_relevant}</td>`);
      // … other calculated columns …
      $tr.append(`
        <td class="text-center">
          <button class="btn btn-sm btn-outline-primary edit-row">✏️</button>
          <button class="btn btn-sm btn-success save-row d-none">💾</button>
          <button class="btn btn-sm btn-secondary cancel-row d-none">❌</button>
          <a href="/lead-data?date_from=${r.date}&date_to=${r.date}&campaign=${campaignId}" 
             class="btn btn-sm btn-info">🔍</a>
        </td>`);
    });
  }

  // handlers: filters
  $("#camp-month").on("change", () => {
    month = $("#camp-month").val();
    reload();
  });
  $("#camp-from,#camp-to").on("change", () => {
    from = $("#camp-from").val();
    to = $("#camp-to").val();
    reload();
  });

  // handlers: edit/save/cancel
  $mount.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr");
    $tr
      .addClass("editing")
      .find(".view-reach, .view-impr, .view-spent")
      .addClass("d-none");
    $tr.find(".edit-reach, .edit-impr, .edit-spent").removeClass("d-none");
    $(this)
      .addClass("d-none")
      .siblings(".save-row, .cancel-row")
      .removeClass("d-none");
  });

  $mount.on("click", ".cancel-row", function () {
    reload();
  });

  $mount.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr"),
      data = $tr.data();
    data.reach = $tr.find(".edit-reach").val();
    data.impressions = $tr.find(".edit-impr").val();
    data.amount_spent = $tr.find(".edit-spent").val();
    $.post(
      AJAX,
      {
        action: "lcm_save_daily_tracker",
        nonce: NONCE,
        campaign_id: campaignId,
        date: data.date,
        reach: data.reach,
        impressions: data.impressions,
        amount_spent: data.amount_spent,
      },
      () => {
        // optionally chain update_campaign_daily_totals here…
        reload();
      }
    );
  });

  // initial load
  reload();
});
