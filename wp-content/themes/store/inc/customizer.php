<?php
/**
 * store Theme Customizer
 *
 * @package store
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function store_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	
	
	
	//Logo Settings
	$wp_customize->add_section( 'title_tagline' , array(
	    'title'      => __( 'Title, Tagline & Logo', 'store' ),
	    'priority'   => 30,
	) );
	
	$wp_customize->add_setting( 'store_logo' , array(
	    'default'     => '',
	    'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control(
	    new WP_Customize_Image_Control(
	        $wp_customize,
	        'store_logo',
	        array(
	            'label' => 'Upload Logo',
	            'section' => 'title_tagline',
	            'settings' => 'store_logo',
	            'priority' => 5,
	        )
		)
	);
	
	$wp_customize->add_setting( 'store_logo_resize' , array(
	    'default'     => 100,
	    'sanitize_callback' => 'store_sanitize_positive_number',
	) );
	$wp_customize->add_control(
	        'store_logo_resize',
	        array(
	            'label' => __('Resize & Adjust Logo','store'),
	            'section' => 'title_tagline',
	            'settings' => 'store_logo_resize',
	            'priority' => 6,
	            'type' => 'range',
	            'active_callback' => 'store_logo_enabled',
	            'input_attrs' => array(
			        'min'   => 30,
			        'max'   => 200,
			        'step'  => 5,
			    ),
	        )
	);
	
	function store_logo_enabled($control) {
		$option = $control->manager->get_setting('store_logo');
		return $option->value() == true;
	}
	
	
	
	//Replace Header Text Color with, separate colors for Title and Description
	//Override store_site_titlecolor
	$wp_customize->remove_control('display_header_text');
	$wp_customize->remove_setting('header_textcolor');
	$wp_customize->add_setting('store_site_titlecolor', array(
	    'default'     => '#FFFFFF',
	    'sanitize_callback' => 'sanitize_hex_color',
	));
	
	$wp_customize->add_control(new WP_Customize_Color_Control( 
		$wp_customize, 
		'store_site_titlecolor', array(
			'label' => __('Site Title Color','store'),
			'section' => 'colors',
			'settings' => 'store_site_titlecolor',
			'type' => 'color'
		) ) 
	);
	
	$wp_customize->add_setting('store_header_desccolor', array(
	    'default'     => '#FFFFFF',
	    'sanitize_callback' => 'sanitize_hex_color',
	));
	
	$wp_customize->add_control(new WP_Customize_Color_Control( 
		$wp_customize, 
		'store_header_desccolor', array(
			'label' => __('Site Tagline Color','store'),
			'section' => 'colors',
			'settings' => 'store_header_desccolor',
			'type' => 'color'
		) ) 
	);
	
	//Settings for Nav Area
	$wp_customize->add_setting( 'store_disable_nav_desc' , array(
	    'default'     => false,
	    'sanitize_callback' => 'store_sanitize_checkbox',
	) );
	
	$wp_customize->add_control(
	'store_disable_nav_desc', array(
		'label' => __('Disable Description of Menu Items','store'),
		'section' => 'nav',
		'settings' => 'store_disable_nav_desc',
		'type' => 'checkbox'
	) );
	
	
	//Settings For Logo Area
	
	$wp_customize->add_setting(
		'store_hide_title_tagline',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_hide_title_tagline', array(
		    'settings' => 'store_hide_title_tagline',
		    'label'    => __( 'Hide Title and Tagline.', 'store' ),
		    'section'  => 'title_tagline',
		    'type'     => 'checkbox',
		)
	);
		
	function store_title_visible( $control ) {
		$option = $control->manager->get_setting('store_hide_title_tagline');
	    return $option->value() == false ;
	}
	
	//SLIDER
	// SLIDER PANEL
	$wp_customize->add_panel( 'store_slider_panel', array(
	    'priority'       => 35,
	    'capability'     => 'edit_theme_options',
	    'theme_supports' => '',
	    'title'          => 'Main Slider',
	) );
	
	$wp_customize->add_section(
	    'store_sec_slider_options',
	    array(
	        'title'     => 'Enable/Disable',
	        'priority'  => 0,
	        'panel'     => 'store_slider_panel'
	    )
	);
	
	
	$wp_customize->add_setting(
		'store_main_slider_enable',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_main_slider_enable', array(
		    'settings' => 'store_main_slider_enable',
		    'label'    => __( 'Enable Slider on HomePage.', 'store' ),
		    'section'  => 'store_sec_slider_options',
		    'type'     => 'checkbox',
		)
	);
	
	
	$wp_customize->add_setting(
		'store_main_slider_count',
			array(
				'default' => '0',
				'sanitize_callback' => 'store_sanitize_positive_number'
			)
	);
	
	// Select How Many Slides the User wants, and Reload the Page.
	$wp_customize->add_control(
			'store_main_slider_count', array(
		    'settings' => 'store_main_slider_count',
		    'label'    => __( 'No. of Slides(Min:0, Max: 10)' ,'store'),
		    'section'  => 'store_sec_slider_options',
		    'type'     => 'number',
		    'description' => __('Save the Settings, and Reload this page to Configure the Slides.','store'),
		    
		)
	);
	
	for ( $i = 1 ; $i <= 10 ; $i++ ) :
		
		//Create the settings Once, and Loop through it.
		static $x = 0;
		$wp_customize->add_section(
		    'store_slide_sec'.$i,
		    array(
		        'title'     => 'Slide '.$i,
		        'priority'  => $i,
		        'panel'     => 'store_slider_panel',
		        'active_callback' => 'store_show_slide_sec'
		        
		    )
		);	
		
		$wp_customize->add_setting(
			'store_slide_img'.$i,
			array( 'sanitize_callback' => 'esc_url_raw' )
		);
		
		$wp_customize->add_control(
		    new WP_Customize_Image_Control(
		        $wp_customize,
		        'store_slide_img'.$i,
		        array(
		            'label' => '',
		            'section' => 'store_slide_sec'.$i,
		            'settings' => 'store_slide_img'.$i,			       
		        )
			)
		);
		
		$wp_customize->add_setting(
			'store_slide_title'.$i,
			array( 'sanitize_callback' => 'sanitize_text_field' )
		);
		
		$wp_customize->add_control(
				'store_slide_title'.$i, array(
			    'settings' => 'store_slide_title'.$i,
			    'label'    => __( 'Slide Title','store' ),
			    'section'  => 'store_slide_sec'.$i,
			    'type'     => 'text',
			)
		);
		
		$wp_customize->add_setting(
			'store_slide_desc'.$i,
			array( 'sanitize_callback' => 'sanitize_text_field' )
		);
		
		$wp_customize->add_control(
				'store_slide_desc'.$i, array(
			    'settings' => 'store_slide_desc'.$i,
			    'label'    => __( 'Slide Description','store' ),
			    'section'  => 'store_slide_sec'.$i,
			    'type'     => 'text',
			)
		);
		
		
		
		$wp_customize->add_setting(
			'store_slide_CTA_button'.$i,
			array( 'sanitize_callback' => 'sanitize_text_field' )
		);
		
		$wp_customize->add_control(
				'store_slide_CTA_button'.$i, array(
			    'settings' => 'store_slide_CTA_button'.$i,
			    'label'    => __( 'Custom Call to Action Button Text(Optional)','store' ),
			    'section'  => 'store_slide_sec'.$i,
			    'type'     => 'text',
			)
		);
		
		$wp_customize->add_setting(
			'store_slide_url'.$i,
			array( 'sanitize_callback' => 'esc_url_raw' )
		);
		
		$wp_customize->add_control(
				'store_slide_url'.$i, array(
			    'settings' => 'store_slide_url'.$i,
			    'label'    => __( 'Target URL','store' ),
			    'section'  => 'store_slide_sec'.$i,
			    'type'     => 'url',
			)
		);
		
	endfor;
	
	//active callback to see if the slide section is to be displayed or not
	function store_show_slide_sec( $control ) {
	        $option = $control->manager->get_setting('store_main_slider_count');
	        global $x;
	        if ( $x < $option->value() ){
	        	$x++;
	        	return true;
	        }
		}
	
	if ( class_exists('woocommerce') ) :
	// CREATE THE fcp PANEL
	$wp_customize->add_panel( 'store_fcp_panel', array(
	    'priority'       => 40,
	    'capability'     => 'edit_theme_options',
	    'theme_supports' => '',
	    'title'          => 'Featured Product Showcase',
	    'description'    => '',
	) );
	
	
	//SQUARE BOXES
	$wp_customize->add_section(
	    'store_fc_boxes',
	    array(
	        'title'     => 'Square Boxes',
	        'priority'  => 10,
	        'panel'     => 'store_fcp_panel'
	    )
	);
	
	$wp_customize->add_setting(
		'store_box_enable',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_box_enable', array(
		    'settings' => 'store_box_enable',
		    'label'    => __( 'Enable Square Boxes & Posts Slider.', 'store' ),
		    'section'  => 'store_fc_boxes',
		    'type'     => 'checkbox',
		)
	);
	
 
	$wp_customize->add_setting(
		'store_box_title',
		array( 'sanitize_callback' => 'sanitize_text_field' )
	);
	
	$wp_customize->add_control(
			'store_box_title', array(
		    'settings' => 'store_box_title',
		    'label'    => __( 'Title for the Boxes','store' ),
		    'section'  => 'store_fc_boxes',
		    'type'     => 'text',
		)
	);
 
 	$wp_customize->add_setting(
	    'store_box_cat',
	    array( 'sanitize_callback' => 'store_sanitize_product_category' )
	);
	
	$wp_customize->add_control(
	    new WP_Customize_Product_Category_Control(
	        $wp_customize,
	        'store_box_cat',
	        array(
	            'label'    => __('Product Category.','store'),
	            'settings' => 'store_box_cat',
	            'section'  => 'store_fc_boxes'
	        )
	    )
	);
	
		
	//SLIDER
	$wp_customize->add_section(
	    'store_fc_slider',
	    array(
	        'title'     => __('3D Cube Products Slider','store'),
	        'priority'  => 10,
	        'panel'     => 'store_fcp_panel',
	        'description' => 'This is the Posts Slider, displayed left to the square boxes.',
	    )
	);
	
	
	$wp_customize->add_setting(
		'store_slider_title',
		array( 'sanitize_callback' => 'sanitize_text_field' )
	);
	
	$wp_customize->add_control(
			'store_slider_title', array(
		    'settings' => 'store_slider_title',
		    'label'    => __( 'Title for the Slider', 'store' ),
		    'section'  => 'store_fc_slider',
		    'type'     => 'text',
		)
	);
	
	$wp_customize->add_setting(
		'store_slider_count',
		array( 'sanitize_callback' => 'store_sanitize_positive_number' )
	);
	
	$wp_customize->add_control(
			'store_slider_count', array(
		    'settings' => 'store_slider_count',
		    'label'    => __( 'No. of Posts(Min:3, Max: 10)', 'store' ),
		    'section'  => 'store_fc_slider',
		    'type'     => 'range',
		    'input_attrs' => array(
		        'min'   => 3,
		        'max'   => 10,
		        'step'  => 1,
		        'class' => 'test-class test',
		        'style' => 'color: #0a0',
		    ),
		)
	);
		
	$wp_customize->add_setting(
		    'store_slider_cat',
		    array( 'sanitize_callback' => 'store_sanitize_product_category' )
		);
		
	$wp_customize->add_control(
	    new WP_Customize_Product_Category_Control(
	        $wp_customize,
	        'store_slider_cat',
	        array(
	            'label'    => __('Category For Slider.','store'),
	            'settings' => 'store_slider_cat',
	            'section'  => 'store_fc_slider'
	        )
	    )
	);
	
	
	
	//COVERFLOW
	
	$wp_customize->add_section(
	    'store_fc_coverflow',
	    array(
	        'title'     => __('Top CoverFlow Slider','store'),
	        'priority'  => 5,
	        'panel'     => 'store_fcp_panel'
	    )
	);
	
	$wp_customize->add_setting(
		'store_coverflow_enable',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_coverflow_enable', array(
		    'settings' => 'store_coverflow_enable',
		    'label'    => __( 'Enable', 'store' ),
		    'section'  => 'store_fc_coverflow',
		    'type'     => 'checkbox',
		)
	);
	
	$wp_customize->add_setting(
		    'store_coverflow_cat',
		    array( 'sanitize_callback' => 'store_sanitize_product_category' )
		);
	
		
	$wp_customize->add_control(
	    new WP_Customize_Product_Category_Control(
	        $wp_customize,
	        'store_coverflow_cat',
	        array(
	            'label'    => __('Category For Image Grid','store'),
	            'settings' => 'store_coverflow_cat',
	            'section'  => 'store_fc_coverflow'
	        )
	    )
	);
	
	$wp_customize->add_setting(
		'store_coverflow_pc',
		array( 'sanitize_callback' => 'store_sanitize_positive_number' )
	);
	
	$wp_customize->add_control(
			'store_coverflow_pc', array(
		    'settings' => 'store_coverflow_pc',
		    'label'    => __( 'Max No. of Posts in the Grid. Min: 5.', 'store' ),
		    'section'  => 'store_fc_coverflow',
		    'type'     => 'number',
		    'default'  => '0'
		)
	);
	
	endif; //end class exists woocommerce
	
	
	//Extra Panel for Users, who dont have WooCommerce
	
	// CREATE THE fcp PANEL
	$wp_customize->add_panel( 'store_a_fcp_panel', array(
	    'priority'       => 40,
	    'capability'     => 'edit_theme_options',
	    'theme_supports' => '',
	    'title'          => 'Featured Posts Showcase',
	    'description'    => '',
	) );
	
	
	//SQUARE BOXES
	$wp_customize->add_section(
	    'store_a_fc_boxes',
	    array(
	        'title'     => 'Square Boxes',
	        'priority'  => 10,
	        'panel'     => 'store_a_fcp_panel'
	    )
	);
	
	$wp_customize->add_setting(
		'store_a_box_enable',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_a_box_enable', array(
		    'settings' => 'store_a_box_enable',
		    'label'    => __( 'Enable Square Boxes & Posts Slider.', 'store' ),
		    'section'  => 'store_a_fc_boxes',
		    'type'     => 'checkbox',
		)
	);
	
 
	$wp_customize->add_setting(
		'store_a_box_title',
		array( 'sanitize_callback' => 'sanitize_text_field' )
	);
	
	$wp_customize->add_control(
			'store_a_box_title', array(
		    'settings' => 'store_a_box_title',
		    'label'    => __( 'Title for the Boxes','store' ),
		    'section'  => 'store_a_fc_boxes',
		    'type'     => 'text',
		)
	);
 
 	$wp_customize->add_setting(
	    'store_a_box_cat',
	    array( 'sanitize_callback' => 'store_sanitize_product_category' )
	);
	
	$wp_customize->add_control(
	    new WP_Customize_Category_Control(
	        $wp_customize,
	        'store_a_box_cat',
	        array(
	            'label'    => __('Posts Category.','store'),
	            'settings' => 'store_a_box_cat',
	            'section'  => 'store_a_fc_boxes'
	        )
	    )
	);
	
		
	//SLIDER
	$wp_customize->add_section(
	    'store_a_fc_slider',
	    array(
	        'title'     => __('3D Cube Products Slider','store'),
	        'priority'  => 10,
	        'panel'     => 'store_a_fcp_panel',
	        'description' => 'This is the Posts Slider, displayed left to the square boxes.',
	    )
	);
	
	
	$wp_customize->add_setting(
		'store_a_slider_title',
		array( 'sanitize_callback' => 'sanitize_text_field' )
	);
	
	$wp_customize->add_control(
			'store_a_slider_title', array(
		    'settings' => 'store_a_slider_title',
		    'label'    => __( 'Title for the Slider', 'store' ),
		    'section'  => 'store_a_fc_slider',
		    'type'     => 'text',
		)
	);
	
	$wp_customize->add_setting(
		'store_a_slider_count',
		array( 'sanitize_callback' => 'store_sanitize_positive_number' )
	);
	
	$wp_customize->add_control(
			'store_a_slider_count', array(
		    'settings' => 'store_a_slider_count',
		    'label'    => __( 'No. of Posts(Min:3, Max: 10)', 'store' ),
		    'section'  => 'store_a_fc_slider',
		    'type'     => 'range',
		    'input_attrs' => array(
		        'min'   => 3,
		        'max'   => 10,
		        'step'  => 1,
		        'class' => 'test-class test',
		        'style' => 'color: #0a0',
		    ),
		)
	);
		
	$wp_customize->add_setting(
		    'store_a_slider_cat',
		    array( 'sanitize_callback' => 'store_sanitize_product_category' )
		);
		
	$wp_customize->add_control(
	    new WP_Customize_Category_Control(
	        $wp_customize,
	        'store_a_slider_cat',
	        array(
	            'label'    => __('Category For Slider.','store'),
	            'settings' => 'store_a_slider_cat',
	            'section'  => 'store_a_fc_slider'
	        )
	    )
	);
	
	
	
	//COVERFLOW
	
	$wp_customize->add_section(
	    'store_a_fc_coverflow',
	    array(
	        'title'     => __('Top CoverFlow Slider','store'),
	        'priority'  => 5,
	        'panel'     => 'store_a_fcp_panel'
	    )
	);
	
	$wp_customize->add_setting(
		'store_a_coverflow_title',
		array( 'sanitize_callback' => 'sanitize_text_field' )
	);
	
	$wp_customize->add_control(
			'store_a_coverflow_title', array(
		    'settings' => 'store_a_coverflow_title',
		    'label'    => __( 'Title for the Coverflow', 'store' ),
		    'section'  => 'store_a_fc_coverflow',
		    'type'     => 'text',
		)
	);
	
	$wp_customize->add_setting(
		'store_a_coverflow_enable',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_a_coverflow_enable', array(
		    'settings' => 'store_a_coverflow_enable',
		    'label'    => __( 'Enable', 'store' ),
		    'section'  => 'store_a_fc_coverflow',
		    'type'     => 'checkbox',
		)
	);
	
	$wp_customize->add_setting(
		    'store_a_coverflow_cat',
		    array( 'sanitize_callback' => 'store_sanitize_category' )
		);
	
		
	$wp_customize->add_control(
	    new WP_Customize_Category_Control(
	        $wp_customize,
	        'store_a_coverflow_cat',
	        array(
	            'label'    => __('Category For Image Grid','store'),
	            'settings' => 'store_a_coverflow_cat',
	            'section'  => 'store_a_fc_coverflow'
	        )
	    )
	);
	
	$wp_customize->add_setting(
		'store_a_coverflow_pc',
		array( 'sanitize_callback' => 'store_sanitize_positive_number' )
	);
	
	$wp_customize->add_control(
			'store_a_coverflow_pc', array(
		    'settings' => 'store_a_coverflow_pc',
		    'label'    => __( 'Max No. of Posts in the Grid. Min: 5.', 'store' ),
		    'section'  => 'store_a_fc_coverflow',
		    'type'     => 'number',
		    'default'  => '0'
		)
	);
	
	
	// Layout and Design
	$wp_customize->add_panel( 'store_design_panel', array(
	    'priority'       => 40,
	    'capability'     => 'edit_theme_options',
	    'theme_supports' => '',
	    'title'          => __('Design & Layout','store'),
	) );
	
	$wp_customize->add_section(
	    'store_design_options',
	    array(
	        'title'     => __('Blog Layout','store'),
	        'priority'  => 0,
	        'panel'     => 'store_design_panel'
	    )
	);
	
	
	$wp_customize->add_setting(
		'store_blog_layout',
		array( 'sanitize_callback' => 'store_sanitize_blog_layout' )
	);
	
	function store_sanitize_blog_layout( $input ) {
		if ( in_array($input, array('grid','grid_2_column','store','store_3_column') ) )
			return $input;
		else 
			return '';	
	}
	
	$wp_customize->add_control(
		'store_blog_layout',array(
				'label' => __('Select Layout','store'),
				'settings' => 'store_blog_layout',
				'section'  => 'store_design_options',
				'type' => 'select',
				'choices' => array(
						'grid' => __('Standard Blog Layout','store'),
						'store' => __('Store Theme Layout','store'),
						'store_3_column' => __('Store Theme Layout (3 Columns)','store'),
						'grid_2_column' => __('Grid - 2 Column','store'),
					)
			)
	);
	
	$wp_customize->add_section(
	    'store_sidebar_options',
	    array(
	        'title'     => __('Sidebar Layout','store'),
	        'priority'  => 0,
	        'panel'     => 'store_design_panel'
	    )
	);
	
	$wp_customize->add_setting(
		'store_disable_sidebar',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_disable_sidebar', array(
		    'settings' => 'store_disable_sidebar',
		    'label'    => __( 'Disable Sidebar Everywhere.','store' ),
		    'section'  => 'store_sidebar_options',
		    'type'     => 'checkbox',
		    'default'  => false
		)
	);
	
	$wp_customize->add_setting(
		'store_disable_sidebar_home',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_disable_sidebar_home', array(
		    'settings' => 'store_disable_sidebar_home',
		    'label'    => __( 'Disable Sidebar on Home/Blog.','store' ),
		    'section'  => 'store_sidebar_options',
		    'type'     => 'checkbox',
		    'active_callback' => 'store_show_sidebar_options',
		    'default'  => false
		)
	);
	
	$wp_customize->add_setting(
		'store_disable_sidebar_front',
		array( 'sanitize_callback' => 'store_sanitize_checkbox' )
	);
	
	$wp_customize->add_control(
			'store_disable_sidebar_front', array(
		    'settings' => 'store_disable_sidebar_front',
		    'label'    => __( 'Disable Sidebar on Front Page.','store' ),
		    'section'  => 'store_sidebar_options',
		    'type'     => 'checkbox',
		    'active_callback' => 'store_show_sidebar_options',
		    'default'  => false
		)
	);
	
	
	$wp_customize->add_setting(
		'store_sidebar_width',
		array(
			'default' => 4,
		    'sanitize_callback' => 'store_sanitize_positive_number' )
	);
	
	$wp_customize->add_control(
			'store_sidebar_width', array(
		    'settings' => 'store_sidebar_width',
		    'label'    => __( 'Sidebar Width','store' ),
		    'description' => __('Min: 25%, Default: 33%, Max: 40%','store'),
		    'section'  => 'store_sidebar_options',
		    'type'     => 'range',
		    'active_callback' => 'store_show_sidebar_options',
		    'input_attrs' => array(
		        'min'   => 3,
		        'max'   => 5,
		        'step'  => 1,
		        'class' => 'sidebar-width-range',
		        'style' => 'color: #0a0',
		    ),
		)
	);
	
	/* Active Callback Function */
	function store_show_sidebar_options($control) {
	   
	    $option = $control->manager->get_setting('store_disable_sidebar');
	    return $option->value() == false ;
	    
	}
	
	class Store_Custom_CSS_Control extends WP_Customize_Control {
	    public $type = 'textarea';
	 
	    public function render_content() {
	        ?>
	            <label>
	                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
	                <textarea rows="8" style="width:100%;" <?php $this->link(); ?>><?php echo esc_textarea( $this->value() ); ?></textarea>
	            </label>
	        <?php
	    }
	}
	
	$wp_customize-> add_section(
    'store_custom_codes',
    array(
    	'title'			=> __('Custom CSS','store'),
    	'description'	=> __('Enter your Custom CSS to Modify design.','store'),
    	'priority'		=> 11,
    	'panel'			=> 'store_design_panel'
    	)
    );
    
	$wp_customize->add_setting(
	'store_custom_css',
	array(
		'default'		=> '',
		'capability'           => 'edit_theme_options',
		'sanitize_callback'    => 'wp_filter_nohtml_kses',
		'sanitize_js_callback' => 'wp_filter_nohtml_kses'
		)
	);
	
	$wp_customize->add_control(
	    new Store_Custom_CSS_Control(
	        $wp_customize,
	        'store_custom_css',
	        array(
	            'section' => 'store_custom_codes',
	            'settings' => 'store_custom_css'
	        )
	    )
	);
	
	function store_sanitize_text( $input ) {
	    return wp_kses_post( force_balance_tags( $input ) );
	}
	
	$wp_customize-> add_section(
    'store_custom_footer',
    array(
    	'title'			=> __('Custom Footer Text','store'),
    	'description'	=> __('Enter your Own Copyright Text.','store'),
    	'priority'		=> 11,
    	'panel'			=> 'store_design_panel'
    	)
    );
    
	$wp_customize->add_setting(
	'store_footer_text',
	array(
		'default'		=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
		)
	);
	
	$wp_customize->add_control(	 
	       'store_footer_text',
	        array(
	            'section' => 'store_custom_footer',
	            'settings' => 'store_footer_text',
	            'type' => 'text'
	        )
	);	
	
	
	//Select the Default Theme Skin
	$wp_customize->add_section(
	    'store_skin_options',
	    array(
	        'title'     => __('Choose Skin','store'),
	        'priority'  => 39,
	    )
	);
	
	$wp_customize->add_setting(
		'store_skin',
		array(
			'default'=> 'default',
			'sanitize_callback' => 'store_sanitize_skin' 
			)
	);
	
	$skins = array( 'default' => __('Default(blue)','store'),
					'orange' =>  __('Orange','store'),
					'brown' =>  __('Brown','store'),
					'green' => __('Green','store'),
					'grayscale' => __('GrayScale','store') );
	
	$wp_customize->add_control(
		'store_skin',array(
				'settings' => 'store_skin',
				'section'  => 'store_skin_options',
				'type' => 'select',
				'choices' => $skins,
			)
	);
	
	function store_sanitize_skin( $input ) {
		if ( in_array($input, array('default','orange','brown','green','grayscale') ) )
			return $input;
		else
			return '';
	}
	
	
	//Fonts
	$wp_customize->add_section(
	    'store_typo_options',
	    array(
	        'title'     => __('Google Web Fonts','store'),
	        'priority'  => 41,
	        'description' => __('Defaults: Lato, Open Sans.','store')
	    )
	);
	
	$font_array = array('Raleway','Khula','Open Sans','Droid Sans','Droid Serif','Roboto','Roboto Condensed','Lato','Bree Serif','Oswald','Slabo','Lora','Source Sans Pro','PT Sans','Ubuntu','Lobster','Arimo','Bitter','Noto Sans');
	$fonts = array_combine($font_array, $font_array);
	
	$wp_customize->add_setting(
		'store_title_font',
		array(
			'default'=> 'Lato',
			'sanitize_callback' => 'store_sanitize_gfont' 
			)
	);
	
	function store_sanitize_gfont( $input ) {
		if ( in_array($input, array('Raleway','Khula','Open Sans','Droid Sans','Droid Serif','Roboto','Roboto Condensed','Lato','Bree Serif','Oswald','Slabo','Lora','Source Sans Pro','PT Sans','Ubuntu','Lobster','Arimo','Bitter','Noto Sans') ) )
			return $input;
		else
			return '';	
	}
	
	$wp_customize->add_control(
		'store_title_font',array(
				'label' => __('Title','store'),
				'settings' => 'store_title_font',
				'section'  => 'store_typo_options',
				'type' => 'select',
				'choices' => $fonts,
			)
	);
	
	$wp_customize->add_setting(
		'store_body_font',
			array(	'default'=> 'Open Sans',
					'sanitize_callback' => 'store_sanitize_gfont' )
	);
	
	$wp_customize->add_control(
		'store_body_font',array(
				'label' => __('Body','store'),
				'settings' => 'store_body_font',
				'section'  => 'store_typo_options',
				'type' => 'select',
				'choices' => $fonts
			)
	);
	
	// Social Icons
	$wp_customize->add_section('store_social_section', array(
			'title' => __('Social Icons','store'),
			'priority' => 44 ,
	));
	
	$social_networks = array( //Redefinied in Sanitization Function.
					'none' => __('-','store'),
					'facebook' => __('Facebook','store'),
					'twitter' => __('Twitter','store'),
					'google-plus' => __('Google Plus','store'),
					'instagram' => __('Instagram','store'),
					'rss' => __('RSS Feeds','store'),
					'vine' => __('Vine','store'),
					'vimeo-square' => __('Vimeo','store'),
					'youtube' => __('Youtube','store'),
					'flickr' => __('Flickr','store'),
				);
				
	$social_count = count($social_networks);
				
	for ($x = 1 ; $x <= ($social_count - 3) ; $x++) :
			
		$wp_customize->add_setting(
			'store_social_'.$x, array(
				'sanitize_callback' => 'store_sanitize_social',
				'default' => 'none'
			));

		$wp_customize->add_control( 'store_social_'.$x, array(
					'settings' => 'store_social_'.$x,
					'label' => __('Icon ','store').$x,
					'section' => 'store_social_section',
					'type' => 'select',
					'choices' => $social_networks,			
		));
		
		$wp_customize->add_setting(
			'store_social_url'.$x, array(
				'sanitize_callback' => 'esc_url_raw'
			));

		$wp_customize->add_control( 'store_social_url'.$x, array(
					'settings' => 'store_social_url'.$x,
					'description' => __('Icon ','store').$x.__(' Url','store'),
					'section' => 'store_social_section',
					'type' => 'url',
					'choices' => $social_networks,			
		));
		
	endfor;
	
	function store_sanitize_social( $input ) {
		$social_networks = array(
					'none' ,
					'facebook',
					'twitter',
					'google-plus',
					'instagram',
					'rss',
					'vine',
					'vimeo-square',
					'youtube',
					'flickr'
				);
		if ( in_array($input, $social_networks) )
			return $input;
		else
			return '';	
	}
	
	$wp_customize->add_section(
	    'store_sec_upgrade',
	    array(
	        'title'     => __('Store Theme - Help & Support','store'),
	        'priority'  => 45,
	    )
	);
	
	$wp_customize->add_setting(
			'store_upgrade',
			array( 'sanitize_callback' => 'esc_textarea' )
		);
			
	$wp_customize->add_control(
	    new WP_Customize_Upgrade_Control(
	        $wp_customize,
	        'store_upgrade',
	        array(
	            'label' => __('Thank You','store'),
	            'description' => __('Thank You for Choosing Store Theme by Rohitink.com. Store is a Powerful Wordpress theme which also supports WooCommerce in the best possible way. It is "as we say" the last theme you would ever need. It has all the basic and advanced features needed to run a gorgeous looking site. For any Help related to this theme, please visit  <a href="https://rohitink.com/2015/05/21/store-woocommerce-theme/">Store Help & Support</a>.','store'),
	            'section' => 'store_sec_upgrade',
	            'settings' => 'store_upgrade',			       
	        )
		)
	);
	
	
	/* Sanitization Functions Common to Multiple Settings go Here, Specific Sanitization Functions are defined along with add_setting() */
	function store_sanitize_checkbox( $input ) {
	    if ( $input == 1 ) {
	        return 1;
	    } else {
	        return '';
	    }
	}
	
	function store_sanitize_positive_number( $input ) {
		if ( ($input >= 0) && is_numeric($input) )
			return $input;
		else
			return '';	
	}
	
	function store_sanitize_category( $input ) {
		if ( term_exists(get_cat_name( $input ), 'category') )
			return $input;
		else 
			return '';	
	}
	
	function store_sanitize_product_category( $input ) {
		if ( get_term( $input, 'product_cat' ) )
			return $input;
		else 
			return '';	
	}
	
	
}
add_action( 'customize_register', 'store_customize_register' );


/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function store_customize_preview_js() {
	wp_enqueue_script( 'store_customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20130508', true );
}
add_action( 'customize_preview_init', 'store_customize_preview_js' );
