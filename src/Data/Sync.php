<?php
/**
 * Keeps the report table in step with Tutor LMS orders.
 *
 * @package SalesByStateReportForTutorLMS
 */

namespace SBSTL\Data;

use SBSTL\Install\Schema;
use SBSTL\Regions;
use Tutor\Models\OrderModel;

defined( 'ABSPATH' ) || exit;

/**
 * Writes one row per order.
 *
 * Refunds are not modelled as separate records. An order that has been
 * refunded is included or excluded by the status filter like any other order.
 * Native ecommerce has no shipping address, so the shipping columns copy
 * the billing values.
 */
class Sync {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'tutor_order_placed', array( $this, 'on_event' ), 20, 1 );
		add_action( 'tutor_order_updated', array( $this, 'on_event' ), 20, 1 );
		add_action( 'tutor_order_payment_status_changed', array( $this, 'on_order_id' ), 20, 1 );
	}

	/**
	 * Handle a Tutor LMS order event payload.
	 *
	 * @param mixed $payload Event array or object.
	 * @return void
	 */
	public function on_event( $payload ) {
		$order_id = $this->order_id_from( $payload );

		if ( $order_id ) {
			$this->upsert( $order_id );
		}
	}

	/**
	 * Handle a numeric order ID from a payment-status hook.
	 *
	 * @param mixed $order_id Order ID.
	 * @return void
	 */
	public function on_order_id( $order_id ) {
		$this->upsert( (int) $order_id );
	}

	/**
	 * Insert or update the row for one order.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function upsert( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! $order_id ) {
			return false;
		}

		$row = self::build_row( $order_id );

		if ( ! $row ) {
			$this->delete( $order_id );
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->replace(
			Schema::table(),
			$row,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f' )
		);
	}

	/**
	 * Remove the row for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function delete( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! $order_id ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( Schema::table(), array( 'order_id' => $order_id ), array( '%d' ) );
	}

	/**
	 * Build the row for an order from Tutor LMS tables.
	 *
	 * Money is stored in major units. Gross is the order total. Net is that
	 * total minus tax. There is no shipping line.
	 *
	 * @param int $order_id Order ID.
	 * @return array<string,mixed>|false
	 */
	public static function build_row( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! OrderSource::table_exists() ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$order = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, user_id, order_status, payment_status,
				        total_price, tax_amount, created_at_gmt
				 FROM {$wpdb->prefix}tutor_orders
				 WHERE id = %d",
				$order_id
			)
		);

		if ( ! $order ) {
			return false;
		}

		if ( 'trash' === (string) $order->order_status ) {
			return false;
		}

		$billing         = self::billing_of( (int) $order->id, (int) $order->user_id );
		$billing_country = Regions::country_code( $billing['country'] );
		$billing_state   = Regions::state_code( $billing['state'], $billing_country );

		$total   = round( (float) $order->total_price, 2 );
		$tax     = round( (float) $order->tax_amount, 2 );
		$created = self::normalize_datetime( $order->created_at_gmt );
		$paid    = self::is_paid_status( (string) $order->payment_status ) ? $created : null;

		$currency = '';

		if ( function_exists( 'tutor_utils' ) ) {
			$currency = strtoupper( substr( (string) tutor_utils()->get_option( 'currency_code', 'USD' ), 0, 3 ) );
		}

		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {
			$currency = 'USD';
		}

		return array(
			'order_id'         => (int) $order->id,
			'status'           => substr( sanitize_key( (string) $order->order_status ), 0, 32 ),
			'date_created'     => $created ? $created : '0000-00-00 00:00:00',
			'date_paid'        => $paid,
			'billing_country'  => $billing_country,
			'billing_state'    => substr( $billing_state, 0, 50 ),
			'shipping_country' => $billing_country,
			'shipping_state'   => substr( $billing_state, 0, 50 ),
			'currency'         => $currency,
			'total_sales'      => $total,
			'tax_total'        => $tax,
			'shipping_total'   => 0,
			'net_total'        => $total - $tax,
		);
	}

	/**
	 * Billing country and state for an order.
	 *
	 * Prefers order meta, then the customer billing record.
	 *
	 * @param int $order_id Order ID.
	 * @param int $user_id  Customer user ID.
	 * @return array{country:string,state:string}
	 */
	private static function billing_of( $order_id, $user_id ) {
		if ( class_exists( OrderModel::class ) && method_exists( OrderModel::class, 'get_order_billing_address' ) ) {
			$address = OrderModel::get_order_billing_address( $order_id, $user_id );

			if ( is_object( $address ) ) {
				return array(
					'country' => (string) ( $address->country ?? '' ),
					'state'   => (string) ( $address->state ?? '' ),
				);
			}
		}

		$from_meta = self::billing_from_meta( $order_id );

		if ( '' !== $from_meta['country'] || '' !== $from_meta['state'] ) {
			return $from_meta;
		}

		return self::billing_from_customer( $user_id );
	}

	/**
	 * Billing address stored on the order.
	 *
	 * @param int $order_id Order ID.
	 * @return array{country:string,state:string}
	 */
	private static function billing_from_meta( $order_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}tutor_ordermeta
				 WHERE order_id = %d AND meta_key = %s
				 LIMIT 1",
				$order_id,
				'billing_address'
			)
		);

		return self::parse_billing( $meta );
	}

	/**
	 * Billing address stored on the Tutor LMS customer record.
	 *
	 * @param int $user_id User ID.
	 * @return array{country:string,state:string}
	 */
	private static function billing_from_customer( $user_id ) {
		global $wpdb;

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return array(
				'country' => '',
				'state'   => '',
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'tutor_customers' )
		);

		if ( ! $exists ) {
			return array(
				'country' => '',
				'state'   => '',
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT billing_country, billing_state
				 FROM {$wpdb->prefix}tutor_customers
				 WHERE user_id = %d
				 LIMIT 1",
				$user_id
			)
		);

		if ( ! $row ) {
			return array(
				'country' => '',
				'state'   => '',
			);
		}

		return array(
			'country' => (string) $row->billing_country,
			'state'   => (string) $row->billing_state,
		);
	}

	/**
	 * Country and state from a stored billing payload.
	 *
	 * @param mixed $meta Meta value.
	 * @return array{country:string,state:string}
	 */
	private static function parse_billing( $meta ) {
		if ( empty( $meta ) ) {
			return array(
				'country' => '',
				'state'   => '',
			);
		}

		$meta = maybe_unserialize( $meta );

		if ( is_string( $meta ) ) {
			$decoded = json_decode( $meta );
			$meta    = null !== $decoded ? $decoded : $meta;
		}

		if ( is_array( $meta ) ) {
			return array(
				'country' => (string) ( $meta['billing_country'] ?? $meta['country'] ?? '' ),
				'state'   => (string) ( $meta['billing_state'] ?? $meta['state'] ?? '' ),
			);
		}

		if ( is_object( $meta ) ) {
			return array(
				'country' => (string) ( $meta->billing_country ?? $meta->country ?? '' ),
				'state'   => (string) ( $meta->billing_state ?? $meta->state ?? '' ),
			);
		}

		return array(
			'country' => '',
			'state'   => '',
		);
	}

	/**
	 * Whether a payment status counts as paid for date_paid.
	 *
	 * @param string $status Payment status.
	 * @return bool
	 */
	private static function is_paid_status( $status ) {
		return in_array(
			(string) $status,
			array( 'paid', 'refunded', 'partially-refunded' ),
			true
		);
	}

	/**
	 * Pull an order ID out of a Tutor LMS event payload.
	 *
	 * @param mixed $payload Event payload.
	 * @return int
	 */
	private function order_id_from( $payload ) {
		if ( is_object( $payload ) && isset( $payload->id ) ) {
			return (int) $payload->id;
		}

		if ( is_array( $payload ) && isset( $payload['id'] ) ) {
			return (int) $payload['id'];
		}

		if ( is_numeric( $payload ) ) {
			return (int) $payload;
		}

		return 0;
	}

	/**
	 * Normalise a datetime string.
	 *
	 * @param mixed $value Datetime.
	 * @return string|null
	 */
	private static function normalize_datetime( $value ) {
		$value = (string) $value;

		if ( '' === $value || '0000-00-00 00:00:00' === $value ) {
			return null;
		}

		$ts = strtotime( $value );

		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
	}
}
