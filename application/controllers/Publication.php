<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Publication Controller
 * Handles unified publish functionality for Facebook and Instagram
 * Supports batch creation, file uploads, and scheduling integration
 */
class Publication extends CI_Controller
{
    const UPLOAD_DIR = 'uploads/publish_batches/';
    const ALLOWED_EXTENSIONS = ['mp4', 'mov', 'mkv', 'm4v', 'jpg', 'jpeg', 'png'];
    const MIN_FILE_SIZE_BYTES = 50 * 1024; // 50KB
    const MAX_FILE_SIZE_BYTES = 100 * 1024 * 1024; // 100MB

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Publish_batches_model', 'publish_model');
        $this->load->model('Facebook_pages_model', 'pages_model');
        $this->load->library(['session', 'upload']);
        $this->load->helper(['url', 'form', 'security', 'file']);
        $this->load->database();
        $this->lang->load('unified_publish', $this->session->userdata('language') ?? 'ar');
    }

    /**
     * Display unified publish interface
     */
    public function index()
    {
        $this->require_login();
        $user_id = (int)$this->session->userdata('user_id');
        
        // Get user's Facebook pages
        $pages = $this->pages_model->get_pages_by_user($user_id);
        if (!$pages) {
            $this->session->set_flashdata('msg', $this->lang->line('no_pages_found'));
            redirect('reels/pages');
            return;
        }

        $data = [
            'pages' => $pages,
            'max_file_size' => self::MAX_FILE_SIZE_BYTES
        ];
        
        $this->load->view('unified_publish', $data);
    }

    /**
     * Create batch endpoint
     * POST /publication/create_batch
     */
    public function create_batch()
    {
        $this->require_login();
        
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $user_id = (int)$this->session->userdata('user_id');
        $platform = $this->input->post('platform', true);
        $title = $this->input->post('title', true);
        $items_json = $this->input->post('items', true);

        // Validate inputs
        if (!in_array($platform, ['facebook', 'instagram'])) {
            $this->send_json(['ok' => false, 'errors' => ['Invalid platform']], 400);
            return;
        }

        if (empty($title)) {
            $this->send_json(['ok' => false, 'errors' => ['Title is required']], 400);
            return;
        }

        $items = json_decode($items_json, true);
        if (!is_array($items) || empty($items)) {
            $this->send_json(['ok' => false, 'errors' => ['No items provided']], 400);
            return;
        }

        // Validate files
        if (empty($_FILES['files']['name'])) {
            $this->send_json(['ok' => false, 'errors' => ['No files uploaded']], 400);
            return;
        }

        try {
            // Create upload directory
            $upload_dir = FCPATH . self::UPLOAD_DIR;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }

            // Process files
            $uploaded_files = $this->process_file_uploads();
            if (empty($uploaded_files)) {
                $this->send_json(['ok' => false, 'errors' => ['File upload failed']], 400);
                return;
            }

            // Create batch
            $batch_data = [
                'user_id' => $user_id,
                'platform' => $platform,
                'title' => $title,
                'status' => 'pending',
                'created_at' => gmdate('Y-m-d H:i:s')
            ];

            $batch_id = $this->publish_model->create_batch($batch_data);
            if (!$batch_id) {
                $this->send_json(['ok' => false, 'errors' => ['Failed to create batch']], 500);
                return;
            }

            // Create batch items and handle scheduling
            $created_items = [];
            $scheduled_created = 0;

            foreach ($items as $index => $item) {
                if (!isset($uploaded_files[$index])) {
                    continue; // Skip if file upload failed
                }

                $file_path = $uploaded_files[$index];
                $media_kind = $this->get_media_kind($file_path);

                $item_data = [
                    'batch_id' => $batch_id,
                    'file_path' => $file_path,
                    'description' => $item['description'] ?? '',
                    'publish_mode' => $item['publish_mode'] ?? 'immediate',
                    'scheduled_time' => isset($item['scheduled_time']) ? gmdate('Y-m-d H:i:s', strtotime($item['scheduled_time'])) : null,
                    'media_kind' => $media_kind,
                    'status' => 'pending',
                    'created_at' => gmdate('Y-m-d H:i:s')
                ];

                $item_id = $this->publish_model->create_batch_item($item_data);
                if ($item_id) {
                    $created_items[] = $item_id;

                    // If scheduled, create entry in scheduled_reels table
                    if ($item['publish_mode'] === 'scheduled' && !empty($item['scheduled_time'])) {
                        $scheduled_success = $this->create_scheduled_reel($user_id, $item, $file_path, $platform);
                        if ($scheduled_success) {
                            $scheduled_created++;
                        }
                    }
                }
            }

            $this->send_json([
                'ok' => true,
                'batch_id' => $batch_id,
                'created_items' => $created_items,
                'scheduled_created' => $scheduled_created,
                'errors' => []
            ]);

        } catch (Exception $e) {
            $this->send_json(['ok' => false, 'errors' => [$e->getMessage()]], 500);
        }
    }

    /**
     * Process file uploads
     */
    private function process_file_uploads()
    {
        $uploaded_files = [];
        $upload_dir = FCPATH . self::UPLOAD_DIR;

        if (!isset($_FILES['files']['name']) || !is_array($_FILES['files']['name'])) {
            return $uploaded_files;
        }

        $file_count = count($_FILES['files']['name']);

        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $_FILES['files']['name'][$i];
            $file_tmp = $_FILES['files']['tmp_name'][$i];
            $file_error = $_FILES['files']['error'][$i];
            $file_size = $_FILES['files']['size'][$i];

            // Skip if upload error
            if ($file_error !== UPLOAD_ERR_OK || empty($file_name) || !is_file($file_tmp)) {
                continue;
            }

            // Validate file size
            if ($file_size < self::MIN_FILE_SIZE_BYTES || $file_size > self::MAX_FILE_SIZE_BYTES) {
                continue;
            }

            // Validate extension
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_EXTENSIONS)) {
                continue;
            }

            // Generate safe filename
            $safe_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_name);
            $unique_name = 'batch_' . time() . '_' . $i . '_' . mt_rand(1000, 9999) . '_' . $safe_name;

            // Move file
            if (move_uploaded_file($file_tmp, $upload_dir . $unique_name)) {
                $uploaded_files[$i] = self::UPLOAD_DIR . $unique_name; // Store relative path
            }
        }

        return $uploaded_files;
    }

    /**
     * Get media kind based on file extension
     */
    private function get_media_kind($file_path)
    {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if (in_array($ext, ['mp4', 'mov', 'mkv', 'm4v'])) {
            return 'video';
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return 'image';
        }
        
        return 'unknown';
    }

    /**
     * Create scheduled reel entry for cron processing
     */
    private function create_scheduled_reel($user_id, $item, $file_path, $platform)
    {
        // Get user's pages - for now, use first page as default
        // TODO: Extend to support page selection per item
        $pages = $this->pages_model->get_pages_by_user($user_id);
        if (empty($pages)) {
            return false;
        }

        $page_id = $pages[0]['fb_page_id']; // Use first page for now

        $scheduled_data = [
            'user_id' => $user_id,
            'fb_page_id' => $page_id,
            'video_path' => $file_path,
            'description' => $item['description'] ?? '',
            'scheduled_time' => gmdate('Y-m-d H:i:s', strtotime($item['scheduled_time'])),
            'status' => 'pending',
            'attempt_count' => 0,
            'processing' => 0,
            'created_at' => gmdate('Y-m-d H:i:s')
        ];

        // Add media_type if column exists (for Instagram/stories support)
        if ($this->db->field_exists('media_type', 'scheduled_reels')) {
            $media_kind = $this->get_media_kind($file_path);
            $scheduled_data['media_type'] = ($platform === 'instagram' && $media_kind === 'video') ? 'ig_reel' : 'reel';
        }

        $this->db->insert('scheduled_reels', $scheduled_data);
        return $this->db->insert_id() > 0;
    }

    /**
     * Require user login
     */
    private function require_login()
    {
        if (!$this->session->userdata('user_id')) {
            $redirect = rawurlencode(current_url());
            redirect('home/login?redirect=' . $redirect);
            exit;
        }
    }

    /**
     * Send JSON response
     */
    private function send_json($data, $status_code = 200)
    {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}