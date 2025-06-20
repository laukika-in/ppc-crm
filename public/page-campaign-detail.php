<?php
// File: public/page-campaign-detail.php
if (!defined('ABSPATH')) exit;

$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

?>
<div class="container mt-4">
  <h4 class="mb-3">Campaign Daily Tracker</h4>

  <!-- Performance Summary or Filters Here -->

  <div class="card mt-4">
    <div class="card-header">Editable Daily Reach / Impressions / Amount</div>
    <div class="card-body p-0">
      <table class="table table-bordered table-sm mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 20%">Date</th>
            <th style="width: 20%">Reach</th>
            <th style="width: 20%">Impressions</th>
            <th style="width: 20%">Amount Spent (INR)</th>
            <th style="width: 20%"></th>
          </tr>
        </thead>
        <tbody id="tracker-body">
          <tr><td colspan="5">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
 