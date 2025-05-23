/* ==============================================================
 *  LEAD DATA GRID  – full UX, role-aware
 * ==============================================================*/
jQuery(function ($) {

  /* ---------- role flags -------------------------------------- */
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE  = LCM.per_page;

  /* ---------- column schema ----------------------------------- */
  const cols = [
    ["_action", "Action", "action"],
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
    ["ad_name", "Ad Name", "text"],
    ["adset", "Adset", "select", LCM.adsets],
    ["uid", "UID", "text"],
    ["lead_date", "Date", "date"],
    ["lead_time", "Time", "time"],
    ["day", "Day", "select", ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]],
    ["phone_number", "Phone", "text"],
    ["attempt", "Attempt", "select", [1,2,3,4,5,6]],
    ["attempt_type", "Attempt Type", "select",
        ["Connected:Not Relevant","Connected:Relevant","Not Connected"]],
    ["attempt_status", "Attempt Status", "select",
        ["Call Rescheduled","Just browsing","Not Interested","Ringing / No Response",
         "Store Visit Scheduled","Wrong Number / Invalid Number"]],
    ["store_visit_status", "Store Visit", "select", ["Show","No Show"]],
    ["remarks", "Remarks", "text"],
  ];

  /* ---------- DOM refs --------------------------------------- */
  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const $filter = $("#lcm-filter-client");

  let page = 1;
  let filterClient = IS_CLIENT ? CLIENT_ID : "";

  /* ---------- header ----------------------------------------- */
  $thead.html(
    "<tr>" + cols.map(c => `<th>${c[1]}</th>`).join("") + "</tr>"
  );

  /* ---------- helpers ---------------------------------------- */
  const opts = (arr, cur = "") =>
    "<option value=''></option>" +
    arr.map(o => {
      const v = Array.isArray(o) ? o[0] : o;
      const t = Array.isArray(o) ? o[1] : o;
      return `<option value="${v}"${v == cur ? " selected" : ""}>${t}</option>`;
    }).join("");

  const collect = $tr => {
    const obj = {};
    $tr.find("[data-name]").each(function () {
      obj[this.dataset.name] = $(this).val();
    });
    return obj;
  };

  /* ---------- dependency locks -------------------------------- */
  function toggleDeps($tr) {
    const attempt = $tr.find('[data-name=attempt]').val();
    const aType   = $tr.find('[data-name=attempt_type]').val();
    const aStatus = $tr.find('[data-name=attempt_status]').val();
    $tr.find('[data-name=attempt_type]').prop('disabled', !attempt);
    $tr.find('[data-name=attempt_status]').prop('disabled', !aType);
    $tr.find('[data-name=store_visit_status]')
       .prop('disabled', aStatus !== 'Store Visit Scheduled');
  }

  /* ---------- build row -------------------------------------- */
  function rowHtml(r = {}) {
    const isSaved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${isSaved ? "" : ' class="table-warning"'}>`;

    cols.forEach(([f, _l, typ, opt]) => {
      const v = r[f] || "";
      const dis = isSaved ? " disabled" : "";

      if (typ === "action") {
        html += isSaved
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-row" data-id="${r.id}">🗑</button>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1">💾</button>
               <button class="btn btn-warning btn-sm cancel-draft">✖</button>
             </td>`;
      } else if (typ === "select") {
        /* extra locks */
        let extra = "";
        if (f === "attempt_type"       && !r.attempt)        extra = " disabled";
        if (f === "attempt_status"     && !r.attempt_type)   extra = " disabled";
        if (f === "store_visit_status" && r.attempt_status !== "Store Visit Scheduled") extra = " disabled";
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}${extra}>${opts(opt, v)}</select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="date" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (typ === "time") {
        html += `<td><input type="time" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      } else if (typ === "readonly") {
        html += `<td>${LCM.clients.find(c => c[0] == v)?.[1] || ""}</td>`;
      } else {
        html += `<td><input type="text" class="form-control form-control-sm" data-name="${f}" value="${v}"${dis}></td>`;
      }
    });

    return html + "</tr>";
  }

  /* ---------- pager + load ----------------------------------- */
  function renderPager(total) {
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));
    $pager.html(Array.from({ length: pages }, (_, i) => {
      const n = i + 1;
      return `<button class="btn btn-outline-secondary ${n === page ? "active" : ""}" data-p="${n}">${n}</button>`;
    }).join(""));
  }

  function load(p = 1) {
    const q = { action:"lcm_get_leads_json", nonce:LCM.nonce, page:p, per_page:PER_PAGE };
    if (filterClient) q.client_id = filterClient;

    $.getJSON(LCM.ajax_url, q, res => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      renderPager(res.total);
    });
  }
  $pager.on("click", "button", e => load(+e.currentTarget.dataset.p));

  /* ---------- filter dropdown -------------------------------- */
  if (!IS_CLIENT && $filter.length) {
    $filter.on("change", function () {
      filterClient = this.value;
      load(1);
    });
  }

  /* ---------- add draft ------------------------------------- */
  $("#lcm-add-row-lead").on("click", () => {
    const d = {};
    cols.forEach(c => d[c[0]] = "");
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(d));
  });

  /* ---------- row click => edit ----------------------------- */
  $tbody.on("click", "tr", function (e) {
    if ($(e.target).closest(".btn").length) return;
    const $tr = $(this);
    if (!$tr.data("id") || $tr.hasClass("lcm-editing")) return;
    $tr.find(".edit-row").trigger("click");
  });

  /* ---------- edit mode switch ------------------------------ */
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr").addClass("lcm-editing");
    $tr.find("input,select").prop("disabled", false);
    if (IS_CLIENT) $tr.find('[data-name=client_id]').prop("disabled", true);

    $(this).removeClass("edit-row btn-secondary")
           .addClass("save-edit btn-success").text("💾")
           .after('<button class="btn btn-warning btn-sm cancel-edit ms-1">✖</button>');

    toggleDeps($tr);
  });

  /* ---------- cancel edit & cancel draft -------------------- */
  $tbody.on("click", ".cancel-edit", () => load(page));
  $tbody.on("click", ".cancel-draft", function () { $(this).closest("tr").remove(); });

  /* ---------- save edited row ------------------------------- */
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const d   = collect($tr);
    d.id      = $tr.data("id");
    d.action  = "lcm_update_lead";
    d.nonce   = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- save draft row -------------------------------- */
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const d   = collect($tr);
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    if (!d.uid || !d.adset || (!IS_CLIENT && !d.client_id)) {
      alert("UID, Adset & Client are required");
      return;
    }
    d.action = "lcm_create_lead"; d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- dependencies ---------------------------------- */
  $tbody.on("change", "select[data-name=attempt], select[data-name=attempt_type], select[data-name=attempt_status]", function () {
    toggleDeps($(this).closest("tr"));
  });

  /* ---------- date → day ------------------------------------ */
  $tbody.on("change", "input[type=date]", function () {
    const d = new Date(this.value + "T12:00:00");
    if (!isNaN(d)) {
      const day = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"][d.getDay()];
      $(this).closest("tr").find('[data-name=day]').val(day);
    }
  });

  /* ---------- delete with modal ----------------------------- */
  let delId = 0;
  const modal = new bootstrap.Modal('#lcmDelModal');

  $tbody.on("click", ".del-row", function () {
    delId = $(this).data("id") || 0;
    if (!delId) { $(this).closest("tr").remove(); return; }
    modal.show();
  });

  $("#lcm-confirm-del").on("click", function () {
    $.post(LCM.ajax_url,
      { action:"lcm_delete_lead", nonce:LCM.nonce, id:delId },
      res => {
        const pages = Math.max(1, Math.ceil(res.data.total / PER_PAGE));
        if (page > pages) page = pages;
        load(page);
        modal.hide();
      },
      "json"
    );
  });

  /* ---------- init ----------------------------------------- */
  load(1);
});
