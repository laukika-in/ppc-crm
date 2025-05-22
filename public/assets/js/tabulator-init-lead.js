document.addEventListener("DOMContentLoaded", function () {
  if (!document.getElementById("lcm-lead-tbl")) return;

  new Tabulator("#lcm-lead-tbl", {
    ajaxURL: LCM.ajax_url,
    ajaxParams: { action: LCM.action, nonce: LCM.nonce },
    layout: "fitColumns",
    height: "600px",
    pagination: false,
    columns: [
      { title: "UID", field: "uid", headerFilter: "input" },
      { title: "Ad Name", field: "ad_name", headerFilter: "input" },
      { title: "Adset", field: "adset", headerFilter: "input" },
      { title: "Attempt", field: "attempt", hozAlign: "right" },
      { title: "Attempt Type", field: "attempt_type" },
      { title: "Attempt Status", field: "attempt_status" },
      { title: "Store Visit Status", field: "store_visit_status" },
      { title: "Lead Date", field: "lead_date" },
      { title: "Lead Time", field: "lead_time" },
      { title: "Name", field: "name", headerFilter: "input" },
      { title: "Phone", field: "phone_number" },
    ],
  });
});
