/* public/assets/js/campaign-table.js */
jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;

  // 1) Define all potential columns
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

  // 2) Filter out client_id column entirely for Client users
  const cols = IS_CLIENT
    ? allCols.filter((col) => col[0] !== "client_id")
    : allCols;

  /* ---------- DOM refs ------------------------------------------- */
  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");
  const $addBtn = $("#lcm-add-row-campaign");

  let page = 1;

  /* ---------- render header -------------------------------------- */
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  /* ---------- helpers -------------------------------------------- */
  const opts = (arr, cur = "") =>
    "<option value=''></option>" +
    arr
      .map((o) => {
        if (Array.isArray(o)) {
          const [val, text] = o;
          return `<option value="${val}"${
            val == cur ? " selected" : ""
          }>${text}</option>`;
        } else {
          return `<option value="${o}"${
            o == cur ? " selected" : ""
          }>${o}</option>`;
        }
      })
      .join("");

  const collect = ($tr) => {
    const data = {};
    $tr.find("[data-name]").each(function () {
      data[this.dataset.name] = $(this).val();
    });
    return data;
  };

  /* ---------- build one row -------------------------------------- */
  function rowHtml(r = {}) {
    const isSaved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      !isSaved ? ' class="table-warning"' : ""
    }>`;

    cols.forEach(([field, _lbl, type, opt]) => {
      const val = r[field] || "";
      const disabled = isSaved ? " disabled" : "";

      if (type === "action") {
        if (!IS_CLIENT) {
          html += isSaved
            ? `<td class="text-center">
                 <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil"></i></button>
                 <button class="btn btn-danger btn-sm del-camp" data-id="${r.id}"><i class="bi bi-trash"></i></button>
               </td>`
            : `<td class="text-center">
                 <button class="btn btn-success btn-sm save-camp me-1"><i class="bi bi-save"></i></button>
                 <button class="btn btn-warning btn-sm cancel-draft"><i class="bi bi-x-lg"></i></button>
               </td>`;
        } else {
          html += `<td></td>`;
        }
      } else if (type === "select") {
        html += `<td>
                   <select class="form-select form-select-sm"
                           data-name="${field}"${disabled}>
                     ${opts(opt, val)}
                   </select>
                 </td>`;
      } else if (type === "date") {
        // Use a text input for Flatpickr
        html += `<td>
                   <input type="text" class="form-control form-control-sm flatpickr-date"
                          data-name="${field}" value="${val}"${disabled}>
                 </td>`;
      } else if (type === "number") {
        html += `<td>
                   <input type="number" step="any" class="form-control form-control-sm"
                          data-name="${field}" value="${val}"${disabled}>
                 </td>`;
      } else if (type === "readonly") {
        html += `<td>${val}</td>`;
      } else {
        html += `<td>
                   <input type="text" class="form-control form-control-sm"
                          data-name="${field}" value="${val}"${disabled}>
                 </td>`;
      }
    });

    html += "</tr>";
    return html;
  }

  /* ---------- pagination & load ---------------------------------- */
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

  /* ---------- hide add button for clients ----------------------- */
  if (IS_CLIENT) {
    $addBtn.hide();
  }

  /* ---------- add draft row -------------------------------------- */
  $addBtn.on("click", () => {
    const draft = {};
    cols.forEach(([field]) => {
      draft[field] = "";
    });
    if (IS_CLIENT) draft.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(draft));
    LCM_initFlatpickr($tbody.find("tr").first());
  });

  /* ---------- edit row (zoom + show fields) --------------------- */
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

  /* ---------- save draft ----------------------------------------- */
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

  /* ---------- save edited row ----------------------------------- */
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const data = collect($tr);
    data.id = $tr.data("id");
    data.action = "lcm_update_campaign";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
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

  /* ---------- init load ----------------------------------------- */
  load(1);
});
