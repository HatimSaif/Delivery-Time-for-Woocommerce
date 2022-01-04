<?php
/*
Plugin Name: Delivery Time for WooCommerce
Description: To show delivery time in woocommerce product detail and archive page.
Version: 1.0
Author: HatimS
Author URI:
*/
defined('ABSPATH') or die('Don\'t have permission to access the file');

class deliveryTimeWoocommerce {
  public function __construct() {
    // Check if the Woocommerce plugin is install or not.
    add_action('plugins_loaded', array($this,'checkDependency'));
    //Add Settings tab
    add_filter( 'woocommerce_settings_tabs_array', array($this,'addSettingsTab'),50,1);
    // Add fields in Settings tab
    add_action( 'woocommerce_settings_tabs_delivery_time', array($this,'settingsTab') );
    // Update fields in Settings tab
    add_action( 'woocommerce_update_options_delivery_time', array($this,'updateSettings') );
    // Add fields in general tab in product page 
    add_action( 'woocommerce_product_options_general_product_data', array($this, 'woocommerceProductDeliveryTime') ); 
    // Save fields in general tab in product page 
    add_action( 'woocommerce_process_product_meta', array($this, 'woocommerceProductDeliveryTimeSave') );
    // Show text on Single page
    add_action( 'woocommerce_single_product_summary', array($this,'showDeliveryTime'));
    // Show text on Archive page
    add_action( 'woocommerce_after_shop_loop_item_title', array($this,'showDeliveryTime'));
    // Enqueue Scripts
    add_action('wp_enqueue_scripts', array($this,'enqueueScripts'));
    // Ajax Call to Show description 
    add_action( 'wp_ajax_nopriv_deliveryTimeDesc', array($this, 'getDeliveryTimeDescFunc'));
    add_action( 'wp_ajax_deliveryTimeDesc', array($this, 'getDeliveryTimeDescFunc'));

  }
  public function checkDependency(){
      if (!class_exists('woocommerce')) {
          add_action('admin_notices',array($this, 'dependencyError'));
          return;
      }
  }
  public function dependencyError(){
      ?>
      <div class="error">
          <p>
              <?php _e('Delivery Time for WooCommerce requires Woocommerce plugin to work. Please install and activate it.'); ?>
          </p>
      </div>
      <?php
  }
  public function addSettingsTab($settingsTabs){
    $settingsTabs['delivery_time'] = __( 'Delivery Time', 'woocommerce-delivery-time' );
    return $settingsTabs;
  }
  public function settingsTab() {
      woocommerce_admin_fields($this->getFields());
  }
  public function updateSettings() {
      woocommerce_update_options( $this->getFields() );
  }
  public function getFields() {
      $settings = array(
          'section_title' => array(
              'name'     => 'Delivery Time for Woocommerce',
              'type'     => 'title',
              'desc'     => '',
              'id'       => 'wc_settings_tab_delivery_time_title'
          ),
          array(
              'name' => 'Delivery Time',
              'type' => 'number',
              'desc' => 'This time will show on the page',
              'custom_attributes' => array('required' => 'true', 'min' => '0'),
              'id'   => 'wc_settings_tab_delivery_time_name'
          ),
          array(
            'name'         => 'Display on',
            'id'            => 'wc_settings_tab_delivery_time_display_on',
            'type'          => 'multiselect',
            'custom_attributes' => array('multiple' => 'true'),
            'options'       => array (
              ''    => __('--- Select Option ---', 'woocommerce' ),
              'singlePage'    => __('Single Page', 'woocommerce' ),
              'archivePage'        => __('Archive Page', 'woocommerce' )
            ),
        ),
          array(
              'name' =>'Color',
              'type' => 'color',
              'id' => 'wc_settings_tab_delivery_time_color'
          ),
          'section_end' => array(
            'type' => 'sectionend',
            'id' => 'wc_settings_tab_delivery_time_end'
      )
      );
      return apply_filters( 'wc_settings_tabs_delivery_time', $settings );
  }
  public function woocommerceProductDeliveryTime(){
      global $woocommerce, $post;
      $deliveryTimeVal =  get_post_meta($post->ID, '_custom_product_delivery_time', true);
      $deliveryTimeDescVal =  get_post_meta($post->ID, '_custom_product_delivery_time_desc', true);

      echo '<div class=" product_custom_field ">';
      woocommerce_wp_text_input(
        array(
            'id' => '_custom_product_delivery_time',
            'label' => __('Delivery time'),
            'type' => 'number',
            'value' => $deliveryTimeVal,
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '-1'
            )
        )
      );
      woocommerce_wp_textarea_input(
        array(
            'id' => '_custom_product_delivery_time_desc',
            'label' => __('Delivery time description'),
            'value' => $deliveryTimeDescVal
        )
      );
      echo '</div>';
  }
  public function woocommerceProductDeliveryTimeSave($post_id){
    $woocommerce_product_delivery_time = $_POST['_custom_product_delivery_time'];
    $woocommerce_product_delivery_time_desc = $_POST['_custom_product_delivery_time_desc'];
    if (!empty($woocommerce_product_delivery_time) || $woocommerce_product_delivery_time == 0)
        update_post_meta($post_id, '_custom_product_delivery_time',  esc_attr($woocommerce_product_delivery_time));
    if(!empty($woocommerce_product_delivery_time_desc))
        update_post_meta($post_id, '_custom_product_delivery_time_desc',  esc_attr($woocommerce_product_delivery_time_desc));
  }
  public function showDeliveryTime(){
    $id = get_the_ID();
    $text = '';
    $deliveryTimeVal =  get_post_meta($id, '_custom_product_delivery_time', true);
    $deliveryTextDisplayOn =  get_option('wc_settings_tab_delivery_time_display_on', true);
    $deliveryTextColor =  get_option('wc_settings_tab_delivery_time_color', true);
    /*
    1.. If field display on is selected for both pages
    $flag = true
    2.. elseIf field display on have single page value
    $flag = true
    3.. elseIf field display on have archive page value
    $flag = true
    else
    $flag = false

    If "$flag = true" print Delivery time text
    */

    $flag = (in_array("archivePage", $deliveryTextDisplayOn) && in_array("singlePage", $deliveryTextDisplayOn) ? true : ( in_array("singlePage", $deliveryTextDisplayOn) && is_singular() ? true : ( in_array("archivePage", $deliveryTextDisplayOn) && is_archive() ? true : false) ));

    if(empty($deliveryTimeVal) || $deliveryTimeVal == 0){
      $globalDeliveryTimeVal = get_option( 'wc_settings_tab_delivery_time_name', true );
      $deliveryTimeVal = $globalDeliveryTimeVal;

      if(empty($globalDeliveryTimeVal) || $globalDeliveryTimeVal == 0)
          return;
    }
    if($deliveryTimeVal == -1)
      return;
    $text .= '<div class="delivery_details">';
      $text .= '<span id="delivery_time" data-id="'.$id.'" style="color:'.$deliveryTextColor.';">Delivery time: '.$deliveryTimeVal.' day(s)</span>';
      $text .= '<span id="delivery_time_desc" style="display: block;"></span>';
    $text .= '</div>';

    if($flag)
      echo $text;
  }
  public function enqueueScripts() {
    wp_enqueue_style('deliveryTime', plugins_url('css/style.css', __FILE__),'',null);
    wp_enqueue_script('deliveryTime', plugins_url('scripts/scripts.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script( 'deliveryTime', 'delivery_time', array('ajaxurl' => admin_url('admin-ajax.php')));
  }
  public function getDeliveryTimeDescFunc(){
    $notFound = 'No description found!';
    if(isset($_POST['id'])){
      $id = $_POST['id'];
        $deliveryTimeVal =  get_post_meta($id, '_custom_product_delivery_time_desc', true);
        ( !empty($deliveryTimeVal) ? wp_send_json_success($deliveryTimeVal) : wp_send_json_success($notFound));
        wp_die();
    }else{
      wp_send_json_error($notFound);
    }
  }
}
  $deliveryTimeWoocommerce = new deliveryTimeWoocommerce();