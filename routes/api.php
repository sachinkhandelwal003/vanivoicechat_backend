<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\InviteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\FrameController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\MomentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ThemeController;
use App\Http\Controllers\Api\CustomerSupportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\AppPageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\VipController;
use App\Http\Controllers\Api\WCLevelController;
use App\Http\Controllers\Api\MedalController;
use App\Http\Controllers\Api\RelationshipController;
use App\Http\Controllers\Api\RoleManagementController;
use App\Http\Controllers\Api\RoomMusicController;
use App\Http\Controllers\Api\RedEnvelopeController;
use App\Http\Controllers\Api\RoomRewardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


Route::get('/', function () {
    return response()->json([
        'message' => "Api Working Fine."
    ]);
});

Route::get('clear-all', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('storage:link');
    return response()->json([
        'status' => true,
        'message' => 'Clear All'
    ]);
});

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/auth/google', [AuthController::class, 'login']);
Route::post('/auth/login-by-uid', [AuthController::class, 'loginByUid']);
Route::post('/auth/login-by-phone', [AuthController::class, 'loginByPhone']);
Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp']);
Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);

Route::get('entry', [StoreController::class, 'getEntry']);
Route::get('themes', [StoreController::class, 'getThemes']);
Route::get('chat-bubbles', [StoreController::class, 'getChatBubbles']);
Route::get('filter-options', [StoreController::class, 'filterOptions']);
Route::get('frames', [StoreController::class, 'getFrames']);
Route::get('store-uids', [StoreController::class, 'getStoreUids']);
Route::post('filter-store-uids', [StoreController::class, 'filterStoreUids']);

Route::get('svip-list', [VipController::class, 'getSvipList']);
Route::get('vip-list', [VipController::class, 'getVipList']);

