<?php
declare( strict_types=1 );

namespace PinkCrab\FgPosSync\Webhook;

use WP_REST_Request;
use PinkCrab\FgPosSync\Sync\Sync_Service;
use PinkCrab\FgPosSync\Webhook\WebhookLogger;
use PinkCrab\FgPosSync\Core\Interfaces\HasRegisterHook;
use PinkCrab\FgPosSync\Core\Services\Registration\Loader;

class Webhook implements HasRegisterHook {

    /**
     * @var \PinkCrab\FgPosSync\Sync\Sync_Service
     */
    private $sync_service;

    public function __construct( Sync_Service $sync_service ) {
        $this->sync_service = $sync_service;
    }

    /**
     * Register hooks.
     *
     * @param Loader $loader
     */
    public function register( Loader $loader ): void {
        $loader->action( 'rest_api_init', array( $this, 'add_webhook_endpoint' ) );
    }

    /**
     * Register the sales endpoint.
     */
    public function add_webhook_endpoint() {
        register_rest_route(
            PC_POS_REST_NAMESPACE,
            'sales/(?P<id>\d+)',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'sales_webhook_callback' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Call the logger and log whatever we got from the endpoint.
     *
     * @param WP_REST_Request $request
     */
    public function sales_webhook_callback( WP_REST_Request $request ) {
        $response = array();
        // Enable if you need to log the webhook payloads
        global $wpdb;


        try {
            $payload = json_decode( $request->get_body() );

            if ( $payload ) {
                $handling_service = new WebhookDataHandlingService( $this->sync_service );
                $stock_response = $handling_service->handle_stock_payload( $payload );

                // Removed as not used now.
                // $delivery_response = $handling_service->handle_delivery_payload( $payload );

                $response = array(
                    'stock' => $stock_response,
                    'delivery' => 'SKIPPED.',
                );

                // Log the payload
                ( new WebhookLogger( $GLOBALS['wpdb'] ) )
                    ->log_payload( json_encode( $payload ) );

                return $response;
            }
        } catch ( \Exception $e ) {
            error_log( $e->getMessage() );

            return array(
                'status'  => 'error',
                'message' => $e->getMessage(),
            );
        }

        return array(
            'status'  => 'error',
            'message' => 'Invalid payload detected.',
        );
    }
}
