<?php
/* 
**   Custom Modifcations in CSS depending on user settings.
*/

function store_custom_css_mods() {

	echo "<style id='custom-css-mods'>";
	
	
	//If Menu Description is Disabled.
	if ( !has_nav_menu('primary') || get_theme_mod('store_disable_nav_desc') ) :
		echo "#site-navigation ul li a { padding: 16px 12px; }";
	endif;
	
	
	//Exception: IMage transform origin should be left on Left Alignment, i.e. Default
	if ( !get_theme_mod('store_center_logo') ) :
		echo "#masthead #site-logo img { transform-origin: left; }";
	endif;	
	
	if ( get_theme_mod('store_title_font') ) :
		echo ".title-font, h1, h2, .section-title, .woocommerce ul.products li.product h3 { font-family: ".esc_html( get_theme_mod('store_title_font','Lato') )."; }";
	endif;
	
	if ( get_theme_mod('store_body_font') ) :
		echo "body { font-family: ".esc_html( get_theme_mod('store_body_font','Open Sans') )."; }";
	endif;
	
	if ( get_theme_mod('store_site_titlecolor') ) :
		echo "#masthead h1.site-title a { color: ".esc_html( get_theme_mod('store_site_titlecolor', '#FFFFFF') )."; }";
	endif;
	
	
	if ( get_theme_mod('store_header_desccolor','#777') ) :
		echo ".site-description { color: ".esc_html( get_theme_mod('store_header_desccolor','#FFFFFF') )."; }";
	endif;
	//Check Jetpack is active
	if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'infinite-scroll' ) )
		echo '.pagination { display: none; }';


	if ( get_theme_mod('store_custom_css') ) :
		echo  esc_html( get_theme_mod('store_custom_css') );
	endif;
	
	
	if ( get_theme_mod('store_hide_title_tagline') ) :
		echo "#masthead .site-branding #text-title-desc { display: none; }";
	endif;
	
	if ( get_theme_mod('store_logo_resize') ) :
		$val = esc_html( get_theme_mod('store_logo_resize') )/100;
		echo "#masthead #site-logo img { transform: scale(".$val."); -webkit-transform: scale(".$val."); -moz-transform: scale(".$val."); -ms-transform: scale(".$val."); }";
		endif;

	echo "</style>";
}

add_action('wp_head', 'store_custom_css_mods');