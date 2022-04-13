<?php declare( strict_types=1 );
/**
 * Handles all scraping of products and caching.
 *
 * @since 1.0.0
 * @package    PinkCrab\SimilarProducts
 * @subpackage Ajax
 */

namespace PinkCrab\SimilarProducts;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use WC_Product;
use PinkCrab\SimilarProducts\Request;
use PinkCrab\SimilarProducts\Translations;
use PinkCrab\SimilarProducts\Product_Scraper;

/**
 * Creates an instance of the similar products class.
 */
class Similar_Products_Service {

    /**
     * Holds the parent product.
     *
     * @var WC_Product
     */
    protected $product;

    /**
     * Instance of the product scraper.
     *
     * @var Product_Scraper
     */
    protected $product_scraper;

    /**
     * Holds this defined products, related items.
     *
     * @var array
     */
    protected $related_products = array();

    /**
     * Holds the singleton instance.
     *
     * @var Similar_Products_Service
     */
    public static $instance;

    /**
     * Constructs an instance of the Similar Products Service.
     *
     * @param WC_Product $product
     */
    private function __construct( WC_Product $product, Product_Scraper $product_scraper ) {
        $this->product         = $product;
        $this->product_scraper = $product_scraper;

        $this->set_related();
    }

    // Prevent cloning and deserialisation.
    private function __clone() {}
    private function __wakeup() {}

    /**
     * Creates the isntance of the service.
     *
     * @param integer $product_id
     * @return self
     */
    public static function boot( int $product_id ): self {

        // If we have not already created, create singleton.
        if ( ! self::$instance ) {
            // If arabic, get the EN version
            self::$instance = new self(
                wc_get_product( Translations::maybe_get_english_post_id( $product_id ) ),
                new Product_Scraper()
            );
        }
        return self::$instance;
    }

    /**
     * Returns the current instance.
     *
     * @return self
     */
    public static function get_instance():? self {
        return self::$instance;
    }

    /**
     * Gets the related products based on family
     *
     * @param integer $count
     * @return array
     */
    public function get_related_family( int $count = 5 ): array {
        return array_slice( $this->related_products['family'], 0, $count, true );
    }

    /**
     * Get related products based on main note.
     *
     * @param integer $count
     * @return array
     */
    public function get_related_main_note( int $count = 5 ): array {
        return array_slice( $this->related_products['main_note'], 0, $count, true );
    }

    /**
     * Get related similar products.
     * Includes offset to show an addtional selection.
     *
     * @param integer $count
     * @param integer $offset
     * @return array
     */
    public function get_related_similar( int $count = 5, int $offset = 0 ): array {
        usort(
            $this->related_products['similar'],
            function( $a, $b ) {
                return $b['score'] <=> $a['score'];
            }
        );
        return array_slice( $this->related_products['similar'], $offset, $count, true );
    }

    /**
     * Sets the related items for the defined product.
     *
     * @return void
     */
    private function set_related(): void {
        $this->related_products = $this->product_scraper->get_related( $this->product );
    }

    /**
     * Formats the key for the current product.
     *
     * @return string
     */
    public function formated_cache_key(): string {
        return "similar_{$this->product->get_id()}";
    }

    /**
     * Checks if one of the related product sets is empty.
     *
     * @param string $key
     * @return bool
     */
    public function is_empty( string $key ): bool {
        switch ( $key ) {
            case 'similar':
                return ! empty( $this->related_products['similar'] );
            case 'main_note':
                return ! empty( $this->related_products['main_note'] );
            case 'family':
                return ! empty( $this->related_products['family'] );
            default:
                return false;
        }
    }

    /**
     * Counts the contents of a set.
     *
     * @param string $key
     * @return int
     */
    public function count( string $key ): int {
        switch ( $key ) {
            case 'similar':
                return count( $this->related_products['similar'] );
            case 'main_note':
                return count( $this->related_products['main_note'] );
            case 'family':
                return count( $this->related_products['family'] );
            default:
                return 0;
        }
    }
}
