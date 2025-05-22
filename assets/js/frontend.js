jQuery(function($){
    function initTable(type){
        var table = new Tabulator("#"+type+"_table", {
            ajaxURL: PPC_CRM_Ajax.url,
            ajaxParams: { action: 'ppc_crm_load', nonce: PPC_CRM_Ajax.nonce, type: type.replace('_data','') },
            layout:"fitColumns",
            columns: [
                {title:"UID", field:"uid", editor:"input"},
                // ... map all fields dynamically ...
                {title:"Save", formatter:function(){
                    return "<button class='save-btn'>Save</button>";
                }, cellClick:function(e,cell){
                    var data = cell.getRow().getData();
                    data.action = 'ppc_crm_save';
                    data.nonce  = PPC_CRM_Ajax.nonce;
                    data.type   = type.replace('_data','');
                    $.post(PPC_CRM_Ajax.url, data, function(resp){
                        if(resp.success) alert("Saved!");
                    });
                }}
            ],
        });
    }

    initTable('lead_data');
    initTable('campaign_data');
});
