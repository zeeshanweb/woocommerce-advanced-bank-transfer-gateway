<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WC_Gateway_Abtpg_Hook' ) )
{
	class WC_Gateway_Abtpg_Hook
	{
		private static $_this;
		public function __construct()
		{
			self::$_this = $this;
			add_filter( 'woocommerce_general_settings', array( $this, 'abtpg_general_settings') );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'abtpg_payment_gateway_filter') );
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'abtpg_order_meta') );
			add_action( 'wp_ajax_file_upload', array( $this, 'file_upload_callback') );
            add_action( 'wp_ajax_nopriv_file_upload', array( $this, 'file_upload_callback') );
			add_action( 'wp_footer', array( $this, 'wp_footer_func') );
			add_action( 'wp_ajax_unlink_uploaded_file', array( $this, 'unlink_uploaded_file') );
            add_action( 'wp_ajax_nopriv_unlink_uploaded_file', array( $this, 'unlink_uploaded_file') );
		}
		public function wp_footer_func()
		{
			?>
            <style>
            .remove_uploaded_file{
				color:#F00;
				margin-left: 14px;
				cursor:pointer;
			}
            </style>
            <?php
		}
		public function abtpg_general_settings( $settings ) 
		{

		  $updated_settings = array();
		
		  foreach ( $settings as $section ) 
		  {
		
			// at the bottom of the General Options section
			if ( isset( $section['id'] ) && 'general_options' == $section['id'] && isset( $section['type'] ) && 'sectionend' == $section['type'] ) 
			 {
		
			  $updated_settings[] = array(
				'name'    => __( 'Receipt Upload Country Restriction', 'wc-payment-gateways-abtpg' ),
				'desc'    => __( 'This controls the position of the receipt.', 'wc-payment-gateways-abtpg' ),
				'id'      => 'woocommerce_receipt_restrict_country',
				'css'     => 'min-width:150px;',
				'std'     => 'left', // WooCommerce < 2.0
				'default' => 'left', // WooCommerce >= 2.0
				'type'    => 'multi_select_countries',
				'desc_tip' =>  true,
			  );
			}		
			$updated_settings[] = $section;
          }

		  return $updated_settings;
		}
		public function abtpg_payment_gateway_filter( $available_gateways ) 
		{
			if ( is_admin() ) return $available_gateways;
			if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) )
			{
				//unset( $available_gateways['abtpg'] );
			}
			return $available_gateways;
		}
		public function abtpg_order_meta( $order )
		{
			$order_id = $order->get_id();
			$advance_bank_file_validation = get_post_meta( $order_id, '_advance_bank_file_validation', true );
			if( !empty($advance_bank_file_validation) )
			{
			?>
                <p class="form-field form-field-wide wc-customer-user" style="font-size:18px;">
                <a href="<?php echo $advance_bank_file_validation;?>"><?php esc_html_e( 'View Receipt', 'wc-payment-gateways-abtpg' ); ?></a>
                </p>
			<?php
			}
		}
		public function file_upload_callback()
		{
			$response_array = array();
			$arr_img_ext = array('image/png', 'image/jpeg', 'image/jpg', 'image/png', 'application/pdf');
			//echo '<pre>';
			//print_r($_FILES['file']);die;
			if(in_array($_FILES['file']['type'], $arr_img_ext)) 
			{
				$upload = wp_upload_bits($_FILES["file"]["name"], null, file_get_contents($_FILES["file"]["tmp_name"]));
				//$upload['url'] will gives you uploaded file path
			}else
			{
				$response_array['success'] = false;
				$response_array['mssg'] = 'Only images and PDF are allowed.';
				echo json_encode($response_array);die;
			}
			if( $upload )
			{
				$response_array['url'] = $upload['url'];
				$response_array['file'] = $upload['file'];
				$response_array['success'] = true;
			}else
			{
				
			}		
			echo json_encode($response_array);die;
			wp_die();
		}
		public function unlink_uploaded_file()
		{
			$response_array = array();
			$file_path = $_POST['get_file_path'];
			$wp_delete_file = wp_delete_file( $file_path );
			$response_array['success'] = true;
			echo json_encode($response_array);die;
		}
	}
	new WC_Gateway_Abtpg_Hook();
}