<?php 
namespace API;

class Products{
    public function __construct(){
        add_action( 'rest_api_init', array($this, 'register_routes') );
    }

    public function register_routes(){
        register_rest_route( 'retail-express/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products'),
        ) );
    }

    public function get_products(){
        $shortcodes = new \Shortcodes\Shortcodes;
        return $shortcodes->retail_express_shortcode();
    }
}