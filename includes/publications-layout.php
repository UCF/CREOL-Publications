<?php
/**
 * Handles the form and the output.
 **/
 //  TODO:
 //  Make search button work.

 // Handles the dropdown on the left.
 function publications_form_display( $atts = [], $content = null, $tag = '' ) {

	$atts = array_change_key_case( (array) $atts, CASE_LOWER );

    $wporg_atts = shortcode_atts(
        array(
            'auth'  => '',
        ), $atts, $tag
    );

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
							<option value=0>Year</option>
							<?php for ( $i = 0; $i < count( $year_arr ); $i++ ) : ?>
								<option value="<?= $year_arr[ $i ]->PublicationTxt ?>">
									<?= $year_arr[ $i ]->PublicationTxt ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-2 form-group">
						<select name="type" id="type" class="form-control" style="width: 100%;">
							<option value=0>Type</option>
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
								<button class="btn btn-primary" type="button" id="search-button"><i class="fa fa-search" aria-hidden="true"></i></button>
							</span>
						</div>
					</div>
					<br>
				</form>

				<script>
					document.addEventListener("DOMContentLoaded", function() {

					// Define fetchPublications and updateURL in this same scope
					function fetchPublications(page = 1) {
						const url = new URL(window.location);
						const params = new URLSearchParams(url.search);
						params.set('pg', page);
						url.search = params.toString();
						const decodedUrl = decodeURIComponent(url.toString());
						fetch(decodedUrl)
						.then(response => response.text())
						.then(data => {
							const parser = new DOMParser();
							const doc = parser.parseFromString(data, 'text/html');
							const publications = doc.getElementById('results');
							const pagination = doc.getElementById('pagination-container');
							document.getElementById('results').innerHTML = publications ? publications.innerHTML : '';
							if (pagination) {
							document.getElementById('pagination-container').innerHTML = pagination.innerHTML;
							// Reattach listeners on newly rendered anchors.
							attachPaginationListeners();
							} else {
							document.getElementById('pagination-container').innerHTML = '';
							}
						})
						.catch(error => console.error('Error Fetching Publications:', error));
					}

					function updateURL(page = 1) {
						const url = new URL(window.location);
						const params = new URLSearchParams(url.search);
						params.set('pg', page);
						history.pushState(null, '', url.pathname + '?' + params.toString());
					}

					function loadPublications(e) {
						if (e) { e.preventDefault(); }
						const form = document.getElementById("publication-form");
						const formData = new FormData(form);
						formData.set('pg', 1);
						const params = new URLSearchParams(formData);
						history.pushState(null, '', window.location.pathname + '?' + params.toString());
						fetchPublications(1);
					}

					// Attach event handlers to the form:
					const form = document.getElementById("publication-form");
					form.addEventListener("change", loadPublications);
					document.getElementById("search-button").addEventListener("click", loadPublications);
					form.addEventListener("submit", function(e) {
						e.preventDefault();
						loadPublications();
					});

					// Prevent enter key on the search input from submitting the form.
					document.getElementById("search").addEventListener("keydown", function(e) {
						if (e.key === "Enter") {
							e.preventDefault();
							loadPublications();
						}
					});

					// Attach listeners directly to each pagination anchor:
					function attachPaginationListeners() {
						const anchors = document.querySelectorAll("#pagination-container a");
						anchors.forEach(function(anchor) {
						anchor.addEventListener("click", function(e) {
							e.preventDefault();
							const page = anchor.dataset.page; // using the anchor's dataset page directly
							updateURL(page);
							fetchPublications(page);
						});
						});
					}

					// Initially attach listeners to any existing pagination links:
					attachPaginationListeners();
					});
				</script>

			<!-- Results Container -->
			<div class="col mt-lg-0 mt-5">
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

				?>
				<div id="results">
				<?php
				if( $isDefault) {
					$authorToUse = $isDefault && !empty($wporg_atts['auth']) ? $wporg_atts['auth'] : $pubAuth;
		 
						publications_display($pubyr, $type, $authorToUse, $page, $search);
					?>
					<script>
					document.getElementById("pubAuth").value = <?php echo $authorToUse ?>;
					</script>
					<?php
				}
				else {
					publications_display($pubyr, $type, $pubAuth, $page, $search);
				?>
				<script>
						const urlParams = new URLSearchParams(window.location.search);
						document.getElementById("pubyr").value = urlParams.get("pubyr") || "<?= ALL_YEARS ?>";
						document.getElementById("type").value = urlParams.get("type") || "<?= ALL_TYPES ?>";
						document.getElementById("pubAuth").value = urlParams.get("pubAuth") || "<?= ALL_AUTHORS ?>";
						document.getElementById("search").value = urlParams.get("search") || "";
				</script>
				<?php
				}
				?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}



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