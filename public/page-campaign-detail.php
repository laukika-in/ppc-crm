<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$campaign_post_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$year = intval(substr($current_month, 0, 4));
$month = intval(substr($current_month, 5, 2));

if (!$campaign_post_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}

$rows = $wpdb->get_results($wpdb->prepare("
    SELECT 
        lead_date AS date,
        COUNT(*) AS total_leads,
        SUM(CASE WHEN attempt_status LIKE 'Connected:%%' THEN 1 ELSE 0 END) AS connected,
        SUM(CASE WHEN attempt_status = 'Not Connected' THEN 1 ELSE 0 END) AS not_connected,
        SUM(CASE WHEN attempt_status = 'Connected:Relevant' THEN 1 ELSE 0 END) AS relevant,
        SUM(CASE WHEN attempt_status = 'Connected:Not Relevant' THEN 1 ELSE 0 END) AS not_relevant,
        SUM(CASE WHEN store_visit_status = 'Store Visit Scheduled' THEN 1 ELSE 0 END) AS scheduled_visit,
        SUM(CASE WHEN store_visit_status = 'Show' THEN 1 ELSE 0 END) AS store_visit
    FROM {$wpdb->prefix}lcm_leads
    WHERE campaign_id = %d
      AND MONTH(lead_date) = %d
      AND YEAR(lead_date) = %d
    GROUP BY lead_date
    ORDER BY lead_date DESC
", $campaign_post_id, $month, $year));
?>

<div class="wrap">
  <h2>📅 Daily Lead Metrics – <?= date("F Y", strtotime($current_month . "-01")) ?></h2>

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
            <th>Not Connected</th>
            <th>Relevant</th>
            <th>Not Relevant</th>
            <th>N/A</th>
            <th>Scheduled Visit</th>
            <th>Store Visit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) :
              $connected = intval($r->connected);
              $not_connected = intval($r->not_connected);
              $relevant = intval($r->relevant);
              $not_relevant = intval($r->not_relevant);
              $not_available = intval($r->total_leads) - ($connected + $not_connected + $relevant + $not_relevant);
          ?>
            <tr>
              <td><?= esc_html($r->date) ?></td>
              <td><?= intval($r->total_leads) ?></td>
              <td><?= $connected ?></td>
              <td><?= $not_connected ?></td>
              <td><?= $relevant ?></td>
              <td><?= $not_relevant ?></td>
              <td><?= max(0, $not_available) ?></td>
              <td><?= intval($r->scheduled_visit) ?></td>
              <td><?= intval($r->store_visit) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
