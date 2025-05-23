jQuery(function ($) {
  /* ---------- helpers ------------------------------------------------ */
  const $tbl = $("#lcm-lead-table");
  const $thead = $tbl.find("thead");
  const $tbody = $tbl.find("tbody");
  const $pager = $("#lcm-pager");
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

  let curPage = 1;

  /* build thead */
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  function renderRow(r) {
    return (
      "<tr data-id='" +
      (r.id || "") +
      "' class='" +
      (r.id ? "" : "table-warning") +
      "'>" +
      cols
        .map((c) => {
          const name = c[0],
            type = c[2],
            opts = c[3] || [];
          const val = r[name] || "";
          if (type === "select") {
            const optHtml = ["<option value=''></option>"]
              .concat(
                opts.map((o) => {
                  const v = Array.isArray(o) ? o[0] : o;
                  const t = Array.isArray(o) ? o[1] : o;
                  return `<option value="${v}" ${
                    v == val ? "selected" : ""
                  }>${t}</option>`;
                })
              )
              .join("");
            return `<td><select class='form-select form-select-sm' data-name='${name}'>${optHtml}</select></td>`;
          }
          return `<td><input type='text' class='form-control form-control-sm' data-name='${name}' value='${val}'></td>`;
        })
        .join("") +
      "</tr>"
    );
  }

  function loadPage(p) {
    $.getJSON(
      LCM.ajax_url,
      {
        action: LCM.action,
        nonce: LCM.nonce,
        page: p,
        per_page: LCM.per_page,
      },
      function (res) {
        curPage = p;
        $tbody.html(res.rows.map(renderRow).join(""));
        buildPager(res.total);
      }
    );
  }

  function buildPager(total) {
    const pages = Math.ceil(total / LCM.per_page) || 1;
    let html = "";
    for (let i = 1; i <= pages; i++) {
      html += `<button class='btn btn-outline-secondary ${
        i === curPage ? "active" : ""
      }' data-page='${i}'>${i}</button>`;
    }
    $pager.html(html);
  }

  /* pager click */
  $pager.on("click", "button", function () {
    loadPage(parseInt(this.dataset.page, 10));
  });

  /* Add new blank row */
  $("#lcm-add-row").on("click", function () {
    const blank = {};
    cols.forEach((c) => (blank[c[0]] = ""));
    $tbody.prepend(renderRow(blank));
  });

  /* Save on blur/select change */
  $tbody.on("change blur", "input,select", function () {
    const $tr = $(this).closest("tr");
    if ($tr.data("id")) return; // only new rows
    const data = { action: "lcm_create_lead", nonce: LCM.nonce };
    $tr.find("input,select").each(function () {
      data[this.dataset.name] = $(this).val();
    });
    if (!data.uid || !data.adset) return; // need keys
    $.post(
      LCM.ajax_url,
      data,
      function (res) {
        if (res.success) {
          loadPage(curPage); // reload current page
        } else {
          alert(res.data.msg || "Save error");
        }
      },
      "json"
    );
  });

  /* load first page */
  loadPage(1);
});
