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

	function publications_add_rewrite_tags() {
		add_rewrite_tag( '%pubyr%',   '([^&]+)' );
		add_rewrite_tag( '%type%',    '([^&]+)' );
		add_rewrite_tag( '%pubAuth%', '([^&]+)' );
		add_rewrite_tag( '%pg%',      '([^&]+)' );
		add_rewrite_tag( '%search%',  '([^&]+)' );
	}
	add_action( 'init', 'publications_add_rewrite_tags' );

    // Enqueue our external JavaScript file and pass settings.
    function enqueue_publications_scripts() {
        wp_enqueue_script(
            'publications-script', 
            plugin_dir_url(__FILE__) . '../src/js/publications-display.js',
            array('jquery'),
            '1.0',
            true
        );
    }
    add_action('wp_enqueue_scripts', 'enqueue_publications_scripts');

    // Displays the form for query parameters and handles the logic for updating the results using AJAX.
    function publications_form_display( $atts = [], $content = null, $tag = '' ) {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        
		$wporg_atts = shortcode_atts(array(
            'auth'  => '',
        ), $atts, $tag);

		// Determine default auth from the shortcode attribute.
		$defaultAuth = !empty( $wporg_atts['auth'] ) ? $wporg_atts['auth'] : ALL_AUTHORS;
	
		// Enqueue the publications script.
		wp_enqueue_script(
			'publications-script', 
			plugin_dir_url(__FILE__) . '../src/js/publications-display.js',
			array('jquery'),
			'1.0',
			true
		);
		
		// Pass the defaultAuth to our script.
		wp_localize_script('publications-script', 'publicationsSettings', array(
			'defaultAuth' => $defaultAuth,
		));
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
						<select name="pubyr" id="pubyr" class="form-control" style="width: 100%;" aria-label="Filter publications by year">
							<option value="0">Year</option>
							<?php for ( $i = 0; $i < count( $year_arr ); $i++ ) : ?>
								<option value="<?= $year_arr[ $i ]->Year ?>">
									<?= $year_arr[ $i ]->Year ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>
                    <div class="col-xs-12 col-sm-6 col-md-2 form-group">
                        <select name="type" id="type" class="form-control" style="width: 100%;"aria-label="Filter publications by type">
                            <option value="0">Type</option>
                            <?php for ( $i = 0; $i < count( $type_arr ); $i++ ) : ?>
                                <option value="<?= $type_arr[ $i ]->PublicationType ?>">
                                    <?= pub_type($type_arr[ $i ]->PublicationType) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                     <div class="col-xs-12 col-sm-6 col-md-2 form-group">
                        <select name="pubAuth" id="pubAuth" class="form-control" style="width: 100%;" aria-label="Filter publications by author">
                            <option value="0">Author</option>
                            <?php foreach ($pubAuth_arr as $author) : ?>
                                <option value="<?= $author->PeopleID ?>">
                                    <?= $author->LastFirstName ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="pg" id="pg" value="<?php echo isset($_GET['pg']) ? $_GET['pg'] : 1; ?>">
                    <div class="col-xs-12 col-sm-6 col-md-6 form-group">
                        <div class="input-group" style="width: 100%;">
                            <input type="search" id="search" name="search" class="form-control" placeholder="Search" aria-label="Search">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="button" id="search-button" aria-label="Search">
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


	// Fetches parameters from the URL, displays the pagination, and displays the publications.
	function publications_display( $year, $type, $pubAuth, $page, $search ) {
		$url = 'https://api.creol.ucf.edu/PublicationsJson.asmx/PublicationInfo?pubyr=' . $year . '&pubType=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $page . '&pubsearch=' . $search;
		$publication_info_arr = get_json_nocache($url);
		if (empty($publication_info_arr)) {
			?>
			<div class="container">
				<div class="row">
					<div class="col">
						<p class="py-4">No results found. Try a different search.</p>
					</div>
				</div>
			</div>
			<?php
			return;
		}

	
		$countUrl = 'https://api.creol.ucf.edu/PublicationsJson.asmx/PublicationInfoCount?Yr=' . $year . '&Type=' . $type . '&Author=' . $pubAuth . '&pubsearch=' . $search;
		$total_publications = get_plain_text($countUrl);

		error_log(json_encode($publication_info_arr));

		// Ensures at least one page and counts total pages.
		$pageSize = 20;
		if($total_publications == 0 || is_null($total_publications)) $totalPages = 1;
		else $totalPages = ceil($total_publications / $pageSize);
		?>

		<br>
		<div class="row float-right">
			Found <?= $total_publications ?> publications.
		</div>
		<br>

		<?php
		$range = 3;
		echo '<div class="text-right" id="pagination-container">';
		// Adds the first and left arrow.
		if ($page > 1) {
			echo '<a href="#" data-page="1" >First</a> ';
			echo '<a href="#" data-page="' . ($page - 1) . '" ><i class="fa fa-caret-left" aria-hidden="true"></i></a> ';
		} else {
			echo '<span>First</span> ';
			echo '<span><i class="fa fa-caret-left" aria-hidden="true"></i></span> ';
		}

		// Displayes visible page numbers and the links attatched to them.
		for ($x = ($page - $range); $x < (($page + $range) + 1); $x++) {
			if (($x > 0) && ($x <= $totalPages)) {
				if ($x == $page) {
					echo '<strong>' . $x . '</strong> ';
				} else {
					echo '<a href="#" data-page="' . $x . '" >' . $x . '</a> ';
				}
			}
		}
		// Adds the right arrow and last page link.
		if ($page < $totalPages) {
			echo '<a href="#" data-page="' . ($page + 1) . '" ><i class="fa fa-caret-right" aria-hidden="true"></i></a> ';
			echo '<a href="#" data-page="' . $totalPages . '" >Last</a> ';
		} else {
			echo '<span><i class="fa fa-caret-right" aria-hidden="true"></i></span> ';
			echo '<span>Last</span>';
		}
		echo '</div>';
		?>
		<script>
			var publications = <?= json_encode($publication_info_arr); ?>;
			var count = publications.length;
			// document.getElementById('publicationCount').textContent = count;
		</script>
		<?php
		$currentType = -1;
		foreach ( $publication_info_arr as $curr ) {
			?>
			<div class="px-2 pb-3 container">
				<?php if ( $curr->PublicationType != $currentType ) {
					?>
					<div class="row font-weight-bold">
						<?= pub_type($curr->PublicationType) ?>
					</div>
					<?php
					$currentType = $curr->PublicationType;
				}?>
				<div class="row">
					<div class="col-xs">
						<span class="h-5 font-weight-bold letter-spacing-1">
							<?= $curr->PublicationYear ?>
						</span>
					</div>
					<div class="col-sm">
						<?= $curr->Authors ?>.
						<span class="fw-italic">
						"<?= $curr->Title ?>".
						</span>
						<?= $curr->Reference ?>
						<?php if (isset($curr->PDFLink) && $curr->PDFLink != '') : ?>
							<a href="<?= $curr->PDFLink ?>" target="_blank"><i class="fa fa-file-pdf-o"></i></a>
						<?php endif; ?>
						<?php if (isset($curr->Link) && $curr->Link != '') : ?>
							<a href="<?= $curr->Link ?>" target="_blank"><i class="fa fa-external-link"></i></a>
						<?php endif; ?>
						<?php if (isset($curr->DOI) && $curr->DOI != '' && isset($curr->DOIVisble)) : ?>
							<a href="<?= $curr->DOI ?>" target="_blank"><i class="fa fa-external-link"></i></a>
						<?php endif; ?>
					</div>
				</div>
			</div>
				
			<?php
		}
		
		$range = 3;
		echo '<div class="text-right" id="pagination-container">';
		if ($page > 1) {
			echo '<a href="#" data-page="1" >First</a> ';
			echo '<a href="#" data-page="' . ($page - 1) . '" ><i class="fa fa-caret-left" aria-hidden="true"></i></a> ';
		} else {
			echo '<span>First</span> ';
			echo '<span><i class="fa fa-caret-left" aria-hidden="true"></i></span> ';
		}

		for ($x = ($page - $range); $x < (($page + $range) + 1); $x++) {
			if (($x > 0) && ($x <= $totalPages)) {
				if ($x == $page) {
					echo '<strong>' . $x . '</strong> ';
				} else {
					echo '<a href="#" data-page="' . $x . '" >' . $x . '</a> ';
				}
			}
		}

		if ($page < $totalPages) {
			echo '<a href="#" data-page="' . ($page + 1) . '" ><i class="fa fa-caret-right" aria-hidden="true"></i></a> ';
			echo '<a href="#" data-page="' . $totalPages . '" >Last</a> ';
		} else {
			echo '<span><i class="fa fa-caret-right" aria-hidden="true"></i></span> ';
			echo '<span>Last</span>';
		}
		echo '</div>';
		echo '<br>';
		echo '<br>';
}