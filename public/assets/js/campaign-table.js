/* ------------------------------------------------ CAMPAIGN GRID ----- */
jQuery(function ($) {
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

  const $thead = $("#lcm-campaign-table thead");
  const $tbody = $("#lcm-campaign-table tbody");
  const $pager = $("#lcm-pager-campaign");
  const per = LCM.per_page;
  let page = 1;

  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  const opts = (a) =>
    "<option value=''></option>" +
    a.map((o) => `<option>${o}</option>`).join("");

  function rowHtml(r = {}) {
    let html = `<tr data-id="${r.id || ""}"${
      r.id ? "" : " class='table-warning'"
    }>`;

    cols.forEach(([f, _l, type, arr]) => {
      const v = r[f] || "";
      const dis = r.id ? " disabled" : "";

      if (type === "action") {
        html += r.id
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-camp" data-id="${r.id}">🗑</button>
             </td>`
          : `<td class="text-center"><button class="btn btn-success btn-sm save-camp">💾</button></td>`;
      } else if (type === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}>${opts(
          arr
        )}</select></td>`;
      } else if (type === "date") {
        html += `<td><input type="date" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (type === "number") {
        html += `<td><input type="number" step="any" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (type === "readonly") {
        html += `<td>${v}</td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      }
    });

    return html + "</tr>";
  }

  function collect($tr) {
    const o = {};
    $tr.find("[data-name]").each(function () {
      o[this.dataset.name] = $(this).val();
    });
    return o;
  }

  /* pager + load */
  function buildPager(t) {
    const p = Math.max(1, Math.ceil(t / per));
    $pager.html(
      Array.from(
        { length: p },
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
        per_page: per,
      },
      (res) => {
        page = p;
        $tbody.html(res.rows.map(rowHtml).join(""));
        buildPager(res.total);
      }
    );
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  $("#lcm-add-row-campaign").on("click", () => {
    $tbody.prepend(rowHtml({}));
  });

  /* N/A live recalc */
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

  /* Save new draft */
  $tbody.on("click", ".save-camp", function () {
    const d = collect($(this).closest("tr"));
    if (!d.adset) {
      alert("Adset required");
      return;
    }
    d.action = "lcm_create_campaign";
    d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* Edit → Save / Cancel */
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr");
    $tr.find("input,select").prop("disabled", false);
    $(this)
      .removeClass("edit-row btn-secondary")
      .addClass("save-edit btn-success")
      .text("💾")
      .after(
        '<button class="btn btn-warning btn-sm cancel-edit ms-1">✖</button>'
      );
  });
  $tbody.on("click", ".cancel-edit", () => load(page));
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const d = collect($tr);
    d.id = $tr.data("id");
    d.action = "lcm_update_campaign";
    d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* Delete */
  /* ---------- Delete flow (lazy modal) ------------------------------ */
  let delId = 0;

  /* open modal on 🗑 click */
  $tbody.on("click", ".del-row, .del-camp", function () {
    delId = $(this).data("id") || 0;

    /* draft row: just remove from DOM */
    if (!delId) {
      $(this).closest("tr").remove();
      return;
    }

    /* get or create the Bootstrap modal */
    const modalEl = document.getElementById("lcmDelModal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  });

  /* confirm button inside the shared modal */
  $("#lcm-confirm-del")
    .off("click")
    .on("click", function () {
      const action =
        $(this).closest("table").attr("id") === "lcm-campaign-table"
          ? "lcm_delete_campaign"
          : "lcm_delete_lead";

      $.post(
        LCM.ajax_url,
        { action, nonce: LCM.nonce, id: delId },
        (res) => {
          const total = res.data.total || 0;
          const pages = Math.max(1, Math.ceil(total / LCM.per_page));
          if (page > pages) page = pages;
          load(page); // refresh current grid page

          bootstrap.Modal.getInstance(
            document.getElementById("lcmDelModal")
          ).hide();
        },
        "json"
      );
    });

  load(1);
});
