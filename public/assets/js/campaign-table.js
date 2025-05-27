jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;

  const cols = [
    ["_action", "Action", "action"],
    ["client_id", "Client", IS_CLIENT ? "hidden" : "select", LCM.clients],
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
  const $addBtn = $("#lcm-add-row-campaign");

  let page = 1;

  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

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

  function rowHtml(r = {}) {
    const isSaved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      isSaved ? "" : ' class="table-warning"'
    }>`;

    cols.forEach(([f, _l, type, opt]) => {
      const v = r[f] || "";
      const dis = isSaved ? " disabled" : "";

      if (type === "action") {
        html +=
          isSaved && !IS_CLIENT
            ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil"></i></button>
               <button class="btn btn-danger btn-sm del-camp" data-id="${r.id}"><i class="bi bi-trash"></i></button>
             </td>`
            : !isSaved && !IS_CLIENT
            ? `<td class="text-center">
               <button class="btn btn-success btn-sm save-camp me-1"><i class="bi bi-save"></i></button>
               <button class="btn btn-warning btn-sm cancel-draft"><i class="bi bi-x-lg"></i></button>
             </td>`
            : `<td></td>`;
      } else if (type === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}>${opts(
          opt,
          v
        )}</select></td>`;
      } else if (type === "date") {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (type === "number") {
        html += `<td><input type="number" step="any" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (type === "readonly") {
        html += `<td>${v}</td>`;
      } else if (type === "hidden") {
        html += `<td><input type="hidden" data-name="${f}" value="${v}"></td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      }
    });

    html += "</tr>";
    return html;
  }

  function renderPager(total) {
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    $pager.html(
      Array.from({ length: pages }, (_, i) => {
        const n = i + 1;
        return `<button class="btn btn-outline-secondary ${
          n === page ? "active" : ""
        }" data-p="${n}">${n}</button>`;
      }).join("")
    );
  }

  function load(p = 1) {
    const q = {
      action: "lcm_get_campaigns_json",
      nonce: LCM.nonce,
      page: p,
      per_page: PER_PAGE,
    };
    if (IS_CLIENT) q.client_id = CLIENT_ID;

    $.getJSON(LCM.ajax_url, q, (res) => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      renderPager(res.total);
    });
  }

  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  if (IS_CLIENT) $addBtn.hide();

  $addBtn.on("click", () => {
    const d = {};
    cols.forEach(([f]) => {
      d[f] = "";
    });
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(d));
    LCM_initFlatpickr($tbody.find("tr").first());
  });

  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr").addClass("lcm-editing");
    $tr.find("input,select").prop("disabled", false);
    $(this)
      .removeClass("edit-row btn-secondary")
      .addClass("save-edit btn-success")
      .html('<i class="bi bi-save"></i>')
      .after(
        '<button class="btn btn-warning btn-sm cancel-edit ms-1"><i class="bi bi-x-lg"></i></button>'
      );
    LCM_initFlatpickr($tr);
  });

  $tbody.on("click", ".cancel-edit", () => load(page));
  $tbody.on("click", ".cancel-draft", function () {
    $(this).closest("tr").remove();
  });

  $tbody.on("click", ".save-camp", function () {
    const $tr = $(this).closest("tr");
    const d = collect($tr);
    if (IS_CLIENT) d.client_id = CLIENT_ID;

    if (!d.adset) {
      alert("Adset required");
      return;
    }

    d.action = "lcm_create_campaign";
    d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const d = collect($tr);
    d.id = $tr.data("id");
    d.action = "lcm_update_campaign";
    d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

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
        const pages = Math.max(1, Math.ceil(res.data.total / PER_PAGE));
        if (page > pages) page = pages;
        load(page);
        modal.hide();
      },
      "json"
    );
  });

  load(1);
});
