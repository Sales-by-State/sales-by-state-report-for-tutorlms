<?php
/**
 * Country and state labels for the report.
 *
 * @package SalesByStateReportForTutorLMS
 */

namespace SBSTL;

defined( 'ABSPATH' ) || exit;

/**
 * US, Canada, and UK subdivisions.
 *
 * Tutor LMS native ecommerce stores country and state as full names
 * (for example "United States" / "California"), not ISO codes. The
 * report table stores ISO codes so zero-sales rows match sales rows.
 */
class Regions {

	/**
	 * Countries the report always offers.
	 *
	 * @return array<string,string>
	 */
	public static function countries() {
		return array(
			'US' => __( 'United States', 'sales-by-state-report-for-tutorlms' ),
			'CA' => __( 'Canada', 'sales-by-state-report-for-tutorlms' ),
			'GB' => __( 'United Kingdom', 'sales-by-state-report-for-tutorlms' ),
		);
	}

	/**
	 * State code => name for a country.
	 *
	 * @param string $country Country code.
	 * @return array<string,string>
	 */
	public static function states_for( $country ) {
		$country = strtoupper( (string) $country );

		if ( 'CA' === $country ) {
			return self::canada();
		}

		if ( 'GB' === $country ) {
			return self::united_kingdom();
		}

		if ( 'US' === $country ) {
			return self::united_states();
		}

		return array();
	}

	/**
	 * Two-letter country code from a Tutor LMS billing country value.
	 *
	 * @param mixed $country Country name or ISO code.
	 * @return string
	 */
	public static function country_code( $country ) {
		$country = trim( (string) $country );

		if ( '' === $country ) {
			return '';
		}

		if ( preg_match( '/^[A-Za-z]{2}$/', $country ) ) {
			return strtoupper( $country );
		}

		if ( function_exists( 'tutor_get_country_info_by_name' ) ) {
			$info = tutor_get_country_info_by_name( $country );

			if ( is_array( $info ) && ! empty( $info['alpha_2'] ) ) {
				return strtoupper( (string) $info['alpha_2'] );
			}
		}

		$names = array(
			'united states'            => 'US',
			'united states of america' => 'US',
			'usa'                      => 'US',
			'canada'                   => 'CA',
			'united kingdom'           => 'GB',
			'great britain'            => 'GB',
			'england'                  => 'GB',
		);

		$key = strtolower( $country );

		return isset( $names[ $key ] ) ? $names[ $key ] : '';
	}

	/**
	 * State / province / country code from a Tutor LMS billing state value.
	 *
	 * @param mixed  $state   State name or code.
	 * @param string $country Two-letter country code.
	 * @return string
	 */
	public static function state_code( $state, $country ) {
		$state   = trim( (string) $state );
		$country = strtoupper( (string) $country );

		if ( '' === $state ) {
			return '';
		}

		$states = self::states_for( $country );

		if ( isset( $states[ strtoupper( $state ) ] ) ) {
			return strtoupper( $state );
		}

		if ( isset( $states[ $state ] ) ) {
			return $state;
		}

		foreach ( $states as $code => $label ) {
			if ( 0 === strcasecmp( (string) $label, $state ) ) {
				return (string) $code;
			}
		}

		if ( 'GB' === $country ) {
			$aliases = array(
				'england'           => 'ENG',
				'scotland'          => 'SCT',
				'wales'             => 'WLS',
				'northern ireland'  => 'NIR',
			);

			$key = strtolower( $state );

			if ( isset( $aliases[ $key ] ) ) {
				return $aliases[ $key ];
			}
		}

		return $state;
	}

	/**
	 * US states and DC.
	 *
	 * @return array<string,string>
	 */
	private static function united_states() {
		return array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
		);
	}

	/**
	 * Canadian provinces and territories.
	 *
	 * @return array<string,string>
	 */
	private static function canada() {
		return array(
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon',
		);
	}

	/**
	 * UK countries.
	 *
	 * @return array<string,string>
	 */
	private static function united_kingdom() {
		return array(
			'ENG' => 'England',
			'SCT' => 'Scotland',
			'WLS' => 'Wales',
			'NIR' => 'Northern Ireland',
		);
	}
}
