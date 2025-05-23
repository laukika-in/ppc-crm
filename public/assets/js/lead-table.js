/* ------------------------------------------------ LEAD GRID --------- */
jQuery(function ($) {
  /* column schema: [field, label, type, options] */
  const cols = [
    ["_action", "Action", "action"],
    ["client_id", "Client", "select", LCM.clients],
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

  /* ---------- DOM refs */
  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const per = LCM.per_page;
  let page = 1;

  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  /* ---------- util */
  const opts = (arr, cur = "") =>
    "<option value=''></option>" +
    arr
      .map((o) => {
        const v = Array.isArray(o) ? o[0] : o;
        const t = Array.isArray(o) ? o[1] : o;
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

  /* ---------- row builder */
  function rowHtml(r = {}) {
    let html = `<tr data-id="${r.id || ""}"${
      r.id ? "" : " class='table-warning'"
    }>`;

    cols.forEach(([field, _lbl, colType, options]) => {
      const val = r[field] || "";
      const dis = r.id ? " disabled" : "";

      if (colType === "action") {
        html += r.id
          ? `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-row" data-id="${r.id}">🗑</button>
             </td>`
          : `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1">💾</button>
               <button class="btn btn-danger  btn-sm del-row">🗑</button>
             </td>`;
      } else if (colType === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${field}"${dis}>${opts(
          options,
          val
        )}</select></td>`;
      } else if (colType === "date") {
        html += `<td><input type="date"  class="form-control form-control-sm" data-name="${field}" value="${val}"${dis}></td>`;
      } else if (colType === "time") {
        html += `<td><input type="time"  class="form-control form-control-sm" data-name="${field}" value="${val}"${dis}></td>`;
      } else {
        /* text / number */
        html += `<td><input type="text"  class="form-control form-control-sm" data-name="${field}" value="${val}"${dis}></td>`;
      }
    });

    html += "</tr>";
    return html;
  }

  /* ---------- pagination */
  function buildPager(total) {
    const pages = Math.max(1, Math.ceil(total / per));
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
        action: "lcm_get_leads_json",
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

  $("#lcm-add-row-lead").on("click", () => {
    $tbody.prepend(rowHtml({}));
  });

  /* ---------- progressive unlock */
  function toggleDeps($tr) {
    const attempt = $tr.find("[data-name=attempt]").val();
    const attemptType = $tr.find("[data-name=attempt_type]").val();
    const attemptStat = $tr.find("[data-name=attempt_status]").val();
    $tr
      .find("[data-name=attempt_type]")
      .prop("disabled", !$tr.data("id") && !attempt)
      .prop("disabled", !attempt);
    $tr.find("[data-name=attempt_status]").prop("disabled", !attemptType);
    $tr
      .find("[data-name=store_visit_status]")
      .prop("disabled", attemptStat !== "Store Visit Scheduled");
  }

  $tbody.on(
    "change",
    "select[data-name=attempt], select[data-name=attempt_type], select[data-name=attempt_status]",
    function () {
      toggleDeps($(this).closest("tr"));
    }
  );

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

  //   /* ---------- draft autosave */
  //   $tbody.on("change blur","input,select",function(){
  //     const $tr=$(this).closest("tr");
  //     if($tr.data("id"))return;
  //     const d=collect($tr);
  //     if(d.uid&&d.adset){
  //       d.action="lcm_create_lead"; d.nonce=LCM.nonce;
  //       $.post(LCM.ajax_url,d,()=>load(page),"json");
  //     }
  //   });

  /* ---------- Save new draft row explicitly */
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const d = collect($tr);
    if (!d.uid || !d.adset) {
      alert("UID & Adset required");
      return;
    }
    d.action = "lcm_create_lead";
    d.nonce = LCM.nonce;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- Edit → Save / Cancel on saved rows */
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
    const id = $tr.data("id");
    const d = collect($tr);
    d.action = "lcm_update_lead";
    d.nonce = LCM.nonce;
    d.id = id;
    $.post(LCM.ajax_url, d, () => load(page), "json");
  });

  /* ---------- Delete (modal) */
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

  load(1);
});
