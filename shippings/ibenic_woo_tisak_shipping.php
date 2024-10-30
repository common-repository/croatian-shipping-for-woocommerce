<?php


    function ibenic_wc_tisak_shipping() {
        if ( ! class_exists( 'WC_TISAK_Shipping' ) ) {
            class WC_TISAK_Shipping extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct( $instance_id = 0 ) {
                    $this->id                 = 'wc_tisak_shipping'; // Id for your shipping method. Should be uunique.
                    $this->method_title       = __( 'Tisak Shipping', 'ibenic_woo_shipping' );  // Title shown in admin
                    $this->method_description = __( 'Calculate shipping cost for package  delivery using Tisak', 'ibenic_woo_shipping' ); // Description shown in admin
                    $this->title              = __("Tisak","ibenic_woo_shipping"); // This can be added as an setting but for this example its forced.
                    $this->init();
                    $countries = array_keys( ibenic_woo_tisak_shipping_countries() );
                    $countries[] = 'HR';
                    $this->countries = $countries;
                    $this->availability = 'including';
                    //display_admin_countries();
                    $this->enabled            = $this->settings["enable"]; // This can be added as an setting but for this example its forced enabled
                    
                    //echo $this->croatia_shipping_value(4, 1);
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                /**
                 * Initialise Gateway Settings Form Fields
                 */
                 function init_form_fields() {
                     $this->form_fields = array(
                     'enable' => array(
                          'title' => __( 'Enable', 'ibenic_woo_shipping' ),
                          'type' => 'checkbox',
                          'description' => __( 'Enable this shipping.', 'ibenic_woo_shipping' ),
                          'default' => 'no'
                          ),
                     );
                    
                } // End init_form_fields()

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {

                    $cost = 0;
                    $weight = 0; 
                    $currency = get_woocommerce_currency();
                    $maximumLength = 0;
                    $maximumHeight = 0;
                    $maximumWidth = 0;

                    if( $currency != 'HRK' ){
                      return false;
                    }

                    $dimensions = 0;
                    foreach ( $package['contents'] as $item_id => $values ) 
                        { 
                            $_product = $values['data']; 
                            $weight = $weight + $_product->get_weight() * $values['quantity']; 
                            $width = wc_get_dimension( $_product->width, 'cm' );
                            if( $maximumWidth < $width ) {
                                $maximumWidth = $width;
                            }

                            $height = wc_get_dimension( $_product->height, 'cm' );
                            if( $maximumHeight < $height ) {
                                $maximumHeight = $height;
                            }

                            $length = wc_get_dimension( $_product->length, 'cm' );
                            if( $maximumLength < $length ) {
                                $maximumLength = $length;
                            }

                            $dimensions = $dimensions + (( $length * $values['quantity']) * $width * $height ); 
                        }

                   
                   $weight = wc_get_weight( $weight, 'kg');

                   if( $weight > 10 ){
                    return false;
                   }

                   $tisak_package = $this->getTisakPackage( $dimensions, $maximumLength, $maximumWidth, $maximumHeight ); 

                    if( $tisak_package == false ) {
                       return false;
                    }

                    if( $package['destination']['country'] == 'HR' ) {

                            $cost = $this->croatia_shipping_value( $tisak_package );

                    }else{

                            $cost = $this->international_shipping_value( $tisak_package, $package['destination']['country'] );

                    }
                   
                   if( $cost == false ) {
                    return false;
                   }

                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $cost,
                        //'calc_tax' => 'per_item'
                    );

                    // Register the rate
                    $this->add_rate( $rate );
                }

                public function getTisakPackage( $dimension, $maximumLength, $maximumWidth, $maximumHeight ) {
                    $packageS = 20 * 20 * 15;
                    $packageM = 30 * 20 * 20;
                    $packageL = 40 * 30 * 15;

                    if( $maximumLength > 40 ){
                        return false;
                    }

                    if( $maximumWidth > 30 ){
                        return false;
                    }

                    if( $maximumHeight > 20 ) {
                        return false;
                    }
 
                    if( $dimension <= $packageS ) {

                      return 's';

                    } elseif ( $dimension <= $packageM ) {

                      return 'm';

                    } elseif ( $dimension <= $packageL ) {

                      return 'l';

                    }

                    return false;
                }

                public function croatia_shipping_value( $package ){
                  switch ( $package ) {
                    case 's':
                      return 15;
                      break;
                    case 'm':
                      return 20;
                      break;
                    default:
                      return 25;
                      break;
                  }

                }  

                public function international_shipping_value( $package, $country ){
                  return ibenic_woo_tisak_get_price_by_country_and_package( $country, $package );
                }

            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'ibenic_wc_tisak_shipping' );