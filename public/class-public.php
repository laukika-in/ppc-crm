<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PPC_CRM_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
        add_shortcode( 'lcm_lead_table',     [ $this, 'shortcode_lead_table' ] );
        add_shortcode( 'lcm_campaign_table', [ $this, 'shortcode_campaign_table' ] );
        add_shortcode('campaign_detail_page', [$this, 'render_campaign_detail']);

    }

    /**
     * Register and enqueue all necessary CSS/JS assets
     */
    public function register_assets() {
        $base = plugin_dir_url( __FILE__ );

        // Bootstrap CSS & JS
        wp_register_style(
            'bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            [],
            '5.3.3'
        );
        wp_register_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [ 'jquery' ],
            '5.3.3',
            true
        );

        // Bootstrap Icons
        wp_register_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
            [],
            null
        );

        // Flatpickr date/time picker
        wp_register_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            null
        );
        wp_register_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            [],
            null,
            true
        );
        wp_register_script(
            'flatpickr-init',
            $base . 'assets/js/flatpickr-init.js',
            [ 'jquery', 'flatpickr-js' ],
            PPC_CRM_VERSION,
            true
        );

        // Table styling
        wp_register_style(
            'lcm-tables',
            $base . 'assets/css/lcm-tables.css',
            [ 'bootstrap-css' ],
            PPC_CRM_VERSION
        );

        // Lead table script
        wp_register_script(
            'lcm-lead-table',
            $base . 'assets/js/lead-table.js',
            [ 'jquery', 'bootstrap-js', 'flatpickr-init' ],
            PPC_CRM_VERSION,
            true
        );

        // Campaign table script
        wp_register_script(
            'lcm-campaign-table',
            $base . 'assets/js/campaign-table.js',
            [ 'jquery', 'bootstrap-js', 'flatpickr-init' ],
            PPC_CRM_VERSION,
            true
        );
        
        // Campaign Detail Tracker
        wp_register_script(
            'lcm-campaign-detail',
            $base . 'assets/js/campaign-detail.js',
            [ 'jquery', 'bootstrap-js', 'flatpickr-init' ],
            PPC_CRM_VERSION,
            true
        );
         wp_register_style(
        'select2-css',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        [],
        '4.1.0'
    );
    wp_register_script(
        'select2-js',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        [ 'jquery' ],
        '4.1.0',
        true
    );
 wp_register_style(
        'select2-css',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
        [],
        '4.1.0'
    );
    wp_register_script(
        'select2-js',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        [ 'jquery' ],
        '4.1.0',
        true
    );
 
    }

    /**
     * Shortcode: Lead Data Table
     */
    public function shortcode_lead_table(): string {
        $user        = wp_get_current_user();
        $is_client   = in_array( 'client', (array) $user->roles, true );

        // Data sources
        $clients   = get_users( [ 'role__in' => ['client'], 'fields' => ['ID','display_name'] ] );
        
        $campaigns = get_posts( [ 'post_type' => 'lcm_campaign', 'numberposts' => -1, 'fields' => 'ids' ] );
      global $wpdb;

      $rows = $wpdb->get_results(
        "SELECT client_id,post_id, adset FROM {$wpdb->prefix}lcm_campaigns WHERE adset<>''",
        ARRAY_A
      );
      $adsets_by_client = [];
      foreach ( $rows as $r ) {
        $adsets_by_client[ $r['client_id'] ][] = [ $r['post_id'], $r['adset'] ];
      }


      $rows2 = $wpdb->get_results(
        "SELECT client_id, post_id, campaign_name FROM {$wpdb->prefix}lcm_campaigns WHERE campaign_name<>''",
        ARRAY_A
      );
      $adnames_by_client = [];
      foreach ( $rows2 as $r ) {
        $adnames_by_client[ $r['client_id'] ][] = [ $r['post_id'], $r['campaign_name'] ];
      }
      // Localize variables for JS
        $vars = [
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'lcm_ajax' ),
            'per_page'          => 10,
            'is_client'         => $is_client,
            'current_client_id' => $user->ID,
            'clients'           => array_map( fn($u) => [ $u->ID, $u->display_name ], $clients ),
            'adsets_by_client'    => $adsets_by_client,   
            'adnames_by_client'   => $adnames_by_client,
        ];

        // Enqueue styles & scripts
        wp_enqueue_style( 'bootstrap-css' );
        wp_enqueue_style( 'bootstrap-icons' );
        wp_enqueue_style( 'flatpickr-css' );
        wp_enqueue_style( 'lcm-tables' );
        wp_enqueue_script( 'bootstrap-js' );
        wp_enqueue_script( 'flatpickr-js' );
        wp_enqueue_script( 'flatpickr-init' );
        wp_enqueue_script( 'lcm-lead-table' );
        wp_enqueue_style( 'select2-css' );
        wp_enqueue_script( 'select2-js' );

        wp_localize_script( 'lcm-lead-table', 'LCM', $vars );

        // Render HTML
        ob_start(); ?>
        <div class="d-flex justify-content-between mb-2">
            <button id="lcm-add-row-lead" class="btn btn-primary btn-sm">+ Add Lead</button>
           

      <div class="lcm-filters">
       
        <?php if ( ! $is_client ) : ?>  <div class="col-auto">
                    <select id="lcm-filter-client" class="form-select form-select-sm me-2" style="max-width:220px">
                        <option value="">All Clients</option>
                        <?php foreach ( $clients as $c ) : ?>
                            <option value="<?= esc_attr( $c->ID ); ?>"><?= esc_html( $c->display_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                 </div>
          <?php endif; ?>
     
        <!-- Date range -->
      <div class="col-auto">
        <div class="input-group input-group-sm">
          <input id="lcm-filter-date-from" type="date" class="form-control" placeholder="From date">
          <input id="lcm-filter-date-to"   type="date" class="form-control" placeholder="To date">
        </div>
      </div>

      <!-- Campaign Name -->
<div class="col-auto">
  <select id="lcm-filter-adname" class="form-select form-select-sm">
    <option value="">All Campaigns</option>
    <?php
      // $vars['adnames_by_client'] is [ client_id => [ [post_id, title], … ] ]
      $all = array_merge(...array_values($adnames_by_client));
      usort($all, fn($a,$b)=> strcasecmp($a[1],$b[1]));
      foreach( $all as list($pid,$title) ): ?>
        <option value="<?php echo esc_attr($pid) ?>">
          <?php echo esc_html($title) ?>
        </option>
      <?php endforeach; ?>
  </select>
</div>

<!-- Adset -->
<div class="col-auto">
  <select id="lcm-filter-adset" class="form-select form-select-sm">
    <option value="">All Adsets</option>
    <?php
      $all = array_merge(...array_values($adsets_by_client));
      usort($all, fn($a,$b)=> strcasecmp($a[1],$b[1]));
      foreach( $all as list($pid,$title) ): ?>
        <option value="<?php echo esc_attr($pid) ?>">
          <?php echo esc_html($title) ?>
        </option>
      <?php endforeach; ?>
  </select>
</div>


      <!-- Day -->
      <div class="col-auto">
        <select id="lcm-filter-day" class="form-select form-select-sm">
          <option value="">All Days</option>
          <?php foreach ( [ 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' ] as $d ) : ?>
            <option value="<?= esc_attr($d) ?>"><?= esc_html($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Client Type -->
      <div class="col-auto">
        <select id="lcm-filter-client-type" class="form-select form-select-sm">
          <option value="">All Client Types</option>
          <option value="New Client">New Client</option>
          <option value="Existing Client">Existing Client</option>
        </select>
      </div>

      <!-- Source -->
      <div class="col-auto">
        <select id="lcm-filter-source" class="form-select form-select-sm">
          <option value="">All Sources</option>
          <?php foreach (["Google","Meta","WhatsApp","LinkedIn","Twitter","TikTok","Email","Referral","Organic","Other"] as $src): ?>
            <option value="<?= esc_attr($src) ?>"><?= esc_html($src) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

          <!-- Attempt Type -->
                <div class="col-auto">
                  <div class="input-group input-group-sm lcm-filter-group" id="filter-attempt-type-group">
                    <select id="lcm-filter-attempt-type" class="form-select form-select-sm">
                      <option value="">All Attempt Types</option>
                      <?php foreach ([
                        'Connected:Not Relevant',
                        'Connected:Relevant',
                        'Not Connected'
                      ] as $type): ?>
                        <option value="<?= esc_attr($type) ?>"><?= esc_html($type) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="button"
                            class="btn btn-outline-secondary clear-filter"
                            data-filter="attempt_type"
                            title="Clear attempt-type filter">&times;</button>
                  </div>
                </div>

                <!-- Attempt Status -->
                <div class="col-auto">
                  <div class="input-group input-group-sm lcm-filter-group" id="filter-attempt-status-group">
                    <select id="lcm-filter-attempt-status" class="form-select form-select-sm">
                      <option value="">All Attempt Statuses</option>
                      <?php foreach ([
                        'Call Rescheduled',
                        'Just browsing',
                        'Not Interested',
                        'Ringing / No Response',
                        'Store Visit Scheduled',
                        'Wrong Number / Invalid Number'
                      ] as $st): ?>
                        <option value="<?= esc_attr($st) ?>"><?= esc_html($st) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="button"
                            class="btn btn-outline-secondary clear-filter"
                            data-filter="attempt_status"
                            title="Clear attempt-status filter">&times;</button>
                  </div>
                </div>
        <!-- Store Visit -->
        <div class="col-auto">
          <select id="lcm-filter-store-visit-status" class="form-select form-select-sm">
            <option value="">All Store Visits</option>
            <option value="Show">Show</option>
            <option value="No Show">No Show</option>
          </select>
        </div>

        <!-- Occasion -->
        <div class="col-auto">
          <select id="lcm-filter-occasion" class="form-select form-select-sm">
            <option value="">All Occasions</option>
            <?php foreach (["Anniversary","Birthday","Casual Occasion","Engagement/Wedding","Gifting","Others","N/A"] as $oc): ?>
              <option value="<?= esc_attr($oc) ?>"><?= esc_html($oc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Free-text search -->
        <div class="col-auto">
          <input id="lcm-filter-text" type="text" class="form-control form-control-sm" placeholder="Search name/phone/email">
        </div>
        <!-- Budget & Product -->
        <div class="col-auto">
          <input id="lcm-filter-budget" type="text" class="form-control form-control-sm" placeholder="Budget contains…">
        </div>
        <div class="col-auto">
          <input id="lcm-filter-product" type="text" class="form-control form-control-sm" placeholder="Product interest…">
        </div>
              </div>
                <div id="lcm-pager-lead" class="btn-group btn-group-sm ms-2"></div>
                </div>

                <div class="table-responsive lcm-scroll">
                    <table id="lcm-lead-table" class="table table-bordered table-striped table-sm lcm-table mb-0"
                          style="table-layout:auto; min-width:1200px;">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="lcmDelModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Delete Row</h5></div>
              <div class="modal-body">Are you sure you want to delete this row?</div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="lcm-confirm-del">Delete</button>
              </div>
            </div>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Campaign Data Table
     */
    public function shortcode_campaign_table(): string {
        return $this->render_table( 'campaign' );
    }

    /**
     * Shared renderer for both tables
     */
    private function render_table( string $which ): string {
        // Current user & role
        $user      = wp_get_current_user();
        $is_client = in_array( 'client', (array) $user->roles, true );

        // Data sources
        $clients   = get_users( [ 'role__in' => ['client'], 'fields' => ['ID','display_name'] ] );
        $campaigns = get_posts( [ 'post_type' => 'lcm_campaign', 'numberposts' => -1, 'fields' => 'ids' ] );

        // Localize for JS
        $vars = [
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'lcm_ajax' ),
            'per_page'          => 10,
            'is_client'         => $is_client,
            'current_client_id' => $user->ID,
            'clients'           => array_map( fn($u) => [ $u->ID, $u->display_name ], $clients ),
            //'adsets'            => array_map( fn($id) => get_the_title($id), $campaigns ),
        ];

        // Enqueue assets
        wp_enqueue_style( 'bootstrap-css' );
        wp_enqueue_style( 'bootstrap-icons' );
        wp_enqueue_style( 'flatpickr-css' );
        wp_enqueue_style( 'lcm-tables' );
        wp_enqueue_script( 'bootstrap-js' );
        wp_enqueue_script( 'flatpickr-js' );
        wp_enqueue_script( 'flatpickr-init' );

        if ( $which === 'lead' ) {
            // Already handled above, but keep consistent
            wp_enqueue_script( 'lcm-lead-table' );
            wp_localize_script( 'lcm-lead-table', 'LCM', $vars );
        } else {
            wp_enqueue_script( 'lcm-campaign-table' );
            wp_localize_script( 'lcm-campaign-table', 'LCM', $vars );
        }

        $div = ( $which === 'lead' ) ? 'lcm-lead-table' : 'lcm-campaign-table';

        ob_start(); ?>
        <div class="d-flex justify-content-between mb-2">
            <button id="lcm-add-row-<?= esc_attr( $which ); ?>" class="btn btn-primary btn-sm">
                + Add <?= ucfirst( $which ); ?>
            </button>

          
                               <div class="lcm-filters">   
           
 
            <?php if ( ! $is_client ) : ?>
            <div class="col-auto">
                            <select id="lcm-filter-client" class="form-select form-select-sm me-2" style="max-width:220px"> 
                                <option value="">All Clients</option>
                                <?php foreach ( $clients as $c ) : ?>
                                    <option value="<?= esc_attr( $c->ID ); ?>"><?= esc_html( $c->display_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                              </div> <?php endif; ?> 
                        
              <div class="col-auto">
              <div class="input-group input-group-sm lcm-filter-group" id="filter-month-group">
              <select id="lcm-filter-month-camp" class="form-select">
                <option value="">All Months</option>
                <?php foreach ( [ 'January','February','March','April','May','June','July','August','September','October','November','December' ] as $m ) : ?>
                  <option value="<?= esc_attr($m) ?>"><?= esc_html($m) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button"
                      class="btn btn-outline-secondary clear-filter"
                      data-filter="month"
                      title="Clear month filter">&times;</button>
            </div>
          </div>

          <div class="col-auto">
            <div class="input-group input-group-sm lcm-filter-group" id="filter-location-group">
              <input id="lcm-filter-location-camp"
                    class="form-control"
                    type="text"
                    placeholder="Location" />
              <button type="button"
                      class="btn btn-outline-secondary clear-filter"
                      data-filter="location"
                      title="Clear location filter">&times;</button>
            </div>
          </div>

          <div class="col-auto">
            <div class="input-group input-group-sm lcm-filter-group" id="filter-store-group">
              <select id="lcm-filter-store-camp" class="form-select">
                <option value="">All Store Visits</option>
                <option value="yes">Visited</option>
                <option value="no">Not Visited</option>
              </select>
              <button type="button"
                      class="btn btn-outline-secondary clear-filter"
                      data-filter="store"
                      title="Clear store-visit filter">&times;</button>
            </div>
          </div>

          <div class="col-auto">
            <div class="input-group input-group-sm lcm-filter-group" id="filter-connected-group">
              <select id="lcm-filter-connected-camp" class="form-select">
                <option value="">All Connected</option>
                <option value="yes">Has Connected Calls</option>
                <option value="no">No Connected Calls</option>
              </select>
              <button type="button"
                      class="btn btn-outline-secondary clear-filter"
                      data-filter="connected"
                      title="Clear connected filter">&times;</button>
            </div>
          </div>
        </div>

            <div id="lcm-pager-<?= esc_attr( $which ); ?>" class="btn-group btn-group-sm ms-2"></div>
        </div>

        <div class="table-responsive lcm-scroll">
            <table id="<?= esc_attr( $div ); ?>"
                   class="table table-bordered table-striped table-sm lcm-table mb-0"
                   style="table-layout:auto; min-width:1200px;">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" id="lcmDelModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Delete Row</h5></div>
              <div class="modal-body">Are you sure you want to delete this row?</div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="lcm-confirm-del">Delete</button>
              </div>
            </div>
          </div>
        </div>
        <?php
        return ob_get_clean();
    }
 
  public function render_campaign_detail() {
    wp_enqueue_style('bootstrap-css');
    wp_enqueue_style('bootstrap-icons');
    wp_enqueue_style('flatpickr-css');
    wp_enqueue_script('bootstrap-js');
    wp_enqueue_script('flatpickr-js');
    wp_enqueue_script('flatpickr-init');
    wp_enqueue_script('lcm-campaign-detail');

    wp_localize_script('lcm-campaign-detail', 'LCM', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce'    => wp_create_nonce('lcm_ajax'),
      'campaign_id' => absint($_GET['campaign_id'] ?? 0),
    ]);

    ob_start();
    include 'page-campaign-detail.php';
    return ob_get_clean();
  }
}
