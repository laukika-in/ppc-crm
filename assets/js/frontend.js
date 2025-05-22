jQuery(function ($) {
  function initTable(type) {
    var table = new Tabulator("#" + type + "_table", {
      ajaxURL: PPC_CRM_Ajax.url,
      ajaxParams: {
        action: "ppc_crm_load",
        nonce: PPC_CRM_Ajax.nonce,
        type: type.replace("_data", ""),
      },
      layout: "fitColumns",
      columns: [
        {
          title: "Client",
          field: "client",
          editor: "select",
          editorParams: { values: PpcCrmData.clients },
        },
        { title: "UID", field: "uid", editor: "input" },
        {
          title: "Date of Lead",
          field: "date_of_lead",
          editor: "input",
          formatter: "datetime",
          formatterParams: {
            inputFormat: "YYYY-MM-DD",
            outputFormat: "DD-MMM-YYYY",
          },
        },
        { title: "Time of Lead", field: "time_of_lead", editor: "input" },
        {
          title: "Day",
          field: "day",
          editor: "select",
          editorParams: { values: PpcCrmData.days },
        },
        { title: "Name", field: "name", editor: "input" },
        { title: "Phone", field: "phone", editor: "input" },
        { title: "Alt. Phone", field: "alt_phone", editor: "input" },
        { title: "Email", field: "email", editor: "input" },
        { title: "Location", field: "location", editor: "input" },
        {
          title: "Client Type",
          field: "client_type",
          editor: "select",
          editorParams: { values: PpcCrmData.client_types },
        },
        { title: "Sources", field: "sources", editor: "input" },
        { title: "Campaign Source", field: "source_campaign", editor: "input" },
        { title: "Targeting", field: "targeting", editor: "input" },
        { title: "Ad Name", field: "ad_name", editor: "input" },
        { title: "Adset", field: "adset", editor: "input" },
        { title: "Budget", field: "budget", editor: "input" },
        { title: "Product", field: "product", editor: "input" },
        {
          title: "Occasion",
          field: "occasion",
          editor: "select",
          editorParams: { values: PpcCrmData.occasions },
        },
        { title: "For Whom", field: "for_whom", editor: "input" },
        { title: "Final Type", field: "final_type", editor: "input" },
        { title: "Final Subtype", field: "final_subtype", editor: "input" },
        { title: "Main City", field: "main_city", editor: "input" },
        { title: "Store Location", field: "store_location", editor: "input" },
        { title: "Store Visit", field: "store_visit", editor: "input" },
        {
          title: "Visit Status",
          field: "store_visit_status",
          editor: "select",
          editorParams: { values: PpcCrmData.attempt_statuses },
        },
        { title: "Attempts", field: "attempts", editor: "input" },
        {
          title: "Attempt Type",
          field: "attempt_type",
          editor: "select",
          editorParams: { values: PpcCrmData.attempt_types },
        },
        { title: "Remarks", field: "remarks", editor: "textarea" },
        {
          title: "Save",
          formatter: function () {
            return "<button class='save-btn'>Save</button>";
          },
          cellClick: function (e, cell) {
            var data = cell.getRow().getData();
            data.action = "ppc_crm_save";
            data.nonce = PPC_CRM_Ajax.nonce;
            data.type = type.replace("_data", "");
            $.post(PPC_CRM_Ajax.url, data, function (resp) {
              if (resp.success) {
                alert("Saved!");
              } else {
                alert("Error: " + resp.data);
              }
            });
          },
        },
      ],
    });

    // If PPC role, wire up client-filter dropdown
    if (type === "lead_data" && $("#ppc_crm_lead_client_filter").length) {
      $("#ppc_crm_lead_client_filter").on("change", function () {
        table.setData(PPC_CRM_Ajax.url, {
          action: "ppc_crm_load",
          nonce: PPC_CRM_Ajax.nonce,
          type: "lead",
          client: $(this).val(),
        });
      });
    }
    if (
      type === "campaign_data" &&
      $("#ppc_crm_campaign_client_filter").length
    ) {
      $("#ppc_crm_campaign_client_filter").on("change", function () {
        table.setData(PPC_CRM_Ajax.url, {
          action: "ppc_crm_load",
          nonce: PPC_CRM_Ajax.nonce,
          type: "campaign",
          client: $(this).val(),
        });
      });
    }
  }

  // Initialize both tables
  initTable("lead_data");
  initTable("campaign_data");
  // after table is initialized…
  $("#ppc_crm_add_lead").on("click", function () {
    table
      .addRow({}, true) // true to add at top
      .then((row) => row.getCell("uid").edit());
  });
});
