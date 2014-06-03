<?php

/*
Plugin Name: GK Tabs
Plugin URI: http://www.gavick.com/
Description: Widget for the tabs interface display
Version: 1.0
Author: GavickPro
Author URI: http://www.gavick.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*

Copyright 2013-2013 GavickPro (info@gavick.com)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/*

Available actions:
- gk_tabs_before_tabs_wrapper
- gk_tabs_before_tabs_list
- gk_tabs_after_tabs_list
- gk_tabs_before_tabs
- gk_tabs_after_tabs
- gk_tabs_after_tabs_wrapper

Available filters:
- gk_tabs_tab_title
- gk_tabs_tab_content
- gk_tabs_prev_button
- gk_tabs_next_button

*/

if ( !defined( 'WPINC' ) ) {
    die;
}

/**
 * i18n
 */
load_plugin_textdomain( 'gk-tabs', false, dirname( dirname( plugin_basename( __FILE__) ) ).'/languages' );

/**
 *  wiget initialization and adding additional sidebars
 */
add_action( 'widgets_init', 'gk_tabs_register' );
add_action( 'wp_loaded', array('Gk_Tabs_Widget', 'register_sidebars'));

/**
 * Register the GK Tabs Widget.
 *
 * Hooks into the widgets_init action.
 */
function gk_tabs_register() {
	register_widget( 'Gk_Tabs_Widget' );
}

/**
 * install & uninstall
 */
register_activation_hook( __FILE__, array( 'Gk_Tabs_Widget', 'install' ) );
register_deactivation_hook( __FILE__, array( 'Gk_Tabs_Widget', 'uninstall' ) );

/**
 *
 * Main widget class
 *
 */
class Gk_Tabs_Widget extends WP_Widget {
	// storage of the widget settings with default values
	private $config = array(
								'title' 				=> '',
								'selected_sidebar' 		=> '',
								'first_tab'				=> '1',
								'event' 				=> 'click',
								'swipe' 				=> 'off',
								'navbuttons' 			=> 'off',
								'tabs_position'			=> 'top',
								'autoanim' 				=> 'off',
								'stop_on_hover'			=> 'off',
								'anim_speed' 			=> '500',
								'anim_interval' 		=> '5000',
								'anim_type'				=> 'opacity',
								'amount_of_sidebars' 	=> '3',
								'style'					=> 'default-style',
								'cache'					=> 'none',
								'cache_time'			=> 60
							);

	/**
	 *
	 * Constructor
	 *
	 * @return void
	 *
	 **/
	function __construct() {
		$this->WP_Widget(
			'gk_tabs', 
			__( 'Tabs by GavickPro', 'gk-tabs' ), 
			array( 
				'classname' => 'widget_gk_tabs', 
				'description' => __( 'Use this widget to show tabs created form the selected sidebar', 'gk-tabs') 
			)
		);
		
		$this->alt_option_name = 'gk_tabs';
		//
		add_action('wp_enqueue_scripts', array($this, 'add_js'));
		add_action('wp_enqueue_scripts', array($this, 'add_css'));
		add_action('admin_enqueue_scripts', array($this, 'add_admin_css'));
	}
	
