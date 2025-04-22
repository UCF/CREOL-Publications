(function($){
    $(document).ready(function(){
        // Load publications HTML from our REST endpoint.
        function loadPublications(page = 1) {
            var formData = $("#publication-form").serialize();
            var params = new URLSearchParams(formData);
            params.set('pg', page);
            // Build the REST endpoint URL that returns styled HTML.
            var endpoint = '/wp-json/publications/v1/html?' + params.toString();

            $.get(endpoint, function(html){
                // Replace the results container with the rendered HTML.
                $("#results").html(html);
                // Reattach pagination link handlers, if needed.
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
        // Handler for pagination links (pagination is rendered in the HTML fragment).
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