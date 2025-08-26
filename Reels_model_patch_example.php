<?php
// مثال (لا تستبدل موديلك إن كان مختلفاً) – فقط وضح كيف تضيف فلتر المنصة
class Reels_model_patch_example extends CI_Model {
    public function get_facebook_reels($user_id, $limit=50) {
        return $this->db->where('user_id',$user_id)
                        ->where('platform','facebook')
                        ->order_by('created_at','DESC')
                        ->limit($limit)
                        ->get('reels')->result_array();
    }
    public function get_instagram_media($user_id, $limit=50) {
        return $this->db->where('user_id',$user_id)
                        ->where('platform','instagram')
                        ->order_by('created_at','DESC')
                        ->limit($limit)
                        ->get('reels')->result_array();
    }
}