/**
 * Unified Publish JavaScript
 * Handles file upload, item management, and batch creation
 */
window.UnifiedPublish = (function() {
    'use strict';

    let config = {};
    let dropzone = null;
    let uploadedFiles = [];
    let itemTemplate = '';

    // Initialize the unified publish interface
    function init(userConfig) {
        config = Object.assign({
            maxFileSize: 100 * 1024 * 1024, // 100MB
            createBatchUrl: '/publication/create_batch',
            allowedExtensions: ['mp4', 'mov', 'mkv', 'm4v', 'jpg', 'jpeg', 'png'],
            lang: {}
        }, userConfig);

        initializeDropzone();
        bindEvents();
        loadItemTemplate();
    }

    // Initialize Dropzone
    function initializeDropzone() {
        Dropzone.autoDiscover = false;

        dropzone = new Dropzone('#dropzone', {
            url: '/dev/null', // We'll handle upload ourselves
            autoProcessQueue: false,
            uploadMultiple: false,
            parallelUploads: 1,
            maxFilesize: config.maxFileSize / (1024 * 1024), // Convert to MB
            acceptedFiles: '.' + config.allowedExtensions.join(',.'),
            addRemoveLinks: true,
            dictDefaultMessage: config.lang.dropFilesHere || 'Drop files here or click to browse',
            dictFileTooBig: config.lang.fileTooBig || 'File is too big',
            dictInvalidFileType: config.lang.invalidFileType || 'Invalid file type',
            dictRemoveFile: config.lang.removeFile || 'Remove file',

            init: function() {
                this.on('addedfile', function(file) {
                    handleFileAdded(file);
                });

                this.on('removedfile', function(file) {
                    handleFileRemoved(file);
                });

                this.on('error', function(file, message) {
                    console.error('Dropzone error:', message);
                });
            }
        });
    }

    // Bind UI events
    function bindEvents() {
        // Default publish mode change
        document.getElementById('defaultPublishMode').addEventListener('change', function() {
            const isScheduled = this.value === 'scheduled';
            document.getElementById('defaultScheduleTimeContainer').style.display = isScheduled ? 'block' : 'none';
            
            // Update all existing items
            updateAllItemsPublishMode(this.value);
        });

        // Default schedule time change
        document.getElementById('defaultScheduleTime').addEventListener('change', function() {
            updateAllItemsScheduleTime(this.value);
        });

        // Form submission
        document.getElementById('publishForm').addEventListener('submit', handleFormSubmit);

        // Clear all button
        document.getElementById('clearAllBtn').addEventListener('click', clearAllItems);

        // Platform change
        document.querySelectorAll('input[name="platform"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateUIForPlatform(this.value);
            });
        });
    }

    // Load item template
    function loadItemTemplate() {
        // For now, we'll create the template programmatically
        // In a real implementation, you might fetch this from the server
        itemTemplate = createItemTemplate();
    }

    // Handle file added to dropzone
    function handleFileAdded(file) {
        const fileIndex = uploadedFiles.length;
        
        // Add to our files array
        uploadedFiles.push({
            file: file,
            index: fileIndex,
            description: '',
            publishMode: document.getElementById('defaultPublishMode').value || 'immediate',
            scheduleTime: document.getElementById('defaultScheduleTime').value || ''
        });

        // Create preview URL
        const fileUrl = URL.createObjectURL(file);
        
        // Add item to UI
        addItemToContainer(file, fileIndex, fileUrl);
        
        // Update UI state
        updateUIState();
    }

    // Handle file removed from dropzone
    function handleFileRemoved(file) {
        const fileIndex = uploadedFiles.findIndex(f => f.file === file);
        if (fileIndex !== -1) {
            uploadedFiles.splice(fileIndex, 1);
            removeItemFromContainer(fileIndex);
            updateFileIndices();
            updateUIState();
        }
    }

    // Add item to container
    function addItemToContainer(file, fileIndex, fileUrl) {
        const container = document.getElementById('itemsContainer');
        const noItemsMessage = document.getElementById('noItemsMessage');
        
        // Hide no items message
        if (noItemsMessage) {
            noItemsMessage.style.display = 'none';
        }

        // Create item element
        const itemElement = document.createElement('div');
        itemElement.className = 'upload-item mb-3';
        itemElement.setAttribute('data-file-index', fileIndex);
        
        const isVideo = isVideoFile(file.name);
        const fileSize = formatFileSize(file.size);
        const fileName = file.name;
        const fileExtension = getFileExtension(file.name);
        
        const defaultPublishMode = document.getElementById('defaultPublishMode').value || 'immediate';
        const defaultScheduleTime = document.getElementById('defaultScheduleTime').value || '';
        
        itemElement.innerHTML = createItemHTML({
            fileIndex: fileIndex,
            fileName: fileName,
            fileSize: fileSize,
            fileUrl: fileUrl,
            fileExtension: fileExtension,
            isVideo: isVideo,
            description: '',
            publishMode: defaultPublishMode,
            scheduleTime: defaultScheduleTime,
            minDateTime: getMinDateTime()
        });

        container.appendChild(itemElement);

        // Bind item events
        bindItemEvents(itemElement, fileIndex);
    }

    // Create item HTML
    function createItemHTML(data) {
        return `
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="file-preview">
                                <div class="preview-container">
                                    ${data.isVideo ? 
                                        `<video class="preview-media" controls>
                                            <source src="${data.fileUrl}" type="${data.file?.type || ''}">
                                            ${config.lang.videoNotSupported || 'Your browser does not support video playback'}
                                        </video>` :
                                        `<img class="preview-media" src="${data.fileUrl}" alt="${data.fileName}">`
                                    }
                                </div>
                                <div class="file-info mt-2">
                                    <small class="text-muted d-block">${data.fileName}</small>
                                    <small class="text-muted">${data.fileSize}</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="item-settings">
                                <div class="mb-3">
                                    <label class="form-label">${config.lang.description || 'Description'}</label>
                                    <textarea class="form-control item-description" rows="3" 
                                              placeholder="${config.lang.descriptionPlaceholder || 'Enter post description...'}">${data.description}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label">${config.lang.publishMode || 'Publish Mode'}</label>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check publish-mode-radio" 
                                                       name="publish_mode_${data.fileIndex}" id="immediate_${data.fileIndex}" 
                                                       value="immediate" ${data.publishMode === 'immediate' ? 'checked' : ''}>
                                                <label class="btn btn-outline-success btn-sm" for="immediate_${data.fileIndex}">
                                                    ${config.lang.immediate || 'Immediate'}
                                                </label>
                                                
                                                <input type="radio" class="btn-check publish-mode-radio" 
                                                       name="publish_mode_${data.fileIndex}" id="scheduled_${data.fileIndex}" 
                                                       value="scheduled" ${data.publishMode === 'scheduled' ? 'checked' : ''}>
                                                <label class="btn btn-outline-warning btn-sm" for="scheduled_${data.fileIndex}">
                                                    ${config.lang.scheduled || 'Scheduled'}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="mb-3 schedule-time-container" style="${data.publishMode === 'scheduled' ? '' : 'display: none;'}">
                                            <label class="form-label">${config.lang.scheduleTime || 'Schedule Time'}</label>
                                            <input type="datetime-local" class="form-control form-control-sm schedule-time-input" 
                                                   value="${data.scheduleTime}" min="${data.minDateTime}">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <span class="badge bg-info media-type-badge">
                                        ${data.isVideo ? (config.lang.video || 'Video') : (config.lang.image || 'Image')}
                                    </span>
                                    <span class="badge bg-secondary">${data.fileExtension}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="item-actions text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" 
                                        title="${config.lang.removeFile || 'Remove file'}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Bind events for individual items
    function bindItemEvents(itemElement, fileIndex) {
        // Publish mode change
        itemElement.querySelectorAll('.publish-mode-radio').forEach(function(radio) {
            radio.addEventListener('change', function() {
                const scheduleContainer = itemElement.querySelector('.schedule-time-container');
                if (this.value === 'scheduled') {
                    scheduleContainer.style.display = 'block';
                } else {
                    scheduleContainer.style.display = 'none';
                }
                
                // Update file data
                const fileData = uploadedFiles.find(f => f.index === fileIndex);
                if (fileData) {
                    fileData.publishMode = this.value;
                }
            });
        });

        // Schedule time change
        const scheduleTimeInput = itemElement.querySelector('.schedule-time-input');
        if (scheduleTimeInput) {
            scheduleTimeInput.addEventListener('change', function() {
                const fileData = uploadedFiles.find(f => f.index === fileIndex);
                if (fileData) {
                    fileData.scheduleTime = this.value;
                }
            });
        }

        // Description change
        const descriptionInput = itemElement.querySelector('.item-description');
        if (descriptionInput) {
            descriptionInput.addEventListener('input', function() {
                const fileData = uploadedFiles.find(f => f.index === fileIndex);
                if (fileData) {
                    fileData.description = this.value;
                }
            });
        }

        // Remove item
        const removeBtn = itemElement.querySelector('.remove-item-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                removeItem(fileIndex);
            });
        }
    }

    // Remove item
    function removeItem(fileIndex) {
        const fileData = uploadedFiles.find(f => f.index === fileIndex);
        if (fileData && dropzone) {
            dropzone.removeFile(fileData.file);
        }
    }

    // Remove item from container
    function removeItemFromContainer(fileIndex) {
        const itemElement = document.querySelector(`[data-file-index="${fileIndex}"]`);
        if (itemElement) {
            itemElement.remove();
        }

        // Show no items message if empty
        if (uploadedFiles.length === 0) {
            const noItemsMessage = document.getElementById('noItemsMessage');
            if (noItemsMessage) {
                noItemsMessage.style.display = 'block';
            }
        }
    }

    // Update file indices after removal
    function updateFileIndices() {
        uploadedFiles.forEach(function(fileData, index) {
            fileData.index = index;
            const itemElement = document.querySelector(`[data-file-index="${fileData.index}"]`);
            if (itemElement) {
                itemElement.setAttribute('data-file-index', index);
            }
        });
    }

    // Update UI state
    function updateUIState() {
        const itemsCount = uploadedFiles.length;
        const itemsCountBadge = document.getElementById('itemsCount');
        const publishBtn = document.getElementById('publishBtn');

        // Update count
        if (itemsCountBadge) {
            itemsCountBadge.textContent = itemsCount;
        }

        // Enable/disable publish button
        if (publishBtn) {
            publishBtn.disabled = itemsCount === 0 || !isFormValid();
        }
    }

    // Check if form is valid
    function isFormValid() {
        const title = document.getElementById('batchTitle')?.value?.trim();
        const selectedPages = document.querySelectorAll('.page-checkbox:checked');
        return title && selectedPages.length > 0;
    }

    // Update all items publish mode
    function updateAllItemsPublishMode(publishMode) {
        uploadedFiles.forEach(function(fileData) {
            fileData.publishMode = publishMode;
            
            const itemElement = document.querySelector(`[data-file-index="${fileData.index}"]`);
            if (itemElement) {
                const radio = itemElement.querySelector(`input[value="${publishMode}"]`);
                if (radio) {
                    radio.checked = true;
                }
                
                const scheduleContainer = itemElement.querySelector('.schedule-time-container');
                if (scheduleContainer) {
                    scheduleContainer.style.display = publishMode === 'scheduled' ? 'block' : 'none';
                }
            }
        });
    }

    // Update all items schedule time
    function updateAllItemsScheduleTime(scheduleTime) {
        uploadedFiles.forEach(function(fileData) {
            if (fileData.publishMode === 'scheduled') {
                fileData.scheduleTime = scheduleTime;
                
                const itemElement = document.querySelector(`[data-file-index="${fileData.index}"]`);
                if (itemElement) {
                    const scheduleInput = itemElement.querySelector('.schedule-time-input');
                    if (scheduleInput) {
                        scheduleInput.value = scheduleTime;
                    }
                }
            }
        });
    }

    // Update UI for platform
    function updateUIForPlatform(platform) {
        // Future implementation for platform-specific features
        console.log('Platform changed to:', platform);
    }

    // Handle form submission
    function handleFormSubmit(event) {
        event.preventDefault();

        if (uploadedFiles.length === 0) {
            alert(config.lang.noFilesSelected || 'No files selected');
            return;
        }

        if (!isFormValid()) {
            alert(config.lang.formValidationError || 'Please fill in all required fields');
            return;
        }

        const publishBtn = document.getElementById('publishBtn');
        const spinner = publishBtn.querySelector('.spinner-border');
        
        // Show loading state
        publishBtn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';
        publishBtn.textContent = config.lang.processing || 'Processing...';

        // Prepare form data
        const formData = new FormData();
        
        // Add basic form fields
        formData.append('platform', document.querySelector('input[name="platform"]:checked').value);
        formData.append('title', document.getElementById('batchTitle').value.trim());

        // Add files
        uploadedFiles.forEach(function(fileData) {
            formData.append('files[]', fileData.file);
        });

        // Prepare items data
        const items = uploadedFiles.map(function(fileData, index) {
            const itemElement = document.querySelector(`[data-file-index="${fileData.index}"]`);
            const description = itemElement?.querySelector('.item-description')?.value || '';
            const publishMode = itemElement?.querySelector('.publish-mode-radio:checked')?.value || 'immediate';
            const scheduleTime = itemElement?.querySelector('.schedule-time-input')?.value || '';

            return {
                index: index,
                description: description,
                publish_mode: publishMode,
                scheduled_time: scheduleTime
            };
        });

        formData.append('items', JSON.stringify(items));

        // Submit form
        fetch(config.createBatchUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            handleFormResponse(data);
        })
        .catch(error => {
            console.error('Error:', error);
            handleFormError(error);
        })
        .finally(() => {
            // Reset loading state
            publishBtn.disabled = false;
            if (spinner) spinner.style.display = 'none';
            publishBtn.textContent = config.lang.createBatch || 'Create Batch';
        });
    }

    // Handle form response
    function handleFormResponse(data) {
        if (data.ok) {
            showResultModal(true, data);
            clearAllItems();
        } else {
            showResultModal(false, data);
        }
    }

    // Handle form error
    function handleFormError(error) {
        showResultModal(false, { errors: [error.message || 'An error occurred'] });
    }

    // Show result modal
    function showResultModal(success, data) {
        const modal = new bootstrap.Modal(document.getElementById('resultModal'));
        const content = document.getElementById('resultContent');
        
        if (success) {
            content.innerHTML = `
                <div class="alert alert-success">
                    <h6>${config.lang.success || 'Success'}!</h6>
                    <p>${config.lang.batchCreated || 'Batch created successfully'}</p>
                    <ul>
                        <li>${config.lang.batchId || 'Batch ID'}: ${data.batch_id}</li>
                        <li>${config.lang.itemsCreated || 'Items created'}: ${data.created_items.length}</li>
                        <li>${config.lang.scheduledItemsCreated || 'Scheduled items'}: ${data.scheduled_created}</li>
                    </ul>
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h6>${config.lang.error || 'Error'}!</h6>
                    <ul>
                        ${data.errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `;
        }
        
        modal.show();
    }

    // Clear all items
    function clearAllItems() {
        if (dropzone) {
            dropzone.removeAllFiles();
        }
        uploadedFiles = [];
        document.getElementById('itemsContainer').innerHTML = '<div class="text-center text-muted py-4" id="noItemsMessage"><i class="fas fa-inbox fa-2x mb-2"></i><p>' + (config.lang.noFilesSelected || 'No files selected') + '</p></div>';
        updateUIState();
    }

    // Utility functions
    function isVideoFile(filename) {
        const videoExtensions = ['mp4', 'mov', 'mkv', 'm4v'];
        const ext = getFileExtension(filename);
        return videoExtensions.includes(ext);
    }

    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getMinDateTime() {
        const now = new Date();
        now.setMinutes(now.getMinutes() + 30); // Minimum 30 minutes in future
        return now.toISOString().slice(0, 16);
    }

    function createItemTemplate() {
        // Template would be loaded from server in real implementation
        return '';
    }

    // Public API
    return {
        init: init
    };
})();