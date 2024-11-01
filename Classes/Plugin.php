<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_Variation_Message {

	private static $instance;
	public $file;

	/**
	 * Creates an instance if one isn't already available,
	 * then return the current instance.
	 * @param  string $file The file from which the class is being instantiated.
	 * @return object       The class instance.
	 */
	public static function get_instance( $file ) {
	    if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Plugin ) ) {
	        self::$instance = new WC_Variation_Message;
	        self::$instance->init();

	        self::$instance->file = $file;
	    }
	    return self::$instance;
	}
	
	/**
	 * clone
	 *
	 * Kopieren der Instanz von aussen ebenfalls verbieten
	 */
	protected function __clone() {}

	/**
	 * constructor
	 *
	 * externe Instanzierung verbieten
	 */
	protected function __construct() {}

	/**
	* Runs init actions (load textdomain, checks dependecies)
	*/
	private function init() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'check_dependencies' ) );
	}

	/**
	* Load translation files from the indicated directory.
	*/
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woo-variation-message', false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}
	
	/**
	* Load script files.
	*/
	public function load_scripts() {

		if ( is_singular( 'product' ) ) {

			global $post;
			$product = new WC_Product_Variable( $post->ID );

			wp_register_script( 'woo-variation-message', plugin_dir_url( $this->file ) . 'assets/js/woo-variation-message-public.js', array('jquery'), false, true );
			wp_localize_script( 'woo-variation-message', 'woocommerce_variation_message', $this->get_json_settings($product));
			wp_enqueue_script( 'woo-variation-message' );
		}
	}
	
	/**
	* Checks for dependecies, displays error if missing else continue
	*/
	public function check_dependencies() {
		if ( class_exists( 'WooCommerce' ) ) { //Add more dependecies
			$this->run();
		} else {
			add_action( 'admin_notices', array( $this, 'message_missing_dependencies' ) );
		}
	}
	
	/**
	* 
	*/
	public function message_missing_dependencies() {
		echo '<div class="error"><p>' . sprintf(
			__('“%1$s” requires “%2$s”. Please install.', 'woo-variation-message'),
			'WC Variation Message',
			'WooCommerce' //Add more dependecies
		) . '</p></div>';
	}
	
	
	
	
	/**
	* Run Plugin (Add Actions, filters etc.)
	*/
	public function run() {
		add_action( 'woocommerce_variation_options', array($this, 'show_variation_options'), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array($this, 'save_variation_options'), 10, 2 );
		add_action( 'woocommerce_general_settings', array($this, 'register_settings') );
		add_action( 'woocommerce_single_variation', array($this, 'render_variation_options'), 10 );
		add_action( 'woocommerce_admin_field_variation_messages', array($this, 'field_variation_messages') );
		
		add_action( 'wp_enqueue_scripts', array($this, 'load_scripts') );
	}
	
	/**
	* Placeholder to get all defined messages.
	*/
	public function get_all_messages() {
		$json_messages = get_option( 'woocommerce_variation_messages' );
		$messages = json_decode($json_messages);
		return $messages;
	}
	
	/**
	*
	*/
	public function get_json_settings($product) {
		
		$variation_options = array();
		foreach ($this->get_all_messages() as $id => $message) {
			$variation_options[ $id ] = array();
			foreach( $product->get_available_variations() as $variation ) {
				$variation_options[ $id ][ $variation['variation_id'] ] = (Boolean) get_post_meta( $variation['variation_id'], '_woocommerce_variation_message[' . $id . ']' );
			}
			$variation_options[ $id ] = json_encode($variation_options[ $id ]);
		}
		return $variation_options;
	}
	
	/**
	*
	*/
	public function show_variation_options( $loop, $variation_data, $variation ) {
		?>
			<div class="form-row form-row-full options">
				<p><strong><?php _e('Variation Messages', 'woo-variation-message'); ?></strong> <a href="<?php echo admin_url("admin.php?page=wc-settings"); ?>"><?php _e('edit', 'woo-variation-message'); ?></a></p>
		<?php
		foreach ($this->get_all_messages() as $id => $message):
			?>
				<label>
					<input type="checkbox" class="checkbox" name="_woocommerce_variation_message[<?php echo $id; ?>][<?php echo $variation->ID; ?>]" <?php checked( get_post_meta( $variation->ID, '_woocommerce_variation_message[' . $id . ']', true ), "1" ); ?> />
					<?php echo $message->description; ?>
				</label>
			<?php
		endforeach;
		?>
	</div>
		<?php
	}
	
	public function save_variation_options( $post_id ) {
		foreach ($this->get_all_messages() as $id => $message) {
			if ( ! empty( $_POST['_woocommerce_variation_message'][$id][$post_id] ) ) {
				update_post_meta( $post_id, '_woocommerce_variation_message[' . $id . ']', '1' );
			} else {
				delete_post_meta( $post_id, '_woocommerce_variation_message[' . $id . ']' );
			}
		}
	}
	
	public function register_settings( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'general_options' == $section['id'] && isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
				$updated_settings[] = array(
					'name'     		=> __('Variation Messages', 'woo-variation-message'),
					'id'       		=> 'woocommerce_variation_messages',
					'type'     		=> 'variation_messages',
					'placeholder'	=> __('Enter description for new message', 'woo-variation-message'),
					'css'      		=> 'min-width:300px;',
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	public function render_variation_options() {
		?>
			<div class="woocommerce_variation_messages">
		<?php
		foreach ($this->get_all_messages() as $id => $message) {
			?>
				<p class="woocommerce_variation_message_<?php echo $id; ?>" style="display: none;"><?php echo $message->message; ?></p>
			<?php
		}
		?>
			</div>
		<?php
	}
	
	public function field_variation_messages( $value ) {
		// Load all messages and decode them
		$json_messages = get_option( $value['id'] );
		$messages = json_decode($json_messages);
		if ($messages === null) {
			$messages = [];	
		}
		?>
		<tr valign="top" class="<?php echo $value['id']; ?>_field">
			<script>
				jQuery(document).ready(function() {
					function randomID() {
					  var text = "";
					  var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

					  for (var i = 0; i < 5; i++)
					    text += possible.charAt(Math.floor(Math.random() * possible.length));

					  return text;
					}
					// Function to create new Input field
					function create_text_input(id, description) {
						return `
						<tr valign="top" class="<?php echo $value['id']; ?>_item  <?php echo $value['id']; ?>_field">
							<th scope="row" class="titledesc sub">
								<input type="hidden" class="id_input" value="` + id +  `">
								<input type="text" class="description_input" value="` + description + `">
							</th>
							<td class="forminp forminp-text">
								<input type="text" class="message_input">
								<a class="remove_button">-</a>
							</td>
						</tr>
						`;
					}
					function update_json() {
						// Empty array and push id and name of every input field to array
						<?php echo $value['id']; ?>_save = {};
						jQuery(selector).parents("tbody").find(item).each(function(i) {
							<?php echo $value['id']; ?>_save[jQuery(this).find('.id_input').val()] = {'description': jQuery(this).find('.description_input').val(), 'message': jQuery(this).find('.message_input').val()};
						});
						// Set value of field to JSON-String of array
						jQuery("#<?php echo $value['id']; ?>").val(JSON.stringify(<?php echo $value['id']; ?>_save));
					}
					// Set jQuery selectors for later use
					selector = ".<?php echo $value['id']; ?>_selector";
					item = ".<?php echo $value['id']; ?>_item";
					// Add and remove New Fields
					jQuery(selector).on( 'keypress', function( e ){
						// Detect Enter
						var charCode = ( e.which ) ? e.which : e.keyCode;
						if( charCode == 13 ){
							// Dont submit form
							e.preventDefault();
							// Create input field
							jQuery(selector).parents("tbody").append(create_text_input(randomID(), jQuery(selector).val() ));
							update_json();
							// Empty Add new message field
							jQuery(selector).val("");
							return false;
						}
					});
					// Detect Click on remove button
					jQuery('body').on("click", item + " .remove_button", function() {
						// Remove input field and update JSON
						jQuery(this).parents(item).remove();
						update_json();
					});
					// Detect Click on remove button
					jQuery('body').on("change", item + " input", function() {
						// Update JSON
						update_json();

					});
				});
			</script>
			<style>
				tr.<?php echo $value['id']; ?>_field {
					padding-left: 10px;
					border-right: 1px dashed #666666;
					border-left: 1px dashed #666666;
					border-top: 1px dashed #666666;
				}
				tr.<?php echo $value['id']; ?>_field:last-of-type {
					border-bottom: 1px dashed #666666;
				}
				tr.<?php echo $value['id']; ?>_field.<?php echo $value['id']; ?>_item {
					border-top: none;
				}
				tr.<?php echo $value['id']; ?>_item > td, tr.<?php echo $value['id']; ?>_item > th {
					padding-top: 0;
					padding-bottom: 0;
				}
				tr.<?php echo $value['id']; ?>_field th, tr.<?php echo $value['id']; ?>_field td {
					padding-left: 20px;
					padding-right: 20px;
				}
				tr.<?php echo $value['id']; ?>_field:last-of-type td {
					padding-bottom: 10px;
				}
				tr.<?php echo $value['id']; ?>_field input.woocommerce_variation_messages_selector {
					min-width: 300px;
					display: inline-block;
				}
				tr.<?php echo $value['id']; ?>_field.<?php echo $value['id']; ?>_item input {
					min-width: 200px;
					max-width: 400px;
					display: inline-block;
				}
				tr.<?php echo $value['id']; ?>_item .remove_button {
					display: inline-block;
					cursor:pointer;
					width:14px;
					height:14px;
					background-color:red;
					border-radius:50%;
					color:white;
					font-size:30px;
					line-height:7px;
					text-align:center;
					margin-top:8px;
				}
				th.titledesc.sub {
					font-weight: normal;
					font-style: italic;
				}
				.mobile_label {
					display: none;
				}
				@media (max-width: 782px) {
					.desktop_labels {
						display: none;
					}
					.mobile_label {
						display: block;
					}
				}

			</style>
			<th scope="row" class="titledesc">
				<label for="<?php echo $value['id']; ?>"><?php echo $value['name']; ?></label>
			</th>
			<td class="forminp">
				<!-- This field has json-string of all active messages in it / gets saved -->
				<input name="<?php echo $value['id']; ?>" id="<?php echo $value['id']; ?>" type="hidden" value='<?php echo $json_messages; ?>'>
				<!-- This field is just used to add new message-fields, doesnt get saved -->
				<input type="text" class="<?php echo $value['id']; ?>_selector" placeholder="<?php echo $value['placeholder']; ?>">
			</td>
		</tr>
		<tr valign="top" class="<?php echo $value['id']; ?>_field desktop_labels">
			<th scope="row" class="titledesc">
				<label>Description</label>
			</th>
			<th scope="row" class="titledesc">
				<label>Message</label>
			</th>
		</tr>
			<?php
			// Loop through all messages on pageload, render them
			foreach ($messages as $id => $message) {
				?>
					<tr valign="top" class="<?php echo $value['id']; ?>_item <?php echo $value['id']; ?>_field">
						<td class="">
							<label class="mobile_label">Description</label>
							<input type="hidden" class="id_input" value="<?php echo $id; ?>">
							<input type="text" class="description_input" value="<?php echo $message->description; ?>">
						</th>
						<td>
							<label class="mobile_label">Message</label>
							<input type="text" class="message_input" value="<?php echo $message->message; ?>">
							<div class="remove_button">-</div>
						</td>
					</tr>
				<?php
			}
			?>
		<?php
	}
		
	
}