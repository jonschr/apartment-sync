<?php

/**
 * Register the content stypes
 */
add_action( 'init', 'apartmentsync_register_properties_cpt' );
function apartmentsync_register_properties_cpt() {

	//* Properties
	$name_plural = 'Properties';
	$name_singular = 'Property';
	$post_type = 'properties';
	$slug = 'properties';
	$icon = 'admin-home'; //* https://developer.wordpress.org/resource/dashicons/
	$supports = array( 'title' );

	$labels = array(
		'name' => $name_plural,
		'singular_name' => $name_singular,
		'add_new' => 'Add new',
		'add_new_item' => 'Add new ' . $name_singular,
		'edit_item' => 'Edit ' . $name_singular,
		'new_item' => 'New ' . $name_singular,
		'view_item' => 'View ' . $name_singular,
		'search_items' => 'Search ' . $name_plural,
		'not_found' =>  'No ' . $name_plural . ' found',
		'not_found_in_trash' => 'No ' . $name_plural . ' found in trash',
		'parent_item_colon' => '',
		'menu_name' => $name_plural,
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'rewrite' => array( 'slug' => $slug ),
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => null,
		'menu_icon' => 'dashicons-' . $icon,
		'show_in_rest' => true,
		'supports' => $supports,
	);

	register_post_type( $post_type, $args );

}

