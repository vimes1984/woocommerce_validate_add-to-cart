/**
 * Validate product quantity when added to cart.
 */
function wpbo_minimum_item_quantity_validation( $passed, $product_id, $quantity, $variation_id, $variations ) {
	// Make $woocommerce accessable
		global $woocommerce;
		//loop across cart array checking for quantity and  custom variation weight
		foreach ($woocommerce->cart->cart_contents as $cart_key => $cart_item_array) {
				$amount += $cart_item_array['quantity'] * $cart_item_array['variation']['attribute_weight'];
			}
		
		//$amount from above minus a Global variable called availble this month 
		$availbleprecheckout = $GLOBALS['avail'] - $amount;
		
		//times items to add's weight against attribute wieght for item adding to cart
		$precheck			= $quantity * $variations['attribute_weight'];
		
		//set check weight which should be as is says... 
		$checkweight = $availbleprecheckout - $precheck; 

		if($checkweight < 0){
		// Get the product title for the error statement
		$product = get_product( $product_id );
		$product_title = $product->post->post_title;
		// Add the error
		wc_add_notice( sprintf( "That goes over your limit left this month is: %s and you have  %s grams in your cart" , $GLOBALS['avail'], $amount ) );
		
		} else {

		return true;

	}
}

add_action( 'woocommerce_add_to_cart_validation', 'wpbo_minimum_item_quantity_validation', 10, 5);

/**
 * Validate product quantity on cart update.
 * same as above but for the cart page....
 */
function cs_update_validate_quantity( $valid, $cart_item_key, $values, $quantity ) {
    global $woocommerce;
    		foreach ($woocommerce->cart->cart_contents as $cart_key => $cart_item_array) {
				if($cart_key != $cart_item_key){	
					$amount += $cart_item_array['quantity'] * $cart_item_array['variation']['attribute_weight'];
				}
			}
			$getquantity = $quantity * $values['variation']['attribute_weight'];
			
			$GLOBALS['cartamount'] =  $amount + $getquantity;

		if($GLOBALS['cartamount'] > $GLOBALS['avail']){
        	$valid = false;
			wc_add_notice( sprintf( "you tried to add: %s and your limit is  %s grams" , $GLOBALS['cartamount'],  $GLOBALS['avail']  ), 'error');
			$woocommerce->set_messages();
		} else {

			$valid = true;
		}
		return $valid;
}
 
add_filter( 'woocommerce_update_cart_validation', 'cs_update_validate_quantity', 1, 6 );


//this is what set's the $GLOBALS['avail']; 
	function fused_get_all_products_ordered_by_user($user_id=false){ 
		 $orders=fused_get_all_user_orders($user_id);
		 if(empty($orders))
		   return false;
		 //let us make a list for the query this comes from above...
		 $order_list='('.join(',', $orders).')';

		 global $wpdb;
		 
		 //this will get the orders we need to be checking per user
		 $query_select_order_items="SELECT order_item_id as id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id IN {$order_list}";
		 
		 // this is a big fucker ok this compares all the values on a per order basis then multiplies the total quantity of baggies by the grams purchased  
		 $myrows = $wpdb->get_results( "SELECT order_item_id, weight * quantity total FROM ( SELECT order_item_id, MAX(CASE WHEN meta_key = '_qty' THEN meta_value ELSE 0 END) quantity , MAX(CASE WHEN meta_key = 'weight' THEN meta_value ELSE 0 END) weight FROM wp_woocommerce_order_itemmeta GROUP BY order_item_id) x WHERE  order_item_id  IN ($query_select_order_items);");
		  //and here we add it up!
		  $sum = 0;
		 
		 foreach ($myrows as $key => $value) {$sum+=$value->total;}
		 
		 return  $sum;
	}
	
//this init's the global variables...
	function returnglobals($user_id){
		$current_user = wp_get_current_user();
		$getdaylimit = get_the_author_meta( 'limitgrs', $current_user->ID );
		$prods_orded = fused_get_all_products_ordered_by_user($current_user->ID);
		$monthlimit = $getdaylimit * 30;
		$GLOBALS['avail']  = $monthlimit - $prods_orded;
	}
		add_action('init', 'returnglobals', 1, 1);
