<?php

declare(strict_types=1);
/**
 * Product scrapper that creates a transient containing a map of all the products and their attributes we use for comparison.
 *
 * @since 1.0.0
 * @package    PinkCrab\SimilarProducts
 * @subpackage DI Container
 */

namespace PinkCrab\SimilarProducts;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Closure;
use WC_Product;
use PinkCrab\SimilarProducts\Product_Helper;

/**
 * Class ProductScrapper
 *
 * @package PinkCrab\SimilarProducts
 */
class Product_Scraper {


    public const SCRAPE_TRANSIENT_KEY    = 'pc_scraped_products';
    public const SCRAPE_TRANSIENT_EXPIRY = 6 * \HOUR_IN_SECONDS;

    /**
     * Runs through the products on the site, puts them into an array and saves it as a transient.
     *
     * @return void
     */
    public function scrape_products(): void {
        $final_products = array();

        // Get the products.
        $args     = array(
            'status'   => 'publish',
            'orderby'  => 'id',
            'order'    => 'ASC',
            'category' => array( 'perfume' ),
            'limit'    => -1,
        );
        $products = wc_get_products( $args );

        foreach ( $products as $product ) {

            $attributes = $product->get_attributes();
            $gender     = ! empty( $attributes['pa_gender']['options'] ) ?
                $this->map_gender_term( $attributes['pa_gender']['options'][0] ) :
                '';

            // Create an array mapped to product IDs containing the attribute IDs in dev-friendly keys.
            $final_products[ $product->get_id() ] = array(
                'id'             => $product->get_id(),
                'concentration'  => ! empty( $attributes['pa_concentration'] ) ? $attributes['pa_concentration']->get_options() : array(),
                'gender'         => $gender,
                'fragrance_type' => ! empty( $attributes['pa_fragrance-type'] ) ? $attributes['pa_fragrance-type']->get_options() : array(),
                'main_note'      => ! empty( $attributes['pa_main-accord'] ) ? $attributes['pa_main-accord']->get_options() : array(),
                'top_notes'      => ! empty( $attributes['pa_topnotes'] ) ? $attributes['pa_topnotes']->get_options() : array(),
                'middle_notes'   => ! empty( $attributes['pa_heart'] ) ? $attributes['pa_heart']->get_options() : array(),
                'base_notes'     => ! empty( $attributes['pa_base'] ) ? $attributes['pa_base']->get_options() : array(),
                'date'           => $product->get_date_created()->getTimestamp(),
                'purchasable'    => Product_Helper::can_be_purchased( $product ),
            );
        }

        usort(
            $final_products,
            function ( $a, $b ) {
                return $b['date'] <=> $a['date'];
            }
        );

        // Remap the products, so the product id is the key.
        $final_products = array_reduce(
            $final_products,
            function ( $carry, $item ) {
                $carry[ $item['id'] ] = $item;
                return $carry;
            },
            array()
        );

        set_transient( self::SCRAPE_TRANSIENT_KEY, $final_products, self::SCRAPE_TRANSIENT_EXPIRY );
    }

    /**
     * Gets all products from transient
     * Can optionally remove all unpurchasable products.
     *
     * Unpurchaseable removed for listing views.
     *
     * @param bool $remove_unpurchasable
     * @return array
     */
    public function get_product_from_cache( bool $remove_unpurchasable = false ): array {
        $products_transient = get_transient( self::SCRAPE_TRANSIENT_KEY ) ?: array();

        return $remove_unpurchasable
            ? $this->remove_unpurchaseable( $products_transient )
            : $products_transient;
    }

    /**
     * Will refresh the transient if it is not currently set.
     * Also allows passing of a product ID, which if not present in
     * transient will force a refresh
     *
     * This is used to include new items as they are added.
     *
     * @param int|null $product_id
     * @return void
     */
    public function maybe_refresh_product_cache( ?int $product_id = null ): void {
        $products_transient = get_transient( self::SCRAPE_TRANSIENT_KEY ) ?: array();

        // If transient isnt set or a product id is passed and its not in the transient.
        if ( ! $products_transient
            || ( ! array_key_exists( $product_id, $products_transient ) && is_int( $product_id ) )
        ) {
            $this->scrape_products();
        }
    }

