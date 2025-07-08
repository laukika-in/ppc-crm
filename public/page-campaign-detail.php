<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$campaign_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$from = sanitize_text_field($_GET['from'] ?? '');
$to = sanitize_text_field($_GET['to'] ?? '');

if (!$campaign_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}
?>

<div class="wrap">
  <h2>📊 Campaign Daily Report</h2>

  <!-- Filters -->
  <form id="campaign-detail-filters" method="get" class="row g-2 align-items-center mb-3">
    <input type="hidden" name="page" value="campaign-detail">
    <input type="hidden" name="campaign_id" value="<?= esc_attr($campaign_id); ?>">

    <div class="col-auto">
      <label for="month" class="form-label">Month:</label>
      <input type="month" id="month" name="month" class="form-control" value="<?= esc_attr($current_month); ?>">
    </div>
    <div class="col-auto">
      <label for="from" class="form-label">From:</label>
      <input type="text" id="lcm-filter-date-from" class="form-control form-control-sm flatpickr-date" value="<?= esc_attr($from); ?>" placeholder="From date">
    </div>
    <div class="col-auto">
      <label for="to" class="form-label">To:</label>
      <input type="text" id="lcm-filter-date-to" class="form-control form-control-sm flatpickr-date" value="<?= esc_attr($to); ?>" placeholder="To date">
    </div>
  </form>

  <!-- Summary -->
  <div class="card mb-4" style="max-width:1000px">
    <div class="card-body">
      <div class="row text-center">
        <div class="col"><strong>Total Leads:</strong><br><span>0</span></div>
        <div class="col"><strong>Connected:</strong><br><span>0</span></div>
        <div class="col"><strong>Relevant:</strong><br><span>0</span></div>
        <div class="col"><strong>Not Relevant:</strong><br><span>0</span></div>
        <div class="col"><strong>Not Connected:</strong><br><span>0</span></div>
        <div class="col"><strong>N/A:</strong><br><span>0</span></div>
        <div class="col"><strong>Scheduled Visit:</strong><br><span>0</span></div>
        <div class="col"><strong>Store Visit:</strong><br><span>0</span></div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-responsive lcm-scroll">
    <table class="table table-bordered table-striped table-sm lcm-table mb-0" style="min-width:1200px;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Total Leads</th>
          <th>Reach</th>
          <th>Impressions</th>
          <th>Amount Spent (INR)</th>
          <th>Action</th>
          <th>Connected</th>
          <th>Relevant</th>
          <th>Not Relevant</th>
          <th>Not Connected</th>
          <th>N/A</th>
          <th>Scheduled Visit</th>
          <th>Store Visit</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
