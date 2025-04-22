(function($){
    $(document).ready(function(){
        // Set default publication author if missing.
        var defaultAuth = publicationsSettings.defaultAuth || "";
        var urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.get('pubAuth') || urlParams.get('pubAuth') === "0") {
            urlParams.set('pubAuth', defaultAuth);
            history.replaceState(null, '', window.location.pathname + '?' + urlParams.toString());
            $("#pubAuth").val(defaultAuth);
        }

        // Function to fetch and update publications.
        function fetchPublications(page = 1) {
            var url = new URL(window.location);
            var params = new URLSearchParams(url.search);
            params.set('pg', page);
            url.search = params.toString();
            fetch(decodeURIComponent(url.toString()))
                .then(response => response.text())
                .then(data => {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(data, 'text/html');
                    var publications = doc.getElementById('results');
                    var pagination = doc.getElementById('pagination-container');
                    if(publications) {
                        $("#results").html(publications.innerHTML);
                    } else {
                        $("#results").html('');
                    }
                    if(pagination) {
                        $("#pagination-container").html(pagination.innerHTML);
                        attachPaginationListeners();
                    } else {
                        $("#pagination-container").html('');
                    }
                })
                .catch(error => console.error('Error Fetching Publications:', error));
        }

        function updateURL(page = 1) {
            var url = new URL(window.location);
            var params = new URLSearchParams(url.search);
            params.set('pg', page);
            history.pushState(null, '', url.pathname + '?' + params.toString());
        }

        function loadPublications(e) {
            if(e) { e.preventDefault(); }
            var formData = $("#publication-form").serialize();
            var params = new URLSearchParams(formData);
            params.set('pg', 1);
            history.pushState(null, '', window.location.pathname + '?' + params.toString());
            fetchPublications(1);
        }

        $("#publication-form").on("change", loadPublications);
        $("#search-button").on("click", loadPublications);
        $("#publication-form").on("submit", function(e) {
            e.preventDefault();
            loadPublications();
        });
        $("#search").on("keydown", function(e){
            if(e.key === "Enter"){
                e.preventDefault();
                loadPublications();
            }
        });

        function attachPaginationListeners() {
            $("#pagination-container a").each(function(){
                $(this).on("click", function(e){
                    e.preventDefault();
                    var page = $(this).data("page");
                    updateURL(page);
                    fetchPublications(page);
                });
            });
        }

        attachPaginationListeners();
    });
})(jQuery);