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
    let h = `<tr data-id="${r.id || ""}"${
      r.id ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, _, t, a]) => {
      const v = r[f] || "";
        const disabled = r.id ? " disabled" : "";   // ← the one-liner flag

     if (type === "action") {

  if (!r.id) { // draft
    html += `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1">💾</button>
               <button class="btn btn-danger  btn-sm del-row">🗑</button>
             </td>`;
  } else {     // saved
    html += `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-row" data-id="${r.id}">🗑</button>
             </td>`;
  }
}
else if (type === "select") {
      html += `<td>
        <select class="form-select form-select-sm" data-name="${field}"${disabled}>
          ${opts(options, val)}
        </select>
      </td>`;
    } else if (type === "date") {
      html += `<td>
        <input type="date" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    } else if (type === "time") {
      html += `<td>
        <input type="time" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    } else if (type === "number") {
      html += `<td>
        <input type="number" step="any" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    } else if (type === "readonly") {
      html += `<td>${val}</td>`;
    } else { // plain text
      html += `<td>
        <input type="text" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    }
  });

  html += "</tr>";
  return html;
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
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  $("#lcm-add-row-campaign").on("click", () => {
    $tbody.prepend(rowHtml({}));
  });

  /* Re-calc N/A when Leads changes */
  $tbody.on("input", "[data-name=leads]", function () {
    const $tr = $(this).closest("tr");
    const leads = +this.value || 0;
    const conn = +$tr.find("td").eq(14).text() || 0;
    const notc = +$tr.find("td").eq(15).text() || 0;
    const rel = +$tr.find("td").eq(16).text() || 0;
    $tr
      .find("td")
      .eq(17)
      .text(Math.max(0, leads - conn - notc - rel));
  });

  /* Save campaign */
  $tbody.on("click", ".save-camp", function () {
    const $tr = $(this).closest("tr");
    const d = { action: "lcm_create_campaign", nonce: LCM.nonce };
    $tr.find("input,select").each(function () {
      d[this.dataset.name] = $(this).val();
    });
    if (!d.adset) {
      alert("Adset required");
      return;
    }
    $.post(
      LCM.ajax_url,
      d,
      (res) => {
        res.success ? load(page) : alert(res.data.msg || "Error");
      },
      "json"
    );
  });

  /* Delete campaign */
  let delId = 0;
  const modal = new bootstrap.Modal("#lcmDelModal");
  $tbody.on("click", ".del-camp", function () {
    delId = $(this).data("id");
    modal.show();
  });
  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      { action: "lcm_delete_campaign", nonce: LCM.nonce, id: delId },
      (res) => {
        if (res.success) {
          const total = res.data.total;
          const pages = Math.max(1, Math.ceil(total / per));
          if (page > pages) page = pages;
          load(page);
        } else alert(res.data.msg || "Delete failed");
        modal.hide();
      },
      "json"
    );
  });

  load(1);
});
