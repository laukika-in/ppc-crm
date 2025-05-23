/* ------------------------------------------------ LEAD GRID (role-aware) */
jQuery(function ($) {
  /* 1 — role flags */
  const IS_CLIENT = !!LCM.is_client;
  const THIS_ID = LCM.current_client_id;

  /* 2 — column schema (client col conditional) */
  const base = [
    ["_action", "Action", "action"],
    ["ad_name", "Ad Name", "text"],
    ["adset", "Adset", "select", LCM.adsets],
    ["uid", "UID", "text"],
    ["lead_date", "Date", "date"],
    ["lead_time", "Time", "time"],
    [
      "day",
      "Day",
      "select",
      [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
      ],
    ],
    ["phone_number", "Phone", "text"],
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
  if (!IS_CLIENT) base.splice(1, 0, ["client_id", "Client", "readonly"]);

  const cols = base;

  /* ---------- DOM refs */
  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const per = LCM.per_page;
  let page = 1,
    filterClient = "";

  /* ---------- header */
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  /* ---------- util helpers */
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
    const o = {};
    $tr.find("[data-name]").each(function () {
      o[this.dataset.name] = $(this).val();
    });
    return o;
  };

  /* ---------- row builder (disabled for saved rows) */
  function rowHtml(r = {}) {
    let h = `<tr data-id="${r.id || ""}"${
      r.id ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([field, _lbl, type, options]) => {
      const v = r[field] || "";
      const disabled = r.id ? " disabled" : "";

      if (type === "action") {
        h += r.id
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-row" data-id="${r.id}">🗑</button>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1">💾</button>
               <button class="btn btn-danger  btn-sm del-row">🗑</button>
             </td>`;
      } else if (type === "select") {
        h += `<td><select class="form-select form-select-sm" data-name="${field}"${disabled}>${opts(
          options,
          v
        )}</select></td>`;
      } else if (type === "date") {
        h += `<td><input type="date" class="form-control form-control-sm" data-name="${field}" value="${v}"${disabled}></td>`;
      } else if (type === "time") {
        h += `<td><input type="time" class="form-control form-control-sm" data-name="${field}" value="${v}"${disabled}></td>`;
      } else if (type === "readonly") {
        h += `<td>${LCM.clients.find((c) => c[0] == v)?.[1] || ""}</td>`;
      } else {
        h += `<td><input type="text" class="form-control form-control-sm" data-name="${field}" value="${v}"${disabled}></td>`;
      }
    });
    return h + "</tr>";
  }

  /* ---------- pagination + load */
  function buildPager(t) {
    const pages = Math.max(1, Math.ceil(t / per));
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
    const params = {
      action: "lcm_get_leads_json",
      nonce: LCM.nonce,
      page: p,
      per_page: per,
    };
    if (filterClient) params.client_id = filterClient;
    $.getJSON(LCM.ajax_url, params, (res) => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      buildPager(res.total);
    });
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  /* ---------- filter (admins only) */
  if (!IS_CLIENT) {
    $("#lcm-filter-client").on("change", function () {
      filterClient = this.value;
      load(1);
    });
  } else {
    filterClient = THIS_ID; // enforce own rows
  }

  /* ---------- Add draft row */
  $("#lcm-add-row-lead").on("click", () => {
    const draft = {};
    cols.forEach((c) => (draft[c[0]] = ""));
    if (IS_CLIENT) {
      draft.client_id = THIS_ID;
    }
    $tbody.prepend(rowHtml(draft));
  });

  /* ---------- date → day */
  $tbody.on("change", "input[type=date]", function () {
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

  /* ---------- draft autosave */
  // $tbody.on("change blur","input,select",function(){
  //   const $tr=$(this).closest("tr"); if($tr.data("id"))return;
  //   const d=collect($tr);
  //   if(IS_CLIENT){ d.client_id=THIS_ID; }
  //   if(d.uid&&d.adset){
  //     d.action="lcm_create_lead"; d.nonce=LCM.nonce;
  //     $.post(LCM.ajax_url,d,()=>load(page),"json");
  //   }
  // });

  /* ---------- explicit save draft */
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const d = collect($tr);
    if (IS_CLIENT) {
      d.client_id = THIS_ID;
    }
    if (!d.uid || !d.adset) {
      alert("UID & Adset required");
      return;
    }
    d.action = "lcm_create_lead";
    d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- Edit / Save / Cancel on saved rows */
  $tbody.on("click", ".edit-row", function () {
    const $tr = $(this).closest("tr");
    $tr.find("input,select").prop("disabled", false);
    if (IS_CLIENT) {
      $tr.find("[data-name=client_id]").prop("disabled", true);
    }
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
    d.action = "lcm_update_lead";
    d.nonce = LCM.nonce;
    d.id = $tr.data("id");
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- Delete flow (shared modal) */
  let delId = 0;
  const modal = new bootstrap.Modal("#lcmDelModal");
  $tbody.on("click", ".del-row", function () {
    delId = $(this).data("id") || 0;
    if (!delId) {
      $(this).closest("tr").remove();
      return;
    }
    modal.show();
  });
  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      { action: "lcm_delete_lead", nonce: LCM.nonce, id: delId },
      (res) => {
        const total = res.data.total;
        const pages = Math.max(1, Math.ceil(total / per));
        if (page > pages) page = pages;
        load(page);
        modal.hide();
      },
      "json"
    );
  });

  /* ---------- initial load */
  load(1);
});
