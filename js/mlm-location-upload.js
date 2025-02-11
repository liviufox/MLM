(function ($) {

    $(document).ready(function () {

        // Intercept form submission for location upload

        $('#mlm_location_form').on('submit', function (e) {

            e.preventDefault();

            var formData = new FormData(this);

            formData.append('action', 'mlm_location_upload'); // For your backend AJAX handler

            if (typeof mlm_ajax_object !== 'undefined') {
                formData.append('security', mlm_ajax_object.nonce);
            }

            $.ajax({
                url: (typeof mlm_ajax_object !== 'undefined') ? mlm_ajax_object.ajax_url : ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $('#mlm_location_form').find('input, button, textarea, select').prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        alert('Location uploaded successfully.');
                        $('#mlm_location_form')[0].reset();
                        $('#location_pictures_preview').html(''); // Clear previews
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    alert('An unexpected error occurred while uploading the location.');
                },
                complete: function () {
                    $('#mlm_location_form').find('input, button, textarea, select').prop('disabled', false);
                }
            });
        });

        // Handle file input & preview logic
        const fileInput = document.getElementById('location_pictures');
        const previewDiv = document.getElementById('location_pictures_preview');

        if (fileInput && previewDiv) {
            fileInput.addEventListener('change', function () {
                console.log('File input changed');

                const files = fileInput.files;
                if (!files || files.length === 0) {
                    console.warn('No files selected.');
                    return;
                }

                // Process each file without clearing the preview container
                Array.from(files).forEach(function (file) {
                    console.log('Processing file:', file.name, file.type);

                    // Validate file size
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    if (file.size > maxSize) {
                        console.error(`File too large (${file.size} bytes):`, file.name);
                        alert(`${file.name} is too large. Maximum size is 5MB.`);
                        return;
                    }

                    // Validate file type
                    if (!file.type.startsWith("image/") && !file.type.startsWith("video/")) {
                        console.warn('Unsupported file type:', file.name);
                        return;
                    }

                    // Read file and create preview
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const previewContainer = document.createElement('div');
                        previewContainer.classList.add('mlm_preview_container');

                        let mediaElement;
                        if (file.type.startsWith("image/")) {
                            mediaElement = document.createElement('img');
                        } else if (file.type.startsWith("video/")) {
                            mediaElement = document.createElement('video');
                            mediaElement.controls = true;
                        }

                        mediaElement.src = event.target.result;
                        mediaElement.alt = file.name;
                        mediaElement.classList.add('mlm_preview_media');

                        const deleteBtn = document.createElement('span');
                        deleteBtn.textContent = '×';
                        deleteBtn.classList.add('mlm-image-delete');
                        deleteBtn.addEventListener('click', function () {
                            previewDiv.removeChild(previewContainer);
                        });

                        const captionInput = document.createElement('input');
                        captionInput.type = 'text';
                        captionInput.name = 'media_captions[]';
                        captionInput.placeholder = 'Enter description';

                        previewContainer.appendChild(mediaElement);
                        previewContainer.appendChild(deleteBtn);
                        previewContainer.appendChild(captionInput);
                        previewDiv.appendChild(previewContainer);
                    };

                    reader.onerror = function () {
                        console.error('Error reading file:', file.name);
                    };

                    reader.readAsDataURL(file);
                });
            });
        } else {
            console.error('File input or preview container not found in the DOM.');
        }
    });

})(jQuery);
