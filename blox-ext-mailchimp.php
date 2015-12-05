<?php
/*
Plugin Name: Blox - MailChimp
Plugin URI: http://www.marcusbattle.com/
Version: 0.1.0
Author: Marcus Battle
Description: Allows a user to subscribe to a mailing list in mailchimp
*/

class Blox_Ext_MailChimp {

	protected static $single_instance = null;

	static function init() { 

		if ( self::$single_instance === null ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;

	}

	public function __construct() { }

	public function hooks() {

		add_action( 'cmb2_admin_init', array( $this, 'init_cmb2_mailchimp_metabox' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_and_scripts' ) );

		add_action( 'wp_ajax_do_subscribe', array( $this, 'do_subscribe' ) );
		add_action( 'wp_ajax_nopriv_do_subscribe', array( $this, 'do_subscribe' ) );

		
		

	}

	public function load_styles_and_scripts() {

		wp_enqueue_script( 'blox-mailchimp', plugin_dir_url( __FILE__ ) . 'assets/js/blox-mailchimp.js', array('jquery'), '1.0.0', true );
		
		wp_localize_script( 'blox-mailchimp', 'blox_mailchimp',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) 
        ) );

	}
	
	public function init_cmb2_mailchimp_metabox() {

		$prefix = '_block_mailchimp_';

		$stripe_settings_metabox = new_cmb2_box( array(
	        'id'           	=> $prefix . 'settings_metabox',
	        'title'        	=> 'Mailchimp Settings',
	        'object_types' 	=> array( 'block' ),
	        'context'    	=> 'side',
	        'priority' 		=> 'low'
	    ) );

		$stripe_settings_metabox->add_field( array(
			'name' => 'Datacenter',
	        'id'   => $prefix . 'datacenter',
	        'type' => 'text_small',
		) );

		$stripe_settings_metabox->add_field( array(
			'name' => 'API Key',
	        'id'   => $prefix . 'api_key',
	        'type' => 'text',
		) );

		$stripe_settings_metabox->add_field( array(
			'name' => 'List ID',
	        'id'   => $prefix . 'list_id',
	        'type' => 'text',
		) );

	}

	public function do_subscribe() {

		$json_POST = file_get_contents('php://input');

		if ( $json_POST ) {
			$_POST = json_decode( $json_POST, true ); 	
		}
		
		$block_id = $_POST['block_id'];
		$api_key = get_post_meta( $block_id, '_block_mailchimp_api_key', true );
		$mailchimp_list = get_post_meta( $block_id, '_block_mailchimp_list_id', true );
		$mailchimp_datacenter = get_post_meta( $block_id, '_block_mailchimp_datacenter', true );

		$api_endpoint = "https://{$mailchimp_datacenter}.api.mailchimp.com/3.0/lists/{$mailchimp_list}/members/";
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key )
			),
			'body' => json_encode( array(
				'email_address'		=> $_POST['email'],
				'status'			=> 'subscribed'
			) )
			
		);

		$response = wp_remote_post( $api_endpoint, $args );

		$data = json_decode( $response['body'], true );
		
		if ( isset( $data['status'] ) && ( $data['status'] == 'subscribed' ) ) {
			wp_send_json_success( $data );
		}

		wp_send_json_error( $data );

	}

}

add_action( 'plugins_loaded', array( Blox_Ext_MailChimp::init(), 'hooks' ) );