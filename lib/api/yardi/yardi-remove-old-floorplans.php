<?php

add_action( 'apartmentsync_do_remove_old_data', 'apartmentsync_do_remove_floorplans_from_orphan_yardi_properties' );
function apartmentsync_do_remove_floorplans_from_orphan_yardi_properties() {
            
    $sync_term = get_field( 'sync_term', 'option' );
    $data_sync = get_field( 'data_sync', 'option' );
    
    // if syncing is paused or data dync is off, then then bail, as we won't be restarting anything
    if ( $sync_term == 'paused' || $data_sync == 'delete' || $data_sync == 'nosync' ) {
        as_unschedule_action( 'apartmentsync_do_remove_floorplans_from_orphan_yardi_properties_specific', array(), 'yardi' );
        as_unschedule_all_actions( 'apartmentsync_do_remove_floorplans_from_orphan_yardi_properties_specific', array(), 'yardi' );
        as_unschedule_action( 'apartmentsync_do_remove_orphan_yardi_properties', array(), 'yardi' );
        as_unschedule_all_actions( 'apartmentsync_do_remove_orphan_yardi_properties', array(), 'yardi' );
        return;
    }
    
    if ( as_next_scheduled_action( 'apartmentsync_do_remove_floorplans_from_orphan_yardi_properties_specific', array(), 'yardi' ) == false ) {
        apartmentsync_verbose_log( "Scheduling regular task to remove floorplans from properties that are no longer set to sync." );
        as_schedule_recurring_action( time(), apartmentsync_get_sync_term_in_seconds(), 'apartmentsync_do_remove_floorplans_from_orphan_yardi_properties_specific', array(), 'yardi' );
    }
    
    if ( as_next_scheduled_action( 'apartmentsync_do_remove_orphan_yardi_properties', array(), 'yardi' ) == false ) {
        apartmentsync_verbose_log( "Scheduling regular task to remove properties that are no longer set to sync." );
        as_schedule_recurring_action( time(), apartmentsync_get_sync_term_in_seconds(), 'apartmentsync_do_remove_orphan_yardi_properties', array(), 'yardi' );
    }
 
}

function apartentsync_get_meta_values( $key = '', $type = 'post', $status = 'publish' ) {

    global $wpdb;

    if( empty( $key ) )
        return;

    $r = $wpdb->get_col( $wpdb->prepare( "
        SELECT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s 
        AND p.post_status = %s 
        AND p.post_type = %s
    ", $key, $status, $type ) );
    
    return $r;
}

// //* Temp activation of the script to delete properties
// add_action( 'init', 'apartmentsync_remove_orphan_yardi_properties' );

add_action( 'apartmentsync_do_remove_orphan_yardi_properties', 'apartmentsync_remove_orphan_yardi_properties' );
function apartmentsync_remove_orphan_yardi_properties() {
    
    apartmentsync_verbose_log( "Running script to delete orphan properties from Yardi." );
    
    $property_ids_attached_to_properties = apartentsync_get_meta_values( 'voyager_property_code', 'properties' );
    $property_ids_attached_to_properties = array_unique( $property_ids_attached_to_properties );
    $property_ids_attached_to_properties = array_map('strtolower', $property_ids_attached_to_properties); // lowercase everything, as case mismatches can give us bad results
    
    // echo 'In the database: ' . count( $property_ids_attached_to_properties ) . '<br/>';
    // var_dump( $property_ids_attached_to_properties );

    // get the property ids from the setting
    $yardi_integration_creds = get_field( 'yardi_integration_creds', 'option' );
    $yardi_api_key = $yardi_integration_creds['yardi_api_key'];
    $properties_in_setting = $yardi_integration_creds['yardi_property_code'];
    $properties_in_setting = explode( ',', $properties_in_setting );
    $properties_in_setting = array_unique( $properties_in_setting );
    $properties_in_setting = array_map('strtolower', $properties_in_setting); // lowercase everything, as case mismatches can give us bad results
    
    // echo 'In setting: ' . count( $properties_in_setting ) . '<br/>';
    // var_dump( $properties_in_setting );
    
    // get the ones that are in the database, but that aren't in the setting
    $properties = array_diff( $property_ids_attached_to_properties, $properties_in_setting );
    
    // var_dump( $properties );
    // echo 'Diff: ' . count( $properties );
    // var_dump( $properties );
    
    // bail if there's nothing to delete
    if ( empty( $properties )) {
        apartmentsync_verbose_log( "No orphan properties found." );
        return;
    }
            
    $args = array(
        'post_type' => 'properties',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'relation' => 'AND',
                array(
                    'key' => 'property_source',
                    'value' => 'yardi',
                ),
                array(
                    'key'   => 'voyager_property_code',
                    'value' => $properties,
                ),
            ),
        ),
    );
    
    $properties_to_delete = new WP_Query($args);
    if ( $properties_to_delete->have_posts() ) {
        while ( $properties_to_delete->have_posts() ) {
            $properties_to_delete->the_post();
            
            $property_id = get_the_ID();
            $voyager_property_code = get_post_meta( $property_id, 'voyager_property_code', true );
            
            apartmentsync_log( "Deleting property $voyager_property_code." );
            wp_delete_post( $property_id, true );
        }
    }
}