    /**
     * Gets the gender term slug from id.
     *
     * @param int $term_id
     * @return string
     */
    public function map_gender_term( int $term_id ): string {
        $term = get_term_by( 'id', $term_id, 'pa_gender' );
        return is_a( $term, 'WP_Term' ) ? $term->slug : 'unisex';
    }

    /**
     * Creates a callable for filtering products by gender.
     *
     * @param string $gender
     * @return Closure
     */
    public function filter_gender( string $gender ): Closure {
        return function ( $product ) use ( $gender ) {
            switch ( $gender ) {
                case 'unisex':
                    return true;
                case 'men':
                    return in_array( $product['gender'], array( 'men', 'unisex' ), true );
                case 'women':
                    return in_array( $product['gender'], array( 'women', 'unisex' ), true );
            }
            return false;
        };
    }

    /**
     * Removes all unpurchable products from an array of products
     * from transient.
     *
     * @param array$products
     * @return array
     */
    public function remove_unpurchaseable( $products ): array {
        return array_filter(
            $products,
            function( $product ): bool {
                return isset( $product['purchasable'] ) && $product['purchasable'] === true;
            }
        );
    }

    /**
     * This is the main method that calls the other methods to generate the related product lists and
     * returns an array of arrays containing product ids and their scores for the similar products.
     *
     * @param WC_Product $product
     * @return array
     */
    public function get_related( WC_Product $product ): array {
        $product_attributes = $product->get_attributes();

        // Map the gender.
        $product_attributes['gender'] = $this->map_gender_term( $product_attributes['pa_gender']['options'][0] ?? 0 );

        $related_family        = $this->get_related_family_products( $product->get_id(), $product_attributes );
        $related_main_note     = $this->get_related_note_products( $product->get_id(), $product_attributes );
        $related_by_similarity = $this->get_similar_products_by_formula( $product->get_id(), $product_attributes );

        return array(
            'family'    => $related_family,
            'main_note' => $related_main_note,
            'similar'   => $related_by_similarity,
        );
    }

    /**
     * Searches through the transient of product data and returns an array of product ids where the fragrances are identical.
     *
     * @param int   $product_id the source product id.
     * @param array $product_attributes the source product attributes from product->get_attributes().
     *
     * @return array
     */
    public function get_related_family_products( int $product_id, array $product_attributes ): array {

        $this->maybe_refresh_product_cache( $product_id );
        $related_family_products = array();

        // Check we have valid attributes
        $source_product_family = array();
        if ( is_array( $product_attributes )
            && array_key_exists( 'pa_fragrance-type', $product_attributes )
            && is_a( $product_attributes['pa_fragrance-type'], 'WC_Product_Attribute' ) ) {
            $source_product_family = $product_attributes['pa_fragrance-type']->get_options();
        }

        if ( count( $source_product_family ) > 0 ) {

            sort( $source_product_family );

            $products_transient = array_filter(
                $this->get_product_from_cache( true ),
                $this->filter_gender( $product_attributes['gender'] )
            );

            foreach ( $this->order_by_gender( $products_transient, $product_attributes['gender'] ) as $product ) {
                sort( $product['fragrance_type'] );

                if ( $source_product_family === $product['fragrance_type']
                    && $product_id != $product['id'] ) {
                    array_push( $related_family_products, array( 'id' => $product['id'] ) );
                }
            }
        }

        return $related_family_products;
    }

