<?php 
namespace WooCommerce;

class CustomerSync {

    protected $access_token;

    public function __construct(){
        // add a sync customers button in each order page in admin panel 
        add_action( 'woocommerce_admin_order_data_after_billing_address', array($this, 'sync_customers_button') );
        $this->access_token = get_option('retail_express_api_key');
    }

    // get auth token from API
    public function auth_token(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.retailexpress.com.au/v2/auth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'x-api-key: ' . $this->access_token,
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response_array = json_decode($response, true);
        $access_token = $response_array['access_token'];

        return $access_token;

    }

    // get customers from WooCommerce
    public function get_customers(){
        // Get all customers from WooCommerce
        $customers = get_users( array( 'role' => 'customer' ) );

        $customer_data = array();

        // Loop through each customer
        foreach ($customers as $customer) {
            $customer_id = $customer->ID; // Get the customer ID
            $customer_email = $customer->user_email; // Get the customer email
            $customer_first_name = $customer->first_name; // Get the customer first name
            $customer_last_name = $customer->last_name; // Get the customer last name

            // Store customer data in an array
            $customer_data[] = array(
                'customer_id' => $customer_id,
                'customer_email' => $customer_email,
                'customer_first_name' => $customer_first_name,
                'customer_last_name' => $customer_last_name,
            );
        }

        return $customer_data;

    }

    // sync customers to Retail Express
    public function sync_customers_button(){
        $order_id = isset($_GET['id']) ? $_GET['id'] : null;
        $order_id = $order_id == null ? $_GET['post'] : $order_id;
        $order = wc_get_order($order_id);
        $customer_email = $order->get_billing_email();
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $billing_phone = $order->get_billing_phone();
        $billing_country_code = $order->get_billing_country();
        $billing_country = WC()->countries->countries[ $order->get_billing_country() ];
        $billing_state = $order->get_billing_state();
        $billing_city = $order->get_billing_city();
        $billing_address_1 = $order->get_billing_address_1();
        $billing_address_2 = $order->get_billing_address_2();
        $billing_postcode = $order->get_billing_postcode();
        $billing_company = $order->get_billing_company();

        $shipping_first_name = $order->get_shipping_first_name();
        $shipping_last_name = $order->get_shipping_last_name();
        $shipping_phone = $order->get_shipping_phone();
        $shipping_country = WC()->countries->countries[ $order->get_shipping_country() ];
        $shipping_country_code = $order->get_shipping_country();
        $shipping_state = $order->get_shipping_state();
        $shipping_city = $order->get_shipping_city();
        $shipping_address_1 = $order->get_shipping_address_1();
        $shipping_address_2 = $order->get_shipping_address_2();
        $shipping_postcode = $order->get_shipping_postcode();
        $shipping_company = $order->get_shipping_company();
        
        
        $synced_customer = get_option('synced_customers') ? get_option('synced_customers') : array();
        
        ?>
        <style>
            /* Define the animation */
            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">

        <script>
            (function($){
                $(document).ready(function(){
                    $('#sync_customers').click(function(){
                        $(this).attr('disabled', true);
                        $(".loading-icon").show();
                        var settings = {
                        "url": "<?php echo home_url(); ?>/wp-json/retail-express/v1/add-customer",
                        "method": "POST",
                        "timeout": 0,
                        "headers": {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                        },
                        "data": JSON.stringify({
                            "email": "<?php echo $customer_email; ?>",
                            "first_name": "<?php echo $billing_first_name; ?>",
                            "last_name": "<?php echo $billing_last_name; ?>",
                            "phone": "<?php echo $billing_phone; ?>",
                            "billing_first_name": "<?php echo $billing_first_name; ?> ",
                            "billing_last_name": "<?php echo $billing_last_name; ?>",
                            "billing_country": "<?php echo $billing_country; ?>",
                            "billing_country_code": "<?php echo $billing_country_code; ?>",
                            "billing_state": "<?php echo $billing_state; ?>",
                            "billing_suburb": "<?php echo $shipping_city; ?>",
                            "billing_address_line1": "<?php echo $billing_address_1; ?>",
                            "billing_address_line2": "<?php echo $billing_address_2; ?>",
                            "billing_postcode": "<?php echo $billing_postcode; ?>",
                            "billing_company": "<?php echo $billing_company; ?>",

                            "shipping_company": "<?php echo $shipping_company; ?>",
                            "shipping_mobile": "<?php echo $shipping_phone; ?>",
                            "shipping_phone": "<?php echo $shipping_phone; ?>",
                            "shipping_first_name": "<?php echo $shipping_first_name; ?>",
                            "shipping_last_name": "<?php echo $shipping_last_name; ?>",
                            "shipping_country": "<?php echo $shipping_country; ?>",
                            "shipping_country_code": "<?php echo $shipping_country_code; ?>",
                            "shipping_state": "<?php echo $shipping_state; ?>",
                            "shipping_suburb": "<?php echo $shipping_city; ?>",
                            "shipping_address_line1": "<?php echo $shipping_address_1; ?>",
                            "shipping_address_line2": "<?php echo $shipping_address_2; ?>",
                            "shipping_postcode": "<?php echo $shipping_postcode; ?>",

                            "order_id": "<?php echo $order_id; ?>"
                        }),
                        };

                        $.ajax(settings).done(function (response) {
                            $(".loading-icon").hide();
                            $(".sync-status").show();
                        });
                        return false;
                    });
                });
            })(jQuery)
            
        </script>
        
            <?php if(in_array($customer_email, $synced_customer)) : ?>
                <button class="button" disabled="disabled" title="Customer is synced already!">Sync Customer</button>
                <h3 class="sync-status">Customer is synced already!</h3>
            <?php else : ?>
                <button type="submit" name="sync_customers" class="button" id="sync_customers">Sync Customer</button>
            <?php endif; ?>
            
            <i style="display: none; font-size: 20px;margin-top: 6px;margin-left: 5px; animation: spin 2s linear infinite;" class="fas fa-spinner loading-icon"></i> 
            <h3 class="sync-status" style="display: none">Customer has been synced successfully!</h3>
        
        <?php 
    }

}