jQuery(function ($) {
  const AJAX = LCM.ajax_url;
  const NONCE = LCM.nonce;
  const PER_PAGE = 32;

  // Mount
  const $mount = $("#lcm-daily-tracker");
  $mount.html(`
    <div class="d-flex align-items-center mb-3">
      <input id="dt-month" class="form-control form-control-sm me-2" type="month"/>
      <input id="dt-from"  class="form-control form-control-sm me-2" type="date"   />
      <input id="dt-to"    class="form-control form-control-sm"     type="date"   />
    </div>
    <div class="lcm-summary row mb-4"></div>
    <div class="position-relative">
      <div class="lcm-preloader position-absolute top-0 start-0 w-100 h-100 d-none 
                  d-flex align-items-center justify-content-center bg-white bg-opacity-75">
        <div class="spinner-border"></div>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
          <thead>
            <tr>
              <th>Date</th><th>Reach</th><th>Impr</th><th>Spent</th>
              <th>Leads</th><th>Relevant</th><th>Not Relevant</th>
              <th>Not Connected</th><th>N/A</th><th>Connected</th>
              <th>Sched Visit</th><th>Store Visit</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <nav><ul class="pagination mt-2"></ul></nav>
    </div>
  `);

  // Flatpickr
  $("#dt-month").flatpickr({
    plugins: [new monthSelectPlugin({ dateFormat: "Y-m", altFormat: "F Y" })],
    defaultDate: new Date(),
  });
  $("#dt-from,#dt-to").flatpickr({ dateFormat: "Y-m-d", allowInput: true });

  // State
  let month = $("#dt-month").val(),
    from = $("#dt-from").val(),
    to = $("#dt-to").val(),
    page = 1;

  // Show/hide preloader
  function togglePreloader(show) {
    $mount.find(".lcm-preloader").toggleClass("d-none", !show);
  }

  // Fetch & render
  function reload() {
    togglePreloader(true);
    $.getJSON(AJAX, {
      action: "lcm_get_daily_tracker_rows",
      nonce: NONCE,
      month,
      from,
      to,
      page,
      per_page: PER_PAGE,
    })
      .always(() => {
        togglePreloader(false);
      })
      .done((res) => {
        renderSummary(res.data.summary);
        renderRows(res.data.rows);
        renderPager(res.data.total_days);
      });
  }

  // Summary
  function renderSummary(s) {
    const html = `
      <div class="col">Leads: ${s.total_leads}</div>
      <div class="col">Connected: ${s.connected}</div>
      <div class="col">Relevant: ${s.relevant}</div>
      <div class="col">Not Relevant: ${s.not_relevant}</div>
      <div class="col">Not Connected: ${s.not_connected}</div>
      <div class="col">N/A: ${s.not_available}</div>
      <div class="col">Scheduled: ${s.scheduled_visit}</div>
      <div class="col">Visited: ${s.store_visit}</div>
    `;
    $mount.find(".lcm-summary").html(html);
  }

  // Rows
  function renderRows(rows) {
    const $tb = $mount.find("tbody").empty();
    rows.forEach((r) => {
      const $tr = $("<tr>").appendTo($tb);
      $tr.append(`<td>${r.date}</td>`);
      $tr.append(`<td>${r.reach}</td>`);
      $tr.append(`<td>${r.impressions}</td>`);
      $tr.append(`<td>${r.amount_spent}</td>`);
      $tr.append(`<td>${r.total_leads}</td>`);
      $tr.append(`<td>${r.relevant}</td>`);
      $tr.append(`<td>${r.not_relevant}</td>`);
      $tr.append(`<td>${r.not_connected}</td>`);
      $tr.append(`<td>${r.not_available}</td>`);
      $tr.append(`<td>${r.connected_total}</td>`);
      $tr.append(`<td>${r.scheduled_visit}</td>`);
      $tr.append(`<td>${r.store_visit}</td>`);
    });
  }

  // Pagination
  function renderPager(totalDays) {
    const pages = Math.ceil(totalDays / PER_PAGE);
    const $ul = $mount.find(".pagination").empty();
    if (pages > 1) {
      for (let i = 1; i <= pages; i++) {
        $ul.append(`
          <li class="page-item ${i === page ? "active" : ""}">
            <a class="page-link" href="#">${i}</a>
          </li>`);
      }
    }
  }
  $mount.on("click", ".page-link", function (e) {
    e.preventDefault();
    page = +$(this).text();
    reload();
  });

  // Filter handlers
  $("#dt-month").on("change", () => {
    month = $("#dt-month").val();
    page = 1;
    reload();
  });
  $("#dt-from,#dt-to").on("change", () => {
    from = $("#dt-from").val();
    to = $("#dt-to").val();
    page = 1;
    reload();
  });

  // Initial load
  reload();
});
