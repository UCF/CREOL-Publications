(function($){
    $(document).ready(function(){
        // Function to load publications via our REST endpoint.
        function loadPublications(page = 1) {
            var form = $("#publication-form");
            var formData = form.serializeArray();
            var params = {};
            formData.forEach(function(field){
                params[field.name] = field.value;
            });
            // Ensure correct page number.
            params.pg = page;
            
            // Build URL query string.
            var queryString = $.param(params);
            var endpoint = '/wp-json/publications/v1/list?' + queryString;
            
            // Call the REST endpoint.
            $.getJSON(endpoint, function(data) {
                if(data.message){
                    $("#results").html('<p>' + data.message + '</p>');
                } else {
                    // Update your results container with the returned JSON data.
                    // You might want to build HTML based on your publications, for example:
                    var html = '';
                    $.each(data, function(index, pub){
                        html += '<div class="publication-entry">';
                        html += '<h5>' + pub.Title + '</h5>';
                        html += '<p>' + pub.Authors + ' (' + pub.PublicationYear + ')</p>';
                        html += '</div>';
                    });
                    $("#results").html(html);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching publications: ', errorThrown );
            });
        }
        
        // Attach events to update publications on form change.
        $("#publication-form").on("change submit", function(e){
            e.preventDefault();
            loadPublications(1);
        });
        $("#search-button").on("click", function(e){
            e.preventDefault();
            loadPublications(1);
        });
        
        // Attach pagination listeners if you output pagination links.
        $(document).on("click", "#pagination-container a", function(e){
            e.preventDefault();
            var page = $(this).data("page");
            loadPublications(page);
        });
        
        // Optionally load initial publications.
        loadPublications();
    });
})(jQuery);