    /**
     * Searches through the transient of product data and returns an array of product ids where the main notes are identical.
     *
     * @param int   $product_id the source product id.
     * @param array $product_attributes the source product attributes from product->get_attributes().
     *
     * @return array
     */
    public function get_related_note_products( int $product_id, array $product_attributes ): array {

        $related_note_products = array();

        $this->maybe_refresh_product_cache( $product_id );

        // Check we have valid attributes
        $source_product_note = array();
        if ( is_array( $product_attributes )
            && array_key_exists( 'pa_main-accord', $product_attributes )
            && is_a( $product_attributes['pa_main-accord'], 'WC_Product_Attribute' ) ) {
            $source_product_note = $product_attributes['pa_main-accord']->get_options();
        }

        if ( count( $source_product_note ) > 0 ) {

            sort( $source_product_note );

            $products_transient = array_filter(
                $this->get_product_from_cache( true ),
                $this->filter_gender( $product_attributes['gender'] )
            );

            foreach ( $this->order_by_gender( $products_transient, $product_attributes['gender'] ) as $product ) {

                sort( $product['main_note'] );

                if ( $source_product_note === $product['main_note']
                    && $product_id != $product['id'] ) {
                    array_push(
                        $related_note_products,
                        array(
                            'id'     => $product['id'],
                            'gender' => $product['gender'],
                        )
                    );
                }
            }
        }

        return $related_note_products;
    }

    /**
     * Sorts the array of products base don the gender then ID.
     *
     * @param array $data
     * @param string $gender
     * @return array
     */
    public function order_by_gender( array $data, string $gender ): array {
        $results = array_reduce(
            $data,
            function( $carry, $item ) use ( $gender ) {
                // Based on the gender, push into a or b (a being primary)
                switch ( $gender ) {
                    case 'men':
                        if ( $item['gender'] === 'men' ) {
                            $carry['a'][ $item['id'] ] = $item;
                        }
                        if ( $item['gender'] === 'unisex' ) {
                            $carry['b'][ $item['id'] ] = $item;
                        }
                        break;

                    case 'women':
                        if ( $item['gender'] === 'women' ) {
                            $carry['a'][ $item['id'] ] = $item;
                        }
                        if ( $item['gender'] === 'unisex' ) {
                            $carry['b'][ $item['id'] ] = $item;
                        }
                        break;

                    default: // Unisex
                        if ( $item['gender'] === 'unisex' ) {
                            $carry['a'][ $item['id'] ] = $item;
                        } else {
                            $carry['b'][ $item['id'] ] = $item;
                        }
                        break;
                }
                return $carry;
            },
            array(
                'a' => array(),
                'b' => array(),
            )
        );

        return $results['a'] + $results['b'];
    }

