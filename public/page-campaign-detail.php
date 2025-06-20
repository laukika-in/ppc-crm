<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$campaign_post_id = absint($_GET['campaign_id'] ?? 0);

if (!$campaign_post_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}

$rows = $wpdb->get_results($wpdb->prepare("
    SELECT 
        lead_date,
        COUNT(*) AS total
    FROM {$wpdb->prefix}lcm_leads
    WHERE campaign_id = %d
    GROUP BY lead_date
    ORDER BY lead_date DESC
", $campaign_post_id));

?>

<div class="wrap">
  <h2>📅 Lead Entries by Date</h2>

  <?php if (empty($rows)) : ?>
    <div class="alert alert-warning">No leads found for this campaign.</div>
  <?php else : ?>
    <div class="table-responsive lcm-scroll">
      <table class="table table-bordered table-striped table-sm lcm-table mb-0" style="min-width:600px;">
        <thead>
          <tr>
            <th>Date</th>
            <th>Total Leads</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) : ?>
            <tr>
              <td><?= esc_html($r->lead_date) ?></td>
              <td><?= intval($r->total) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</div>