// Route::post('/agora/media-pull/webhook', [RoomMusicController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

    Route::get('entry-tags', [StoreController::class, 'getEntryTags']);
    Route::get('profile-cards', [StoreController::class, 'getDataCards']);
    Route::get('voices', [StoreController::class, 'getVoices']);
    Route::post('/send-user-gift', [StoreController::class, 'sendUserGift']);
    Route::get('/my-items/{type}', [StoreController::class, 'myItems']);
    Route::post('/use-item', [StoreController::class, 'useMyItem']);
    Route::post('/buy-item', [StoreController::class, 'buyItem']);

    Route::get('/invite-cms', [InviteController::class, 'cmsData']);

    Route::post('/store/password', [AuthController::class, 'setPassword']);
    Route::post('/bind-email', [AuthController::class, 'bindEmail']);
    Route::post('/verify-bind-email', [AuthController::class, 'verifyBindEmail']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/profile/registration', [UserController::class, 'profileRegistration']);
    Route::get('/user/profile', [UserController::class, 'getUserDetails']);
    Route::get('/is-email-bind', [UserController::class, 'isEmailBind']);
    Route::post('/follow-user', [UserController::class, 'followUser']);
    Route::get('/my-following', [UserController::class, 'myFollowing']);
    Route::get('/my-fans', [UserController::class, 'myFans']);
    Route::post('/visit-profile', [UserController::class, 'visitProfile']);
    Route::get('/profile-visitors', [UserController::class, 'profileVisitors']);
    Route::get('/profile-stats', [UserController::class, 'profileStats']);

    Route::get('/my-posts', [UserController::class, 'getUserPosts']);
    Route::get('/profile-overview', [UserController::class, 'getProfileOverview']);
    Route::post('/album-upload', [UserController::class, 'uploadAlbum']);
    Route::get('/get-album', [UserController::class, 'getMyAlbum']);
    Route::get('/get-user-relationships', [UserController::class, 'getUserRelationships']);

    Route::delete('/album/{id}', [UserController::class, 'deleteAlbum']);
    Route::get('get/profile/details', [UserController::class, 'getProfile']);
    Route::post('/profile/update', [UserController::class, 'updateProfile']);

    Route::post('/create/room', [RoomController::class, 'createRoom']);
    Route::post('/update/room-seat', [RoomController::class, 'updateRoomSeat']);
    Route::get('/get/room', [RoomController::class, 'getAuthUserRoom']);
    Route::get('/get/gifts', [RoomController::class, 'getGiftList']);
    Route::post('/send-gift', [RoomController::class, 'sendGift']);
    Route::get('/get/room/details', [RoomController::class, 'getRoomDetails']);
    Route::get('/get/userlevel', [RoomController::class, 'userLevel']);
    Route::post('room/join', [RoomController::class, 'join']);
    Route::post('room/leave', [RoomController::class, 'leave']);
    Route::get('room/{roomId}/online-count', [RoomController::class, 'count']);
    Route::post('room/take-seat', [RoomController::class, 'takeSeat']);
    Route::post('room/change-seat', [RoomController::class, 'changeSeat']);
    Route::post('room/leave-seat', [RoomController::class, 'leaveSeat']);
    Route::post('room/take-or-change-seat', [RoomController::class, 'takeOrChangeSeat']);
    Route::post('/room/agora-token', [RoomController::class, 'agoraToken']);
    Route::post('/room/message/send', [RoomController::class, 'sendMessage']);
    Route::post('/room/room-profile-update', [RoomController::class, 'updateRoomBasicInfo']);
    Route::post('lock-unlock-seat', [RoomController::class, 'toggleLockSeat']);
    Route::post('mute-seat', [RoomController::class, 'toggleMuteSeat']);
    Route::get('/room/{roomId}/room-presense-userlist', [RoomController::class, 'getRoomUsersList']);
    Route::post('/room/ban-user', [RoomController::class, 'bannedUserFromRoom']);
    Route::get('/room/{roomId}/banned-list', [RoomController::class, 'getBannedUsersList']);
    Route::post('/update-seat-mic-status', [RoomController::class, 'updateSeatMicStatus']);
    Route::post('handle-mic-invite', [RoomController::class, 'handleMicInvite']);
    Route::post('invite-to-mic', [RoomController::class, 'inviteToMic']);

    Route::post('/update-room-user-role', [RoomController::class, 'updateRoomUserRole']);
    Route::get('/room/room-admins', [RoomController::class, 'getRoomAdmins']);
    Route::post('/room/room-lock', [RoomController::class, 'lockRoom']);
    Route::post('/room/room-unlock', [RoomController::class, 'unlockRoom']);
    Route::post('/room/room-setting-access', [RoomController::class, 'updateRoomAccess']);
    Route::post('/room/remove-user-from-seat', [RoomController::class, 'removeUserFromSeat']);
    Route::get('/room/room-message-list', [RoomController::class, 'getRoomMessages']);
    Route::post('room/clear-messages', [RoomController::class, 'clearRoomMessages']);

    Route::get('/room/room-effect-setting', [RoomController::class, 'getEffectSettings']);
    Route::post('room/update-room-effect-setting', [RoomController::class, 'updateEffectSettings']);
    Route::get('/room/room-members', [RoomController::class, 'getRoomMembers']);


    Route::post('room-music/add-song', [RoomMusicController::class, 'addSong']);
    Route::get('room-music/list', [RoomMusicController::class, 'musicList']);
    Route::post('room-music/play', [RoomMusicController::class, 'playSong']);
    Route::post('room-music/pause', [RoomMusicController::class, 'pauseSong']);
    Route::post('room-music/update-options', [RoomMusicController::class, 'updateMusicOptions']);
    Route::post('room-music/update-volume', [RoomMusicController::class, 'updateVolume']);
    Route::post('room-music/resume', [RoomMusicController::class, 'resumeSong']);
    Route::post('room-music/seek', [RoomMusicController::class, 'seekSong']);
    Route::post('room-music/delete-song', [RoomMusicController::class, 'deleteSong']);
    Route::post('/room-music/song-finished', [RoomMusicController::class, 'onSongFinished']);

    Route::get('room-reward/{room_id}', [RoomRewardController::class, 'roomRewardDetails']);
    Route::post('room-reward/claim', [RoomRewardController::class, 'claimRoomReward']);



    Route::get('wc-levels', [WCLevelController::class, 'getLevels']);

    Route::get('/medals', [MedalController::class, 'index']);

    Route::get('/bd-list', [RoleManagementController::class, 'bdListByAdmin']);
    Route::get('/admin/agency-list', [RoleManagementController::class, 'agencyListByAdmin']);
    Route::post('/invite-bd', [RoleManagementController::class, 'inviteBd']);
    Route::post('/invite-agency', [RoleManagementController::class, 'inviteAgency']);
    Route::post('/invite/update-status', [RoleManagementController::class, 'updateInviteStatus']);

    Route::get('/banner', [HomeController::class, 'banner']);
    Route::get('/top-charm', [HomeController::class, 'topCharms']);
    Route::get('/top-wealth', [HomeController::class, 'topWealth']);
    Route::get('/top-room', [HomeController::class, 'topRoom']);

    Route::get('/coin-packages', [PaymentController::class, 'getCoinPackages']);
    Route::post('/buy-coins', [PaymentController::class, 'buyCoinPackage']);

    Route::post('/create-family', [FamilyController::class, 'createFamily']);
    Route::get('/top-families', [FamilyController::class, 'topFamilies']);
    Route::get('/family-ranks', [FamilyController::class, 'familyRank']);
    Route::get('/family-ranks/{rankId}/levels', [FamilyController::class, 'familyRankLevels']);
    Route::get('/family-levels/{levelId}/benefits', [FamilyController::class, 'levelBenefits']);
    Route::post('/join-family', [FamilyController::class, 'joinFamily']);
    Route::post('/approve-join-family', [FamilyController::class, 'approveJoinRequest']);
    Route::post('/leave-family', [FamilyController::class, 'leaveFamily']);
    Route::get('/pending-family-request', [FamilyController::class, 'pendingFamilyRequests']);
    Route::get('/family-members-list', [FamilyController::class, 'familyMembersList']);
    Route::post('/remove-family-member', [FamilyController::class, 'removeFamilyMember']);
    Route::post('/family/set-admin', [FamilyController::class, 'setAsAdmin']);
    Route::post('/family/set-member', [FamilyController::class, 'setAsMember']);
    Route::get('/family/my-family', [FamilyController::class, 'myFamily']);
    Route::get('/family/family-edit-data', [FamilyController::class, 'familyEditData']);
    Route::post('/family/update', [FamilyController::class, 'updateFamily']);
    Route::get('/family/details/{family_id}', [FamilyController::class, 'familyDetails']);

    Route::get('/hot-rooms', [HomeController::class, 'hotRooms']);
    Route::get('/new-rooms', [HomeController::class, 'newRooms']);
    Route::post('/store-room-visit', [HomeController::class, 'storeRoomVisit']);
    Route::get('/get-room-visited-list', [HomeController::class, 'getRoomVisitedList']);
    Route::post('/follow-room', [HomeController::class, 'followRoom']);
    Route::post('/unfollow-room', [HomeController::class, 'unfollowRoom']);
    Route::get('/get-following-room-list', [HomeController::class, 'getFollowingRoomList']);
    Route::post('/join-room', [HomeController::class, 'joinRoom']);
    Route::post('/unjoin-room', [HomeController::class, 'unjoinRoom']);
    Route::get('/get-joined-rooms-list', [HomeController::class, 'getJoinedRoomsList']);
    Route::post('/send-broadcast', [HomeController::class, 'sendBroadcast']);
    Route::get('/get-broadcast-list', [HomeController::class, 'listBroadcasts']);
    Route::get('/get-broadcast-price', [HomeController::class, 'broadcastPrice']);
    Route::get('/search', [HomeController::class, 'search']);
    Route::get('rules/{type}', [HomeController::class, 'getRules']);


    Route::get('/get-theme-lists', [ThemeController::class, 'themeList']);
    Route::post('/buy-theme', [ThemeController::class, 'buyTheme']);
    Route::get('/get-own-theme', [ThemeController::class, 'getOwnTheme']);
    Route::post('/room/use-theme', [ThemeController::class, 'useTheme']);
    Route::post('/upload-theme', [ThemeController::class, 'uploadUserTheme']);

    Route::get('/get-frame-lists', [FrameController::class, 'frameList']);
    Route::post('/buy-frame', [FrameController::class, 'buyFrame']);

    Route::post('/posts', [MomentController::class, 'store']);
    Route::get('/hot-posts', [MomentController::class, 'hotPosts']);
    Route::get('/new-posts', [MomentController::class, 'newPosts']);
    Route::get('/topic-lists', [MomentController::class, 'topiclist']);
    Route::post('/topic/like-toggle', [MomentController::class, 'toggleTopicLike']);
    Route::get('/topics/{topicId}/posts', [MomentController::class, 'topicPosts']);
    Route::post('/comment/add', [MomentController::class, 'addComment']);
    Route::post('/comment/delete', [MomentController::class, 'deleteComment']);
    Route::post('/post/like-toggle', [MomentController::class, 'togglePostLike']);
    Route::get('/post/{id}/details', [MomentController::class, 'postDetails']);
    Route::post('/post/hide', [MomentController::class, 'hidePost']);
    Route::post('/post/report', [MomentController::class, 'reportPost']);
    Route::post('/comment/comment-like', [MomentController::class, 'toggleCommentLike']);
    Route::post('/post/send-gift', [MomentController::class, 'postGift']);

    Route::post('/message/send-request', [MessageController::class, 'sendRequest']);
    Route::post('/message/accept-request', [MessageController::class, 'acceptRequest']);
    Route::post('/message/reject-request', [MessageController::class, 'rejectRequest']);
    Route::get('/message/get-friend-request-list', [MessageController::class, 'friendRequestList']);
    Route::get('/message/get-friend-list', [MessageController::class, 'friendList']);
    Route::post('/message/send-message', [MessageController::class, 'sendMessage']);
    Route::get('/message/recent-chat-list', [MessageController::class, 'recentChats']);
    Route::get('/message/chat/messages/{friendId}', [MessageController::class, 'chatMessages']);
    Route::get('/message/official-notification', [MessageController::class, 'officialNotification']);
    Route::delete('/message/delete-message/{id}', [MessageController::class, 'deleteMessage']);
    Route::post('/message/report-user', [MessageController::class, 'reportUser']);
    Route::post('/message/block-user', [MessageController::class, 'blockUser']);
    Route::post('/message/unblock-user', [MessageController::class, 'unblockUser']);
    Route::get('/message/blocked-user-list', [MessageController::class, 'blockedUsersList']);

    Route::get('/notifications', [NotificationController::class, 'getNotifications']);

    Route::post('support/send-message', [MessageController::class, 'sendSupportMessage']);

    Route::get('/support/support-user', [CustomerSupportController::class, 'getSupportUser']);
    Route::post('/support/start-conversation', [CustomerSupportController::class, 'startChat']);
    Route::post('/support/send-message', [CustomerSupportController::class, 'sendMessage']);
    Route::get('/support/messages/{id}', [CustomerSupportController::class, 'getMessages']);
    Route::get('/support/conversations', [CustomerSupportController::class, 'supportConversations']);
    Route::delete('/support/message/delete/{id}', [CustomerSupportController::class, 'deleteMessage']);

    Route::get('/feedback-list', [SettingController::class, 'index']);
    Route::post('/feedback/store', [SettingController::class, 'store']);

    Route::get('/privacy-policy', [AppPageController::class, 'privacyPolicy']);
    Route::get('/user-agreement', [AppPageController::class, 'userAgreement']);
    Route::get('/payment-agreement', [AppPageController::class, 'paymentAgreement']);
    Route::get('/about-us', [AppPageController::class, 'aboutUs']);

    Route::get('relationship-items', [RelationshipController::class, 'index']);
    Route::post('relationships/invite', [RelationshipController::class, 'sendInvite']);
    Route::post('relationships/respond', [RelationshipController::class, 'respondInvite']);
    Route::get('relationships/invitations', [RelationshipController::class, 'getInvitations']);
    Route::get('/relationship/invite-preview', [RelationshipController::class, 'relationInvitePreview']);


    Route::get('red-envelope/config', [RedEnvelopeController::class, 'config']);
    Route::post('red-envelope/create', [RedEnvelopeController::class, 'createRedEnvelope']);
    Route::post('red-envelope/claim', [RedEnvelopeController::class, 'claimRedEnvelope']);
    Route::post('red-envelope/details', [RedEnvelopeController::class, 'redEnvelopeDetails']);
});


Route::any('{path}', function () {
    return response()->json([
        'status' => false,
        'message' => 'Api not found..!!'
    ], 404);
})->where('path', '.*');
