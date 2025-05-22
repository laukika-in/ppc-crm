(function(){
  const buildCols = (data, type) => {
    const dropdowns = PPC_CRM.dropdowns[type] || {};
    return Object.keys(data[0]).filter(k=>k!=='id').map(f=>{
      const col = { title: f.replace(/_/g,' ').toUpperCase(), field:f };
      if(dropdowns[f]){
        col.editor = 'select';
        col.editorParams = { values: dropdowns[f] };
      }else{
        col.editor = 'input';
      }
      return col;
    });
  };

  const init = (selector, url, type) => {
    fetch(url, { headers:{'X-WP-Nonce':PPC_CRM.nonce} })
      .then(r=>r.json())
      .then(rows=>{
        if(!rows.length) return;
        new Tabulator(selector, {
          data: rows,
          layout:"fitDataStretch",
          columns: buildCols(rows, type),
          cellEdited: cell => {
            const row = cell.getRow().getData();
            fetch(`${url}/${row.id}`, {
              method:'POST',
              headers:{'Content-Type':'application/json','X-WP-Nonce':PPC_CRM.nonce},
              body: JSON.stringify(row)
            });
          }
        });
      });
  };

  document.addEventListener('DOMContentLoaded', ()=>{
    const leadEl = document.getElementById('ppc-crm-leads');
    if(leadEl) init('#ppc-crm-leads', PPC_CRM.rest.leads, 'lead');
    const campEl = document.getElementById('ppc-crm-camps');
    if(campEl) init('#ppc-crm-camps', PPC_CRM.rest.camps, 'campaign');
  });
})();