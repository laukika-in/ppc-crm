<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$campaign_id = absint($_GET['campaign_id'] ?? 0);
if (!$campaign_id) {
    echo "<div class='notice'>Invalid Campaign</div>";
    return;
}

$current_month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
$year = substr($current_month, 0, 4);
$month = substr($current_month, 5, 2);

// Grouped daily stats
$results = $wpdb->get_results($wpdb->prepare("
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
", $campaign_id, $month, $year));

echo "<h2>Campaign Daily Report – " . date("F Y", strtotime($current_month)) . "</h2>";
?>

<div style="margin: 10px 0;">
    <form method="get">
        <input type="hidden" name="campaign_id" value="<?= esc_attr($campaign_id); ?>">
        <label>Select Month: 
            <input type="month" name="month" value="<?= esc_attr($current_month); ?>">
        </label>
        <button type="submit">Go</button>
    </form>
</div>

<table class="wp-list-table widefat striped">
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
            <th>Store Visit Scheduled</th>
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
                    <a href="<?= admin_url('admin.php?page=ppc-leads&campaign_id=' . $campaign_id . '&lead_date=' . $row->date) ?>" class="button">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
