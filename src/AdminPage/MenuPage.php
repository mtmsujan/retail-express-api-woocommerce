<?php 
namespace AdminPage;

class MenuPage {
    // Option name for API key
    private $option_name = 'retail_express_api_key';

    // register the menu page
    public function __construct() {
        add_action('admin_menu', array($this, 'addMenuPage'));
        add_action('admin_init', array($this, 'registerSettings'));
    }

    // add menu page
    public function addMenuPage() {
        add_menu_page(
            'Retail Express API',
            'Retail Express API',
            'manage_options',
            'retail-express',
            array($this, 'menuPageTemplate'),
            'dashicons-store',
            6
        );
    }

    // Render the menu page template
    public function menuPageTemplate() {
        ?>
        <div class="wrap">
            <h1>Retail Express API Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('retail_express_settings_group'); ?>
                <?php do_settings_sections('retail-express'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // Register settings and fields
    public function registerSettings() {
        register_setting('retail_express_settings_group', $this->option_name);

        add_settings_section(
            'retail_express_api_section',
            'API Settings',
            array($this, 'sectionCallback'),
            'retail-express'
        );

        add_settings_field(
            'retail_express_api_key',
            'API Key',
            array($this, 'apiKeyCallback'),
            'retail-express',
            'retail_express_api_section'
        );
    }

    // Section callback (optional)
    public function sectionCallback() {
        echo '<p>Enter your Retail Express API Key below:</p>';
    }

    // API Key field callback
    public function apiKeyCallback() {
        // saved message 
        if (isset($_GET['settings-updated'])) {
            echo '<div id="message" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';
        }
        $api_key = get_option($this->option_name);
        echo '<input type="text" class="widefat" id="retail_express_api_key" name="' . $this->option_name . '" value="' . esc_attr($api_key) . '" />';
    }
}
