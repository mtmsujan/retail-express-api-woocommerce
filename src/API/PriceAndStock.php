<?php 
namespace API;

class PriceAndStock{

    protected $access_token;

    public function __construct(){
        $this->access_token = get_option('retail_express_api_key');
        add_action( 'rest_api_init', array($this, 'register_routes') );
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

    public function register_routes(){
        register_rest_route( 'retail-express/v1', '/existing-products-to-db', array(
            'methods' => 'GET',
            'callback' => array($this, 'existing_products_to_db'),
        ) );

        register_rest_route( 'retail-express/v1', 'update-stock-price', array(
            'methods' => 'GET',
            'callback' => array($this, 'update_stock_and_price'),
        ) );

    }

    public function existing_products_to_db(){
        // get all woocommerce products and insert them into database table sync_price_and_stock with operation_type = 'simple' or 'variable' and operation_value = product_id and add status = 'pending' and add the product only if sku is not empty
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_price_and_stock';

        // truncate table sync_price_and_stock
        $wpdb->query("TRUNCATE TABLE $table_name");

        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish',
        ));

        foreach($products as $product){
            if(!empty($product->get_sku())){
                $product_id = $product->get_id();
                return $product_id;
                $product_type = $product->get_type();
                $product_sku = $product->get_sku();
                $product_name = $product->get_name();
                $product_price = $product->get_price();
                $product_stock = $product->get_stock_quantity();
                $product_status = $product->get_status();
                $product_created_at = $product->get_date_created();
                $product_updated_at = $product->get_date_modified();
                $product_created_at = $product_created_at->date('Y-m-d H:i:s');
                $product_updated_at = $product_updated_at->date('Y-m-d H:i:s');
                $product_data = array(
                    'operation_type' => $product_type,
                    'operation_value' => $product_id,
                    'status' => 'pending',
                    'created_at' => $product_created_at,
                    'updated_at' => $product_updated_at,
                );
                $wpdb->insert($table_name, $product_data);
            }
        }
        return 'Existing products inserted into database table sync_price_and_stock';
    }

    public function update_stock_and_price(){

        date_default_timezone_set('Asia/Dhaka');
        $current_timestamp = time();
        $formatted_time = date('Y-m-d\TH:i:sP', $current_timestamp);

        // get the first row from database table sync_price_and_stock where status = 'pending' and operation_type = 'simple' or 'variable' and operation_value = product_id and update the stock and price of the product in woocommerce and update the status = 'completed' and update the updated_at = current_timestamp
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_price_and_stock';
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE status = 'pending' AND operation_type = 'simple' ORDER BY id ASC LIMIT 1");
        if($row){
            $product_id = $row->operation_value;

            $product_type = $row->operation_type;
            $product = wc_get_product($product_id);
            if($product){
                $api_product_id_and_auth_token = $this->update_product_stock($product_id);
                $api_product_id = $api_product_id_and_auth_token['api_product_id'];
                $auth_token = $api_product_id_and_auth_token['auth_token'];
                $update_price = $this->update_product_price($product_id, $api_product_id, $auth_token);
                $product_data = array(
                    'status' => 'completed',
                    'updated_at' => current_time('mysql'),
                );
                $wpdb->update($table_name, $product_data, array('id' => $row->id));

                update_post_meta($product_id, 'last_updated', $formatted_time);

                return 'Stock and price updated for product_id = ' . $product_id;
            }
        }
    }

    public function update_product_stock($product_id){
        $product_updated = get_post_meta($product_id, 'last_updated', true);
        // get the product from woocommerce and update the stock of the product in woocommerce
        $product = wc_get_product($product_id);
        if($product){
            $sku = $product->get_sku();
            $auth_token = $this->auth_token();

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://prdinfamsapi001.azure-api.net/v2.1/inventory?sku=' . $sku,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $this->access_token,
                'Accept: application/json',
                'Authorization: Bearer ' . $auth_token,
            ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response_array = json_decode($response, true);
            $stock = isset($response_array['data'][0]['available']) ? $response_array['data'][0]['available'] : 0;

            $api_product_id = isset($response_array['data'][0]['product_id']) ? $response_array['data'][0]['product_id'] : 0;

            $modified_on = isset($response_array['data'][0]['modified_on']) ? $response_array['data'][0]['modified_on'] : "";

            if( empty($product_updated) ){
                // the update the product stock in woocommerce 
                $product->set_stock_quantity($stock);
                $product->save();
            }elseif( !empty($modified_on) && $product_updated < $modified_on ){
                // the update the product stock in woocommerce 
                $product->set_stock_quantity($stock);
                $product->save();
            }else {
                // do nothing
            }

            return [
                'api_product_id' => $api_product_id, 
                'auth_token' => $auth_token,
                'modified_on' => $modified_on,
            ];
        }
    }


    public function update_product_price($product_id, $api_product_id, $auth_token){
        $product_updated = get_post_meta($product_id, 'last_updated', true);
        // get the product from woocommerce and update the stock of the product in woocommerce
        $product = wc_get_product($product_id);
        if($product){
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://prdinfamsapi001.azure-api.net/v2.1/products/' . $api_product_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                    'x-api-key: ' . $this->access_token,
                    'Accept: application/json',
                    'Authorization: Bearer ' . $auth_token,
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response_array = json_decode($response, true);
            $api_price = isset($response_array['sell_price_inc']) ? $response_array['sell_price_inc'] : 0;
            $modified_on = isset($response_array['modified_on']) ? $response_array['modified_on'] : "";

            if( empty($product_updated) ){
                // Update the product price
                update_post_meta($product_id, '_regular_price', $api_price);
                update_post_meta($product_id, '_price', $api_price);
            }elseif( !empty($modified_on) && $product_updated < $modified_on ){
                // Update the product price
                update_post_meta($product_id, '_regular_price', $api_price);
                update_post_meta($product_id, '_price', $api_price);
            }else {
                // do nothing
            }

            return [
                "api_price" => $api_price,
                "modified_on" => $modified_on,
            ];

        }
    }
}