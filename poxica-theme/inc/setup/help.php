<?php
/**
 * Theme onboarding and help.
 *
 * @package CommerceGurus
 * @subpackage Poxica_Theme
 */
class Poxica_Theme_Help {

	/**
	 * Constructor
	 * Sets up the welcome screen
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'poxica_theme_help_register_menu' ) );
		add_action( 'load-themes.php', array( $this, 'poxica_theme_help_activation_admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'poxica_theme_help_assets' ) );

		add_action( 'poxica_theme_help', array( $this, 'poxica_theme_help_intro' ), 10 );
		add_action( 'poxica_theme_help', array( $this, 'poxica_theme_help_usefulplugins' ), 20 );
	}

	// End constructor.
	/**
	 * Redirect to Onboarding page upon theme switch/activation
	 */
	public function poxica_theme_help_activation_admin_init() {
		global $pagenow;

		if ( is_admin() && 'themes.php' === $pagenow && isset( $_GET['activated'] ) ) { // input var okay.
			add_action( 'admin_notices', array( $this, 'poxica_theme_welcome_admin_notice' ), 99 );
		}
	}

	/**
	 * Display an admin notice linking to the welcome screen
	 *
	 * @since 1.0.3
	 */
	public function poxica_theme_welcome_admin_notice() {
		?>
		<div class="updated notice is-dismissible">
			<p><?php echo sprintf( esc_html__( 'Thanks for choosing Poxica_Theme! You can read hints and tips on how get the most out of your new theme in the %1$sHelp section%2$s.', 'poxica_theme' ), '<a href="' . esc_url( admin_url( 'themes.php?page=ccfw-help' ) ) . '">', '</a>' ); ?></p>
			<p><a href="<?php echo esc_url( admin_url( 'themes.php?page=ccfw-help' ) ); ?>" class="button" style="text-decoration: none;"><?php esc_html_e( 'Get started with Poxica_Theme', 'poxica_theme' ); ?></a></p>
		</div>
		<?php
	}

	// Help assets.
	public function poxica_theme_help_assets( $hook_suffix ) {
		global $poxica_theme_version;

		if ( 'appearance_page_ccfw-help' === $hook_suffix ) {
			wp_enqueue_style( 'ccfw-help', get_template_directory_uri() . '/inc/setup/help.css', $poxica_theme_version );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'ccfw-help', get_template_directory_uri() . '/inc/setup/help.js', array( 'jquery' ), '1.0.0', true );
		}
	}

	// Quick Start menu.
	public function poxica_theme_help_register_menu() {
		add_theme_page(
		__( 'Poxica_Theme Help', 'poxica_theme' ), __( 'Poxica_Theme Help', 'poxica_theme' ), 'activate_plugins', 'ccfw-help', array( $this, 'poxica_theme_help_screen' ) );
	}

	/**
	 * The welcome screen
	 *
	 * @since 1.0.0
	 */
	public function poxica_theme_help_screen() {
		?>
		<div class="ccfw-help container">

			<h1 class="ccfw-help-title"><?php esc_html_e( 'Poxica_Theme Help', 'poxica_theme' ); ?></h1>
			<h2 class="ccfw-help-desc"><?php esc_html_e( 'Everything you need to get the most out of Poxica_Theme.', 'poxica_theme' ); ?></h2>
			<ul class="ccfw-nav-tabs" role="tablist">
				<li role="presentation" class="active"><a href="#intro" aria-controls="getting_started" role="tab" data-toggle="tab"><?php esc_html_e( 'Getting Started', 'poxica_theme' ); ?></a></li>
				<li role="presentation"><a href="#usefulplugins" aria-controls="usefulplugins" role="tab" data-toggle="tab"><?php esc_html_e( 'Useful Plugins', 'poxica_theme' ); ?></a></li>
			</ul>

			<div class="ccfw-tab-content">
		<?php
		/**
		 * @hooked poxica_theme_welcome_intro - 10
		 */
		do_action( 'poxica_theme_help' );
		?>


			</div>
		</div>
		<?php
	}

	/**
	 * Help - plugin list.
	 */
	public function poxica_theme_help_intro() {
		get_template_part('inc/setup/sections/intro');
	}

	/**
	 * Help - plugin list.
	 */
	public function poxica_theme_help_usefulplugins() {
		get_template_part('inc/setup/sections/usefulplugins');
	}

}

$GLOBALS['poxica_theme_help'] = new Poxica_Theme_Help();
