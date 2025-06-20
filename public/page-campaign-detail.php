<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// Step 1: Get campaign's post ID
$campaign_post_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$year = intval(substr($current_month, 0, 4));
$month = intval(substr($current_month, 5, 2));

if (!$campaign_post_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}

// Step 2: Fetch leads for that campaign and month
// 🧠 SQL: get daily totals with SUM for all specified fields
$rows = $wpdb->get_results($wpdb->prepare("
    SELECT 
        lead_date AS date,
        COUNT(*) AS total,
        SUM(connected_number) AS connected,
        SUM(relevant) AS relevant,
        SUM(not_connected) AS not_connected,
        SUM(not_relevant) AS not_relevant,
        SUM(not_available) AS not_available,
        SUM(scheduled_store_visit) AS scheduled_store_visit,
        SUM(store_visit) AS store_visit
    FROM {$wpdb->prefix}lcm_leads
    WHERE campaign_id = %d
      AND MONTH(lead_date) = %d
      AND YEAR(lead_date) = %d
    GROUP BY lead_date
    ORDER BY lead_date DESC
", $campaign_post_id, $month, $year));

?>

<div class="wrap">
  <h2>📅 Daily Lead Count – <?= date("F Y", strtotime($current_month . "-01")) ?></h2>

  <form method="get" class="row g-3 align-items-center mb-3">
    <input type="hidden" name="page" value="campaign-detail">
    <input type="hidden" name="campaign_id" value="<?= esc_attr($campaign_post_id); ?>">
    <div class="col-auto">
      <label for="month" class="col-form-label">Select Month:</label>
    </div>
    <div class="col-auto">
      <input type="month" id="month" name="month" class="form-control" value="<?= esc_attr($current_month); ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Go</button>
    </div>
  </form>

  <?php if (empty($rows)) : ?>
    <div class="alert alert-warning">No leads found for this campaign in <?= date("F Y", strtotime($current_month . "-01")) ?>.</div>
  <?php else : ?>
 <div class="table-responsive lcm-scroll">
  <table class="table table-bordered table-striped table-sm lcm-table mb-0" style="min-width:1200px;">
    <thead>
      <tr>
        <th>Date</th>
        <th>Total Leads</th>
        <th>Connected</th>
        <th>Relevant</th>
        <th>Not Connected</th>
        <th>Not Relevant</th>
        <th>Not Available</th>
        <th>Scheduled Visit</th>
        <th>Store Visit</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r) : ?>
        <tr>
          <td><?= esc_html($r->date) ?></td>
          <td><?= intval($r->total) ?></td>
          <td><?= intval($r->connected) ?></td>
          <td><?= intval($r->relevant) ?></td>
          <td><?= intval($r->not_connected) ?></td>
          <td><?= intval($r->not_relevant) ?></td>
          <td><?= intval($r->not_available) ?></td>
          <td><?= intval($r->scheduled_store_visit) ?></td>
          <td><?= intval($r->store_visit) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

  <?php endif ?>
</div>
