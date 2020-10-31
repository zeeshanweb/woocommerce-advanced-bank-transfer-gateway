<?php
class WC_Gateway_Abtpg extends WC_Payment_Gateway 
{
 
 		/**
 		 * Class constructor
 		 */
 		public function __construct() {
 
		$this->id = 'abtpg'; // payment gateway plugin ID
		$this->icon = apply_filters( 'woocommerce_abtpg_icon', '' ); // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields = true; // in case you need a custom credit card form
		$this->method_title = __( 'Advanced Bank Transfer Gateway', 'wc-payment-gateways-abtpg' );
		$this->method_description = 'Take payments in person via receipt.'; // will be displayed on the options page
 
		// but in this tutorial we begin with simple payments
		$this->supports = array(
			'products'
		);
 
		// Method with all the options fields
		$this->init_form_fields();
	 
		// Load the settings.
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled = $this->get_option( 'enabled' );
		//$this->testmode = 'yes' === $this->get_option( 'testmode' );
		//$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
		//$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
	 
		$this->account_details = get_option(
			'woocommerce_abtpg_accounts',
			array(
				array(
					'account_name'   => $this->get_option( 'account_name' ),
					'account_number' => $this->get_option( 'account_number' ),
					'sort_code'      => $this->get_option( 'sort_code' ),
					'bank_name'      => $this->get_option( 'bank_name' ),
					'iban'           => $this->get_option( 'iban' ),
					'bic'            => $this->get_option( 'bic' ),
				),
			)
		);
		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
	 
		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	 
		// You can also register a webhook here
		// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 
 		}
 
		/**
 		 * Plugin options
 		 */
 		public function init_form_fields()
		{
 
		$this->form_fields = array(
		'enabled' => array(
			'title'       => __('Enable/Disable','wc-payment-gateways-abtpg'),
			'label'       => __('Enable Abtpg Gateway','wc-payment-gateways-abtpg'),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => __('Title','wc-payment-gateways-abtpg'),
			'type'        => 'text',
			'description' => __('This controls the title which the user sees during checkout.','wc-payment-gateways-abtpg'),
			'default'     => __('Advanced Bank Transfer','wc-payment-gateways-abtpg'),
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => __('Description','wc-payment-gateways-abtpg'),
			'type'        => 'textarea',
			'description' => __('This controls the description which the user sees during checkout.','wc-payment-gateways-abtpg'),
			'default'     => __('Make your payment directly into our bank account.','wc-payment-gateways-abtpg'),
			'desc_tip'    => true,
		),
		'instructions' => array(
			'title'       => __('Instructions','wc-payment-gateways-abtpg'),
			'type'        => 'textarea',
			'description' => __('Instructions that will be added to the thank you page and emails.','wc-payment-gateways-abtpg'),
			'default'     => '',
			'desc_tip'    => true,
		),
		'account_details' => array(
				'type' => 'account_details',
			),
	     );
 
	 	}
 
