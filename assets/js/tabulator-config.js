export function dropdownEditor(values){
  return function(cell, onRendered, success, cancel){
    var select = document.createElement('select');
    values.forEach(v=>{ let o=document.createElement('option'); o.value=v; o.text=v; select.appendChild(o); });
    select.value = cell.getValue();
    select.addEventListener('change', ()=> success(select.value));
    cell.getElement().innerHTML='';
    cell.getElement().appendChild(select);
  };
}