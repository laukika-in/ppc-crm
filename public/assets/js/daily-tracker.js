jQuery(function ($) {
  const AJAX = LCM.ajax_url;
  const NONCE = LCM.nonce;
  const PER_PAGE = 32;
  let currentSort = null;
  // State
  let filterCamp = "",
    month = "",
    from = "",
    to = "",
    page = 1;

  // Mount
  const $mount = $("#lcm-daily-tracker");
  $mount.html(`
    <div class="d-flex align-items-center mb-3">
      <div class="input-group input-group-sm me-2" id="filter-camp-group">
        <select id="dt-campaign" class="form-select form-select-sm">
          <option value="">All Campaigns</option>
          ${LCM.campaigns
            .map((c) => `<option value="${c[0]}">${c[1]}</option>`)
            .join("")}
        </select>
        <button class="btn btn-outline-secondary clear-filter" data-filter="campaign">&times;</button>
      </div>
      <div class="input-group input-group-sm me-2" id="filter-month-group">
        <input id="dt-month" type="month" class="form-control form-control-sm"/>
        <button class="btn btn-outline-secondary clear-filter" data-filter="month">&times;</button>
      </div>
      <div class="input-group input-group-sm me-2" id="filter-from-group">
        <input id="dt-from" type="date" class="form-control form-control-sm" placeholder="From"/>
        <button class="btn btn-outline-secondary clear-filter" data-filter="from">&times;</button>
      </div>
      <div class="input-group input-group-sm" id="filter-to-group">
        <input id="dt-to" type="date" class="form-control form-control-sm" placeholder="To"/>
        <button class="btn btn-outline-secondary clear-filter" data-filter="to">&times;</button>
      </div>
    </div>
    <div class="lcm-summary row mb-4"></div>
    <div class="position-relative">
      <div class="lcm-preloader position-absolute top-0 start-0 w-100 h-100 d-none
                  d-flex align-items-center justify-content-center bg-white bg-opacity-75">
        <div class="spinner-border"></div>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
          <thead>
            <tr>
             <th class="lcm-sortable" data-sort="date">Date <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="reach">Reach <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="impressions">Impr <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="amount_spent">Spent <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="total_leads">Leads <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="relevant">Relevant <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="not_relevant">Not Relevant <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="not_connected">Not Connected <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="not_available">N/A <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="connected_total">Connected <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="scheduled_visit">Sched Visit <span class="lcm-clear-sort">×</span></th>
    <th class="lcm-sortable" data-sort="store_visit">Store Visit <span class="lcm-clear-sort">×</span></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <nav class="mt-2"><ul class="pagination"></ul></nav>
    </div>
  `);

  // Flatpickr
  $("#dt-month").flatpickr({
    plugins: [new monthSelectPlugin({ dateFormat: "Y-m", altFormat: "F Y" })],
    defaultDate: new Date(),
  });
  $("#dt-from,#dt-to").flatpickr({ dateFormat: "Y-m-d", allowInput: true });

  // Helpers
  function togglePreloader(show) {
    $mount.find(".lcm-preloader").toggleClass("d-none", !show);
  }

  // Fetch
  function reload() {
    togglePreloader(true);
    $.getJSON(AJAX, {
      action: "lcm_get_daily_tracker_rows",
      nonce: NONCE,
      campaign_id: filterCamp,
      month,
      from,
      to,
      page,
      per_page: PER_PAGE,
    })
      .always(() => togglePreloader(false))
      .done((res) => {
        const { summary, rows, total_days } = res.data;
        renderSummary(summary);
        renderRows(rows);
        renderPager(total_days);
      });
  }

  function renderSummary(s) {
    $mount.find(".lcm-summary").html(`
      <div class="col">Leads: ${s.total_leads}</div>
      <div class="col">Connected: ${s.connected}</div>
      <div class="col">Relevant: ${s.relevant}</div>
      <div class="col">Not Relevant: ${s.not_relevant}</div>
      <div class="col">Not Connected: ${s.not_connected}</div>
      <div class="col">N/A: ${s.not_available}</div>
      <div class="col">Scheduled: ${s.scheduled_visit}</div>
      <div class="col">Visited: ${s.store_visit}</div>
      
    `);
  }

  function renderRows(rows) {
    const $tb = $mount.find("tbody").empty();
    rows.forEach((r) => {
      $("<tr>").appendTo($tb).append(`
        <td>${r.date}</td>
        <td>${r.reach}</td>
        <td>${r.impressions}</td>
        <td>${r.amount_spent}</td>
        <td>${r.total_leads}</td>
        <td>${r.relevant}</td>
        <td>${r.not_relevant}</td>
        <td>${r.not_connected}</td>
        <td>${r.not_available}</td>
        <td>${r.connected_total}</td>
        <td>${r.scheduled_visit}</td>
        <td>${r.store_visit}</td>
      `);
    });
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
    $(`#${tableId} th`).removeClass(
      "lcm-sort-asc lcm-sort-desc lcm-sort-active"
    );

    if (!col) return; // No sort to apply

    const $th = $(`#${tableId} th[data-sort="${col}"]`);
    $th.addClass(
      "lcm-sort-active " + (dir === "asc" ? "lcm-sort-asc" : "lcm-sort-desc")
    );
  }

  $mount.on("click", "th.lcm-sortable", function () {
    const col = $(this).data("sort");
    const dir =
      currentSort?.col === col && currentSort.dir === "asc" ? "desc" : "asc";
    currentSort = { col, dir };
    reload();
  });
  $mount.on("click", ".lcm-clear-sort", function (e) {
    e.stopPropagation();
    currentSort = null;
    reload();
  });
  function renderPager(totalDays) {
    const pages = Math.ceil(totalDays / PER_PAGE);
    const $ul = $mount.find(".pagination").empty();
    if (pages > 1) {
      for (let i = 1; i <= pages; i++) {
        $ul.append(`
          <li class="page-item ${i === page ? "active" : ""}">
            <a class="page-link" href="#">${i}</a>
          </li>`);
      }
    }
  }

  // Clear‐filter buttons
  $mount.on("click", ".clear-filter", function () {
    const f = $(this).data("filter");
    switch (f) {
      case "campaign":
        filterCamp = "";
        $("#dt-campaign").val("");
        break;
      case "month":
        month = "";
        $("#dt-month").val("");
        break;
      case "from":
        from = "";
        $("#dt-from").val("");
        break;
      case "to":
        to = "";
        $("#dt-to").val("");
        break;
    }
    page = 1;
    reload();
  });

  // Filters mutual‐excl & trigger reload
  $("#dt-campaign").on("change", function () {
    filterCamp = this.value;
    page = 1;
    reload();
  });
  $("#dt-month").on("change", () => {
    $("#dt-from,#dt-to").val("");
    from = to = "";
    month = $("#dt-month").val();
    page = 1;
    reload();
  });
  $("#dt-from,#dt-to").on("change", () => {
    $("#dt-month").val("");
    month = "";
    from = $("#dt-from").val();
    to = $("#dt-to").val();
    page = 1;
    reload();
  });

  // Page click
  $mount.on("click", ".page-link", function (e) {
    e.preventDefault();
    page = +$(this).text();
    reload();
  });

  // Initial load
  reload();
});
