<?php

add_action( 'init', 'four_star_register_propertytype_taxonomy' );
function four_star_register_propertytype_taxonomy() {
	register_taxonomy(
		'propertytypes',
		'properties',
		array(
			'label' 			=> __( 'Property types' ),
			'rewrite' 		=> array( 'slug' => 'propertytypes' ),
			'hierarchical' 	=> false,
			'show_in_rest' 	=> true,
		)
	);
}