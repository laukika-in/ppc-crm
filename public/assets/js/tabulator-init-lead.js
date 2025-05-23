document.addEventListener("DOMContentLoaded", function () {
  /* EXIT if shortcode not on page */
  const tbl = document.getElementById("lcm-lead-tbl");
  if (!tbl) return;

  /* 1. Inject ‘Add’ button */
  const btn = document.createElement("button");
  btn.textContent = "➕ Add Lead";
  btn.className = "lcm-add-btn";
  tbl.parentNode.insertBefore(btn, tbl);

  /* 2. Build Tabulator */
  const grid = new Tabulator("#lcm-lead-tbl", {
    ajaxURL: LCM.ajax_url,
    ajaxParams: { action: LCM.action, nonce: LCM.nonce },
    layout: "fitColumns",
    height: "600px",
    pagination: false,
    columns: [
      { title: "UID", field: "uid", headerFilter: "input" },
      { title: "Ad Name", field: "ad_name", headerFilter: "input" },
      { title: "Adset", field: "adset", headerFilter: "input" },
      { title: "Attempt", field: "attempt", hozAlign: "right" },
      { title: "Attempt Type", field: "attempt_type" },
      { title: "Attempt Status", field: "attempt_status" },
      { title: "Store Visit Status", field: "store_visit_status" },
      { title: "Lead Date", field: "lead_date" },
      { title: "Lead Time", field: "lead_time" },
      { title: "Name", field: "name", headerFilter: "input" },
      { title: "Phone", field: "phone_number" },
    ],
  });

  /* 3. Prompt helper -------------------------------------------------- */
  const promptVal = (label, def = "") => {
    const v = prompt(label, def);
    return v === null ? null : v.trim();
  };

  /* 4. Click handler --------------------------------------------------- */
  btn.addEventListener("click", async () => {
    const uid = promptVal("UID");
    if (!uid) return;
    const adset = promptVal("Adset");
    if (!adset) return;

    const attempt = promptVal("Attempt (1-6)");
    if (!attempt) return;
    const type = promptVal(
      "Attempt Type (Connected:Relevant / Connected:Not Relevant / Not Connected)"
    );
    if (!type) return;
    const status = promptVal("Attempt Status (Store Visit Scheduled / ...)");
    if (!status) return;
    const store = promptVal("Store Visit Status (Show / No Show)", "No Show");
    if (store === null) return;

    btn.disabled = true;
    btn.textContent = "Saving…";

    const body = new URLSearchParams({
      action: "lcm_create_lead",
      nonce: LCM.nonce,
      uid,
      adset,
      attempt,
      attempt_type: type,
      attempt_status: status,
      store_status: store,
    });

    try {
      const r = await fetch(LCM.ajax_url, { method: "POST", body });
      const j = await r.json();
      if (!j.success) {
        alert(j.data.msg || "Error");
      } else {
        grid.replaceData(); // reload Ajax data
      }
    } catch (e) {
      alert("Network error");
    }

    btn.disabled = false;
    btn.textContent = "➕ Add Lead";
  });
});
