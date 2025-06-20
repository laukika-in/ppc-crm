<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// ✅ Step 1: campaign_id from GET is actually the WP post_id
$campaign_post_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$year = intval(substr($current_month, 0, 4));
$month = intval(substr($current_month, 5, 2));

if (!$campaign_post_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}

// ✅ Step 2: Query lcm_leads where campaign_id matches post_id
$rows = $wpdb->get_results($wpdb->prepare("
    SELECT 
        lead_date AS date,
        COUNT(*) AS total,
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
", $campaign_post_id, $month, $year));
echo "<pre>Debug Dump:\n";
var_dump($rows);
echo "</pre>";
?>

<div class="wrap">
  <h2>📊 Campaign Daily Metrics – <?= date("F Y", strtotime($current_month . "-01")) ?></h2>

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
            <th>Reach</th>
            <th>Impressions</th>
            <th>Amount Spent</th>
            <th>Connected</th>
            <th>Not Connected</th>
            <th>Relevant</th>
            <th>N/A</th>
            <th>Scheduled Visit</th>
            <th>Store Visit</th>
            <th>View Leads</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) : ?>
            <tr>
              <td><?= esc_html($r->date) ?></td>
              <td><?= intval($r->reach) ?></td>
              <td><?= intval($r->impressions) ?></td>
              <td><?= floatval($r->amount_spent) ?></td>
              <td><?= intval($r->connected) ?></td>
              <td><?= intval($r->not_connected) ?></td>
              <td><?= intval($r->relevant) ?></td>
              <td><?= intval($r->not_available) ?></td>
              <td><?= intval($r->scheduled_visit) ?></td>
              <td><?= intval($r->store_visit) ?></td>
              <td>
                <a href="<?= admin_url('admin.php?page=ppc-leads&campaign_id=' . $campaign_post_id . '&lead_date=' . $r->date); ?>" class="btn btn-sm btn-outline-primary">View</a>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
