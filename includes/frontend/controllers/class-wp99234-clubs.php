<?php
/**
 * WP99234_Company class
 */
class WP99234_Clubs
{

    public function __construct()
    {

        $this->setup_actions();

    }

    public function setup_actions()
    {

        add_action('wp_ajax_subs_import_memberships', array($this, 'on_ajax_subs_import_memberships'));

    }

    public function on_ajax_subs_import_memberships()
    {

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

        if (!wp_verify_nonce($_REQUEST['nonce'], 'subs_import_memberships')) {
            WP99234()->send_sse_message(0, __('Invalid Request', 'wp99234'));
            exit;
        }

        $this->get_company_membership_types(true);

        exit;

    }

    public function get_company_membership_types($is_sse = false)
    {

        $cid = get_option('wp99234_check_no');

        if (!$cid) {
            return false;
        }

        $time_started = time();

        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Importing Clubs...';

        if ($is_sse) {
            WP99234()->send_sse_message($time_started, __('Importing Clubs...', 'wp99234'), 'start');
        }

        $endpoint = sprintf('%s/companies/%s/membership_types?l=100&visibility_in[]=public&visibility_in[]=restricted&visibility_in[]=private', untrailingslashit(WP99234_Api::$endpoint), $cid);

        $results = WP99234()->_api->_call($endpoint);

        if ($results) {

            //Make the results an associative array to make processing users and finding prices a much easier operation later.
            $types = array();

            $total = (integer)$results->count;
            $progress = 0;

            $start_time = time();

            foreach ($results->results as $membership_type) {
                $types[$membership_type->id] = $membership_type;
                $progress++;
                if ($is_sse) {
                    WP99234()->send_sse_message($start_time, "&gt; <i><a href='//" . WP99234_DOMAIN . "/o/memberships/$membership_type->id/edit' target='_blank'>$membership_type->name</a></i>", 'message', ($progress / $total) * 10);
                }
            }

            update_option('wp99234_company_membership_types', $types);

            // Update customer tags
            self::setup_customer_tags(true);

            if ($is_sse) {
                WP99234()->send_sse_message($time_started, __('Clubs successfully imported!', 'wp99234'), 'close', 100);
            } else {
                WP99234()->_admin->add_notice(__('Clubs successfully imported', 'wp99234'), 'success');
                wp_redirect(remove_query_arg('do_wp99234_import_membership_types'));
            }

            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly( 1, 3, 4, $message, 'Clubs successfully imported' );
            }

            exit;

        }

        if (isset($_GET['do_wp99234_import_membership_types'])) {

            if ($is_sse) {
                WP99234()->send_sse_message($time_started, __('Clubs failed to import', 'wp99234'), 'fatal');
            } else {

                WP99234()->_admin->add_notice(__('Clubs failed to import', 'wp99234'), 'fatal');
                wp_redirect(remove_query_arg('do_wp99234_import_membership_types'));

            }

            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly( 0, 3, 4, $message, 'Clubs failed to import' );
            }

            exit;

        }

    }

    /**
     * Pull and save Customer tags
     * @param bool $refresh
     * @return bool
     */
    public static function setup_customer_tags($refresh = false)
    {
        $customer_tags = get_option('troly_customer_tags', array());
        if ($refresh || empty($customer_tags)) {
            $customer_tags = array();
            $company_id = null;
            $company_membership_types = get_option('wp99234_company_membership_types');

            // Only valid membership type and set to fail DB upgrade
            if ( !is_array($company_membership_types) ) return false;

            foreach ($company_membership_types as $row) {
                if (isset($row->company_id)) {
                    $company_id = $row->company_id;
                    break;
                }
            }

            $endpoint = WP99234_Api::$endpoint . 'companies/' . $company_id . '.json';
            $response = WP99234()->_api->_call($endpoint);

            if (is_object($response)) {
                foreach ($response->tags as $row) {
                    if ($row->usage === 'customer') {
                        $endpoint = WP99234_Api::$endpoint . 'tags.json';
                        $endpoint .= "?json_search=true&l=1&q=" . rawurlencode($row->name) . "&usage=customer";
                        $tags = WP99234()->_api->_call($endpoint);
                        if ( $tags->count >= 1 && $tags->results[0]->name === $row->name ) {
                            $customer_tags[] = $tags->results[0];
                        }
                    }
                }

                update_option('troly_customer_tags', $customer_tags, true);
            }
        }

        // Ready for DB upgrade
        return true;
    }

}
