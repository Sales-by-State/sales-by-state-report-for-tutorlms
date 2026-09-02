<?php
/**
 * The report page and its assets.
 *
 * @package SalesByStateReportForTutorLMS
 */

namespace SBSTL\Admin;

use SBSTL\Filters;
use SBSTL\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the report under Tutor LMS.
 */
class Page {

	/**
	 * Menu slug.
	 */
	const SLUG = 'sbstl-sales-by-state';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_head', array( $this, 'print_css' ) );
	}

	/**
	 * Whether this screen is showing.
	 *
	 * @param string $hook Optional enqueue hook.
	 * @return bool
	 */
	private function is_screen( $hook = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading the current screen, not acting on it.
		if ( isset( $_GET['page'] ) && self::SLUG === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return true;
		}

		return is_string( $hook ) && false !== strpos( $hook, self::SLUG );
	}

	/**
	 * Add the report under Tutor LMS.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'tutor',
			__( 'Sales by State', 'sales-by-state-report-for-tutorlms' ),
			__( 'Sales by State', 'sales-by-state-report-for-tutorlms' ),
			'manage_tutor',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Register script and style handles.
	 *
	 * @return void
	 */
	private function register_assets() {
		$script = SBSTL_DIR . 'assets/js/report.js';
		$style  = SBSTL_DIR . 'assets/css/report.css';

		wp_register_style(
			'sbstl-report',
			SBSTL_URL . 'assets/css/report.css',
			array( 'wp-components' ),
			file_exists( $style ) ? (string) filemtime( $style ) : SBSTL_VERSION
		);

		wp_register_script(
			'sbstl-report',
			SBSTL_URL . 'assets/js/report.js',
			array(
				'wp-hooks',
				'wp-element',
				'wp-i18n',
				'wp-api-fetch',
				'wp-url',
				'wp-components',
			),
			file_exists( $script ) ? (string) filemtime( $script ) : SBSTL_VERSION,
			true
		);

		wp_set_script_translations( 'sbstl-report', 'sales-by-state-report-for-tutorlms', SBSTL_DIR . 'languages' );
		wp_localize_script( 'sbstl-report', 'sbstlConfig', $this->config() );
	}

	/**
	 * Enqueue the report bundle.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! $this->is_screen( $hook ) ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'sbstl-report' );
		wp_enqueue_script( 'sbstl-report' );
	}

	/**
	 * Print styles in the head if the enqueue hook did not run.
	 *
	 * Tutor LMS's React admin can skip `admin_enqueue_scripts` on some screens.
	 *
	 * @return void
	 */
	public function print_css() {
		if ( ! $this->is_screen() ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'sbstl-report' );
		wp_print_styles( array( 'wp-components', 'sbstl-report' ) );
	}

	/**
	 * Render the root element for the standalone page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Plugin::can_view() ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'sbstl-report' );
		wp_enqueue_script( 'sbstl-report' );

		printf(
			'<div class="wrap sbstl-wrap">
				<div class="sbstl-page-header"><h1 class="sbstl-page-header__title">%s</h1></div>
				<div id="sbstl-root"></div>
			</div>',
			esc_html__( 'Sales by State', 'sales-by-state-report-for-tutorlms' )
		);

		wp_print_scripts( array( 'sbstl-report' ) );
	}

	/**
	 * Data the bundle needs to draw its controls.
	 *
	 * @return array
	 */
	private function config() {
		$measures = array();

		foreach ( Filters::measures() as $key => $measure ) {
			$measures[] = array(
				'key'   => $key,
				'label' => $measure['label'],
				'type'  => $measure['type'],
			);
		}

		$statuses = array();

		foreach ( Filters::order_statuses() as $key => $label ) {
			$statuses[] = array(
				'value' => $key,
				'label' => $label,
			);
		}

		$years = array();

		foreach ( Filters::years() as $year ) {
			$years[] = array(
				'value' => (string) $year,
				'label' => (string) $year,
			);
		}

		$countries = array();

		foreach ( Filters::countries_with_states() as $code => $label ) {
			$countries[] = array(
				'value' => $code,
				'label' => $label,
			);
		}

		return array(
			'measures'        => $measures,
			'statuses'        => $statuses,
			'years'           => $years,
			'countries'       => $countries,
			'defaultCountry'  => Filters::default_country(),
			'defaultYear'     => (string) Filters::default_year(),
			'defaultStatuses' => Filters::default_statuses(),
			'perPageOptions'  => array( 10, 25, 50, 100 ),
			'title'           => __( 'Sales by State', 'sales-by-state-report-for-tutorlms' ),
			'canBuild'        => Plugin::can_manage(),
			'mode'            => 'standalone',
		);
	}
}
