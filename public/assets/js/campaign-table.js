jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;

  // Define all columns
  const allCols = [
    ["_action", "Action", "action"],
    ["client_id", "Client", "select", LCM.clients],
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

  // Remove client_id column for Clients
  const cols = IS_CLIENT
    ? allCols.filter((c) => c[0] !== "client_id")
    : allCols;

  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");
  const $add = $("#lcm-add-row-campaign");

  let page = 1;

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
      if (typ === "action") {
        if (!saved && !IS_CLIENT) {
          html += `<td class="text-center">
                   <button class="btn btn-success btn-sm save-camp me-1"><i class="bi bi-save"></i></button>
                   <button class="btn btn-warning btn-sm cancel-draft"><i class="bi bi-x-lg"></i></button>
                 </td>`;
        } else if (saved && !IS_CLIENT) {
          html += `<td class="text-center">
                   <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil"></i></button>
                   <button class="btn btn-danger btn-sm del-camp" data-id="${r.id}"><i class="bi bi-trash"></i></button>
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
    };
    if (IS_CLIENT) params.client_id = CLIENT_ID;
    $.getJSON(LCM.ajax_url, params, (res) => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      renderPager(res.total);
    });
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

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

  // Init
  load(1);
});
