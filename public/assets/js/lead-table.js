jQuery(function ($) {
  /* columns ---------------------------------------------------------- */
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

  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager");
  const per = LCM.per_page;
  let page = 1;

  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  function opts(arr, cur = "") {
    return (
      "<option value=''></option>" +
      arr
        .map((o) => {
          const v = Array.isArray(o) ? o[0] : o,
            t = Array.isArray(o) ? o[1] : o;
          return `<option value="${v}"${
            v == cur ? " selected" : ""
          }>${t}</option>`;
        })
        .join("")
    );
  }
  function rowHtml(r) {
    let html = `<tr data-id="${r.id || ""}"${
      r.id ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, _, t, arr]) => {
      const v = r[f] || "";
      if (t === "action") {
        html += r.id
          ? `<td class="text-center">
         <button class="btn btn-danger btn-sm del-row" data-id="${r.id}">🗑</button>
       </td>` // saved rows – only delete icon (future)
          : `<td class="text-center">
             <button class="btn btn-success btn-sm save-row me-1">💾</button>
             <button class="btn btn-danger  btn-sm del-row">🗑</button>
           </td>`;
      } else if (t === "select") {
        html += `<td><select class='form-select form-select-sm' data-name='${f}'>${opts(
          arr,
          v
        )}</select></td>`;
      } else if (t === "date") {
        html += `<td><input type='date'  class='form-control form-control-sm' data-name='${f}' value='${v}'></td>`;
      } else if (t === "time") {
        html += `<td><input type='time'  class='form-control form-control-sm' data-name='${f}' value='${v}'></td>`;
      } else {
        html += `<td><input type='text'  class='form-control form-control-sm' data-name='${f}' value='${v}'></td>`;
      }
    });
    return html + "</tr>";
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
  function buildPager(total) {
    const pages = Math.max(1, Math.ceil(total / per));
    $pager.html(
      Array.from({ length: pages }, (_, i) => {
        const n = i + 1;
        return `<button class='btn btn-outline-secondary ${
          n === page ? "active" : ""
        }' data-p='${n}'>${n}</button>`;
      }).join("")
    );
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  $("#lcm-add-row").on("click", () => {
    const blank = {};
    cols.forEach((c) => (blank[c[0]] = ""));
    $tbody.prepend(rowHtml(blank));
  });

  /* Date → Day auto-fill (no UTC offset issue) */
  $tbody.on("change", "input[type=date]", function () {
    const val = this.value;
    if (!val) return;
    const d = new Date(val + "T12:00:00"); // noon avoids UTC shift
    const day = [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ][d.getDay()];
    $(this).closest("tr").find("select[data-name=day]").val(day);
  });

  /* Autosave new row */
  $tbody.on("change blur", "input,select", function () {
    const $tr = $(this).closest("tr");
    if ($tr.data("id")) return;
    const d = { action: "lcm_create_lead", nonce: LCM.nonce };
    $tr.find("input,select").each(function () {
      d[this.dataset.name] = $(this).val();
    });
    if (!d.uid || !d.adset) return;
    $.post(
      LCM.ajax_url,
      d,
      (res) => {
        res.success ? load(page) : alert(res.data.msg || "Save error");
      },
      "json"
    );
  });
  /* Delete icon click -------------------------------------------------- */
  $tbody.on("click", ".del-row", function () {
    const $tr = $(this).closest("tr");
    const id = $(this).data("id") || ""; // blank for drafts

    if (!confirm("Delete this lead?")) return;

    if (!id) {
      // draft row
      $tr.remove();
      return;
    }

    /* saved row – call Ajax delete */
    $.post(
      LCM.ajax_url,
      {
        action: "lcm_delete_lead",
        nonce: LCM.nonce,
        id: id,
      },
      (res) => {
        if (res.success) {
          load(page); // reload current page
        } else {
          alert(res.data?.msg || "Delete failed");
        }
      },
      "json"
    );
  });

  load(1);
});
