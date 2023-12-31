<?php 
namespace API;

class CustomerAPI{

    protected $api_key;

    public function __construct(){
        add_action( 'rest_api_init', array($this, 'register_routes') );
        $this->api_key = get_option('retail_express_api_key');
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
            'x-api-key: ' . $this->api_key,
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response_array = json_decode($response, true);
        $access_token = $response_array['access_token'];

        return $access_token;

    }

    public function register_routes(){
        register_rest_route( 'retail-express/v1', '/add-customer', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_customer'),
            'args' => array(
                'email' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_email($param);
                    }
                ),
                'first_name' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ),
                'last_name' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param);
                    }
                ),
                
            ),
        ) );
    }

    public function add_customer($request){

        // Retrieve parameters sent in the request
        $email = $request->get_param('email');
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');
        $phone = $request->get_param('phone');
        $billing_first_name = $request->get_param('billing_first_name');
        $billing_last_name = $request->get_param('billing_last_name');
        $billing_country = $request->get_param('billing_country');
        $billing_country = $billing_country == 'United States (US)' ? 'United States' : $billing_country;
        $billing_country_code = $request->get_param('billing_country_code');
        $billing_state = $request->get_param('billing_state');
        $billing_suburb = $request->get_param('billing_suburb');
        $billing_address_line1 = $request->get_param('billing_address_line1');
        $billing_address_line2 = $request->get_param('billing_address_line2');
        $billing_postcode = $request->get_param('billing_postcode');
        $billing_company = $request->get_param('billing_company');

        $shipping_company = $request->get_param('shipping_company');
        $shipping_first_name = $request->get_param('shipping_first_name');
        $shipping_last_name = $request->get_param('shipping_last_name');
        $shipping_country = $request->get_param('shipping_country');
        $shipping_country = $shipping_country == 'United States (US)' ? 'United States' : $shipping_country;
        $shipping_country_code = $request->get_param('shipping_country_code');
        $shipping_state = $request->get_param('shipping_state');
        $shipping_suburb = $request->get_param('shipping_suburb');
        $shipping_address_line1 = $request->get_param('shipping_address_line1');
        $shipping_address_line2 = $request->get_param('shipping_address_line2');
        $shipping_postcode = $request->get_param('shipping_postcode');
        $shipping_mobile = $request->get_param('shipping_mobile');
        $shipping_phone = $request->get_param('shipping_phone');

        $random_number = ceil(rand(1, 1000000));
        // current time in this format 2023-12-14T17:50:37+11:00
        $current_time = date('Y-m-d\TH:i:sP');

        $order_id = $request->get_param('order_id');

        $synced_customer = get_option('synced_customers') ? get_option('synced_customers') : array();

        $synced_customer[] = $email;

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://prdinfamsapi001.azure-api.net/v2.1/customers',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "email": "' . $email . '",
            "first_name": "' . $first_name . '",
            "last_name": "' . $last_name . '",
            "phone": "' . $phone . '",
            "billing_address": {
                "first_name": "' . $billing_first_name . '",
                "last_name": "' . $billing_last_name . '",
                "country": "' . $billing_country . '",
                "country_code": "' . $billing_country_code . '",
                "state": "' . $billing_state . '",
                "suburb": "' . $billing_suburb . '",
                "address_line1": "' . $billing_address_line1 . '",
                "address_line2": "' . $billing_address_line2 . '",
                "postcode": "' . $billing_postcode . '"
            },
            "delivery_addresses": [
                {
                    "id": ' . $order_id . ',
                    "default": true,
                    "modified_on": "' . $current_time . '",
                    "company": "' . $shipping_company . '",
                    "mobile": "' . $shipping_mobile . '",
                    "phone": "' . $shipping_phone . '",
                    "first_name": "' . $shipping_first_name . '",
                    "last_name": "' . $shipping_last_name . '",
                    "country": "' . $shipping_country . '",
                    "country_code": "' . $shipping_country_code . '",
                    "state": "' . $shipping_state . '",
                    "suburb": "' . $shipping_suburb . '",
                    "address_line1": "' . $shipping_address_line1 . '",
                    "address_line2": "' . $shipping_address_line2 . '",
                    "postcode": "' . $shipping_postcode . '"
                }
            ]
        }',
        CURLOPT_HTTPHEADER => array(
            'x-api-key: ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->auth_token(),
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        update_option('synced_customers', $synced_customer);

        return $response;
    }
}