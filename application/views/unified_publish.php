<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->lang->line('unified_publish_title') ?? 'نشر موحد' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/unified_publish.css') ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0"><?= $this->lang->line('unified_publish_title') ?? 'نشر موحد' ?></h1>
                    <a href="<?= site_url('reels/pages') ?>" class="btn btn-outline-secondary">
                        <?= $this->lang->line('back_to_pages') ?? 'العودة للصفحات' ?>
                    </a>
                </div>

                <!-- Alert Messages -->
                <?php if ($this->session->flashdata('msg')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $this->session->flashdata('msg') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('msg_success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $this->session->flashdata('msg_success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Main Form -->
                <form id="publishForm" action="<?= site_url('publication/create_batch') ?>" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Left Column: Settings -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?= $this->lang->line('batch_settings') ?? 'إعدادات الدفعة' ?></h5>
                                </div>
                                <div class="card-body">
                                    <!-- Platform Selection -->
                                    <div class="mb-3">
                                        <label class="form-label"><?= $this->lang->line('platform') ?? 'المنصة' ?></label>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="platform" id="platform_facebook" value="facebook" checked>
                                            <label class="btn btn-outline-primary" for="platform_facebook">
                                                <?= $this->lang->line('facebook') ?? 'فيسبوك' ?>
                                            </label>
                                            <input type="radio" class="btn-check" name="platform" id="platform_instagram" value="instagram">
                                            <label class="btn btn-outline-primary" for="platform_instagram">
                                                <?= $this->lang->line('instagram') ?? 'إنستغرام' ?>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Batch Title -->
                                    <div class="mb-3">
                                        <label for="batchTitle" class="form-label"><?= $this->lang->line('batch_title') ?? 'عنوان الدفعة' ?></label>
                                        <input type="text" class="form-control" id="batchTitle" name="title" required
                                               placeholder="<?= $this->lang->line('batch_title_placeholder') ?? 'أدخل عنوان للدفعة' ?>">
                                    </div>

                                    <!-- Pages Selection -->
                                    <div class="mb-3">
                                        <label class="form-label"><?= $this->lang->line('target_pages') ?? 'الصفحات المستهدفة' ?></label>
                                        <div class="pages-list" style="max-height: 200px; overflow-y: auto;">
                                            <?php foreach ($pages as $page): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input page-checkbox" type="checkbox" 
                                                           value="<?= htmlspecialchars($page['fb_page_id']) ?>" 
                                                           id="page_<?= htmlspecialchars($page['fb_page_id']) ?>">
                                                    <label class="form-check-label" for="page_<?= htmlspecialchars($page['fb_page_id']) ?>">
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?= htmlspecialchars($page['page_picture'] ?? 'https://graph.facebook.com/' . $page['fb_page_id'] . '/picture?type=small') ?>" 
                                                                 class="rounded-circle me-2" width="24" height="24" alt="">
                                                            <span><?= htmlspecialchars($page['page_name']) ?></span>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Global Settings -->
                                    <div class="mb-3">
                                        <label class="form-label"><?= $this->lang->line('global_settings') ?? 'الإعدادات العامة' ?></label>
                                        
                                        <!-- Default Publish Mode -->
                                        <div class="mb-2">
                                            <label class="form-label small"><?= $this->lang->line('default_publish_mode') ?? 'وضع النشر الافتراضي' ?></label>
                                            <select class="form-select form-select-sm" id="defaultPublishMode">
                                                <option value="immediate"><?= $this->lang->line('immediate') ?? 'فوري' ?></option>
                                                <option value="scheduled"><?= $this->lang->line('scheduled') ?? 'مجدول' ?></option>
                                            </select>
                                        </div>

                                        <!-- Default Schedule Time -->
                                        <div class="mb-2" id="defaultScheduleTimeContainer" style="display: none;">
                                            <label class="form-label small"><?= $this->lang->line('default_schedule_time') ?? 'وقت الجدولة الافتراضي' ?></label>
                                            <input type="datetime-local" class="form-control form-control-sm" id="defaultScheduleTime">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: File Upload and Items -->
                        <div class="col-lg-8">
                            <!-- File Upload Area -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><?= $this->lang->line('file_upload') ?? 'رفع الملفات' ?></h5>
                                </div>
                                <div class="card-body">
                                    <div id="dropzone" class="dropzone">
                                        <div class="dz-message">
                                            <div class="text-center">
                                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                <h5><?= $this->lang->line('drop_files_here') ?? 'اسحب الملفات هنا أو انقر للتصفح' ?></h5>
                                                <p class="text-muted">
                                                    <?= $this->lang->line('supported_formats') ?? 'الصيغ المدعومة: MP4, MOV, JPG, PNG' ?>
                                                    <br>
                                                    <?= $this->lang->line('max_file_size') ?? 'حد أقصى' ?>: <?= number_format($max_file_size / (1024*1024), 0) ?>MB
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Items -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><?= $this->lang->line('upload_items') ?? 'عناصر الرفع' ?></h5>
                                    <span class="badge bg-secondary" id="itemsCount">0</span>
                                </div>
                                <div class="card-body">
                                    <div id="itemsContainer" class="upload-items-container">
                                        <div class="text-center text-muted py-4" id="noItemsMessage">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p><?= $this->lang->line('no_files_selected') ?? 'لم يتم اختيار ملفات بعد' ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-outline-danger me-2" id="clearAllBtn">
                                    <?= $this->lang->line('clear_all') ?? 'مسح الكل' ?>
                                </button>
                                <button type="submit" class="btn btn-primary" id="publishBtn" disabled>
                                    <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
                                    <?= $this->lang->line('create_batch') ?? 'إنشاء الدفعة' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Result Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $this->lang->line('batch_result') ?? 'نتيجة الدفعة' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="resultContent">
                    <!-- Result content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= $this->lang->line('close') ?? 'إغلاق' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script src="<?= base_url('assets/js/unified_publish.js') ?>"></script>

    <script>
        // Initialize with configuration
        window.UnifiedPublish.init({
            maxFileSize: <?= $max_file_size ?>,
            createBatchUrl: '<?= site_url('publication/create_batch') ?>',
            allowedExtensions: <?= json_encode(['mp4', 'mov', 'mkv', 'm4v', 'jpg', 'jpeg', 'png']) ?>,
            lang: {
                dropFilesHere: '<?= $this->lang->line('drop_files_here') ?? 'اسحب الملفات هنا' ?>',
                fileTooBig: '<?= $this->lang->line('file_too_big') ?? 'الملف كبير جداً' ?>',
                invalidFileType: '<?= $this->lang->line('invalid_file_type') ?? 'نوع ملف غير مدعوم' ?>',
                removeFile: '<?= $this->lang->line('remove_file') ?? 'حذف الملف' ?>',
                description: '<?= $this->lang->line('description') ?? 'الوصف' ?>',
                publishMode: '<?= $this->lang->line('publish_mode') ?? 'وضع النشر' ?>',
                immediate: '<?= $this->lang->line('immediate') ?? 'فوري' ?>',
                scheduled: '<?= $this->lang->line('scheduled') ?? 'مجدول' ?>',
                scheduleTime: '<?= $this->lang->line('schedule_time') ?? 'وقت الجدولة' ?>',
                processing: '<?= $this->lang->line('processing') ?? 'جاري المعالجة...' ?>',
                success: '<?= $this->lang->line('success') ?? 'تم بنجاح' ?>',
                error: '<?= $this->lang->line('error') ?? 'خطأ' ?>',
                batchCreated: '<?= $this->lang->line('batch_created') ?? 'تم إنشاء الدفعة بنجاح' ?>',
                scheduledItemsCreated: '<?= $this->lang->line('scheduled_items_created') ?? 'تم إنشاء عناصر مجدولة' ?>'
            }
        });
    </script>
</body>
</html>