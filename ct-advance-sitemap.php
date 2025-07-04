<?php 
/*
Plugin Name: CT Advance Sitemap
Description: Advance XML Sitemap with customizable options. For Documentation, Please visit Plugin site.
Plugin URI: https://github.com/gulariav/ct-advance-sitemap
Author: Vishal Gularia
Author URI: https://clicktecs.com/
Requires at least: 4.6.14
Tested up to: 6.2
Version: 2.4.1
License: GPL v2 or later
package ctas
*/



/* 1. Create Main sitemap file, "sitemap.xml". 
 * This file list all sub sitemap files. 
 * Sub site will be automatically generated on the basis of post type enabled in the backend option. 
 * 
 * Example: If post_type "Post" enabled. it's sitemap file generate with name sitemap-post i.e. 
 * sitemap-<post_type>. For Default option i.e. Sitemap by post type will be in this way:
 * sitemap-post.xml have two files, 
 *  a) first, sitemap-latest-posts.xml  
 * 	b) second, sitemap-older-posts.xml
 * 
 * For pages, default main file pages_sitemap.xml.	
 * 
 * For default post type sitemap file of pages, list of all pages in sitemap.
 *
 * If sitemap by taxonomy selected, then sitemap_page.xml having list of selected taxonomy terms. 
 *
 * Similar all post type show in backend options. They also havnng two options:
 *
 * 	a) Sitemap by post type. 
 * 	a) Sitemap by taxonomy. 
 * 
 * All post type have their exclude option.
 * 
*/


/*****************************************************
Plugin Global Options & Settings
******************************************************/


/*
 * Register Custom Taxonomy, "Location" associated with pages. 
 * It might possible this taxonomy already created by location served plugin. 
 * But it not created then create it here on initialize the plugin. 
*/


add_action( 'init', 'ctas_retister_taxonomy', 0 );
 
function ctas_retister_taxonomy() {

	$labels = array(
		"name" 						 => __( "Locations", "ctas" ),
		"singular_name" 			 => __( "Location", "ctas" ),		
		'menu_name'                  => __( 'Locations', 'ctas' ),
		'all_items'                  => __( 'All Locations', 'ctas' ),
		'parent_item'                => __( 'Parent Location', 'ctas' ),
		'parent_item_colon'          => __( 'Parent Location:', 'ctas' ),
		'new_item_name'              => __( 'New Location', 'ctas' ),
		'add_new_item'  			 => __( 'Add New Location', 'ctas' ),
		'edit_item'                  => __( 'Edit Location', 'ctas' ),
		'update_item'                => __( 'Update Location', 'ctas' ),
		'view_item'                  => __( 'View Location', 'ctas' ),
		'separate_items_with_commas' => __( 'Separate Locations with commas', 'ctas' ),
		'add_or_remove_items'        => __( 'Add or remove Locations', 'ctas' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'ctas' ),
		'popular_items'              => __( 'Popular Locations', 'ctas' ),
		'search_items'               => __( 'Search Locations', 'ctas' ),
		'not_found'                  => __( 'Not Found', 'ctas' ),
		'no_terms'                   => __( 'No Locations', 'ctas' ),
		'items_list'                 => __( 'Locations list', 'ctas' ),
		'items_list_navigation'      => __( 'Locations list navigation', 'ctas' ),
	);


	$args = array(
		"label" => __( "Locations", "ctas" ),
		"labels" => $labels,
		"public" => true,
		"publicly_queryable" => false,
		"hierarchical" => true,
		"show_ui" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"query_var" => true,
		"rewrite" => [ 'slug' => 'location', 'with_front' => true, ],
		"show_admin_column" => true,
		"show_in_rest" => false,
		"rest_base" => "location",
		"rest_controller_class" => "WP_REST_Terms_Controller",
		"show_in_quick_edit" => true,
	);

	register_taxonomy( 'location', array( 'page' ), $args );

}



/*
 * Defining Global varaibles, which will use across the sitemap options.  
 * 
 * $ctas_sitemap_dir: hold sitemap dirctory 
 * $master_sitemap_file_url: Master sitemap file URL, default "/sitemap.xml"
 * $ctas_sitemap_dir_uri: Master sitemap Directory Public URL, default "ctas_sitemaps" under home directory.
 * $master_sitemap_file_name: Master sitemap physical filename "sitemap.xml" 
 * 
 * But it not created then create it here on initialize the plugin. 
*/



global 	$ctas_plugin_data, 
		$ctas_sitemap_dir, 
		$master_sitemap_file_url,  
		$ctas_sitemap_dir_uri, 
		$master_sitemap_file_name, 
		$change_freq, 
		$priority;


$ctas_sitemap_dir = ABSPATH  ; 
$ctas_sitemap_dir_uri = home_url('/');


if ( is_multisite() ) { 
	$upload_dir   = wp_upload_dir(); //var_dump($upload_dir);

	$ctas_sitemap_dir = $upload_dir['basedir']. '/ctas_sitemaps/'; //Changed the directory for MU

	$ctas_sitemap_dir_uri = $upload_dir['baseurl'].'/ctas_sitemaps/'; //Changed the URL for MU
}

if ( wp_mkdir_p( $ctas_sitemap_dir ) ) 	{
		//$ctas_sitemap_dir = ABSPATH . 'ctas_sitemaps/' ; 

}
else die('Failed to create directory');


$master_sitemap_file_name = 'sitemap.xml';
$master_sitemap_file_url = $ctas_sitemap_dir_uri.$master_sitemap_file_name;



$change_frequencies = array('always','hourly','daily','weekly','monthly','yearly','never');
$priorities = array('0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0');

/***************** Plugin Global Options & Settings ends *************************/


if ( is_admin() ) {

    if( ! function_exists('get_plugin_data') ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    $ctas_plugin_data =  get_plugin_data( __FILE__ ); 
 
}



//Rewrite Rules
function ctas_sitemap_rewrites() {

	if ( is_multisite() ) { 

		global $wp;
		$wp->add_query_var( 'ctas_sf' );

	    add_rewrite_rule( 'sitemap.xml', 'index.php?ctas_sf=default', 'top' );
	    add_rewrite_rule( 'sitemap-+([a-zA-Z0-9_-]+)?\.xml$', 'index.php?ctas_sf=$matches[1]', 'top' );

	}


}
add_action( 'init', 'ctas_sitemap_rewrites', 99 );



function template_render_sitemap_file_mu( $template ) {

	global $ctas_sitemap_dir, $ctas_sitemap_dir_uri, $master_sitemap_file_name, $wp_query, $post;

	$ctas_sf = get_query_var( 'ctas_sf' );
	$file = '';


	if ( ! empty( $ctas_sf ) && is_multisite() ) {

		if ( 'default' == $ctas_sf ) {
			
			$file = $ctas_sitemap_dir.$master_sitemap_file_name;
		
		} else { 

			$file_path = $ctas_sitemap_dir.'sitemap-'.$ctas_sf.'.xml';

			if ( file_exists( $file_path ) ) {
				$file = $file_path;
			}
			
		}


		if (!empty ($file)) {	
			//echo $file;	
			header( 'Content-type: text/xml' );
			header('Pragma: public');
			header('Cache-control: private');
			//header('Expires: -1');
			readfile($file);
			die();
		}
		else {
			$wp_query->set_404();
	  		status_header( 404 );
	  		get_template_part( 404 ); exit();
	    }


	}
	return $template;
}
add_action( 'template_include', 'template_render_sitemap_file_mu' );




/*add_action( 'template_include', function( $template ) {
    if ( get_query_var( 'ctas_sit' ) == false || get_query_var( 'ctas_sit' ) == '' ) {
        return $template;
    }
 
    return plugin_dir_path( __FILE__ ) . '/ctas_render_file.php';
} );*/


function ctas_prevent_slash_on_map_variable( $redirect ) {
	if ( get_query_var( 'ctas_sf' ) ) {
		return false;
	}
	return $redirect;
}
add_filter( 'redirect_canonical', 'ctas_prevent_slash_on_map_variable' );








/*
 * Add plugin setting page into backend option. 
 * This page will be under Settings Menu, hold all sitemap configuration options.   
*/

add_action( 'admin_menu', 'ctas_settings_page' );

function ctas_settings_page() {
	add_options_page( 'CT Advance Sitemap', 'CT Advance Sitemap', 'manage_options', 'ctas_settings', 'ctas_render_plugin_settings_page' );
}




/*
 * Enqueue the scipts used by plugin for the backend, for plugin setting page only. 
 * No scripts will be used on frontend.    
*/
add_action( 'admin_enqueue_scripts', 'ctas_scripts', 99 );

function ctas_scripts( $page ) {

	if($page != 'settings_page_ctas_settings') 
		return;

	// Register the Javascript, dependency with jquery.
	wp_enqueue_script( 'ctas', plugin_dir_url( __FILE__ ) . 'js/scpe.js', array('jquery'), '', true );

	wp_enqueue_style('ctas', plugin_dir_url( __FILE__ ) . 'css/ctas_style.css');

}



/*****************************************************
Sitemap Options Started
******************************************************/




/*
 * Render Plugin Settings Page & its options. 
 * This is the default first function of Plugin. 
*/


function ctas_render_plugin_settings_page() 
{

	//ctas_create_sitemap(); // Enable to Create/update sitemap while open this page. 

	global $ctas_sitemap_dir; global $ctas_sitemap_dir_uri; global $change_frequencies; global $priorities; global $ctas_plugin_data;



	if( isset( $_POST['ctas_form'] ) && isset ($_POST['updated']) && $_POST['updated'] === 'true' ) {

		//Save the options
		handle_form();
		
	} 


	if( isset( $_POST['generate_sitemap_btn'] ) )  {
		

		/*
		Check if this a multisite network. 
		If yes, delete all the files in microsite folder. 

		Additional parameters to enable to a pertifular microsite && get_current_blog_id() == 52 
		*/
		if(  is_multisite() && ! is_main_site() ) {
			$files = glob($ctas_sitemap_dir.'*'); // get all file names 


			// Delete all the files in sitemap directory.

			if ( $files ) : 
				foreach($files as $file) : // iterate files
				  	if(is_file($file)) {
				    	//echo $file; 
				    	unlink($file); // delete file
				  	}
				endforeach; 
			endif;
		}


		//Now, Generate the Sitemap. 
		ctas_create_sitemap();
		flush_rewrite_rules();

	}

	if( isset ($_POST['copy_cities_to_locations']) ) {

		# This will copy all the locations create by Location served plugin 
		# in posttype "uprodudcts" to location under pages.
		copy_cities_to_locations();
		
	} 

	if( isset ($_POST['auto_update_products']) ) {

		//update product pages programatically. 
		auto_update_products();
		
	} 


	if( isset ($_POST['update_post_time']) ) {

		//Update post modified time.
		auto_update_posts_modified_time();
		
	} 
	



	//Get all existing sitemap configuration/options & hold into variable.
	$existing_ctas_options = get_option('ctas_generate_sitemap');

	//echo '<pre>'; print_r($existing_ctas_options); echo '</pre>'; //Enable to test.


	//Get all post registered & public post types.
	$args = array(
		'public'   => true
	);

	$post_types = get_post_types($args, 'objects'); //var_dump($post_types); 
	?>


	<!--------------------------------------------------
	Render the Settings Page
	---------------------------------------------------->    


	<div id="ctas-wrapper" class="">

	 	<h2 class="title">Advance XML Sitemap Generator <span class="ctas-version">v<?php echo $ctas_plugin_data['Version']; ?></span></h2>
	 	

	 	<div id="ctas-post-body-content">

	 		<form method="POST">

	 			<input type="hidden" name="updated" value="true" />
	 			<?php wp_nonce_field( 'ctas_update', 'ctas_form' ); ?>

	 			<div id="basic-sitemap-options" class="ctas-postbox" style="display: none;">

	 				<h3 class="title">Sitemap Options</h3>

	 				<div class="inside">


	 					<?php 
	 					settings_fields( 'dbi_example_plugin_options' );
	 					do_settings_sections( 'dbi_example_plugin' ); ?>
	 					<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
	 					
	 				</div>
	 			</div> <!-- #basic-sitemap-options .ctas-postbox -->

	 			<?php foreach($post_types as $post_type): 


	 				# Skip Media/Attachment WP default post type.
					if ( $post_type->name == 'attachment') continue; 

	 				$post_type_name = $post_type->name;
	 				$post_type_label = $post_type->label;

	 				$curr_freq =''; $curr_priority = '';
	 				$curr_freq = $existing_ctas_options[$post_type_name]['change_frequency'];
	 				$curr_priority = $existing_ctas_options[$post_type_name]['priority'];

	 				?>

	 				<div id="<?php echo $post_type_name; ?>_sitemap" class="ctas-postbox">

	 					<h3 class="title"><?php echo $post_type_label; ?> Sitemap</h3>

	 					<div class="inside">


	 						<table>
	 							<tr>
	 								<td class="heading-col">
	 									<h4>Generate Sitemap By:</h4>
	 								</td>
	 								<td>
	 									
	 									<span class="radio-btn-span"> 

	 										<?php $default_checked = 
	 										($existing_ctas_options[$post_type_name]['sitemap_by'] == 'default') ? 'checked ="checked"' : ''; ?>

	 										<input type="radio" name="generate_sitemap[<?php echo $post_type_name; ?>][sitemap_by]" value="default" <?php echo $default_checked; ?>> Post Type (Default) 
	 									</span>




	 									<?php 
	 									$taxonomies = get_object_taxonomies( "$post_type_name", 'objects' );
														  //print_r($taxonomies);

	 									if ($taxonomies) {
																//echo '<span class="radio-btn-span"> <input type="radio" name="sitemap_by[$post_type]"> Post Taxonomies </span>';
																//echo '<span class="radio-btn-span">  Post Taxonomies </span>';

	 										
	 										echo '<div class="taxonomies-list">';
	 										foreach ($taxonomies as $taxonomy) {
																	 //echo '<div>' . $taxonomy . '</div>';

	 											$this_tax_sel = ($existing_ctas_options[$post_type_name]['sitemap_by'] == $taxonomy->name) 
	 											? 'checked ="checked"' : '';

	 											echo '<span class="radio-btn-span"> 
	 											<input type="radio" name="generate_sitemap['.$post_type_name.'][sitemap_by]" 
	 											value='.$taxonomy->name.' '.$this_tax_sel.'> '. ucwords($taxonomy->label);

	 											echo '</span>';
	 										}

	 										echo '</div>';

	 									}
														  //else echo 'No Tax found';
	 									?> 


	 									<span class="radio-btn-span"> 

	 										<?php $null_checked = 
	 										($existing_ctas_options[$post_type_name]['sitemap_by'] == '') ? 'checked ="checked"' : ''; ?>

	 										<input type="radio" name="generate_sitemap[<?php echo $post_type_name; ?>][sitemap_by]" value="" <?php echo $null_checked; ?>> Disable (Don't Create Sitemap)  
	 									</span>


	 								</td>
	 							</tr>

	 							<tr>
	 								<td>
	 									<h4>
	 										Exclude <?php echo $post_type_label; ?> 
	 										by 
	 										<?php echo $post_type->labels->singular_name; ?> ID
	 									</h4>
	 								</td>
	 								<td>

	 									<input type="text" name="generate_sitemap[<?php echo $post_type_name;?>][excluded_posts_id]" class="input-big" value="<?php echo $existing_ctas_options[$post_type_name]['excluded_posts_id']; ?>" />

	 									<div>
	 										<small class="help-tip"> Use Comma separated numeric ID's. Work for all options.</small>
	 										<br>
	 										<small class="help-tip">
	 											<?= ($post_type_label=='Pages' ? 
	 											'Home Page already excluded here, as it goes to separate sitemap file.' : '');
	 											?>
	 										</small>
	 									</div>

	 								</td>
	 							</tr>


	 							<?php if ($taxonomies) : foreach ($taxonomies as $taxonomy) :  ?>   

	 								<tr>
	 									<td><h4>Exclude <?php echo $taxonomy->label; ?> by <?php echo $taxonomy->labels->singular_name; ?> ID </h4></td>
	 									<td>

	 										<?php $this_tax_sel = ($existing_ctas_options[$post_type_name]['sitemap_by'] == $taxonomy->name) 
	 										? 'checked ="checked"' : ''; ?>

	 										<input type="text" 
	 										name="generate_sitemap[<?php echo $post_type_name;?>][excluded_terms_id][<?php echo $taxonomy->name; ?>]" 
	 										value="<?php echo $existing_ctas_options[$post_type_name]['excluded_terms_id'][$taxonomy->name]; ?> " />

	 										<div><small class="help-tip"> Use Comma separated numeric ID's. Works Only if sitemap generated by <?php echo $taxonomy->label; ?> selected</small></div>
	 									</td>
	 								</tr>

	 							<?php endforeach; endif; ?>

	 							<tr>
	 								<td><h4>Change Frequency</h4></td>
	 								<td>

	 									<select name="generate_sitemap[<?php echo $post_type_name;?>][change_frequency]">
	 										<?php $sel = 'selected="Selected"'; ?>
	 											<option value="always" <?php echo ($curr_freq == 'always' ? $sel : ''); ?>>Always</option>
		 										<option value="hourly" <?php echo ($curr_freq == 'hourly' ? $sel : ''); ?>>Hourly</option>
		 										<option value="daily" <?php echo ($curr_freq == 'daily' ? $sel : ''); ?>>Daily</option>
		 										<option value="weekly" <?php echo ($curr_freq == 'weekly' ? $sel : ''); ?>>Weekly</option>
		 										<option value="monthly" <?php echo ($curr_freq == 'monthly' ? $sel : ''); ?>>Monthly</option>
		 										<option value="yearly" <?php echo ($curr_freq == 'yearly' ? $sel : ''); ?>>Yearly</option>
		 										<option value="never" <?php echo ($curr_freq == 'never' ? $sel : ''); ?>>Never</option>
	 										
	 									</select>

	 									<div><small class="help-tip"></small></div>

	 								</td>
	 							</tr>

	 							<tr>
	 								<td><h4>Priority</h4></td>
	 								<td>

	 									<select name="generate_sitemap[<?php echo $post_type_name;?>][priority]">
	 										<?php $sel = 'selected="Selected"'; 
	 											foreach ($priorities as $priority): 
	 										?>
	 											<option value="<?php echo $priority; ?>" 
	 												<?php echo ($curr_priority == $priority ? $sel : ''); ?> >
	 												<?php echo $priority; ?>
	 											</option>
	 											
	 										<?php endforeach; ?>
	 									</select>

	 									<div><small class="help-tip"></small></div>

	 								</td>
	 							</tr>

	 							<tr>
	 								<td><h4>Enable Auto Update Time</h4></td>
	 								<td>

	 									<?php 
										$curr_auto_update_val = @$existing_ctas_options[$post_type_name]['enable_auto_update'];
										$check = 'checked="checked"';

										?>

										<input type="checkbox" name="generate_sitemap[<?php echo $post_type_name;?>][enable_auto_update]" class="input-big" value="yes" 
										<?php echo ( $curr_auto_update_val == 'yes' ? $check : '') ; ?>
										 />

	 									<div><small class="help-tip">If checked, <?php echo $post_type_label; ?> Modified Time will be auto updated at 00:00 hours.</small></div>

	 								</td>
	 							</tr>


	 							<tr>
	 								<td></td>
	 								<td>


	 									<div class="submit-buttons">
	 										<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Update' ); ?>" />
	 										<!-- <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Update All' ); ?>" /> -->
	 									</div>

	 								</td>
	 							</tr>


	 						</table>

	 					</div> <!-- .inside -->
	 				</div> <!--  .ctas-postbox -->

	 			<?php endforeach; ?>

	 		</form>
	 	</div>

	 	<div id="inner-sidebar">
	 		
	 		<div id="generate-sitemap-options" class="ctas-postbox" style="">
	 			<form method="POST">

	 				<h3 class="title">Generate Sitemap </h3>

	 				<div class="inside">

	 					<?php 

	 					global $master_sitemap_file_url, $master_sitemap_file_name; // = home_url('/sitemap.xml');

	 					$sitemap_link = home_url('/').$master_sitemap_file_name;

	 					echo '<p><strong>Master Sitemap URL: </strong>';
	 					echo '<a href="'.$sitemap_link.'" target="_blank">'.$sitemap_link.'</a>';
	 					echo '</p>';

	 					?>

	 					<div class="submit-buttons">
							<input name="generate_sitemap_btn" class="button button-primary ctas-large-btn" type="submit" value="<?php esc_attr_e( 'Generate Sitemap' ); ?>" />
							<!-- <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Update All' ); ?>" /> -->
						</div>

	 				</div>
	 			</form>
 			</div> <!-- #generate-sitemap-options -->

 			<div id="sitemap-plugin-stats" class="ctas-postbox" style="">
	 			<form method="POST">

	 				<h3 class="title">Sitemap Plugin Stats </h3>

	 				<div class="inside">

	 					<?php 

	 					global $ctas_sitemap_dir,  $ctas_sitemap_dir_uri, $master_sitemap_file_name, $master_sitemap_file_url;

	 					$ctas_sitemap_generated_time = get_option('ctas_sitemap_generated_time');

	 					if ( $ctas_sitemap_generated_time )
 							echo '<p><strong>Sitemap Generated on: </strong><br/>' . $ctas_sitemap_generated_time.'</p>';

	 					//echo '<p><strong>Master Sitemap URL: </strong><br/>' . $master_sitemap_file_url.'</p>';						

	 					echo '<p><strong>Sitemap Directory Absolute Path: </strong><br/>' . $ctas_sitemap_dir.'</p>';

	 					//echo '<p><strong>Sitemap Directory URL: </strong><br/>' . $ctas_sitemap_dir_uri.'</p>';

	 					echo '<p><strong>Master Sitemap Filename: </strong><br/>' . $master_sitemap_file_name.'</p>';

	 					?>

	 				</div>
	 			</form>
 			</div> <!-- #sitemap-plugin-stats -->

 			<?php 
 			//plugin is activated
 			if ( is_plugin_active( 'ak-location-served/ak-location.php' )  ) : ?>

	 			<div id="copy-cities-options" class="ctas-postbox" style="">
		 			<form method="POST">

		 				<h3 class="title">Copy Cities to Location </h3>

		 				<div class="inside">

		
		 					<p>All the cities in Products section copied to locations in pages.</p>

		 					<p><strong>Warning:</strong> If location already exist then it will not create a duplicate/new location.</p>
		
		 					<div class="submit-buttons">
								<input name="copy_cities_to_locations" class="button button-primary ctas-large-btn" type="submit" value="<?php esc_attr_e( 'Copy Cities to Locations' ); ?>" />
								
							</div>

		 				</div>
		 			</form>
	 			</div> <!-- #copy-cities-options -->

	 			<div id="auto-update-products-options" class="ctas-postbox" style="">
		 			<form method="POST">

		 				<h3 class="title">Auto update all products.</h3>

		 				<div class="inside">

		
		 					<p>This will add location to pages. Run if after copying all the cities to locations.</p>

		 					<p><strong>Warning:</strong> All product pages update automatically. This will use functions inherited from AK Location Served to execute the hooks only, no other information will modify. This feature works only when AK Location served plugin installed and activated. </p>

		 					<p><strong>Limit no. of Product pages:</strong>
			 					<input type="text" name="update_limit" class="input-big" value="" />
			 					<small>Limit no of pages to update, so that server will not overload and throw error. Use it with offset carefully. Default: -1 (All Pages)</small>
		 					</p>

		 					<p><strong>Offset:</strong>
			 					<input type="text" name="offset_posts" class="input-big" value="" />
			 					<small>Offet posts i.e. skip the update counter by no of posts already updated or ignore. Default: 0.</small>
		 					</p>

		 					<p><strong>Post Type:</strong>
			 					<select name="auto_update_posttype" class="input-big au_posttype" style="clear: both; display: block;">
			 						<?php foreach($post_types as $au_post_type): ?>
			 							<option value="<?php echo $au_post_type->name; ?>"><?php echo $au_post_type->label  .' (' . $au_post_type->name .')'; ?></option>
			 						<?php endforeach; ?>
			 					</select>
			 					<small>Select Post type to auto update.</small>
		 					</p>


		 					
		
		 					<div class="submit-buttons">
								<input name="auto_update_products" class="button button-primary ctas-large-btn" type="submit" value="<?php esc_attr_e( 'Auto Update Products' ); ?>" />
								
							</div>

		 				</div>
		 			</form>
	 			</div> <!-- #auto-update-products-options -->

 			<?php endif; ?>

 			<div id="update-pt-cpt-time-options" class="ctas-postbox" style="display: none;">
	 			<form method="POST">

	 				<h3 class="title">Update Post/Pages/CPT Time.</h3>

	 				<div class="inside">

	
	 					<p>Update modified time of PT/CPT to today.</p>

	 					<!-- <p><strong>Warning:</strong> </p> -->
	
	 					<div class="submit-buttons">
							<input name="update_post_time" class="button button-primary ctas-large-btn" type="submit" value="<?php esc_attr_e( 'Update Post Modified Time' ); ?>" />
							
						</div>

	 				</div>
	 			</form>
 			</div> <!-- #update-pt-cpt-time-options -->

	 	</div>

	 	

	 </div>

	<!---------------------------------------
	 	Rendering Completed
 	---------------------------------------->

	 <?php
	}





	function handle_form() {
		if(  ! isset( $_POST['ctas_form'] ) || ! wp_verify_nonce( $_POST['ctas_form'], 'ctas_update' ) )  { 

			echo '<div class="error">
			<p>Sorry, your nonce was not correct. Please try again.</p>
			</div>';

			exit;
		} 
		else 
		{
		  // Save Form data to database.

		  //echo '<pre>'; print_r($_POST); echo '</pre>'; //Enable to test $_POST global variable

		  $generate_sitemap = $_POST['generate_sitemap']; 

		  if($generate_sitemap) { 

		  	if( update_option( 'ctas_generate_sitemap', $generate_sitemap, false ) )         
		  		echo '<div class="updated success-msg"><p>Options Updated.</p></div>';

		  } else { 

		  	echo '<div class="error failure-msg"> <p>Your username or email were invalid.</p> </div>';
		  }


		}
	}



#Diabled these options due to large sitemap files. If it will create on every update the server will timeout frequently.

//add_action( 'publish_post', 'ctas_create_sitemap' );
//add_action( 'publish_page', 'ctas_create_sitemap' );
//add_action( 'save_post', 'ctas_create_sitemap' );


/*
 * Create the sitemap using the saved options only. 
*/	

function ctas_create_sitemap() {

	$ctas_options = get_option('ctas_generate_sitemap');

	//echo '<pre>'; print_r($ctas_options); echo '</pre>'; //Enable to test $_POST global variable

	 //Generate the sitemap files

	//$ctas_sitemap_dir = ABSPATH ; 
	global $ctas_sitemap_dir,   $ctas_sitemap_dir_uri, $master_sitemap_file_name;
	
	

	$error = '';

	

	/* 
	 * Create Master sitemap file.
	*/
	/*$master_sitemap_file_name = 'sitemap.xml';
	$ctas_sitemap_dir_uri = home_url('/ctas_sitemaps/');*/



	$master_sitemap_fcon = '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
	$master_sitemap_fcon .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'. "\n";


	// Create misc sitemap for home page then add it to master file
	if( create_misc_sitemap() )
	{
		$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
		"\t\t" . '<loc>' . home_url('/')  . 'sitemap-misc.xml'.'</loc>' .
		"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
		"\n\t" . '</sitemap>' . "\n";
	
	}



	foreach ($ctas_options as $ct_post_type => $post_type_details) 
	{

		//echo '<pre>'; print_r($ct_post_type);  print_r($post_type_details); echo '</pre>';


		# Get post type details, labels public name etc. 
		$curr_post_type_obj = get_post_type_object( $ct_post_type );

		$curr_freq = $ctas_options[$ct_post_type]['change_frequency'];
		$curr_priority = $ctas_options[$ct_post_type]['priority'];


		$excluded_posts_id = array();

		$front_page_id = get_option('page_on_front');

		if($front_page_id && $ct_post_type == 'page') {
			$excluded_posts_id[] = $front_page_id;
		}


		// 1. Get excluded posts from options
		$excl_posts_sitemap_by_default = $ctas_options[$ct_post_type]['excluded_posts_id'];

		$excl_posts = explode(',' ,$excl_posts_sitemap_by_default); //var_dump($excl_posts);


		foreach($excl_posts as $post_id )
		{	
			$post_id = trim($post_id);
			if( !empty($post_id) ) $excluded_posts_id[] = $post_id;
		}

		// 2. Get posts marked noindex by Yoast
		$yoast_noindex_ids = get_yoast_noindex_post_ids($ct_post_type); // Pass post type

		// 3. Merge and remove duplicates
		$excluded_posts_id = array_unique(array_merge($excluded_posts_id, $yoast_noindex_ids));

		

		

		//Create Sitemap File for default posts,
		if( $ct_post_type == 'post' && $post_type_details['sitemap_by'] == 'default')
		{

			

			/*
			 * Get all posts modified in last six months. Create a file with it. 	
			*/

			$args = array(
					'numberposts' => -1,
					  'post_type'  => array( 'post' ), //array( 'post', 'page' ),
					  'order'    => 'DESC',
					  'post_status' => 'publish',
					  'exclude'	 => $excluded_posts_id,
					  'date_query' => array(
							/*array(
								 'column' => 'post_date_gmt',
								 'before' => '1 year ago',
								),*/
								array(
									'column' => 'post_modified_gmt',
									'after'  => date('Y-m-d', strtotime('-180 days'))
								),
							)	
					);

			$last_six_months_posts = get_posts( $args ) ;

			//echo '<pre>'; print_r($last_six_months_posts); echo '</pre>'; //Enable to test $_POST global variable

			$file_name = 'sitemap-latest-posts.xml';

			if($last_six_months_posts) { 

				if( create_sitemap_file($last_six_months_posts, $file_name, $curr_freq, $curr_priority) ) {

					$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
					"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
					"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) )  . '</lastmod>' .
					"\n\t" . '</sitemap>' . "\n";

				}
				else { $error = 'Failed to create '.$file_name; }

			}
			/*
			 * Get all posts modified before last six months, which are not included in above file. Create a file with it. 	
			*/
			$before_six_months_posts = get_posts(array(
				'numberposts' => -1,
				  'post_type'  => array( 'post' ), //array( 'post', 'page' ),
				  'order'    => 'DESC',
				  'exclude'	 => $excluded_posts_id,
				  'date_query' => array(
						/*array(
							 'column' => 'post_date_gmt',
							 'before' => '1 year ago',
							),*/
							array(
								'column' => 'post_modified_gmt',
								'before'  => '6 month ago',
							),
						)
				));

			//echo '<pre>'; print_r($last_six_months_posts); echo '</pre>'; //Enable to test $_POST global variable

			$file_name = 'sitemap-older-posts.xml';

			if($before_six_months_posts) {

				if( create_sitemap_file($before_six_months_posts, $file_name, $curr_freq, $curr_priority) ) {
					$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
					"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
					"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
					"\n\t" . '</sitemap>' . "\n";
				}
				else { $error = 'Failed to create '.$file_name; }
			}


		} //$ct_post_type == 'post' && $post_type_details['sitemap_by'] == 'default'

		/*
		 * Create Sitemap with all posts in one file, order by date updated.
		 * 
		*/

		else if( $ct_post_type != '' && $post_type_details['sitemap_by'] == 'default')
		{

			$total_posts = wp_count_posts($ct_post_type); //var_dump($total_posts->publish); //exit;

			if($total_posts->publish > 1000 ) 
			{
				$sitemap_part = 1;
				$i=$total_posts->publish;
				$offset_posts = 0;
				while($i>=0) 
				{

					$fetch_posts = get_posts(array(
						'numberposts' => 1000,
						'offset'      => $offset_posts,
						'exclude'	 => $excluded_posts_id,
						  'post_type'  => array( $ct_post_type ), //array( 'post', 'page' ),
						  'order'    => 'DESC',
					));

					$post_type_name =$curr_post_type_obj->label;

					$file_name = 'sitemap-'. $post_type_name.'-part-'.$sitemap_part;
					$file_name = sanitize_title($file_name).'.xml';

					if( create_sitemap_file($fetch_posts, $file_name,$curr_freq, $curr_priority) ) {
						$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
						"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
						"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
						"\n\t" . '</sitemap>' . "\n";
					}
					else { $error = 'Failed to create '.$file_name; }

					$i = $i-1000;
					$offset_posts = $offset_posts + 1000;
					$sitemap_part++;
				}

			} else {
				$fetch_posts = get_posts(array(
					'numberposts' => -1,
					'exclude'	 => $excluded_posts_id,
					  'post_type'  => array( $ct_post_type ), //array( 'post', 'page' ),
					  'order'    => 'DESC',
				));

				$post_type_name =$curr_post_type_obj->label;

				$file_name = 'sitemap-'. $post_type_name;
				$file_name = sanitize_title($file_name).'.xml';

				if( create_sitemap_file($fetch_posts, $file_name,$curr_freq, $curr_priority) ) {
					$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
					"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
					"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
					"\n\t" . '</sitemap>' . "\n";
				}
				else { $error = 'Failed to create '.$file_name; }
			} 

			

		}	
		
		else if ($post_type_details['sitemap_by'] != 'default' && $post_type_details['sitemap_by'] != '')
		{

			//Create sitemap by taxonomy
			$sitemap_by_tax = $post_type_details['sitemap_by'];

			$this_tax_detail = get_taxonomy( $sitemap_by_tax ); 
			$this_tax_name = $this_tax_detail->labels->singular_name;



			$excl_terms_from_sitemap = $ctas_options[$ct_post_type]['excluded_terms_id'][$sitemap_by_tax];

			$excl_terms = explode(',' ,$excl_terms_from_sitemap); //var_dump($excl_posts);

			$excl_terms_id = array();

			foreach($excl_terms as $term_id )
			{	
				$term_id = trim($term_id);
				if( !empty($term_id) ) $excl_terms_id[] = $term_id;
			}

			

			$args = array( 
			    'taxonomy' => $sitemap_by_tax,
			    'exclude'	 => $excl_terms_id,
			    'hide_empty' => true
			);

			$terms = get_terms($args);

			//echo '<pre>'; print_r($terms); echo '</pre>'; //Enable to test


			$post_type_name =$curr_post_type_obj->label;


			//Create sitemap of all posts found in terms
			foreach ( $terms as $term ) {
		        //echo '<li>' . $term->name . '</li>';


				//If the current term excluded from sitemap, break the loop, continue to next. Already excluded above but double check. 
		        if( in_array($term->term_id, $excl_terms_id) ) break;

		        $args = array(
		        	'post_type' => $ct_post_type,
    				'post_status' => 'publish',
    				'exclude'	 => $excluded_posts_id,
    				'numberposts' => -1,
				    'tax_query' => array(
				        array(
				            'taxonomy' => $sitemap_by_tax,
				            'field'    => 'term_taxonomy_id',
				            'terms'    => $term->term_id
				        )
				    )
				); //echo '<pre>'; var_dump( $args ); echo '</pre>'; //Enable to test


		        $postslist = get_posts($args); //echo '<pre>'; var_dump( $postslist ); echo '</pre>'; //Enable to test

				
				//$file_name = 'sitemap-'. $post_type_name .'-'.$this_tax_name.'-'.$term->slug; //Changed name without post type

				$file_name = 'sitemap-'. $this_tax_name.'-'.$term->slug;
				$file_name = sanitize_title($file_name).'.xml';

				if( create_sitemap_file($postslist, $file_name, $curr_freq, $curr_priority) ) 
				{
					
					$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
					"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
					"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
					"\n\t" . '</sitemap>' . "\n";

				}
				else { $error = 'Failed to create '.$file_name; }
				wp_reset_postdata();
		    }



		    //Now, get all posts without any terms marked i.e. all posts not included in the terms sitemaps. 

		    $args = array( 
			    'post_type' => $ct_post_type,
			    //'posts_per_page' => 10,
				'post_status' => 'publish',
				'exclude'	 => $excluded_posts_id,
				'numberposts' => -1,
			    'tax_query' => array(
			        array(
			            'taxonomy' => $sitemap_by_tax,
			            'terms'    => get_terms( $sitemap_by_tax, [ 'fields' => 'ids'  ] ),
			            'operator' => 'NOT IN'
			        )
			    )
			);

			$posts_uncategorized = get_posts($args);
			//echo '<pre>'; var_dump( $posts_uncategorized ); echo '</pre>'; //Enable to test

			$total_uncategorized_posts = count($posts_uncategorized); //var_dump($total_uncategorized_posts); exit;

			if($total_uncategorized_posts > 1000 ) 
			{
				$sitemap_part = 1;
				$i=$total_uncategorized_posts;
				$offset_posts = 0;
				while($i>=0) 
				{
					$fetch_posts = get_posts(array(
						'posts_per_page' => 2000,
						'offset'      => $offset_posts,
						'exclude'	 => $excluded_posts_id,
					  	'post_type'  => array( $ct_post_type ), //array( 'post', 'page' ),
					  	'order'    => 'DESC',
					));

					$file_name = 'sitemap-'. $post_type_name.'-part-'.$sitemap_part;
					$file_name = sanitize_title($file_name).'.xml';

					if( create_sitemap_file($fetch_posts, $file_name,$curr_freq, $curr_priority) ) {
						$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
						"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
						"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
						"\n\t" . '</sitemap>' . "\n";
					}
					else { $error = 'Failed to create '.$file_name; }

					$i = $i-2000;
					$offset_posts = $offset_posts + 2000;
					$sitemap_part++;
				}
				wp_reset_postdata();

			} else {

				$file_name = 'sitemap-main-'. $post_type_name;
				$file_name = sanitize_title($file_name).'.xml';

				if( create_sitemap_file($posts_uncategorized, $file_name, $curr_freq, $curr_priority) ) 
				{
					
					$master_sitemap_fcon .= "\t" . '<sitemap>' . "\n" .
					"\t\t" . '<loc>' . esc_url( home_url('/')  ) . $file_name.'</loc>' .
					"\n\t\t" . '<lastmod>' . date( "c", current_time( 'timestamp', 0 ) ) .  '</lastmod>' .
					"\n\t" . '</sitemap>' . "\n";

				}
				else { $error = 'Failed to create '.$file_name; }
				wp_reset_postdata();

			}
			


		}
		else 
		{
			// Fallback, If sitemap by not selected for any post type. 
			/*echo '<div class="ctas-error-notice error">';
			echo '<p>Sitemap Not Created for '.$curr_post_type_obj->label .' </p>';
			echo '</div>';*/
		}


		/*
		* Now $excluded_posts_id contains all excluded post IDs, including Yoast noindex. Save it back to DB. 
		*/

		// Convert to comma-separated string
		$excluded_posts_str = implode(',', $excluded_posts_id);

		// Update the options array
		$ctas_options[$ct_post_type]['excluded_posts_id'] = $excluded_posts_str;

		        
	} //end foreach loop

	
	// Save the updated options back to the database
	update_option('ctas_generate_sitemap', $ctas_options);


	$master_sitemap_fcon .= '</sitemapindex>';

	$fp = fopen( $ctas_sitemap_dir . $master_sitemap_file_name , 'w' );

	$result = fwrite( $fp, $master_sitemap_fcon );
	fclose( $fp );

	if ( $result ) {

		$file_link = $ctas_sitemap_dir_uri . $master_sitemap_file_name; 

		$mu_file_link = home_url('/') . $master_sitemap_file_name;


		if ( is_multisite() ) {
			echo '<div class="updated success-msg"><p><strong>Master Sitemap Updated: <a href="'.$mu_file_link.'" target="_blank">
			'.$mu_file_link.'</a></strong></p></div>'; 
		} else {
			echo '<div class="updated success-msg"><p><strong>Master Sitemap Updated: <a href="'.$file_link.'" target="_blank">
			'.$file_link.'</a></strong></p></div>'; 
		}

		update_option( 'ctas_sitemap_generated_time', current_time('mysql'), false );
	}
		
} 



function create_sitemap_file($msf_posts_array, $msf_file_name, $curr_freq, $curr_priority)
{
	
	global $ctas_sitemap_dir; //$ctas_sitemap_dir = ABSPATH  ;  

	global $ctas_sitemap_dir_uri; // = home_url('/');

	/*
	Check if file exist. 
	In case location taxonomy enabled for two post types for example, 
	then append the same xml file for second post type

	Additional parameters to enable to a pertifular microsite && get_current_blog_id() == 52 
	*/

	if(  is_multisite()  && file_exists($ctas_sitemap_dir . $msf_file_name) && strpos($msf_file_name, '-location-') ) {
		

		//echo '<pre>Catch'.$ctas_sitemap_dir . $msf_file_name; echo '<br/>'; //Print Filename

		$xml = simplexml_load_file($ctas_sitemap_dir . $msf_file_name); //var_dump($xml);

		foreach( $msf_posts_array as $post ) {
			setup_postdata( $post );

			//echo '<pre>'; print_r($post); echo '</pre>'; //Enable to test $_POST global variable

			$postdate = explode( " ", $post->post_modified ); //var_dump($postdate);

			$formatted_date = date('c', strtotime($postdate[0] . '' . $postdate[1]) );

			//$sitemap .= "\t" . '<url>'. "\n". 
			$sitemap ="\t\t" . '<loc>' . get_permalink( $post->ID ) . '</loc>' .
			"\n\t\t" . '<lastmod>' .$formatted_date . '</lastmod>' .
			"\n\t\t" . '<changefreq>'.$curr_freq.'</changefreq>' .
			"\n\t\t" . '<priority>'.$curr_priority.'</priority>' ;
			//"\n\t" . '</url>' . "\n";

			$node = $xml->addChild("url"); //var_dump($node); //exit;
			$node->addChild('loc', get_permalink( $post->ID ) );
			$node->addChild('lastmod', $formatted_date );
			$node->addChild('changefreq', $curr_freq );
			$node->addChild('priority', $curr_priority );
		}

		//echo '<br/><br/>';
		//echo $xml->asXML();
		$res = $xml->asXML($ctas_sitemap_dir . $msf_file_name);

		return true; exit;
	}
	
	$sitemap = '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
	$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'. "\n";

	/*$sitemap .= "\t" . '<url>' . "\n" .
	"\t\t" . '<loc>' . esc_url( home_url( '/' ) ) . '</loc>' .
	"\n\t\t" . '<lastmod>' . date( "Y-m-d\TH:i:s", current_time( 'timestamp', 0 ) ) . $tempo . '</lastmod>' .
	"\n\t\t" . '<changefreq>daily</changefreq>' .
	"\n\t\t" . '<priority>1.0</priority>' .
	"\n\t" . '</url>' . "\n";*/



	foreach( $msf_posts_array as $post ) {
		setup_postdata( $post );

		//echo '<pre>'; print_r($post); echo '</pre>'; //Enable to test $_POST global variable

		$postdate = explode( " ", $post->post_modified ); //var_dump($postdate);

		$formatted_date = date('c', strtotime($postdate[0] . '' . $postdate[1]) );

		$sitemap .= "\t" . '<url>'. "\n". 
		"\t\t" . '<loc>' . get_permalink( $post->ID ) . '</loc>' .
		"\n\t\t" . '<lastmod>' .$formatted_date . '</lastmod>' .
		"\n\t\t" . '<changefreq>'.$curr_freq.'</changefreq>' .
		"\n\t\t" . '<priority>'.$curr_priority.'</priority>' .
		"\n\t" . '</url>' . "\n";
	}

	$sitemap .= '</urlset>';

	 //echo $sitemap;

	// $msf_file_name = 'ctas_sitemap_latest_posts.xml';

	$fp = fopen( $ctas_sitemap_dir . $msf_file_name , 'w' );

	$result = fwrite( $fp, $sitemap );
	fclose( $fp );

	if ( $result ) {
		unset($sitemap);

		$file_link = $ctas_sitemap_dir_uri . $msf_file_name;

		$mu_file_link = home_url('/') . $msf_file_name;


		if ( is_multisite() ) {
			echo '<div class="updated success-msg"><p>Sitemap Created: <a href="'.$mu_file_link.'" target="_blank">'.$mu_file_link.'</a></p></div>'; 
		} else {
			echo '<div class="updated success-msg"><p>Sitemap Created: <a href="'.$file_link.'" target="_blank">'.$file_link.'</a></p></div>'; 
		}


		return true;
	}
	else {
		return false;
	}
}


function create_misc_sitemap()
{

	global $ctas_sitemap_dir,  $ctas_sitemap_dir_uri;

	$curr_time = date( 'c', current_time( 'timestamp', 0 ) );

	$sitemap = '<?xml version="1.0" encoding="UTF-8"?>'. "\n";
	$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'. "\n";
	
	$sitemap .= "\t" . '<url>'. "\n". 
				"\t\t" . '<loc>' . home_url('/') . '</loc>' .
				"\n\t\t" . '<lastmod>' . $curr_time . '</lastmod>' .
				"\n\t\t" . '<changefreq>daily</changefreq>' .
				"\n\t\t" . '<priority>1.0</priority>' .
				"\n\t" . '</url>' . "\n";

	$sitemap .= '</urlset>';
	
	$file_name = 'sitemap-misc.xml';

	$fp = fopen( $ctas_sitemap_dir . $file_name , 'w' );

	$result = fwrite( $fp, $sitemap );
	
	fclose( $fp );

	if ( $result ) {
		unset($sitemap);

		$file_link = $ctas_sitemap_dir_uri . $file_name;

		$mu_file_link = home_url('/') . $file_name;

		if ( is_multisite() ) {
			echo '<div class="updated success-msg"><p>Misc Sitemap Created: <a href="'.$mu_file_link.'" target="_blank">'.$mu_file_link.'</a></p></div>'; 
		} else {
			echo '<div class="updated success-msg"><p>Misc Sitemap Created: <a href="'.$file_link.'" target="_blank">'.$file_link.'</a></p></div>'; 
		}
 
		return true;
	}
	else {
		return false;
	}		
}



	
/*
* On activation, set a hook for hourly event.
*/

register_activation_hook(__FILE__,'ctas_activate');
function ctas_activate() 
{
	//Some installation work   
	
	if (! wp_next_scheduled ( 'ctas_hourly_event' )) {
        wp_schedule_event( time(), 'hourly', 'ctas_hourly_event' );
    }


    if (! wp_next_scheduled ( 'ctas_twicedaily_event' )) {
        wp_schedule_event( time(), 'twicedaily', 'ctas_twicedaily_event' );
    }

    flush_rewrite_rules();

}




register_deactivation_hook( __FILE__, 'ctas_deactivation' );
 
function ctas_deactivation() {
    wp_clear_scheduled_hook( 'ctas_hourly_event' );
    wp_clear_scheduled_hook( 'ctas_twicedaily_event' );
    flush_rewrite_rules();
}



/*
 * Add hourly event cron job to an hourly event action.  
 * 
*/
add_action( 'ctas_hourly_event', 'do_this_hourly', 10 );
function do_this_hourly() {
    // Run this function every hour to update posts modified time. 
    auto_update_posts_modified_time();
}


/*
 * Add twice daily event cron job to twice day event action.
 * Generate sitemap twice daily to update time/date, if not update while updating the post.   
 * 
*/
add_action( 'ctas_twicedaily_event', 'do_this_twicedaily', 10 );
function do_this_twicedaily() {
    // Run this function every hour to update posts modified time. 
    ctas_create_sitemap();
}



/*
 * Fetch all ctas sitemap enabled post types and update their posts automatically, 
 * so that their updated time will changed.
*/

function auto_update_posts_modified_time()
{

	$ctas_options = get_option('ctas_generate_sitemap');

	//echo '<pre>'; print_r($ctas_options); echo '</pre>';

	foreach ($ctas_options as $ct_post_type => $post_type_details) 
		{
			
			# If auto update not enabled for this post type skip this operation.

			//echo $post_type_details['enable_auto_update'];

			//echo '<pre>'; print_r($post_type_details); echo '</pre>';

			if( $post_type_details['enable_auto_update'] != 'yes' )  continue;


			if ( $post_type_details['sitemap_by'] != '' ) 
			{

				$excl_posts_sitemap_by_default = $ctas_options[$ct_post_type]['excluded_posts_id'];

				$excl_posts = explode(',' ,$excl_posts_sitemap_by_default); //var_dump($excl_posts);

				$excluded_posts_id = array();

				foreach($excl_posts as $post_id )
				{	
					$post_id = trim($post_id);
					if( !empty($post_id) ) $excluded_posts_id[] = $post_id;
				}

				//var_dump($excluded_posts_id); //Enable to check exclude post id

				// Get posts marked noindex by Yoast
				$yoast_noindex_ids = get_yoast_noindex_post_ids($ct_post_type); // Pass post type

				// Merge and remove duplicates
				$excluded_posts_id = array_unique(array_merge($excluded_posts_id, $yoast_noindex_ids));

				// Fetch posts
				$args = array(
					'numberposts' => 10,
					'posts_per_page' => 10,
					'post_status' => 'publish',
					'exclude'	 => $excluded_posts_id,
					'post_type'  => array( $ct_post_type ), //array( 'post', 'page' )
					'date_query' => array(
						array(
							'column' => 'post_modified',

							'before' => '1 day ago',

							/*'year'  => date('Y'),
		                	'month' => date('m'),
							'day'   => date('19'),
							'compare'   => '<',*/
							//'before' => array(
							               
							//            ),	//date('Y-m-d h:i:s', strtotime('2020-05-19 00:00:00')),
							'inclusive' => true,
						),
					)
				);


				$result = new WP_Query( $args );

				//echo '<pre>'; print_r($result->found_posts); echo '</pre>';

				$fetch_yesterday_updated_posts = $result->get_posts();

				foreach( $fetch_yesterday_updated_posts as $post ) {
					setup_postdata( $post );

					//echo '<pre>'; print_r($post); echo '</pre>'; //Enable to test $_POST global variable					
					update_post_meta( $post->ID, 'auto_update_by_ctas', current_time('mysql') ); //Enable to Debug

					//echo 'Post Updated: '.$post->guid.'<br/>';
					//echo 'Post Modified: '.$post->post_modified.'<br/>';

					$update = array( 'ID' => $post->ID );

					// before saving post
					remove_filter('content_save_pre', 'wp_filter_post_kses');
					remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

					wp_update_post( $update );

					// after saving post
					add_filter('content_save_pre', 'wp_filter_post_kses');
					add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

					//These filter only work for content. 


				}

				/*
				* Now $excluded_posts_id contains all excluded post IDs, including Yoast noindex. Save it back to DB. 
				*/

				// Convert to comma-separated string
				$excluded_posts_str = implode(',', $excluded_posts_id);

				// Update the options array
				$ctas_options[$ct_post_type]['excluded_posts_id'] = $excluded_posts_str;

				

			}
		}
		// Save the updated options back to the database
		update_option('ctas_generate_sitemap', $ctas_options);

}


function copy_cities_to_locations()
{
	$args = array( 
			    'taxonomy' => 'cities',
			    'exclude'	 => '',
			    'hide_empty' => false
			);

	$cities = get_terms($args);


	if ( !empty($cities) ) :

		$i = 0;
	    foreach( $cities as $city ) {
	       
	        $create_city = wp_insert_term(
                 esc_attr( $city->name ),
                'location', 
                array(
                    'description'   => $city->description,
                    'slug'          => $city->slug,
                )
            );

            if (! is_wp_error($create_city) ) $i++;

            //echo '<pre>'; var_dump($create_city); echo '</pre>';
	    }

	    if( $i === 0 )      
	  		echo '<div class="error failure-msg ctas-error-notice"> <p>Zero Location copied.</p> </div>';
		else
		  	echo '<div class="updated success-msg"><p> '.$i.' Location Copied.</p></div>';  	

	    
	endif;
	
	//echo '<pre>'; var_dump($cities); echo '</pre>';
}


function auto_update_products() {
	
	$update_limit = sanitize_text_field( $_POST['update_limit'] );
	$offset_posts = sanitize_text_field( $_POST['offset_posts'] );
	$au_posttype = sanitize_text_field( $_POST['auto_update_posttype'] );

	$numberposts  = ( $update_limit > 0 ) ? $update_limit : -1 ; 
	$offset  = ( $offset_posts > 0 ) ? $offset_posts : 0 ; 


	$args = array(
		'numberposts' => $numberposts,
		'posts_per_page' => $numberposts,
		'offset' => $offset,
		'post_status' => 'publish',
		'meta_key'	=> 'ak_primary_city',
		'orderby' => 'ID',
    	'order'  => 'ASC',
		'post_type'  => array( $au_posttype ) //array( 'post', 'page' )
	);


	$get_products = get_posts( $args );

	//print_r($args);


	$i = 1;

	foreach( $get_products as $product ) {

		//echo $product->ID.'<br/>';
		//setup_postdata( $post );

		//echo '<pre>'; print_r($post); echo '</pre>'; //Enable to test $_POST global variable					
		//echo update_post_meta( $post->ID, 'auto_update_by_ctas', current_time('mysql') ); //Enable to Debug

		//$update = array( 'ID' => $product->ID );
		//if ( wp_update_post( $update ) )   $i++;
		//echo do_action( 'acf/save_post', $product->ID);
		//echo do_action( 'save_post', $product->ID, $product);




		if ( function_exists( 'ak_save_post' ) ) {
			ak_save_post($product->ID);
		}


	}

	if( $i === 0 )      
	  		echo '<div class="error failure-msg ctas-error-notice"> <p>Zero Product updated.</p> </div>';
	else
	  	echo '<div class="updated success-msg"><p>Products Updated.</p></div>';


}



/*
 * Added in 2.4. 
 * To respect all posts marked noindex by yoast. 
*/
function get_yoast_noindex_post_ids($post_type = 'page') {
    global $wpdb;

    $post_ids = $wpdb->get_col( $wpdb->prepare(
        "
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_yoast_wpseo_meta-robots-noindex'
        AND pm.meta_value = '1'
        AND p.post_type = %s
        AND p.post_status = 'publish'
        ",
        $post_type
    ));

    //var_dump($post_ids); //exit;
    return $post_ids;
}