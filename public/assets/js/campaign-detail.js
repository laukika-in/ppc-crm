jQuery(function ($) {
  const AJAX = LCM.ajax_url;
  const NONCE = LCM.nonce;
  const CAMPAIGN_ID = LCM.campaign_id;

  // Mount point
  const $mount = $("#lcm-campaign-detail");
  $mount.html(`
  <div class="d-flex align-items-center mb-3 lcm-detail-filters">

    <!-- Month filter + clear -->
    <div class="input-group input-group-sm me-2" id="filter-month-group">
      <input id="camp-month"
             class="form-control form-control-sm"
             type="month"
             value="${new Date().toISOString().slice(0, 7)}" />
      <button class="btn btn-outline-secondary clear-filter"
              data-filter="month">&times;</button>
    </div>

    <!-- From date filter + clear -->
    <div class="input-group input-group-sm me-2" id="filter-from-group">
      <input id="camp-from"
             class="form-control form-control-sm"
             type="date"
             placeholder="From" />
      <button class="btn btn-outline-secondary clear-filter"
              data-filter="from">&times;</button>
    </div>

    <!-- To date filter + clear -->
    <div class="input-group input-group-sm" id="filter-to-group">
      <input id="camp-to"
             class="form-control form-control-sm"
             type="date"
             placeholder="To" />
      <button class="btn btn-outline-secondary clear-filter"
              data-filter="to">&times;</button>
    </div>

  </div>

  <!-- Pagination (will show only if rows > 32) -->
  <nav class="mb-3"><ul class="pagination"></ul></nav>

  <!-- Summary placeholder -->
  <div class="lcm-summary row mb-4"></div>

  <!-- Data table -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>Date</th>
          <th>Reach</th>
          <th>Impression</th>
          <th>Amount Spent</th>
          <th>Total Leads</th>
          <th>Relevant</th>
          <th>Not Relevant</th>
          <th>Not Connected</th>
          <th>N/A</th>
          <th>Total Connected</th>
          <th>Scheduled Visit</th>
          <th>Store Visited</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
`);

  // Initialize Flatpickr
  // Month → clear date range
  $("#camp-month").on("change", () => {
    // Clear the from/to inputs
    $("#camp-from, #camp-to").val("");
    month = $("#camp-month").val();
    from = "";
    to = "";
    reload();
  });

  // From/To → clear month
  $("#camp-from, #camp-to").on("change", () => {
    // Clear the month input
    $("#camp-month").val("");
    month = "";
    from = $("#camp-from").val();
    to = $("#camp-to").val();
    reload();
  });

  // State
  let month = $("#camp-month").val();
  let from = $("#camp-from").val();
  let to = $("#camp-to").val();

  // Fetch & render
  function reload() {
    $.getJSON(AJAX, {
      action: "lcm_get_campaign_detail_rows",
      nonce: NONCE,
      campaign_id: CAMPAIGN_ID,
      month,
      from,
      to,
    }).done((res) => {
      const { summary, rows } = res.data;
      renderSummary(summary);
      renderRows(rows);
    });
  }
  function initSummaryEdit() {
    $(".lcm-summary-field").each(function () {
      const $wrap = $(this);
      const $val = $wrap.find(".lcm-summary-val");
      const key = $val.data("key");
      const val = $val.text();

      $wrap.append(
        `<i class='ms-2 text-muted fas fa-edit edit-summary-icon' style='cursor:pointer'></i>`
      );

      $wrap.find(".edit-summary-icon").on("click", function () {
        $val.replaceWith(
          `<input type="number" class="form-control form-control-sm lcm-summary-input" data-key="${key}" value="${val}">`
        );
        $wrap.append(
          `<button class='btn btn-sm btn-success ms-2 btn-save-summary'>Save</button>`
        );
        $wrap.append(
          `<button class='btn btn-sm btn-outline-secondary ms-1 btn-cancel-summary'>Cancel</button>`
        );

        $wrap.find(".btn-save-summary").on("click", function () {
          const newVal = $wrap.find(".lcm-summary-input").val();
          $.post(
            LCM.ajax_url,
            {
              action: "lcm_save_summary_field",
              nonce: LCM.nonce,
              campaign_id: LCM.campaign_id,
              field: key,
              value: newVal,
            },
            function () {
              location.reload();
            }
          );
        });

        $wrap.find(".btn-cancel-summary").on("click", function () {
          location.reload();
        });
      });
    });
  }
  $(document).ready(function () {
    initSummaryEdit();
  });
  // Summary block
  function renderSummary(s) {
    const html = `
      <div class="col">Total Leads: ${s.total_leads}</div>
      <div class="col">Connected: ${s.connected}</div>
      <div class="col">Relevant: ${s.relevant}</div>
      <div class="col">Not Connected: ${s.not_connected}</div>
      <div class="col">Scheduled Visits: ${s.scheduled_visit}</div>
      <div class="col">Store Visits: ${s.store_visit}</div>
    `;
    $mount.find(".lcm-summary").html(html);
  }

  // Build table rows
  function renderRows(rows) {
    const $tb = $mount.find("tbody").empty();
    rows.forEach((r) => {
      const $tr = $("<tr>").data(r).appendTo($tb);

      // 1) Date
      $tr.append(`<td>${r.date}</td>`);

      // 2) Reach (editable)
      $tr.append(`
      <td>
        <span class="view reach">${r.reach}</span>
        <input type="number" class="edit reach form-control form-control-sm d-none" value="${r.reach}"/>
      </td>`);

      // 3) Impressions (editable)
      $tr.append(`
      <td>
        <span class="view impr">${r.impressions}</span>
        <input type="number" class="edit impr form-control form-control-sm d-none" value="${r.impressions}"/>
      </td>`);

      // 4) Amount Spent (editable)
      $tr.append(`
      <td>
        <span class="view spent">${r.amount_spent}</span>
        <input type="number" step="0.01" class="edit spent form-control form-control-sm d-none" value="${r.amount_spent}"/>
      </td>`);

      // 5) Total Leads
      $tr.append(`<td>${r.total_leads}</td>`);

      // 6) Relevant
      $tr.append(`<td>${r.relevant}</td>`);

      // 7) Not Relevant
      $tr.append(`<td>${r.not_relevant}</td>`);

      // 8) Not Connected
      $tr.append(`<td>${r.not_connected}</td>`);

      // 9) N/A
      $tr.append(`<td>${r.not_available}</td>`);

      // 10) Total Connected
      $tr.append(`<td>${r.connected_total}</td>`);

      // 11) Scheduled Visit
      $tr.append(`<td>${r.scheduled_visit}</td>`);

      // 12) Store Visited
      $tr.append(`<td>${r.store_visit}</td>`);

      // 13) Actions
      $tr.append(`
      <td class="text-center">
        <button class="btn btn-sm btn-outline-primary edit-row">✏️</button>
        <button class="btn btn-sm btn-success save-row d-none">💾</button>
        <button class="btn btn-sm btn-secondary cancel-row d-none">❌</button>
        <a href="/lead-data?date_from=${r.date}&date_to=${r.date}&campaign_id=${CAMPAIGN_ID}"
           class="btn btn-sm btn-info">🔍</a>
      </td>`);
    });
  }

  // Filters change
  $("#camp-month").on("change", () => {
    month = $("#camp-month").val();
    reload();
  });
  $("#camp-from,#camp-to").on("change", () => {
    from = $("#camp-from").val();
    to = $("#camp-to").val();
    reload();
  });

  // Inline edit handlers
  $mount.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr");
    $tr
      .addClass("editing")
      .find(".view")
      .addClass("d-none")
      .siblings(".edit")
      .removeClass("d-none");
    $(this)
      .addClass("d-none")
      .siblings(".save-row, .cancel-row")
      .removeClass("d-none");
  });

  $mount.on("click", ".cancel-row", function () {
    reload();
  });

  $mount.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const data = $tr.data();
    data.reach = $tr.find(".edit.reach").val();
    data.impressions = $tr.find(".edit.impr").val();
    data.amount_spent = $tr.find(".edit.spent").val();

    $.post(AJAX, {
      action: "lcm_save_daily_tracker",
      nonce: NONCE,
      campaign_id: CAMPAIGN_ID,
      date: data.date,
      reach: data.reach,
      impressions: data.impressions,
      amount_spent: data.amount_spent,
    }).done(() => {
      // Optionally refresh campaign totals here
      reload();
    });
  });
  $mount.on("click", ".clear-filter", function () {
    const f = $(this).data("filter");
    switch (f) {
      case "month":
        $("#camp-month").val("");
        month = "";
        break;
      case "from":
        $("#camp-from").val("");
        from = "";
        break;
      case "to":
        $("#camp-to").val("");
        to = "";
        break;
    }
    page = 1;
    reload();
  });

  // Initial load
  reload();
});
