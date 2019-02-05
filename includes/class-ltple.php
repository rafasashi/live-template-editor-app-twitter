<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_App_Twitter {

	/**
	 * The single instance of LTPLE_App_Twitter.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	 
	var $slug = 'twitter';
	 
	public function __construct ( $file='', $parent, $version = '1.0.0' ) {

		$this->parent = $parent;
	
		$this->_version = $version;
		$this->_token	= md5($file);
		
		$this->message = '';
		
		// Load plugin environment variables
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->vendor  		= trailingslashit( $this->dir ) . 'vendor';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		
		//$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = '';

		register_activation_hook( $this->file, array( $this, 'install' ) );
		
		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		
		$this->settings = new LTPLE_App_Twitter_Settings( $this->parent );
		
		$this->admin = new LTPLE_App_Twitter_Admin_API( $this );

		if ( !is_admin() ) {

			// Load API for generic admin functions
			
			add_action( 'wp_head', array( $this, 'get_header') );
			add_action( 'wp_footer', array( $this, 'get_footer') );
			
			add_action( 'ltple_alternative_login', array( $this, 'get_login_button' ));
		}
		
		// Handle localisation
		
		$this->load_plugin_textdomain();
		
		// get triggers
		
		$this->get_triggers();
		
		// load_localisation
		
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
		
		//init profiler 
		
		add_action( 'init', array( $this, 'init_app' ));

		add_action( 'admin_init', array( $this, 'admin_init_app' ));		

		// Custom editor template
		
		add_filter( 'template_include', array( $this, 'app_template'), 1 );

		// list apps
		
		add_action( 'ltple_list_apps', array( $this, 'register_app_type' ), 10 );
		
	} // End __construct ()
	
	public function register_app_type(){

		$apps = $this->parent->apps->get_terms( 'app-type', array(

			'twitter' => array(
			
				'name' 		=> 'Twitter',
				'options' 	=> array(
				
					'thumbnail' => $this->parent->assets_url . 'twitter.jpg',
					'types' 	=> array('networks','images'),
					'api_client'=> 'twitter',
					'parameters'=> array (
					
						'input' => array ( 'password', 'password' ),
						'key' 	=> array ( 'twt_consumer_key', 'twt_consumer_secret' ),
						'value' => array ( '', ''),
					),
				),
			),
			
		),'DESC');
		
		// merge app
		
		foreach( $apps as $app){
			
			if( !in_array_field( $app->term_id, 'term_id', $this->parent->apps->list ) ){
				
				$this->parent->apps->list[] = $app;
			}			
		}
	}
	
	public function app_template( $template_path ){
		
		
		return $template_path;
	}
	
	public function get_triggers(){
		
		$this->parent->stars->triggers['connected apps']['ltple_twitter_account_connected'] = array(
				
			'description' => 'when you connect a new Twitter account'
		);
		
		return true;
	}
	
	public function init_app(){	
		
		include( $this->vendor . '/autoload.php' );
		
		add_action( 'ltple_twt_auto_retweet', array( $this, 'ltple_twt_auto_retweet_event'),1,2);
		//add_action( 'ltple_twt_auto_follow', array( $this, 'ltple_twt_auto_follow_event'),1,2);
		//add_action( 'ltple_twt_auto_unfollow', array( $this, 'ltple_twt_auto_unfollow_event'),1,2);
		//add_action( 'ltple_twt_import_leads', array( $this, 'ltple_twt_import_leads_event'),1,2);
	}
	
	public function admin_init_app(){	
	
		// debug cron action from plugin settings
		
		if(isset($_GET['debug'])){
		
			$this->ltple_twt_auto_retweet_event(15167, 10);
			
			//$this->ltple_twt_import_leads_event();
		
			//$this->ltple_twt_auto_follow_event(15167,5);
			
			//$this->ltple_twt_auto_unfollow_event(15167,5);
		}		
	}
	
	public function get_header(){
		
	}
	
	public function get_footer(){
		
		
	}	
	
	public function get_login_button(){
	
		$redirect_to = '';
		
		if(!empty($_REQUEST['redirect_to'])){
			
			$redirect_to = '&ref='.str_replace(array('http://','https://'),'',$_REQUEST['redirect_to']);
		}

		echo'<a href="' . $this->parent->urls->apps . '?app=twitter&action=login' . $redirect_to . '" style="border-radius:5px;width:100%;display: block;text-align: center;margin-top: 10px;" class="btn-lg btn-info">';
			
			echo'Twitter Login';
		
		echo'</a>';
	}
	
	public function get_user_profile_url($app){
		
		return 'https://twitter.com/' . $app->user_name . '/';
	}						
	
	public function get_social_icon_url($app){
		
		return $this->assets_url . 'images/social-icon.png';
	}
	
	public function ltple_twt_auto_retweet_event( $appId, $last ){
		
		if( !isset( $this->parent->apps->{$this->slug} ) ){
			
			$this->parent->apps->includeApp($this->slug,true);
		}
		
		$this->parent->apps->{$this->slug}->retweetLastTweet($appId, $last);
	}
	
	public function ltple_twt_auto_follow_event( $appId, $next ){
		
		if( !isset( $this->parent->apps->{$this->slug} ) ){
			
			$this->parent->apps->includeApp($this->slug,true);
		}
		
		$this->parent->apps->{$this->slug}->followNextLeads($appId, $next);
	}
	
	public function ltple_twt_auto_unfollow_event( $appId, $last ){
		
		if( !isset( $this->parent->apps->{$this->slug} ) ){
			
			$this->parent->apps->includeApp($this->slug,true);
		}
		
		$this->parent->apps->{$this->slug}->unfollowLastLeads($appId, $last);
	}
	
	public function ltple_twt_import_leads_event(){

		if( !isset( $this->parent->apps->{$this->slug} ) ){
			
			$this->parent->apps->includeApp($this->slug,true);
		}
		
		$this->parent->apps->{$this->slug}->importPendingLeads();
	}	
	
	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new LTPLE_Client_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new LTPLE_Client_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		
		//wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		//wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		//wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		
		//wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		//wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		//wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		
		load_plugin_textdomain( $this->settings->plugin->slug, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
		
	    $domain = $this->settings->plugin->slug;

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main LTPLE_App_Twitter Instance
	 *
	 * Ensures only one instance of LTPLE_App_Twitter is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see LTPLE_App_Twitter()
	 * @return Main LTPLE_App_Twitter instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
