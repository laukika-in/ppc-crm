// Basic Tabulator defaults
Tabulator.prototype.extendModule("format", "formatters", {
    datePickerEditorFormatter: function(cell, formatterParams, onRendered){
        return cell.getValue();
    }
});
