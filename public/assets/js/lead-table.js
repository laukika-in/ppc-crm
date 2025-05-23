jQuery(function ($) {
  /* ------------------------------------------------ columns ---------- */
  const cols = [
    ["_action", "Action", "action"],
    ["client_id", "Client", "select", LCM.clients],
    ["ad_name", "Ad Name", "text"],
    ["adset", "Adset", "select", LCM.adsets],
    ["uid", "UID", "text"],
    ["lead_date", "Date", "date"],
    ["lead_time", "Time", "time"],
    [
      "day",
      "Day",
      "select",
      [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
      ],
    ],
    ["phone_number", "Phone", "number"],
    ["attempt", "Attempt", "select", [1, 2, 3, 4, 5, 6]],
    [
      "attempt_type",
      "Attempt Type",
      "select",
      ["Connected:Not Relevant", "Connected:Relevant", "Not Connected"],
    ],
    [
      "attempt_status",
      "Attempt Status",
      "select",
      [
        "Call Rescheduled",
        "Just browsing",
        "Not Interested",
        "Ringing / No Response",
        "Store Visit Scheduled",
        "Wrong Number / Invalid Number",
      ],
    ],
    ["store_visit_status", "Store Visit", "select", ["Show", "No Show"]],
    ["remarks", "Remarks", "text"],
  ];

  const $thead = $("#lcm-lead-table thead");
  const $tbody = $("#lcm-lead-table tbody");
  const $pager = $("#lcm-pager");
  const per = LCM.per_page;
  let page = 1;

  /* ---------------- header ------------------------------------------ */
  $thead.html("<tr>" + cols.map((c) => `<th>${c[1]}</th>`).join("") + "</tr>");

  const opts = (arr, cur = "") =>
    "<option value=''></option>" +
    arr
      .map((o) => {
        const v = Array.isArray(o) ? o[0] : o,
          t = Array.isArray(o) ? o[1] : o;
        return `<option value="${v}"${
          v == cur ? " selected" : ""
        }>${t}</option>`;
      })
      .join("");

  /* ---------------- row builder ------------------------------------- */
  function rowHtml(r = {}) {
    let h = `<tr data-id="${r.id || ""}"${
      r.id ? "" : " class='table-warning'"
    }>`;
    cols.forEach(([f, _, type, arr]) => {
      const v = r[f] || "";
      const dis =
        (f === "attempt_type" && !r.attempt) ||
        (f === "attempt_status" && !r.attempt_type) ||
        (f === "store_visit_status" &&
          r.attempt_status !== "Store Visit Scheduled")
          ? " disabled"
          : "";
   const disabled = r.id ? " disabled" : "";   // ← the one-liner flag

     if (type === "action") {

  if (!r.id) { // draft
    html += `<td class="text-center">
               <button class="btn btn-success btn-sm save-row me-1">💾</button>
               <button class="btn btn-danger  btn-sm del-row">🗑</button>
             </td>`;
  } else {     // saved
    html += `<td class="text-center">
               <button class="btn btn-secondary btn-sm edit-row me-1">✏️</button>
               <button class="btn btn-danger   btn-sm del-row" data-id="${r.id}">🗑</button>
             </td>`;
  }
}
else if (type === "select") {
      html += `<td>
        <select class="form-select form-select-sm" data-name="${field}"${disabled}>
          ${opts(options, val)}
        </select>
      </td>`;
    } else if (type === "date") {
      html += `<td>
        <input type="date" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    } else if (type === "time") {
      html += `<td>
        <input type="time" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    } else if (type === "number") {
      html += `<td>
        <input type="number" step="any" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    } else if (type === "readonly") {
      html += `<td>${val}</td>`;
    } else { // plain text
      html += `<td>
        <input type="text" class="form-control form-control-sm"
               data-name="${field}" value="${val}"${disabled}>
      </td>`;
    }
  });

  html += "</tr>";
  return html;
}

  /* ---------------- pagination -------------------------------------- */
  function load(p = 1) {
    $.getJSON(
      LCM.ajax_url,
      {
        action: "lcm_get_leads_json",
        nonce: LCM.nonce,
        page: p,
        per_page: per,
      },
      (res) => {
        page = p;
        $tbody.html(res.rows.map(rowHtml).join(""));
        buildPager(res.total);
      }
    );
  }
  function buildPager(total) {
    const pages = Math.max(1, Math.ceil(total / per));
    $pager.html(
      Array.from({ length: pages }, (_, i) => {
        const n = i + 1;
        return `<button class="btn btn-outline-secondary ${
          n === page ? "active" : ""
        }" data-p="${n}">${n}</button>`;
      }).join("")
    );
  }
  $pager.on("click", "button", (e) => load(+e.currentTarget.dataset.p));

  /* ---------------- add blank row ----------------------------------- */
  $("#lcm-add-row").on("click", () => {
    $tbody.prepend(rowHtml({}));
  });

  /* ---------------- helpers ----------------------------------------- */
  function collect($tr) {
    const d = {};
    $tr.find("input,select").each(function () {
      d[this.dataset.name] = $(this).val();
    });
    return d;
  }
  function toggleDependencies($tr) {
    const a = $tr.find("[data-name=attempt]").val();
    const t = $tr.find("[data-name=attempt_type]").val();
    const s = $tr.find("[data-name=attempt_status]").val();
    $tr.find("[data-name=attempt_type]").prop("disabled", !a);
    $tr.find("[data-name=attempt_status]").prop("disabled", !t);
    $tr
      .find("[data-name=store_visit_status]")
      .prop("disabled", s !== "Store Visit Scheduled");
  }

  /* ---------------- dynamic enabling -------------------------------- */
  $tbody.on(
    "change",
    "select[data-name=attempt], select[data-name=attempt_type], select[data-name=attempt_status]",
    function () {
      const $tr = $(this).closest("tr");
      toggleDependencies($tr);
    }
  );

  /* ---------------- auto-fill day ----------------------------------- */
  $tbody.on("change", "input[type=date]", function () {
    const d = new Date(this.value + "T12:00:00");
    if (!isNaN(d)) {
      const day = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ][d.getDay()];
      $(this).closest("tr").find("select[data-name=day]").val(day);
    }
  });

  /* ---------------- save button ------------------------------------- */
  $tbody.on("click", ".save-row", function () {
    const $tr = $(this).closest("tr");
    const d = collect($tr);
    d.action = "lcm_create_lead";
    d.nonce = LCM.nonce;
    if (!d.uid || !d.adset) {
      alert("UID & Adset required");
      return;
    }
    $.post(
      LCM.ajax_url,
      d,
      (res) => {
        res.success ? load(page) : alert(res.data.msg || "Save failed");
      },
      "json"
    );
  });

  /* ---------------- delete (modal) ---------------------------------- */
  let delId = 0;
  const delModal = new bootstrap.Modal("#lcmDelModal");

  $tbody.on("click", ".del-row", function () {
    delId = $(this).data("id") || 0;
    if (!delId) {
      $(this).closest("tr").remove();
      return;
    }
    delModal.show();
  });

  $("#lcm-confirm-del").on("click", function () {
    $.post(
      LCM.ajax_url,
      { action: "lcm_delete_lead", nonce: LCM.nonce, id: delId },
      (res) => {
        if (res.success) {
          const total = res.data.total;
          const pages = Math.max(1, Math.ceil(total / per));
          if (page > pages) page = pages;
          load(page);
        } else alert(res.data.msg || "Delete failed");
        delModal.hide();
      },
      "json"
    );
  });
public function update_lead() {

	$this->verify();
	global $wpdb;

	$id = absint( $_POST['id'] ?? 0 );
	if ( ! $id ) wp_send_json_error( [ 'msg'=>'Missing id' ], 400 );

	$cols = [
		'ad_name','adset','lead_date','lead_time','day','phone_number',
		'attempt','attempt_type','attempt_status','store_visit_status','remarks'
	];
	$data = [];
	foreach ( $cols as $c ) {
		if ( isset( $_POST[$c] ) ) $data[$c] = sanitize_text_field( $_POST[$c] );
	}
	if ( empty( $data ) ) wp_send_json_success();  // nothing to update

	$wpdb->update( $wpdb->prefix.'lcm_leads', $data, [ 'id'=>$id ] );
	wp_send_json_success();
}

  /* initial load */
  load(1);
});
