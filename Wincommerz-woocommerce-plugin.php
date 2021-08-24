<?php
/**
 * Plugin Name: Wincommerz Woocommerce
 * Plugin URI: https://wincommerz.com
 * Author Name: Eishfaq Ahmed
 * Author URI: https://eishfaqahmed.com
 * Description: This is Wincommerz Payment Gateway's woocommerce plugin for wordpress ecommerce.
 * Version: 1.0.0
 * Licence: 1.0.0
 * Licence URI: https://www.gnu.org/licences/gpl-2.0.txt
 * text domain: win_pay_woo
*/

if (!in_array('woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins')))) return;

add_action('plugins_loaded','wincommerz_init',11);

function wincommerz_init() {
    if (class_exists('WC_Payment_Gateway')) {
        class Wincommerz_Payment_Gateway extends WC_Payment_Gateway {
            public function __construct() {
                $this->id = 'win_payment';
                $this->icon = apply_filters('wincommerz-icon',plugins_url('/assets/icon.png',__FILE__));
                $this->has_fields = false;
                $this->method_title = __('Wincommerz Payment Gateway','win_pay_woo');
                $this->method_description = __('This is Wincommerz Payment Gateways woocommerce plugin for wordpress ecommerce.','win_pay_woo');
                
                $this->title = $this->get_option('title');
                $this->user_id = $this->get_option('user_id');
                $this->description = $this->get_option('instructions');
                $this->instructions = $this->get_option('instructions',$this->description);
                
                $this->init_form_fields();
                
                add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this,'process_admin_options'));
                // add_action('woocommerce_thank_you_'.$this->id,array($this,'thank_you_page'));
            }
            
            public function init_form_fields() {
                $this->form_fields = apply_filters('wincommerz_pay_fields',array(
                        'enabled' => array(
                                'title' => __('Enable/Disable','win_pay_woo'),
                                'type' => 'checkbox',
                                'label' => __('Enable or Disable Wincommerz'),
                                'default' => 'no'
                            ),
                        'title' => array(
                                'title' => __('Title','win_pay_woo'),
                                'type' => 'text',
                                'default' => 'Pay with Bkash, Nagad, Rocket, Nexus, Master, Visa',
                                'desc_tip' => true,
                                'description' => __('This is the title what your customers can see, You can customize the title if you want.', 'win_pay_woo')
                            ),
                        'description' => array(
                                'title' => __('Description','win_pay_woo'),
                                'type' => 'textarea',
                                'default' => __('Secure payment by Credit/Debit card and Mobile banking through Wincommerz','win_pay_woo'),
                                'desc_tip' => true,
                                'description' => __('This is the description what your customers can see, You can customize the description if you want.', 'win_pay_woo')
                            ),
                        'instructions' => array(
                                'title' => __('Instructions','win_pay_woo'),
                                'type' => 'textarea',
                                'default' => __('Secure payment by Credit/Debit card and Mobile banking through Wincommerz','win_pay_woo'),
                                'desc_tip' => true,
                                'description' => __('This is the instruction what your customers can see, You can customize the instruction if you want.', 'win_pay_woo')
                            ),
                        'user_id' => array(
                                'title' => __('Username','win_pay_woo'),
                                'type' => 'text'
                            ),
                    ));
            }
            
            public function process_payment($order_id) {
                $order = wc_get_order($order_id);
                $order->update_status('pending-payment',__('Payment is processing','win_pay_woo'));
                $payment = $this->process_payment_with_api($order_id,$order);
                // $order->reduce_order_stock();
                // $order->update_status('completed',__('Payment Completed.','win_pay_woo'));
                // WC()->cart->empty_cart();
                return array(
                        'result' => 'success',
                        //'redirect' => $this->get_return_url($order)
                        'redirect' => $payment['redirect']
                    );
            }
            
            public function process_payment_with_api($order_id,$order) {
                $post_data = array();
                $post_data['clientId'] = $this->user_id;
                $post_data['amount'] = (int) $order->get_total();
                $post_data['uniqueId'] = 'win'.$this->user_id.'-'.$order_id; //it can be your unique invoice_id
                $endpoint = "https://wincommerz.com/payment.php";
                $handle = curl_init();
                curl_setopt($handle, CURLOPT_URL, $endpoint );
                curl_setopt($handle, CURLOPT_TIMEOUT, 30);
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($handle, CURLOPT_POST, 1 );
                curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); //false for local environment
                $content = curl_exec($handle);
                $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                if($code == 200 && !( curl_errno($handle))) {
                    curl_close( $handle);
                    $response = $content;
                } else {
                    curl_close($handle);
                    echo "Failed to connect with API";
                    exit;
                }
                $parse = json_decode($response,true);
                if (isset($parse['gateURL'])) {
                    return array(
                        'result' => 'success',
                        'redirect' => $parse['gateURL']
                    );
                } else {
                    echo "Data parsing error!";
                }
            }
            
            // public function thank_you_page($order_id) {
            //     echo $order_id;exit;
            //     $order->reduce_order_stock();
            //     WC()->cart->empty_cart();
            //     if ($this->instructions) {
            //         echo wpautop($this->instructions);
            //     }
            // }
        }
    }
}

add_filter('woocommerce_payment_gateways', 'add_to_woocommerce_payment_gateway');

function add_to_woocommerce_payment_gateway($gateways) {
    $gateways[] = 'Wincommerz_Payment_Gateway';
    return $gateways;
}


