<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$campaign_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$year = substr($current_month, 0, 4);
$month = substr($current_month, 5, 2);

if (!$campaign_id) {
  echo "<div class='notice notice-error'>Invalid Campaign</div>";
  return;
}

$results = $wpdb->get_results($wpdb->prepare("
    SELECT 
        lead_date AS date,
        COUNT(*) AS total_leads,
        SUM(reach) AS reach,
        SUM(impressions) AS impressions,
        SUM(amount_spent) AS amount_spent,
        SUM(connected_number) AS connected,
        SUM(not_connected) AS not_connected,
        SUM(relevant) AS relevant,
        SUM(not_available) AS not_available,
        SUM(scheduled_store_visit) AS scheduled_visit,
        SUM(store_visit) AS store_visit
    FROM {$wpdb->prefix}lcm_leads
    WHERE campaign_id = %d
      AND MONTH(lead_date) = %d
      AND YEAR(lead_date) = %d
    GROUP BY lead_date
    ORDER BY lead_date DESC
", $campaign_id, $month, $year));

?>

<div class="wrap">
  <h2>📅 Campaign Details - <?= date("F Y", strtotime("$current_month-01")) ?></h2>

  <form method="get" class="row g-3 align-items-center mb-3">
    <input type="hidden" name="page" value="campaign-detail">
    <input type="hidden" name="campaign_id" value="<?= esc_attr($campaign_id); ?>">
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

  <?php if (!$results): ?>
    <div class="notice notice-warning">No data found for this campaign in <?= date("F Y", strtotime("$current_month-01")) ?>.</div>
  <?php else: ?>
    <div class="table-responsive lcm-scroll">
      <table class="table table-bordered table-striped table-sm lcm-table mb-0" style="min-width:1200px;">
        <thead>
          <tr>
            <th>Date</th>
            <th>Reach</th>
            <th>Impressions</th>
            <th>Amount Spent</th>
            <th>Connected</th>
            <th>Not Connected</th>
            <th>Relevant</th>
            <th>N/A</th>
            <th>Sched Visit</th>
            <th>Store Visit</th>
            <th>View Leads</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $row): ?>
            <tr>
              <td><?= esc_html($row->date) ?></td>
              <td><?= intval($row->reach) ?></td>
              <td><?= intval($row->impressions) ?></td>
              <td><?= floatval($row->amount_spent) ?></td>
              <td><?= intval($row->connected) ?></td>
              <td><?= intval($row->not_connected) ?></td>
              <td><?= intval($row->relevant) ?></td>
              <td><?= intval($row->not_available) ?></td>
              <td><?= intval($row->scheduled_visit) ?></td>
              <td><?= intval($row->store_visit) ?></td>
              <td>
                <a class="btn btn-sm btn-secondary"
                   href="<?= admin_url('admin.php?page=ppc-leads&campaign_id=' . $campaign_id . '&lead_date=' . $row->date); ?>">
                   View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