		/**
		 * You will need it if you want your custom credit card form
		 */
		public function payment_fields() 
		{
 
			// ok, let's display some description before the payment form
			if ( $this->description ) 
			{
				// you can instructions for test mode, I mean test card numbers etc.
				$this->description  = trim( $this->description );
				// display the description with <p> tags etc.
				echo wpautop( wp_kses_post( $this->description ) );
			}
 
			// I will echo() the form, but you can close PHP tags and print it directly in HTML
			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
		 
			// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_abtpg_form_start', $this->id );
		 
			// Conditioninal based upload fields display
			 $display_file_upload = $this->file_upload_condition();
			 if( $display_file_upload === true )
			 {
			?>
			<div class="form-row form-row-first" style="width:100%;">
			<label><?php esc_html_e( 'Upload your receipt here.', 'wc-payment-gateways-abtpg' ); ?><span class="required">*</span></label>
			<input type="hidden" name="advance_bank_file_validation" id="advance_bank_file_validation" value="" />
            <input type="hidden" name="file_absolute_path" id="file_absolute_path" value="" />
            <div class="show_file_path"></div>
			<input accept="image/x-png,image/jpg,image/jpeg,application/pdf" type="file" id="file_upload" name="upload_file" />
			</div>
			<?php
			 }
			echo '<div class="clear"></div>';
		 
			do_action( 'woocommerce_abtpg_form_end', $this->id );
		 
			echo '<div class="clear"></div></fieldset>';
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() 
		{
 
			// we need JavaScript to process a token only on cart/checkout pages, right?
			if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) 
			{
				return;
			}
	 
			// if our payment gateway is disabled, we do not have to enqueue JS too
			if ( 'no' === $this->enabled ) 
			{
				return;
			}
		 
			// do not work with card detailes without SSL unless your website is in a test mode
			if ( !is_ssl() ) {
				//return;
			}
		 
			// and this is our custom JS in your plugin directory that works with token.js
			wp_register_script( 'woocommerce_abtpg', WC_ABTPG_PLUGIN_URL.'/assets/js/abtpg.js', array( 'jquery' ) );
		 
			// in most payment processors you have to use PUBLIC KEY to obtain a token
			wp_localize_script( 'woocommerce_abtpg', 'abtpg_params', array(
				'abtpg_ajaxurl' => admin_url( 'admin-ajax.php' ),
			) );
		 
			wp_enqueue_script( 'woocommerce_abtpg' );
 
	 	} 
		/*
 		 * File upload validation
		 */
		public function file_upload_condition()
		{
			 $country_restrict = get_option( 'woocommerce_receipt_restrict_country' );
			 if( !empty($country_restrict) && !in_array(WC()->customer->get_billing_country(), $country_restrict) )
			 {
				 return false;
			 }
			 if( empty($country_restrict) || in_array(WC()->customer->get_billing_country(), $country_restrict) )
			 {
				 return true;
			 }
			 return '';
		}
		/*
 		 * Fields validation
		 */
		public function validate_fields() 
		{		
			 //echo '<pre>';
			 //print_r($_POST);
			 $validate_field = $this->file_upload_condition();
			 if( $validate_field === false )
			 {
				 return true;
			 }
			 global $wp_filesystem;
			 require_once ( ABSPATH . '/wp-admin/includes/file.php' );
			 WP_Filesystem();
			 $local_file_url = $_POST[ 'advance_bank_file_validation' ];
			 $local_file_path = $_POST[ 'file_absolute_path' ];
			 if( empty($local_file_url) ) 
			 {
					wc_add_notice(  'Please upload you payment receipt!', 'error' );
					return false;
			 }
			 if( isset($local_file_path) && !$wp_filesystem->exists( $local_file_path ) )
			 {
				 wc_add_notice(  'Receipt file is not exist!', 'error' );
				 return false;
			 }
			 return true;
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) 
		{
			$order = wc_get_order( $order_id );
			$advance_bank_file_validation = $_POST['advance_bank_file_validation'];
            $order->update_meta_data( '_advance_bank_file_validation', $advance_bank_file_validation );
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-payment-gateways-abtpg' ) );
					
			// Reduce stock levels
			$order->reduce_order_stock();
					
			// Remove cart
			WC()->cart->empty_cart();
					
			// Return thankyou redirect
			return array(
				'result'    => 'success',
				'redirect'  => $this->get_return_url( $order )
			);
		}
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() 
		{ 
			$order = wc_get_order( $_GET['id'] );
			$order->payment_complete();
			$order->reduce_order_stock();		 
			update_option('webhook_debug', $_GET);
 
	 	}
		/**
	 * Generate account details html.
	 *
	 * @return string
	 */
	public function generate_account_details_html() 
	{

		ob_start();

		$country = WC()->countries->get_base_country();
		$locale  = $this->get_country_locale();

		// Get sortcode label in the $locale array and use appropriate one.
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'wc-payment-gateways-abtpg' );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Account details:', 'wc-payment-gateways-abtpg' ); ?></th>
			<td class="forminp" id="bacs_accounts">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
							<tr>
								<th class="sort">&nbsp;</th>
								<th><?php esc_html_e( 'Account name', 'wc-payment-gateways-abtpg' ); ?></th>
								<th><?php esc_html_e( 'Account number', 'wc-payment-gateways-abtpg' ); ?></th>
								<th><?php esc_html_e( 'Bank name', 'wc-payment-gateways-abtpg' ); ?></th>
								<th><?php echo esc_html( $sortcode ); ?></th>
								<th><?php esc_html_e( 'IBAN', 'wc-payment-gateways-abtpg' ); ?></th>
								<th><?php esc_html_e( 'BIC / Swift', 'wc-payment-gateways-abtpg' ); ?></th>
							</tr>
						</thead>
						<tbody class="accounts">
							<?php
							$i = -1;
							if ( $this->account_details ) {
								foreach ( $this->account_details as $account ) {
									$i++;

									echo '<tr class="account">
										<td class="sort"></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $account['account_name'] ) ) . '" name="bacs_account_name[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['account_number'] ) . '" name="bacs_account_number[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $account['bank_name'] ) ) . '" name="bacs_bank_name[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['sort_code'] ) . '" name="bacs_sort_code[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['iban'] ) . '" name="bacs_iban[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( $account['bic'] ) . '" name="bacs_bic[' . esc_attr( $i ) . ']" /></td>
									</tr>';
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="7"><a href="#" class="add button"><?php esc_html_e( '+ Add account', 'wc-payment-gateways-abtpg' ); ?></a> <a href="#" class="remove_rows button"><?php esc_html_e( 'Remove selected account(s)', 'wc-payment-gateways-abtpg' ); ?></a></th>
							</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#bacs_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#bacs_accounts').find('tbody .account').length;

							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_sort_code[' + size + ']" /></td>\
									<td><input type="text" name="bacs_iban[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bic[' + size + ']" /></td>\
								</tr>').appendTo('#bacs_accounts table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}
	/**
	 * Save account details table.
	 */
	public function save_account_details() 
	{

		$accounts = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification already handled in WC_Admin_Settings::save()
		if ( isset( $_POST['bacs_account_name'] ) && isset( $_POST['bacs_account_number'] ) && isset( $_POST['bacs_bank_name'] )
			 && isset( $_POST['bacs_sort_code'] ) && isset( $_POST['bacs_iban'] ) && isset( $_POST['bacs_bic'] ) ) {

			$account_names   = wc_clean( wp_unslash( $_POST['bacs_account_name'] ) );
			$account_numbers = wc_clean( wp_unslash( $_POST['bacs_account_number'] ) );
			$bank_names      = wc_clean( wp_unslash( $_POST['bacs_bank_name'] ) );
			$sort_codes      = wc_clean( wp_unslash( $_POST['bacs_sort_code'] ) );
			$ibans           = wc_clean( wp_unslash( $_POST['bacs_iban'] ) );
			$bics            = wc_clean( wp_unslash( $_POST['bacs_bic'] ) );

			foreach ( $account_names as $i => $name ) {
				if ( ! isset( $account_names[ $i ] ) ) {
					continue;
				}

				$accounts[] = array(
					'account_name'   => $account_names[ $i ],
					'account_number' => $account_numbers[ $i ],
					'bank_name'      => $bank_names[ $i ],
					'sort_code'      => $sort_codes[ $i ],
					'iban'           => $ibans[ $i ],
					'bic'            => $bics[ $i ],
				);
			}
		}
		// phpcs:enable

		update_option( 'woocommerce_abtpg_accounts', $accounts );
	}
		/**
	 * Get country locale if localized.
	 *
	 * @return array
	 */
	public function get_country_locale() 
	{

		if ( empty( $this->locale ) ) 
		{

			// Locale information to be used - only those that are not 'Sort Code'.
			$this->locale = apply_filters(
				'woocommerce_get_bacs_locale',
				array(
					'AU' => array(
						'sortcode' => array(
							'label' => __( 'BSB', 'wc-payment-gateways-abtpg' ),
						),
					),
					'CA' => array(
						'sortcode' => array(
							'label' => __( 'Bank transit number', 'wc-payment-gateways-abtpg' ),
						),
					),
					'IN' => array(
						'sortcode' => array(
							'label' => __( 'IFSC', 'wc-payment-gateways-abtpg' ),
						),
					),
					'IT' => array(
						'sortcode' => array(
							'label' => __( 'Branch sort', 'wc-payment-gateways-abtpg' ),
						),
					),
					'NZ' => array(
						'sortcode' => array(
							'label' => __( 'Bank code', 'wc-payment-gateways-abtpg' ),
						),
					),
					'SE' => array(
						'sortcode' => array(
							'label' => __( 'Bank code', 'wc-payment-gateways-abtpg' ),
						),
					),
					'US' => array(
						'sortcode' => array(
							'label' => __( 'Routing number', 'wc-payment-gateways-abtpg' ),
						),
					),
					'ZA' => array(
						'sortcode' => array(
							'label' => __( 'Branch code', 'wc-payment-gateways-abtpg' ),
						),
					),
				)
			);

		}

		return $this->locale;

	}
 	}