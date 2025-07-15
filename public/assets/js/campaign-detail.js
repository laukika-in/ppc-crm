jQuery(function ($) {
  const AJAX = LCM.ajax_url;
  const NONCE = LCM.nonce;
  const CAMPAIGN_ID = LCM.campaign_id;
  let rows = [];

  // Mount point & initial markup
  const $mount = $("#lcm-campaign-detail");
  $mount.html(`
    <div class="d-flex align-items-center mb-3 lcm-detail-filters">
      <div class="input-group input-group-sm me-2">
        <input id="camp-month" class="form-control form-control-sm" type="month"
               value="${new Date().toISOString().slice(0, 7)}"/>
        <button class="btn btn-outline-secondary clear-filter" data-filter="month">&times;</button>
      </div>
      <div class="input-group input-group-sm me-2">
        <input id="camp-from" class="form-control form-control-sm" type="date" placeholder="From"/>
        <button class="btn btn-outline-secondary clear-filter" data-filter="from">&times;</button>
      </div>
      <div class="input-group input-group-sm">
        <input id="camp-to" class="form-control form-control-sm" type="date" placeholder="To"/>
        <button class="btn btn-outline-secondary clear-filter" data-filter="to">&times;</button>
      </div>
    </div>
    <nav class="mb-3"><ul class="pagination"></ul></nav>
    <div class="lcm-summary row mb-4"></div>
    <div class="table-responsive">
      <table id="campaign-detail-table"
             class="table table-bordered table-striped table-hover align-middle">
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

  // State
  let month = $("#camp-month").val();
  let from = "";
  let to = "";
  let currentSort = { col: "date", dir: "desc" };

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
      rows = res.data.rows;
      renderSummary(res.data.summary);
      renderRows(rows);
      applySortingIcons(
        "campaign-detail-table",
        currentSort.col,
        currentSort.dir
      );
    });
  }

  function renderSummary(s) {
    $mount.find(".lcm-summary").html(`
      <div class="col">Total Leads: ${s.total_leads}</div>
      <div class="col">Connected: ${s.connected}</div>
      <div class="col">Relevant: ${s.relevant}</div>
      <div class="col">Not Connected: ${s.not_connected}</div>
      <div class="col">Scheduled Visits: ${s.scheduled_visit}</div>
      <div class="col">Store Visits: ${s.store_visit}</div>
    `);
  }

  function renderRows(data = []) {
    const $tb = $mount.find("tbody").empty();
    data.forEach((r) => {
      // assume r.id holds the tracker row ID
      const rowId = r.id || "";
      const $tr = $(
        `<tr data-date="${r.date}" data-row-id="${rowId}">`
      ).appendTo($tb);
      $tr.append(`<td>${r.date}</td>`);
      ["reach", "impressions", "amount_spent"].forEach((field) => {
        const step = field === "amount_spent" ? ' step="0.01"' : "";
        $tr.append(`
          <td>
            <span class="view ${field}">${r[field]}</span>
            <input type="number" data-type="${field}" class="edit editable ${field}-input form-control form-control-sm d-none"
                   value="${r[field]}"${step}/>
          </td>`);
      });
      [
        "total_leads",
        "relevant",
        "not_relevant",
        "not_connected",
        "not_available",
        "connected_total",
        "scheduled_visit",
        "store_visit",
      ].forEach((col) => $tr.append(`<td>${r[col]}</td>`));

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

  // Sorting helpers
  function sortBy(col, dir = "asc") {
    return (a, b) => {
      let A = a[col] ?? "",
        B = b[col] ?? "";
      const num =
        !isNaN(parseFloat(A)) &&
        isFinite(A) &&
        !isNaN(parseFloat(B)) &&
        isFinite(B);
      if (num) {
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
      .find(".sort-clear")
      .remove();
    $(`#${tableId} th[data-sort="${col}"]`).addClass(
      dir === "asc" ? "lcm-sort-asc" : "lcm-sort-desc"
    );
  }

  // Event bindings
  // Filters
  $("#camp-month").on("change", () => {
    from = to = "";
    month = $("#camp-month").val();
    reload();
  });
  $("#camp-from,#camp-to").on("change", () => {
    month = "";
    from = $("#camp-from").val();
    to = $("#camp-to").val();
    reload();
  });
  $mount.on("click", ".clear-filter", function () {
    const f = $(this).data("filter");
    if (f === "month") {
      $("#camp-month").val("");
      month = "";
    }
    if (f === "from") {
      $("#camp-from").val("");
      from = "";
    }
    if (f === "to") {
      $("#camp-to").val("");
      to = "";
    }
    reload();
  });

  // Sorting
  $mount.on("click", "th.lcm-sortable", function () {
    const col = $(this).data("sort");
    const dir =
      currentSort.col === col && currentSort.dir === "asc" ? "desc" : "asc";
    currentSort = { col, dir };
    rows.sort(sortBy(col, dir));
    applySortingIcons("campaign-detail-table", col, dir);
    renderRows(rows);
  });
  $mount.on("click", ".sort-clear", (e) => {
    e.stopPropagation();
    currentSort = { col: "date", dir: "desc" };
    reload();
  });

  // Inline edit
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
  $mount.on("click", ".cancel-row", () => reload());

  // Save handler
  $mount.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const rowId = $tr.data("row-id");
    const payload = {
      action: "lcm_save_daily_tracker",
      nonce: NONCE,
      row_id: rowId,
      campaign_id: CAMPAIGN_ID,
      date: $tr.data("date"),
      reach: parseInt($tr.find(".reach-input").val(), 10) || 0,
      impressions: parseInt($tr.find(".impressions-input").val(), 10) || 0,
      amount_spent: parseFloat($tr.find(".amount_spent-input").val()) || 0,
    };
    $.post(AJAX, payload, null, "json")
      .done((res) => {
        if (res.success) reload();
        else alert("Save failed: " + (res.data?.message || "unknown"));
      })
      .fail(() => alert("Server error, please try again."));
  });

  // Initial load
  reload();
});
