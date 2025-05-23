document.addEventListener("DOMContentLoaded", () => {
  const host = document.getElementById("lcm-lead-tbl");
  if (!host) return;

  /* ---- Add button -------------------------------------------------- */
  const addBtn = document.createElement("button");
  addBtn.textContent = "➕ Add Lead";
  addBtn.className = "lcm-add-btn";
  host.before(addBtn);

  /* ---- Column helpers -------------------------------------------- */
  const sel = (v) => ({ editor: "select", editorParams: { values: v } });
  const num = { hozAlign: "right", editor: "number" };

  /* ---- Columns (all fields) -------------------------------------- */
  const cols = [
    { title: "Client", field: "client_id", ...sel(LCM.clients) },
    { title: "Ad Name", field: "ad_name", ...sel(LCM.ad_names) },
    { title: "Adset", field: "adset", editor: "input" },
    { title: "UID", field: "uid", editor: "input", validator: ["required"] },
    { title: "Date", field: "lead_date", editor: "input" },
    { title: "Time", field: "lead_time", editor: "input" },
    {
      title: "Day",
      field: "day",
      ...sel([
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
      ]),
    },
    { title: "Name", field: "name", editor: "input" },
    { title: "Phone", field: "phone_number", editor: "input" },
    { title: "Alt Phone", field: "alt_number", editor: "input" },
    { title: "Email", field: "email", editor: "input" },
    { title: "Location", field: "location", editor: "input" },
    {
      title: "Client Type",
      field: "client_type",
      ...sel(["Existing Client", "New Client"]),
    },
    { title: "Sources", field: "sources", editor: "input" },
    { title: "Source Campaign", field: "source_of_campaign", editor: "input" },
    { title: "Targeting", field: "targeting_of_campaign", editor: "input" },
    { title: "Budget", field: "budget", editor: "input" },
    { title: "Product", field: "product_looking_to_buy", editor: "input" },
    {
      title: "Occasion",
      field: "occasion",
      ...sel([
        "Anniversary",
        "Birthday",
        "Casual Occasion",
        "Engagement/Wedding",
        "Gifting",
        "N/A",
      ]),
    },
    { title: "For Whom", field: "for_whom", editor: "input" },
    { title: "Final Type", field: "final_type", editor: "input" },
    { title: "Final Sub", field: "final_sub_type", editor: "input" },
    { title: "Main City", field: "main_city", editor: "input" },
    { title: "Store Loc", field: "store_location", editor: "input" },
    { title: "Store Visit", field: "store_visit", editor: "input" },
    {
      title: "Store Visit Status",
      field: "store_visit_status",
      ...sel(["Show", "No Show"]),
    },
    { title: "Attempt", field: "attempt", ...sel([1, 2, 3, 4, 5, 6]) },
    {
      title: "Attempt Type",
      field: "attempt_type",
      ...sel(["Connected:Not Relevant", "Connected:Relevant", "Not Connected"]),
    },
    {
      title: "Attempt Status",
      field: "attempt_status",
      ...sel([
        "Call Rescheduled",
        "Just browsing",
        "Not Interested",
        "Ringing / No Response",
        "Store Visit Scheduled",
        "Wrong Number / Invalid Number",
      ]),
    },
    { title: "Remarks", field: "remarks", editor: "input" },
  ];

  /* ---- Build grid -------------------------------------------------- */
  const grid = new Tabulator(host, {
    ajaxURL: LCM.ajax_url,
    ajaxParams: { action: LCM.action, nonce: LCM.nonce },
    layout: "fitColumns",
    height: "600px",
    columns: cols,
    placeholder: "No Leads yet",
  });

  /* ---- Add row handler -------------------------------------------- */
  addBtn.addEventListener("click", () => grid.addRow({ id: null }, true));

  /* ---- Autosave new rows ------------------------------------------ */
  grid.on("cellEdited", async (cell) => {
    const row = cell.getRow();
    const data = row.getData();
    if (data.id !== null) return; // existing row

    if (!data.uid || !data.adset) return; // require keys first

    row.getElement().style.opacity = 0.5;

    const body = new URLSearchParams({
      action: "lcm_create_lead",
      nonce: LCM.nonce,
    });
    cols.forEach((c) => body.append(c.field, data[c.field] ?? ""));

    try {
      const r = await fetch(LCM.ajax_url, { method: "POST", body });
      const j = await r.json();
      if (!j.success) throw new Error(j.data?.msg || "Save failed");
      grid.setData(LCM.ajax_url, { action: LCM.action, nonce: LCM.nonce });
    } catch (e) {
      alert(e.message || "Network error");
      row.getElement().style.opacity = 1;
    }
  });
});
