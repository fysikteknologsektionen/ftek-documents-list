<?php

/** 
* Plugin Name: Ftek Documents
* Description: Shortcode for listing documents such as meeting records.
* Author: Pontus Granström
* Version: 2.4
* Text Domain: ftekdoc
* Domain Path: /languages
* GitHub Plugin URI: Fysikteknologsektionen/ftek-documents-list
*/

function ftek_documents_shortcode($atts, $content, $tag)
{
    extract( shortcode_atts( array(
		'path' => '',
                'order' => 'default'
	), $atts ) );
    
    if (!$path) {
        return __('No path supplied: [ftek_documents path="your/path/here"]', 'ftekdoc');
    }

    $subpath = untrailingslashit("ftek-documents/$path");

    // We expect years as titles
    // Added some choices. /algmyr Fri Jul 28 20:41:26 CEST 2017
    if ($order == "chrono_reversed") {
        $sorting_options = array('section_order' => SORT_DESC,
                                 'section_type'  => SORT_NATURAL,
                                 'doc_order'     => SORT_DESC,
                                 'doc_type'      => SORT_NATURAL);
    } elseif ($order == "chrono") {
        $sorting_options = array('section_order' => SORT_ASC,
                                 'section_type'  => SORT_NATURAL,
                                 'doc_order'     => SORT_ASC,
                                 'doc_type'      => SORT_NATURAL);
    } else {
        $sorting_options = array('section_order' => SORT_DESC,
                                 'section_type'  => SORT_NUMERIC,);
    }
    return ftek_documents_listing($subpath, $sorting_options);
}
add_shortcode('ftek_documents', 'ftek_documents_shortcode');

/*
 * Given a path to a directory, generates sections for every subdirectory and lists the documents in each subdirectory under that section.
 */

function ftek_documents_listing($path, $sorting_options = array()) {
    $default_sorting_options =  array('section_order'  => SORT_ASC, 
                                      'section_type'   => SORT_REGULAR,
                                      'doc_order'    => SORT_ASC,
                                      'doc_type'     => SORT_REGULAR);
    $sorting_options = array_merge($default_sorting_options, $sorting_options);
    
    // js and css for collapsing document sections
    wp_enqueue_script( 'ftek_documents_collapse', 
                        plugins_url() . '/ftek-documents-list/collapse/collapse.js',
                        array( 'jquery' ),
                        false,
                        false);
    wp_enqueue_style( 'ftek_documents_collapse',
                      plugins_url() . '/ftek-documents-list/collapse/collapse.css');
    
    $upload_dir = wp_upload_dir();
    $basepath = trailingslashit($upload_dir['basedir']) . $path;
    $baseurl  = trailingslashit($upload_dir['baseurl']) . $path;
    // In case of encoding issues, look here: http://se1.php.net/manual/en/function.iconv.php
    $result = '<div class="ftek-documents"><ul>';

    $result .= generate_collapsible($basepath,$baseurl,0,$sorting_options);
    
    return $result . '</ul></div>';
}

//Recursive function to hande subdirectories
function generate_collapsible($path, $urlPath, $depth, $sorting_options = array()) { //Will always have a dir input on $path

    if($depth > 50) { // Prevent too deep recusion
        return ''; 
    }

    $result = "";

    $dirContents  = directory_contents($path);

    if ( empty($dirContents) ) { //If a directory is empty return.
        return '';
    }

    array_multisort($dirContents, $sorting_options['section_order'], $sorting_options['section_type']);

    foreach ($dirContents as $dirItem) {

        if (is_dir("$path/$dirItem")) { //Check if content of a dir is another dir
            $result .= "<h3 class='collapsible'>" 
                    . $dirItem
                    . "</h3>"
                    . "<ul style='display: none;'>";
            
            $subDirPath = "$path/$dirItem";
            $subDirURLPath = "$urlPath/$dirItem";
            $result .= generate_collapsible($subDirPath, $subDirURLPath, ($depth + 1), $sorting_options) . "</ul>"; //Hold my recusion I'm going in!

        }else {
            $docname = pathinfo($dirItem)['filename'];
            $docurl =  "$urlPath/$dirItem";

            $result .= "<li><a href='$docurl' target='_blank'>$docname</a></li>";
        }

    }
    return $result;
}

// Removes crufty "directories" and returns an empty array on error or if folder is empty.
function directory_contents($path) {
    if (!$path or !is_dir($path)) {
        return array();
    }
    $dir_array = scandir($path);
    if ($dir_array) {
        $blacklist = array('..', '.', '.DS_Store');
        return array_diff($dir_array, $blacklist);
    }
    else {
        return array();
    }
}

