<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = 'welcome/not_found';


/* ==============      User Controller Start     ========= */

$route['registration'] = 'user/signup';
$route['social_media_access'] = 'user/socialmedia_login_signup';
$route['login'] = 'user/signin';
// $route['mail_verification'] = 'user/mail_verify';
$route['forgot_password'] = 'user/user_forgot_password';
$route['reset_password'] = 'user/user_reset_password';
$route['otp_verification'] = 'user/otp_verify';
$route['otp_resend'] = 'user/resend_otp';
$route['logout'] = 'user/user_logout';

/* ==============      User Controller End     ========= */


/* ==============      Profile Controller Start     ========= */

$route['profile'] = 'profile/user_profile';
$route['album'] = 'profile/user_album';
$route['block'] = 'profile/user_block';
$route['friends'] = 'profile/user_friends';
$route['location_update'] = 'profile/update_user_location';
$route['calender_events'] = 'profile/user_calender_events';

/* ==============      Profile Controller End     ========= */


/* ==============      Event Controller Start     ========= */

$route['event'] = 'event/user_event';
$route['event_invite_list'] = 'event/user_event_invite_list';

/* ==============      Event Controller End     ========= */


/* ==============      Subscription Controller Start - old     ========= */

// $route['subscription_plans'] = 'subscription/subscription_list';
// $route['subscription_activation'] = 'subscription/user_subscription_activation';

/* ==============      Subscription Controller End - start    ========= */

/* ==============      Subscription Controller Start - new     ========= */

$route['subscription_activation'] = 'subscription/user_subscription_activation';
$route['subscription_details'] = 'subscription/user_subscription_details';

/* ==============      Subscription Controller End - new     ========= */

/* ==============      Turfmate Controller Start     ========= */

$route['turfmate_questions'] = 'turfmate/user_turfmate_questions';
$route['turfmate_userlist'] = 'turfmate/user_turfmate_userlist';
$route['turfmate_profile'] = 'turfmate/user_turfmate_profile';
$route['turfmate_album'] = 'turfmate/user_turfmate_album';
$route['turfmate_invite'] = 'turfmate/user_turfmate_invite';

/* ==============      Turfmate Controller End     ========= */

/* ==============      Notification Start     ========= */

$route['notifications'] = 'notification/user_notifications';

/* ==============      Notification End     ========= */

/* ==============      Settings Start     ========= */

$route['settings'] = 'settings/user_settings';
$route['change_password'] = 'settings/user_change_password';
$route['blocking'] = 'settings/user_blocking';
$route['login_update'] = 'settings/user_login_update';
$route['login_otp_verification'] = 'settings/user_otp_verification';

/* ==============      Settings End     ========= */


/* ==============      Sample push notification start     ========= */

$route['sample_notification'] = 'profile/sample_push_notification';

/* ==============      Sample push notification End     ========= */


/* ==============      Review Start     ========= */

$route['review_list'] = 'review/user_review_list';
$route['review'] = 'review/user_review';
$route['reply'] = 'review/user_reply';

/* ==============      Review End     ========= */

/* ==============      Chat Start old    ========= */

// $route['group'] = 'chat/user_group';
// $route['friends_list'] = 'chat/user_friends_list';
// $route['chat_media'] = 'chat/user_chat_media';
// $route['conversation_list'] = 'chat/user_conversation_list';
// $route['conversation_history'] = 'chat/user_conversation_history';
// $route['send_message'] = 'message/user_send_message';

/* ==============      Chat End old    ========= */

/* ==============      Chat Start     ========= */

$route['group'] = 'chat/user_group';
$route['friends_list'] = 'chat/user_friends_list';
$route['local_message'] = 'chat/user_local_message';
$route['local_chat_conversation'] = 'chat/user_local_chat_conversation';
$route['chatmedia_restriction'] = 'chat/user_local_chatmedia_restriction';

/* ==============      Chat End     ========= */

/* ==============      Chat Media Start     ========= */

$route['chat_media'] = 'chat_media/user_chat_media';

/* ==============      Chat Media End     ========= */

/* ==============      Chat Media Start     ========= */

$route['expire'] = 'expire/user_expire';

/* ==============      Chat Media End     ========= */


/* ==============      Game Start     ========= */

$route['game_hangman'] = 'game/user_game_hangman';
$route['hangman_notification'] = 'game/user_hangman_notification';
$route['game_tictactoe'] = 'game/user_game_tictactoe';


// Tictactoe action routes
$route['tictactoe_activities'] = 'tictactoe/user_tictactoe_activities';

// ajax routes
$route['game_update'] = 'tictactoe/user_game_update';
$route['update_game_status'] = 'tictactoe/user_update_game_status';
$route['game_end'] = 'tictactoe/user_game_end';


/* ==============      Game End     ========= */


/* ==============      Referral Bonus Start     ========= */

$route['referral_initiate'] = 'referral/user_referral_initiate';
$route['referral_credits'] = 'referral/user_referral_credits';
$route['redeem_credits'] = 'referral/user_redeem_credits';

/* ==============      Referral Bonus End     ========= */