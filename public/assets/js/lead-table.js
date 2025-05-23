/* ==============================================================
 *  LEAD DATA GRID  –  client-aware, editable, Ajax-driven
 * ==============================================================*/
jQuery(function ($) {

  /* ------------------------------------------------ role flags ---- */
  const IS_CLIENT   = !!LCM.is_client;          // true if logged user is ‘client’
  const THIS_ID     = LCM.current_client_id;    // current user ID

  /* ------------------------------------------------ column schema -- */
  const base = [
    ["_action","Action","action"],
    ["ad_name","Ad Name","text"],
    ["adset","Adset","select",LCM.adsets],
    ["uid","UID","text"],
    ["lead_date","Date","date"],
    ["lead_time","Time","time"],
    ["day","Day","select",["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"]],
    ["phone_number","Phone","text"],
    ["attempt","Attempt","select",[1,2,3,4,5,6]],
    ["attempt_type","Attempt Type","select",["Connected:Not Relevant","Connected:Relevant","Not Connected"]],
    ["attempt_status","Attempt Status","select",["Call Rescheduled","Just browsing","Not Interested","Ringing / No Response","Store Visit Scheduled","Wrong Number / Invalid Number"]],
    ["store_visit_status","Store Visit","select",["Show","No Show"]],
    ["remarks","Remarks","text"],
  ];
if (!IS_CLIENT) base.splice(1, 0, ["client_id","Client","select", LCM.clients]);
  const cols = base;

  /* ------------------------------------------------ DOM refs ------- */
  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const per    = LCM.per_page;
  let   page   = 1;
  let   filterClient = IS_CLIENT ? THIS_ID : "";   // default filter

  /* ------------------------------------------------ header --------- */
  $thead.html("<tr>" + cols.map(c => `<th>${c[1]}</th>`).join("") + "</tr>");

  /* ------------------------------------------------ helpers -------- */
  const opts = (arr, cur = "") =>
    "<option value=''></option>" + arr.map(o => {
      const v = Array.isArray(o) ? o[0] : o;
      const t = Array.isArray(o) ? o[1] : o;
      return `<option value="${v}"${v == cur ? " selected" : ""}>${t}</option>`;
    }).join("");

  const collect = $tr => {
    const o = {};
    $tr.find("[data-name]").each(function () { o[this.dataset.name] = $(this).val(); });
    return o;
  };

  /* ---------- progressive lock/unlock ----------------------------- */
  function toggleDeps($tr) {
    const attempt      = $tr.find('[data-name=attempt]').val();
    const aType        = $tr.find('[data-name=attempt_type]').val();
    const aStatus      = $tr.find('[data-name=attempt_status]').val();
    const $typeSel     = $tr.find('[data-name=attempt_type]');
    const $statusSel   = $tr.find('[data-name=attempt_status]');
    const $storeSel    = $tr.find('[data-name=store_visit_status]');

    $typeSel.prop('disabled', !attempt);
    $statusSel.prop('disabled', !aType);
    $storeSel.prop('disabled', aStatus !== 'Store Visit Scheduled');
  }

  /* ---------- build one row --------------------------------------- */
  function rowHtml(r = {}) {

    let html = `<tr data-id="${r.id || ""}"${r.id ? "" : " class='table-warning'"}>`;

    cols.forEach(([field, _lbl, colType, options]) => {

      const val = r[field] || "";
      const disBase = r.id ? " disabled" : "";   // lock saved rows

      if (colType === "action") {

        html += r.id
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-row"  data-id="${r.id}">🗑</button>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1">💾</button>
               <button class="btn btn-danger  btn-sm del-row">🗑</button>
             </td>`;

      } else if (colType === "select") {

        /* extra dependency locks */
        let extra = "";
        if (field === "attempt_type"      && !r.attempt)                       extra = " disabled";
        if (field === "attempt_status"    && !r.attempt_type)                 extra = " disabled";
        if (field === "store_visit_status"&& r.attempt_status !== "Store Visit Scheduled") extra = " disabled";

        html += `<td>
                   <select class="form-select form-select-sm"
                           data-name="${field}"${disBase}${extra}>
                     ${opts(options, val)}
                   </select>
                 </td>`;

      } else if (colType === "date") {

        html += `<td><input type="date"  class="form-control form-control-sm"
                           data-name="${field}" value="${val}"${disBase}></td>`;

      } else if (colType === "time") {

        html += `<td><input type="time"  class="form-control form-control-sm"
                           data-name="${field}" value="${val}"${disBase}></td>`;

      } else if (colType === "readonly") {

        const label = LCM.clients.find(c => c[0] == val)?.[1] || "";
        html += `<td>${label}</td>`;

      } else { /* text */
        html += `<td><input type="text"  class="form-control form-control-sm"
                           data-name="${field}" value="${val}"${disBase}></td>`;
      }
    });

    html += "</tr>";
    return html;
  }

  /* ---------- pagination & load ------------------------------------ */
  function buildPager(total) {
    const pages = Math.max(1, Math.ceil(total / per));
    $pager.html(Array.from({ length: pages }, (_, i) => {
      const n = i + 1;
      return `<button class="btn btn-outline-secondary ${n === page ? "active" : ""}" data-p="${n}">${n}</button>`;
    }).join(""));
  }

  function load(p = 1) {
    const params = {
      action: "lcm_get_leads_json",
      nonce:  LCM.nonce,
      page:   p,
      per_page: per
    };
    if (filterClient) params.client_id = filterClient;

    $.getJSON(LCM.ajax_url, params, res => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      buildPager(res.total);
    });
  }
  $pager.on("click", "button", e => load(+e.currentTarget.dataset.p));

  /* ---------- filter (admin/PPC) ----------------------------------- */
  if (!IS_CLIENT) {
    $("#lcm-filter-client").on("change", function () {
      filterClient = this.value;
      load(1);
    });
  }

  /* ---------- add draft row ---------------------------------------- */
  $("#lcm-add-row-lead").on("click", () => {
    const draft = {};
    cols.forEach(c => draft[c[0]] = "");
    if (IS_CLIENT) draft.client_id = THIS_ID;
    $tbody.prepend(rowHtml(draft));
  });

  /* ---------- date → day autofill --------------------------------- */
  $tbody.on("change", "input[type=date]", function () {
    const d = new Date(this.value + "T12:00:00");
    if (!isNaN(d)) {
      const day = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"][d.getDay()];
      $(this).closest("tr").find('[data-name=day]').val(day);
    }
  });

  /* ---------- dependency toggles ---------------------------------- */
  $tbody.on("change", "select[data-name=attempt], select[data-name=attempt_type], select[data-name=attempt_status]", function () {
    toggleDeps($(this).closest("tr"));
  });

  // /* ---------- autosave drafts ------------------------------------- */
  // $tbody.on("change blur", "input,select", function () {
  //   const $tr = $(this).closest("tr");
  //   if ($tr.data("id")) return;                        // only drafts

  //   const d = collect($tr);
  //   if (IS_CLIENT) d.client_id = THIS_ID;

  //   if (d.uid && d.adset) {
  //     d.action = "lcm_create_lead";
  //     d.nonce  = LCM.nonce;
  //     $.post(LCM.ajax_url, d, () => load(page), "json");
  //   }
  // });

  /* ---------- explicit save draft --------------------------------- */
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const d   = collect($tr);
    if (IS_CLIENT) d.client_id = THIS_ID;
    if (!d.uid || !d.adset) { alert("UID & Adset required"); return; }

    d.action = "lcm_create_lead"; d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- Edit / Save / Cancel on saved rows ------------------ */
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr");
    $tr.find("input,select").prop("disabled", false);
    if (IS_CLIENT) $tr.find('[data-name=client_id]').prop("disabled", true);
    $(this).removeClass("edit-row btn-secondary")
           .addClass("save-edit btn-success").text("💾")
           .after('<button class="btn btn-warning btn-sm cancel-edit ms-1">✖</button>');
    toggleDeps($tr);                                   // re-apply field locks
  });

  $tbody.on("click", ".cancel-edit", () => load(page));

  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr");
    const d   = collect($tr);
    d.action  = "lcm_update_lead"; d.nonce = LCM.nonce; d.id = $tr.data("id");
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- delete (modal) -------------------------------------- */
  let delId = 0;
  const modal = new bootstrap.Modal('#lcmDelModal');

  $tbody.on("click", ".del-row", function () {
    delId = $(this).data("id") || 0;
    if (!delId) { $(this).closest("tr").remove(); return; }
    modal.show();
  });

  $("#lcm-confirm-del").on("click", function () {
    $.post(LCM.ajax_url, { action:"lcm_delete_lead", nonce:LCM.nonce, id:delId }, res => {
      const total = res.data.total;
      const pages = Math.max(1, Math.ceil(total / per));
      if (page > pages) page = pages;
      load(page);
      modal.hide();
    }, "json");
  });

  /* ---------- init */
  load(1);
});
