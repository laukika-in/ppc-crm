<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$campaign_post_id = absint($_GET['campaign_id'] ?? 0);
$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$from = sanitize_text_field($_GET['from'] ?? '');
$to = sanitize_text_field($_GET['to'] ?? '');

$year = intval(substr($current_month, 0, 4));
$month = intval(substr($current_month, 5, 2));

if (!$campaign_post_id) {
    echo "<div class='notice notice-error'>Invalid Campaign ID.</div>";
    return;
}

// Build WHERE clause
$where = $wpdb->prepare("campaign_id = %d", $campaign_post_id);

if ($from && $to) {
    $where .= $wpdb->prepare(" AND lead_date BETWEEN %s AND %s", $from, $to);
    $filter_label = "From $from to $to";
} else {
    $where .= $wpdb->prepare(" AND MONTH(lead_date) = %d AND YEAR(lead_date) = %d", $month, $year);
    $filter_label = date("F Y", strtotime($current_month . "-01"));
}

// Main data query (grouped by lead_date)
$rows = $wpdb->get_results(
    "SELECT 
        lead_date AS date,
        COUNT(*) AS total_leads,
        SUM(CASE WHEN attempt_type = 'Connected:Relevant' THEN 1 ELSE 0 END) AS relevant,
        SUM(CASE WHEN attempt_type = 'Connected:Not Relevant' THEN 1 ELSE 0 END) AS not_relevant,
        SUM(CASE WHEN attempt_type = 'Not Connected' THEN 1 ELSE 0 END) AS not_connected,
        SUM(CASE WHEN attempt_status = 'Store Visit Scheduled' THEN 1 ELSE 0 END) AS scheduled_visit,
        SUM(CASE WHEN store_visit_status = 'Show' THEN 1 ELSE 0 END) AS store_visit
     FROM {$wpdb->prefix}lcm_leads
     WHERE $where
     GROUP BY lead_date
     ORDER BY lead_date DESC"
);

$tracker_rows = $wpdb->get_results(
  $wpdb->prepare(
    "SELECT id, track_date, reach, impressions, amount_spent
     FROM {$wpdb->prefix}lcm_campaign_daily_tracker
     WHERE campaign_post_id = %d",
    $campaign_post_id
  )
);

$tracker = [];
foreach ($tracker_rows as $row) {
  $tracker[$row->track_date] = $row;
}



// Summary block query
$summary = $wpdb->get_row(
    "SELECT 
        COUNT(*) AS total_leads,
        SUM(CASE WHEN attempt_type = 'Connected:Relevant' THEN 1 ELSE 0 END) AS relevant,
        SUM(CASE WHEN attempt_type = 'Connected:Not Relevant' THEN 1 ELSE 0 END) AS not_relevant,
        SUM(CASE WHEN attempt_type = 'Not Connected' THEN 1 ELSE 0 END) AS not_connected,
        SUM(CASE WHEN attempt_status = 'Store Visit Scheduled' THEN 1 ELSE 0 END) AS scheduled_visit,
        SUM(CASE WHEN store_visit_status = 'Show' THEN 1 ELSE 0 END) AS store_visit
     FROM {$wpdb->prefix}lcm_leads
     WHERE $where"
);

$relevant = intval($summary->relevant);
$not_relevant = intval($summary->not_relevant);
$not_connected = intval($summary->not_connected);
$connected = $relevant + $not_relevant;
$not_available = intval($summary->total_leads) - ($connected + $not_connected);
?>

