/**
 * Upload functionality for Poxica Image Uploader
 */

(function($) {
    'use strict';

    let currentDropzone = null;
    let activeProductId = null;
    let activeUnitNumber = null;

    $(document).ready(function() {
        initializeUploadZones();
        bindEvents();
    });

    function initializeUploadZones() {
        $('.poxica-dropzone').each(function() {
            const $dropzone = $(this);
            const productId = $dropzone.data('product-id');
            const unitNumber = $dropzone.data('unit-number');

            // Add drag and drop event listeners
            $dropzone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.addClass('poxica-dragover');
            });

            $dropzone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.removeClass('poxica-dragover');
            });

            $dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.removeClass('poxica-dragover');

                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0], productId, unitNumber, $dropzone);
                }
            });

            // Click to select file
            $dropzone.on('click', '.poxica-select-file-btn', function(e) {
                e.preventDefault();
                selectFile(productId, unitNumber, $dropzone);
            });

            // Click on dropzone area
            $dropzone.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('poxica-dropzone-content')) {
                    selectFile(productId, unitNumber, $dropzone);
                }
            });
        });
    }

    function bindEvents() {
        // File input change event
        $('#poxica-file-input').on('change', function() {
            const file = this.files[0];
            if (file && currentDropzone) {
                handleFileSelect(file, activeProductId, activeUnitNumber, currentDropzone);
            }
            // Reset the input
            this.value = '';
        });

        // Modal close on background click
        $('.poxica-modal').on('click', function(e) {
            if (e.target === this) {
                poxicaCloseModal();
            }
        });
    }

    function selectFile(productId, unitNumber, $dropzone) {
        currentDropzone = $dropzone;
        activeProductId = productId;
        activeUnitNumber = unitNumber;
        
        // Trigger file input
        $('#poxica-file-input').click();
    }

    function handleFileSelect(file, productId, unitNumber, $dropzone) {
        // Validate file
        if (!validateFile(file)) {
            return;
        }

        // Show upload progress
        showUploadProgress($dropzone);

        // Upload file
        uploadFile(file, productId, unitNumber, $dropzone);
    }

    function validateFile(file) {
        // Check file type
        const allowedTypes = poxica_ajax.allowed_types.split(',').map(type => type.trim());
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            poxicaShowModal('error', poxica_ajax.strings.invalid_file_type + ': ' + allowedTypes.join(', '));
            return false;
        }

        // Check file size
        if (file.size > poxica_ajax.max_file_size) {
            const maxSizeFormatted = formatFileSize(poxica_ajax.max_file_size);
            poxicaShowModal('error', poxica_ajax.strings.file_too_large + ': ' + maxSizeFormatted);
            return false;
        }

        return true;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showUploadProgress($dropzone) {
        $dropzone.find('.poxica-dropzone-content').hide();
        $dropzone.find('.poxica-upload-progress').show();
        $dropzone.addClass('poxica-uploading');
    }

    function hideUploadProgress($dropzone) {
        $dropzone.find('.poxica-dropzone-content').show();
        $dropzone.find('.poxica-upload-progress').hide();
        $dropzone.removeClass('poxica-uploading');
    }

    function updateUploadProgress(percent, $dropzone) {
        $dropzone.find('.poxica-progress-fill').css('width', percent + '%');
    }

    function uploadFile(file, productId, unitNumber, $dropzone) {
        const formData = new FormData();
        formData.append('action', 'poxica_upload_image');
        formData.append('nonce', poxica_ajax.nonce);
        formData.append('order_id', poxica_ajax.order_id);
        formData.append('token', poxica_ajax.token);
        formData.append('product_record_id', productId);
        formData.append('unit_number', unitNumber);
        formData.append('file', file);

        $.ajax({
            url: poxica_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        updateUploadProgress(percentComplete, $dropzone);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    handleUploadSuccess(response.data, $dropzone, productId, unitNumber);
                } else {
                    handleUploadError(response.data.message, $dropzone);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = poxica_ajax.strings.upload_error;
                
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (error) {
                    errorMessage += ': ' + error;
                }
                
                handleUploadError(errorMessage, $dropzone);
            }
        });
    }

    function handleUploadSuccess(data, $dropzone, productId, unitNumber) {
        // Hide upload progress
        hideUploadProgress($dropzone);

        // Mark unit as completed
        markUnitAsCompleted($dropzone, productId, unitNumber);

        // Update progress counters
        updateProgressCounters();

        // Show success message
        poxicaShowModal('success', data.message || poxica_ajax.strings.upload_success);

        // Check if all uploads are complete
        setTimeout(poxicaCheckAllComplete, 1000);
    }

    function handleUploadError(message, $dropzone) {
        // Hide upload progress
        hideUploadProgress($dropzone);

        // Show error message
        poxicaShowModal('error', message);
    }

    function markUnitAsCompleted($dropzone, productId, unitNumber) {
        const $unit = $dropzone.closest('.poxica-unit');
        
        // Update unit classes
        $unit.addClass('poxica-unit-completed');
        
        // Update status
        $unit.find('.poxica-status')
            .removeClass('poxica-status-pending')
            .addClass('poxica-status-uploaded')
            .text('Subida');

        // Replace dropzone with success indicator
        $dropzone.replaceWith(`
            <div class="poxica-uploaded-image">
                <div class="poxica-upload-success">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4906 2.02168 11.3412C2.16356 9.19173 2.99721 7.14459 4.39394 5.49695C5.79067 3.84930 7.67293 2.69547 9.75166 2.1917C11.8304 1.68794 14.0089 1.84809 15.9999 2.65"
                              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="22,4 12,14.01 9,11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>Imagen subida correctamente</p>
                </div>
            </div>
        `);
    }

    function updateProgressCounters() {
        $('.poxica-product').each(function() {
            const $product = $(this);
            const $uploadedCount = $product.find('.poxica-uploaded-count');
            const totalCount = parseInt($product.find('.poxica-total-count').text());
            const uploadedCount = $product.find('.poxica-unit-completed').length;
            
            $uploadedCount.text(uploadedCount);

            // Check if product is complete
            if (uploadedCount >= totalCount) {
                if (!$product.find('.poxica-complete-badge').length) {
                    $product.find('.poxica-product-progress').append('<span class="poxica-complete-badge">Completado</span>');
                }
            }
        });

        // Update overall progress
        const totalUploaded = $('.poxica-unit-completed').length;
        const totalNeeded = $('.poxica-unit').length;
        const progressPercent = totalNeeded > 0 ? (totalUploaded / totalNeeded) * 100 : 0;

        $('.poxica-progress-fill').css('width', progressPercent + '%');
        $('.poxica-progress-text').text(`${totalUploaded} de ${totalNeeded} imágenes subidas`);
    }

    // Global functions
    window.poxicaCloseModal = function() {
        $('.poxica-modal').hide();
    };

    window.poxicaShowModal = function(type, message) {
        if (type === 'success') {
            $('#poxica-success-message').text(message);
            $('#poxica-success-modal').show();
        } else {
            $('#poxica-error-message').text(message);
            $('#poxica-error-modal').show();
        }
    };

    window.poxicaCheckAllComplete = function() {
        const pendingUnits = $('.poxica-unit:not(.poxica-unit-completed)');
        if (pendingUnits.length === 0) {
            poxicaShowModal('success', poxica_ajax.strings.all_complete);
            setTimeout(function() {
                location.reload();
            }, 3000);
        }
    };

})(jQuery);