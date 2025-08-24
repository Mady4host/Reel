<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "home";
$route['404_override'] = 'home/error_404';
$route['send_instagram_broadcast'] = 'send_instagram_broadcast/index';
// AJAX endpoints
$route['reels/ajax/toggle_favorite'] = 'reels/ajax_toggle_favorite';
$route['reels/ajax/bulk_action']     = 'reels/ajax_bulk_action';
$route['reels/ajax/sync_page']       = 'reels/ajax_sync_page';
$route['reels/ajax/scheduled_list']  = 'reels/ajax_scheduled_list';
$route['reels/ajax/unlink_page']     = 'reels/ajax_unlink_page';
$route['instagram/hashtags_trend'] = 'instagram/hashtags_trend';
/* End of file routes.php */
$route['reels/upload_chunk'] = 'reels/upload_chunk';
/* Location: ./application/config/routes.php */