<div class="wrap">
  <h2>📊 Campaign Daily Report – <?= esc_html($filter_label) ?></h2>

  <!-- Filters -->
  <form method="get" class="row g-2 align-items-center mb-3">
    <input type="hidden" name="page" value="campaign-detail">
    <input type="hidden" name="campaign_id" value="<?= esc_attr($campaign_post_id); ?>">
    <div class="col-auto">
      <label for="month" class="form-label">Month:</label>
      <input type="month" id="month" name="month" class="form-control" value="<?= esc_attr($current_month); ?>">
    </div>
    <div class="col-auto">
      <label for="from" class="form-label">From:</label>
      <input type="date" id="from" name="from" class="form-control" value="<?= esc_attr($from); ?>">
    </div>
    <div class="col-auto">
      <label for="to" class="form-label">To:</label>
      <input type="date" id="to" name="to" class="form-control" value="<?= esc_attr($to); ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Filter</button>
    </div>
  </form>

  <!-- Summary -->
  <div class="card mb-4" style="max-width:1000px">
    <div class="card-body">
      <div class="row text-center">
        <div class="col"><strong>Total Leads:</strong><br><?= intval($summary->total_leads) ?></div>
        <div class="col"><strong>Connected:</strong><br><?= $connected ?></div>
        <div class="col"><strong>Relevant:</strong><br><?= $relevant ?></div>
        <div class="col"><strong>Not Relevant:</strong><br><?= $not_relevant ?></div>
        <div class="col"><strong>Not Connected:</strong><br><?= $not_connected ?></div>
        <div class="col"><strong>N/A:</strong><br><?= max(0, $not_available) ?></div>
        <div class="col"><strong>Scheduled Visit:</strong><br><?= intval($summary->scheduled_visit) ?></div>
        <div class="col"><strong>Store Visit:</strong><br><?= intval($summary->store_visit) ?></div>
      </div>
    </div>
  </div>

  <!-- Table -->
  <?php if (empty($rows)) : ?>
    <div class="alert alert-warning">No leads found for this campaign in the selected range.</div>
  <?php else : ?>
    <div class="table-responsive lcm-scroll">
      <table class="table table-bordered table-striped table-sm lcm-table mb-0" style="min-width:1200px;">
        <thead>
          <tr>
            <th>Date</th>
            <th>Total Leads</th>
            <th>Reach</th>
            <th>Impressions</th>
            <th>Amount Spent (INR)</th>
            <th>Connected</th>
            <th>Relevant</th>
            <th>Not Relevant</th>
            <th>Not Connected</th>
            <th>N/A</th>
            <th>Scheduled Visit</th>
            <th>Store Visit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r) :
              $rel = intval($r->relevant);
              $nrel = intval($r->not_relevant);
              $ncon = intval($r->not_connected);
              $con = $rel + $nrel;
              $na = intval($r->total_leads) - ($con + $ncon);
              $reach = $tracker[$r->date]->reach ?? '';
              $imp = $tracker[$r->date]->impressions ?? '';
              $spent = $tracker[$r->date]->amount_spent ?? '';
          ?>
          <?php
            $track = $tracker[$r->date] ?? null;
            $row_id = $track->id ?? 0;
          ?>
             <tr data-row-id="<?= $r->id ?>" data-date="<?= esc_attr($r->date) ?>">

              <td><?= esc_html($r->date) ?></td>
              <td><?= intval($r->total_leads) ?></td>
              <td contenteditable class="editable" data-field="reach"><?= esc_html($reach) ?></td>
              <td contenteditable class="editable" data-field="impressions"><?= esc_html($imp) ?></td>
              <td contenteditable class="editable" data-field="amount_spent"><?= esc_html($spent) ?></td>
              <td><?= $con ?></td>
              <td><?= $rel ?></td>
              <td><?= $nrel ?></td>
              <td><?= $ncon ?></td>
              <td><?= max(0, $na) ?></td>
              <td><?= intval($r->scheduled_visit) ?></td>
              <td><?= intval($r->store_visit) ?></td>
                <!-- Reach, Impressions, Spent editable fields -->
 <td><input type="number" class="form-control form-control-sm reach-input" value="<?= intval($reach) ?>"></td>
<td><input type="number" class="form-control form-control-sm impressions-input" value="<?= intval($imp) ?>"></td>
<td><input type="number" class="form-control form-control-sm spent-input" value="<?= floatval($spent) ?>"></td>
<td><button class="btn btn-sm btn-success save-daily-tracker">💾 Save</button></td>

  
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
     
    </div>
  <?php endif ?>
</div>
