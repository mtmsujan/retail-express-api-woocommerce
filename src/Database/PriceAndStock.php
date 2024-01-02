<?php 
namespace Database;

class PriceAndStock {
    public function __construct(){

    }

    // create table sync_products if not exists while activating the plugin
    public static function create_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_price_and_stock';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            operation_type varchar(255) NOT NULL,
            operation_value varchar(255) NOT NULL,
            status varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    // remove table sync_products while deactivating the plugin
    public static function remove_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_price_and_stock';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }
}