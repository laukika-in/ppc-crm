document.addEventListener("DOMContentLoaded", function () {
  if (!document.getElementById("lcm-campaign-tbl")) return;

  new Tabulator("#lcm-campaign-tbl", {
    ajaxURL: LCM.ajax_url,
    ajaxParams: { action: LCM.action, nonce: LCM.nonce },
    layout: "fitColumns",
    height: "600px",
    pagination: false, // switch to "remote" later if needed
    columns: [
      { title: "Adset", field: "adset", headerFilter: "input" },
      { title: "Leads", field: "leads", hozAlign: "right" },
      { title: "Connected", field: "connected_number", hozAlign: "right" },
      { title: "Not Connected", field: "not_connected", hozAlign: "right" },
      { title: "Relevant", field: "relevant", hozAlign: "right" },
      { title: "N/A", field: "not_available", hozAlign: "right" },
      {
        title: "Scheduled Visit",
        field: "scheduled_store_visit",
        hozAlign: "right",
      },
      { title: "Store Visit", field: "store_visit", hozAlign: "right" },
      {
        title: "Amount Spent",
        field: "amount_spent",
        hozAlign: "right",
        formatter: "money",
      },
      { title: "CPM", field: "cpm", hozAlign: "right", formatter: "money" },
    ],
  });
});