    /**
     * Searches through the transient of product data and returns an array of product ids and scores based on a points-based system.
     *
     * @param int   $product_id the source product id.
     * @param array $product_attributes the source product attributes from product->get_attributes().
     *
     * @return array
     */
    public function get_similar_products_by_formula( int $product_id, array $product_attributes ): array {
        $related_products_by_formula = array();

        $this->maybe_refresh_product_cache( $product_id );

        // Map the product attributes for ease of use.
        $source_product_attr_formatted = array(
            'concentration'  => $product_attributes['pa_concentration']['options'] ?? array(),
            'fragrance_type' => $product_attributes['pa_fragrance-type']['options'] ?? array(),
            'main_note'      => $product_attributes['pa_main-accord']['options'] ?? array(),
            'top_notes'      => $product_attributes['pa_topnotes']['options'] ?? array(),
            'middle_notes'   => $product_attributes['pa_heart']['options'] ?? array(),
            'base_notes'     => $product_attributes['pa_base']['options'] ?? array(),
            'gender'         => $this->map_gender_term($product_attributes['pa_gender']['options'][0] ?? 0),
        );

        $products_transient = array_filter(
            $this->get_product_from_cache( true ),
            $this->filter_gender( $source_product_attr_formatted['gender'] )
        );

        foreach ( $products_transient as $product ) {
            if ( $product_id != $product['id'] ) {

                // Starting values.
                $product_points          = 0;
                $total_note_matches      = 0;
                $total_potential_matches = 0;
                $top_note_matches        = array();
                $mid_note_matches        = array();
                $base_note_matches       = array();

                // Calculate the fragrance type, up to 20 points based on how exact is the match.
                if ( count( $source_product_attr_formatted['fragrance_type'] ) > 0 && count( $product['fragrance_type'] ) > 0 ) {
                    $matches         = array_intersect( $source_product_attr_formatted['fragrance_type'], $product['fragrance_type'] );
                    $product_points += ( count( $matches ) / count( $product['fragrance_type'] ) ) * 20;
                }

                // If the main note is an exact match give it 10 points, otherwise 0.
                if ( count( $source_product_attr_formatted['main_note'] ) > 0 && count( $product['main_note'] ) > 0 ) {
                    $matches         = array_intersect( $source_product_attr_formatted['main_note'], $product['main_note'] );
                    $product_points += count( $matches ) === count( $product['main_note'] ) ? 10 : 0;
                }

                // If the concentration is an exact match give it 5 points, otherwise 0.
                if ( count( $source_product_attr_formatted['concentration'] ) > 0 && count( $product['concentration'] ) > 0 ) {
                    $matches         = array_intersect( $source_product_attr_formatted['concentration'], $product['concentration'] );
                    $product_points += count( $matches ) === count( $product['concentration'] ) ? 5 : 0;
                }

                // Calculate the top notes, up to 5 points based on how exact is the match.
                if ( count( $source_product_attr_formatted['top_notes'] ) > 0 && count( $product['top_notes'] ) > 0 ) {
                    $top_note_matches = array_intersect( $source_product_attr_formatted['top_notes'], $product['top_notes'] );
                    $product_points  += ( count( $top_note_matches ) / count( $product['top_notes'] ) ) * 5;
                }

                // Calculate the mid notes, up to 5 points based on how exact is the match.
                if ( count( $source_product_attr_formatted['middle_notes'] ) > 0 && count( $product['middle_notes'] ) > 0 ) {
                    $mid_note_matches = array_intersect( $source_product_attr_formatted['middle_notes'], $product['middle_notes'] );
                    $product_points  += ( count( $mid_note_matches ) / count( $product['middle_notes'] ) ) * 5;
                }

                // Calculate the base notes, up to 15 points based on how exact is the match.
                if ( count( $source_product_attr_formatted['base_notes'] ) > 0 && count( $product['base_notes'] ) > 0 ) {
                    $base_note_matches = array_intersect( $source_product_attr_formatted['base_notes'], $product['base_notes'] );
                    $product_points   += ( count( $base_note_matches ) / count( $product['base_notes'] ) ) * 15;
                }

                // Calculate the total extra points from matching the top, middle and base notes up to 40.
                $total_note_matches += isset( $top_note_matches ) ? count( $top_note_matches ) : 0;
                $total_note_matches += isset( $mid_note_matches ) ? count( $mid_note_matches ) : 0;
                $total_note_matches += isset( $base_note_matches ) ? count( $base_note_matches ) : 0;

                $total_potential_matches += ! empty( $product['top_notes'] ) ? count( $product['top_notes'] ) : 0;
                $total_potential_matches += ! empty( $product['middle_notes'] ) ? count( $product['middle_notes'] ) : 0;
                $total_potential_matches += ! empty( $product['base_notes'] ) ? count( $product['base_notes'] ) : 0;

                if ( $total_note_matches > 0 && $total_potential_matches > 0 ) {
                    // Get the ratio.
                    $ratio = $total_note_matches / $total_potential_matches;
                    // Added for edge cases, if we have more total and matched, we get over 100%
                    if ( $ratio > 1 ) {
                        // So switch to give us a low rating, so then its hides (as doesnt match)
                        $ratio = $total_potential_matches / $total_note_matches;
                    }
                    $product_points += ( $ratio ) * 40;
                }

                // Finally add the score to the array if it has any points.
                if ( $product_points > 0 ) {
                    array_push(
                        $related_products_by_formula,
                        array(
                            'id'    => $product['id'],
                            'score' => round( $product_points / 5 ) * 5,
                        )
                    );
                }
            }
        }

        return $related_products_by_formula;
    }

    /**
     * Whenever any changes happen to a product (add, edit, delete)
     * Cache is updated.
     *
     * @param integer $product_id
     * @return void
     */
    public function update_cache( int $product_id ): void {
        if ( get_post_type( $product_id ) === 'product' && ! get_transient( self::SCRAPE_TRANSIENT_KEY ) ) {
            $this->scrape_products();
            // Reset transient.
            set_transient( self::SCRAPE_TRANSIENT_KEY, 1, self::SCRAPE_TRANSIENT_EXPIRY );
        }
    }
}