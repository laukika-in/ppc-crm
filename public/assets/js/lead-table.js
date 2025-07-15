jQuery(function ($) {
  // ─── 1) Preloader overlay ─────────────────────────────────────────────────
  const $preloader = $("#lcm-preloader");
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
  const ADSETS_BY_CLIENT = LCM.adsets_by_client;
  const ADNAMES_BY_CLIENT = LCM.adnames_by_client;

  let cachedPages = {}; // store fetched rows per page
  let lastTotalPages = 1;
  // ─── 3) Column definitions ────────────────────────────────────────────────
  const cols = [
    ["_action", "Action", "action"],
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
    ["lead_title", "Lead Title", "text"],
    ["source", "Source", "select", ["Google", "Meta"]],
    ["ad_name", "Campaign Name", "select", []],
    ["adset", "Adset", "select", []],
    ["uid", "UID", "text"],
    ["lead_date", "Date", "date"],
    ["lead_time", "Time", "time"],
    [
      "day",
      "Day",
      "select",
      [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ],
    ],
    ["name", "Name", "text"],
    ["phone_number", "Phone", "text"],
    ["alt_number", "Alternative #", "text"],
    ["email", "Email", "text"],
    ["location", "Location", "text"],
    ["client_type", "Client Type", "select", ["New Client", "Existing Client"]],
    ["source_campaign", "Source of Campaign", "text"],
    ["targeting", "Targeting", "text"],
    ["budget", "Budget", "text"],
    ["product_interest", "Product Looking To Buy", "text"],
    [
      "occasion",
      "Occasion",
      "select",
      [
        "Anniversary",
        "Birthday",
        "Casual Occasion",
        "Engagement/Wedding",
        "Gifting",
        "Others",
        "N/A",
      ],
    ],
    ["attempt", "Attempt", "select", [1, 2, 3, 4, 5, 6]],
    [
      "attempt_type",
      "Attempt Type",
      "select",
      ["Connected:Not Relevant", "Connected:Relevant", "Not Connected"],
    ],
    [
      "attempt_status",
      "Attempt Status",
      "select",
      [
        "Call Rescheduled",
        "Just browsing",
        "Not Interested",
        "Ringing / No Response",
        "Store Visit Scheduled",
        "Wrong Number / Invalid Number",
      ],
    ],
    ["store_visit_status", "Store Visit", "select", ["Show", "No Show"]],
    ["remarks", "Remarks", "text"],
  ];

  // ─── 4) DOM references ────────────────────────────────────────────────────
  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const $filter = $("#lcm-filter-client");
  const $filterDateFrom = $("#lcm-filter-date-from");
  const $filterDateTo = $("#lcm-filter-date-to");
  const $filterAdName = $("#lcm-filter-adname");
  const $filterAdset = $("#lcm-filter-adset");
  const $filterDay = $("#lcm-filter-day");
  const $filterClientType = $("#lcm-filter-client-type");
  const $filterSource = $("#lcm-filter-source");
  const $filterAttemptType = $("#lcm-filter-attempt-type");
  const $filterAttemptStatus = $("#lcm-filter-attempt-status");
  const $filterStoreVisit = $("#lcm-filter-store-visit-status");
  const $filterOccasion = $("#lcm-filter-occasion");
  const $filterText = $("#lcm-filter-text");
  const $filterBudget = $("#lcm-filter-budget");
  const $filterProduct = $("#lcm-filter-product");
  const $filterCity = $("#lcm-filter-city");

  // ─── 5) Filter state ─────────────────────────────────────────────────────
  let page = 1;
  let filterClient = IS_CLIENT ? CLIENT_ID : "";
  let filterDateFrom = "";
  let filterDateTo = "";
  let filterAdNameVal = "";
  let filterAdsetVal = "";
  let filterDayVal = "";
  let filterClientTypeVal = "";
  let filterSourceVal = "";
  let filterAttemptTypeVal = "";
  let filterAttemptStatusVal = "";
  let filterStoreVal = "";
  let filterOccasionVal = "";
  let filterTextVal = "";
  let filterBudgetVal = "";
  let filterProductVal = "";
  let filterCityVal = "";

  // ─── 6) Helpers: init select2, opts builder, collect row data ─────────────
  function initSearchable($scope) {
    $scope
      .find('select[data-name="adset"], select[data-name="ad_name"]')
      .select2({ width: "100%" });
  }

  const opts = (arr, cur = "") =>
    '<option value=""></option>' +
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

  function toggleDeps($tr) {
    const a = $tr.find("[data-name=attempt]").val();
    const t = $tr.find("[data-name=attempt_type]").val();
    const s = $tr.find("[data-name=attempt_status]").val();
    $tr.find("[data-name=attempt_type]").prop("disabled", !a);
    $tr.find("[data-name=attempt_status]").prop("disabled", !t);
    $tr
      .find("[data-name=store_visit_status]")
      .prop("disabled", s !== "Store Visit Scheduled");
  }

  function renderPager(total) {
    const maxButtons = 5;
    const delta = 2;
    const firstPage = 1;
    const lastPage = total;
    let html = "";

    // Helper to append a button
    const addBtn = (
      pageNum,
      label,
      iconClass,
      disabled = false,
      isActive = false
    ) => {
      const cls = [
        "btn",
        "btn-sm",
        disabled ? "btn-outline-secondary disabled" : "btn-outline-secondary",
        isActive ? "active" : "",
      ].join(" ");
      html += `<button class="${cls}" data-p="${pageNum}">
               ${iconClass ? `<i class="${iconClass}"></i>` : label}
             </button>`;
    };

    // First & Prev
    addBtn(firstPage, "First", "bi bi-chevron-double-left", page === firstPage);
    addBtn(page - 1, "Prev", "bi bi-chevron-left", page === firstPage);

    // Page numbers w/ ellipses
    let left = Math.max(page - delta, firstPage + 1);
    let right = Math.min(page + delta, lastPage - 1);

    // ensure we have enough buttons if we're near the edges
    if (page - firstPage <= delta) {
      right = Math.min(firstPage + maxButtons, lastPage - 1);
    }
    if (lastPage - page <= delta) {
      left = Math.max(lastPage - maxButtons, firstPage + 1);
    }

    // 1 always
    addBtn(1, "1", "", false, page === 1);

    if (left > firstPage + 1) {
      html +=
        '<span class="btn btn-sm btn-outline-secondary disabled">…</span>';
    }

    // middle pages
    for (let p = left; p <= right; p++) {
      addBtn(p, String(p), "", false, page === p);
    }

    if (right < lastPage - 1) {
      html +=
        '<span class="btn btn-sm btn-outline-secondary disabled">…</span>';
    }

    // last always
    if (lastPage > 1) {
      addBtn(lastPage, String(lastPage), "", false, page === lastPage);
    }

    // Next & Last
    addBtn(page + 1, "Next", "bi bi-chevron-right", page === lastPage);
    addBtn(lastPage, "Last", "bi bi-chevron-double-right", page === lastPage);

    // render
    $pager.html(html);

    // re-bind click
    $pager
      .find("button")
      .off("click")
      .on("click", (e) => {
        const p = +e.currentTarget.dataset.p;
        if (p >= firstPage && p <= lastPage && p !== page) {
          load(p);
        }
      });
  }

  function rowHtml(r = {}) {
    const saved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      saved ? "" : ' class="table-warning"'
    }>`;
    cols.forEach(([f, label, typ, opt]) => {
      const val = r[f] || "",
        dis = saved ? " disabled" : "";
      if (typ === "action") {
        html += saved
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil-fill"></i></button>
               <button class="btn btn-danger btn-sm del-row" data-id="${r.id}"><i class="bi bi-trash-fill"></i></button>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1"><i class="bi bi-check-circle-fill"></i></button>
               <button class="btn btn-warning btn-sm cancel-draft"><i class="bi bi-x-lg"></i></button>
             </td>`;
      } else if (typ === "select") {
        let choices = opt;
        if (f === "adset")
          choices = ADSETS_BY_CLIENT[IS_CLIENT ? CLIENT_ID : r.client_id] || [];
        if (f === "ad_name")
          choices =
            ADNAMES_BY_CLIENT[IS_CLIENT ? CLIENT_ID : r.client_id] || [];
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}>${opts(
          choices,
          val
        )}</select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="date" class="form-control form-control-sm flatpickr-date" data-name="lead_date" value="${val}"${dis}></td>`;
      } else if (typ === "time") {
        html += `<td><input type="text" class="form-control form-control-sm flatpickr-time" data-name="lead_time" value="${val}"${dis}></td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${val}"${dis}></td>`;
      }
    });
    html += "</tr>";
    return html;
  }

  // ─── 7) Data fetching ────────────────────────────────────────────────────
  function fetchPage(p = 1) {
    const q = {
      action: "lcm_get_leads_json",
      nonce: LCM.nonce,
      page: p,
      per_page: PER_PAGE,
      client_id: filterClient,
      date_from: filterDateFrom,
      date_to: filterDateTo,
      ad_name: filterAdNameVal,
      adset: filterAdsetVal,
      day: filterDayVal,
      client_type: filterClientTypeVal,
      source: filterSourceVal,
      attempt_type: filterAttemptTypeVal,
      attempt_status: filterAttemptStatusVal,
      store_visit_status: filterStoreVal,
      occasion: filterOccasionVal,
      search: filterTextVal,
      budget: filterBudgetVal,
      product_interest: filterProductVal,
      city: filterCityVal,
    };

    return new Promise((resolve) => {
      $.post(
        LCM.ajax_url,
        q,
        (res) => {
          let rows, total;
          if (res && res.success === true && res.data) {
            rows = res.data.rows || [];
            total = res.data.total || 0;
          } else {
            rows = res.rows || [];
            total = res.total || 0;
          }
          resolve({ page: p, rows, total });
        },
        "json"
      );
    });
  }
  function load(p = 1) {
    showPreloader();
    return fetchPage(p).then((data) => {
      lastTotalPages = Math.max(1, Math.ceil(data.total / PER_PAGE));
      page = data.page;
      cachedPages[page] = data.rows;
      $tbody.html(data.rows.map(rowHtml).join(""));
      renderPager(lastTotalPages);
      initSearchable($tbody);
      LCM_initFlatpickr($tbody);
      hidePreloader();
      return data;
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
    const $ths = $(`#${tableId} th`);
    $ths
      .removeClass("lcm-sort-asc lcm-sort-desc")
      .find(".lcm-clear-sort")
      .remove();

    const $active = $ths.filter(`[data-sort="${col}"]`);
    const className = dir === "asc" ? "lcm-sort-asc" : "lcm-sort-desc";
    $active.addClass(className);

    // Add a clear sort icon (×)
    $active.append(
      `<span class="lcm-clear-sort" style="cursor:pointer; margin-left:5px;">&times;</span>`
    );
  }
  $("#lcm-lead-table").on("click", ".lcm-clear-sort", function (e) {
    e.stopPropagation(); // Don't trigger the column sort
    currentSort = { col: "", dir: "" };
    $(`#lcm-lead-table th`).removeClass("lcm-sort-asc lcm-sort-desc");
    $(this).remove();

    // Re-load default unsorted rows
    load(page);
  });
  let currentSort = { col: "date", dir: "desc" }; // default sort

  $("#lcm-lead-table").on("click", "th.lcm-sortable", function () {
    const col = $(this).data("sort");
    const dir =
      currentSort.col === col && currentSort.dir === "asc" ? "desc" : "asc";
    currentSort = { col, dir };

    if (cachedPages[page]) {
      cachedPages[page].sort(sortBy(col, dir));
      $tbody.html(cachedPages[page].map(rowHtml).join(""));
      initSearchable($tbody);
      LCM_initFlatpickr($tbody);
      applySortingIcons("lcm-lead-table", col, dir);
    }
  });

  function prefetchAllPages() {
    for (let p = 2; p <= lastTotalPages; p++) {
      // schedule each fetch 500ms apart
      setTimeout(() => {
        fetchPage(p).then((data) => {
          cachedPages[data.page] = data.rows;
        });
      }, (p - 1) * 500);
    }
  }
  $("#lcm-filter-city").after(
    ' <button class="btn btn-sm btn-outline-secondary export-csv-leads">Export CSV</button>'
  );

  // 2) Handle click with nonce
  $(".export-csv-leads").on("click", function () {
    const from = $("#filter-from").val();
    const to = $("#filter-to").val();
    const adName = $("#filter-adname").val();
    const adset = $("#filter-adset").val();
    const campaign = $("#filter-campaign").val();
    const clientId = $("#filter-client").val(); // visible only for admin

    const filters = {
      action: "lcm_export_csv",
      type: "leads",
    };

    if (from) filters.from = from;
    if (to) filters.to = to;
    if (adName) filters.ad_name = adName;
    if (adset) filters.adset = adset;
    if (campaign) filters.campaign_id = campaign;
    if (clientId) filters.client_id = clientId;

    const queryString = new URLSearchParams(filters).toString();
    const exportUrl = `${LCM.ajax_url}?${queryString}`;

    window.open(exportUrl, "_blank");
  });

  // ─── 8) Pager click handler ──────────────────────────────────────────────
  $pager.on("click", "button", (e) => {
    const p = +e.currentTarget.dataset.p;
    if (cachedPages[p]) {
      page = p;
      $tbody.html(cachedPages[p].map(rowHtml).join(""));
      $pager.find("button.active").removeClass("active");
      $(e.currentTarget).addClass("active");
    } else {
      showPreloader();
      load(p).then(() => hidePreloader());
    }
  });

  // 9) Filter & UI event bindings (unchanged) ──────────────────────────
  if (!IS_CLIENT) {
    $filter.on("change", function () {
      filterClient = this.value;
      load(1);
    });
  }
  $filterDateFrom.on("change", function () {
    filterDateFrom = this.value;
    load(1);
  });
  $filterDateTo.on("change", function () {
    filterDateTo = this.value;
    load(1);
  });
  $filterAdName.on("change", () => {
    filterAdNameVal = $filterAdName.val();
    load(1);
  });
  $filterAdset.on("change", () => {
    filterAdsetVal = $filterAdset.val();
    load(1);
  });
  $filterDay.on("change", () => {
    filterDayVal = $filterDay.val();
    load(1);
  });
  $filterClientType.on("change", () => {
    filterClientTypeVal = $filterClientType.val();
    load(1);
  });
  $filterSource.on("change", () => {
    filterSourceVal = $filterSource.val();
    load(1);
  });
  $filterAttemptType.on("change", () => {
    filterAttemptTypeVal = $filterAttemptType.val();
    load(1);
  });
  $filterAttemptStatus.on("change", () => {
    filterAttemptStatusVal = $filterAttemptStatus.val();
    load(1);
  });
  $filterStoreVisit.on("change", () => {
    filterStoreVal = $filterStoreVisit.val();
    load(1);
  });
  $filterOccasion.on("change", () => {
    filterOccasionVal = $filterOccasion.val();
    load(1);
  });
  $filterText.on("input", () => {
    filterTextVal = $filterText.val().trim();
    load(1);
  });
  $filterBudget.on("input", () => {
    filterBudgetVal = $filterBudget.val().trim();
    load(1);
  });
  $filterProduct.on("input", () => {
    filterProductVal = $filterProduct.val().trim();
    load(1);
  });
  $filterCity.on("input", () => {
    filterCityVal = $filterCity.val().trim();
    load(1);
  });
  flatpickr($filterDateFrom[0], { dateFormat: "Y-m-d", allowInput: true });
  flatpickr($filterDateTo[0], { dateFormat: "Y-m-d", allowInput: true });
  // Add draft
  $("#lcm-add-row-lead").on("click", () => {
    const d = {};
    cols.forEach((c) => (d[c[0]] = ""));
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(d));
    const $new = $tbody.find("tr").first();
    initSearchable($new);
    LCM_initFlatpickr($new);
  });

  // Whenever someone picks a Campaign Name, wipe out the Adset
  $tbody.on("change", "select[data-name=ad_name]", function () {
    const $tr = $(this).closest("tr");
    // only clear if they've actually selected something
    if ($(this).val()) {
      $tr.find("select[data-name=adset]").val("");
    }
  });

  // Conversely: if they pick an Adset, clear the Campaign Name
  $tbody.on("change", "select[data-name=adset]", function () {
    const $tr = $(this).closest("tr");
    if ($(this).val()) {
      $tr.find("select[data-name=ad_name]").val("");
    }
  });

  // When source changes, refresh ad_name & adset lists
  $tbody.on("change", "select[data-name=source]", function () {
    const $tr = $(this).closest("tr");
    const src = this.value;
    const cid = IS_CLIENT
      ? CLIENT_ID
      : $tr.find("select[data-name=client_id]").val() || "";
    if (!src) {
      $adName.prop("disabled", true).html(opts([], ""));
      $adSet.prop("disabled", true).html(opts([], ""));
      return;
    }
    // rebuild Campaign Name dropdown
    const $adName = $tr.find("select[data-name=ad_name]");
    const adNames = src === "Google" ? ADNAMES_BY_CLIENT[cid] || [] : [];
    $adName.html(opts(adNames, "")).prop("disabled", src !== "Google");

    // rebuild Adset dropdown
    const $adSet = $tr.find("select[data-name=adset]");
    const adSets = src === "Google" ? [] : ADSETS_BY_CLIENT[cid] || [];
    $adSet.html(opts(adSets, "")).prop("disabled", src === "Google");
    if (src === "Google") {
      // 2) Google → only Campaign Name (ad_name)
      $adSet.prop("disabled", true).html(opts([], ""));
      const adNames = ADNAMES_BY_CLIENT[cid] || [];
      $adName.prop("disabled", false).html(opts(adNames, ""));
    } else {
      // 3) Meta & others → only Adset
      $adName.prop("disabled", true).html(opts([], ""));
      const adSets = ADSETS_BY_CLIENT[cid] || [];
      $adSet.prop("disabled", false).html(opts(adSets, ""));
    }
  });

  // Row-click edit
  $tbody.on("click", "tr", function (e) {
    if ($(e.target).closest("button").length) return;
    const $tr = $(this);
    if (!$tr.data("id") || $tr.hasClass("lcm-editing")) return;
    $tr.find(".edit-row").trigger("click");
  });

  // Edit mode
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr").addClass("lcm-editing");
    $tr.find("input,select").prop("disabled", false);
    $(this)
      .removeClass("edit-row btn-secondary")
      .addClass("save-edit btn-success")
      .html('<i class="bi bi-check-circle-fill"></i>')
      .after(
        '<button class="btn btn-warning btn-sm cancel-edit ms-1"><i class="bi bi-x-lg"></i></button>'
      );
    // … enable inputs …
    initSearchable($tr);
    LCM_initFlatpickr($tr);
    toggleDeps($tr);
  });
  $tbody.on("click", ".cancel-edit", () => load(page));
  $tbody.on("click", ".cancel-draft", function () {
    $(this).closest("tr").remove();
  });

  // Save draft
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    if (IS_CLIENT) data.client_id = CLIENT_ID;
    if (!data.uid) {
      alert("UID is required");
      return;
    }
    if (data.source === "Google" && !data.ad_name) {
      alert("Campaign Name is required for Google leads");
      return;
    }
    if (data.source == "Meta" && !data.adset) {
      alert("Adset is required for Meta (and other) leads");
      return;
    }
    data.action = "lcm_create_lead";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // Save edit
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    data.id = $tr.data("id");
    data.action = "lcm_update_lead";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // Dependencies
  $tbody.on(
    "change",
    "select[data-name=attempt], select[data-name=attempt_type], select[data-name=attempt_status]",
    function () {
      toggleDeps($(this).closest("tr"));
    }
  );

  // Date → Day
  $tbody.on("change", ".flatpickr-date", function () {
    const d = new Date(this.value + "T12:00:00");
    if (!isNaN(d)) {
      const day = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ][d.getDay()];
      $(this).closest("tr").find("[data-name=day]").val(day);
    }
  });

  // Delete
  let delId = 0;
  const modal = new bootstrap.Modal("#lcmDelModal");
  $tbody.on("click", ".del-row", function () {
    delId = $(this).data("id") || 0;
    if (!delId) $(this).closest("tr").remove();
    else modal.show();
  });
  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      {
        action: "lcm_delete_lead",
        nonce: LCM.nonce,
        id: delId,
      },
      (res) => {
        const pages = Math.max(1, Math.ceil(res.data.total / PER_PAGE));
        if (page > pages) page = pages;
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

  // Whenever PPC/Admin selects a Client, refresh both Adset and Ad Name dropdowns
  $tbody.on("change", "select[data-name=client_id]", function () {
    const $tr = $(this).closest("tr");
    const cid = $(this).val() || "";

    // 1) rebuild Adset list
    const adsetChoices = ADSETS_BY_CLIENT[cid] || [];
    $tr.find("select[data-name=adset]").html(opts(ADSETS_BY_CLIENT[cid], ""));

    // 2) rebuild Ad Name (Campaign Name) list
    const adnameChoices = ADNAMES_BY_CLIENT[cid] || [];
    $tr
      .find("select[data-name=ad_name]")
      .html(opts(ADNAMES_BY_CLIENT[cid], ""));
  });
  function toggleFilterHighlight(group, value) {
    const $grp = $(`#filter-${group}-group`);
    if (value) $grp.addClass("filter-active");
    else $grp.removeClass("filter-active");
  }
  // Clear-button clicks
  $(".lcm-filters").on("click", ".clear-filter", function () {
    const filter = this.dataset.filter;
    switch (filter) {
      case "attempt_type":
        filterAttemptTypeVal = "";
        $filterAttemptType.val("");
        break;
      case "attempt_status":
        filterAttemptStatusVal = "";
        $filterAttemptStatus.val("");
        break;
      // add other cases if you want clear for other filters
    }
    toggleFilterHighlight(filter.replace("_", "-"), ""); // reset highlight
    load(1);
  });
  // Split it into two calls:
  $("#lcm-filter-adname").select2({
    placeholder: "All Campaigns",
    allowClear: true,
    width: "200px",
  });

  $("#lcm-filter-adset").select2({
    placeholder: "All Adsets",
    allowClear: true,
    width: "200px",
  });
  const urlParams = new URLSearchParams(window.location.search);

  // dates
  const df = urlParams.get("date_from");
  const dt = urlParams.get("date_to");
  if (df) {
    filterDateFrom = df;
    $filterDateFrom.val(df);
  }
  if (dt) {
    filterDateTo = dt;
    $filterDateTo.val(dt);
  }
  // campaign or adset
  const adNameId = urlParams.get("ad_name");
  const adsetId = urlParams.get("adset");
  if (adNameId) {
    filterAdNameVal = adNameId;
    $filterAdName.val(adNameId).trigger("change");
  }
  if (adsetId) {
    filterAdsetVal = adsetId;
    $filterAdset.val(adsetId);
  }
  $("#lcm-filter-adset, #lcm-filter-adname").trigger("change");

  if (adNameId && !adsetId) {
    $("#lcm-filter-adname").val(adNameId).trigger("change");

    // Wait for adset dropdown to populate
    setTimeout(() => {
      const $adsetDropdown = $("#lcm-filter-adset");
      const firstAdset = $adsetDropdown.find("option").eq(1).val(); // skip "All Adsets"
      if (firstAdset) {
        $adsetDropdown.val(firstAdset).trigger("change");
      }
    }, 500);
  }
  $("#export-leads").on("click", function () {
    const from = $("#filter-from").val();
    const to = $("#filter-to").val();
    const campaign = $("#filter-campaign").val();
    const client = LCM.is_admin ? $("#filter-client").val() : ""; // only if admin

    const params = new URLSearchParams({
      action: "lcm_export_csv",
      type: "leads",
      from,
      to,
      campaign_id: campaign,
      client_id: client,
    });

    const url = LCM.ajax_url + "?" + params.toString();
    window.open(url, "_blank");
  });

  // Render header
  $thead.html(
    "<tr>" +
      cols
        .map((c) => `<th class="lcm-sortable" data-sort="${c[0]}">${c[1]}</th>`)
        .join("") +
      "</tr>"
  );
  showPreloader();
  load(1).then(() => {
    hidePreloader();
    setTimeout(prefetchAllPages, 1000);
  });
});
