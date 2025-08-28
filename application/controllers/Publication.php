<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Publication Controller (Unified Publisher)
 */
class Publication extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Publish_batches_model');
        $this->load->library(['session']);
        $this->load->helper(['url','form','text','file']);
        $this->load->database();
    }

    private function require_login()
    {
        $uid = (int)$this->session->userdata('user_id');
        if ($uid <= 0) {
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(401)->set_content_type('application/json','utf-8');
                echo json_encode(['ok'=>false,'error'=>'login_required']);
                exit;
            }
            redirect('home/login?redirect='.rawurlencode(current_url()));
            exit;
        }
        return $uid;
    }

    public function upload()
    {
        $uid = $this->require_login();
        if (!file_exists(APPPATH.'views/unified_publish.php')) {
            header('Content-Type: text/html; charset=utf-8');
            echo "<h3>Unified Publish UI</h3><p>View not implemented yet. Create: application/views/unified_publish.php</p>";
            return;
        }
        $this->load->view('unified_publish');
    }

    public function create_batch()
    {
        $uid = $this->require_login();

        $raw = trim(file_get_contents('php://input'));
        $data = null;
        $contentType = $this->input->server('CONTENT_TYPE') ?: '';

        if ($raw !== '' && strpos($contentType,'application/json') !== false) {
            $data = json_decode($raw, true);
        }
        if (!is_array($data)) {
            $post = $this->input->post();
            $data = is_array($post) ? $post : [];
            if (!empty($data['items']) && !is_array($data['items'])) {
                $decoded = json_decode($data['items'], true);
                if (is_array($decoded)) $data['items'] = $decoded;
            }
        }

        if (!is_array($data)) {
            return $this->respond_json(['ok'=>false,'error'=>'invalid_payload'],400);
        }

        $platform = isset($data['platform']) ? trim($data['platform']) : null;
        if (!$platform || !in_array($platform, ['facebook','instagram'], true)) {
            return $this->respond_json(['ok'=>false,'error'=>'invalid_platform'],400);
        }
        $title = isset($data['title']) ? trim($data['title']) : null;
        $itemsRaw = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

        if (!$itemsRaw) {
            return $this->respond_json(['ok'=>false,'error'=>'no_items'],400);
        }

        $filePaths = [];
        if (!empty($_FILES['files'])) {
            $files = $_FILES['files'];
            $count = is_array($files['name']) ? count($files['name']) : 0;
            $destDir = FCPATH . 'uploads/publish_batches/';
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0775, true);
            }
            for ($i = 0; $i < $count; $i++) {
                $err = $files['error'][$i];
                if ($err !== UPLOAD_ERR_OK) {
                    $filePaths[$i] = null;
                    continue;
                }
                $tmp = $files['tmp_name'][$i];
                $origName = $files['name'][$i];
                if (!is_file($tmp)) { $filePaths[$i] = null; continue; }
                $safe = preg_replace('/[^A-Za-z0-9_\-\.\(\)]/','_',basename($origName));
                $newName = 'batch_'.date('Ymd_His').'_'.$uid.'_'.substr(md5($safe.microtime(true).rand()),0,8).'_'.$i.'.'.pathinfo($safe,PATHINFO_EXTENSION);
                $full = $destDir . $newName;
                if (!move_uploaded_file($tmp, $full)) {
                    $filePaths[$i] = null;
                    continue;
                }
                $filePaths[$i] = 'uploads/publish_batches/' . $newName;
            }
        }

        $normalized = [];
        $idx = 0;
        foreach ($itemsRaw as $it) {
            if (!is_array($it)) continue;
            if (isset($it['target_id']) && !isset($it['target_ids'])) $it['target_ids'] = [$it['target_id']];
            if (isset($it['comments']) && !is_array($it['comments'])) {
                $c = json_decode($it['comments'], true);
                if (is_array($c)) $it['comments'] = $c;
            }
            $it['file_path'] = isset($filePaths[$idx]) ? $filePaths[$idx] : ($it['file_path'] ?? null);
            $normalized[] = $it;
            $idx++;
        }

        $res = $this->Publish_batches_model->create_batch($uid, $platform, $title, $normalized);
        if (!$res['ok']) {
            return $this->respond_json(['ok'=>false,'errors'=>$res['errors']],500);
        }

        $batch_id = (int)$res['batch_id'];
        $itemsInserted = $this->Publish_batches_model->get_batch($batch_id, $uid)['items'] ?? [];

        $scheduledInserted = 0;
        foreach ($itemsInserted as $it) {
            $pmode = $it['publish_mode'] ?? 'immediate';
            $scheduled_time = $it['scheduled_time'] ?? null;
            if ($pmode === 'scheduled' && $scheduled_time) {
                $row = [
                    'user_id' => $uid,
                    'fb_page_id' => $it['target_id'] ?? null,
                    'video_path' => $it['file_path'] ?? null,
                    'description' => $it['caption'] ?? '',
                    'scheduled_time' => $scheduled_time,
                    'original_local_time' => null,
                    'original_offset_minutes' => 0,
                    'original_timezone' => null,
                    'status' => 'pending',
                    'attempt_count' => 0,
                    'processing' => 0,
                    'created_at' => gmdate('Y-m-d H:i:s')
                ];
                $cols = $this->db->query("SHOW COLUMNS FROM scheduled_reels LIKE 'media_type'")->result_array();
                if (!empty($cols)) {
                    $row['media_type'] = $it['media_kind'] ?? null;
                }
                $this->db->insert('scheduled_reels', $row);
                $scheduledInserted++;
            }
        }

        return $this->respond_json([
            'ok' => true,
            'batch_id' => $batch_id,
            'created_items' => $res['created'],
            'scheduled_created' => $scheduledInserted,
            'errors' => $res['errors']
        ],200);
    }

    private function respond_json($arr, $code = 200)
    {
        $this->output->set_status_header($code);
        $this->output->set_content_type('application/json', 'utf-8');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        return;
    }
}