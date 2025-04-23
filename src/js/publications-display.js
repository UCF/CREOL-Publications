(function($){
    $(document).ready(function(){
        // Retrieve defaultAuth that was passed from PHP
        const defaultAuth = publicationsSettings.defaultAuth;

        // Use the URL API to parse and update query parameters.
        const url = new URL(window.location);
        const params = url.searchParams;

        // Check if we're not on the homepage.
        const isHomepage = window.location.pathname === '/';

        // If 'pubAuth' is missing or set to "0" and we're not on the homepage, update the URL.
        if (!isHomepage && (!params.get('pubAuth') || params.get('pubAuth') === "0")) {
            params.set('pubAuth', defaultAuth);
            // Replace the current URL, without reloading the page.
            history.replaceState(null, '', url.pathname + '?' + params.toString());
        }

        // Always update the form field.
        $('#pubAuth').val(defaultAuth);
        
        // Load publications HTML from our REST endpoint.
        function loadPublications(page = 1) {
            var formData = $("#publication-form").serialize();
            var params = new URLSearchParams(formData);
            params.set('pg', page);
            // Build the REST endpoint URL that returns styled HTML.
            var endpoint = '/wp-json/publications/v1/html?' + params.toString();

            $.get(endpoint, function(html){
                $("#results").html(html);
                attachPaginationListeners();
            }).fail(function(jqXHR, textStatus, errorThrown){
                console.error("Error fetching publications HTML:", errorThrown);
            });
        }

        // Update the URL state without reloading the page.
        function updateURL(page = 1) {
            var formData = $("#publication-form").serialize();
            var params = new URLSearchParams(formData);
            params.set('pg', page);
            history.pushState(null, '', window.location.pathname + '?' + params.toString());
        }

        // Attach event handlers.
        $("#publication-form").on("submit change", function(e){
            e.preventDefault();
            updateURL(1);
            loadPublications(1);
        });
        $("#search-button").on("click", function(e){
            e.preventDefault();
            updateURL(1);
            loadPublications(1);
        });
        // Handler for pagination links.
        function attachPaginationListeners() {
            $("#pagination-container a").each(function(){
                $(this).on("click", function(e){
                    e.preventDefault();
                    var page = $(this).data("page");
                    updateURL(page);
                    loadPublications(page);
                });
            });
        }
        
        // Load initial publications.
        loadPublications();
    });
})(jQuery);