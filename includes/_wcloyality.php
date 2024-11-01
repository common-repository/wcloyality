<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

/**
 *  Loyality pos api. Available to implement pos systems and 
 *  websites. 
 * 
 * Author: Philip Neves (Neves Software Inc.)
 * Copyright: © 2019 Neves Software Inc.
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */


require_once plugin_dir_path(__FILE__) . "LoyalityPosApi.php";

ini_set('log_errors', 1);
/**
 * Loyality for Woocoomerce plugin class. 
 * Provides all the functionality for wcLoyality.
 */
class WcLoyality
        {
        static $inactive_gift_cards = [];


        /**
         * Constructor 
         * 
         * Registers actions to execute the plugin. 
         */
        function __construct()
                {
                ini_set("log_errors", 1);

                if (is_admin()) 
                        {
                        add_action('admin_menu', array($this, 'wcloyality_options_page' ));
                        add_action('admin_init', array($this, 'init'));
                        add_action( 'updated_option_wc_loyality_options', array($this, 'action_updated_option_callback'), 10, 3 ); 
                        add_action( 'wp_enqueue_scripts', array($this, 'wcloyality_js_scripts')); 
                        add_action( 'wp_enqueue_scripts', array($this, 'wcloyality_css_style' )); 
                        do_action('wp_enqueue_scripts');
                        }


                add_action('woocommerce_checkout_order_processed', array($this, 'initiate_purchase_callback'), 10, 3);
                add_action('woocommerce_order_status_changed', array($this, 'payment_successful_result'),10, 3); 
                //add_action('woocommerce_order_status_changed', array($this, 'payment_failed'), 10, 3);
               
                add_action('woocommerce_after_order_notes', array($this, 'generate_recipient_checkout_fields'));
                }

        /**
         * Add menu item for the wcLoyality Options Page. 
         *
         * @return void
         */
        function wcloyality_options_page() 
                {
                add_menu_page( 'wcloyality',
                                'WC Loylality',
                                'manage_options',
                                'wcloyality',
                                array($this, 'wcloyality_options_page_html')
                                );        
                }   
       
        /**
         * Initialize the settings page. 
         *
         * @return void
         */
        function init()
                {
                add_option( 'wc_loyality_options', '');

                register_setting(
                        'wcloyality', 
                        'wc_loyality_options' ); 
                
                add_settings_section(
                        'wcloyality_section_api_key', 
                        __('Loyality API Key and Organization ID', 'wcloyality'),
                        array($this, 'section_api_key_callback'),
                        'wcloyality');

                add_settings_field(
                        'wcloyality_field_api_key',
                        __('API Key', 'wcloyality'),
                        array($this, 'field_api_key_callback'),
                        'wcloyality',
                        'wcloyality_section_api_key',
                        [    
                        'label_for' => 'wcloyality_field_api_key',
                        'class' => 'wcloyality_section_row',
                        'wcloyality_custom_data' => 'custom'
                        ]);

                              
                add_settings_field(
                        'wcloyality_field_organization_id',
                        __('Organization ID', 'wcloyality'),
                        array($this, 'field_organization_id_callback'),
                        'wcloyality',
                        'wcloyality_section_api_key',
                        [    
                        'label_for' => 'wcloyality_field_organization_id',
                        'class' => 'wcloyality_section_row',
                        'wcloyality_custom_data' => 'custom'
                        ]);

                add_settings_section('wcloyality_section_template', 
                        __('Select E-Gift Card Template', 'wcloyality'),
                        array($this, 'section_template_callback'),
                       'wcloyality');

                add_settings_field(
                        'wcloyality_field_template_id',
                        __('Gift Card Template', 'wcloyality'),
                        array($this, 'field_template_id_callback'),
                        'wcloyality',
                        'wcloyality_section_template',
                        [    
                        'label_for' => 'wcloyality_field_template_id',
                        'class' => 'wcloyality_section_row',
                        'wcloyality_custom_data' => 'custom'
                        ]);

                add_settings_section(
                        'wcloyality_section_giftcard_amounts',
                        __('Select Giftcard Amounts', 'wcloyality'),
                        array($this, 'section_gift_card_amount_callback'),
                        'wcloyality');

                add_settings_field(
                        'wcloyality_field_gift_card_amounts',
                        __('Gift Card Amounts', 'wcloyality'),
                        array($this, 'field_gift_card_amounts_callback'),
                        'wcloyality',
                        'wcloyality_section_giftcard_amounts',
                        [
                        'label_for' => 'wcloyality_field_gift_card_amounts',
                        'class' => 'wcloyality_section_row',
                        'wcloyality_custom_data' => 'custom'       
                        ]);

                 add_settings_section(  
                        'wcloyality_section_vouchers',
                        __('Voucher Blocks', 'wcloyality'),
                        array($this, 'section_voucher_block_select'),
                        'wcloyality');

                add_settings_field(
                        'wcloyality_field_voucher_blocks',
                        __('Voucher Blocks', 'wcloyality'),
                        array($this, 'wcloyality_field_voucher_blocks_callback'),
                        'wcloyality', 
                        'wcloyality_section_vouchers',
                        [
                        'label_for' => 'wcloyality_field_voucher_blocks',
                        'class' => 'field_voucher_blocks',
                        'wcloyality_custom_data' => 'custom'       
                        ]); 
                }
        /**
         * Voucher block select section generator. 
         *
         * @return void
         */
        function section_voucher_block_select()
                {
                ?>
                <p id="<php echo esc_attr($args['id']); ?>">
                        <?php esc_html_e( 'Select vouchers to sell on your website. The plugin will create those vouchers that you select when you save the settings.', 'wcloyality' ); ?>
                </p>
                <?php           
                }

        /**
         *  Loyailty field voucher blocks callback 
         *
         *  Description: this creates a field for selecting voucher blocks created 
         * in the Loyality platform. The selected voucher blocks will create a product 
         * for sale in woocommerce once the form is saved.
         * 
         * @param [type] $args
         * @return void
         */
        function wcloyality_field_voucher_blocks_callback($args)
                {
                $options = get_option('wc_loyality_options');

                $posapi = new LoyalityPosApi();

                // output save settings button
                $posapi->setApiKey($options['wcloyality_field_api_key']);
                $posapi->setOrganizationId($options['wcloyality_field_organization_id']);
                
                $voucherBlocks = $posapi->get_voucher_block_list(); 

                if ($voucherBlocks['success'] == true) :
                        ?>
                        <select id="<?php echo esc_attr($args['label_for']); ?>"
                                data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                                name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>][]"
                                multiple
                                class="voucher_block_list">
                                <?php   
                                
                                foreach($voucherBlocks['result'] as $voucher) :
                                ?>
                                        <option value="<?php echo $voucher['voucher_block_id']; ?>"><?php echo $voucher['name']; ?></option>
                                <?php
                                endforeach;
                                ?>
                        </select>
                        <?php
                else :
                        ?>
                                <input  type="hidden" 
                                        id="<?php echo esc_attr($args['label_for']); ?>"
                                        data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                                        name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>]);" />

                                <p><?php echo $voucherBlocks['errors'] ?></p>
                        <?php
                endif;

                if (!empty($options[$args['label_for']]))
                        {
                        echo "selected voucher not empty.";

                        $posapi = new LoyalityPosApi();

                        $options = get_option('wc_loyality_options'); 
                        
                        $posapi->setApiKey($options['wcloyality_field_api_key']);
                        $posapi->setOrganizationId($options['wcloyality_field_organization_id']);;        
                        
                        $voucher_blocks = $posapi->get_voucher_block_list(); 

                        if ($voucher_blocks['success'] == true)
                                {
                                echo "voucher blocks successfully returned."; 

                                foreach($options[$args['label_for']] as $selected_voucher)
                                        {
                                        echo "processing $selected_voucher"; 
                                        foreach($voucher_blocks['result'] as $voucher)
                                                {
                                                if ($voucher['voucher_block_id'] == $selected_voucher)
                                                        {
                                                        echo "voucher block found.";

                                                        $image_id = $this->save_image($voucher['encoded_image'], $voucher['name']) ;      
                                                        
                                                        $product = new WC_Product(); 
                                                        $product->set_sku(uniqid("loyality_" )); 
                                                        $product->set_name($voucher['name']); 
                                                        $product->set_regular_price($voucher['purchase_amount']);
                                                        $product->set_description($voucher['description']);
                                                        $product->set_virtual(true);
                                                        $product->set_image_id($image_id);

                                                        $data = [];

                                                        $voucher_block_id = new WC_Product_Attribute();
                                                        $voucher_block_id->set_visible(false);        
                                                        $voucher_block_id->set_name('voucher_block_id');
                                                        $voucher_block_id->set_variation(false); 
                                                        $voucher_block_id->set_position(0); 
                                                        $voucher_block_id->set_options([ $voucher['voucher_block_id'] ]); 

                                                        $data[] = $voucher_block_id;

                                                        $product->set_attributes($data);

                                                        $result = $product->save();

                                                        echo print_r($result, true); 
                                                        }
                                                }
                                        }
                                }

                        $options[$args['label_for']] = null;
                        update_option('wc_loyality_options', $options); 
                        }
                }

        /**
         * gift card amounts callback. 
         * 
         * creates a field for selecting the gift card amounts for sale 
         * on the website. When the voucher amount and template is selected
         * it will create a product inside woocommerce that is connected 
         * with gift cards provided by the Loyality platform. 
         *
         * @param [type] $args
         * @return void
         */
        function field_gift_card_amounts_callback($args)
                {
                $options = get_option('wc_loyality_options');

                $posapi = new LoyalityPosApi();
                // output save settings button
                $posapi->setApiKey($options['wcloyality_field_api_key']);
                $posapi->setOrganizationId($options['wcloyality_field_organization_id']);

                $amountList = $posapi->get_gift_card_load_amounts(); 
                
                if ($amountList['success'] == true) :
                        ?>
                        <select id="<?php echo esc_attr($args['label_for']); ?>"
                                data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                                name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>][]"
                                multiple
                                class="gift_card_purchase_amount">
                                <?php   
                                
                                foreach($amountList['result'] as $amount) :
                                ?>
                                        <option value="<?php echo $amount['giftcard_purchase_amount_id']; ?>"><?php echo $amount['amount']; ?></option>
                                <?php
                                endforeach;
                                ?>
                        </select>
                        <?php
                else :
                        ?>
                                <input  type="hidden" 
                                        id="<?php echo esc_attr($args['label_for']); ?>"
                                        data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                                        name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>][]">
                                <p><?php echo $amountList['errors']; ?></p>
                        <?php
                endif;

               

                if (!empty($options[ $args['label_for'] ]))
                        {
                        echo "gift card amounts found."; 
                        $posapi = new LoyalityPosApi();

                        $options = get_option('wc_loyality_options'); 
                        
                        $posapi->setApiKey(sanitize_text_field($options['wcloyality_field_api_key']));
                        $posapi->setOrganizationId(sanitize_text_field($options['wcloyality_field_organization_id']));
        
                        $gift_card_amounts = $posapi->get_gift_card_load_amounts();

                       
                        if ($gift_card_amounts['success'] == true)
                                {
                                $template_image_id = null;
                                
                                $templateList = $posapi->get_gift_card_templates();

                                if ($templateList['success'] != true) :
                                   
                                        echo "templates list not found."; 
                                        return;
                                endif;

                                foreach($templateList['result'] as $template)
                                        {
                                        if ($template['template_id'] == $options['wcloyality_field_template_id']) :
                                               
                                                $template_image_id =  $this->save_image($template['template_image'], $template['template_name']);
                                                
                                                break;
                                        endif;
                                        }
 
                                foreach ($options[$args['label_for']] as $selected_amount)
                                        {
                                        foreach($gift_card_amounts['result'] as $amount)
                                                {
                                                if ( $amount['giftcard_purchase_amount_id'] == $selected_amount)
                                                        {
                                                        $product = new WC_Product(); 

                                                        $product->set_sku(uniqid("loyality_"));
                                                        
                                                        $product->set_name($amount['amount'] . " Gift Card");
                                                        $product->set_regular_price($amount['amount']); 
                                                        $product->set_description($amount['amount'] . " Gift Card");
                                                        $product->set_short_description($amount['amount']. " Gift Card");
                                                        $product->set_image_id($template_image_id); 
                                                        $product->set_virtual(true);

                                                        $data = [];

                                                        $giftcard_purchase_amount_id = new WC_Product_Attribute();
                                                        $giftcard_purchase_amount_id->set_visible(false);        
                                                        $giftcard_purchase_amount_id->set_name('giftcard_purchase_amount_id');
                                                        $giftcard_purchase_amount_id->set_variation(false); 
                                                        $giftcard_purchase_amount_id->set_position(0); 
                                                        $giftcard_purchase_amount_id->set_options([$amount['giftcard_purchase_amount_id']]); 
                                                        

                                                        $data[] = $giftcard_purchase_amount_id;

                                                        $giftcard_template_id = new WC_Product_Attribute();
                                                        $giftcard_template_id->set_visible(false);        
                                                        $giftcard_template_id->set_name('giftcard_template_id');
                                                        $giftcard_template_id->set_variation(false); 
                                                        $giftcard_template_id->set_position(0); 
                                                        $giftcard_template_id->set_options([ $options['wcloyality_field_template_id'] ]); 
                                                        
                                                        $data[] = $giftcard_template_id; 

                                                        $product->set_attributes($data);

                                                        $result = $product->save();

                                                        echo "Product save result: " . $result ? "true" : "false";
                                                        }       
                                                }        
                                        }
                                }
                        
                        $options['wcloyality_field_gift_card_amounts'] = null;
                        update_option('wc_loyality_options', $options); 
                        }
                }

        /**
         * creates the select gift card amount section on the options screen.
         *
         * @param [type] $args
         * @return void
         */
        function section_gift_card_amount_callback($args)
                {
                ?>
                <p id="<php echo esc_attr($args['id']); ?>">
                        <?php esc_html_e( 'Select Gift Card Amounts for Gift product you want to add to your site. This will create products for sale on your site corresponding to the amounts you select.', 'wcloyality' ); ?>
                </p>
                <?php        
                }
        
        /**
         * Undocumented function
         *
         * @param [type] $args
         * @return void
         */
        function section_api_key_callback($args)
                {
                ?>
                <p id="<php echo esc_attr($args['id']); ?>">
                        <?php esc_html_e( 'Loyality API Key and Organization ID', 'wcloyality' ); ?>
                </p>
                <?php
                }

        /**
         * Generates the api key field. API key is provided inside the Loyality merchant 
         * Portal Settings screen.
         *
         * @param [type] $args
         * @return void
         */
        function field_api_key_callback($args)
                {
                $options = get_option('wc_loyality_options'); 
                ?>
                <input type="text" 
                       id="<?php echo esc_attr($args['label_for']); ?>" 
                       data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                       name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>]"
                       value="<?php echo $options[ $args['label_for'] ]; ?>">
                <?php
                }

        /**
         * Every organization in the Loyality platform has an organization id. 
         * It must be provided and it is located in the Loyality platform settings 
         * Screen. 
         * 
         * @param [type] $args
         * @return void
         */
        function field_organization_id_callback($args)
                {
                $options = get_option('wc_loyality_options');
                ?>
                <input type="text" 
                       id="<?php echo esc_attr($args['label_for']); ?>" 
                       data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                       name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>]"
                       value="<?php echo $options[ $args['label_for'] ]; ?>">
                <?php
                }

        /**
         * Creates the field for selecting a template for generating 
         * the gift card product. A template must be provided to 
         * gift cards. Templates are designed in the loyality merchant
         * portal.
         *
         * @param [type] $args
         * @return void
         */
        function field_template_id_callback($args)
                {
                $options = get_option('wc_loyality_options');

                $posapi = new LoyalityPosApi();
                // output save settings button
                $posapi->setApiKey(sanitize_text_field($options['wcloyality_field_api_key']));
                $posapi->setOrganizationId(sanitize_text_field($options['wcloyality_field_organization_id']));
        
                $templateList = $posapi->get_gift_card_templates();
                
                ?>
                <input type="hidden"
                        id="<?php echo esc_attr($args['label_for']); ?>"
                        data-custom="<?php echo esc_attr($args['wcloyality_custom_data']); ?>"
                        name="wc_loyality_options[<?php echo esc_attr($args['label_for']); ?>]"
                        value="<?php echo $options[ $args['label_for'] ]; ?>">

                <?php
                 if ($templateList == null) :
                       
                        echo "<p>No templates to display.</p>"; 
                        

                elseif ($templateList['success'] == false) :

                        echo "<p>" . $templateList['errors'] . "</p>"; 
                        
                else :
                ?>
                <div class="wrap">
                        <div id="template-select" class="slick">

                        <?php 
                                $index = 0;
                                // Not sure if there is a way to sanitize a base 64 image in wordpress. 
                                // Howver, the template images come off the Loyality server in Base64 encoded png 
                                // format. They don't come in any other way. Those base64 encoded images are 
                                // generated and sanitized on the loyality server. So they don't pose a threat.

                                foreach( $templateList['result'] as $template ) 
                                        {
                                        ?>
                                        <div id="template-index-<?php echo $index; ?>" data-template="<?php echo $template['template_id']; ?>">
                                                <div class="template-panel-height">
                                                        <h4 class='text-center'><?php echo esc_attr($template['template_name']); ?></h4>
                                                        
                                                        <img src="<?php echo $template['template_image']; ?>" height='300' alt='' />
                                                                
                                                </div>
                                        </div>  
                                        <?php 
                                        $index++;
                                        } 
                                        ?>
                        </div>
                </div>
                <?php
                endif; 
                }

      
        /**
         * Generate options page. 
         *
         * @return void
         */        
        function wcloyality_options_page_html() 
                {
                // check user capabilities
                if ( ! current_user_can( 'manage_options' ) ) 
                        {
                        echo "<p>Forbidden, you do not have permission to manage options.</p>";
                        return;
                        }

                        
                if ( isset( $_GET['settings-updated'] ) ) 
                        {
                        // add settings saved message with the class of "updated"
                        add_settings_error( 'wcloyality_messages', 'wcloyality_message', __( 'Settings Saved', 'wcloyality' ), 'updated' );
                        }

                settings_errors( 'wcloyality_messages' );


                ?>
                
                        <div class="wrap">
                                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

                                <form action="options.php" method="post">
                                        <?php
                                        // output security fields for the registered setting "wcloyality"
                                        settings_fields( 'wcloyality' );

                                        // output setting sections and their fields
                                        // (sections are registered for "wcloyality", each field is registered to a specific section)
                                        do_settings_sections( 'wcloyality' );
                                        
                                        submit_button( 'Save Settings' );
                                        ?>
                                </form>
                        </div>


                <?php
                }

        /**
         * generate gift card products. 
         *
         * @return void
         */
        function generateGiftcardProducts()
                {
                $posapi = new LoyalityPosApi();

                $options = get_option('wc_loyality_options'); 
                
                $posapi->setApiKey($options['wcloyality_field_api_key']);
                $posapi->setOrganizationId($options['wcloyality_field_organization_id']);

                $gift_card_load_amounts = $posapi->get_gift_card_load_amounts();
                }

        /**
         *  generate voucher products. 
         */

        function generateVoucherProducts()
                {
                $posapi = new LoyalityPosApi();

                $options = get_option('wc_loyality_options'); 
                
                $posapi->setApiKey(sanitize_text_field($options['wcloyality_field_api_key']));
                $posapi->setOrganizationId(sanitize_text_field($options['wcloyality_field_organization_id']));       
                
                $voucher_blocks = $posapi->get_voucher_block_list(); 
                }

        /**
         * Load javascript.
         *
         * @return void
         */
        function wcloyality_js_scripts()
                {
                wp_enqueue_script("jquery");
                wp_register_script('slick-min-js', plugins_url('../js/slick.min.js', __FILE__)); 
                wp_register_script('wcloyality-js', plugins_url('../js/wcloyality.js', __FILE__));
                wp_enqueue_script( 'slick-min-js', array('jquery'));
                wp_enqueue_script('wcloyality-js', array('jquery', 'slick-min-js'));
                }
             
        /**
         * Load CSS.
         */

        function wcloyality_css_style()
                {
                wp_register_style('slick-css', plugins_url('../css/slick.css', __FILE__)); 
                wp_register_style('slick-theme-css', plugins_url('../css/slick-theme.css', __FILE__));
                wp_register_style('wployality', plugins_url('../css/wployality.css', __FILE__)); 
                wp_enqueue_style( 'slick-css' ); 
                wp_enqueue_style('slick-theme-css');
                wp_enqueue_style('wployality');      
                }


       

        /**
         * save image.
         *
         * @param char[] $base64_img
         * @param string $title
         * @return void
         */
        function save_image($base64_img, $title)
                {
                echo "Entering save image.";

                try 
                        {
                        // Upload dir.
                        $upload_dir  = wp_upload_dir();  
                        
                        $upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

                        if (preg_match('/^data:image\/(\w+);base64,/', $base64_img, $type)) 
                                {
                                $data = substr($base64_img, strpos($base64_img, ',') + 1);
                                $type = strtolower($type[1]); // jpg, png, gif
                        
                                if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) 
                                        {
                                        throw new \Exception('invalid image type');
                                        }
                        
                                $decoded = base64_decode(urldecode($data));
                        
                                if ($decoded === false) 
                                        {
                                        throw new \Exception('base64_decode failed');
                                        }

                                $title = str_replace(' ','_', $title); 

                                $filename        = $title . '.' . $type;
                                $file_type = "image/$type";
                                $hashed_filename = md5($filename . microtime()) . '_' . $filename;

                                $upload_file = file_put_contents($upload_path . $hashed_filename, $decoded); 

                                $file = [
                                        'error' => '',
                                        'tmp_name' => $upload_path . $hashed_filename,
                                        'name' => $hashed_filename,
                                        'type' => $file_type,
                                        'size' => filesize( $upload_path . $hashed_filename )
                                        ]; 

                                $file_return =  wp_handle_sideload( $file, array( 'test_form' => false ) );

                                $filename = $file_return['file'];

                                $attachment = array(
                                        'post_mime_type' => $file_return['type'],
                                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename  ) ),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit',
                                        'guid'           => $upload_dir['url'] . '/' . basename( $filename )
                                        );
                                
                                $attach_id = wp_insert_attachment( $attachment, $filename );

                                $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );

                                wp_update_attachment_metadata($attach_id, $attach_data); 

                                return $attach_id;
                                } 
                        else 
                                {
                                throw new \Exception('did not match data URI with image data');
                                }
                        }
                catch(\Exception $ex)
                        {
                        echo "Exception thrown" . $ex->getMessage();

                        return false; 
                        }
                }

        /**
         * Generate checkout fields.
         *
         * @param [type] $checkout
         * @return void
         */
        function generate_recipient_checkout_fields( $checkout )
                {
                ?>
                <div id="wcloyality_custom_checkout_field">
                        <h2><?php echo __('Gifting Email and Phone Field'); ?></h2>
                
                <?php 
                        woocommerce_form_field('wcloyality_recipient_name', 
                                                array(
                                                        'type' => 'text',
                                                        'class' => array('wcloyality_class, form-row-wide'),
                                                        'label' => __('Recipient Name'),
                                                        'placeholder' => __('Please Enter recipient name')
                                                ), 
                                                $checkout->get_value('wcloyality_recipient_name')); 

                        woocommerce_form_field('wcloyality_recipient_email_name', 
                                                array(
                                                        'type'  => 'text',
                                                        'class' => array('wcloyality_class, form-row-wide'),
                                                        'label' => __('Recipient Email'),
                                                        'placeholder' => __('Please enter email recipient.')
                                                     ), 
                                                $checkout->get_value('wcloyality_recipient_email_name'));

                        woocommerce_form_field('wcloyality_recipient_phone_number', 
                                                array(
                                                        'type'  => 'text',
                                                        'class' => array('wcloyality_class, form-row-wide'),
                                                        'label' => __('Recipient Phone'),
                                                        'placeholder' => __('Please enter phone recipient.')   
                                                     ), 
                                                $checkout->get_value('wcloyality_recipient_phone_number'));

                        woocommerce_form_field('wcloyality_recipient_message',
                                                array(
                                                        'type'  => 'text',
                                                        'class' => array('wcloyality_class, form-row-wide'),
                                                        'label' => __('Message to Recipient.'),
                                                        'placeholder' => __('Please enter message to recipient.')      
                                                     ), 
                                                $checkout->get_value('wcloyality_recipient_message'));
                
                ?>
                </div>
                <?php
                }

        /**
         * initiate purchase callback.
         *
         * @param [type] $order_id
         * @param [type] $posted_data
         * @param [type] $order
         * @return void
         */
        function initiate_purchase_callback($order_id, $posted_data, $order)
                {
                global $wpdb;
                global $post;

                error_log("initiate_purchase_callback");

                $options = get_option('wc_loyality_options');     
                
                $table_name = $wpdb->prefix . 'wcloyality_pending_gift_card_activations';

                $voucher_table_name =  $wpdb->prefix . "wcloyality_pending_voucher_activations";
                
                $posapi = new LoyalityPosApi();
                

                $posapi->setApiKey($options['wcloyality_field_api_key']);
                $posapi->setOrganizationId($options['wcloyality_field_organization_id']);

                if (! $order_id)
                        {
                        throw new Exception( __( "Invalid Order Id"));
                        }

                //$order = new WC_Order( $order_id );
                $items = $order->get_items();

                foreach($items as $item)
                        {
                        $product = $item->get_product(); 
                        
                        $metadata = $product->get_attributes();

                        $productData = []; 

                        foreach ($metadata as $attribute_option)
                                {
                                $option = $attribute_option->get_options();

                                if ($attribute_option->get_name() == 'giftcard_purchase_amount_id') 
                                        {
                                        $productData[ 'giftcard_purchase_amount_id'] = $option[0]; 
                                        }

                                else if ($attribute_option->get_name() == 'giftcard_template_id')
                                        {
                                        $productData[ 'template_id'] = $option[0];        
                                        }

                                else if ($attribute_option->get_name() == 'voucher_block_id')
                                        {
                                        $productData[ 'voucher_block_id'] = $option[0];         
                                        }
                                }

                        if (array_key_exists('giftcard_purchase_amount_id', $productData))
                                {
                                error_log("found gift card purchase amount."); 

                                $table_name = $wpdb->prefix . 'wcloyality_pending_gift_card_activations';

                                $purchase_amount_option = $productData['giftcard_purchase_amount_id'];
                               
                                $template_id = $productData['template_id'];

                                for ($index = 0; $index < $item->get_quantity(); $index++)
                                        {
                                        error_log("Logging get first inactive gift card.");

                                        $result = $posapi->get_first_inactive_card();

                                        if ($result['success'] == false)
                                                {
                                                error_log($result['errors']);

                                                $sql = "SELECT * FROM $table_name WHERE orderid=%d";
                                        
                                                $this->inactive_gift_cards = $wpdb->get_results($wpdb->prepare($sql, [$order_id])); 

                                                foreach($this->inactive_gift_cards as $card)
                                                        {
                                                        $unlock_result = $posapi->unlock_gift_card($card['giftcardid']); 

                                                        if ($unlock_result['success'] != true)
                                                                {
                                                                error_log($unlock_result['errors']);       
                                                                }
                                                        }

                                                $sql = "DELETE FROM " . $table_name . " WHERE orderid=%d;";  

                                                $wpdb->query($wpdb->prepare($sql, [$order_id]));

                                                // Add redirect back here to stop credit card processing.
                                                throw new Exception( __( $result['errors'] ) );
                                                }

                                        $get_card_result = $result['result'];

                                        $email = sanitize_email($_POST['wcloyality_recipient_email_name']);
                                        $phone = wc_sanitize_phone_number($_POST['wcloyality_recipient_phone_number']);

                                        if (empty($email) && empty($phone))
                                                {
                                                $email = sanitize_email($_POST['billing_email']); 
                                                }

                                        $card = [
                                                'orderid' => $order_id,
                                                'giftcardid' => $get_card_result['gift_card_id'], 
                                                'templateid' => $template_id,
                                                'amount' => $product->get_price(),  
                                                'recipient_name' => sanitize_text_field($_POST['wcloyality_recipient_name']),
                                                'email' => $email,
                                                'phone' => $phone,
                                                'greetingMessage' =>  sanitize_text_field($_POST['wcloyality_recipient_message']),
                                                'pending' => true
                                                ];

                                        $result = $wpdb->insert($table_name, $card);

                                        if (!$result)
                                                {
                                                throw new Exception($wpdb->last_error);         
                                                }
                                        }
                                }
                        
                        elseif (array_key_exists('voucher_block_id', $productData))
                                {
                                $voucher_block_id = $productData['voucher_block_id']; 

                               // $options = $voucher_block_id->get_options(); 

                                $result = $posapi->get_unsold_voucher_count($voucher_block_id);      

                                if ($result['success'] == false)
                                        {
                                        error_log($result['errors']); 

                                        $sql = "SELECT * FROM $table_name WHERE order_id=%d";
                                        
                                        $this->inactive_gift_cards = $wpdb->get_results($wpdb->prepare($sql, [$order_id])); 

                                        foreach($this->inactive_gift_cards as $card)
                                                {
                                                $posapi->unlock_gift_card($card['giftcardid']); 
                                                }  

                                        $sql = "DELETE FROM " . $table_name . " WHERE orderid=%d;";  

                                        $wpdb->query($wpdb->prepare($sql, [$order_id]));

                                        // Add redirect back here to stop credit card processing.

                                        throw new Exception( __( "Error getting available voucher count" ) );
                                        }

                                elseif ( $result['result'] < $item->get_quantity() )
                                        {
                                        $sql = "SELECT * FROM $table_name WHERE orderid=%d";

                                        $this->inactive_gift_cards = $wpdb->get_results($wpdb->prepare($sql, [$order_id])); 

                                        foreach($this->inactive_gift_cards as $card)
                                                {
                                                $posapi->unlock_gift_card($card['giftcardid']); 
                                                }  

                                        $sql = "DELETE FROM " . $table_name . " WHERE orderid=%d;";  

                                        $wpdb->query($wpdb->prepare($sql, [$order_id]));

                                        // Add redirect back here to stop credit card processing.
                                        
                                        throw new Exception( __( "Not enough vouchers to fill order") );
                                        }

                                $email = sanitize_email($_POST['wcloyality_recipient_email_name']);
                                $phone = wc_sanitize_phone_number($_POST['wcloyality_recipient_phone_number']);
                                
                                if (empty($email))
                                        {
                                        $email = sanitize_email($_POST['billing_email']); 
                                        }

                                $voucher = [
                                        'orderid' => $order_id, 
                                        'voucherblockid' => $voucher_block_id, 
                                        'recipient_name' =>  sanitize_text_field($_POST['wcloyality_recipient_email_name']),
                                        'phone' =>  $phone,
                                        'email' => $email,
                                        'greetingMessage' =>  sanitize_text_field($_POST['wcloyality_recipient_message']),
                                        'qty' => $item->get_quantity(),
                                        'pending' => true
                                        ];

                                $result = $wpdb->insert($voucher_table_name, $voucher); 

                                if (!$result)
                                        {
                                        throw new Exception(__($wpdb->last_error));         
                                        }
                                }
                        }
                }        

        /**
         * payment failed handler 
         *
         * @param [type] $order_id
         * @param [type] $old_status
         * @param [type] $new_status
         * @return void
         */
        function payment_failed($order_id, $old_status, $new_status)
                {
                global $wpdb; 

                $table_name = $wpdb->prefix . 'wcloyality_pending_gift_card_activations';

                if ($new_status != 'failed' || $new_status != 'canceled')
                        return; 

                $posapi = new LoyalityPosApi();

                $options = get_option('wc_loyality_options');  

                $posapi->setApiKey($options['wcloyality_field_api_key']);
                $posapi->setOrganizationId($options['wcloyality_field_organization_id']);


                $sql = "SELECT * FROM $table_name WHERE orderid=%d";

                $this->inactive_gift_cards = $wpdb->get_results($wpdb->prepare($sql, [$order_id])); 

                if ($wpdb->lasterror)
                        {
                        return new Exception($wpdb->lasterror);        
                        }
        
                foreach($this->inactive_gift_cards as $card)
                        {
                        $posapi->unlock_gift_card($card); 
                        }
                }

        /**
         * Payment successful.
         *
         * @param [type] $order_id
         * @param [type] $old_status
         * @param [type] $new_status
         * @return void
         */
        function payment_successful_result($order_id, $old_status, $new_status)
                {
                global $wpdb;   

                $table_name = $wpdb->prefix . 'wcloyality_pending_gift_card_activations';
                
                if( strtolower($new_status) != "completed" )
                        {
                        $logger = wc_get_logger();

                        $logger->info($new_status, "payment_succesful_result"); 

                        return;
                        }

                if (! $order_id)
                        {
                        throw new Exception(__("No order id")); 
                        }

                $order = new WC_Order( $order_id );

                $items = $order->get_items();
                
                $posapi = new LoyalityPosApi();
                $options = get_option('wc_loyality_options'); 

                $posapi->setApiKey($options['wcloyality_field_api_key']);
                $posapi->setOrganizationId($options['wcloyality_field_organization_id']);

                $email = sanitize_email($_POST['wcloyality_recipient_email_name']);

                $phone = wc_sanitize_phone_number($_POST['wcloyality_recipient_phone_number']);

                $message = sanitize_text_field($_POST['wcloyality_recipient_message']);

                $sql = "SELECT * FROM $table_name WHERE orderid=%d";

                $this->inactive_gift_cards = $wpdb->get_results($wpdb->prepare($sql, [$order_id]), ARRAY_A); 

                if ($wpdb->lasterror)
                        {
                        throw new Exception($wpdb->lasterror);
                        }

                foreach($this->inactive_gift_cards as $card)
                        {

                        error_log("Calling activate gift card", 3); 

                        $result = $posapi->issue_gift_card([  'gift_card_id' => $card['giftcardId'], 
                                                              'template_id' => $card['templateid'], 
                                                              'recipient_name' => $card['recipient_name'],
                                                              'email' => $card['email'],
                                                              'phone' => $card['phone'], 
                                                              'gift_card_amount' => $card['amount'],
                                                              'message' => $card['greetingMessage'] 
                                                              ]);  
                                                              
                        if ($result == false)
                                {
                                $logger = wc_get_logger();

                                $logger->info(print_r($card, true), "issue_gift_card_result");

                                throw new Exception($posapi->errors); 
                                }

                        }

                $voucher_table_name =  $wpdb->prefix . "wcloyality_pending_voucher_activations";

                $sql = "SELECT * FROM $voucher_table_name WHERE orderid=%d"; 

                $voucher_results = $wpdb->get_results($wpdb->prepare($sql, [$order_id]), ARRAY_A); 

                if ($wpdb->lasterror)
                        {
                        throw new Exception($wpdb->lasterror);
                        }
        
                foreach ($voucher_results as $voucher_item)
                        {
                        $result = $posapi->issue_voucher($voucher_item['voucherblockid'], 
                                                         $voucher_item['email'], 
                                                         $voucher_item['phone'], 
                                                         $voucher_item['recipient_name'],
                                                         $voucher_item['greetingMessage'],
                                                         $voucher_item['qty']);

                        if ($result['success'] == false)
                                {
                                throw new Exception($result['errors']);        
                                }        
                        }

                return;
                }

        /**
         * section template callback.
         *
         * @return void
         */
        function section_template_callback()
                {
                echo "<p>Select template to use create gift card product in woocommerce.</p>"; 
                }
        
        }