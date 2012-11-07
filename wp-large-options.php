<?php

/*
  Plugin Name: WP Large Options
  Plugin URI: http://www.vocecommunications.com
  Description: Allows larger options to be stored in custom post type to prevent
  all_options from overflowing 1MB value limit.
  Author: prettyboymp, voceplatforms
  Version: 1.0
  Author URI: http://www.vocecommunications.com

  GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 */

define( 'WLO_POST_TYPE', 'wlo_option' );

/**
 * Add a new option.
 * @param string $option
 * @param mixed $value
 * @return boolean 
 */
function wlo_add_option( $option, $value ) {
	$option = trim( $option );
	if ( empty( $option ) )
		return false;

	if ( false !== wlo_get_option( $option ) ) {
		return false;
	}

	$_value = $value;
	$value = maybe_serialize( $value );

	$post = array(
		'post_type' => WLO_POST_TYPE,
		'post_name' => $option,
		'post_title' => $option,
		'post_status' => 'publish',
		'post_content' => $value,
	);

	$post_id = wp_insert_post( $post );

	if ( !is_wp_error( $post_id ) ) {
		wp_cache_set( 'wlo_option_id_' . $option, $post_id );
		do_action( "add_wlo_option_{$option}", $option, $_value );
		do_action( 'added_wlo_option', $option, $_value );
		return true;
	}
	return false;
}

/**
 * Update or add an option
 * @param string $option
 * @param mixed $newvalue
 * @return boolean 
 */
function wlo_update_option( $option, $newvalue ) {
	$option = trim( $option );
	if ( empty( $option ) )
		return false;

	$oldvalue = wlo_get_option( $option );

	// If the new and old values are the same, no need to update.
	if ( $newvalue === $oldvalue )
		return false;

	if ( false === $oldvalue )
		return wlo_add_option( $option, $newvalue );

	$_newvalue = $newvalue;
	$newvalue = maybe_serialize( $newvalue );

	$post = wlo_get_option_post( $option );
	$post->post_content = $newvalue;
	$post_id = wp_insert_post( ( array ) $post );

	if ( !is_wp_error( $post_id ) ) {
		do_action( "update_wlo_option_{$option}", $oldvalue, $_newvalue );
		do_action( 'updated_wlo_option', $option, $oldvalue, $_newvalue );
		return true;
	}
	return false;
}

/**
 * Deletes the option
 * @param string $option
 * @return boolean 
 */
function wlo_delete_option( $option ) {
	$option = trim( $option );
	if ( empty( $option ) )
		return false;

	if ( $post = wlo_get_option_post( $option ) ) {
		return wp_delete_post( $post->ID, true );
	}
	return false;
}

/**
 * Returns the option
 * @param string $option
 * @param mixed $default
 * @return mixed 
 */
function wlo_get_option( $option, $default = false ) {
	$option = trim( $option );
	if ( empty( $option ) )
		return false;

	if ( defined( 'WP_SETUP_CONFIG' ) )
		return false;

	if ( !( $post = wlo_get_option_post( $option ) ) )
		return $default;

	return maybe_unserialize( $post->post_content );
}

/**
 * Returns the post that is storing the specific option
 * @param string $option
 * @return bool|object 
 */
function wlo_get_option_post( $option ) {
	if ( false === ($post_id = wp_cache_get( 'wlo_option_id_' . $option ) ) ) {
		$posts = get_posts( array(
			'post_type' => WLO_POST_TYPE,
			'posts_per_page' => 1,
			'name' => $option,
			'fields' => 'ids'
			) );

		if ( count( $posts ) === 1 ) {
			$post_id = $posts[0];
		}
	}

	return $post_id ? get_post( $post_id ) : false;
}


add_action( 'init', function() {
		register_post_type( WLO_POST_TYPE, array(
			'labels' => array(
				'name' => 'Large Options',
				'singular_name' => 'Large Option'
			),
			'publicly_queryable' => false,
			'capability_type' => 'wlo_debug',
			'public' => true,
			'rewrite' => false,
			'has_archive' => false,
			'query_var' => false,
			'taxonomies' => array( ),
			'show_ui' => true,
			'can_export' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => true,
			'show_in_admin_bar' => false,
			'delete_with_user' => false,
		) );
	}, 1 );
