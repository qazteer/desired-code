<?php
	//show_admin_bar(false);

	add_action( 'admin_init', 'am_remove_menu_pages' );
	function am_remove_menu_pages() {
		global $user_ID;
		function is_user_role( $role, $user_id = null ) {
			$user = is_numeric( $user_id ) ? get_userdata( $user_id ) : wp_get_current_user();
			if( ! $user )
			return false;
			return in_array( $role, (array) $user->roles );
		}
		if( is_user_role( 'editor' ) ) {
			remove_menu_page( 'edit.php?post_type=acf-field-group' );
			remove_menu_page('admin.php?page=activity_log_page');
			remove_menu_page('tools.php');
			remove_menu_page('options-general.php');
			remove_menu_page('themes.php');
			remove_menu_page('theme-general-settings');
		}
	}

	if(!session_id()) {
	    session_start();
	}

	// get link title for menu
	function am_get_menu_link_title($theme_location, $locations){
		$menu = wp_get_nav_menu_object( $locations[$theme_location] );
		$link = am_check_link(get_field('link_title', $menu));
		return $link;
	}

	//check link
	function am_check_link($link){

		if( (isset($link['url']) && !empty($link['url'])) && (isset($link['title']) && !empty($link['title'])) ) return $link;
		else false;
	}


	// get section ID
	function get_section_ID(){

		$id = get_sub_field('section_id');
		if(!$id) $id = get_row_index();

		return $id;
	}

	function get_section_option_ID(){
		$id = get_field('section_id','option');
		if(!$id) $id = get_row_index();

		return $id;
	}

	// create arguments for wp_query for taxonomy
	function am_taxonomy_for_wp_query($taxonomy, $term_id, $args){

		if(!empty($term_id)){

			$args['tax_query'][] =  array(
									'taxonomy' => $taxonomy,
									'field'    => 'id',
									'terms'    => $term_id
								);
		}

		return $args;
	}

	function am_the_selected($request_value, $value){
		if($request_value == $value) echo ' selected';
	}

	// get state and city for location filter
	function am_get_location_field($post_type = 'rentals'){

		//$location_field = get_transient( 'location_field' );
		//if ( false === $location_field ) {
			
			$args = array(
				'posts_per_page' => -1,
				'post_type' => $post_type,
				'post_status' => 'publish'
			);
			$rentals = new WP_Query( $args );	

			$usa = array();
			$canada = array();

			$usa_states = array();
			$usa_city = array();

			$canada_provinces = array();
			$canada_city = array();

			if($rentals->posts){
				foreach ($rentals->posts as $rental) {

					$country = get_field('country', $rental->ID);

					if($country == 'USA'){

						array_push($usa_states, get_field('usa_states', $rental->ID));
						array_push($usa_city, get_field('usa_city', $rental->ID));

					}elseif($country == 'Canada'){

						array_push($canada_provinces, get_field('canada_provinces', $rental->ID));
						array_push($canada_city, get_field('canada_city', $rental->ID));
					}
				}
			}

			$usa = am_array_multisort($usa, 'USA', $usa_states, $usa_city);
			$canada = am_array_multisort($canada, 'Canada', $canada_provinces, $canada_city);
		    
			$usa = array_unique($usa);
			$canada = array_unique($canada);

			$location_field = array_merge($usa, $canada);

			//print_r($usa);
			//print_r($canada);
			//print_r($result);

			//set_transient( 'location_field', $location_field, 1 * HOUR_IN_SECONDS );
		//}

		return $location_field;
	}

	function am_array_multisort($country, $country_name, $states, $cities){

		array_multisort($states, SORT_ASC, $cities, SORT_ASC);

		foreach ($states as $key => $state) {

			array_push($country, $country_name.'_'.$state.'_'.$cities[$key]);
		}

		return $country;
	}

	// get Cove mobile menu
	function am_get_cove_menu_mobile($cove_main_menu){

		$html_menu = '';

		if(isset($cove_main_menu['cove_menu']) && !empty($cove_main_menu['cove_menu'])){
			$html_menu = '<ul class="secondary-nav mobile">';
			foreach($cove_main_menu['cove_menu'] as $menu){

				if($menu['is_link'] && $menu['link']){

					$html_menu .='<li><a href="'.esc_url($menu['link']['url']).'">'.$menu['link']['title'].'</a></li>';

				}elseif($menu['menu']){

					extract(am_get_menu_from_acf($menu['menu']));

					$menu_name = '';
					// if(isset($obj_menu->name) && !empty($obj_menu->name)){
					// 	$menu_name = '<a href="#!">'.$obj_menu->name.'</a>';
					// }

					if($link = am_check_link($link_title)){
						$menu_name .= '<a href="'.esc_url($link['url']).'">'.$link['title'].'</a>';
					}

					if($menu['image_frame']){

						$html_menu .='<li>'.$menu_name.'<div class="drop"><div class="nav-drop-frame">';

						if($title){
							$html_menu .= '<h3 class="title">'.$title.'</h3>';
						}



						if($menu_items){
							$html_menu .= '<ul class="nav-link-boxes">';

							foreach($menu_items as $item){
								$html_menu .= '<li><a href="' . esc_url($item->url) . '" class="box">';
								

								$label = '';
								if($label = get_field('label', $item->ID)) $label = '<span class="label">'.$label.'</span>';

								$image = '';
								if($image = get_field('image', $item->ID)) $image = '<span class="thumb">'.$label.am_get_retina_img($image['url'], '', '191', '118', $image['alt']).'</span>';

								$html_menu .= $image;

								$html_menu .= '<span class="caption">'.$item->title.'</span>';

								$html_menu .= '</a></li>';
							}

							$html_menu .= '</ul>';

							if($button){
								$html_menu .= '<div class="btn-handler"><a href="'.esc_url($button['url']).'" class="btn">'.$button['title'].'</a></div>';
							}
						}

						//$html_menu .='<li>';
						$html_menu .='</div></div></li>';

					}else{

						$html_menu .= '<li class="fixed-drop">';

						if($link = am_check_link($link_title)){
							$html_menu .= '<a href="'.esc_url($link['url']).'">'.$link['title'].'</a>';
						}

						if($menu_items){
							$html_menu .= '<div class="drop"><ul class="secondary-sub-nav">';

							foreach($menu_items as $item){

								$item_class ='';
								if(next($menu_items) != true) $item_class =' class="see-all-btn"';

								$html_menu .= '<li><a href="'.esc_url($item->url).'"'.$item_class.'>'.$item->title.'</a></li>';
							}

							$html_menu .= '</ul></div>';
						}

						$html_menu .='</li>';
					}
				}
			}
			$html_menu .= '</ul>';
		}
		return $html_menu;
	}

	// get data form menu
	function am_get_menu_from_acf($menu_id){

		$menu_data = array();

		if(!empty($menu_id)){

			$obj_menu = wp_get_nav_menu_object( $menu_id );

			if($obj_menu){
				$menu_data['title'] = get_field('title', $obj_menu);
				$menu_data['link_title'] = get_field('link_title', $obj_menu);
				$menu_data['title_class'] = get_field('title_class', $obj_menu);
				$menu_data['menu_class'] = get_field('menu_class', $obj_menu);
				$menu_data['button'] = get_field('button', $obj_menu);
				$menu_data['obj_menu'] = $obj_menu;
				$menu_data['menu_items'] = wp_get_nav_menu_items( $obj_menu );
			}
		}

		return $menu_data;
	}

	// create and get Advanced Menu
	function am_create_advanced_menu($advanced_menu, $property_id){

		$menu_list = '';
		if($advanced_menu){
			foreach($advanced_menu as $menu){
				if(isset($menu['menu']) && !empty($menu['menu'])){

					extract(am_get_menu_from_acf($menu['menu']));

					if(isset($menu_items)){
						

						if($title){
							$menu_list .= '<span class="main-nav-title '.$title_class.'">'.$title.'</span>';
						}

						$menu_list .= '<ul class="main-nav '.$menu_class.'">';
						foreach($menu_items as $item){
							if($item->post_parent == 0 || $item->post_parent == $property_id){
								$menu_list .= '<li><a href="' . esc_url($item->url) . '">' . $item->title . '</a></li>';
							}
						}
						$menu_list .= '</ul>';
					}
				}
			}
		}

		return $menu_list;
	}


	function am_get_property_ID($queried_object){

		$property_id = 0;

		if(!empty($queried_object)){

			if(isset($queried_object->post_type) && $queried_object->post_type == 'property'){

				if($queried_object->post_parent == 0){
					$property_id = $queried_object->ID;
				}else{
					$property_id = am_get_top_level_property($queried_object);
				}
			}elseif($property = get_field('property', $queried_object->ID)){
				$property = end($property);
				$property_id = $property->ID;
			}
		}

		return $property_id;
	}

	function am_get_resorts_communities_ID($queried_object){

		// $post_id = 0;

		// if(!empty($queried_object)){

		// 	if(isset($queried_object->post_type) && $queried_object->post_type == 'page'){

		// 		$term = wp_get_post_terms( $queried_object->ID, 'resorts-communities');
		// 		if($term && !is_wp_error($term)) $term_id = $term[0]->term_id;

		// 	}elseif(isset($queried_object->post_type) && $queried_object->post_type != 'page' && $queried_object->post_type != 'post'){

		// 		$resorts_communities = get_field('resorts_communities', $queried_object->ID);
		// 		$term = get_term( $resorts_communities, 'resorts-communities' );
		// 		if($term && !is_wp_error($term)) $term_id = $term->term_id;

		// 	}elseif(isset($queried_object->taxonomy) && ($queried_object->taxonomy == 'property_category' || $queried_object->taxonomy == 'groups' || $queried_object->taxonomy == 'ways-to-stay' || $queried_object->taxonomy == 'amenities')){
		// 		$resorts_communities = get_field('resorts_communities', 'term_'.$queried_object->term_id);
		// 		$term = get_term( $resorts_communities, 'resorts-communities' );
		// 		if($term && !is_wp_error($term)) $term_id = $term->term_id;
		// 	}
		// }

		// return $post_id;
	}

	function am_get_property_booking_page(){
	
		// 1 array - common name array
		$terms_name = array();
		// 2 array - Canada name array
		$terms_canada_name = array();

		//get all the taxonomy and distribute them by two arrays
		$terms = get_terms('property_category');
		if($terms){
			foreach($terms as $term) {
			    if ($term->parent != 0) { 
			    	if($country = get_field('country', 'term_'.$term->term_id)){
			    		if($country == 'Canada'){
			    			array_push($terms_canada_name, $term->name);
			    			continue;
			    		}
			    	}
			        array_push($terms_name, $term->name);
			    }
			}
		}

		//unique and sort common array of name
		$unique_terms_name = array_unique($terms_name);
		asort($unique_terms_name);

		//unique and sort for Canada name
		$unique_canada_name = array_unique($terms_canada_name);
		asort($unique_canada_name);

		//add Canada name to the end of the common array
		if($unique_canada_name){
			foreach($unique_canada_name as $canada_name) {
				array_push($unique_terms_name, $canada_name);
			}
		}

		//get all 'property' from each taxonomy
		$property_child_taxs = array();
		if($unique_terms_name){
			foreach($unique_terms_name as $term_name) {
			    $args = array(
			    	'posts_per_page' => -1,
					'post_type' => 'property',
					'post_status' => 'publish',
					'post_parent' => 0,
					'orderby' => 'title',
					'order' => 'ASC',
					'tax_query' => array(
						array(
							'taxonomy' => 'property_category',
							'field'    => 'name',
							'terms'    => $term_name
						)
					)
				);
				$property = new WP_Query( $args );
				if($property->posts){
					foreach($property->posts as $post_property) {
						$property_child_taxs[$term_name][] = $post_property;
					}
				}
			}
		}

		return $property_child_taxs;
	}

	function am_get_without_parent_property(){
		$args = array(
			'posts_per_page' => -1,
			'post_type' => 'property',
			'post_status' => 'publish',
			'post_parent' => 0
		);

		$property = new WP_Query( $args );

		return $property;
	}

	function am_get_all_property_cpt(){
		
		$property = am_get_without_parent_property();

		$data = array();
		if($property->have_posts()){
			$item = array();
			while($property->have_posts()){
				$property->the_post();

				$location = get_field('map');
				if(!empty($location)){
					$item['latitude'] = $location['lat'];
					$item['longitude'] = $location['lng'];
				}
				$item['title'] = get_the_title();
				$item['link'] = get_the_permalink();
				$item['text'] = get_field('map_text', get_the_ID());
				$item['picture'] = ($img = get_field('picture', get_the_ID())) ? $img['url'] : '';
				$item['city'] = get_field('city', get_the_ID());
				$item['state'] = get_field('state', get_the_ID());

				$arr_property_category = am_get_top_level_parent_term(get_the_ID(), 'property_category');
				$arr_ways_to_stay = am_get_top_level_parent_term(get_the_ID(), 'ways-to-stay');
				$arr_amenities = am_get_top_level_parent_term(get_the_ID(), 'amenities');
				$arr_groups = am_get_top_level_parent_term(get_the_ID(), 'groups');

				$all_type = array_merge($arr_property_category, $arr_ways_to_stay);
				$all_interests = array_merge($arr_groups, $arr_amenities);

				$item['all_type'] = $all_type;
				$item['groups'] = $all_interests;
				$item['property_tag'] = am_get_top_level_parent_term(get_the_ID(), 'property_tag');

				array_push($data, $item);
			}
		}

		return $data;
	}

	function am_get_top_level_property($queried_object){
		$all_parrents = get_post_ancestors( $queried_object->ID );
		$rootId = end( $all_parrents );
		return $rootId;
	}

	function am_get_top_level_parent_term($post_id, $taxonomy){
		$root_term = array();
		if($terms = get_the_terms($post_id, $taxonomy)){
			foreach ($terms as $term){
				if(isset($term->parent) && $term->parent == 0){
					array_push($root_term, $term->slug);
				}else{
					if(isset($term->term_id)){
						$end = get_ancestors( $term->term_id, $taxonomy );
						$rootId = end( $end );
						$root = get_term( $rootId, $taxonomy );
						array_push($root_term, $root->slug);
					}
				}
			}
		}
		return $root_term;
	}

	// get custom $terms
	function am_get_taxonomy($taxonomy){
		$args = array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'parent' => 0
		);
		$terms = get_terms( $args );
		return $terms;
	}


	//for search header
	function am_search_header($query) {
    if($query->is_search && !is_admin() ) {
        if(isset($_GET['search-type'])) {
            $type = $_GET['search-type'];
            if($type == 'header') {
                $query->set('post_type',array('post','page'));
                $posts_per_page_header = get_field('sh_post_count','option');
                $query->set('posts_per_page', $posts_per_page_header);
            }
        }       
    }
	return $query;
	}
	add_filter('pre_get_posts','am_search_header');

	function am_instagram_api_curl_connect( $api_url ){
		$connection_c = curl_init(); // initializing
		curl_setopt( $connection_c, CURLOPT_URL, $api_url ); // API URL to connect
		curl_setopt( $connection_c, CURLOPT_RETURNTRANSFER, 1 ); // return the result, do not print
		curl_setopt( $connection_c, CURLOPT_TIMEOUT, 20 );
		$json_return = curl_exec( $connection_c ); // connect and get json data
		curl_close( $connection_c ); // close connection
		return json_decode( $json_return ); // decode and return
	}

	remove_filter( 'the_title' , 'wptexturize'  );

	function am_get_blog_id(){
		$page = get_posts( array(
		    'post_type' => 'page',
		    'meta_key' => '_wp_page_template',
		    'meta_value' => 'page-templates/blog.php',
		    'hierarchical' => 0,
		    'posts_per_page' => 1,
		) );

		if ( $page ){
		    $page = current( $page );
		    return $page->ID;
		}else{
			return false;
		}
	}

	add_action("wp_ajax_am_load_more_ccw", "am_load_more_ccw");
	add_action("wp_ajax_nopriv_am_load_more_ccw", "am_load_more_ccw");

	function am_load_more_ccw() {
		$post_content = '';

		if ($_POST["pagetype"] == "search"){

			$posts_per_page = get_option('posts_per_page');
			$top_post = get_field('top_post', am_get_blog_id());
			$posts_per_page = $posts_per_page - 1;

			$args = array(
				'paged' => $_POST["page"],
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => $posts_per_page,
				'orderby' => 'date',
				'order' => 'DESC',
				'cat' => $_POST["cat"],
			    's' => $_POST["search"]
			);

			if($top_post){
				$args['post__not_in'] = array( $top_post[0]->ID );
			}

		}elseif($_POST["pagetype"] == "archive"){

			$posts_per_page = get_option('posts_per_page');
			$top_post = get_field('top_post', am_get_blog_id()); 
			$posts_per_page = $posts_per_page - 1;

			$year = '';
			$monthnum = '';
			if($_POST['archivedate']){
				$archivedate = explode("&", $_POST['archivedate']);
				$year = $archivedate[0];
				$monthnum = $archivedate[1];
			}

			$args = array(
				'posts_per_page' => $posts_per_page,
				'post_type' => 'post',
				'paged' => $_POST["page"],
				'post_status' => 'publish',
				'orderby' => 'date',
				'order' => 'DESC',
				'cat' => $_POST["cat"],
				'year' => $year,
				'monthnum' => $monthnum
			);
			
			if($top_post){
				$args['post__not_in'] = array( $top_post[0]->ID );
			}

		}else{

			$posts_per_page = get_option('posts_per_page');
			$top_post = get_field('top_post', am_get_blog_id());
			$posts_per_page = $posts_per_page - 1;

			
			$args = array(
				'paged' => $_POST["page"],
				'post_type' => 'post',
				'post_status' => 'publish',
				'posts_per_page' => $posts_per_page,
				'orderby' => 'date',
				'order' => 'DESC',
				'cat' => $_POST["cat"]
			);

			if($top_post){
				$args['post__not_in'] = array( $top_post[0]->ID );
			}
			
		}
		
		$query  = new WP_Query( $args );
		$post_content = '';
		$quantity = 0;
		$show_button = false;
		if ($query->have_posts()) :
			while ($query->have_posts()) : $query->the_post();
				$post_content .= load_template_part('template-parts/content', 'post');
			endwhile;
			
			$found_posts = $query->found_posts;
		endif;
		wp_reset_query();
		
		if (($posts_per_page * intval($_POST["page"])) < $found_posts) :
			$show_button = true;
		endif;
		$response['post'] = $post_content;
		$response['button'] = $show_button;
		echo json_encode($response);
		die();
	}

	function am_get_menu_acf_field($field, $theme_location){
		$locations = get_nav_menu_locations();
		$menu = wp_get_nav_menu_object($locations[$theme_location]);
		if($field = get_field($field, $menu)) return $field;
		else return '';
	}
	
	function am_get_menu_title($theme_location) {
		if ( $theme_location && ( $locations = get_nav_menu_locations() ) && isset( $locations[ $theme_location ] ) ) {
			$menu = wp_get_nav_menu_object( $locations[ $theme_location ] );
				
			if( $menu && $menu->name ) {
				return $menu->name;
			}
		}
		return '';
	}

	class AM_Walker_Header_Menu extends Walker_Nav_Menu {

		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			$args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );

			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			// создаем HTML код элемента меню
			$output .= $indent . '<li' . $id . $class_names .'>';

			$atts = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
			$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
			$atts['href']   = ! empty( $item->url )        ? $item->url        : '';

			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( ! empty( $value ) ) {
					$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			$title = apply_filters( 'the_title', $item->title, $item->ID );
			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
			$title = '<span class="caption">'.$title.'</span>';
			$label = '';
			if($label = get_field('label', $item->ID)) $label = '<span class="label">'.$label.'</span>';
			$image = '';
			if($image = get_field('image', $item->ID)) $image = '<span class="thumb">'.$label.am_get_retina_img($image['url'], '', '191', '118', $image['alt']).'</span>';
			

			$item_output = $args->before;
			$item_output .= '<a'. $attributes .' class="box">';
			$item_output .= $args->link_before . $image . $title . $args->link_after;
			$item_output .= '</a>';
			$item_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}



	class Mobilemenu_Walker extends Walker_Nav_Menu {

		public function start_lvl( &$output, $depth = 0, $args = array() ) {

				$indent = str_repeat("\t", $depth);
	        	$output .= "\n$indent<div class='drop'><ul class='secondary-sub-nav'>\n";
			
		}

		public function end_lvl( &$output, $depth = 0, $args = array() ) {

				$indent = str_repeat("\t", $depth);
	        	$output .= "$indent</ul></div>\n";
			
		}

		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

			

			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			// Check our custom has_children property.
			//print_r($args);exit;
		    if ( in_array('menu-item-has-children', $classes) ) {
		      $classes[] = 'fixed-drop';
		    }

			$args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );


			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';


			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			$output .= $indent . '<li' . $id . $class_names .'>';

			$atts = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
			$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
			$atts['href']   = ! empty( $item->url )        ? $item->url        : '';


			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( ! empty( $value ) ) {
					$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			/** This filter is documented in wp-includes/post-template.php */
			$title = apply_filters( 'the_title', $item->title, $item->ID );


			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

			$item_output = $args->before;
			$item_output .= '<a'. $attributes .'>';
			$item_output .= $args->link_before . $title . $args->link_after;
			$item_output .= '</a>';
			$item_output .= $args->after;


			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}

	}


	// Register Custom Post Type Property
	function am_property_post_type() {

	    $labels = array(
	        'name' => _x('Property', 'Post Type General Name', 'am'),
	        'singular_name' => _x('Property', 'Post Type Singular Name', 'am'),
	        'menu_name' => __('Property', 'am'),
	        'name_admin_bar' => __('Property', 'am'),
	        'archives' => __('Property Archives', 'am'),
	        'attributes' => __('Property Attributes', 'am'),
	        'parent_item_colon' => __('Parent Item:', 'am'),
	        'all_items' => __('All Items', 'am'),
	        'add_new_item' => __('Add New Item', 'am'),
	        'add_new' => __('Add New', 'am'),
	        'new_item' => __('New Item', 'am'),
	        'edit_item' => __('Edit Item', 'am'),
	        'update_item' => __('Update Item', 'am'),
	        'view_item' => __('View Item', 'am'),
	        'view_items' => __('View Items', 'am'),
	        'search_items' => __('Search Item', 'am'),
	        'not_found' => __('Not found', 'am'),
	        'not_found_in_trash' => __('Not found in Trash', 'am'),
	        'featured_image' => __('Featured Image', 'am'),
	        'set_featured_image' => __('Set featured image', 'am'),
	        'remove_featured_image' => __('Remove featured image', 'am'),
	        'use_featured_image' => __('Use as featured image', 'am'),
	        'insert_into_item' => __('Insert into item', 'am'),
	        'uploaded_to_this_item' => __('Uploaded to this item', 'am'),
	        'items_list' => __('Items list', 'am'),
	        'items_list_navigation' => __('Items list navigation', 'am'),
	        'filter_items_list' => __('Filter items list', 'am'),
	    );
	    $args = array(
	        'label' => __('Property', 'am'),
	        'description' => __('Post Type Description', 'am'),
	        'labels' => $labels,
	        'supports' => array('title', 'editor', 'page-attributes'),
	        'hierarchical' => true,
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 8,
	        'menu_icon' => 'dashicons-images-alt',
	        'show_in_admin_bar' => true,
	        'show_in_nav_menus' => true,
	        'can_export' => true,
	        'has_archive' => false,
	        'exclude_from_search' => true,
	        'publicly_queryable' => true,
	        'capability_type' => 'page',
	        'rewrite' => true
	        //'rewrite' => array( 'slug'=>'property', 'with_front'=>true),
	        //'rewrite' => array( 'slug'=>'property/%property_category%', 'with_front'=>false, 'pages'=>false, 'feeds'=>false, 'feed'=>false ),
	        // 'taxonomies' => array('post_tag')
	    );
	    register_post_type('property', $args);
	}

	add_action('init', 'am_property_post_type', 0);








	// registration resorts taxonomies
	function create_property_taxonomies(){
	    
	    $labels = array(
	        'name' => _x( 'Property Categories', 'taxonomy general name' ),
	        'singular_name' => _x( 'Property Category', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Property Categories' ),
	        'all_items' => __( 'All Property Categories' ),
	        'parent_item' => __( 'Parent Property Category' ),
	        'parent_item_colon' => __( 'Parent Property Category:' ),
	        'edit_item' => __( 'Edit Property Category' ),
	        'update_item' => __( 'Update Property Category' ),
	        'add_new_item' => __( 'Add New Property Category' ),
	        'new_item_name' => __( 'New Property Category Name' ),
	        'menu_name' => __( 'Property Categories' ),
	    );

	    // Add taxonomie 'property_category' (as category)
	    register_taxonomy('property_category', array('property'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	        //'rewrite' => true
	        'rewrite' => array('slug'=>'property-category', 'hierarchical'=>true, 'with_front'=>false, 'feed'=>false ),
	    ));
	}
	add_action( 'init', 'create_property_taxonomies', 0 );


	// start remove term slug
	add_filter('request', 'am_change_term_request', 1, 1 );
	function am_change_term_request($query){
	 
		$tax_name = 'property_category'; // specify you taxonomy name here, it can be also 'category' or 'post_tag'
	 	
	 	$name = '';
		// Request for child terms differs, we should make an additional check
		if( isset($query['attachment']) ) :
			$include_children = true;
			$name = $query['attachment'];
		elseif(isset($query['name'])):
			$include_children = false;
			$name = $query['name'];
		endif;
	 
	 	if($name): 
			$term = get_term_by('slug', $name, $tax_name); // get the current term to make sure it exists
		 
			if (isset($name) && $term && !is_wp_error($term)): // check it here
		 
				if( $include_children ) {
					unset($query['attachment']);
					$parent = $term->parent;
					while( $parent ) {
						$parent_term = get_term( $parent, $tax_name);
						$name = $parent_term->slug . '/' . $name;
						$parent = $parent_term->parent;
					}
				} else {
					unset($query['name']);
				}
		 		
		 		if($tax_name == 'property_category'){ $query['property_category'] = $name;}
		 
			endif;
		endif;
	 
		return $query;
	 
	}
	  
	add_filter( 'term_link', 'am_term_permalink', 10, 3 ); 
	function am_term_permalink( $url, $term, $taxonomy ){
	 
		$taxonomy_name = 'property_category'; // your taxonomy name here
		$taxonomy_slug = 'property-category'; // the taxonomy slug 
	 
		// exit the function if taxonomy slug is not in URL
		if ( strpos($url, $taxonomy_slug) === FALSE || $taxonomy != $taxonomy_name ) return $url;
	 
		$url = str_replace('/' . $taxonomy_slug, '', $url);
	 
		return $url;
	}
	// end remove term slug


	






	//ways-to-stay
	function create_ways_to_stay_taxonomies(){
	    
	    $labels = array(
	        'name' => _x( 'Ways To Stay', 'taxonomy general name' ),
	        'singular_name' => _x( 'Ways To Stay', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Ways To Stay' ),
	        'all_items' => __( 'All Ways To Stay' ),
	        'parent_item' => __( 'Parent Ways To Stay' ),
	        'parent_item_colon' => __( 'Parent Ways To Stay:' ),
	        'edit_item' => __( 'Edit Ways To Stay' ),
	        'update_item' => __( 'Update Ways To Stay' ),
	        'add_new_item' => __( 'Add New Ways To Stay' ),
	        'new_item_name' => __( 'New Ways To Stay Name' ),
	        'menu_name' => __( 'Ways To Stay' ),
	    );

	    // Add taxonomie 'ways-to-stay' (as category)
	    register_taxonomy('ways-to-stay', array('property'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	        'rewrite' => true,
	    ));
	}
	add_action( 'init', 'create_ways_to_stay_taxonomies', 0 );

	// groups
	function create_groups_taxonomies(){
	    
	    $labels = array(
	        'name' => _x( 'Groups', 'taxonomy general name' ),
	        'singular_name' => _x( 'Group', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Groups' ),
	        'all_items' => __( 'All Groups' ),
	        'parent_item' => __( 'Parent Group' ),
	        'parent_item_colon' => __( 'Parent Group:' ),
	        'edit_item' => __( 'Edit Group' ),
	        'update_item' => __( 'Update Group' ),
	        'add_new_item' => __( 'Add New Group' ),
	        'new_item_name' => __( 'New Group Name' ),
	        'menu_name' => __( 'Groups' ),
	    );

	    // Add taxonomie 'groups' (as category)
	    register_taxonomy('groups', array('property'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	        'rewrite' => true,
	    ));
	}
	add_action( 'init', 'create_groups_taxonomies', 0 );

	// amenities
	function create_amenities_taxonomies(){
	    
	    $labels = array(
	        'name' => _x( 'Amenities', 'taxonomy general name' ),
	        'singular_name' => _x( 'Amenities', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Amenities' ),
	        'all_items' => __( 'All Amenities' ),
	        'parent_item' => __( 'Parent Amenities' ),
	        'parent_item_colon' => __( 'Parent Amenities:' ),
	        'edit_item' => __( 'Edit Amenities' ),
	        'update_item' => __( 'Update Amenities' ),
	        'add_new_item' => __( 'Add New Amenities' ),
	        'new_item_name' => __( 'New Amenities Name' ),
	        'menu_name' => __( 'Amenities' ),
	    );

	    // Add taxonomie 'amenities' (as category)
	    register_taxonomy('amenities', array('property'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	        'rewrite' => true,
	    ));
	}
	add_action( 'init', 'create_amenities_taxonomies', 0 );

	function create_property_tag(){
	    
	    $labels = array(
	        'name' => _x( 'Property Tags', 'taxonomy general name' ),
	        'singular_name' => _x( 'Property Tag', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Property Tags' ),
	        'all_items' => __( 'All Property Tags' ),
	        'parent_item' => __( 'Parent Property Tag' ),
	        'parent_item_colon' => __( 'Parent Property Tag:' ),
	        'edit_item' => __( 'Edit Property Tag' ),
	        'update_item' => __( 'Update Property Tag' ),
	        'add_new_item' => __( 'Add New Property Tag' ),
	        'new_item_name' => __( 'New Property Tag Name' ),
	        'menu_name' => __( 'Property Tags' ),
	    );

	    // Add taxonomie 'property_tag' (as tag)
	    register_taxonomy('property_tag', array('property'), array(
	        'hierarchical' => false,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	        'rewrite' => true,
	    ));
	}

	// registration resorts tag
	add_action( 'init', 'create_property_tag', 0 );


 



	// Register Custom Post Type Weekend
	function am_weekend_post_type() {

	    $labels = array(
	        'name' => _x('Weekend', 'Post Type General Name', 'am'),
	        'singular_name' => _x('Weekend', 'Post Type Singular Name', 'am'),
	        'menu_name' => __('Weekend', 'am'),
	        'name_admin_bar' => __('Weekend', 'am'),
	        'archives' => __('Item Archives', 'am'),
	        'attributes' => __('Item Attributes', 'am'),
	        'parent_item_colon' => __('Parent Item:', 'am'),
	        'all_items' => __('All Items', 'am'),
	        'add_new_item' => __('Add New Item', 'am'),
	        'add_new' => __('Add New', 'am'),
	        'new_item' => __('New Item', 'am'),
	        'edit_item' => __('Edit Item', 'am'),
	        'update_item' => __('Update Item', 'am'),
	        'view_item' => __('View Item', 'am'),
	        'view_items' => __('View Items', 'am'),
	        'search_items' => __('Search Item', 'am'),
	        'not_found' => __('Not found', 'am'),
	        'not_found_in_trash' => __('Not found in Trash', 'am'),
	        'featured_image' => __('Featured Image', 'am'),
	        'set_featured_image' => __('Set featured image', 'am'),
	        'remove_featured_image' => __('Remove featured image', 'am'),
	        'use_featured_image' => __('Use as featured image', 'am'),
	        'insert_into_item' => __('Insert into item', 'am'),
	        'uploaded_to_this_item' => __('Uploaded to this item', 'am'),
	        'items_list' => __('Items list', 'am'),
	        'items_list_navigation' => __('Items list navigation', 'am'),
	        'filter_items_list' => __('Filter items list', 'am'),
	    );
	    $args = array(
	        'label' => __('Weekend', 'am'),
	        'description' => __('Post Type Description', 'am'),
	        'labels' => $labels,
	        'supports' => array('title', 'editor'),
	        'hierarchical' => false,
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 8,
	        'menu_icon' => 'dashicons-calendar-alt',
	        'show_in_admin_bar' => true,
	        'show_in_nav_menus' => false,
	        'can_export' => true,
	        'has_archive' => true,
	        'exclude_from_search' => true,
	        'publicly_queryable' => true,
	        'capability_type' => 'post',
	        // 'taxonomies' => array('post_tag')
	    );
	    register_post_type('weekend', $args);
	}

	add_action('init', 'am_weekend_post_type', 0);




	// Register Custom Post Type Specials
	function am_specials_post_type() {

	    $labels = array(
	        'name' => _x('Specials', 'Post Type General Name', 'am'),
	        'singular_name' => _x('Special', 'Post Type Singular Name', 'am'),
	        'menu_name' => __('Specials', 'am'),
	        'name_admin_bar' => __('Specials', 'am'),
	        'archives' => __('Specials Archives', 'am'),
	        'attributes' => __('Specials Attributes', 'am'),
	        'parent_item_colon' => __('Parent Item:', 'am'),
	        'all_items' => __('All Items', 'am'),
	        'add_new_item' => __('Add New Item', 'am'),
	        'add_new' => __('Add New', 'am'),
	        'new_item' => __('New Item', 'am'),
	        'edit_item' => __('Edit Item', 'am'),
	        'update_item' => __('Update Item', 'am'),
	        'view_item' => __('View Item', 'am'),
	        'view_items' => __('View Items', 'am'),
	        'search_items' => __('Search Item', 'am'),
	        'not_found' => __('Not found', 'am'),
	        'not_found_in_trash' => __('Not found in Trash', 'am'),
	        'featured_image' => __('Featured Image', 'am'),
	        'set_featured_image' => __('Set featured image', 'am'),
	        'remove_featured_image' => __('Remove featured image', 'am'),
	        'use_featured_image' => __('Use as featured image', 'am'),
	        'insert_into_item' => __('Insert into item', 'am'),
	        'uploaded_to_this_item' => __('Uploaded to this item', 'am'),
	        'items_list' => __('Items list', 'am'),
	        'items_list_navigation' => __('Items list navigation', 'am'),
	        'filter_items_list' => __('Filter items list', 'am'),
	    );
	    $args = array(
	        'label' => __('Specials', 'am'),
	        'description' => __('Post Type Description', 'am'),
	        'labels' => $labels,
	        'supports' => array('title'),
	        'hierarchical' => true,
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 8,
	        'menu_icon' => 'dashicons-calendar-alt',
	        'show_in_admin_bar' => true,
	        'show_in_nav_menus' => true,
	        'can_export' => true,
	        'has_archive' => false,
	        'exclude_from_search' => true,
	        'publicly_queryable' => true,
	        'capability_type' => 'page',
	        'rewrite' => true
	    );
	    register_post_type('specials', $args);
	}
	add_action('init', 'am_specials_post_type', 0);

	// registration specials taxonomies
	function create_specials_taxonomies(){
	    
	    $labels = array(
	        'name' => _x( 'Specials Categories', 'taxonomy general name' ),
	        'singular_name' => _x( 'Special Category', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Special Categories' ),
	        'all_items' => __( 'All Special Categories' ),
	        'parent_item' => __( 'Parent Special Category' ),
	        'parent_item_colon' => __( 'Parent Special Category:' ),
	        'edit_item' => __( 'Edit Special Category' ),
	        'update_item' => __( 'Update Special Category' ),
	        'add_new_item' => __( 'Add New Special Category' ),
	        'new_item_name' => __( 'New Special Category Name' ),
	        'menu_name' => __( 'Special Categories' ),
	    );

	    // Add taxonomie 'specials-category' (as category)
	    register_taxonomy('specials-category', array('specials'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	     	'rewrite' => true
	    ));
	}
	add_action( 'init', 'create_specials_taxonomies', 0 );




	// Register Custom Post Type Rentals
	function am_rentals_post_type() {

	    $labels = array(
	        'name' => _x('Rentals', 'Post Type General Name', 'am'),
	        'singular_name' => _x('Rental', 'Post Type Singular Name', 'am'),
	        'menu_name' => __('Rentals', 'am'),
	        'name_admin_bar' => __('Rentals', 'am'),
	        'archives' => __('Rentals Archives', 'am'),
	        'attributes' => __('Rentals Attributes', 'am'),
	        'parent_item_colon' => __('Parent Item:', 'am'),
	        'all_items' => __('All Items', 'am'),
	        'add_new_item' => __('Add New Item', 'am'),
	        'add_new' => __('Add New', 'am'),
	        'new_item' => __('New Item', 'am'),
	        'edit_item' => __('Edit Item', 'am'),
	        'update_item' => __('Update Item', 'am'),
	        'view_item' => __('View Item', 'am'),
	        'view_items' => __('View Items', 'am'),
	        'search_items' => __('Search Item', 'am'),
	        'not_found' => __('Not found', 'am'),
	        'not_found_in_trash' => __('Not found in Trash', 'am'),
	        'featured_image' => __('Featured Image', 'am'),
	        'set_featured_image' => __('Set featured image', 'am'),
	        'remove_featured_image' => __('Remove featured image', 'am'),
	        'use_featured_image' => __('Use as featured image', 'am'),
	        'insert_into_item' => __('Insert into item', 'am'),
	        'uploaded_to_this_item' => __('Uploaded to this item', 'am'),
	        'items_list' => __('Items list', 'am'),
	        'items_list_navigation' => __('Items list navigation', 'am'),
	        'filter_items_list' => __('Filter items list', 'am'),
	    );
	    $args = array(
	        'label' => __('Rentals', 'am'),
	        'description' => __('Post Type Description', 'am'),
	        'labels' => $labels,
	        'supports' => array('title'),
	        'hierarchical' => true,
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 8,
	        'menu_icon' => 'dashicons-building',
	        'show_in_admin_bar' => true,
	        'show_in_nav_menus' => true,
	        'can_export' => true,
	        'has_archive' => false,
	        'exclude_from_search' => true,
	        'publicly_queryable' => true,
	        'capability_type' => 'page',
	        'rewrite' => true
	    );
	    register_post_type('rentals', $args);
	}
	add_action('init', 'am_rentals_post_type', 0);

	// registration Rental Type taxonomies
	function create_rental_type(){
	    
	    $labels = array(
	        'name' => _x( 'Rental Types', 'taxonomy general name' ),
	        'singular_name' => _x( 'Rental Type', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Rental Types' ),
	        'all_items' => __( 'All Rental Types' ),
	        'parent_item' => __( 'Parent Rental Type' ),
	        'parent_item_colon' => __( 'Parent Rental Type:' ),
	        'edit_item' => __( 'Edit Rental Type' ),
	        'update_item' => __( 'Update Rental Type' ),
	        'add_new_item' => __( 'Add New Rental Type' ),
	        'new_item_name' => __( 'New Rental Type Name' ),
	        'menu_name' => __( 'Rental Types' ),
	    );

	    // Add taxonomie 'rental-type' (as category)
	    register_taxonomy('rental-type', array('rentals'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	     	'rewrite' => true
	    ));
	}
	add_action( 'init', 'create_rental_type', 0 );

	// registration RV Site Type taxonomies
	function create_rv_site_type(){
	    
	    $labels = array(
	        'name' => _x( 'RV Site Types', 'taxonomy general name' ),
	        'singular_name' => _x( 'RV Site Type', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search RV Site Types' ),
	        'all_items' => __( 'All RV Site Types' ),
	        'parent_item' => __( 'Parent RV Site Type' ),
	        'parent_item_colon' => __( 'Parent RV Site Type:' ),
	        'edit_item' => __( 'Edit RV Site Type' ),
	        'update_item' => __( 'Update RV Site Type' ),
	        'add_new_item' => __( 'Add New RV Site Type' ),
	        'new_item_name' => __( 'New RV Site Type Name' ),
	        'menu_name' => __( 'RV Site Types' ),
	    );

	    // Add taxonomie 'rental-type' (as category)
	    register_taxonomy('rv-site-type', array('rentals'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	     	'rewrite' => true
	    ));
	}
	add_action( 'init', 'create_rv_site_type', 0 );

	// registration Home Dimensions taxonomies
	function create_home_dimension(){
	    
	    $labels = array(
	        'name' => _x( 'Home Dimensions', 'taxonomy general name' ),
	        'singular_name' => _x( 'Home Dimension', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Home Dimensions' ),
	        'all_items' => __( 'All Home Dimensions' ),
	        'parent_item' => __( 'Parent Home Dimension' ),
	        'parent_item_colon' => __( 'Parent Home Dimension:' ),
	        'edit_item' => __( 'Edit Home Dimension' ),
	        'update_item' => __( 'Update Home Dimension' ),
	        'add_new_item' => __( 'Add New Home Dimension' ),
	        'new_item_name' => __( 'New Home Dimension Name' ),
	        'menu_name' => __( 'Home Dimensions' ),
	    );

	    // Add taxonomie 'home dimension' (as category)
	    register_taxonomy('home-dimension', array('rentals'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	     	'rewrite' => true
	    ));
	}
	add_action( 'init', 'create_home_dimension', 0 );




	// Register Custom Post Type Homes
	function am_homes_post_type() {

	    $labels = array(
	        'name' => _x('Homes', 'Post Type General Name', 'am'),
	        'singular_name' => _x('Home', 'Post Type Singular Name', 'am'),
	        'menu_name' => __('Homes', 'am'),
	        'name_admin_bar' => __('Homes', 'am'),
	        'archives' => __('Homes Archives', 'am'),
	        'attributes' => __('Homes Attributes', 'am'),
	        'parent_item_colon' => __('Parent Item:', 'am'),
	        'all_items' => __('All Items', 'am'),
	        'add_new_item' => __('Add New Item', 'am'),
	        'add_new' => __('Add New', 'am'),
	        'new_item' => __('New Item', 'am'),
	        'edit_item' => __('Edit Item', 'am'),
	        'update_item' => __('Update Item', 'am'),
	        'view_item' => __('View Item', 'am'),
	        'view_items' => __('View Items', 'am'),
	        'search_items' => __('Search Item', 'am'),
	        'not_found' => __('Not found', 'am'),
	        'not_found_in_trash' => __('Not found in Trash', 'am'),
	        'featured_image' => __('Featured Image', 'am'),
	        'set_featured_image' => __('Set featured image', 'am'),
	        'remove_featured_image' => __('Remove featured image', 'am'),
	        'use_featured_image' => __('Use as featured image', 'am'),
	        'insert_into_item' => __('Insert into item', 'am'),
	        'uploaded_to_this_item' => __('Uploaded to this item', 'am'),
	        'items_list' => __('Items list', 'am'),
	        'items_list_navigation' => __('Items list navigation', 'am'),
	        'filter_items_list' => __('Filter items list', 'am'),
	    );
	    $args = array(
	        'label' => __('Homes', 'am'),
	        'description' => __('Post Type Description', 'am'),
	        'labels' => $labels,
	        'supports' => array('title'),
	        'hierarchical' => true,
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 8,
	        'menu_icon' => 'dashicons-building',
	        'show_in_admin_bar' => true,
	        'show_in_nav_menus' => true,
	        'can_export' => true,
	        'has_archive' => false,
	        'exclude_from_search' => true,
	        'publicly_queryable' => true,
	        'capability_type' => 'page',
	        'rewrite' => true
	    );
	    register_post_type('homes', $args);
	}
	add_action('init', 'am_homes_post_type', 0);

	// registration Home Dimensions taxonomies
	function create_home_sale_dimension(){
	    
	    $labels = array(
	        'name' => _x( 'Home Dimensions', 'taxonomy general name' ),
	        'singular_name' => _x( 'Home Dimension', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Home Dimensions' ),
	        'all_items' => __( 'All Home Dimensions' ),
	        'parent_item' => __( 'Parent Home Dimension' ),
	        'parent_item_colon' => __( 'Parent Home Dimension:' ),
	        'edit_item' => __( 'Edit Home Dimension' ),
	        'update_item' => __( 'Update Home Dimension' ),
	        'add_new_item' => __( 'Add New Home Dimension' ),
	        'new_item_name' => __( 'New Home Dimension Name' ),
	        'menu_name' => __( 'Home Dimensions' ),
	    );

	    // Add taxonomie 'home dimension' (as category)
	    register_taxonomy('home-sale-dimension', array('homes'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	     	'rewrite' => true
	    ));
	}
	add_action( 'init', 'create_home_sale_dimension', 0 );

	// registration Manufacturer taxonomies
	function create_home_sale_manufacturer(){
	    
	    $labels = array(
	        'name' => _x( 'Manufacturers', 'taxonomy general name' ),
	        'singular_name' => _x( 'Manufacturer', 'taxonomy singular name' ),
	        'search_items' =>  __( 'Search Manufacturers' ),
	        'all_items' => __( 'All Manufacturers' ),
	        'parent_item' => __( 'Parent Manufacturer' ),
	        'parent_item_colon' => __( 'Parent Manufacturer:' ),
	        'edit_item' => __( 'Edit Manufacturer' ),
	        'update_item' => __( 'Update Manufacturer' ),
	        'add_new_item' => __( 'Add New Manufacturer' ),
	        'new_item_name' => __( 'New Manufacturer Name' ),
	        'menu_name' => __( 'Manufacturers' ),
	    );

	    // Add taxonomie 'home dimension' (as category)
	    register_taxonomy('home-sale-manufacturers', array('homes'), array(
	        'hierarchical' => true,
	        'labels' => $labels,
	        'show_ui' => true,
	        'query_var' => true,
	     	'rewrite' => true
	    ));
	}
	add_action( 'init', 'create_home_sale_manufacturer', 0 );

?>
