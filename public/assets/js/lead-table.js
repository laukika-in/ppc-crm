jQuery(function ($) {
  const IS_CLIENT = !!LCM.is_client;
  const CLIENT_ID = LCM.current_client_id;
  const PER_PAGE = LCM.per_page;

  const cols = [
    ["_action", "Action", "action"],
    ...(!IS_CLIENT ? [["client_id", "Client", "select", LCM.clients]] : []),
    ["ad_name", "Ad Name", "text"],
    ["adset", "Adset", "select", LCM.adsets],
    ["uid", "UID", "text"],
    ["lead_date", "Date", "date"], // date picker
    ["lead_time", "Time", "time"], // time picker
    [
      "day",
      "Day",
      "select",
      [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
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

  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager-lead");
  const $filter = $("#lcm-filter-client");
  let page = 1,
    filterClient = IS_CLIENT ? CLIENT_ID : "";

  // 1) Render header
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  // 2) Helpers
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
  function toggleDeps($tr) {
    const a = $tr.find("[data-name=attempt]").val();
    const t = $tr.find("[data-name=attempt_type]").val();
    const s = $tr.find("[data-name=attempt_status]").val();
    $tr.find("[data-name=attempt_type]").prop("disabled", !a);
    $tr.find("[data-name=attempt_status]").prop("disabled", !t);
    $tr
      .find("[data-name=store_visit_status]")
      .prop("disabled", s !== "Store Visit Scheduled");
  }

  // 3) Build row HTML
  function rowHtml(r = {}) {
    const saved = !!r.id;
    let html = `<tr data-id="${r.id || ""}"${
      saved ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, _l, typ, opt]) => {
      const val = r[f] || "",
        dis = saved ? " disabled" : "";
      if (typ === "action") {
        if (!saved) {
          html += `<td class="text-center">
                  <button class="btn btn-success btn-sm save-row me-1"><i class="bi bi-save"></i></button>
                  <button class="btn btn-warning btn-sm cancel-draft"><i class="bi bi-x-lg"></i></button>
                 </td>`;
        } else {
          html += `<td class="text-center">
                  <button class="btn btn-secondary btn-sm edit-row me-1"><i class="bi bi-pencil"></i></button>
                  <button class="btn btn-danger btn-sm del-row" data-id="${r.id}"><i class="bi bi-trash"></i></button>
                 </td>`;
        }
      } else if (typ === "select") {
        html += `<td><select class="form-select form-select-sm" data-name="${f}"${dis}>
                  ${opts(opt, val)}
                </select></td>`;
      } else if (typ === "date") {
        html += `<td><input type="text" class="form-control form-control-sm flatpickr-date"
                         data-name="${f}" value="${val}"${dis}></td>`;
      } else if (typ === "time") {
        html += `<td><input type="text" class="form-control form-control-sm flatpickr-time"
                         data-name="${f}" value="${val}"${dis}></td>`;
      } else {
        // text
        html += `<td><input type="text" class="form-control form-control-sm"
                         data-name="${f}" value="${val}"${dis}></td>`;
      }
    });
    html += "</tr>";
    return html;
  }

  // 4) Pager & Load
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
    const q = {
      action: "lcm_get_leads_json",
      nonce: LCM.nonce,
      page: p,
      per_page: PER_PAGE,
    };
    if (filterClient) q.client_id = filterClient;
    $.getJSON(LCM.ajax_url, q, (res) => {
      page = p;
      $tbody.html(res.rows.map(rowHtml).join(""));
      renderPager(res.total);
    });
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  // 5) Filter (admin/PPC)
  if (!IS_CLIENT) {
    $filter.on("change", function () {
      filterClient = this.value;
      load(1);
    });
  }

  // 6) Add draft
  $("#lcm-add-row-lead").on("click", () => {
    const d = {};
    cols.forEach((c) => (d[c[0]] = ""));
    if (IS_CLIENT) d.client_id = CLIENT_ID;
    $tbody.prepend(rowHtml(d));
    LCM_initFlatpickr($tbody.find("tr").first());
  });

  // 7) Row-click => edit
  $tbody.on("click", "tr", function (e) {
    if ($(e.target).closest("button").length) return;
    const $tr = $(this);
    if (!$tr.data("id") || $tr.hasClass("lcm-editing")) return;
    $tr.find(".edit-row").trigger("click");
  });

  // 8) Edit mode
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
    toggleDeps($tr);
  });
  $tbody.on("click", ".cancel-edit", () => load(page));
  $tbody.on("click", ".cancel-draft", function () {
    $(this).closest("tr").remove();
  });

  // 9) Save draft
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr"),
      data = collect($tr);
    if (IS_CLIENT) data.client_id = CLIENT_ID;
    if (!data.uid || !data.adset) {
      alert("UID & Adset required");
      return;
    }
    data.action = "lcm_create_lead";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // 10) Save edit
  $tbody.on("click", ".save-edit", function () {
    const $tr = $(this).closest("tr"),
      data = collect($tr);
    data.id = $tr.data("id");
    data.action = "lcm_update_lead";
    data.nonce = LCM.nonce;
    $.post(LCM.ajax_url, data, () => load(page), "json");
  });

  // 11) Dependencies
  $tbody.on(
    "change",
    "select[data-name=attempt],select[data-name=attempt_type],select[data-name=attempt_status]",
    function () {
      toggleDeps($(this).closest("tr"));
    }
  );

  // 12) Date → Day
  $tbody.on("change", ".flatpickr-date", function () {
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

  // 13) Delete
  let delId = 0;
  const modal = new bootstrap.Modal("#lcmDelModal");
  $tbody.on("click", ".del-row", function () {
    delId = $(this).data("id");
    if (!delId) $(this).closest("tr").remove();
    else modal.show();
  });
  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      { action: "lcm_delete_lead", nonce: LCM.nonce, id: delId },
      (res) => {
        const p = Math.max(1, Math.ceil(res.data.total / PER_PAGE));
        if (page > p) page = p;
        load(page);
        modal.hide();
      },
      "json"
    );
  });

  // init
  load(1);
});
