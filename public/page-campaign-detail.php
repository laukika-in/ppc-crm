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

<script>
jQuery(document).ready(function($) {
  function loadTrackerRows() {
    $.post(LCMTracker.ajax_url, {
      action: 'lcm_get_daily_tracker',
      campaign_id: LCMTracker.campaign_id,
      nonce: LCMTracker.nonce
    }, function(res) {
      if (res.success && res.data.length > 0) {
        let rows = '';
        res.data.forEach(row => {
          rows += `<tr data-date="${row.track_date}">
            <td>${row.track_date}</td>
            <td><input type="number" class="form-control form-control-sm reach" value="${row.reach || ''}"></td>
            <td><input type="number" class="form-control form-control-sm imp" value="${row.impressions || ''}"></td>
            <td><input type="number" class="form-control form-control-sm amt" value="${row.amount_spent || ''}"></td>
            <td><button class="btn btn-primary btn-sm save-row">Save</button></td>
          </tr>`;
        });
        $('#tracker-body').html(rows);
      } else {
        $('#tracker-body').html('<tr><td colspan="5">No data available</td></tr>');
      }
    });
  }

  $('#tracker-body').on('click', '.save-row', function() {
    const $tr = $(this).closest('tr');
    const track_date = $tr.data('date');
    const reach = $tr.find('.reach').val();
    const imp = $tr.find('.imp').val();
    const amt = $tr.find('.amt').val();

    $.post(LCMTracker.ajax_url, {
      action: 'lcm_save_daily_tracker_row',
      campaign_id: LCMTracker.campaign_id,
      track_date,
      reach,
      impressions: imp,
      amount_spent: amt,
      nonce: LCMTracker.nonce
    }, function(res) {
      if (res.success) {
        alert('Saved!');
        loadTrackerRows();
      }
    });
  });

  loadTrackerRows();
});
</script>
