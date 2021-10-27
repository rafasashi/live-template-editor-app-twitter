<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_App_Twitter_Settings {

	/**
	 * The single instance of LTPLE_App_Twitter_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		
		$this->parent = $parent;
		
		$this->plugin 		 	= new stdClass();
		$this->plugin->slug  	= 'live-template-editor-app-twitter';
		
		add_action('ltple_settings_fields', array($this, 'settings_fields' ) );
		
		add_action( 'ltple_admin_menu' , array( $this, 'add_menu_items' ) );	
	}
	
	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	public function settings_fields ($settings) {

		$settings['twitter'] = array(
			'title'					=> __( 'Twitter', $this->plugin->slug ),
			'description'			=> __( 'Twitter API settings', $this->plugin->slug ),
			'fields'				=> array(
				array(
					'id' 			=> 'twt_main_account',
					'label'			=> __( 'Main account' , $this->plugin->slug ),
					'description'	=> 'Main connected Twitter account',
					'type'			=> 'dropdown_main_apps',
					'app'			=> 'twitter',
				),
				array(
					'id' 			=> 'twt_auto_retweet',
					'label'			=> __( 'Auto Retweet' , $this->plugin->slug ),
					'description'	=> '',
					'type'			=> 'action_schedule',
					'action' 		=> 'retweet',
					'unit' 			=> 'tweets',
					'appId' 		=> ( isset($_POST[$this->parent->_base .'twt_main_account']) ? intval($_POST[$this->parent->_base .'twt_main_account']) : intval(get_option( $this->parent->_base .'twt_main_account' )) ),
					'last' 			=> true,
				),
				array(
					'id' 			=> 'twt_import_leads',
					'label'			=> __( 'Import Leads' , $this->plugin->slug ),
					'description'	=> '',
					'type'			=> 'action_schedule',
					'action' 		=> 'import leads',
					'last' 			=> false,
				),
				array(
					'id' 			=> 'twt_welcome_tweet',
					'label'			=> __( 'Welcome Tweet' , $this->plugin->slug ),
					'description'	=> 'Message to be tweeted when a new Twitter account is connected',
					'type'			=> 'textarea',
					'placeholder'	=> __( 'Welcome tweet (140 char)', $this->plugin->slug ),
					'style'			=> 'height:60px;',
				),
				array(
					'id' 			=> 'twt_welcome_dm',
					'label'			=> __( 'Welcome DM' , $this->plugin->slug ),
					'description'	=> 'Direct Message to be sent when a new Twitter account is connected',
					'type'			=> 'textarea',
					'placeholder'	=> __( 'Welcome DM', $this->plugin->slug ),
					'style'			=> 'height:260px;',
				),
				array(
					'id' 			=> 'twt_thanks_followback_dm',
					'label'			=> __( 'Thanks Followback DM' , $this->plugin->slug ),
					'description'	=> 'Direct Message to be sent when a Twitter account followback',
					'type'			=> 'textarea',
					'placeholder'	=> __( 'Thanks Follow DM', $this->plugin->slug ),
					'style'			=> 'height:260px;',
				),
				array(
					'id' 			=> 'twt_unlock_tweet',
					'label'			=> __( 'Unlock Free Tweet' , $this->plugin->slug ),
					'description'	=> 'Message to be tweeted to unlock the Demo output',
					'type'			=> 'textarea',
					'placeholder'	=> __( 'Unlock Free tweet (140 char)', $this->plugin->slug ),
					'style'			=> 'height:60px;',
				),				
			)
		);

		$settings['addons']['app-twitter-plugin'] = array(
			
			'title' 		=> 'App Twitter Plugin',
			'addon_link' 	=> 'https://github.com/rafasashi/live-template-editor-app-twitter',
			'addon_name' 	=> 'live-template-editor-app-twitter',
			'source_url' 	=> 'https://github.com/rafasashi/live-template-editor-app-twitter/archive/master.zip',
			'description'	=> 'Twitter API integrator for Live Template Editor',
			'author' 		=> 'Rafasashi',
			'author_link' 	=> 'https://profiles.wordpress.org/rafasashi/',
		);
				
		
		return $settings;
	}
	
	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_items () {
		
		//add menu in wordpress dashboard
		/*
		add_submenu_page(
			'live-template-editor-client',
			__( 'App Twitter', $this->plugin->slug ),
			__( 'App Twitter', $this->plugin->slug ),
			'edit_pages',
			'edit.php?post_type=post'
		);
		*/
	}
}
