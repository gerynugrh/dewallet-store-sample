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
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, 
				array($this, 'process_admin_options')
			);
         
            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

			add_action('woocommerce_api_confirm-uangkita', array($this, 'webhook'));

            add_action('woocommerce_thankyou_' . $this->id, 
				array($this, 'payment_instructions')
			);
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
                    'default'     => 'Untuk membayar menggunakan UangKita silahkan scan barcode di akhir proses pembelian menggunakan
                    aplikasi yang terdapat pada handphonemu',
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
		}
 
	 	public function payment_scripts() {
  
	 	}
 
		public function validate_fields() {
            return true;
		}
 
		public function process_payment( $order_id ) {
            global $woocommerce;
            $order 	= wc_get_order( $order_id );
  			// we received the payment
			$order->payment_complete();
			$order->reduce_order_stock();
 
			// some notes to customer (replace true with false to make it private)
			$order->add_order_note( 'Hey, your order is paid! Thank you!', true );
 
			// Empty cart
			$woocommerce->cart->empty_cart();
 
			// Redirect to the thank you page
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
        }
         
        public function payment_instructions( $order_id ) {
            $total;
            $order = wc_get_order($order_id);

            foreach ($order->get_items() as $item_key => $item) {
                $total += $item->get_total();
            }

            $this->data = array(
                'publicKey' => $this->public_key,
                'order_id' => $order_id,
                'total' => $total,
				'webhook' => get_home_url() . "/wc-api/confirm-uangkita"
            );

            $encoded_json = json_encode($this->data);
            $url = urlencode($encoded_json);

            echo "<h2>Lakukan pembayaran dengan melakukan scan pada kode ini</h2>";
            echo "
				<img 
					style='height: 300px; width: 300px; max-height: 300px' 
					src='https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$url&choe=UTF-8'/>
			";
        }
 
		public function webhook() {
  			$order = wc_get_order($_POST['orderId']);
			$transaction_id = $_POST['transactionId'];

			$order->payment_complete();
			$order->reduce_order_stock();

			update_options('webhook_debug', $_POST);
	 	}
 	}
}

