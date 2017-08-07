<?php
/**
 * Plugin Name: DjPress
 * Plugin URI: http://clearcode.info
 * Description: A simple WordPress plugin for displaying Mixes.
 * Version: 1.2
 * Author: Mike Flynn <mike@clearcode.info>
 * Author URI: http://clearcode.info
 * License: GPL2
 */
function djpress_user_can_save($post_id) {
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    return ! ( $is_autosave || $is_revision );
}

function djpress_scripts() {
    wp_enqueue_style( 'djpress', plugins_url('stylesheet.css', __FILE__) );
    wp_enqueue_script( 'wavesurfer', plugins_url('js/wavesurfer.min.js', __FILE__), array(), '1.0.0', true );
    wp_enqueue_script( 'wave', plugins_url('js/wave.js', __FILE__), array('jquery','wavesurfer'), '1.0.0', true );
}
function djpress_admin_scripts(){ wp_enqueue_script( 'admin', plugins_url('js/admin.js', __FILE__), array('jquery'), '1.0.0', true ); }
function djpress_post_type() {

    $labels = array(
        'name'               => _x( 'Mixes', 'post type general name', 'djpress_textdomain' ),
        'singular_name'      => _x( 'Mix', 'post type singular name', 'djpress_textdomain' ),
        'menu_name'          => _x( 'Mixes', 'admin menu', 'djpress_textdomain' ),
        'name_admin_bar'     => _x( 'Mix', 'add new on admin bar', 'djpress_textdomain' ),
        'add_new'            => _x( 'Add New', 'mix', 'djpress_textdomain' ),
        'add_new_item'       => __( 'Add New Mix', 'djpress_textdomain' ),
        'new_item'           => __( 'New Mix', 'djpress_textdomain' ),
        'edit_item'          => __( 'Edit Mix', 'djpress_textdomain' ),
        'view_item'          => __( 'View Mix', 'djpress_textdomain' ),
        'all_items'          => __( 'All Mixes', 'djpress_textdomain' ),
        'search_items'       => __( 'Search Mixes', 'djpress_textdomain' ),
        'parent_item_colon'  => __( 'Parent Mixes:', 'djpress_textdomain' ),
        'not_found'          => __( 'No mixes found.', 'djpress_textdomain' ),
        'not_found_in_trash' => __( 'No mixes found in Trash.', 'djpress_textdomain' )
    );    
    
    register_post_type( 'mix', array( 'labels' => $labels, 'public' => true, 'has_archive' => true, ) );
}

function djpress_add_tracklist_meta_box() {add_meta_box( plugin_basename( __FILE__ ), __( 'Tracklist', 'djpress_textdomain' ), 'djpress_tracklist_meta_box_callback', 'mix' ); }
function djpress_add_mix_file_meta_box() { add_meta_box( 'djpress_mix_file', __( 'Upload Mix', 'djpress_textdomain' ), 'djpress_mix_file_meta_box_callback', 'mix' ); }


function djpress_tracklist_meta_box_callback( $post ) {
    $tracklist = get_post_meta( $post->ID, 'djpress_tracklist', true );

    if(!$tracklist) $tracklist = array();
    else if(!is_array($tracklist)) $tracklist = unserialize($tracklist);
    echo '<table class="tracklist" style="width: 100%;"><tr><th></th><th>Song</th><th>Artist</th></tr>';
    $id = 1;
    foreach((array)$tracklist as $track){
        echo "<tr><td>{$id}</td><td><input type='text' name='djpress_tracklist[{$id}][song]' placeholder='Song Title' value='{$track['song']}' style='width:100%'></td><td><input type='text' name='djpress_tracklist[{$id}][artist]' placeholder='Artist' value='{$track['artist']}' style='width:100%'></td><td><a href='#' class='removetrack'>Remove</a></td>";
        $id++;
    }
    echo '</table><p><a href="#" class="addtrack">Add New</a></p>';
}

function djpress_mix_file_meta_box_callback( $post ) {
    $fn = get_post_meta( $post->ID, 'djpress_file', true );
    echo '<p class="description">'.( !$fn  ? 'You have no file attached to this post.' : $fn).'</p>';
}

function djpress_save_tracklist_meta_box_data( $post_id ) {
    if(!djpress_user_can_save($post_id)) return false;
    $tracklist = [];
    $tracklist2 = empty($_POST['djpress_tracklist']) ? array() : $_POST['djpress_tracklist'];
    if(!$tracklist2) $tracklist2 = array();
    foreach((array)$tracklist2 as $track){ if($track['song'] && $track['artist']) $tracklist[] = $track; }
    update_post_meta( $post_id, 'djpress_tracklist', serialize($tracklist) );
}

