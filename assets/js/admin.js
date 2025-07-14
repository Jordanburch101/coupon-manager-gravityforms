/**
 * GF Coupon Generator admin scripts
 */
(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        var $form = $('#gf-coupon-generator-form');
        var $spinner = $form.find('.spinner');
        var $generateBtn = $('#generate-coupons-btn');
        var $resultsContainer = $('#results-container');
        var $couponsList = $('#coupons-list');
        var $generateResultsMessage = $('#generate-results-message');
        var $exportBtn = $('#export-csv-btn');
        var $amountType = $('#amount_type');
        var $discountDescription = $('#discount_description');
        
        // Update discount description based on selected type
        function updateDiscountDescription() {
            if ($amountType.val() === 'percentage') {
                $discountDescription.text('Amount of the discount (without % symbol).');
            } else {
                $discountDescription.text('Amount of the discount in dollars (without $ symbol).');
            }
        }
        
        // Set initial description
        updateDiscountDescription();
        
        // Listen for changes on the amount type dropdown
        $amountType.on('change', updateDiscountDescription);
        
        // Tab functionality
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show corresponding content
            $('.tab-content').removeClass('active').hide();
            $('#' + tabId + '-tab').addClass('active').show();
        });
        
        // Update form elements
        var $updateForm = $('#gf-coupon-update-form');
        var $updateAction = $('#update_action');
        var $updateFieldsContainer = $('#update-fields-container');
        var $updateBtn = $('#update-coupons-btn');
        var $updateSpinner = $updateForm.find('.spinner');
        var $updateResultsContainer = $('#update-results-container');
        var $updateResultsList = $('#update-results-list');
        var $updateResultsMessage = $('#update-results-message');
        
        // Handle update action change
        $updateAction.on('change', function() {
            var action = $(this).val();
            
            // Hide all field groups
            $('.update-fields').hide();
            
            if (action) {
                $updateFieldsContainer.show();
                
                // Show relevant fields
                switch(action) {
                    case 'discount':
                        $('#discount-fields').show();
                        break;
                    case 'dates':
                        $('#dates-fields').show();
                        break;
                    case 'usage':
                        $('#usage-fields').show();
                        break;
                    case 'stackable':
                        $('#stackable-fields').show();
                        break;
                    case 'deactivate':
                    case 'activate':
                        // No additional fields needed
                        break;
                }
            } else {
                $updateFieldsContainer.hide();
            }
        });
        
        // Update discount description for new amount type
        $('#new_amount_type').on('change', function() {
            if ($(this).val() === 'percentage') {
                $('#new_discount_description').text('Amount of the discount (without % symbol).');
            } else {
                $('#new_discount_description').text('Amount of the discount in dollars (without $ symbol).');
            }
        });
        
        // Handle update form submission
        $updateForm.on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!$updateForm[0].checkValidity()) {
                return false;
            }
            
            var fileInput = $('#update_csv_file')[0];
            var file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a CSV file.');
                return false;
            }
            
            // Show loading
            $updateSpinner.addClass('is-active');
            $updateBtn.prop('disabled', true);
            
            // Read CSV file
            var reader = new FileReader();
            reader.onload = function(e) {
                var csvContent = e.target.result;
                
                // Collect form data
                var formData = {
                    action: 'update_coupmafo_coupons',
                    nonce: coupmafoCouponGen.nonce,
                    csv_content: csvContent,
                    update_action: $updateAction.val()
                };
                
                // Add specific fields based on action
                switch(formData.update_action) {
                    case 'discount':
                        formData.new_amount_type = $('#new_amount_type').val();
                        formData.new_amount_value = $('#new_amount_value').val();
                        break;
                    case 'dates':
                        formData.new_start_date = $('#new_start_date').val();
                        formData.new_expiry_date = $('#new_expiry_date').val();
                        break;
                    case 'usage':
                        formData.new_usage_limit = $('#new_usage_limit').val();
                        break;
                    case 'stackable':
                        formData.new_is_stackable = $('#new_is_stackable').val();
                        break;
                }
                
                // Send AJAX request
                $.post(coupmafoCouponGen.ajaxUrl, formData, function(response) {
                    if (response.success) {
                        var result = response.data;
                        
                        // Display results summary
                        var successCount = result.results.filter(function(r) { return r.status === 'success'; }).length;
                        var errorCount = result.results.filter(function(r) { return r.status === 'error'; }).length;
                        
                        var messageHtml = '<div class="notice notice-' + (errorCount > 0 ? 'warning' : 'success') + '">' +
                            '<p>Processed ' + result.results.length + ' coupon(s). ' +
                            successCount + ' updated successfully';
                        
                        if (errorCount > 0) {
                            messageHtml += ', ' + errorCount + ' failed';
                        }
                        
                        messageHtml += '.</p></div>';
                        
                        $updateResultsMessage.html(messageHtml);
                        
                        // Clear and populate results table
                        $updateResultsList.empty();
                        $.each(result.results, function(i, item) {
                            $updateResultsList.append(
                                '<tr>' +
                                '<td>' + item.coupon_code + '</td>' +
                                '<td class="' + item.status + '">' + (item.status === 'success' ? 'Success' : 'Error') + '</td>' +
                                '<td>' + item.message + '</td>' +
                                '</tr>'
                            );
                        });
                        
                        $updateResultsContainer.show();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error occurred.'));
                    }
                }).fail(function() {
                    alert('Server error. Please try again.');
                }).always(function() {
                    // Hide loading
                    $updateSpinner.removeClass('is-active');
                    $updateBtn.prop('disabled', false);
                });
            };
            
            reader.readAsText(file);
            
            return false;
        });
        
        // Handle CSV export for update results
        $('#export-update-results-btn').on('click', function() {
            var rows = [];
            var headers = ['Coupon Code', 'Status', 'Message'];
            
            rows.push(headers);
            
            $('#update-results-table tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function() {
                    row.push('"' + $(this).text().replace(/"/g, '""') + '"');
                });
                rows.push(row);
            });
            
            if (rows.length < 2) {
                alert('No data to export.');
                return;
            }
            
            // Convert to CSV
            var csvContent = rows.map(function(row) {
                return row.join(',');
            }).join('\n');
            
            // Create download link
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'coupon-update-results.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!$form[0].checkValidity()) {
                return false;
            }
            
            // Show loading
            $spinner.addClass('is-active');
            $generateBtn.prop('disabled', true);
            
            // Collect form data
            var formData = {
                action: 'generate_coupmafo_coupons',
                nonce: coupmafoCouponGen.nonce,
                form_id: $('#form_id').val(),
                coupon_prefix: $('#coupon_prefix').val(),
                coupon_length: $('#coupon_length').val(),
                amount_type: $amountType.val(),
                amount_value: $('#amount_value').val(),
                start_date: $('#start_date').val(),
                expiry_date: $('#expiry_date').val(),
                usage_limit: $('#usage_limit').val(),
                is_stackable: $('#is_stackable').is(':checked') ? 1 : 0,
                quantity: $('#quantity').val()
            };
            
            // Send AJAX request
            $.post(coupmafoCouponGen.ajaxUrl, formData, function(response) {
                if (response.success) {
                    var result = response.data;
                    
                    // Display results
                    var messageHtml = '<div class="notice notice-' + (result.failed > 0 ? 'warning' : 'success') + '">' +
                        '<p>Successfully generated ' + result.success + ' coupon' + (result.success !== 1 ? 's' : '') + '.';
                    
                    if (result.failed > 0) {
                        messageHtml += ' Failed to generate ' + result.failed + ' coupon' + (result.failed !== 1 ? 's' : '') + '.';
                    }
                    
                    messageHtml += '</p></div>';
                    
                    $generateResultsMessage.html(messageHtml);
                    
                    // Clear and populate table
                    $couponsList.empty();
                    if (result.coupons.length > 0) {
                        $.each(result.coupons, function(i, coupon) {
                            $couponsList.append(
                                '<tr>' +
                                '<td>' + coupon.id + '</td>' +
                                '<td>' + coupon.coupon_code + '</td>' +
                                '</tr>'
                            );
                        });
                        $resultsContainer.show();
                    } else {
                        $resultsContainer.hide();
                    }
                } else {
                    alert('Error: ' + (response.data || 'Unknown error occurred.'));
                }
            }).fail(function() {
                alert('Server error. Please try again.');
            }).always(function() {
                // Hide loading
                $spinner.removeClass('is-active');
                $generateBtn.prop('disabled', false);
            });
            
            return false;
        });
        
        // Handle CSV export
        $exportBtn.on('click', function() {
            var rows = [];
            var headers = ['ID', 'Coupon Code'];
            
            rows.push(headers);
            
            $('#coupons-table tbody tr').each(function() {
                var row = [];
                $(this).find('td').each(function() {
                    row.push($(this).text());
                });
                rows.push(row);
            });
            
            if (rows.length < 2) {
                alert('No data to export.');
                return;
            }
            
            // Convert to CSV
            var csvContent = rows.map(function(row) {
                return row.join(',');
            }).join('\n');
            
            // Create download link
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            
            link.setAttribute('href', url);
            link.setAttribute('download', 'gf-coupons-export.csv');
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
})(jQuery); 