// //* TEMP activation of the function to delete floorplans
add_action( 'init', 'apartmentsync_remove_floorplans_from_orphan_yardi_properties_specific' );

// add_action( 'apartmentsync_do_remove_floorplans_from_orphan_yardi_properties_specific', 'apartmentsync_remove_floorplans_from_orphan_yardi_properties_specific' );
function apartmentsync_remove_floorplans_from_orphan_yardi_properties_specific() {
    
    // get the property ids which exist in our floorplans CPT 'property_id' meta field
    $property_ids_attached_to_floorplans = apartentsync_get_meta_values( 'voyager_property_code', 'floorplans' );
    $property_ids_attached_to_floorplans = array_unique( $property_ids_attached_to_floorplans );
    
    // get the property ids from the setting
    $yardi_integration_creds = get_field( 'yardi_integration_creds', 'option' );
    $yardi_api_key = $yardi_integration_creds['yardi_api_key'];
    $properties_in_setting = $yardi_integration_creds['yardi_property_code'];
    $properties_in_setting = explode( ',', $properties_in_setting );
    
    // get the ones that are in floorplans, but that aren't in the setting
    $properties = array_diff( $property_ids_attached_to_floorplans, $properties_in_setting );
    
    if ( $properties == null )
        return;
    
    // for each property that's in the DB but not in our list, do a query for floorplans that correspond, then delete those
    foreach( $properties as $property ) {
        
        // remove upcoming actions for pulling floorplans from the API
        apartmentsync_verbose_log( "Property $property found in published floorplans, but not found in setting. Removing upcoming api actions." );
        as_unschedule_action( 'do_get_yardi_floorplans_from_api_for_property', array( $property, $yardi_api_key ), 'yardi' );
        as_unschedule_all_actions( 'do_get_yardi_floorplans_from_api_for_property', array( $property, $yardi_api_key ), 'yardi' );
        
        // remove upcoming actions for syncing floorplans
        apartmentsync_verbose_log( "Property $property found in published floorplans, but not found in setting. Removing upcoming CPT update actions." );
        as_unschedule_action( 'apartmentsync_do_fetch_yardi_floorplans', array( $property ), 'yardi' );
        as_unschedule_all_actions( 'apartmentsync_do_fetch_yardi_floorplans', array( $property ), 'yardi' );
        
        $args = array(
            'post_type' => 'floorplans',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'relation' => 'AND',
                    array(
                        'key' => 'floorplan_source',
                        'value' => 'yardi',
                    ),
                    array(
                        'key'   => 'voyager_property_code',
                        'value' => $property,
                    ),
                ),
            ),
        );
        
        $floorplan_query = new WP_Query($args);
        $floorplanstodelete = $floorplan_query->posts;
        
        foreach ($floorplanstodelete as $floorplantodelete) {
            apartmentsync_verbose_log( "Deleting floorplan $floorplantodelete->ID." );
            wp_delete_post( $floorplantodelete->ID, true );
        }
                
    }
    
}
