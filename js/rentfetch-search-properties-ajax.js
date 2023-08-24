jQuery(function ($) {
    // Function to update URL with query parameters
    function updateURLWithQueryParameters(params) {
        var baseUrl = window.location.href.split('?')[0];
        var queryString = $.param(params); // Serialize the parameters
        var newUrl = baseUrl + (queryString ? '?' + queryString : '');
        history.pushState(null, '', newUrl);
    }

    // Function to get query parameters from POST request
    function getQueryParametersFromForm() {
        var queryParams = {};

        // Loop through all form inputs
        $('#filter')
            .find('input, select')
            .each(function () {
                var inputName = $(this).attr('name');
                if (inputName) {
                    var inputValue = $(this).val();

                    // Handle checkboxes and multiple values
                    if ($(this).is(':checkbox')) {
                        if (!queryParams[inputName]) {
                            queryParams[inputName] = [];
                        }
                        if ($(this).is(':checked')) {
                            queryParams[inputName].push(inputValue);
                        }
                    } else {
                        queryParams[inputName] = inputValue;
                    }
                }
            });

        // Remove empty and unwanted parameters
        $.each(queryParams, function (key, value) {
            if (
                value === '' || // Exclude empty values
                key === 'textsearch' || // Exclude specific parameters
                key === 'dates' || // Exclude specific parameters
                key === 'action' // Exclude specific parameters
            ) {
                delete queryParams[key];
            }
        });

        return queryParams;
    }

    // Function to perform AJAX search
    function performAJAXSearch(queryParams) {
        var filter = $('#filter');

        $.ajax({
            url: filter.attr('action'),
            data: filter.serialize(), // form data
            type: filter.attr('method'), // POST
            beforeSend: function (xhr) {
                filter.find('#reset').text('Searching...'); // changing the button label
                $('#reset').text('Searching...'); // changing the button label
            },
            success: function (data) {
                $('#reset').text('Clear All'); // changing the button label
                $('#response').html(data); // insert data

                // look in data for .properties-loop, and count the number of children
                var count = $('.properties-loop').children().length;
                // update #properties-found with the count
                $('#properties-found').text(count);
            },
        });
    }

    // Our ajax query to get stuff and put it into the response div
    $('#filter').submit(function () {
        var queryParams = getQueryParametersFromForm(); // Get parameters from form

        updateURLWithQueryParameters(queryParams);
        performAJAXSearch(queryParams); // Perform AJAX search

        return false;
    });

    // Handle query parameters when the page loads
    var queryParameters = getQueryParametersFromForm();
    updateURLWithQueryParameters(queryParameters);
    performAJAXSearch(queryParameters); // Perform AJAX search

    // $('a.apply').click(function (e) {
    //     e.preventDefault();
    //     $('#filter').submit();
    //     $(this).parents('.dropdown-menu').removeClass('show');
    // });

    // on page load, submit the form
    $('#filter').submit();

    var submitTimer; // Timer identifier

    // Function to submit the form after 1 second of inactivity
    function submitFormAfterInactivity() {
        var self = this; // Capture the current context
        clearTimeout(submitTimer); // Clear any previous timer
        submitTimer = setTimeout(function () {
            $(self).closest('form').submit(); // Submit the form after 1 second of inactivity
        }, 1000);
    }

    // Call the function on input
    $('#filter').on('input', submitFormAfterInactivity);

    // Function to clear all values from fields in #filter when #reset is clicked
    function clearFilterValues() {
        // Reset all non-hidden inputs to null value
        $('#filter')
            .find('input:not([type="hidden"],[type="checkbox"],[type="radio"])')
            .val('')
            .trigger('change'); // Trigger the change event

        // Reset checkboxes to unchecked
        $('#filter').find(':checkbox').prop('checked', false).trigger('change'); // Trigger the change event

        // Get default values for input#pricesmall and input#pricebig
        var defaultValSmall = $('#pricesmall').data('default-value');
        var defaultValBig = $('#pricebig').data('default-value');

        // Set default values for input#pricesmall and input#pricebig
        $('#pricesmall').val(defaultValSmall).trigger('change'); // Trigger the change event
        $('#pricebig').val(defaultValBig).trigger('change'); // Trigger the change event
    }

    // Call the function when #reset is clicked
    $('#reset').click(function () {
        clearFilterValues();
        $('#filter').submit();
    });
});
