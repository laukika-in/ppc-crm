/* ==============================================================
 *  CAMPAIGN DATA GRID
 *  - Row-click to edit (zoom + shadow)
 *  - ✖ Cancel for drafts and edits
 *  - Add / Delete rows, Ajax pagination
 * ==============================================================*/
jQuery(function ($) {
  /* ---------- column schema -------------------------------------- */
  const cols = [
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

  /* ---------- DOM refs ------------------------------------------- */
  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");

  const PER_PAGE = LCM.per_page;
  let page = 1;

  /* ---------- header --------------------------------------------- */
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  /* ---------- helpers -------------------------------------------- */
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

  /* ---------- build row ------------------------------------------ */
  function rowHtml(r = {}) {
    const saved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      saved ? "" : ' class="table-warning"'
    }>`;

    cols.forEach(([f, _label, type, opt]) => {
      const v = r[f] || "";
      const dis = saved ? " disabled" : "";

      if (type === "action") {
        html += saved
          ? `<td class="text-center"> 
               <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil-fill"></i></button>  
<button class="btn btn-danger btn-sm del-row" data-id="${r.id}"><i class="bi bi-trash-fill"></i></button>

             </td>`
          : `<td class="text-center">                
<button class="btn btn-success btn-sm save-row me-1"><i class="bi bi-check-circle-fill"></i></button>
<button class="btn btn-warning btn-sm cancel-edit ms-1"><i class="bi bi-x-lg"></i></button> 
             </td>`;
      } else if (f === "client_id" && LCM.is_client) {
        html += `<td><input type="hidden" data-name="client_id" value="${v}"></td>`;
      } else if (type === "select") {
        html += `<td><select class="form-select form-select-sm"
                             data-name="${f}"${dis}>${opts(
          opt,
          v
        )}</select></td>`;
      } else if (type === "date") {
        html += `<td><input type="date" class="form-control form-control-sm"
                            data-name="${f}" value="${v}"${dis}></td>`;
      } else if (type === "number") {
        html += `<td><input type="number" step="any"
                            class="form-control form-control-sm"
                            data-name="${f}" value="${v}"${dis}></td>`;
      } else if (type === "readonly") {
        html += `<td>${v}</td>`;
      } else {
        /* text */
        html += `<td><input type="text" class="form-control form-control-sm"
                            data-name="${f}" value="${v}"${dis}></td>`;
      }
    });

    return html + "</tr>";
  }

  /* ---------- pagination & load ---------------------------------- */
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

  /* ---------- add draft row -------------------------------------- */
  $("#lcm-add-row-campaign").on("click", () => {
    const draft = {};
    cols.forEach((c) => (draft[c[0]] = ""));
    if (LCM.is_client) draft.client_id = LCM.current_client_id;

    $tbody.prepend(rowHtml(draft));
    LCM_initFlatpickr($tbody.find("tr").first());
  });

  /* ---------- row click => edit ---------------------------------- */
  $tbody.on("click", "tr", function (e) {
    if ($(e.target).closest(".btn").length) return; // ignore button clicks
    const $tr = $(this);
    if (!$tr.data("id") || $tr.hasClass("lcm-editing")) return;
    $tr.find(".edit-row").trigger("click");
  });

  /* ---------- cancel draft --------------------------------------- */
  $tbody.on("click", ".cancel-draft", function () {
    $(this).closest("tr").remove();
  });

  /* ---------- save draft ----------------------------------------- */
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

  /* ---------- edit / cancel / save edit -------------------------- */
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr").addClass("lcm-editing");
    LCM_initFlatpickr($tr);
    $tr.find("input,select").prop("disabled", false);
    $(this)
      .removeClass("edit-row btn-secondary")
      .addClass("save-edit btn-success")
      .html('<i class="bi bi-check-circle-fill"></i>')
      .after(
        '<button class="btn btn-warning btn-sm cancel-edit ms-1"><i class="bi bi-x-lg"></i></button>'
      );
  });

  $tbody.on("click", ".cancel-edit", () => load(page));

  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    data.id = $tr.data("id");
    data.action = "lcm_update_campaign";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  /* ---------- live N/A recalculation ----------------------------- */
  $tbody.on("input", "[data-name=leads]", function () {
    const $tr = $(this).closest("tr");
    const leads = +this.value || 0;
    const c = +$tr.find("td").eq(13).text() || 0;
    const n = +$tr.find("td").eq(14).text() || 0;
    const r = +$tr.find("td").eq(15).text() || 0;
    $tr
      .find("td")
      .eq(16)
      .text(Math.max(0, leads - c - n - r));
  });

  /* ---------- delete with modal ---------------------------------- */
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

  /* ---------- init ----------------------------------------------- */
  load(1);
});
