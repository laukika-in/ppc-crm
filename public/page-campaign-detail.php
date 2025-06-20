<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$campaign_id = intval( $_GET['campaign_id'] ?? 0 );
$campaign = get_post( $campaign_id );
if ( ! $campaign || $campaign->post_type !== 'lcm_campaign' ) {
  echo '<div class="alert alert-danger">Invalid campaign.</div>';
  return;
}

wp_enqueue_style( 'bootstrap-css' );
wp_enqueue_style( 'bootstrap-icons' );
wp_enqueue_script( 'bootstrap-js' );
wp_enqueue_script( 'flatpickr-js' );
wp_enqueue_style( 'flatpickr-css' );
wp_enqueue_script( 'flatpickr-init' );
wp_enqueue_script( 'campaign-detail' );

wp_localize_script( 'campaign-detail', 'LCM_AJAX', [
  'ajax_url' => admin_url( 'admin-ajax.php' ),
  'nonce' => wp_create_nonce( 'lcm_ajax' ),
  'campaign_id' => $campaign_id,
]);
?>

<div class="container mt-4">
  <h4 class="mb-3">Campaign Detail: <?= esc_html( get_the_title( $campaign ) ); ?></h4>

  <div class="row mb-3">
    <div class="col-md-3">
      <label>Month</label>
      <select id="filter-month" class="form-select form-select-sm">
        <option value="">All Months</option>
        <?php foreach ([ 'January','February','March','April','May','June','July','August','September','October','November','December' ] as $m): ?>
          <option value="<?= esc_attr($m) ?>"><?= esc_html($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label>From Date</label>
      <input type="date" id="filter-from" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
      <label>To Date</label>
      <input type="date" id="filter-to" class="form-control form-control-sm">
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button class="btn btn-primary btn-sm" id="apply-filters">Apply Filter</button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-sm" id="daily-detail-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Leads</th>
          <th>Reach</th>
          <th>Impressions</th>
          <th>Amount Spent (INR)</th>
          <th>Connected</th>
          <th>Not Connected</th>
          <th>Relevant</th>
          <th>Not Relevant</th>
          <th>N/A</th>
          <th>Visit Scheduled</th>
          <th>Visit</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
