<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Publish_batches_model
 * Model for managing publish batches and batch items
 */
class Publish_batches_model extends CI_Model
{
    protected $batch_table = 'publish_batches';
    protected $item_table = 'publish_batch_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Create a new publish batch
     * @param array $data Batch data
     * @return int Batch ID or false on failure
     */
    public function create_batch($data)
    {
        $this->db->insert($this->batch_table, $data);
        return $this->db->insert_id();
    }

    /**
     * Create a new batch item
     * @param array $data Item data
     * @return int Item ID or false on failure
     */
    public function create_batch_item($data)
    {
        $this->db->insert($this->item_table, $data);
        return $this->db->insert_id();
    }

    /**
     * Get batch by ID
     * @param int $batch_id
     * @param int $user_id
     * @return array|null
     */
    public function get_batch($batch_id, $user_id = null)
    {
        $this->db->where('id', $batch_id);
        if ($user_id !== null) {
            $this->db->where('user_id', $user_id);
        }
        return $this->db->get($this->batch_table)->row_array();
    }

    /**
     * Get batch items
     * @param int $batch_id
     * @return array
     */
    public function get_batch_items($batch_id)
    {
        return $this->db->where('batch_id', $batch_id)
                       ->order_by('id', 'ASC')
                       ->get($this->item_table)
                       ->result_array();
    }

    /**
     * Get user batches
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_user_batches($user_id, $limit = 50)
    {
        return $this->db->where('user_id', $user_id)
                       ->order_by('created_at', 'DESC')
                       ->limit($limit)
                       ->get($this->batch_table)
                       ->result_array();
    }

    /**
     * Update batch status
     * @param int $batch_id
     * @param string $status
     * @return bool
     */
    public function update_batch_status($batch_id, $status)
    {
        $this->db->where('id', $batch_id);
        return $this->db->update($this->batch_table, [
            'status' => $status,
            'updated_at' => gmdate('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update batch item status
     * @param int $item_id
     * @param string $status
     * @return bool
     */
    public function update_item_status($item_id, $status)
    {
        $this->db->where('id', $item_id);
        return $this->db->update($this->item_table, [
            'status' => $status,
            'updated_at' => gmdate('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete batch and its items
     * @param int $batch_id
     * @param int $user_id
     * @return bool
     */
    public function delete_batch($batch_id, $user_id)
    {
        // Verify ownership
        $batch = $this->get_batch($batch_id, $user_id);
        if (!$batch) {
            return false;
        }

        // Start transaction
        $this->db->trans_start();

        // Delete items
        $this->db->where('batch_id', $batch_id)->delete($this->item_table);

        // Delete batch
        $this->db->where('id', $batch_id)->where('user_id', $user_id)->delete($this->batch_table);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Get batch statistics
     * @param int $batch_id
     * @return array
     */
    public function get_batch_stats($batch_id)
    {
        $query = $this->db->select('status, COUNT(*) as count')
                         ->where('batch_id', $batch_id)
                         ->group_by('status')
                         ->get($this->item_table);

        $stats = [];
        foreach ($query->result_array() as $row) {
            $stats[$row['status']] = (int)$row['count'];
        }

        return $stats;
    }
}