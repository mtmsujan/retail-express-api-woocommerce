<?php 
namespace Shortcodes;

class Shortcodes{

    protected $access_token;
    protected $max_page_number;
    protected $current_page_number;
    protected $product_page_size = 100;

    public function __construct(){
        add_shortcode( 'retail_express', array($this, 'retail_express_shortcode') );
        $this->access_token = get_option('retail_express_api_key');
        $this->max_page_number = get_option('product_max_page_number');
        $this->current_page_number = get_option('product_current_page_number') == '' ? 1 : get_option('product_current_page_number');
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

    // get products from API
    public function get_products(){
        $access_token = $this->auth_token();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://prdinfamsapi001.azure-api.net/v2.1//products?page_number='.$this->current_page_number.'&page_size=' . $this->product_page_size,
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
                'Authorization: Bearer ' . $access_token,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response_array = json_decode($response, true);
        $products = $response_array['data'];

        update_option('product_total_records', $response_array['total_records']);

        return $products;
    }

    // get products count from Database
    public function get_products_count(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_products';
        $sql = "SELECT COUNT(*) FROM $table_name";
        $result = $wpdb->get_var($sql);
        return $result;
    } 

    public function retail_express_shortcode(){
        ob_start();

        $total_product_in_db = $this->get_products_count();
        
        $page_size = $this->product_page_size; // Assuming a page size of 100
        $total_records = get_option('product_total_records') == "" ? 0 : get_option('product_total_records'); // Total number of records from the API
        $max_page_number = ceil($total_records / $page_size);
        update_option('product_max_page_number', $max_page_number);

        if($total_product_in_db >= $total_records){
            return;
        }

        $products = $this->get_products();
        foreach($products as $product){

            global $wpdb;
            $table_name = $wpdb->prefix . 'sync_products';
            $wpdb->insert(
                $table_name,
                array(
                    'operation_type' => 'create_product',
                    'operation_value' => json_encode($product),
                    'status' => 'pending',
                )
            );
            update_option( "product_current_page_number", $this->current_page_number + 1 );
        }

        return [
            'total_records' => $total_records,
            'total_product_in_db' => $total_product_in_db,
            'max_page_number' => $max_page_number,
            'current_page_number' => $this->current_page_number,
        ];

        return ob_get_clean();
    }
}