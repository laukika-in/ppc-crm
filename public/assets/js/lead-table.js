jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;
  const ADSETS_BY_CLIENT = LCM.adsets_by_client;
  const ADNAMES_BY_CLIENT = LCM.adnames_by_client;

  // Column definitions
  const cols = [
    ["_action", "Action", "action"],
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
    ["lead_title", "Lead Title", "text"],
    ["source", "Source", "select", ["Google", "Meta"]],
    ["ad_name", "Campaign Name", "select", []],
    ["adset", "Adset", "select", "select", []],
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

  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const $filter = $("#lcm-filter-client");
  let page = 1,
    filterClient = IS_CLIENT ? CLIENT_ID : "";
  const $filterDateFrom = $("#lcm-filter-date-from");
  const $filterDateTo = $("#lcm-filter-date-to");
  const $filterAdName = $("#lcm-filter-adname");
  const $filterAdset = $("#lcm-filter-adset");
  const $filterDay = $("#lcm-filter-day");
  const $filterClientType = $("#lcm-filter-client-type");
  const $filterSource = $("#lcm-filter-source");
  const $filterAttemptStatus = $("#lcm-filter-attempt-status");
  const $filterStoreVisit = $("#lcm-filter-store-visit-status");
  const $filterOccasion = $("#lcm-filter-occasion");
  const $filterText = $("#lcm-filter-text");
  const $filterBudget = $("#lcm-filter-budget");
  const $filterProduct = $("#lcm-filter-product");

  (filterDateFrom = ""),
    (filterDateTo = ""),
    (filterAdNameVal = ""),
    (filterAdsetVal = ""),
    (filterDayVal = ""),
    (filterClientTypeVal = ""),
    (filterSourceVal = ""),
    (filterAttemptVal = ""),
    (filterStoreVal = ""),
    (filterOccasionVal = ""),
    (filterTextVal = ""),
    (filterBudgetVal = ""),
    (filterProductVal = "");

  // on-change handlers (all reload page 1)
  $filterDateFrom.on("change", () => {
    filterDateFrom = $filterDateFrom.val();
    load(1);
  });
  $filterDateTo.on("change", () => {
    filterDateTo = $filterDateTo.val();
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
  $filterAttemptStatus.on("change", () => {
    filterAttemptVal = $filterAttemptStatus.val();
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

  // Render header
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

  // Build a row
  function rowHtml(r = {}) {
    const saved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      saved ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, _l, typ, opt]) => {
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
        if (f === "adset") {
          const cid = IS_CLIENT ? CLIENT_ID : r.client_id;
          choices = ADSETS_BY_CLIENT[cid] || [];
        } else if (f === "ad_name") {
          const cid = IS_CLIENT ? CLIENT_ID : r.client_id;
          choices = ADNAMES_BY_CLIENT[cid] || [];
        }

        html += `<td><select class="form-select form-select-sm"
                              data-name="${f}"${dis}>
                            ${opts(choices, val)}
                          </select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="date" class="form-control form-control-sm flatpickr-date"
                         data-name="lead_date" value="${val}"${dis}></td>`;
      } else if (typ === "time") {
        html += `<td><input type="text" class="form-control form-control-sm flatpickr-time"
                         data-name="lead_time" value="${val}"${dis}></td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm"
                         data-name="${f}" value="${val}"${dis}></td>`;
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
    const q = {
      action: "lcm_get_leads_json",
      nonce: LCM.nonce,
      page: p,
      per_page: PER_PAGE,
    };
    if (filterClient) q.client_id = filterClient;
    q.date_from = filterDateFrom;
    q.date_to = filterDateTo;
    q.ad_name = filterAdNameVal;
    q.adset = filterAdsetVal;
    q.day = filterDayVal;
    q.client_type = filterClientTypeVal;
    q.source = filterSourceVal;
    q.attempt_status = filterAttemptVal;
    q.store_visit_status = filterStoreVal;
    q.occasion = filterOccasionVal;
    q.search = filterTextVal;
    q.budget = filterBudgetVal;
    q.product_interest = filterProductVal;
    $.getJSON(LCM.ajax_url, q, (res) => {
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

  // Add draft
  $("#lcm-add-row-lead").on("click", () => {
    const d = {};
    cols.forEach((c) => (d[c[0]] = ""));
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(d));
    LCM_initFlatpickr($tbody.find("tr").first());
  });
  // Whenever Client is changed in a draft/edit row, refresh its Adset options:
  $tbody.on("change", "select[data-name=client_id]", function () {
    const $tr = $(this).closest("tr");
    const cid = $(this).val();
    const $ad = $tr.find("select[data-name=adset]");
    const optsHtml = opts(ADSETS_BY_CLIENT[cid] || [], "");
    $ad.html("<option value=''></option>" + optsHtml);
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

  // Initial load
  load(1);
});
