<?php
/**
 * Plugin Name: Goliath Doc viewer
 * Description: Show the user doc in the admin area of your WordPress installation
 * Author: Studio Goliath
 * Version: 1.0
 * Author URI: https://www.studio-goliath.com
 */


Class Goliath_Doc_viewer{

    var $doc_path;
    var $child_page;


    public function __construct() {

        $doc_path = get_template_directory() . '/doc/';

        // Check if the "doc" folder exist
        if( is_dir( $doc_path ) ){

            $this->doc_path = $doc_path;

            $this->child_page = $this->get_all_child_page();
            $this->add_admin_menu();

            add_action( 'load-post.php', array( $this, 'add_single_page_contextual_help') );
            add_action( 'load-edit.php', array( $this, 'add_archive_page_contextual_help') );

        } else {

            $this->doc_path = false;

        }
    }


    private function add_admin_menu(){

        // Create admin menu
        $page_suffix = array();
        $page_suffix[] = add_menu_page( __('Site Documentation', 'goliath-doc-viewer'), __('Theme Doc', 'goliath-doc-viewer' ) , 'manage_options', 'site-user-documentation', array( $this, 'goliath_add_documentation_content_page') );

        foreach ( $this->child_page as $slug => $title ){

            $page_suffix[] = add_submenu_page( 'site-user-documentation', $title, $title, 'manage_options', $slug, array( $this, 'goliath_add_documentation_content_page'));
        }

        foreach ( $page_suffix as $suffix ){
            add_action( 'admin_print_styles-' . $suffix, array( $this, 'admin_custom_css' ) );
        }

    }

    private function get_all_child_page(){

        $child_page = array();

        $all_doc_files = scandir( $this->doc_path );

        if( $all_doc_files ){


            foreach ( $all_doc_files as $file ){

                if( substr($file, -3) == '.md' && $file != 'readme.md' ){

                    $page_slug = substr( $file, 0, -3 );
                    $page_title =  ucfirst( str_replace( array( '-', '_' ), ' ', $page_slug ) );
                    $child_page[ $page_slug ] = $page_title;

                }
            }
        }

        return $child_page;

    }

    public function admin_custom_css(){


        wp_enqueue_style( 'goliath_doc_viewer_style', plugins_url( 'css/admin-doc.css', __FILE__ ) );
    }

    public function goliath_add_documentation_content_page(){

        $page_slug = isset( $_GET['page'] ) ? $_GET['page'] : false ;

        if( $page_slug ){

            if( 'site-user-documentation' == $page_slug ){
                $page_slug = 'readme';
            }

            $readme_content = file_get_contents( $this->doc_path . $page_slug . '.md');

            if( $readme_content ){

                include_once 'inc/parsedown.php';
                $Parsedown = new Parsedown();

                echo '<div class="wrap">';
                echo    '<div class="goliath-doc-viewer">';
                echo        $Parsedown->text($readme_content);

            }

            if( 'readme' == $page_slug ){
                // Add a list of page on the firste page
                echo '<ul>';
                foreach ( $this->child_page as $slug => $title ){

                    $admin_page = admin_url( "/admin.php?page=$slug" );
                    echo "<li><a href='$admin_page'>$title</a></li>";
                }
                echo '</ul>';
            }

            echo    '</div>';
            echo '</div>';

        }

    }

    public function add_single_page_contextual_help(){

        $screen = get_current_screen();

        $child_page_slug = array_keys( $this->child_page );

        $page_slug = false;

        if( in_array( 'single-' . $screen->post_type, $child_page_slug ) ) {

            $page_slug = 'single-' . $screen->post_type;

        } elseif ( 'page' == $screen->post_type ){

            $page_id = isset( $_GET['post'] ) ? intval($_GET['post'] ) : false;

            if( $page_id ){

                $page_template = get_post_meta( $page_id, '_wp_page_template', true);

                $page_template = substr( $page_template, 0, -4 );

                if( in_array( $page_template, $child_page_slug) ){

                    // Boom on ajoute la bon doc;
                    $page_slug = $page_template;
                }
            }

        }

        if( $page_slug ){

            $this->add_contextual_help( $screen, $page_slug );
        }
    }

    public function add_archive_page_contextual_help(){


        $screen = get_current_screen();

        $child_page_slug = array_keys( $this->child_page );

        $page_slug = false;

        if( in_array( 'archive-' . $screen->post_type, $child_page_slug ) ) {

            $page_slug = 'archive-' . $screen->post_type;

        }


        if( $page_slug ){

            $this->add_contextual_help( $screen, $page_slug );
        }

    }

    private function add_contextual_help( $screen, $page_slug ){

        $doc_content = file_get_contents( $this->doc_path . $page_slug . '.md');

        if( $doc_content ) {

            include_once 'inc/parsedown.php';
            $Parsedown = new Parsedown();

            $screen->add_help_tab( array(
                'id'      => 'goliath_doc_viewer_' . $page_slug,
                'title'   => $this->child_page[ $page_slug ],
                'content' => $Parsedown->text( $doc_content ),
            ) );
        }
    }
}

add_action('admin_menu', function(){ new Goliath_Doc_viewer(); });



