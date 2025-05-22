// Extend Tabulator’s built-in modules if you need custom formatters/editors.
// Here’s an example stub – you can expand as needed.
Tabulator.prototype.extendModule("format", "formatters", {
  datePickerEditorFormatter: function (cell, formatterParams, onRendered) {
    return cell.getValue();
  },
});
