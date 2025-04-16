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
        var $successMessage = $('#success-message');
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
                action: 'generate_gf_coupons',
                nonce: gfCouponGen.nonce,
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
            $.post(gfCouponGen.ajaxUrl, formData, function(response) {
                if (response.success) {
                    var result = response.data;
                    
                    // Display results
                    $successMessage.html(
                        'Successfully generated ' + result.success + ' coupons. ' +
                        (result.failed > 0 ? 'Failed to generate ' + result.failed + ' coupons.' : '')
                    );
                    
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