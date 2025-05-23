jQuery(function ($) {
  /* ---------------- column config ---------------------------------- */
  const cols = [
    ["client_id", "Client", "select", LCM.clients],
    ["ad_name", "Ad Name", "select", LCM.ad_names],
    ["adset", "Adset", "text"],
    ["uid", "UID", "text"],
    ["lead_date", "Date", "text"],
    ["lead_time", "Time", "text"],
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
    ["attempt", "Att", "select", [1, 2, 3, 4, 5, 6]],
    [
      "attempt_type",
      "Att Type",
      "select",
      ["Connected:Not Relevant", "Connected:Relevant", "Not Connected"],
    ],
    [
      "attempt_status",
      "Att Status",
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

  const $tbl = $("#lcm-lead-table");
  const $thead = $tbl.find("thead");
  const $tbody = $tbl.find("tbody");
  const $pager = $("#lcm-pager");
  const per = LCM.per_page;
  let curPage = 1;

  /* build header row */
  $thead.html(
    "<tr>" +
      cols.map((c) => `<th class='small'>${c[1]}</th>`).join("") +
      "</tr>"
  );

  /* fetch and render page */
  function load(p = 1) {
    $.getJSON(
      LCM.ajax_url,
      {
        action: LCM.action,
        nonce: LCM.nonce,
        page: p,
        per_page: per,
      },
      (res) => {
        curPage = p;
        renderRows(res.rows);
        renderPager(res.total);
      }
    );
  }
  /* render rows */
  function renderRows(rows) {
    $tbody.html(
      rows
        .map((r) => {
          let t =
            "<tr data-id='" +
            (r.id || "") +
            "'" +
            (r.id ? "" : ' class="table-warning"') +
            ">";
          cols.forEach(([field, label, type]) => {
            const val = r[field] || "";
            if (type === "select") {
              t +=
                "<td><select class='form-select form-select-sm' data-name='" +
                field +
                "'>" +
                selectOpts(field, val) +
                "</select></td>";
            } else {
              t +=
                "<td><input type='text' class='form-control form-control-sm' data-name='" +
                field +
                "' value='" +
                val +
                "'></td>";
            }
          });
          t += "</tr>";
          return t;
        })
        .join("")
    );
  }
  function selectOpts(field, cur) {
    const def = cols.find((c) => c[0] === field)[3] || [];
    return (
      "<option value=''></option>" +
      def
        .map((o) => {
          const v = Array.isArray(o) ? o[0] : o;
          const t = Array.isArray(o) ? o[1] : o;
          return `<option value="${v}"${
            v == cur ? " selected" : ""
          }>${t}</option>`;
        })
        .join("")
    );
  }

  /* pager */
  function renderPager(total) {
    const pages = Math.max(1, Math.ceil(total / per));
    let html = "";
    for (let i = 1; i <= pages; i++) {
      html += `<button class='btn btn-outline-secondary ${
        i === curPage ? "active" : ""
      }' data-page='${i}'>${i}</button>`;
    }
    $pager.html(html);
  }
  $pager.on("click", "button", (e) => {
    load(parseInt($(e.currentTarget).data("page"), 10));
  });

  /* add new blank row */
  $("#lcm-add-row").on("click", () => {
    const blank = {};
    cols.forEach((c) => (blank[c[0]] = ""));
    $tbody.prepend(renderRows([blank]));
  });

  /* autosave new row */
  $tbody.on("change blur", "input,select", function () {
    const $tr = $(this).closest("tr");
    if ($tr.data("id")) return; // only unsaved rows
    const rowData = { action: "lcm_create_lead", nonce: LCM.nonce };
    $tr.find("input,select").each(function () {
      rowData[this.dataset.name] = $(this).val();
    });
    if (!rowData.uid || !rowData.adset) return;
    $.post(
      LCM.ajax_url,
      rowData,
      (res) => {
        if (res.success) load(curPage);
        else alert(res.data.msg || "Save failed");
      },
      "json"
    );
  });

  /* initial load */
  load(1);
});
