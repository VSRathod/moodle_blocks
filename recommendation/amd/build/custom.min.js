define(['jquery', 'block_recommendation/select2','core/str', 'core/notification', 'core/templates'], function($, str, notification, templates) {
    return {
        init: function() {
            // Initialize Select2 with checkboxes
            function initSelect2($element) {
                $element.select2({
                    placeholder: "Select courses...",
                    width: '100%',
                    allowClear: true,
                    closeOnSelect: false,
                    templateSelection: function(data) {
                        return data.text;
                    },
                    templateResult: function(data) {
                        if (!data.id) return data.text;
                        const checked = $(data.element).is(':selected') ? 'checked' : '';
                        return $('<span><input type="checkbox" ' + checked + '/> ' + data.text + '</span>');
                    }
                });
            }

            // Initialize existing Select2 elements
            $('.select2').each(function() {
                initSelect2($(this));
            });

            // Add new row
            $('#add-map').click(function() {
                const rowCount = $('.mapping-row').length;
                const usedCourseIds = $('.course-dropdown').map(function() {
                    return $(this).val();
                }).get();

                // Prepare options for course dropdown (excluding already selected courses)
                let courseOptions = '<option value="">Select a course...</option>';
                $('.course-dropdown').first().find('option').each(function() {
                    if ($(this).val() && usedCourseIds.indexOf($(this).val()) === -1) {
                        courseOptions += `<option value="${$(this).val()}">${$(this).text()}</option>`;
                    }
                });

                // Prepare options for map dropdown (all courses except current selection)
                let mapOptions = '';
                $('.map-dropdown').first().find('option').each(function() {
                    if ($(this).val()) {
                        mapOptions += `<option value="${$(this).val()}">${$(this).text()}</option>`;
                    }
                });

                const newRow = `
                <tr class="mapping-row">
                    <td>
                        <select name="courseid[${rowCount}]" class="form-control course-dropdown">
                            ${courseOptions}
                        </select>
                    </td>
                    <td>
                        <select name="mapcourse[${rowCount}][]" class="form-control select2 map-dropdown" multiple>
                            ${mapOptions}
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger remove-row">
                            <i class="fa fa-trash"></i> Remove
                        </button>
                    </td>
                </tr>`;

                $('#mapping-rows').append(newRow);
                initSelect2($('.map-dropdown').last());

                // Update course dropdowns to exclude newly selected course
                updateCourseDropdowns();
            });

            // Update all course dropdowns to exclude selected courses
            function updateCourseDropdowns() {
                const usedCourseIds = $('.course-dropdown').map(function() {
                    return $(this).val();
                }).get();

                $('.course-dropdown').each(function() {
                    const currentVal = $(this).val();
                    $(this).find('option').each(function() {
                        if ($(this).val() && $(this).val() !== currentVal) {
                            $(this).toggle(usedCourseIds.indexOf($(this).val()) === -1);
                        }
                    });
                });
            }

            // Handle course selection changes
            $('#mapping-rows').on('change', '.course-dropdown', function() {
                updateCourseDropdowns();
            });

            // Remove row with confirmation
            $('#mapping-rows').on('click', '.remove-row', function() {
                var $row = $(this).closest('tr'); // Get the row to delete
                var courseId = $row.find('.course-dropdown').val(); // Get course ID
        
                // Confirmation message
                var confirmDelete = confirm("Are you sure you want to delete this mapping?");
        
                if (confirmDelete) {
                    // Send AJAX request to delete from DB
                    $.ajax({
                        url: 'delete_mapping.php', // Your backend PHP file
                        type: 'POST',
                        data: { courseid: courseId }, // Send course ID to be deleted
                        success: function(response) {
                            if (response.success) {
                                $row.remove(); // Remove the row from the table
                                updateCourseDropdowns(); // Update dropdowns if needed
                            } else {
                                alert("Failed to delete mapping: " + response.message);
                            }
                        },
                        error: function() {
                            $('#map-message')
                            .removeClass('alert-danger')
                            .addClass('alert-success')
                            .text(json.message)
                            .fadeIn();

                        setTimeout(() => {
                            $('#map-message').fadeOut();
                        }, 3000);

                        }
                    });
                }
            });

            // Ensure consistent width for all Select2 dropdowns
            $(window).on('resize', function() {
                $('.select2').select2({
                    width: 'resolve'
                });
            }).trigger('resize');
            // AJAX Form Submission with validation
$('#map-courses-form').submit(function(event) {
    event.preventDefault(); // Prevent default form submission

    let isValid = true;

    // Validate each mapping row
    $('.mapping-row').each(function(index) {
        const mappedCourses = $(this).find('.map-dropdown').val(); // Get selected mapped courses

        if (!mappedCourses || mappedCourses.length < 2) {
            isValid = false;
            $(this).find('.map-dropdown').addClass('is-invalid');
            $('#map-message')
                .removeClass('alert-success')
                .addClass('alert-danger')
                .text(`Please select at least two mapped courses for row ${index + 1}.`)
                .fadeIn();
        
            setTimeout(() => $('#map-message').fadeOut(), 3000);
            return false; // Break out of each loop        
        } else {
            $(this).find('.map-dropdown').removeClass('is-invalid'); // Remove highlight if valid
        }
            });

            if (!isValid) {
                return; // Stop form submission if validation fails
            }

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#map-message')
                        .removeClass('alert-danger')
                        .addClass('alert-success')
                        .text('Mappings saved successfully!')
                        .fadeIn();

                    setTimeout(() => {
                        $('#map-message').fadeOut();
                        location.reload(); // You can still reload after showing message
                    }, 2000);
                },
                error: function() {
                    $('#map-message')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .text('Error saving mappings. Please try again.')
                        .fadeIn();

                    setTimeout(() => {
                        $('#map-message').fadeOut();
                    }, 3000);
                }
            });
        });

        }
    };
});

