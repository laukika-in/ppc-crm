<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$campaign_post_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$from_date = sanitize_text_field($_GET['from'] ?? '');
$to_date = sanitize_text_field($_GET['to'] ?? '');

if (!$campaign_post_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}

$where = $wpdb->prepare("WHERE campaign_id = %d", $campaign_post_id);
if ($from_date && $to_date) {
    $where .= $wpdb->prepare(" AND lead_date BETWEEN %s AND %s", $from_date, $to_date);
} else {
    $year = intval(substr($current_month, 0, 4));
    $month = intval(substr($current_month, 5, 2));
    $where .= $wpdb->prepare(" AND MONTH(lead_date) = %d AND YEAR(lead_date) = %d", $month, $year);
}

$rows = $wpdb->get_results("SELECT 
    lead_date AS date,
    COUNT(*) AS total_leads,
    SUM(CASE WHEN attempt_type = 'Connected:Relevant' THEN 1 ELSE 0 END) AS relevant,
    SUM(CASE WHEN attempt_type = 'Connected:Not Relevant' THEN 1 ELSE 0 END) AS not_relevant,
    SUM(CASE WHEN attempt_type = 'Not Connected' THEN 1 ELSE 0 END) AS not_connected,
    SUM(CASE WHEN attempt_status = 'Store Visit Scheduled' THEN 1 ELSE 0 END) AS scheduled_visit,
    SUM(CASE WHEN store_visit_status = 'Show' THEN 1 ELSE 0 END) AS store_visit
FROM {$wpdb->prefix}lcm_leads
$where
GROUP BY lead_date
ORDER BY lead_date DESC");
?>

<div class="wrap">
  <h2>📅 Daily Lead Metrics</h2>

  <form method="get" class="row g-3 align-items-center mb-3">
    <input type="hidden" name="page" value="campaign-detail">
    <input type="hidden" name="campaign_id" value="<?= esc_attr($campaign_post_id); ?>">
    <div class="col-auto">
      <label for="month">Month:</label>
    </div>
    <div class="col-auto">
      <input type="month" id="month" name="month" class="form-control" value="<?= esc_attr($current_month); ?>">
    </div>
    <div class="col-auto">
      <label>From:</label>
    </div>
    <div class="col-auto">
      <input type="date" name="from" value="<?= esc_attr($from_date); ?>" class="form-control">
    </div>
    <div class="col-auto">
      <label>To:</label>
    </div>
    <div class="col-auto">
      <input type="date" name="to" value="<?= esc_attr($to_date); ?>" class="form-control">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Filter</button>
    </div>
  </form>

  <?php if (empty($rows)) : ?>
    <div class="alert alert-warning">No leads found for this campaign in the selected range.</div>
  <?php else : ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-sm">
        <thead>
          <tr>
            <th>Date</th>
            <th>Total Leads</th>
            <th>Connected</th>
            <th>Relevant</th>
            <th>Not Relevant</th>
            <th>Not Connected</th>
            <th>N/A</th>
            <th>Scheduled Visit</th>
            <th>Store Visit</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $relevant = (int) $r->relevant;
            $not_relevant = (int) $r->not_relevant;
            $not_connected = (int) $r->not_connected;
            $connected = $relevant + $not_relevant;
            $not_available = max(0, (int) $r->total_leads - ($connected + $not_connected));
          ?>
          <tr>
            <td><?= esc_html($r->date) ?></td>
            <td><?= $r->total_leads ?></td>
            <td><?= $connected ?></td>
            <td><?= $relevant ?></td>
            <td><?= $not_relevant ?></td>
            <td><?= $not_connected ?></td>
            <td><?= $not_available ?></td>
            <td><?= (int) $r->scheduled_visit ?></td>
            <td><?= (int) $r->store_visit ?></td>
            <td>
              <a class="btn btn-sm btn-secondary" href="/leads?campaign_id=<?= $campaign_post_id ?>&date=<?= $r->date ?>">View Leads</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
