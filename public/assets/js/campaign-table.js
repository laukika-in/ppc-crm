/* ==============================================================
 *  CAMPAIGN DATA GRID
 * ==============================================================*/
jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const PER_PAGE = LCM.per_page;

  const cols = [
    ["_action", "Action", "action"],
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
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
    ["campaign_date", "Date", "date"],
    ["location", "Location", "text"],
    ["adset", "Adset", "text"],
    ["leads", "Leads", "number"],
    ["reach", "Reach", "number"],
    ["impressions", "Impr", "number"],
    ["cost_per_lead", "CPL", "number"],
    ["amount_spent", "Spent", "number"],
    ["cpm", "CPM", "number"],
    ["connected_number", "Connected", "readonly"],
    ["not_connected", "Not Conn", "readonly"],
    ["relevant", "Relevant", "readonly"],
    ["not_available", "N/A", "readonly"],
    ["scheduled_store_visit", "Sched Visit", "readonly"],
    ["store_visit", "Visit", "readonly"],
  ];

  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");

  let page = 1;

  // Header
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  // Helpers
  const opts = (arr, cur = "") =>
    "<option value=''></option>" +
    arr
      .map((o) => `<option ${o == cur ? "selected" : ""}>${o}</option>`)
      .join("");
  const collect = ($tr) => {
    const o = {};
    $tr.find("[data-name]").each(function () {
      o[this.dataset.name] = $(this).val();
    });
    return o;
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

      if (typ === "action") {
        html += saved
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-camp" data-id="${r.id}">🗑</button>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-camp me-1">💾</button>
               <button class="btn btn-warning btn-sm cancel-draft">✖</button>
             </td>`;
      } else if (typ === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}>${opts(
          opt,
          v
        )}</select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="date" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (typ === "number") {
        html += `<td><input type="number" step="any" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (typ === "readonly") {
        html += `<td>${v}</td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      }
    });

    return html + "</tr>";
  }

  // Pager & Load
  function renderPager(total) {
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    $pager.html(
      Array.from(
        { length: pages },
        (_, i) =>
          `<button class="btn btn-outline-secondary ${
            i + 1 === page ? "active" : ""
          }" data-p="${i + 1}">${i + 1}</button>`
      ).join("")
    );
  }

  function load(p = 1) {
    $.getJSON(
      LCM.ajax_url,
      {
        action: "lcm_get_campaigns_json",
        nonce: LCM.nonce,
        page: p,
        per_page: PER_PAGE,
      },
      (res) => {
        page = p;
        $tbody.html(res.rows.map(rowHtml).join(""));
        renderPager(res.total);
      }
    );
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  // Add draft
  $("#lcm-add-row-campaign").on("click", () => {
    $tbody.prepend(rowHtml({}));
  });

  // Row-click → Edit
  $tbody.on("click", "tr", function (e) {
    if ($(e.target).closest(".btn").length) return;
    const $tr = $(this);
    if (!$tr.data("id") || $tr.hasClass("lcm-editing")) return;
    $tr.find(".edit-row").trigger("click");
  });

  // Cancel draft
  $tbody.on("click", ".cancel-draft", function () {
    $(this).closest("tr").remove();
  });

  // Save draft
  $tbody.on("click", ".save-camp", function () {
    const data = collect($(this).closest("tr"));
    if (!data.adset) {
      alert("Adset required");
      return;
    }
    data.action = "lcm_create_campaign";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // Enter edit mode
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr").addClass("lcm-editing");
    $tr.find("input,select").prop("disabled", false);
    $(this)
      .removeClass("edit-row btn-secondary")
      .addClass("save-edit btn-success")
      .text("💾")
      .after(
        '<button class="btn btn-warning btn-sm cancel-edit ms-1">✖</button>'
      );
  });

  // Cancel edit
  $tbody.on("click", ".cancel-edit", () => load(page));

  // Save edit
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    data.id = $tr.data("id");
    data.action = "lcm_update_campaign";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // Live N/A calculation
  $tbody.on("input", "[data-name=leads]", function () {
    const $tr = $(this).closest("tr");
    const leads = +this.value || 0;
    const conn = +$tr.find("td").eq(13).text() || 0;
    const notc = +$tr.find("td").eq(14).text() || 0;
    const rel = +$tr.find("td").eq(15).text() || 0;
    $tr
      .find("td")
      .eq(16)
      .text(Math.max(0, leads - conn - notc - rel));
  });

  // Delete with modal
  let delId = 0;
  const modal = new bootstrap.Modal("#lcmDelModal");

  $tbody.on("click", ".del-camp", function () {
    delId = $(this).data("id");
    modal.show();
  });

  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      { action: "lcm_delete_campaign", nonce: LCM.nonce, id: delId },
      (res) => {
        const pages = Math.max(1, Math.ceil(res.data.total / PER_PAGE));
        if (page > pages) page = pages;
        load(page);
        modal.hide();
      },
      "json"
    );
  });

  // Init
  load(1);
});
