jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;

  // Define all columns
  const allCols = [
    ...(!IS_CLIENT ? [["_action", "Action", "action"]] : []),
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
    ["campaign_title", "Campaign Title", "text"],
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
    ["campaign_name", "Campaign Name", "text"],
    ["campaign_date", "Date", "date"],
    ["location", "Location", "text"],
    ["adset", "Adset", "text"],
    ["leads", "Leads", "number", "readonly"],
    ["reach", "Reach", "number"],
    ["impressions", "Impr", "number"],
    ["cost_per_lead", "CPL", "number"],
    ["amount_spent", "Spent", "number"],
    ["cpm", "CPM", "number"],
    ["connected_number", "Connected", "readonly"],
    ["relevant", "Relevant", "readonly"],
    ["not_connected", "Not Conn", "readonly"],
    ["not_relevant", "Not Relevant", "readonly"],
    ["not_available", "N/A", "readonly"],
    ["scheduled_store_visit", "Sched Visit", "readonly"],
    ["store_visit", "Visit", "readonly"],
  ];

  // Remove client_id column for Clients
  const cols = IS_CLIENT
    ? allCols.filter((c) => c[0] !== "client_id")
    : allCols;

  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");
  const $add = $("#lcm-add-row-campaign");
  const $filter = $("#lcm-filter-client");
  const $filterMonth = $("#lcm-filter-month-camp");
  const $filterLocation = $("#lcm-filter-location-camp");
  const $filterStore = $("#lcm-filter-store-camp");
  const $filterConnected = $("#lcm-filter-connected-camp");

  let filterMonth = "";
  let filterLocation = "";
  let filterStore = "";
  let filterConnected = "";
  let page = 1;
  filterClient = IS_CLIENT ? CLIENT_ID : "";

  $filterMonth.on("change", function () {
    filterMonth = this.value;
    setFilterActive("filter-month-group", !!filterMonth);
    load(1);
  });

  $filterLocation.on("input", function () {
    filterLocation = this.value.trim();
    setFilterActive("filter-location-group", !!filterLocation);
    load(1);
  });

  $filterStore.on("change", function () {
    filterStore = this.value;
    setFilterActive("filter-store-group", !!filterStore);
    load(1);
  });

  $filterConnected.on("change", function () {
    filterConnected = this.value;
    setFilterActive("filter-connected-group", !!filterConnected);
    load(1);
  });

  const $filterGroups = $(".lcm-filter-group");
  const $clearButtons = $(".clear-filter");

  // Header
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  // Helpers
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

  // Build row
  function rowHtml(r = {}) {
    const saved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      saved ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, _l, typ, opt]) => {
      const v = r[f] || "",
        dis = saved ? " disabled" : "";
      if (typ === "action" && !IS_CLIENT) {
        if (!saved && !IS_CLIENT) {
          html += `<td class="text-center">
                   <button class="btn btn-success btn-sm save-camp me-1"><i class="bi bi-check-circle-fill"></i></button>
                   <button class="btn btn-warning btn-sm cancel-draft"><i class="bi bi-x-lg"></i></button>
                 </td>`;
        } else if (saved && !IS_CLIENT) {
          html += `<td class="text-center">
                   <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil-fill"></i></button>
                   <button class="btn btn-danger btn-sm del-camp" data-id="${r.id}"><i class="bi bi-trash-fill"></i></button>
                 </td>`;
        } else {
          html += `<td></td>`; // clients get no actions
        }
      } else if (typ === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}>
                  ${opts(opt, v)}
                </select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="date" class="form-control form-control-sm"
                         data-name="campaign_date" value="${v}"${dis}></td>`;
      } else if (typ === "number") {
        html += `<td><input type="number" step="any" class="form-control form-control-sm"
                         data-name="${f}" value="${v}"${dis}></td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm"
                         data-name="${f}" value="${v}"${dis}></td>`;
      }
    });
    html += "</tr>";
    return html;
  }

  // Pager & Load
  function renderPager(total) {
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    $pager.html(
      Array.from({ length: pages }, (_, i) => {
        const n = i + 1;
        return `<button class="btn btn-outline-secondary ${
          n === page ? "active" : ""
        }"
                      data-p="${n}">${n}</button>`;
      }).join("")
    );
  }

  function load(p = 1) {
    const params = {
      action: "lcm_get_campaigns_json",
      nonce: LCM.nonce,
      page: p,
      per_page: PER_PAGE,
      month: filterMonth,
      location: filterLocation,
      store_visit: filterStore,
      has_connected: filterConnected,
    };
    if (filterClient) params.client_id = filterClient;
    $.getJSON(LCM.ajax_url, params, (res) => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      renderPager(res.total);
    });
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  // Filter for PPC/Admin
  if (!IS_CLIENT) {
    $filter.on("change", function () {
      filterClient = this.value;
      load(1);
    });
  }
  // Hide add for clients
  if (IS_CLIENT) $add.hide();

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
    $tr.find("input,select").prop("disabled", false);
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
  $clearButtons.on("click", function () {
    const f = $(this).data("filter"); // “month”, “location”, etc.
    switch (f) {
      case "month":
        filterMonth = "";
        $("#lcm-filter-month-camp").val("");
        setFilterActive("filter-month-group", false);
        break;
      case "location":
        filterLocation = "";
        $("#lcm-filter-location-camp").val("");
        setFilterActive("filter-location-group", false);
        break;
      case "store":
        filterStore = "";
        $("#lcm-filter-store-camp").val("");
        setFilterActive("filter-store-group", false);
        break;
      case "connected":
        filterConnected = "";
        $("#lcm-filter-connected-camp").val("");
        setFilterActive("filter-connected-group", false);
        break;
    }
    load(1);
  });

  // Init
  load(1);
});
