<?php


namespace PinkCrab\FgPosSync\Webhook;


use PinkCrab\FgPosSync\Sync\Sync_Service;
use PinkCrab\FgPosSync\Orders\Order_Controller;

class WebhookDataHandlingService {


    /**
     * @var Sync_Service
     */
    private $sync_Service;

    public function __construct( Sync_Service $sync_Service ) {
        $this->sync_Service = $sync_Service;
    }

    /**
     * @param \stdClass $payload
     *
     * @return string[]
     */
    public function handle_stock_payload( \stdClass $payload ): array {

        // If it's not legit, abort.
        if ( ! $this->is_legit_sale( $payload ) ) {
            return array(
                'status'  => 'error',
                'message' => 'The payload does not contain valid sale data.',
            );
        }

        $prepared_stock_data = $this->stock_data_transformer( $payload );

        foreach ( $prepared_stock_data as $prepared_stock_datum ) {
            // Ensure we skip if not a valid sku passed.
            if ( array_key_exists( 'sku', $prepared_stock_datum ) && is_string( $prepared_stock_datum['sku'] ) ) {
                $this->sync_Service->sync_single_item( $prepared_stock_datum['sku'] );
            }
        }

        return array(
            'status'  => 'success',
            'message' => 'The following products have been synced: ' . implode( ',', array_column( $prepared_stock_data, 'sku' ) ),
        );

    }

    /**
     * @param \stdClass $rawPayload
     *
     * @return array
     */
    private function stock_data_transformer( \stdClass $rawPayload ): array {
        $preparedData = array();

        foreach ( $rawPayload->cart_items as $cart_item ) {
            $preparedData[] = array(
                'product_id' => $cart_item->item_id,
                'quantity'   => $cart_item->quantity,
                'sku'        => $cart_item->item_number,
            );
        }

        return $preparedData;
    }

    /**
     * Mark order as complete if sale marked as delivered.
     *
     * @param \stdClass $payload
     *
     * @return string[]
     */
    public function handle_delivery_payload( \stdClass $payload ) {
        if ( ! $this->is_legit_delivery( $payload ) ) {
            return array(
                'status'  => 'error',
                'message' => 'The payload does not contain valid delivery data.',
            );
        }

        $order_id = Order_Controller::get_order_id_from_sale_id( $payload->sale_id );
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            return array(
                'status'  => 'error',
                'message' => 'Order not found for sale ' . $payload->sale_id,
            );
        }

        $message = sprintf(
            'Delivery confirmation received from POS (Sale ID: %s)',
            $payload->sale_id
        );

        $order->update_status( 'completed', $message );

        return array(
            'status'  => 'success',
            'message' => 'The order associated with Sale ' . $payload->sale_id . ' has been marked completed.',
        );
    }

    /**
     * Using this to determine if the payload is actually a sale. I've picked a few key fields that would show that's a sale.
     *
     * @param \stdClass $payload
     *
     * @return bool
     */
    private function is_legit_sale( \stdClass $payload ): bool {
        return ! empty( $payload->cart_items ) && ( isset( $payload->sale_id ) || isset( $payload->receiving_id ) );
    }

    /**
     * Returns true if the payload contains a valid delivery marker.
     *
     * @param \stdClass $payload
     *
     * @return bool
     */
    private function is_legit_delivery( \stdClass $payload ): bool {
        return \property_exists( $payload, 'sale_id' )
            && \is_numeric( $payload->sale_id )
            && (int) $payload->sale_id > 0;
    }
}