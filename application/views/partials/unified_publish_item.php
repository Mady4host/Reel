<div class="upload-item" data-file-index="{{fileIndex}}">
    <div class="row">
        <!-- File Preview -->
        <div class="col-md-3">
            <div class="file-preview">
                <div class="preview-container">
                    {{#if isVideo}}
                        <video class="preview-media" controls>
                            <source src="{{fileUrl}}" type="{{fileType}}">
                            <?= $this->lang->line('video_not_supported') ?? 'متصفحك لا يدعم تشغيل الفيديو' ?>
                        </video>
                    {{else}}
                        <img class="preview-media" src="{{fileUrl}}" alt="{{fileName}}">
                    {{/if}}
                </div>
                <div class="file-info">
                    <small class="text-muted">{{fileName}}</small>
                    <br>
                    <small class="text-muted">{{fileSize}}</small>
                </div>
            </div>
        </div>

        <!-- Item Settings -->
        <div class="col-md-8">
            <div class="item-settings">
                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label"><?= $this->lang->line('description') ?? 'الوصف' ?></label>
                    <textarea class="form-control item-description" rows="3" 
                              placeholder="<?= $this->lang->line('description_placeholder') ?? 'أدخل وصف للمنشور...' ?>">{{description}}</textarea>
                </div>

                <!-- Publish Mode -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="mb-3">
                            <label class="form-label"><?= $this->lang->line('publish_mode') ?? 'وضع النشر' ?></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check publish-mode-radio" 
                                       name="publish_mode_{{fileIndex}}" id="immediate_{{fileIndex}}" 
                                       value="immediate" {{#if isImmediate}}checked{{/if}}>
                                <label class="btn btn-outline-success btn-sm" for="immediate_{{fileIndex}}">
                                    <?= $this->lang->line('immediate') ?? 'فوري' ?>
                                </label>
                                
                                <input type="radio" class="btn-check publish-mode-radio" 
                                       name="publish_mode_{{fileIndex}}" id="scheduled_{{fileIndex}}" 
                                       value="scheduled" {{#if isScheduled}}checked{{/if}}>
                                <label class="btn btn-outline-warning btn-sm" for="scheduled_{{fileIndex}}">
                                    <?= $this->lang->line('scheduled') ?? 'مجدول' ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Time (shown when scheduled is selected) -->
                    <div class="col-sm-6">
                        <div class="mb-3 schedule-time-container" style="{{#unless isScheduled}}display: none;{{/unless}}">
                            <label class="form-label"><?= $this->lang->line('schedule_time') ?? 'وقت الجدولة' ?></label>
                            <input type="datetime-local" class="form-control form-control-sm schedule-time-input" 
                                   value="{{scheduleTime}}" min="{{minDateTime}}">
                        </div>
                    </div>
                </div>

                <!-- Recurrence Options (for future implementation) -->
                <div class="recurrence-container" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label"><?= $this->lang->line('recurrence') ?? 'التكرار' ?></label>
                        <select class="form-select form-select-sm recurrence-select">
                            <option value="none"><?= $this->lang->line('no_recurrence') ?? 'بدون تكرار' ?></option>
                            <option value="daily"><?= $this->lang->line('daily') ?? 'يومي' ?></option>
                            <option value="weekly"><?= $this->lang->line('weekly') ?? 'أسبوعي' ?></option>
                            <option value="monthly"><?= $this->lang->line('monthly') ?? 'شهري' ?></option>
                        </select>
                    </div>
                </div>

                <!-- Media Type Detection -->
                <div class="mb-2">
                    <span class="badge bg-info media-type-badge">
                        {{#if isVideo}}
                            <?= $this->lang->line('video') ?? 'فيديو' ?>
                        {{else}}
                            <?= $this->lang->line('image') ?? 'صورة' ?>
                        {{/if}}
                    </span>
                    <span class="badge bg-secondary">{{fileExtension}}</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="col-md-1">
            <div class="item-actions text-end">
                <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" 
                        title="<?= $this->lang->line('remove_file') ?? 'حذف الملف' ?>">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>