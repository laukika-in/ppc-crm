jQuery(function ($) {
  const AJAX = LCM.ajax_url;
  const NONCE = LCM.nonce;
  const CAMPAIGN_ID = LCM.campaign_id;
  let rows = [];
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
    <table id="campaign-detail-table" class="table table-bordered table-striped table-hover align-middle">
     <thead>
  <tr>
    <th data-sort="date" class="lcm-sortable">Date <span class="sort-clear">×</span></th>
    <th data-sort="reach" class="lcm-sortable">Reach <span class="sort-clear">×</span></th>
    <th data-sort="impressions" class="lcm-sortable">Impression <span class="sort-clear">×</span></th>
    <th data-sort="amount_spent" class="lcm-sortable">Amount Spent <span class="sort-clear">×</span></th>
    <th data-sort="total_leads" class="lcm-sortable">Total Leads <span class="sort-clear">×</span></th>
    <th data-sort="relevant" class="lcm-sortable">Relevant <span class="sort-clear">×</span></th>
    <th data-sort="not_relevant" class="lcm-sortable">Not Relevant <span class="sort-clear">×</span></th>
    <th data-sort="not_connected" class="lcm-sortable">Not Connected <span class="sort-clear">×</span></th>
    <th data-sort="not_available" class="lcm-sortable">N/A <span class="sort-clear">×</span></th>
    <th data-sort="connected_total" class="lcm-sortable">Total Connected <span class="sort-clear">×</span></th>
    <th data-sort="scheduled_visit" class="lcm-sortable">Scheduled Visit <span class="sort-clear">×</span></th>
    <th data-sort="store_visit" class="lcm-sortable">Store Visited <span class="sort-clear">×</span></th>
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
  let currentSort = { col: "date", dir: "desc" }; // default sort

  $mount.on("click", "th.lcm-sortable", function () {
    const col = $(this).data("sort");
    const dir =
      currentSort.col === col && currentSort.dir === "asc" ? "desc" : "asc";
    currentSort = { col, dir };

    rows.sort(sortBy(col, dir));
    applySortingIcons(
      "campaign-detail-table",
      currentSort.col,
      currentSort.dir
    );
    renderRows(rows); // custom function that re-renders rows
  });
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
      const summary = res.data.summary;
      rows = res.data.rows;
      renderSummary(summary);
      renderRows(rows);
    });
  }

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
  function sortBy(col, dir = "asc") {
    return function (a, b) {
      let A = a[col] ?? "";
      let B = b[col] ?? "";

      const isNum =
        !isNaN(parseFloat(A)) &&
        isFinite(A) &&
        !isNaN(parseFloat(B)) &&
        isFinite(B);

      if (isNum) {
        A = parseFloat(A);
        B = parseFloat(B);
      } else {
        A = String(A).toLowerCase();
        B = String(B).toLowerCase();
      }

      return (A < B ? -1 : A > B ? 1 : 0) * (dir === "asc" ? 1 : -1);
    };
  }
  function applySortingIcons(tableId, col, dir) {
    $(`#${tableId} th`)
      .removeClass("lcm-sort-asc lcm-sort-desc")
      .find(".lcm-clear-sort")
      .remove();
    const className = dir === "asc" ? "lcm-sort-asc" : "lcm-sort-desc";
    $(`#${tableId} th[data-sort="${col}"]`).addClass(className);
  }

  // Build table rows
  function renderRows(data = []) {
    const $tb = $mount.find("tbody").empty();
    data.forEach((r) => {
      const $tr = $("<tr>").data(r).appendTo($tb);
      // 1) Date
      $tr.append(`<td>${r.date}</td>`);

      // 2) Reach (editable)
      $tr.append(`
      <td>
   <span class="view reach">${r.reach}</span>
  <input type="number" data-type="reach"
         class="edit editable reach-input form-control form-control-sm d-none"
         value="${r.reach}"/>
</td>`);

      // 3) Impressions (editable)
      $tr.append(`
      <td>
      <span class="view impr">${r.impressions}</span>
  <input type="number" data-type="impressions"
         class="edit editable impressions-input form-control form-control-sm d-none"
         value="${r.impressions}"/>
</td>`);

      // 4) Amount Spent (editable)
      $tr.append(`
      <td>
   <span class="view spent">${r.amount_spent}</span>
  <input type="number" step="0.01" data-type="amount_spent"
         class="edit editable spent-input form-control form-control-sm d-none"
         value="${r.amount_spent}"/>
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

  // in campaign-detail.js, after renderRows()
  $mount.on("click", ".save-row", function () {
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
      .done(() => {
        reload();
      })
      .fail(() => {
        alert("Failed to save. Please try again.");
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

  // Step 2: Edit Mode
  $(document).on("click", ".edit-tracker", function () {
    const $row = $(this).closest("tr");
    $row.find(".tracker-display").addClass("d-none");
    $row.find(".tracker-input").removeClass("d-none");
    $row.find(".edit-tracker").addClass("d-none");
    $row.find(".save-row").removeClass("d-none");
    $row.find(".cancel-tracker").removeClass("d-none");
    $row.addClass("table-warning shadow-sm");
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
    $row.find(".save-row, .cancel-tracker").addClass("d-none");
    $row.removeClass("table-warning shadow-sm");
  });

  $mount.on("click", ".sort-clear", function (e) {
    e.stopPropagation();
    currentSort = { col: "date", dir: "desc" }; // or null if you want no sort
    reload();
  });
  // Initial load
  reload();
});
