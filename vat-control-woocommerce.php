/**
 * Function that will check for user role and turn off VAT/tax for that role
 */
function wc_diff_rate_for_user() {

	// check for the user role
	if ( is_user_logged_in() && current_user_can( 'customer' ) ) {

		// set the customer object to have no VAT
		WC()->customer->is_vat_exempt = true;
	}

}
add_action( 'template_redirect', 'wc_diff_rate_for_user', 1 );

/**
 * Function that filters the variable product hash based on user
 */
function wc_get_variation_prices_hash_filter( $hash, $item, $display ) {

	// check for the user role
	if ( is_user_logged_in() && current_user_can( 'customer' ) ) {

		// clear key 2, which is where taxes are
		$hash['2'] = array();
	}

	// return the hash
	return $hash;
}
add_filter( 'woocommerce_get_variation_prices_hash', 'wc_get_variation_prices_hash_filter', 1, 3 );

/**
 * Function that removes the price suffix (inc. Tax) from variable products based on role
 */
function wc_get_price_suffix_filter( $price_display_suffix, $item ) {

	// check for the user role
	if ( is_user_logged_in() && current_user_can( 'customer' ) ) {

		// return blank if it matches
		return '';
	}

	// return if unmatched
	return $price_display_suffix;
}
add_filter( 'woocommerce_get_price_suffix', 'wc_get_price_suffix_filter', 10, 2 );
