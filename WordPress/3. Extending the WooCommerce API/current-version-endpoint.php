<?php

/**
 * Class CurrentVersionEndpoint
 */
class CurrentVersionEndpoint
{
    public function __construct() {
        $this->add_actions();
    }

    /**
     * Add actions.
     */
    public function add_actions() {
        add_action( 'rest_api_init', [ $this, 'add_api_version_route' ] );

    }

    public function add_api_version_route() {
        register_rest_route( 'alk-rest-api/v1', 'current', [
            'methods'  => 'GET',
            'callback' => [ $this, 'api_version_callback' ],
        ] );
    }

    public function api_version_callback(){
        $api_ver = get_option( 'cwc_wc_stable_app_version' );

        if($api_ver) {
            return [
                'status' => 'success',
                'message' => $api_ver,
            ];
        }

        return [
            'status' => 'error',
            'message' => 'API version is not currently set.'
        ];
    }
}