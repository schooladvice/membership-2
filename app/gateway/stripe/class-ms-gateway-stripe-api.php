<?php
/**
 * Stripe Gateway API Integration.
 *
 * This object is shared between the Stripe Single and Stripe Subscription
 * gateways.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Stripe_Api extends MS_Model_Option {

	const ID = 'stripe';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var   string $instance
	 */
	public static $instance;

	/**
	 * Holds a reference to the parent gateway (either stripe or stripeplan)
	 *
	 * @since 1.0.1.0
	 * @var   MS_Gateway_Stripe|MS_Gateway_Stripeplan
	 */
	protected $_gateway = null;

	/**
	 * Sets the parent gateway of the API object.
	 *
	 * The parent gateway object is used to fetch the API keys.
	 *
	 * @since 1.0.1.0
	 * @param MS_Gateway $gateway The parent gateway.
	 */
	public function set_gateway( $gateway ) {
		static $Stripe_Loaded = false;

		if ( ! $Stripe_Loaded ) {
			if ( ! class_exists( 'Stripe' ) ) {
				require_once MS_Plugin::instance()->dir . '/lib/stripe-php/lib/Stripe.php';
			}

			do_action(
				'ms_gateway_stripe_load_stripe_lib_after',
				$this
			);

			$Stripe_Loaded = true;
		}

		$this->_gateway = $gateway;

		$secret_key = $this->_gateway->get_secret_key();
		Stripe::setApiKey( $secret_key );
	}

	/**
	 * Get Member's Stripe Customer Object, creates a new customer if not found.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param MS_Model_Member $member The member.
	 * @param string $token The credit card token.
	 */
	public function get_stripe_customer( $member, $token ) {
		$customer = $this->find_customer( $member );

		if ( empty( $customer ) ) {
			$customer = Stripe_Customer::create(
				array(
					'card' => $token,
					'email' => $member->email,
				)
			);
			$member->set_gateway_profile( self::ID, 'customer_id', $customer->id );
			$member->save();
		} else {
			$this->add_card( $member, $customer, $token );
		}

		return apply_filters(
			'ms_gateway_stripe_get_stripe_customer',
			$customer,
			$member,
			$this
		);
	}

	/**
	 * Get Member's Stripe Customer Object.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param MS_Model_Member $member The member.
	 */
	public function find_customer( $member ) {
		$customer_id = $member->get_gateway_profile( self::ID, 'customer_id' );
		$customer = null;

		if ( ! empty( $customer_id ) ) {
			$customer = Stripe_Customer::retrieve( $customer_id );

			// Seems like the customer was manually deleted on Stripe website.
			if ( isset( $customer->deleted ) && $customer->deleted ) {
				$customer = null;
				$member->set_gateway_profile( self::ID, 'customer_id', '' );
			}
		}

		return apply_filters(
			'ms_gateway_stripe_find_customer',
			$customer,
			$member,
			$this
		);
	}

	/**
	 * Add card info to Stripe customer profile and to WordPress user meta.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param MS_Model_Member $member The member model.
	 * @param M2_Stripe_Customer $customer The stripe customer object.
	 * @param string $token The stripe card token generated by the gateway.
	 */
	public function add_card( $member, $customer, $token ) {
		$card = false;

		// 1. Save card to Stripe profile.

		// Stripe API until version 2015-02-16
		if ( ! empty( $customer->cards ) ) {
			$card = $customer->cards->create( array( 'card' => $token ) );
			$customer->default_card = $card->id;
		}

		// Stripe API since 2015-02-18
		if ( ! empty( $customer->sources ) ) {
			$card = $customer->sources->create( array( 'card' => $token ) );
			$customer->default_source = $card->id;
		}

		if ( $card ) {
			$customer->save();
		}

		/**
		 * This action is used by the Taxamo Add-on to check additional country
		 * evidence (CC country).
		 *
		 * @since  1.0.0
		 */
		do_action( 'ms_gateway_stripe_credit_card_saved', $card, $member, $this );

		// 2. Save card to WordPress user meta.

		if ( $card ) {
			$member->set_gateway_profile(
				self::ID,
				'card_exp',
				gmdate( 'Y-m-d', strtotime( "{$card->exp_year}-{$card->exp_month}-01" ) )
			);
			$member->set_gateway_profile( self::ID, 'card_num', $card->last4 );
			$member->save();
		}

		do_action(
			'ms_gateway_stripe_add_card_info_after',
			$customer,
			$token,
			$this
		);
	}

	/**
	 * Creates a one-time charge that is immediately captured.
	 *
	 * This means the money is instantly transferred to our own stripe account.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  M2_Stripe_Customer $customer Stripe customer to charge.
	 * @param  float $amount Amount in currency (i.e. in USD, not in cents)
	 * @param  string $currency 3-digit currency code.
	 * @param  string $description This is displayed on the invoice to customer.
	 * @return M2_Stripe_Charge The resulting charge object.
	 */
	public function charge( $customer, $amount, $currency, $description ) {
                
		$amount = apply_filters(
			'ms_gateway_stripe_charge_amount',
			$amount,
			$currency
		);
                
		$charge = Stripe_Charge::create(
			array(
				'customer' => $customer->id,
				'amount' => intval( $amount * 100 ), // Amount in cents!
				'currency' => strtolower( $currency ),
				'description' => $description,
			)
		);

		return apply_filters(
			'ms_gateway_stripe_charge',
			$charge,
			$customer,
			$amount,
			$currency,
			$description,
			$this
		);
	}

	/**
	 * Fetches an existing subscription from Stripe and returns it.
	 *
	 * If the specified customer did not subscribe to the membership then
	 * boolean FALSE will be returned.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  M2_Stripe_Customer $customer Stripe customer to charge.
	 * @param  MS_Model_Membership $membership The membership.
	 * @return M2_Stripe_Subscription|false The resulting charge object.
	 */
	public function get_subscription( $customer, $membership ) {
		$plan_id = MS_Gateway_Stripeplan::get_the_id(
			$membership->id,
			'plan'
		);

		/*
		 * Check all subscriptions of the customer and find the subscription
		 * for the specified membership.
		 */
		$last_checked = false;
		$has_more = false;
		$subscription = false;

		do {
			$args = array();
			if ( $last_checked ) {
				$args['starting_after'] = $last_checked;
			}
			$active_subs = $customer->subscriptions->all( $args );
			$has_more = $active_subs->has_more;

			foreach ( $active_subs->data as $sub ) {
				if ( $sub->plan->id == $plan_id ) {
					$subscription = $sub;
					$has_more = false;
					break 2;
				}
				$last_checked = $sub->id;
			}
		} while ( $has_more );

		return apply_filters(
			'ms_gateway_stripe_get_subscription',
			$subscription,
			$customer,
			$membership,
			$this
		);
	}

	/**
	 * Creates a subscription that starts immediately.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  M2_Stripe_Customer $customer Stripe customer to charge.
	 * @param  MS_Model_Invoice $invoice The relevant invoice.
	 * @return M2_Stripe_Subscription The resulting charge object.
	 */
	public function subscribe( $customer, $invoice ) {
		$membership = $invoice->get_membership();
		$plan_id = MS_Gateway_Stripeplan::get_the_id(
			$membership->id,
			'plan'
		);

		$subscription = self::get_subscription( $customer, $membership );

		/*
		 * If no active subscription was found for the membership create it.
		 */
		if ( ! $subscription ) {
			$tax_percent = null;
			$coupon_id = null;

			if ( is_numeric( $invoice->tax_rate ) && $invoice->tax_rate > 0 ) {
				$tax_percent = floatval( $invoice->tax_rate );
			}
			if ( $invoice->coupon_id ) {
				$coupon_id = MS_Gateway_Stripeplan::get_the_id(
					$invoice->coupon_id,
					'coupon'
				);
			}

			$args = array(
				'plan' => $plan_id,
				'tax_percent' => $tax_percent,
				'coupon' => $coupon_id,
			);
			$subscription = $customer->subscriptions->create( $args );
		}

		return apply_filters(
			'ms_gateway_stripe_subscribe',
			$subscription,
			$customer,
			$invoice,
			$membership,
			$this
		);
	}

	/**
	 * Creates or updates the payment plan specified by the function parameter.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param array $plan_data The plan-object containing all details for Stripe.
	 */
	public function create_or_update_plan( $plan_data ) {
		$item_id = $plan_data['id'];
		$all_items = MS_Factory::get_transient( 'ms_stripeplan_plans' );
		$all_items = lib3()->array->get( $all_items );

		if ( ! isset( $all_items[$item_id] )
			|| ! is_a( $all_items[$item_id], 'Stripe_Plan' )
		) {
			try {
				$item = Stripe_Plan::retrieve( $item_id );
			} catch( Exception $e ) {
				// If the plan does not exist then stripe will throw an Exception.
				$item = false;
			}
			$all_items[$item_id] = $item;
		} else {
			$item = $all_items[$item_id];
		}

		/*
		 * Stripe can only update the plan-name, so we have to delete and
		 * recreate the plan manually.
		 */
		if ( $item && is_a( $item, 'Stripe_Plan' ) ) {
			$item->delete();
			$all_items[$item_id] = false;
		}

		if ( $plan_data['amount'] > 0 ) {
			$item = Stripe_Plan::create( $plan_data );
			$all_items[$item_id] = $item;
		}

		MS_Factory::set_transient(
			'ms_stripeplan_plans',
			$all_items,
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Creates or updates the coupon specified by the function parameter.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param array $coupon_data The object containing all details for Stripe.
	 */
	public function create_or_update_coupon( $coupon_data ) {
		$item_id = $coupon_data['id'];
		$all_items = MS_Factory::get_transient( 'ms_stripeplan_plans' );
		$all_items = lib3()->array->get( $all_items );

		if ( ! isset( $all_items[$item_id] )
			|| ! is_a( $all_items[$item_id], 'Stripe_Coupon' )
		) {
			try {
				$item = Stripe_Coupon::retrieve( $item_id );
			} catch( Exception $e ) {
				// If the coupon does not exist then stripe will throw an Exception.
				$item = false;
			}
			$all_items[$item_id] = $item;
		} else {
			$item = $all_items[$item_id];
		}

		/*
		 * Stripe can only update the coupon-name, so we have to delete and
		 * recreate the coupon manually.
		 */
		if ( $item && is_a( $item, 'Stripe_Coupon' ) ) {
			$item->delete();
			$all_items[$item_id] = false;
		}

		$item = Stripe_Coupon::create( $coupon_data );
		$all_items[$item_id] = $item;

		MS_Factory::set_transient(
			'ms_stripeplan_coupons',
			$all_items,
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Little hack to force the plugin to store/load the stripe_api data in same
	 * option-field as the stripe-gateway settings.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function option_key() {
		// Option key should be all lowercase.
		$key = 'ms_gateway_stripe';

		// Network-wide mode uses different options then single-site mode.
		if ( MS_Plugin::is_network_wide() ) {
			$key .= '-network';
		}

		return $key;
	}
}
