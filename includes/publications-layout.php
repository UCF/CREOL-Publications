<?php
/**
 * Handles the layout for the publications.
 * Handles query searches, filters data from API, and displays results.
 **/

    // Register custom query variables for filtering
    function register_publications_query_vars( $vars ) {
        $vars[] = 'pubyr';
        $vars[] = 'type';
        $vars[] = 'pubAuth';
        $vars[] = 'pg';
        $vars[] = 'search';
        return $vars;
    }
    add_filter('query_vars', 'register_publications_query_vars');

    // Enqueue our external JavaScript file and pass settings.
    function enqueue_publications_scripts() {
        wp_enqueue_script(
            'publications-script', 
            plugin_dir_url(__FILE__) . '../js/publications.js',
            array('jquery'),
            '1.0',
            true
        );
        wp_localize_script('publications-script', 'publicationsSettings', array(
            'defaultAuth' => !empty($_GET['pubAuth']) ? $_GET['pubAuth'] : (defined('ALL_AUTHORS') ? ALL_AUTHORS : ''),
        ));
    }
    add_action('wp_enqueue_scripts', 'enqueue_publications_scripts');

    // Displays the form for query parameters and handles the logic for updating the results using AJAX.
    function publications_form_display( $atts = [], $content = null, $tag = '' ) {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $wporg_atts = shortcode_atts(array(
            'auth'  => '',
        ), $atts, $tag);
        $year_arr = get_json_nocache( 'https://api.creol.ucf.edu/PublicationsJson.asmx/YearList' );
        $type_arr = get_json_nocache( 'https://api.creol.ucf.edu/PublicationsJson.asmx/TypeList' );
        $pubAuth_arr = get_json_nocache( 'https://api.creol.ucf.edu/PublicationsJson.asmx/AuthorList' );
        ob_start();
        ?>
        <div class="container">
            <div class="row">
                <!-- Form -->
                <form method="get" name="form" id="publication-form" class="form-inline">
                    <div class="col-xs-12 col-sm-6 col-md-2 form-group">
                        <select name="pubyr" id="pubyr" class="form-control" style="width: 100%;">
                            <option value="0">Year</option>
                            <?php for ( $i = 0; $i < count( $year_arr ); $i++ ) : ?>
                                <option value="<?= $year_arr[ $i ]->PublicationTxt ?>">
                                    <?= $year_arr[ $i ]->PublicationTxt ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-2 form-group">
                        <select name="type" id="type" class="form-control" style="width: 100%;">
                            <option value="0">Type</option>
                            <?php for ( $i = 0; $i < count( $type_arr ); $i++ ) : ?>
                                <option value="<?= $type_arr[ $i ]->PublicationType ?>">
                                    <?= pub_type($type_arr[ $i ]->PublicationType) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-xs-12 col-sm-6 col-md-2 form-group">
                        <select name="pubAuth" id="pubAuth" class="form-control" style="width: 100%;">
                            <option value="0">Author</option>
                            <?php for ( $i = 0; $i < count( $pubAuth_arr ); $i++ ) : ?>
                                <option value="<?= $pubAuth_arr[ $i ]->PeopleID ?>">
                                    <?= $pubAuth_arr[ $i ]->LastFirstName ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <input type="hidden" name="pg" id="pg" value="<?php echo isset($_GET['pg']) ? $_GET['pg'] : 1; ?>">
                    <div class="col-xs-12 col-sm-6 col-md-6 form-group">
                        <div class="input-group" style="width: 100%;">
                            <input type="search" id="search" name="search" class="form-control" placeholder="Search" aria-label="Search">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="button" id="search-button">
                                    <i class="fa fa-search" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                    <br>
                </form>

                <!-- Results Container -->
                <div class="col mt-lg-0 mt-5">
                    <div id="results">
                        <?php
                            $isDefault = true;
                            $pubyr = isset($_GET['pubyr']) ? $_GET['pubyr'] : ALL_YEARS;
                            $type = isset($_GET['type']) ? $_GET['type'] : ALL_TYPES;
                            $pubAuth = isset($_GET['pubAuth']) ? $_GET['pubAuth'] : ALL_AUTHORS;
                            $page = isset($_GET['pg']) ? $_GET['pg'] : 1;
                            $search = isset($_GET['search']) ? $_GET['search'] : "";
                            if (isset($_GET['pubyr']) || isset($_GET['type']) || isset($_GET['pubAuth']) || isset($_GET['pg']) || isset($_GET['search'])) {
                                $isDefault = false;
                            }
                            if( $isDefault) {
                                $authorToUse = $isDefault && !empty($wporg_atts['auth']) ? $wporg_atts['auth'] : $pubAuth;
                                publications_display($pubyr, $type, $authorToUse, $page, $search);
                            } else {
                                publications_display($pubyr, $type, $pubAuth, $page, $search);
                            }
                        ?>
                    </div>
                    <div id="pagination-container"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }