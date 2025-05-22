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
        { title: "UID", field: "uid", editor: "input" },
        // …add one column definition per field here…
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
});
