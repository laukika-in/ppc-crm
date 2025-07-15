jQuery(function ($) {
  // ─── 1) Preloader overlay ─────────────────────────────────────────────────
  const $preloader = $("#lcm-preloader-camp");
  function showPreloader() {
    $preloader.show();
  }
  function hidePreloader() {
    $preloader.hide();
  }

  // ─── 2) Core configuration & cache ────────────────────────────────────────
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;
  let cachedPages = {};
  let lastTotalPages = 1;
  let page = 1;
  let currentSortCol = "";
  let currentSortDir = "asc";
  // ─── 3) Column definitions ────────────────────────────────────────────────
  const allCols = [
    ["_action", "Action", "action"],
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
    ["campaign_title", "Campaign", "text"],
    [
      "month",
      "Month",
      "select",
      [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ],
    ],
    ["week", "Week", "text"],
    ["campaign_name", "Name", "text"],
    ["campaign_date", "Date", "date"],
    ["location", "Location", "text"],
    ["adset", "Adset", "text"],
    ["leads", "Leads", "readonly"],
    ["total_reach", "Reach", "readonly"],
    ["total_impressions", "Impr", "readonly"],
    ["total_spent", "Spent", "readonly"],
    ["cost_per_lead", "CPL", "readonly"],
    ["cpm", "CPM", "number"],
    ["connected_number", "Connected", "readonly"],
    ["relevant", "Relevant", "readonly"],
    ["not_connected", "Not Conn", "readonly"],
    ["not_relevant", "Not Rel", "readonly"],
    ["not_available", "N/A", "readonly"],
    ["scheduled_store_visit", "Sched", "readonly"],
    ["store_visit", "Visit", "readonly"],
  ];
  // remove client_id for client-role
  const cols = IS_CLIENT
    ? allCols.filter((c) => c[0] !== "client_id")
    : allCols;

  // ─── 4) DOM references ────────────────────────────────────────────────────
  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");
  const $add = $("#lcm-add-row-campaign");
  const $filterClient = $("#lcm-filter-client");
  const $filterMonth = $("#lcm-filter-month-camp");
  const $filterLocation = $("#lcm-filter-location-camp");
  const $filterStore = $("#lcm-filter-store-camp");
  const $filterConnected = $("#lcm-filter-connected-camp");
  const $filterDateFrom = $("#lcm-filter-date-from");
  const $filterDateTo = $("#lcm-filter-date-to");
  $thead.html(
    "<tr>" +
      cols
        .map((c) => `<th data-sort="${c[0]}" class="lcm-sortable">${c[1]}</th>`)
        .join("") +
      "</tr>"
  );

  // ─── 5) Filter state ─────────────────────────────────────────────────────
  let filterClientVal = IS_CLIENT ? CLIENT_ID : "";
  let filterMonthVal = "";
  let filterLocationVal = "";
  let filterStoreVal = "";
  let filterConnVal = "";
  let filterDateFrom = "";
  let filterDateTo = "";

  // ─── 6) Helpers ──────────────────────────────────────────────────────────
  const opts = (arr, cur = "") =>
    "<option value=''></option>" +
    arr
      .map((o) => {
        const v = Array.isArray(o) ? o[0] : o,
          t = Array.isArray(o) ? o[1] : o;
        return `<option value="${v}"${
          v == cur ? " selected" : ""
        }>${t}</option>`;
      })
      .join("");

  const collect = ($tr) => {
    const data = {};
    $tr.find("[data-name]").each(function () {
      data[this.dataset.name] = $(this).val();
    });
    return data;
  };

  function setFilterActive(groupId, on) {
    $(`#${groupId}`).toggleClass("filter-active", on);
  }

  function renderPager(totalPages) {
    const first = 1,
      last = totalPages;
    let html = "";

    // helper to add button
    const addBtn = (num, lbl, icon, disabled = false, active = false) => {
      const cls = [
        "btn btn-sm",
        disabled ? "btn-outline-secondary disabled" : "btn-outline-secondary",
        active ? "active" : "",
      ].join(" ");
      html += `<button class="${cls}" data-p="${num}">
                 ${icon ? `<i class="${icon}"></i>` : lbl}
               </button>`;
    };

    // First / Prev
    addBtn(first, "First", "bi bi-chevron-double-left", page === first);
    addBtn(page - 1, "Prev", "bi bi-chevron-left", page === first);

    // numeric window
    const delta = 2;
    let left = Math.max(page - delta, first + 1);
    let right = Math.min(page + delta, last - 1);

    if (page - first <= delta) left = first + 1;
    if (last - page <= delta) right = last - 1;

    addBtn(1, "1", "", false, page === 1);
    if (left > first + 1)
      html += `<span class="btn btn-sm btn-outline-secondary disabled">…</span>`;
    for (let p = left; p <= right; p++) {
      addBtn(p, String(p), "", false, page === p);
    }
    if (right < last - 1)
      html += `<span class="btn btn-sm btn-outline-secondary disabled">…</span>`;
    if (last > 1) addBtn(last, String(last), "", false, page === last);

    // Next / Last
    addBtn(page + 1, "Next", "bi bi-chevron-right", page === last);
    addBtn(last, "Last", "bi bi-chevron-double-right", page === last);

    $pager
      .html(html)
      .find("button")
      .off("click")
      .on("click", (e) => {
        const p = +e.currentTarget.dataset.p;
        if (p >= first && p <= last && p !== page) load(p);
      });
  }

  function rowHtml(r = {}) {
    const saved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      saved ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, label, typ, opt]) => {
      const v = r[f] || "",
        d = saved ? " disabled" : "";
      if (typ === "action") {
        const postID = r.post_id || r.id;
        html += saved
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil-fill"></i></button>
               <button class="btn btn-danger btn-sm del-camp me-1" data-id="${r.id}"><i class="bi bi-trash-fill"></i></button>
               <a class="btn btn-info btn-sm" href="/campaign-detail?campaign_id=${postID}">View</a>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-camp me-1"><i class="bi bi-check-circle-fill"></i></button>
               <button class="btn btn-warning btn-sm cancel-draft me-1"><i class="bi bi-x-lg"></i></button>
             </td>`;
      } else if (typ === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${d}>${opts(
          opt,
          v
        )}</select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="date" class="form-control form-control-sm" data-name="campaign_date" value="${v}"${d}></td>`;
      } else if (typ === "number") {
        html += `<td><input type="number" step="any" class="form-control form-control-sm" data-name="${f}" value="${v}"${d}></td>`;
      } else if (typ === "readonly") {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}" disabled></td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}"${d}></td>`;
      }
    });
    return html + "</tr>";
  }

  // ─── 7) Data fetching ────────────────────────────────────────────────────
  function fetchPage(p = 1) {
    const params = {
      action: "lcm_get_campaigns_json",
      nonce: LCM.nonce,
      page: p,
      per_page: PER_PAGE,
      client_id: filterClientVal || undefined,
      month: filterMonthVal,
      location: filterLocationVal,
      store_visit: filterStoreVal,
      has_connected: filterConnVal,
      date_from: filterDateFrom,
      date_to: filterDateTo,
    };
    return new Promise((resolve) => {
      $.post(
        LCM.ajax_url,
        params,
        (res) => {
          let rows = [],
            total = 0;
          // if WP returned a success/data envelope
          if (res && res.success === true && res.data) {
            rows = res.data.rows || [];
            total = res.data.total || 0;
          }
          // otherwise fall back to the raw { rows, total } shape
          else if (res && Array.isArray(res.rows)) {
            rows = res.rows;
            total = res.total || 0;
          }
          resolve({ page: p, rows, total });
        },
        "json"
      );
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
    $(`#${tableId} th`)
      .removeClass("lcm-sort-asc lcm-sort-desc lcm-sort-active")
      .find(".lcm-clear-sort")
      .remove();

    const $th = $(`#${tableId} th[data-sort="${col}"]`);
    $th.addClass(
      "lcm-sort-active " + (dir === "asc" ? "lcm-sort-asc" : "lcm-sort-desc")
    );

    // Add clear icon
    if (!$th.find(".lcm-clear-sort").length) {
      $th.append(
        `<span class="lcm-clear-sort ms-1" title="Clear sort" style="cursor:pointer">×</span>`
      );
    }
  }
  $thead.on("click", "th.lcm-sortable", function () {
    const col = $(this).data("sort");

    if (!col) return;

    if (currentSortCol === col) {
      currentSortDir = currentSortDir === "asc" ? "desc" : "asc";
    } else {
      currentSortCol = col;
      currentSortDir = "asc";
    }

    // Sort and re-render table from cached data
    const rows = cachedPages[page] || [];
    const sorted = [...rows].sort(sortBy(currentSortCol, currentSortDir));
    $tbody.html(sorted.map(rowHtml).join(""));
    applySortingIcons("lcm-campaign-table", currentSortCol, currentSortDir);
  });

  $thead.on("click", ".lcm-clear-sort", function (e) {
    e.stopPropagation(); // prevent triggering sort again
    currentSortCol = "";
    currentSortDir = "asc";
    $thead.find("th").removeClass("lcm-sort-asc lcm-sort-desc lcm-sort-active");
    $(this).remove();

    // Reload from cache (unsorted)
    const rows = cachedPages[page] || [];
    $tbody.html(rows.map(rowHtml).join(""));
  });

  function load(p = 1) {
    showPreloader();
    return fetchPage(p).then(({ page: pg, rows, total }) => {
      page = pg;
      lastTotalPages = Math.max(1, Math.ceil(total / PER_PAGE));
      cachedPages[page] = rows;
      $tbody.html(rows.map(rowHtml).join(""));
      renderPager(lastTotalPages);
      LCM_initFlatpickr($tbody);
      hidePreloader();
      return { page, rows, total };
    });
  }

  function prefetchAllPages() {
    for (let p = 2; p <= lastTotalPages; p++) {
      setTimeout(
        () => fetchPage(p).then((d) => (cachedPages[d.page] = d.rows)),
        (p - 1) * 500
      );
    }
  }

  // ─── 8) Pager click handler ──────────────────────────────────────────────
  $pager.on("click", "button", (e) => {
    const p = +e.currentTarget.dataset.p;
    if (cachedPages[p]) {
      page = p;
      $tbody.html(cachedPages[p].map(rowHtml).join(""));
      $pager.find("button.active").removeClass("active");
      $(e.currentTarget).addClass("active");
    } else {
      load(p);
    }
  });
// 1) After your filter‐init code, append export button:
$('#lcm-filter-date-to').after(
  ' <button class="btn btn-sm btn-outline-secondary export-csv-campaigns">Export CSV</button>'
);

// 2) Delegate click—include nonce
$(document).on('click', '.export-csv-campaigns', () => {
  window.location = `${LCM.ajax_url}?action=lcm_export_csv&type=campaigns&nonce=${LCM.nonce}`;
});

  // ─── 9) Filters & CRUD bindings ─────────────────────────────────────────
  $filterClient.on("change", function () {
    filterClientVal = this.value;
    load(1);
  });
  $filterMonth.on("change", function () {
    filterMonthVal = this.value;
    setFilterActive("filter-month-group", !!filterMonthVal);
    load(1);
  });
  $filterLocation.on("input", function () {
    filterLocationVal = this.value.trim();
    setFilterActive("filter-location-group", !!filterLocationVal);
    load(1);
  });
  $filterStore.on("change", function () {
    filterStoreVal = this.value;
    setFilterActive("filter-store-group", !!filterStoreVal);
    load(1);
  });
  $filterConnected.on("change", function () {
    filterConnVal = this.value;
    setFilterActive("filter-connected-group", !!filterConnVal);
    load(1);
  });

  $filterDateFrom.on("change", function () {
    filterDateFrom = this.value;
    setFilterActive("filter-date-group", !!(filterDateFrom || filterDateTo));
    load(1);
  });

  $filterDateTo.on("change", function () {
    filterDateTo = this.value;
    setFilterActive("filter-date-group", !!(filterDateFrom || filterDateTo));
    load(1);
  });
  $(".clear-filter[data-filter=date]").on("click", function () {
    filterDateFrom = filterDateTo = "";
    $filterDateFrom.val("");
    $filterDateTo.val("");
    setFilterActive("filter-date-group", false);
    load(1);
  });

  $(".clear-filter").on("click", function () {
    const f = $(this).data("filter");
    switch (f) {
      case "month":
        filterMonthVal = "";
        $filterMonth.val("");
        setFilterActive("filter-month-group", false);
        break;
      case "location":
        filterLocationVal = "";
        $filterLocation.val("");
        setFilterActive("filter-location-group", false);
        break;
      case "store":
        filterStoreVal = "";
        $filterStore.val("");
        setFilterActive("filter-store-group", false);
        break;
      case "connected":
        filterConnVal = "";
        $filterConnected.val("");
        setFilterActive("filter-connected-group", false);
        break;
    }
    load(1);
  });
  flatpickr("#lcm-filter-campaign-date", {
    dateFormat: "Y-m-d",
    allowInput: true,
  });
  flatpickr($filterDateFrom[0], { dateFormat: "Y-m-d", allowInput: true });
  flatpickr($filterDateTo[0], { dateFormat: "Y-m-d", allowInput: true });
  // Add draft
  $add.on("click", () => {
    const d = {};
    cols.forEach((c) => (d[c[0]] = ""));
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(d));
    LCM_initFlatpickr($tbody.find("tr").first());
  });

  // Edit mode
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr").addClass("lcm-editing");
    $tr
      .find("input:not(.lcm-readonly), select:not(.lcm-readonly)")
      .prop("disabled", false);
    $(this)
      .removeClass("edit-row btn-secondary")
      .addClass("save-edit btn-success")
      .html('<i class="bi bi-check-circle-fill"></i>')
      .after(
        '<button class="btn btn-warning btn-sm cancel-edit ms-1"><i class="bi bi-x-lg"></i></button>'
      );
    LCM_initFlatpickr($tr);
  });
  $tbody.on("click", ".cancel-edit", () => load(page));
  $tbody.on("click", ".cancel-draft", function () {
    $(this).closest("tr").remove();
  });

  // Save draft
  $tbody.on("click", ".save-camp", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    if (IS_CLIENT) data.client_id = CLIENT_ID;
    if (!data.adset) {
      alert("Adset required");
      return;
    }
    data.action = "lcm_create_campaign";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // Save edit
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    data.id = $tr.data("id");
    data.action = "lcm_update_campaign";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // Delete
  let delId = 0;
  const modal = new bootstrap.Modal("#lcmDelModal");
  $tbody.on("click", ".del-camp", function () {
    delId = $(this).data("id");
    modal.show();
  });
  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      {
        action: "lcm_delete_campaign",
        nonce: LCM.nonce,
        id: delId,
      },
      (res) => {
        const p = Math.max(1, Math.ceil(res.data.total / PER_PAGE));
        if (page > p) page = p;
        load(page);
        modal.hide();
      },
      "json"
    );
  });
  // keyboard shortcuts: Enter = save, Escape = cancel
  $tbody.on("keydown", "input, select", function (e) {
    const $tr = $(this).closest("tr");
    if (e.key === "Enter") {
      // if it’s a draft
      if (!$tr.data("id")) {
        $tr.find(".save-row").trigger("click");
      }
      // if it’s an edit
      else if ($tr.hasClass("lcm-editing")) {
        $tr.find(".save-edit").trigger("click");
      }
    } else if (e.key === "Escape") {
      if ($tr.hasClass("lcm-editing")) {
        $tr.find(".cancel-edit").trigger("click");
      } else {
        $tr.find(".cancel-draft").trigger("click");
      }
    }
  });
  function setFilterActive(groupId, isActive) {
    const $g = $("#" + groupId);
    $g.toggleClass("filter-active", isActive);
  }

  showPreloader();
  load(1).then(() => setTimeout(prefetchAllPages, 1000));
});