	/**
	 *
	 * Installation method - blocks users without capabilities
	 *
	 * @return void
	 *
	 **/
	static function install() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
	}

	/**
	 *
	 * Uninstall method - removes the widget options storage
	 *
	 * @return void
	 *
	 */
	static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		// remove the config option
		delete_option( 'widget_gk_tabs' );
	}

	/**
	 *
	 * Method used to add the widget scripts
	 *
	 * @return void
	 *
	 */
	function add_js() {
		wp_register_script( 'gk-tabs', plugins_url('gk-tabs.js', __FILE__), array('jquery'), false, true);
		wp_enqueue_script('gk-tabs');
	}

	/**
	 *
	 * Method used to add the widget stylesheets
	 * it detects all necessary stylesheets to add
	 *
	 * @return void
	 *
	 */
	function add_css() {
		$instances = get_option('widget_gk_tabs');
		$loaded_files = array();

		if(is_array($instances) || is_object($instances)) {
			foreach($instances as $instance) {
				if($instance['style'] != '' && $instance['style'] != 'none' && !in_array($instance['style'], $loaded_files)) {
					wp_register_style( 'gk-tabs-' . $instance['style'], plugins_url('styles/'. $instance['style'] .'.css', __FILE__), array(), false, 'all');
					wp_enqueue_style('gk-tabs-' . $instance['style']);	
					array_push($loaded_files, $instance['style']);
				}
			}
		}
	}

	/**
	 *
	 * Method used to add the back-end CSS file
	 *
	 * @return void
	 *
	 */
	function add_admin_css() {
		wp_register_style( 'gk-tabs', plugins_url('gk-tabs-admin.css', __FILE__), array(), false, 'all');
		wp_enqueue_style('gk-tabs');
	}

	/**
	 *
	 * Method used for adding sidebars used by widget
	 *
	 * @return void
	 *
	 */
	static function register_sidebars() {
		// get the biggest amount of sidebars
		$option = get_option('widget_gk_tabs');
		$amount_of_sidebars = 0;
		if(is_array($option) && count($option) > 0) {
			foreach($option as $tabs_widget_instance) {
				if($tabs_widget_instance['amount_of_sidebars'] > $amount_of_sidebars) {
					$amount_of_sidebars = $tabs_widget_instance['amount_of_sidebars'];
				}
			}
		} else {
			$amount_of_sidebars = 3;
		}
		// use the value for generating new sidebars
		for($i = 1; $i <= $amount_of_sidebars; $i++) {
			register_sidebar(
				array(
					'name'          => 'GK Tabs ' . Gk_Tabs_Widget::roman_number($i),
					'id'            => 'gk-tabs-sidebar-' . $i,
					'description'   => '',
			        'class'         => '',
					'before_widget' => '',
					'after_widget'  => '',
					'before_title'  => '',
					'after_title'   => ''
				)
			);
		}
	}

	/**
	 *
	 * Outputs the HTML code of this widget.
	 *
	 * @param array An array of standard parameters for widgets in this theme
	 * @param array An array of settings for this widget instance
	 * @return void
	 *
	 **/
	function widget($args, $instance) {	
		global $wp_registered_widgets;
		global $wp_registered_sidebars;
		// check the ID
		if(!isset($args['widget_id'])) {
			$args['widget_id'] = null;
		}
		// change the array of settings to variables
		extract($args, EXTR_SKIP);
		// get the values of settings - and default values for non-existing settings
		foreach($this->config as $key => $value) {
			// the title option is a special case
			if($key == 'title') {
				$this->config['title'] = apply_filters('widget_title', !isset($instance['title']) ? $this->config['title'] : $instance['title'], $instance, $this->id_base);
			} else {
				$this->config[$key] = !isset($instance[$key]) ? $this->config[$key] : $instance[$key];
			}
		}
		// get the cache content
		$cache_content = get_transient(md5($this->id));
		// if the whole widget cache type is enabled and cache content exists - output it
		if($this->config['cache'] == 'widget' && $cache_content && $this->config['cache_time'] > 0) {
			echo $cache_content;
			return;
		}
		// prepare a variable for the cached data
		$cache_output = '';
		// start cache buffering - whole widget
		if($this->config['cache'] == 'widget') {
			ob_start();
		}
		// check if the recursive problem doesn't appear
		$all_sidebars = get_option('sidebars_widgets');
		$recursive_flag = false;

		foreach($all_sidebars as $sidebar_name => $single_sidebar) {
			if(
				is_array($single_sidebar) && 
				in_array($args['widget_id'], $single_sidebar) && 
				$sidebar_name == $this->config['selected_sidebar']
			) {
				$recursive_flag = true;
			}
		}
		// if the recursive problem didn't appeared
		if(!$recursive_flag) {
			// check if user selected the sidebar
			if ($this->config['selected_sidebar'] !== '') {
				// render the widget
				echo $before_widget;
				if($this->config['title'] != '') {
					echo $before_title;
					echo $this->config['title'];
					echo $after_title;
				}
				// get the first tab number
				$first_tab = 1;
				if(
					is_numeric($this->config['first_tab']) && 
					intval($this->config['first_tab']) > 0
				) {
					$first_tab = intval($this->config['first_tab']);
				}
				// check if in the URL there is no other params
				if(
					isset($_GET['gktab']) &&
					is_numeric($_GET['gktab']) && 
					intval($_GET['gktab']) > 0
				) {
					$first_tab = intval($_GET['gktab']);
				}
				// generating wrapper with params in the data-* attributes
				echo '<div 
						class="gk-tabs gk-tabs-'.$this->config['tabs_position'].' '.$this->config['style'].'" 
						data-event="'.$this->config['event'].'" 
						data-stoponhover="'.$this->config['stop_on_hover'].'" 
						data-swipe="'.$this->config['swipe'].'" 
						data-autoanim="'.$this->config['autoanim'].'" 
						data-speed="'.$this->config['anim_speed'].'" 
						data-interval="'.$this->config['anim_interval'].'"
						data-active="'.($first_tab - 1).'"
						data-anim="'.$this->config['anim_type'].'"
					>';
				do_action('gk_tabs_before_tabs_wrapper');
				echo '<div class="gk-tabs-wrap">';
				// store the current output to the cache variable
				if($this->config['cache'] == 'widget') {
					$cache_output = ob_get_flush();
				}
				// creating the tabs
				$sidebars = wp_get_sidebars_widgets();
				$widget_code = array();
				// get the widget classes
				$css_classes = get_option(wp_get_theme() . '_widget_style_css');
				$css_styles = get_option(wp_get_theme() . '_widget_style');
				$css_result = array();
				// get the cache settings to use proper behaviour of the widget code
				if($this->config['cache'] == 'content' && $cache_content && $this->config['cache_time'] > 0) {
					$widget_code = $cache_content;
				} else {
					// get all widgets from the specific sidebar
					foreach($sidebars[$this->config['selected_sidebar']] as $widget) {
						// if widget area exists..
						if(isset($wp_registered_widgets[$widget])) {
							// get the widget data from this widget area
							$selected_sidebar = $wp_registered_sidebars[$this->config['selected_sidebar']];
							// get the widget params and merge with sidebar data, widget ID and name
							$params = array_merge(
								array( 
									array_merge( 
										$selected_sidebar, 
										array(
											'widget_id' => $widget, 
											'widget_name' => $wp_registered_widgets[$widget]['name']
										) 
									) 
								),
								
								(array) $wp_registered_widgets[$widget]['params']
							);
							// apply params
							$params = apply_filters( 'dynamic_sidebar_params', $params );
							// modify params
							$params[0]['before_widget'] = '<div id="'.$widget.'" class="box '.$wp_registered_widgets[$widget]['classname'].'"><div>';
							$params[0]['after_widget'] = '</div></div>';
							$params[0]['before_title'] = '{TABS_TITLE}';
							$params[0]['after_title'] = '{TABS_TITLE}';
							// get the widget callback function
							$callback = $wp_registered_widgets[$widget]['callback'];
							// generate
							ob_start();
							do_action('dynamic_sidebar', $wp_registered_widgets[$widget]);
							// use the widget callback function if exists
							if ( is_callable($callback) ) {
								call_user_func_array($callback, $params);
							}
							// get the widget code
							array_push($widget_code, ob_get_contents());
							ob_end_clean();
							
							$css_classname = '';
							// get the class name				
							if(isset($css_styles[$widget]) && $css_styles[$widget] != '' && $css_styles[$widget] != 'gkcustom') {
								$css_classname = $css_styles[$widget];
							} elseif((isset($css_classes[$widget]) && $css_classes[$widget] != '')){	
								$css_classname = $css_classes[$widget];
							}
							// put the class name to the array
							array_push($css_result, $css_classname);
						}
					}
					// store the results
					if($this->config['cache'] == 'content') {
						$cache_output = $widget_code;
						$this->config['cache_time'] = ($this->config['cache_time'] == '' || !is_numeric($this->config['cache_time'])) ? 60 : (int) $this->config['cache_time'];
						set_transient(md5($this->id) , $cache_output, $this->config['cache_time'] * 60);
					}
				}
				// start caching the whole widget
				if($this->config['cache'] == 'widget') {
					ob_start();
				}
				// get the tabs data
				$tabs = array();
				$tabs_content = array();
				// iterate through all founded widgets
				foreach($widget_code as $code) {
					// get the widget title using string delimiters
					$title_match = array();
					preg_match_all('@{TABS_TITLE}(.*?){TABS_TITLE}@mis', $code, $title_match);
					// and store the title in the tabs titles array
					if(count($title_match) > 1 && isset($title_match[1][0])) {
						array_push($tabs, $title_match[1][0]);
					} else {
						array_push($tabs, _e('No title specified!', 'gk-tabs'));
					}
					// push the content to the content array with removed title using string delimiters
					array_push($tabs_content, preg_replace('@{TABS_TITLE}(.*?){TABS_TITLE}@mis', '', $code));
				}
				// generate the top tabs navigation
				if($this->config['tabs_position'] == 'top') {
					do_action('gk_tabs_before_tabs_list');
					
					echo '<ol class="gk-tabs-nav">';
					for($i = 0; $i < count($tabs); $i++) {
						// if the custom class exists
						if($css_result[$i] == '') { 
							echo '<li'.(($i == 0) ? ' class="active"' : '').'>' . apply_filters('gk_tabs_tab', $tabs[$i]) . '</li>';
						} else {
							echo '<li'.(($i == 0) ? ' class="active '.$css_result[$i].'"' : ' class="'.$css_result[$i].'"').'>' . $tabs[$i] . '</li>';
						}
					}
					echo '</ol>';

					do_action('gk_tabs_after_tabs_list');
				}
				// generate the tabs content
				do_action('gk_tabs_before_tabs');

				echo '<div class="gk-tabs-container">';
				for($i = 0; $i < count($tabs_content); $i++) {
					echo '<div class="gk-tabs-item'.(($i == $first_tab - 1) ? ' active' : '').'">' . apply_filters('gk_tabs_tab_content', $tabs_content[$i]) . '</div>';
				}
				echo '</div>';

				do_action('gk_tabs_after_tabs');

				// generate the tabs navigation
				if($this->config['tabs_position'] == 'bottom') {
					do_action('gk_tabs_before_tabs_list');

					echo '<ol class="gk-tabs-nav">';
					for($i = 0; $i < count($tabs); $i++) {
						echo '<li'.(($i == $first_tab - 1) ? ' class="active"' : '').'>' . apply_filters('gk_tabs_tab_title', $tabs[$i]) . '</li>';
					}
					echo '</ol>';

					do_action('gk_tabs_after_tabs_list');
				}

				// generate the navigation buttons
				if($this->config['navbuttons'] == 'on') {
					echo apply_filters('gk_tabs_prev_button', '<span class="gk-tabs-prev">&laquo;</span>');
					echo apply_filters('gk_tabs_next_button', '<span class="gk-tabs-next">&raquo;</span>');
				}

				// close the tabs wrapper
				echo '</div>';
				do_action('gk_tabs_after_tabs_wrapper');
				echo '</div>';
				// 
				echo $after_widget;
			} else {
				// when user didn't selected any tabs source - show an error
				echo $before_widget;
				if($this->config['title'] != '') {
					echo $before_title;
					echo $this->config['title'];
					echo $after_title;
				}
				//
				echo '<p class="gk-tabs-error"><strong>&times;</strong>'.__('You didn\'t select any tabs source :(', 'gk-tabs').'</p>';
				// 
				echo $after_widget;
			}
		} else {
			// when user selected as a tabs source the widget area with the tabs widget - show an error
			echo $before_widget;
			if($this->config['title'] != '') {
				echo $before_title;
				echo $this->config['title'];
				echo $after_title;
			}
			//
			echo '<p class="gk-tabs-error"><strong>&infin;</strong>'.__('It seems that you want to do something very bad ;)', 'gk-tabs') . '<br /><small>' . __('Tip: recursion is very dangerous - please change the source of tabs for this widget.', 'gk-tabs').'</small></p>';
			// 
			echo $after_widget;
		}
		// the final output of the cache
		if($this->config['cache'] == 'widget') {
			// get the rest of the output
			$cache_output .= ob_get_flush();
			$this->config['cache_time'] = ($this->config['cache_time'] == '' || !is_numeric($this->config['cache_time'])) ? 60 : (int) $this->config['cache_time'];
			set_transient(md5($this->id) , $cache_output, $this->config['cache_time'] * 60);
		}
	}

	/**
	 *
	 * Used in the back-end to update the module options
	 *
	 * @param array new instance of the widget settings
	 * @param array old instance of the widget settings
	 * @return updated instance of the widget settings
	 *
	 **/
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		// check if the new instance contains any options
		if(count($new_instance) > 0) {
			// escape all options
			foreach($new_instance as $key => $option) {
				$instance[$key] = esc_attr(strip_tags($new_instance[$key]));
			}
		}
		// cache refresh
		$this->refresh_cache();
		// remove the gk_Tabs option if exists
		$alloptions = wp_cache_get('alloptions', 'options');
		if(isset($alloptions['gk_tabs'])) {
			delete_option( 'gk_tabs' );
		}
		// return the updated widget instance
		return $instance;
	}

	/**
	 *
	 * Refreshes the widget cache data
	 *
	 * @return void
	 *
	 **/
	function refresh_cache() {
		wp_cache_delete( 'gk_tabs', 'widget' );
		// clear all widget transients
		if(is_array(get_option('widget_gk_tabs'))) {
		    $ids = array_keys(get_option('widget_gk_tabs'));
		    for($i = 0; $i < count($ids); $i++) {
		        if(is_numeric($ids[$i])) {
		            delete_transient(md5('gk_tabs-' . $ids[$i]));
		        }
		    }
	    } else {
	    	delete_transient(md5('gk_tabs-' . $this->id));
	    }
	}

	/**
	 *
	 * Outputs the HTML code of the widget in the back-end
	 *
	 * @param array instance of the widget settings
	 * @return void - HTML output
	 *
	 **/
	function form($instance) {
		global $wp_registered_sidebars;
		// check the option values or default value if the option didn't exist 
		foreach($this->config as $key => $value) {
			$this->config[$key] = isset($instance[$key]) ? esc_attr($instance[$key]) : $this->config[$key];
		}
	
	?>
		<div class="gk-tabs-ui">
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" title="<?php _e('Specify the title of the widget - leave blank to avoid this element.', 'gk-tabs'); ?>"><?php _e( 'Title:', 'gk-tabs' ); ?></label>
				<input class="gk-title" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $this->config['title'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" title="<?php _e('Specify the widget style. You can add your own styles in the styles directory.', 'gk-tabs'); ?>"><?php _e( 'Style:', 'gk-tabs' ); ?></label>
				
				<?php
					// get the styles
					$available_styles = array();
					$styles_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'styles';
					$style_files = scandir($styles_path);
					// iterate through all style files
					foreach($style_files as $file) {
						if($file != '.' && $file != '..' && substr($file, -4) == '.css') {
							$style_data = get_file_data($styles_path . DIRECTORY_SEPARATOR . $file, array('Name' => 'Name'));
							// store the style filename and name
							if(isset($style_data['Name']) && trim($style_data['Name']) != '') {
								$available_styles[$style_data['Name']] = substr($file, 0, -4);
							}
						}
					}
				?>

				<select id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
					<option value="none"<?php selected('none', $this->config['style']); ?>><?php _e('None', 'gk-tabs'); ?></option>
					<?php 
						// iterate through results
						foreach($available_styles as $style_name => $style_file) : 
					?>
					<option value="<?php echo $style_file; ?>"<?php selected($style_file, $this->config['style']); ?>><?php echo $style_name; ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'selected_sidebar' ) ); ?>" title="<?php _e('Specify the sidebar which will be source of the tabs. The widget will receive the widget title as a tabs name and content of the widget as a tabs content.', 'gk-tabs'); ?>"><?php _e( 'Tabs source:', 'gk-tabs' ); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id( 'selected_sidebar' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'selected_sidebar' ) ); ?>">
					<option value=""<?php selected('', $this->config['selected_sidebar']); ?>><?php _e('None', 'gk-tabs'); ?></option>
					<?php foreach(array_keys($wp_registered_sidebars) as $sidebar) : ?>
					<option value="<?php echo $sidebar; ?>"<?php selected($sidebar, $this->config['selected_sidebar']); ?>><?php echo $wp_registered_sidebars[$sidebar]["name"]; ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'first_tab' ) ); ?>" title="<?php _e('Specify which tab will be selected as first.', 'gk-tabs'); ?>"><?php _e( 'First tab:', 'gk-tabs' ); ?></label>
				<input class="gk-small" id="<?php echo esc_attr( $this->get_field_id( 'first_tab' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'first_tab' ) ); ?>" type="text" value="<?php echo esc_attr( $this->config['first_tab'] ); ?>" />
			</p>
			
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('event')); ?>" title="<?php _e('You can specify which event will be used to change tabs.', 'gk-tabs'); ?>"><?php _e('Tabs activator event:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr($this->get_field_id('event')); ?>" name="<?php echo esc_attr( $this->get_field_name('event')); ?>">
					<option value="click"<?php selected($this->config['event'], 'click'); ?>><?php _e('Click', 'gk-tabs'); ?></option>
					<option value="mouseenter"<?php selected($this->config['event'], 'mouseenter'); ?>><?php _e('Hover', 'gk-tabs'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr($this->get_field_id('swipe')); ?>" title="<?php _e('You can enable swipe gesture for tabs navigation on the touch devices.', 'gk-tabs'); ?>"><?php _e('Swipe gesture:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id('swipe')); ?>" name="<?php echo esc_attr( $this->get_field_name('swipe')); ?>">
					<option value="on"<?php selected($this->config['swipe'], 'on'); ?>><?php _e('Enabled', 'gk-tabs'); ?></option>
					<option value="off"<?php selected($this->config['swipe'], 'off'); ?>><?php _e('Disabled', 'gk-tabs'); ?></option>
				</select>
			</p>
			
			<p>
				<label for="<?php echo esc_attr($this->get_field_id('navbuttons')); ?>" title="<?php _e('You can enable the additional navigation buttons.', 'gk-tabs'); ?>"><?php _e('Navigation buttons:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id('navbuttons')); ?>" name="<?php echo esc_attr( $this->get_field_name('navbuttons')); ?>">
					<option value="on"<?php selected($this->config['navbuttons'], 'on'); ?>><?php _e('Enabled', 'gk-tabs'); ?></option>
					<option value="off"<?php selected($this->config['navbuttons'], 'off'); ?>><?php _e('Disabled', 'gk-tabs'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr($this->get_field_id('tabs_position')); ?>" title="<?php _e('You can specify the position of the tabs.', 'gk-tabs'); ?>"><?php _e('Tabs position:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id('tabs_position')); ?>" name="<?php echo esc_attr( $this->get_field_name('tabs_position')); ?>">
					<option value="top"<?php selected($this->config['tabs_position'], 'top'); ?>><?php _e('Top', 'gk-tabs'); ?></option>
					<option value="bottom"<?php selected($this->config['tabs_position'], 'bottom'); ?>><?php _e('Bottom', 'gk-tabs'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr($this->get_field_id('autoanim')); ?>" title="<?php _e('You can enable auto animation of the tabs.', 'gk-tabs'); ?>"><?php _e('Auto animation:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id('autoanim')); ?>" name="<?php echo esc_attr( $this->get_field_name('autoanim')); ?>">
					<option value="on"<?php selected($this->config['autoanim'], 'on'); ?>><?php _e('Enabled', 'gk-tabs'); ?></option>
					<option value="off"<?php selected($this->config['autoanim'], 'off'); ?>><?php _e('Disabled', 'gk-tabs'); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr($this->get_field_id('stop_on_hover')); ?>" title="<?php _e('You can enable function which will stop the auto animation when user hovers the tabs content.', 'gk-tabs'); ?>"><?php _e('Stop on hover:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id('stop_on_hover')); ?>" name="<?php echo esc_attr( $this->get_field_name('stop_on_hover')); ?>">
					<option value="on"<?php selected($this->config['stop_on_hover'], 'on'); ?>><?php _e('Enabled', 'gk-tabs'); ?></option>
					<option value="off"<?php selected($this->config['stop_on_hover'], 'off'); ?>><?php _e('Disabled', 'gk-tabs'); ?></option>
				</select>
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'anim_speed' ) ); ?>" title="<?php _e('You can specify the animation speed in miliseconds.', 'gk-tabs'); ?>"><?php _e( 'Animation speed (ms):', 'gk-tabs' ); ?></label>
				<input class="gk-small" id="<?php echo esc_attr( $this->get_field_id( 'anim_speed' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'anim_speed' ) ); ?>" type="text" value="<?php echo esc_attr( $this->config['anim_speed'] ); ?>" />
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'anim_interval' ) ); ?>" title="<?php _e('You can specify the interval between slides on auto animation effect.', 'gk-tabs'); ?>"><?php _e( 'Animation interval (ms):', 'gk-tabs' ); ?></label>
				<input class="gk-small" id="<?php echo esc_attr( $this->get_field_id( 'anim_interval' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'anim_interval' ) ); ?>" type="text" value="<?php echo esc_attr( $this->config['anim_interval'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo esc_attr($this->get_field_id('anim_type')); ?>" title="<?php _e('You can select the animation type', 'gk-tabs'); ?>"><?php _e('Animation type:', 'gk-tabs'); ?></label>
				
				<select id="<?php echo esc_attr( $this->get_field_id('anim_type')); ?>" name="<?php echo esc_attr( $this->get_field_name('anim_type')); ?>">
					<option value="opacity"<?php selected($this->config['anim_type'], 'opacity'); ?>><?php _e('Opacity', 'gk-tabs'); ?></option>
					<option value="scale-up"<?php selected($this->config['anim_type'], 'scale-up'); ?>><?php _e('Scale up', 'gk-tabs'); ?></option>
					<option value="scale-down"<?php selected($this->config['anim_type'], 'scale-down'); ?>><?php _e('Scale down', 'gk-tabs'); ?></option>
					<option value="rotate-x"<?php selected($this->config['anim_type'], 'rotate-x'); ?>><?php _e('Rotate X', 'gk-tabs'); ?></option>
					<option value="rotate-y"<?php selected($this->config['anim_type'], 'rotate-y'); ?>><?php _e('Rotate Y', 'gk-tabs'); ?></option>
				</select>
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'amount_of_sidebars' ) ); ?>" title="<?php _e('You can specify how many sidebars will be added by this widget. These sidebars are very useful, because they won\'t be displayed in your theme.', 'gk-tabs'); ?>"><?php _e( 'Amount of sidebars:', 'gk-tabs' ); ?></label>
				<input class="gk-small" id="<?php echo esc_attr( $this->get_field_id( 'amount_of_sidebars' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'amount_of_sidebars' ) ); ?>" type="number" min="0" max="100" value="<?php echo esc_attr( $this->config['amount_of_sidebars'] ); ?>" />
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'cache' ) ); ?>" title="<?php _e('You can enable the widget cache. You can specify the cache time (in minutes) and the content to cache - whole widget or only the generated content', 'gk-tabs'); ?>"><?php _e( 'Cache: ', 'gk-tabs' ); ?></label>
				<select id="<?php echo esc_attr( $this->get_field_id('cache')); ?>" name="<?php echo esc_attr( $this->get_field_name('cache')); ?>">
					<option value="none"<?php selected($this->config['cache'], 'none'); ?>><?php _e('Cache disabled', 'gk-tabs'); ?></option>
					<option value="widget"<?php selected($this->config['cache'], 'widget'); ?>><?php _e('Whole widget', 'gk-tabs'); ?></option>
					<option value="content"<?php selected($this->config['cache'], 'content'); ?>><?php _e('Only tabs content', 'gk-tabs'); ?></option>
				</select>
				<input class="gk-small" id="<?php echo esc_attr( $this->get_field_id( 'cache_time' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'cache_time' ) ); ?>" type="number" min="0" max="10000" value="<?php echo esc_attr( $this->config['cache_time'] ); ?>" />
			</p>
		</div>
	<?php
	}

	/**
	 *
	 * Method used to generate the roman numbers
	 *
	 * @param num - number to change
	 * 
	 * @return roman number based on the input param
	 *
	 **/
	static function roman_number($num) {
    	$n = intval($num);
    	$result = '';
 		// array of the roman base numbers
    	$roman_numerals = array(
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1
        );
 		// divide the base number by roman numbers
	    foreach ($roman_numerals as $roman => $number) {
	        $matches = intval($n / $number);
	        $result .= str_repeat($roman, $matches);
	        $n = $n % $number;
	    }
		// return the new value
	    return $result;
    }
}

// EOF