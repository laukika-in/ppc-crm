document.addEventListener("DOMContentLoaded", () => {
  /* EXIT if shortcode not on page ------------------------------------- */
  const tblDiv = document.getElementById("lcm-lead-tbl");
  if (!tblDiv) return;

  /* 1. Inject “Add Lead” button + wrapper ----------------------------- */
  const btn = document.createElement("button");
  btn.textContent = "➕ Add Lead";
  btn.className = "lcm-add-btn";
  tblDiv.parentNode.insertBefore(btn, tblDiv);

  /* 2. Column helpers ------------------------------------------------- */
  const sel = (v) => ({ editor: "select", editorParams: { values: v } });
  const num = { hozAlign: "right", editor: "number" };

  /* 3. Build grid ----------------------------------------------------- */
  const grid = new Tabulator("#lcm-lead-tbl", {
    ajaxURL: LCM.ajax_url,
    ajaxParams: { action: LCM.action, nonce: LCM.nonce },
    layout: "fitColumns",
    height: "600px",
    placeholder: "No Leads yet",
    columns: [
      { title: "UID", field: "uid", editor: "input", validator: ["required"] },
      { title: "Ad Name", field: "ad_name", editor: "input" },
      {
        title: "Adset",
        field: "adset",
        editor: "input",
        validator: ["required"],
      },
      { title: "Attempt", field: "attempt", ...num },
      {
        title: "Attempt Type",
        field: "attempt_type",
        ...sel([
          "Connected:Relevant",
          "Connected:Not Relevant",
          "Not Connected",
        ]),
      },
      {
        title: "Attempt Status",
        field: "attempt_status",
        ...sel([
          "Store Visit Scheduled",
          "Call Rescheduled",
          "Just browsing",
          "Not Interested",
          "Ringing / No Response",
          "Wrong Number / Invalid Number",
        ]),
      },
      {
        title: "Store Visit Status",
        field: "store_visit_status",
        ...sel(["Show", "No Show"]),
      },
      {
        title: "Lead Date",
        field: "lead_date",
        formatter: "datetime",
        formatterParams: { outputFormat: "YYYY-MM-DD" },
      },
      { title: "Name", field: "name", editor: "input" },
      { title: "Phone", field: "phone_number", editor: "input" },
    ],
  });

  /* 4. Add-row button -------------------------------------------------- */
  btn.addEventListener("click", () => {
    grid.addRow({ id: null }, true); // true = add at top
  });

  /* 5. Autosave when a new row gets edited ---------------------------- */
  grid.on("cellEdited", async (cell) => {
    const row = cell.getRow();
    const data = row.getData();

    // Only push rows that don't yet have an ID (newly added)
    if (data.id !== null) return;

    // Require at least UID + Adset
    if (!data.uid || !data.adset) return;

    // Disable UI while saving
    row.getElement().style.opacity = 0.5;

    const body = new URLSearchParams({
      action: "lcm_create_lead",
      nonce: LCM.nonce,
      uid: data.uid,
      adset: data.adset,
      attempt: data.attempt || "",
      attempt_type: data.attempt_type || "",
      attempt_status: data.attempt_status || "",
      store_status: data.store_visit_status || "",
    });

    try {
      const r = await fetch(LCM.ajax_url, { method: "POST", body });
      const j = await r.json();

      if (j.success) {
        grid.replaceData(); // reload entire dataset
      } else {
        alert(j.data.msg || "Save error");
        row.getElement().style.opacity = 1;
      }
    } catch (e) {
      alert("Network error");
      row.getElement().style.opacity = 1;
    }
  });
});
