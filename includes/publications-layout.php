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
				<form method="get" name="form" class="form-inline">
					<div class="col-xs-12 col-sm-6 col-md-2 form-group">
						<select name="pubyr" id="pubyr" class="form-control" onchange="handleSelectorChange()" style="width: 100%;">
							<option value=0>Year</option>
							<?php for ( $i = 0; $i < count( $year_arr ); $i++ ) : ?>
								<option value="<?= $year_arr[ $i ]->PublicationTxt ?>">
									<?= $year_arr[ $i ]->PublicationTxt ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-2 form-group">
						<select name="type" id="type" class="form-control" onchange="handleSelectorChange()" style="width: 100%;">
							<option value=0>Type</option>
							<?php for ( $i = 0; $i < count( $type_arr ); $i++ ) : ?>
								<option value="<?= $type_arr[ $i ]->PublicationType ?>">
									<?= pub_type($type_arr[ $i ]->PublicationType) ?>
								</option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-2 form-group">
						<select name="pubAuth" id="pubAuth" class="form-control" onchange="handleSelectorChange()" style="width: 100%;">
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
								<button class="btn btn-primary" type="button"><i class="fa fa-search" aria-hidden="true"></i></button>
							</span>
						</div>
					</div>
					<br>
				</form>

				<script>
					let form = document.getElementsByName("form")[0];
					let elements = form.elements;

					function loadPublications() {
						// reset page to 1 on selector change
						document.getElementById('pg').value = 1;

						// disable the form fields for visual feedback
						for (let i = 0, len = elements.length; i < len; ++i) {
							elements[i].style.pointerEvents = "none";
							elements[i].onclick = () => false;
							elements[i].onkeydown = () => false;
							elements[i].style.backgroundColor = "#f0f0f0";
							elements[i].style.color = "#6c757d";
							elements[i].style.border = "1px solid #ced4da";
						}

						// build query parameters from the form fields and add ajax=1
						let formData = new FormData(form);
						let params = new URLSearchParams(formData);

						// fetch publications results via AJAX
						fetch(window.location.pathname + "?" + params.toString())
							.then(response => response.text())
							.then(html => {
								document.getElementById("results").innerHTML = html; 
							})
							.catch(error => console.error('Error loading publications:', error))
							.finally(() => {
								// re-enable form fields
								for (let i = 0, len = elements.length; i < len; ++i) {
									elements[i].style.pointerEvents = "";
									elements[i].onclick = null;
									elements[i].onkeydown = null;
									elements[i].style.backgroundColor = "";
									elements[i].style.color = "";
									elements[i].style.border = "";
								}
							});
					}

					// attach handler to search button click
					document.querySelector("button.btn-primary").addEventListener("click", loadPublications);
				</script>


			<div class="col mt-lg-0 mt-5">
				<div id="results">
					<?php
					if ($isDefault) {
						$authortToUse = $isDefault && !empty($wporg_atts['auth']) ? $wporg_atts['auth'] : $pubAuth;
						publications_display($pubyr, $type, $authortToUse, $page, $search);
					} else {
						publications_display($pubyr, $type, $pubAuth, $page, $search);
					}
					?>
				</div>
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
	$countUrl = 'https://api.creol.ucf.edu/PublicationsJson.asmx/PublicationInfoCount?Yr=' . $year . '&Type=' . $type . '&Author=' . $pubAuth;
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
	echo '<div class="text-right">';
    if ($page > 1) {		
        echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=1">First</a> ';
        echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page - 1) . '"><i class="fa fa-caret-left" aria-hidden="true"></i></a> ';
    }
	else {
		echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=1">First</span> ';
        echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page - 1) . '"><i class="fa fa-caret-left" aria-hidden="true"></i></span> ';
	}

    for ($x = ($page - $range); $x < (($page + $range) + 1); $x++) {
        if (($x > 0) && ($x <= $totalPages)) {
            if ($x == $page) {
                echo '<strong>' . $x . '</strong> ';
            } else {
                echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $x . '">' . $x .'</a> '; 
            }
        }
    }

    if ($page < $totalPages) {
        echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page + 1) . '"><i class="fa fa-caret-right" aria-hidden="true"></i></a> ';
        echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $totalPages . '">Last</a>';
    }
	else {
		echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page + 1) . '"><i class="fa fa-caret-right" aria-hidden="true"></i></span> ';
        echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $totalPages . '">Last</span>';
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
	echo '<div class="text-right">';
	if ($page > 1) {		
		echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=1">First</a> ';
		echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page - 1) . '"><i class="fa fa-caret-left" aria-hidden="true"></i></a> ';
	}
	else {
		echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=1">First</span> ';
		echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page - 1) . '"><i class="fa fa-caret-left" aria-hidden="true"></i></span> ';
	}

	for ($x = ($page - $range); $x < (($page + $range) + 1); $x++) {
		if (($x > 0) && ($x <= $totalPages)) {
			if ($x == $page) {
				echo '<strong>' . $x . '</strong> ';
			} else {
				echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $x . '">' . $x .'</a> '; 
			}
		}
	}

	if ($page < $totalPages) {
		echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page + 1) . '"><i class="fa fa-caret-right" aria-hidden="true"></i></a> ';
		echo '<a href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $totalPages . '">Last</a>';
	}
	else {
		echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . ($page + 1) . '"><i class="fa fa-caret-right" aria-hidden="true"></i></span> ';
		echo '<span href="?pubyr=' . $year . '&type=' . $type . '&pubAuth=' . $pubAuth . '&pg=' . $totalPages . '">Last</span>';
	}

	echo '</div>';
	echo '<br>';
	echo '<br>';
}