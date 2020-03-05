<?php
/**
 * Plugin Name: WP Doc viewer
 * Description: Show the user doc in the admin area of your WordPress installation
 * Author: ArnaudBan
 * Version: 1.0
 * Author URI: https://arnaudban.me
 */

use Pagerange\Markdown\MetaParsedown;
use Pagerange\Markdown\ParserNotFoundException;

if( ! class_exists( 'MetaParsedown' ) && file_exists( __DIR__ . '/vendor/autoload.php' ) ){
    require __DIR__ . '/vendor/autoload.php';
}


Class WP_Doc_viewer{

    private $doc_paths;

    private $doc_pages;

    private $order_sections;


    public function __construct() {

        $doc_path = get_template_directory() . '/doc/';

        $doc_paths = apply_filters( 'mdv/doc-paths', [ $doc_path ] );

        $this->doc_paths = [];

        if( is_array( $doc_paths ) && ! empty( $doc_paths ) ){

            foreach ( $doc_paths as $path ){

                // Check if the "doc" folder exist
                if( is_dir( $path ) ){

                    $this->doc_paths[] = $path;

                }
            }
        }

        if( ! empty( $this->doc_paths ) ){

            $this->doc_pages = $this->get_all_child_page();

            $this->setOrderSection();

            $this->add_admin_menu();

            add_action( 'load-post.php', array( $this, 'add_single_page_contextual_help') );
            add_action( 'load-edit.php', array( $this, 'add_archive_page_contextual_help') );
        }
    }

    /**
     *
     */
    private function add_admin_menu(): void
    {

        // Create admin menu
        $page_suffix = array();
        $page_suffix[] = add_menu_page( __('Site Documentation', 'wp-doc-viewer'), __('Theme Doc', 'wp-doc-viewer' ) , 'manage_options', 'site-user-documentation', array( $this, 'add_documentation_content_page') );

        foreach ( $this->order_sections as $section_slug => $pages ){

            $page_suffix[] = add_submenu_page( 'site-user-documentation', $section_slug, $section_slug, 'manage_options', $section_slug, array( $this, 'add_documentation_content_page'));
        }

        foreach ( $page_suffix as $suffix ){
            add_action( 'admin_print_styles-' . $suffix, array( $this, 'admin_custom_css' ) );
        }

    }

    /**
     * @return array
     * @throws ParserNotFoundException
     */
    private function get_all_child_page(): array
    {

        $child_page = array();

        foreach ( $this->doc_paths as $path ){

            $di = new RecursiveDirectoryIterator($path,RecursiveDirectoryIterator::SKIP_DOTS);
            $it = new RecursiveIteratorIterator($di);

            $parsedown = new MetaParsedown();

            foreach($it as $file) {

                if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {

                    $file_name = pathinfo($file,PATHINFO_FILENAME);

                    $meta = $parsedown->meta( file_get_contents( $file ) );

                    $section = 'Générale';
                    if( isset( $meta['section'] ) ){
                        $section = $meta['section'];
                    } else {
                        $dir = str_replace( $path, '', pathinfo($file,PATHINFO_DIRNAME) . '/' );
                        if( $dir ){
                            $section = rtrim( $dir, '/' );
                        }
                    }

                    $child_page[] = [
                        'path'          => $file,
                        'menu_title'    => isset( $meta['title'] ) ? $meta['title'] : ucfirst( str_replace( array( '-', '_' ), ' ', $file_name ) ),
                        'order'         => isset( $meta['order'] ) ? $meta['order'] : 10,
                        'section'       => $section,
                        'file_name'     => $file_name,
                    ];
                }
            }
        }

        return $child_page;

    }


    /**
     *
     */
    public function admin_custom_css(): void
    {

        wp_enqueue_style( 'doc_viewer_style', plugins_url( 'css/admin-doc.css', __FILE__ ) );
    }


    /**
     *
     */
    public function add_documentation_content_page(): void
    {

        $section = $_GET['page'] ?? null;

        if( $section ){

            $file = $_GET['file'] ?? null;

            $current_section = $section === 'site-user-documentation' ? $this->order_sections[ array_key_first($this->order_sections )] : $this->order_sections[ $section ];
            $path = $current_section[0]['path'];
            if( $file ){
                foreach ( $current_section as $page ){
                    if( $page['file_name'] === $file ){
                        $path = $page['path'];
                    }
                }
            }
            $readme_content = file_get_contents( $path );

            if( $readme_content ){

                $parsedown = new MetaParsedown();

                echo '<div class="wrap">';
                echo    '<div class="wp-doc-viewer">';
                echo    '<div class="wp-doc-viewer__content">';

                $admin_page = admin_url( '/admin.php?page=site-user-documentation' );
                echo  "<a href='$admin_page'>< Retour</a>";

                echo        $parsedown->text($readme_content);

                echo    '</div>';
                echo    '<div class="wp-doc-viewer__toc">';
                $this->toc();
                echo    '</div>';
                echo    '</div>';
                echo '</div>';
            }



        }

    }

    private function toc()
    {

        $section = $_GET['page'];
        $file = $_GET['file'];

        if( $section === 'site-user-documentation' ){
            $section = array_key_first( $this->order_sections );
        }

        if( ! $file ){
            $file = $this->order_sections[$section][0]['file_name'];
        }

        echo '<h2>Table des matière</h2>';

        foreach ( $this->order_sections as $sec => $pages ){


            echo "<h3>$sec</h3>";

            echo '<ul>';
            foreach ( $pages as $p ){

                $classe = $section === $sec && $file === $p['file_name'] ? 'wp-doc-viewer__toc--item current' : 'wp-doc-viewer__toc--item';
                $admin_page = admin_url( "/admin.php?page={$sec}&file={$p['file_name']}" );
                echo "<li><a href='$admin_page' class='{$classe}'>{$p['menu_title']}</a></li>";
            }
            echo '</ul>';
        }
    }

    private function setOrderSection()
    {

        $sections = [];
        foreach ( $this->doc_pages as $args ){
            if( ! isset( $sections[ $args['section'] ] ) ){
                $sections[ $args['section'] ] = [];
            }
            $sections[ $args['section'] ][] = $args;
        }

        foreach ( $sections as $section => $pages ){
            usort( $pages, static function( $a, $b ){

                if ($a['order'] === $b['order']) {
                    return 0;
                }
                return ($a['order'] < $b['order']) ? -1 : 1;
            } );
            $sections[$section] = $pages;
        }


        $this->order_sections = $sections;
    }

    /**
     *
     */
    public function add_single_page_contextual_help(): void
    {

        $screen = get_current_screen();

        if ( $screen ){

            $child_page_slug = array_keys( $this->child_page );

            $page_slug = false;

            if( in_array( 'single-' . $screen->post_type, $child_page_slug, true ) ) {

                $page_slug = 'single-' . $screen->post_type;

            } elseif ( 'page' === $screen->post_type ){

                $page_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : false;

                if( $page_id ){

                    if( $page_id === get_option('page_on_front' ) ){

                        if( in_array( 'front-page', $child_page_slug, true ) ){

                            $page_slug = 'front-page';
                        }

                    }

                    $page_template = get_post_meta( $page_id, '_wp_page_template', true);

                    $page_template = substr( $page_template, 0, -4 );

                    if( in_array( $page_template, $child_page_slug, true ) ){

                        // Boom on ajoute la bon doc;
                        $page_slug = $page_template;
                    }
                }

            }

            if( $page_slug ){

                $this->add_contextual_help( $screen, $page_slug );
            }
        }

    }

    public function add_archive_page_contextual_help(): void
    {


        $screen = get_current_screen();

        if( $screen ){

            $child_page_slug = array_keys( $this->child_page );

            $page_slug = false;

            if( in_array( 'archive-' . $screen->post_type, $child_page_slug, true ) ) {

                $page_slug = 'archive-' . $screen->post_type;

            }


            if( $page_slug ){

                $this->add_contextual_help( $screen, $page_slug );
            }
        }


    }

    /**
     * @param WP_Screen $screen
     * @param string $page_slug
     */
    private function add_contextual_help( WP_Screen $screen, string $page_slug ): void
    {

        $doc_content = file_get_contents( $this->doc_path . $page_slug . '.md');

        if( $doc_content ) {

            $parsedown = new Parsedown();

            $screen->add_help_tab( array(
                'id'      => 'doc_viewer_' . $page_slug,
                'title'   => $this->child_page[ $page_slug ],
                'content' => $parsedown->text( $doc_content ),
            ) );
        }
    }
}

add_action('admin_menu', static function(){ new WP_Doc_viewer(); });
