<?php
/*
 * Plugin Name: Woocommerce Dewalley Payment
 * Description: Pay using a decentralized wallet
 * Author: Gery Wahyu
 * Version: 1.0.0
 *
 */

add_filter( 'woocommerce_payment_gateways', 'dewallet_add_gateway_class' );
function dewallet_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Dewallet_Gateway'; // your class name is here
	return $gateways;
}

add_action( 'plugins_loaded', 'dewallet_init_gateway_class' );
function dewallet_init_gateway_class() {
 
	class WC_Dewallet_Gateway extends WC_Payment_Gateway {
 
 		public function __construct() {
            $this->id = 'dewallet';
            $this->icon = '';
            $this->has_fields = true;
            $this->method_title = 'Dewallet Payment';
            $this->method_description = 'Buy using a decentralized wallet';
         
            $this->supports = array(
                'products'
            );
         
            $this->init_form_fields();
         
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->private_key = $this->get_option( 'private_key' );
            $this->public_key = $this->get_option( 'public_key' );
         
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
         
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 		}
 
 		public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Dewallet Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'UangKita',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Bayar menggunakan dompet UangKita',
                ),
                'public_key' => array(
                    'title'       => 'Stellar Public Key',
                    'type'        => 'text'
                ),
                'private_key' => array(
                    'title'       => 'Stellar Private Key',
                    'type'        => 'password'
                )
            );
	 	}
 
		public function payment_fields() {
            if ( $this->description ) {
                echo wpautop( wp_kses_post( $this->description ) );
            }

            $this->data = array(
                'publicKey' => $this->public_key
            );
            
            $encoded_json = json_encode($this->data);
            $url = urlencode($encoded_json);
        
            echo "<img src='https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$url&choe=UTF-8'/>";
		}
 
	 	public function payment_scripts() {
  
	 	}
 
		public function validate_fields() {
  
		}
 
		public function process_payment( $order_id ) {
  
	 	}
 
		public function webhook() {
  
	 	}
 	}
}

