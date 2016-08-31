<?php
/**
* Plugin Name: APS 2.3 Guidlines
* Plugin URI: http://itconnect.uw.edu
* Author: Nick Rohde
* Version: 1.0.0
* License GPLv2
* Description: Adds the APS 2.3 functionality to IT Connect
*/
$aps23 = new Aps_Widget();
class Aps_Widget
{
    /**
     * If you should add the script or not
     *
     * @var bool
     */
    private $addScript = false;

    public function __construct()
    {
    	// Define the shortcode
        add_shortcode('aps23', array($this, 'aps23_content'));

        // Add styles and scripts to the page
        add_action('wp_footer', array($this, 'add_aps_scripts'));
    }

    public function aps23_content( $attr, $content )
    {
        $this->addScript = true;

        require( plugin_dir_path( __FILE__ ) . 'inc/aps-content.php');
        
    }

    public function add_aps_scripts()
    {
        if(!$this->addScript)
        {
            return false;
        }

        wp_register_script('aps23_js', plugin_dir_url(__FILE__) . 'js/aps23_itc.js', array('jquery'), '1.0', true);
  		wp_enqueue_script('aps23_js');

  		wp_register_style('aps23_css', plugin_dir_url(__FILE__) . 'css/aps23_itc.css');
  		wp_enqueue_style('aps23_css');
    }
}