(function($){
    $(document).ready(function(){

        // Retrieve default that was passed from PHP
        const defaultAuth = publicationsSettings.defaultAuth;
        const defaultYear = publicationsSettings.defaultYear;
        const defaultType = publicationsSettings.defaultType;

        // Use the hash fragment instead of search parameters.
        let raw = window.location.hash.slice(1);
        let params = new URLSearchParams(raw);
        let currentAuth = params.get('pubAuth'); // Get current value from hash
        let currentYear = params.get('pubyr'); // Get current year from hash
        let currentType = params.get('type'); // Get current type from hash

        // If 'pubAuth' is missing from the hash, set it to the default and update the hash.
        if (currentAuth === null) {
            currentAuth = defaultAuth; // Use default only if missing
            params.set('pubAuth', currentAuth);
            history.replaceState(null, '', window.location.pathname + '#' + params.toString());
        }
        if (currentYear === null) {
            currentYear = defaultYear; // Use default only if missing
            params.set('pubyr', currentYear);
            history.replaceState(null, '', window.location.pathname + '#' + params.toString());
        }
        if (currentType === null) {
            currentType = defaultType; // Use default only if missing
            params.set('type', currentType);
            history.replaceState(null, '', window.location.pathname + '#' + params.toString());
        }

        // Update the relevant form field based on the value determined from the hash (or default).
        if (currentAuth && currentAuth.includes(',')) {
            $('#pubAuth').val(currentAuth.split(','));
        } else {
            $('#pubAuth').val(currentAuth);
        }
        $('#pubyr').val(currentYear);
        $('#type').val(currentType);

        // Load publications HTML from our REST endpoint.
        function loadPublications(page = 1) {
            const formData = $("#publication-form").serialize(); // This will now correctly include pubAuth=0 if selected
            const formParams = new URLSearchParams(formData);
            formParams.set('pg', page);
            // Build the REST endpoint URL with query parameters.
            const endpoint = (publicationsSettings.restUrl || '/wp-json/publications/v1/html') + '?' + formParams.toString();

            $.get(endpoint, function(html){
                // Replace the results container with the rendered HTML.
                $("#results").html(html);
                // Reattach pagination link handlers, if needed.
                attachPaginationListeners();
            }).fail(function(jqXHR, textStatus, errorThrown){
                console.error("Error fetching publications HTML:", errorThrown);
            });
        }

        // Update the hash state without reloading the page.
        function updateHash(page = 1) {
            const formData = $("#publication-form").serialize();
            const formParams = new URLSearchParams(formData);
            formParams.set('pg', page);
            history.replaceState(null, '', window.location.pathname + '#' + formParams.toString());
        }

        // Attach event handlers.
        $("#publication-form").on("submit change", function(e){
            e.preventDefault();
            updateHash(1); // Update hash based on current form state
            loadPublications(1); // Load based on current form state
        });
        $("#search-button").on("click", function(e){
            e.preventDefault();
            updateHash(1);
            loadPublications(1);
        });
        $('#pubAuth').on('change', function() {
            updateHash(1);
            loadPublications(1);
        });
        $('#pubyr').on('change', function() {
            updateHash(1);
            loadPublications(1);
        });
        $('#type').on('change', function() {
            updateHash(1);
            loadPublications(1);
        });

        // Handler for pagination links (pagination is rendered in the HTML fragment).
        function attachPaginationListeners() {
            $("#pagination-container a").each(function(){
                $(this).on("click", function(e){
                    e.preventDefault();
                    const page = $(this).data("page");
                    updateHash(page); // Update hash first
                    loadPublications(page); // Load based on updated hash/form state
                });
            });
        }

        // Load initial publications based on the potentially updated hash/form state.
        loadPublications();
    });
})(jQuery);