function djpress_save_mix_file_meta_box_data( $post_id ) {
    if(!djpress_user_can_save($post_id)) return;
    if( ! empty( $_FILES ) && isset( $_FILES['djpress_file'] ) ) {
        if(!file_exists($_FILES['djpress_file']['tmp_name'])) return;
        $rg = file_get_contents( $_FILES['djpress_file']['tmp_name'] );
        $file = wp_upload_bits( $_FILES['djpress_file']['name'], null, $rg );
        if( !$file['error'] ) update_post_meta( $post_id, 'djpress_file', $file['url'] );
    }
}

function djpress_content_filter( $content ) {
    if (! is_singular('mix') ) return $content;
    $downloads = get_post_meta(get_the_ID(), 'djpress_downloads', true);
    if(!$downloads) $downloads = 0;
    $plays = get_post_meta(get_the_ID(), 'djpress_listens', true);
    if(!$plays) $plays = 0;
    $tracklist = get_post_meta(get_the_ID(), 'djpress_tracklist', true );
    if(!$tracklist) $tracklist = array();
    else if(!is_array($tracklist)) $tracklist = unserialize($tracklist);
    $tl = '<table class="tracklist"><tr><th>#</th><th>Track</th><th>Artist</th></tr>';
    $id = 1;
    foreach((array)$tracklist as $track){
        $tl .= "<tr><td>{$id}</td><td>{$track['song']}</td><td>{$track['artist']}</td>";
        $id++;
    }
    $tl .=  '</table>';
    return "<div class='djpress_wave' id='".get_the_ID()."' pls='{$plays}' dls='{$downloads}' ></div>" . $tl . $content;
}

function djpress_excerpt_filter( $content ) {
    if (get_post_type() != 'mix') return $content;
    $downloads = get_post_meta(get_the_ID(), 'djpress_downloads', true); if(!$downloads) $downloads = 0;
    $plays = get_post_meta(get_the_ID(), 'djpress_listens', true); if(!$plays) $plays = 0;
    return "<div class='djpress_wave' id='".get_the_ID()."' pls='{$plays}' dls='{$downloads}' ></div>";
}

function djpress_listen(){
    $ida = 'djpress_listen';
    $count = 'djpress_listens';
    if(empty($_GET[$ida])) return;
    $id = $_GET[$ida];
    $file = get_post_meta($id, 'djpress_file', true);
    $listens = get_post_meta($id, $count, true);
    if(!is_numeric($listens)) $listens = 0;
    $f = ABSPATH.str_replace(get_site_url(),'',$file);

    if(file_exists($f)){
        $listens++;
        update_post_meta($id, $count, $listens);

        header('Content-Description: File Transfer');
        header('Content-type: audio/mpeg, audio/x-mpeg, audio/x-mpeg-3, audio/mpeg3');
        header('Content-Disposition: attachment; filename='.basename($f));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($f));
        readfile($f);
    } else{
        header("HTTP/1.0 404 Not Found");
        echo $f;
    }
    exit;
}
function djpress_download(){
    $ida = 'djpress_download';
    $count = 'djpress_downloads';
    if(empty($_GET[$ida])) return;
    $id = $_GET[$ida];
    $file = get_post_meta($id, 'djpress_file', true);
    $listens = get_post_meta($id, $count, true);
    if(!is_numeric($listens)) $listens = 0;
    $f = ABSPATH.str_replace(get_site_url(),'',$file);
    if(file_exists($f)){
        $listens++;
        update_post_meta($id, $count, $listens);
        header('Content-Description: File Transfer');
        header('Content-type: audio/mpeg, audio/x-mpeg, audio/x-mpeg-3, audio/mpeg3');
        header('Content-Disposition: attachment; filename='.basename($f));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($f));
        readfile($f);
    } else{
        header("HTTP/1.0 404 Not Found");
        echo $f;
    }
    exit;
}

add_filter( 'the_content', 'djpress_content_filter', 20 );
add_filter( 'the_excerpt', 'djpress_excerpt_filter', 20 );

add_action( 'wp_loaded', 'djpress_download');
add_action( 'wp_loaded', 'djpress_listen');
add_action( 'wp_enqueue_scripts', 'djpress_scripts' );
add_action( 'admin_enqueue_scripts', 'djpress_admin_scripts' );

add_action( 'init', 'djpress_post_type' );

add_action( 'save_post', 'djpress_save_tracklist_meta_box_data' );
add_action( 'add_meta_boxes', 'djpress_add_tracklist_meta_box' );

add_action( 'save_post', 'djpress_save_mix_file_meta_box_data' );
add_action( 'add_meta_boxes', 'djpress_add_mix_file_meta_box' );

