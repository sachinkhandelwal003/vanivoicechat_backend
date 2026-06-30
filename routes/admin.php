<?php

use App\Http\Controllers\AccountBanController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\FeedBackController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\AppUserController;
use App\Http\Controllers\BdUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RewardInvitationRechargeController;
use App\Http\Controllers\RewardInvitingController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\UserAlbumController;
use App\Http\Controllers\UserBackpackController;
use App\Http\Controllers\UserBadgeController;
use App\Http\Controllers\UserMusicController;
use App\Http\Controllers\UserSpeakerController;
use App\Http\Controllers\UserVideoController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\WalletController;
use App\Routes\Profile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PremiumNumberController;
use App\Http\Controllers\FrameController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ChatBubbleController;
use App\Http\Controllers\CustomerSupportController;
use App\Http\Controllers\DataCardController;
use App\Http\Controllers\UserLevelController;
use App\Http\Controllers\EntryTagController;
use App\Http\Controllers\MomentController;
use App\Http\Controllers\PropsController;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\VipController;
use App\Http\Controllers\StoreUidsController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\OfficialNotificationController;
use App\Http\Controllers\CoinController;
use App\Http\Controllers\CoinSellerController;
use App\Http\Controllers\HostController;
use App\Http\Controllers\MedalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RelationshipItemController;
use App\Http\Controllers\SvipController;
use App\Http\Controllers\WCLevelController;
use App\Http\Controllers\RoomRewardSlabController;
use App\Http\Controllers\AppRuleController;
use App\Http\Controllers\TreasureLevelController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| Web Routes For Admin
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('clear-all', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('storage:link');
    return '<h1>Clear All</h1>';
});

Route::get('invite-index', [HomeController::class, 'storeIndex'])->name('store.index');
Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/delete-app-user', [HomeController::class, 'deleteAccount']);

// Admin & Sub-Admin Routes
Route::middleware(['auth', 'permission', 'authCheck', 'verified'])->group(function () {
    Profile::routes();
    Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('customer/support', [HomeController::class, 'customerSupport'])->name('support.index');

    Route::post('/support/send', [SupportChatController::class, 'send'])->name('support.send');
    Route::get('/support', [SupportChatController::class, 'index'])->name('support.index');
    Route::get('/support/messages/{id}', [SupportChatController::class, 'getMessages']);
    Route::get('/support/conversation-row/{id}', [SupportChatController::class, 'conversationRow']);

    // ----------------------- Role Routes ----------------------------------------------------
    Route::controller(RolesController::class)->name('roles')->group(function () {
        Route::get('roles', 'index')->middleware('isAllow:102,can_view');
        Route::post('roles', 'save')->middleware('isAllow:102,can_add');
        Route::put('roles', 'update')->middleware('isAllow:102,can_edit');
        Route::delete('roles', 'delete')->middleware('isAllow:102,can_delete');
        Route::get('roles/permission/{id}', 'permission')->name('.permission.view')->middleware('isAllow:102,can_edit');
        Route::put('roles/permission', 'permission_update')->name('.permission.update')->middleware('isAllow:102,can_edit');
    });

    // ----------------------- Admin and Sub Admin Routes ----------------------------------------------------
    Route::controller(UsersController::class)->group(function () {
        Route::get('users', 'index')->name('users')->middleware('isAllow:103,can_view');
        Route::get('users/add', 'add')->name('users.add')->middleware('isAllow:103,can_add');
        Route::post('users/add', 'save')->name('users.add')->middleware('isAllow:103,can_add');
        Route::get('users/{slug}', 'edit')->name('users.edit')->middleware('isAllow:103,can_edit');
        Route::post('users/{slug}', 'update')->name('users.edit')->middleware('isAllow:103,can_edit');
        Route::delete('users', 'delete')->name('users')->middleware('isAllow:103,can_delete');
        Route::get('users/permission/{id}', 'permission')->name('users.permission.view')->middleware('isAllow:103,can_edit');
        Route::put('users/permission', 'permission_update')->name('users.permission.update')->middleware('isAllow:103,can_edit');
    });

    // ----------------------- States Routes ----------------------------------------------------
    Route::controller(StateController::class)->name('states')->group(function () {
        Route::get('states', 'index')->middleware('isAllow:105,can_view');
        Route::post('states', 'save')->middleware('isAllow:105,can_add');
        Route::put('states', 'update')->middleware('isAllow:105,can_edit');
        Route::delete('states', 'delete')->middleware('isAllow:105,can_delete');
    });

    // ----------------------- City Routes ----------------------------------------------------
    Route::controller(CityController::class)->name('cities')->group(function () {
        Route::get('cities', 'index')->middleware('isAllow:106,can_view');
        Route::post('cities', 'save')->middleware('isAllow:106,can_add');
        Route::put('cities', 'update')->middleware('isAllow:106,can_edit');
        Route::delete('cities', 'delete')->middleware('isAllow:106,can_delete');
    });


    // ----------------------- CMS Routes ----------------------------------------------------
    Route::controller(CmsController::class)->group(function () {
        Route::get('cms', 'index')->name('cms')->middleware('isAllow:104,can_view');
        Route::get('cms/add', 'add')->name('cms.add')->middleware('isAllow:104,can_add');
        Route::post('cms/add', 'save')->name('cms.add')->middleware('isAllow:104,can_add');
        Route::get('cms/{id}', 'edit')->name('cms.edit')->middleware('isAllow:104,can_edit');
        Route::post('cms', 'slug')->name('cms.slug')->middleware('isAllow:104,can_edit');
        Route::post('cms/{id}', 'update')->name('cms.edit')->middleware('isAllow:104,can_edit');
        Route::delete('cms', 'delete')->name('cms')->middleware('isAllow:104,can_delete');
    });

    Route::controller(MedalController::class)->group(function () {
        Route::get('medals', 'index')->name('medals.index')->middleware('isAllow:104,can_view');
        Route::get('medals/add/{id?}', 'form')->name('medals.form')->middleware('isAllow:104,can_edit');
        Route::post('medals/add/{id?}', 'store')->name('medals.store')->middleware('isAllow:104,can_edit');
        Route::delete('medals/delete', 'delete')->name('medals.delete')->middleware('isAllow:104,can_delete');
    });

    Route::controller(AdminAccountController::class)->group(function () {
        Route::get('admin-account', 'index')->name('admin.account')->middleware('isAllow:104,can_view');
        Route::get('admin-account/add/{id?}', 'form')->name('admin.account.form')->middleware('isAllow:104,can_edit');
        Route::post('admin-account/add/{id?}', 'save')->name('admin.account.save')->middleware('isAllow:104,can_edit');
        Route::delete('admin-account/delete', 'delete')->name('admin.account.delete')->middleware('isAllow:104,can_delete');
    });

    Route::controller(AgencyController::class)->group(function () {
        Route::get('agency', 'index')->name('agency')->middleware('isAllow:104,can_view');
        Route::get('agency/add/{id?}', 'form')->name('agency.form')->middleware('isAllow:104,can_edit');
        Route::post('agency/add/{id?}', 'save')->name('agency.save')->middleware('isAllow:104,can_edit');
        Route::delete('agency/delete', 'delete')->name('agency.delete')->middleware('isAllow:104,can_delete');
    });

    Route::controller(HostController::class)->group(function () {
        Route::get('host', 'index')->name('host')->middleware('isAllow:104,can_view');
        Route::get('host/add/{id?}', 'form')->name('host.form')->middleware('isAllow:104,can_edit');
        Route::post('host/add/{id?}', 'save')->name('host.save')->middleware('isAllow:104,can_edit');
        Route::delete('host/delete', 'delete')->name('host.delete')->middleware('isAllow:104,can_delete');

        Route::get('host/transfer/{id}', 'transferForm')->name('host.transfer.form');
        Route::post('host/transfer/{id}', 'transferSave')->name('host.transfer.save');
    });

    Route::controller(BdUserController::class)->group(function () {
        Route::get('bd-user', 'index')->name('bd-user')->middleware('isAllow:104,can_view');
        Route::get('bd-user/add/{id?}', 'form')->name('bd-user.form')->middleware('isAllow:104,can_edit');
        Route::post('bd-user/add/{id?}', 'save')->name('bd-user.save')->middleware('isAllow:104,can_edit');
        Route::delete('bd-user/delete', 'delete')->name('bd-user.delete')->middleware('isAllow:104,can_delete');

        Route::get('bd-user/transfer/{id}', 'transferForm')->name('bd-user.transfer.form');
        Route::post('bd-user/transfer/{id}', 'transferSave')->name('bd-user.transfer.save');
    });

    Route::controller(CoinSellerController::class)->group(function () {
        Route::get('coin-seller', 'index')->name('coin_seller')->middleware('isAllow:104,can_view');
        Route::get('coin-seller/add/{id?}', 'form')->name('coin_seller.form')->middleware('isAllow:104,can_edit');
        Route::post('coin-seller/add/{id?}', 'save')->name('coin_seller.save')->middleware('isAllow:104,can_edit');
        Route::delete('coin-seller/delete', 'delete')->name('coin_seller.delete')->middleware('isAllow:104,can_delete');

        Route::post('coin-seller/toggle-merchant', 'toggleMerchant')->name('coin_seller.toggleMerchant')->middleware('isAllow:104,can_edit');
        Route::post('coin-seller/recharge', 'recharge')->name('coin_seller.recharge')->middleware('isAllow:104,can_edit');
        Route::post('coin-seller/deduct', 'deduct')->name('coin_seller.deduct')->middleware('isAllow:104,can_edit');
        Route::get('coin-seller-transactions', 'transactions')->name('coin_seller.transactions');

        Route::get('merchant', 'merchantIndex')->name('merchant')->middleware('isAllow:104,can_view');
        Route::get('merchant/add/{id?}', 'merchantForm')->name('merchant.form')->middleware('isAllow:104,can_add');
        Route::post('merchant/add/{id?}', 'merchantSave')->name('merchant.save')->middleware('isAllow:104,can_add');
        Route::post('merchant/remove', 'removeMerchant')->name('merchant.remove')->middleware('isAllow:104,can_delete');
        Route::delete('merchant/delete', 'merchantDelete')->name('merchant.delete')->middleware('isAllow:104,can_delete');
    });

    Route::controller(SvipController::class)->group(function () {
        Route::get('svip', 'index')->name('svip')->middleware('isAllow:104,can_view');
        Route::get('svip/add/{id?}', 'form')->name('svip.form')->middleware('isAllow:104,can_add');
        Route::post('svip/add', 'save')->name('svip.add')->middleware('isAllow:104,can_add');
        Route::delete('svip', 'delete')->name('svip.delete')->middleware('isAllow:104,can_delete');
        Route::post('svip/update/{id}', 'save')->name('svip.update')->middleware('isAllow:104,can_edit');

        Route::get('svip-privilege', 'privilegeList')->name('svip-privilege.list')->middleware('isAllow:104,can_view');
        Route::get('svip-privilege/add/{id?}', 'privilegeForm')->name('svip-privilege')->middleware('isAllow:104,can_edit');
        Route::post('svip-privilege/add/{id?}', 'privilegeAdd')->name('svip-privilege.add')->middleware('isAllow:104,can_edit');
        Route::delete('svip-privilege/delete', 'privilegeDelete')->name('svip-privilege.delete')->middleware('isAllow:104,can_delete');
    });

    // -------------------------------- VIP Routes --------------------------------
    Route::controller(VipController::class)->group(function () {
        Route::get('vip', 'index')->name('vip')->middleware('isAllow:104,can_view');
        Route::get('vip/add', 'add')->name('vip.add')->middleware('isAllow:104,can_add');
        Route::post('vip/add', 'save')->name('vip.add')->middleware('isAllow:104,can_add');
        Route::get('vip/{id}', 'edit')->name('vip.edit')->middleware('isAllow:104,can_edit');
        Route::post('vip/{id}', 'update')->name('vip.edit')->middleware('isAllow:104,can_edit');
        Route::delete('vip', 'delete')->name('vip')->middleware('isAllow:104,can_delete');

        Route::get('vip/privilege/{id}', 'privilegeIndex')->name('privilege.index')->middleware('isAllow:104,view');
        Route::get('vip/privilege/add/{id}', 'privilegeAdd')->name('privilege.add')->middleware('isAllow:104,can_add');
        Route::post('vip/privilege/save/{id}', 'privilegeSave')->name('privilege.save')->middleware('isAllow:104,can_add');
        Route::get('vip/privilege/edit/{id}', 'privilegeEdit')->name('privilege.edit')->middleware('isAllow:104,can_edit');
        Route::post('vip/privilege/update/{id}', 'privilegeUpdate')->name('privilege.update')->middleware('isAllow:104,can_edit');
        Route::delete('vip/privilege/delete', 'privilegeDelete')->name('privilege.delete')->middleware('isAllow:104,can_delete');
    });

    Route::controller(WCLevelController::class)->group(function () {
        Route::get('levels', 'index')->name('levels')->middleware('isAllow:104,can_view');
        Route::get('levels/add/{id?}', 'form')->name('levels.form')->middleware('isAllow:104,can_add');
        Route::post('levels/add/{id?}', 'save')->name('levels.add')->middleware('isAllow:104,can_add');
        Route::delete('levels/delete', 'delete')->name('levels.delete')->middleware('isAllow:104,can_delete');

        Route::get('level-setting/{type?}', 'settingForm')->name('level-setting.form');
        Route::post('level-setting/add', 'settingSave')->name('level-setting.add');
    });


    Route::controller(RewardInvitingController::class)->group(function () {
        Route::get('reward-inviting', 'index')->name('reward-inviting')->middleware('isAllow:104,can_view');
        Route::get('reward-inviting/add', 'add')->name('reward-inviting.add')->middleware('isAllow:104,can_add');
        Route::post('reward-inviting/add', 'save')->name('reward-inviting.add')->middleware('isAllow:104,can_add');
        Route::get('reward-inviting/{id}', 'edit')->name('reward-inviting.edit')->middleware('isAllow:104,can_edit');
        Route::post('reward-inviting', 'slug')->name('reward-inviting.slug')->middleware('isAllow:104,can_edit');
        Route::post('reward-inviting/{id}', 'update')->name('reward-inviting')->middleware('isAllow:104,can_edit');
        Route::delete('reward-inviting', 'delete')->name('reward-inviting')->middleware('isAllow:104,can_delete');
    });

    Route::controller(RewardInvitationRechargeController::class)->group(function () {
        Route::get('reward-invitation-recharge', 'index')->name('reward-invitation-recharge')->middleware('isAllow:104,can_view');
        Route::get('reward-invitation-recharge/add', 'add')->name('reward-invitation-recharge.add')->middleware('isAllow:104,can_add');
        Route::post('reward-invitation-recharge/add', 'save')->name('reward-invitation-recharge.add')->middleware('isAllow:104,can_add');
        Route::get('reward-invitation-recharge/{id}', 'edit')->name('reward-invitation-recharge')->middleware('isAllow:104,can_edit');
        Route::post('reward-invitation-recharge', 'slug')->name('reward-invitation-recharge.slug')->middleware('isAllow:104,can_edit');
        Route::post('reward-invitation-recharge/{id}', 'update')->name('reward-invitation-recharge.edit')->middleware('isAllow:104,can_edit');
        Route::delete('reward-invitation-recharge', 'delete')->name('reward-invitation-recharge')->middleware('isAllow:104,can_delete');
    });


    Route::controller(StaticPageController::class)->group(function () {

        // List
        Route::get('static-page', 'index')
            ->name('static-page')
            ->middleware('isAllow:104,can_view');

        // Add Form
        Route::get('static-page/create', 'add')->name('static-page.create')->middleware('isAllow:104,can_add');

        // Save
        Route::post('static-page/store', 'save')->name('static-page.store')->middleware('isAllow:104,can_add');

        // Edit Form
        Route::get('static-page/{id}/edit', 'edit')->name('static-page.edit')->middleware('isAllow:104,can_edit');

        // Update
        Route::post('static-page/{id}/update', 'update')->name('static-page.update')->middleware('isAllow:104,can_edit');

        // Delete
        Route::delete('static-page/{id}', 'delete')->name('static-page.delete')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Categories Routes ----------------------------------------------------
    Route::controller(CategoryController::class)->group(function () {
        Route::get('categories', 'index')->name('categories')->middleware('isAllow:104,can_view');
        Route::get('categories/add', 'add')->name('categories.add')->middleware('isAllow:104,can_add');
        Route::post('categories/add', 'save')->name('categories.add')->middleware('isAllow:104,can_add');
        Route::get('categories/{id}', 'edit')->name('categories.edit')->middleware('isAllow:104,can_edit');
        Route::post('categories', 'slug')->name('categories.slug')->middleware('isAllow:104,can_edit');
        Route::post('categories/{id}', 'update')->name('categories.update')->middleware('isAllow:104,can_edit');
        Route::delete('categories', 'delete')->name('categories')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Banner Routes ----------------------------------------------------
    Route::controller(BannerController::class)->group(function () {
        Route::get('banner', 'index')->name('banner')->middleware('isAllow:104,can_view');
        Route::get('banner/add', 'add')->name('banner.add')->middleware('isAllow:104,can_add');
        Route::post('banner/add', 'save')->name('banner.add')->middleware('isAllow:104,can_add');
        Route::get('banner/{id}', 'edit')->name('banner.edit')->middleware('isAllow:104,can_edit');
        Route::post('banner', 'slug')->name('banner.slug')->middleware('isAllow:104,can_edit');
        Route::post('banner/{id}', 'update')->name('banner.edit')->middleware('isAllow:104,can_edit');
        Route::delete('banner', 'delete')->name('banner')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Gift Routes ----------------------------------------------------
    Route::controller(GiftController::class)->group(function () {
        Route::get('gift', 'index')->name('gift')->middleware('isAllow:104,can_view');
        Route::get('gift/add', 'add')->name('gift.add')->middleware('isAllow:104,can_add');
        Route::post('gift/add', 'save')->name('gift.add')->middleware('isAllow:104,can_add');
        Route::get('gift/{id}', 'edit')->name('gift.edit')->middleware('isAllow:104,can_edit');
        Route::post('gift', 'slug')->name('gift.slug')->middleware('isAllow:104,can_edit');
        Route::post('gift/{id}', 'update')->name('gift.edit')->middleware('isAllow:104,can_edit');
        Route::delete('gift', 'delete')->name('gift')->middleware('isAllow:104,can_delete');


        Route::get('{id}/lucky-gift-setting', 'luckyGiftSettingindex')->name('lucky-gift-setting')->middleware('isAllow:104,can_view');
        Route::get('{id}/lucky-gift-setting/add', 'luckyGiftSettingAdd')->name('lucky-gift-setting.add')->middleware('isAllow:104,can_add');
        Route::post('{id}/lucky-gift-setting/add', 'luckyGiftSettingSave')->name('lucky-gift-setting.add')->middleware('isAllow:104,can_add');
        Route::get('lucky-gift-setting/{id}', 'luckyGiftSettingEdit')->name('lucky-gift-setting.edit')->middleware('isAllow:104,can_edit');
        Route::post('lucky-gift-setting/{id}', 'luckyGiftSettingUpdate')->name('lucky-gift-setting.edit')->middleware('isAllow:104,can_edit');
        Route::delete('lucky-gift-setting', 'luckyGiftSettingDelete')->name('lucky-gift-setting.delete')->middleware('isAllow:104,can_delete');


        Route::get('gift-records', 'giftRecords')->name('giftrecords');
        Route::get('gift/details/{id}',  'giftDetails')->name('gift.details');
    });

    // ----------------------- PremiumNumber Routes ----------------------------------------------------
    Route::controller(PremiumNumberController::class)->group(function () {
        Route::get('premium_number', 'index')->name('premium_number')->middleware('isAllow:104,can_view');
        Route::get('premium_number/add', 'add')->name('premium_number.add')->middleware('isAllow:104,can_add');
        Route::post('premium_number/add', 'save')->name('premium_number.add')->middleware('isAllow:104,can_add');
        Route::get('premium_number/{id}', 'edit')->name('premium_number.edit')->middleware('isAllow:104,can_edit');
        Route::post('premium_number', 'slug')->name('premium_number.slug')->middleware('isAllow:104,can_edit');
        Route::post('premium_number/{id}', 'update')->name('premium_number.edit')->middleware('isAllow:104,can_edit');
        Route::delete('premium_number', 'delete')->name('premium_number')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Frame Routes ----------------------------------------------------
    Route::controller(FrameController::class)->group(function () {
        Route::get('frame', 'index')->name('frame')->middleware('isAllow:104,can_view');
        Route::get('frame/add', 'add')->name('frame.add')->middleware('isAllow:104,can_add');
        Route::post('frame/add', 'save')->name('frame.add')->middleware('isAllow:104,can_add');
        Route::get('frame/{id}', 'edit')->name('frame.edit')->middleware('isAllow:104,can_edit');
        Route::post('frame', 'slug')->name('frame.slug')->middleware('isAllow:104,can_edit');
        Route::post('frame/{id}', 'update')->name('frame.edit')->middleware('isAllow:104,can_edit');
        Route::delete('frame', 'delete')->name('frame')->middleware('isAllow:104,can_delete');
    });


    // ----------------------- App Rules Routes ----------------------------------------------------
    Route::controller(AppRuleController::class)->group(function () {
        Route::get('app-rules',  'index')->name('app-rules.index')->middleware('isAllow:104,can_view');
        Route::get('app-rules/add', 'add')->name('app-rules.add')->middleware('isAllow:104,can_add');
        Route::post('app-rules/store',  'store')->name('app-rules.store')->middleware('isAllow:104,can_add');
        Route::get('app-rules/edit/{id}',  'edit')->name('app-rules.edit')->middleware('isAllow:104,can_edit');
        Route::post('app-rules/update/{id}',  'update')->name('app-rules.update')->middleware('isAllow:104,can_edit');
        Route::delete('app-rules/delete',  'destroy')->name('app-rules.destroy')->middleware('isAllow:104,can_delete');
        Route::get('rules/{id}/view', 'view')->name('admin.rules.view');
    });
    // ----------------------- Frame Routes ----------------------------------------------------
    Route::controller(TreasureLevelController::class)->group(function () {
        Route::get('treasure-levels', 'index')->name('treasure-levels.index');
        Route::get('treasure-levels/create', 'create')->name('treasure-levels.create');
        Route::post('treasure-levels/store',  'store')->name('treasure-levels.store');
        Route::get('treasure-levels/{id}/edit',  'edit')->name('treasure-levels.edit');
        Route::post('treasure-levels/{id}/update',  'update')->name('treasure-levels.update');
        Route::delete('treasure-levels/delete', 'destroy')->name('treasure-levels.destroy');
        Route::get('treasure-levels/get-reward-items','getRewardItems')->name('treasure-levels.getRewardItems');
    });

    // ----------------------- Cars Routes ----------------------------------------------------
    Route::controller(CarController::class)->group(function () {
        Route::get('cars', 'index')->name('cars')->middleware('isAllow:104,can_view');
        Route::get('cars/add', 'add')->name('cars.add')->middleware('isAllow:104,can_add');
        Route::post('cars/add', 'save')->name('cars.add')->middleware('isAllow:104,can_add');
        Route::get('cars/{id}', 'edit')->name('cars.edit')->middleware('isAllow:104,can_edit');
        Route::post('cars/{id}', 'update')->name('cars.edit')->middleware('isAllow:104,can_edit');
        Route::delete('cars', 'delete')->name('cars')->middleware('isAllow:104,can_delete');
    });

    Route::controller(RelationshipItemController::class)->group(function () {
        Route::get('relationship-item', 'index')->name('relationship.item')->middleware('isAllow:104,can_view');
        Route::get('relationship-item/add/{id?}', 'form')->name('relationship.item.form')->middleware('isAllow:104,can_add');
        Route::post('relationship-item/add', 'save')->name('relationship.item.add')->middleware('isAllow:104,can_add');
        Route::delete('relationship-item', 'delete')->name('relationship.item.delete')->middleware('isAllow:104,can_delete');
        Route::post('relationship-item/update/{id}', 'save')->name('relationship.item.update')->middleware('isAllow:104,can_edit');
    });

    // ----------------------- Customer Support Routes ----------------------------------------------------
    Route::controller(CustomerSupportController::class)->group(function () {
        Route::get('customer-support', 'index')->name('customer_support')->middleware('isAllow:104,can_view');
        Route::get('customer-support/form/{id?}', 'form')->name('customer_support.form')->middleware('isAllow:104,can_add');
        Route::post('customer-support/store', 'store')->name('customer_support.store')->middleware('isAllow:104,can_add');
        Route::delete('customer-support/delete', 'delete')->name('customer_support.delete')->middleware('isAllow:104,can_delete');
    });

    Route::post('/save-token', function (Request $request) {

        $token = $request->input('token');

        DB::table('users')
            ->where('id', Auth::id())
            ->update([
                'fcm_token' => $token
            ]);

        return response()->json([
            'success' => true
        ]);
    })->middleware('auth')->name('save.token');

    // ----------------------- Voice Routes ----------------------------------------------------
    Route::controller(VoiceController::class)->group(function () {
        Route::get('voice', 'index')->name('voice')->middleware('isAllow:104,can_view');
        Route::get('voice/add', 'add')->name('voice.add')->middleware('isAllow:104,can_add');
        Route::post('voice/add', 'save')->name('voice.add')->middleware('isAllow:104,can_add');
        Route::get('voice/{id}', 'edit')->name('voice.edit')->middleware('isAllow:104,can_edit');
        Route::post('voice/{id}', 'update')->name('voice.edit')->middleware('isAllow:104,can_edit');
        Route::delete('voice', 'delete')->name('voice')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Chat Bubble Routes ----------------------------------------------------
    Route::controller(ChatBubbleController::class)->group(function () {
        Route::get('chat-bubble', 'index')->name('chat.bubble')->middleware('isAllow:104,can_view');
        Route::get('chat-bubble/add', 'add')->name('chat.bubble.add')->middleware('isAllow:104,can_add');
        Route::post('chat-bubble/add', 'save')->name('chat.bubble.add')->middleware('isAllow:104,can_add');
        Route::get('chat-bubble/{id}', 'edit')->name('chat.bubble.edit')->middleware('isAllow:104,can_edit');
        Route::post('chat-bubble/{id}', 'update')->name('chat.bubble.edit')->middleware('isAllow:104,can_edit');
        Route::delete('chat-bubble', 'delete')->name('chat.bubble')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Entry Tag Routes ----------------------------------------------------
    Route::controller(EntryTagController::class)->group(function () {
        Route::get('entry-tag', 'index')->name('entry.tag')->middleware('isAllow:104,can_view');
        Route::get('entry-tag/add', 'add')->name('entry.tag.add')->middleware('isAllow:104,can_add');
        Route::post('entry-tag/add', 'save')->name('entry.tag.add')->middleware('isAllow:104,can_add');
        Route::get('entry-tag/{id}', 'edit')->name('entry.tag.edit')->middleware('isAllow:104,can_edit');
        Route::post('entry-tag/{id}', 'update')->name('entry.tag.edit')->middleware('isAllow:104,can_edit');
        Route::delete('entry-tag', 'delete')->name('entry.tag')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Data Card Routes ----------------------------------------------------
    Route::controller(DataCardController::class)->group(function () {
        Route::get('data-card', 'index')->name('data.card')->middleware('isAllow:104,can_view');
        Route::get('data-card/add', 'add')->name('data.card.add')->middleware('isAllow:104,can_add');
        Route::post('data-card/add', 'save')->name('data.card.add')->middleware('isAllow:104,can_add');
        Route::get('data-card/{id}', 'edit')->name('data.card.edit')->middleware('isAllow:104,can_edit');
        Route::post('data-card/{id}', 'update')->name('data.card.edit')->middleware('isAllow:104,can_edit');
        Route::delete('data-card', 'delete')->name('data.card')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Rank Routes ----------------------------------------------------
    Route::controller(StoreUidsController::class)->group(function () {
        Route::get('rank', 'index')->name('rank')->middleware('isAllow:104,can_view');
        Route::get('rank/add', 'add')->name('rank.add')->middleware('isAllow:104,can_add');
        Route::post('rank/add', 'save')->name('rank.add')->middleware('isAllow:104,can_add');
        Route::get('rank/{id}', 'edit')->name('rank.edit')->middleware('isAllow:104,can_edit');
        Route::post('rank/{id}', 'update')->name('rank.edit')->middleware('isAllow:104,can_edit');
        Route::delete('rank', 'delete')->name('rank')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Pattern Routes ----------------------------------------------------
    Route::controller(StoreUidsController::class)->group(function () {
        Route::get('pattern', 'patternIndex')->name('pattern')->middleware('isAllow:104,can_view');
        Route::get('pattern/add', 'patternAdd')->name('pattern.add')->middleware('isAllow:104,can_add');
        Route::post('pattern/add', 'patternSave')->name('pattern.add')->middleware('isAllow:104,can_add');
        Route::get('pattern/{id}', 'patternEdit')->name('pattern.edit')->middleware('isAllow:104,can_edit');
        Route::post('pattern/{id}', 'patternUpdate')->name('pattern.edit')->middleware('isAllow:104,can_edit');
        Route::delete('pattern', 'patternDelete')->name('pattern')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Store Uid Routes ----------------------------------------------------
    Route::controller(StoreUidsController::class)->group(function () {
        Route::get('store-uid', 'storeUidIndex')->name('store.uid')->middleware('isAllow:104,can_view');
        Route::get('store-uid/add', 'storeUidAdd')->name('store.uid.add')->middleware('isAllow:104,can_add');
        Route::post('store-uid/add', 'storeUidSave')->name('store.uid.add')->middleware('isAllow:104,can_add');
        Route::get('store-uid/{id}', 'storeUidEdit')->name('store.uid.edit')->middleware('isAllow:104,can_edit');
        Route::post('store-uid/{id}', 'storeUidUpdate')->name('store.uid.edit')->middleware('isAllow:104,can_edit');
        Route::delete('store-uid', 'storeUidDelete')->name('store.uid')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Coin Package Routes ----------------------------------------------------
    Route::controller(CoinController::class)->group(function () {
        Route::get('coin-package', 'index')->name('coin.package')->middleware('isAllow:104,can_view');
        Route::get('coin-package/add', 'add')->name('coin.package.add')->middleware('isAllow:104,can_add');
        Route::post('coin-package/add', 'save')->name('coin.package.add')->middleware('isAllow:104,can_add');
        Route::get('coin-package/{id}', 'edit')->name('coin.package.edit')->middleware('isAllow:104,can_edit');
        Route::post('coin-package/{id}', 'update')->name('coin.package.edit')->middleware('isAllow:104,can_edit');
        Route::delete('coin-package', 'delete')->name('coin.package')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Theme Routes ----------------------------------------------------
    Route::controller(ThemeController::class)->group(function () {
        Route::get('theme', 'index')->name('theme')->middleware('isAllow:104,can_view');
        Route::get('theme/add', 'add')->name('theme.add')->middleware('isAllow:104,can_add');
        Route::post('theme/add', 'save')->name('theme.add')->middleware('isAllow:104,can_add');
        Route::get('theme/{id}', 'edit')->name('theme.edit')->middleware('isAllow:104,can_edit');
        Route::post('theme', 'slug')->name('theme.slug')->middleware('isAllow:104,can_edit');
        Route::post('theme/{id}', 'update')->name('theme.edit')->middleware('isAllow:104,can_edit');
        Route::delete('theme', 'delete')->name('theme')->middleware('isAllow:104,can_delete');

        Route::get('theme/give/{theme_id}', 'give')->name('theme.give')->middleware('isAllow:104,can_add');
        Route::post('theme/save/give', 'giveSave')->name('theme.save.give')->middleware('isAllow:104,can_add');
    });

    // ----------------------- Broadcast Routes ----------------------------------------------------
    Route::controller(BroadcastController::class)->group(function () {
        Route::get('broadcast', 'index')->name('broadcast')->middleware('isAllow:104,can_view');
        Route::delete('broadcast', 'delete')->name('broadcast')->middleware('isAllow:104,can_delete');

        Route::get('broadcast-price', 'BroadcastPriceIndex')->name('broadcast-price')->middleware('isAllow:104,can_view');
        Route::get('broadcast-price/add', 'BroadcastPriceAdd')->name('broadcast-price.add')->middleware('isAllow:104,can_add');
        Route::post('broadcast-price/add', 'BroadcastPriceSave')->name('broadcast-price.add')->middleware('isAllow:104,can_add');
        Route::get('broadcast-price/{id}', 'BroadcastPriceEdit')->name('broadcast-price.edit')->middleware('isAllow:104,can_edit');
        Route::post('broadcast-price/{id}', 'BroadcastPriceUpdate')->name('broadcast-price.edit')->middleware('isAllow:104,can_edit');
        Route::delete('broadcast-price', 'BroadcastPriceDelete')->name('broadcast-price')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Room Routes ----------------------------------------------------
    Route::controller(RoomController::class)->group(function () {
        Route::get('room', 'index')->name('room')->middleware('isAllow:104,can_view');
        Route::get('room/add', 'add')->name('room.add')->middleware('isAllow:104,can_add');
        Route::post('room/add', 'save')->name('room.add')->middleware('isAllow:104,can_add');
        Route::get('room/{id}', 'edit')->name('room.edit')->middleware('isAllow:104,can_edit');
        Route::post('room', 'slug')->name('room.slug')->middleware('isAllow:104,can_edit');
        Route::post('room/{id}', 'update')->name('room.edit')->middleware('isAllow:104,can_edit');
        Route::delete('room', 'delete')->name('room')->middleware('isAllow:104,can_delete');

        Route::get('room-members/{room_id}', 'members')->name('room.members')->middleware('isAllow:104,can_view');
        Route::get('room-members-ajax/{room_id}', 'membersAjax')->name('room.members.ajax');
        Route::get('room-view/{id}', 'view')->name('room.view');
    });

    // --------------------------------- User Level Routes ---------------------------------
    Route::controller(UserLevelController::class)->group(function () {
        Route::get('user-level', 'index')->name('user.level')->middleware('isAllow:104,can_view');
        Route::get('user-level/add', 'add')->name('user.level.add')->middleware('isAllow:104,can_add');
        Route::post('user-level/add', 'save')->name('user.level.add')->middleware('isAllow:104,can_add');
        Route::get('user-level/{id}', 'edit')->name('user.level.edit')->middleware('isAllow:104,can_edit');
        Route::post('user-level/{id}', 'update')->name('user.level.edit')->middleware('isAllow:104,can_edit');
        Route::delete('user-level', 'delete')->name('user.level')->middleware('isAllow:104,can_delete');
    });

    // --------------------------------- Family Routes ---------------------------------
    Route::controller(FamilyController::class)->group(function () {
        Route::get('family', 'index')->name('family')->middleware('isAllow:104,can_view');
        Route::get('family/members/{id}', 'familyMember')->name('family.members')->middleware('isAllow:104,can_view');
        Route::delete('family/members/remove', 'familyMemberRemove')->name('family.members.remove')->middleware('isAllow:104,can_view');
        Route::get('family/togglestatus/{id}', 'toggleStatus')->name('family.toggleStatus')->middleware('isAllow:104,can_edit');

        Route::get('family/rank', 'rank')->name('family.rank')->middleware('isAllow:104,can_view');
        Route::get('family/rank/add', 'rankAdd')->name('family.rank.add')->middleware('isAllow:104,can_add');
        Route::post('family/rank/add', 'rankSave')->name('family.rank.add')->middleware('isAllow:104,can_add');
        Route::get('family/rank/{id}', 'rankEdit')->name('family.rank.edit')->middleware('isAllow:104,can_edit');
        Route::post('family/rank/{id}', 'rankUpdate')->name('family.rank.edit')->middleware('isAllow:104,can_edit');
        Route::delete('family/rank', 'rankDelete')->name('family.rank')->middleware('isAllow:104,can_delete');


        Route::get('family/{id}/level', 'level')->name('family.level')->middleware('isAllow:104,can_view');
        Route::get('family/{id}/level/add', 'levelAdd')->name('family.level.add')->middleware('isAllow:104,can_add');
        Route::post('family/{id}/level/add', 'levelSave')->name('family.level.add')->middleware('isAllow:104,can_add');
        Route::get('family/level/{id}', 'levelEdit')->name('family.level.edit')->middleware('isAllow:104,can_edit');
        Route::post('family/level/{id}', 'levelUpdate')->name('family.level.edit')->middleware('isAllow:104,can_edit');
        Route::delete('family/level', 'levelDelete')->name('family.level.delete')->middleware('isAllow:104,can_delete');

        Route::get('family/{id}/level/privilege', 'levelPrivilege')->name('family.level.privilege')->middleware('isAllow:104,can_view');
        Route::get('family/{id}/level/privilege/add', 'levelPrivilegeAdd')->name('family.level.privilege.add')->middleware('isAllow:104,can_add');
        Route::post('family/{id}/level/privilege/add', 'levelPrivilegeSave')->name('family.level.privilege.add')->middleware('isAllow:104,can_add');
        Route::get('family/level/privilege/{id}', 'levelPrivilegeEdit')->name('family.level.privilege.edit')->middleware('isAllow:104,can_edit');
        Route::post('family/level/privilege/{id}', 'levelPrivilegeUpdate')->name('family.level.privilege.edit')->middleware('isAllow:104,can_edit');
        Route::delete('family/level/privilege', 'levelPrivilegeDelete')->name('family.level.privilege.delete')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Officail notiffication Routes ----------------------------------------------------
    Route::controller(OfficialNotificationController::class)->name('official_notifications.')->group(function () {
        Route::get('official-notifications', 'index')->name('index')->middleware('isAllow:104,can_view');
        Route::get('official-notifications/create', 'create')->name('create')->middleware('isAllow:104,can_add');
        Route::post('official-notifications', 'store')->name('store')->middleware('isAllow:104,can_add');
        Route::get('official-notifications/{id}/edit', 'edit')->name('edit')->middleware('isAllow:104,can_edit');
        Route::put('official-notifications/{id}', 'update')->name('update')->middleware('isAllow:104,can_edit');
        Route::delete('official-notifications/{id}', 'destroy')->name('destroy')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Props Routes ----------------------------------------------------
    Route::controller(PropsController::class)->group(function () {
        Route::get('item/delivery', 'index')->name('props.item.delivery')->middleware('isAllow:104,can_view');
        Route::get('/props/get-items/{type}', 'getItems');
        Route::post('/props/item-delivery/store', 'store')->name('props.delivery.store');
    });

    // ----------------------- Cars Routes ----------------------------------------------------
    Route::controller(MomentController::class)->group(function () {
        Route::get('topic-category', 'index')->name('topic.category')->middleware('isAllow:104,can_view');
        Route::get('topic-category/add', 'add')->name('topic.category.add')->middleware('isAllow:104,can_add');
        Route::post('topic-category/add', 'save')->name('topic.category.add')->middleware('isAllow:104,can_add');
        Route::get('topic-category/{id}', 'edit')->name('topic.category.edit')->middleware('isAllow:104,can_edit');
        Route::post('topic-category/{id}', 'update')->name('topic.category.edit')->middleware('isAllow:104,can_edit');
        Route::delete('topic-category', 'delete')->name('topic.category')->middleware('isAllow:104,can_delete');

        Route::get('topic', 'topicIndex')->name('topic')->middleware('isAllow:104,can_view');
        Route::get('topic/add', 'topicAdd')->name('topic.add')->middleware('isAllow:104,can_add');
        Route::post('topic/add', 'topicSave')->name('topic.add')->middleware('isAllow:104,can_add');
        Route::get('topic/{id}', 'topicEdit')->name('topic.edit')->middleware('isAllow:104,can_edit');
        Route::post('topic/{id}', 'topicUpdate')->name('topic.edit')->middleware('isAllow:104,can_edit');
        Route::delete('topic', 'topicDelete')->name('topic')->middleware('isAllow:104,can_delete');


        Route::get('moments', 'postIndex')->name('posts.index')->middleware('isAllow:104,can_view');
        Route::get('moments/{id}/details', 'details')->name('posts.details')->middleware('isAllow:104,can_view');
        Route::delete('moments/delete', 'postDelete')->name('post.delete')->middleware('isAllow:104,can_delete');
    });

    // --------------------------------- User Level Routes ---------------------------------
    Route::controller(RoomRewardSlabController::class)->group(function () {
        Route::get('room-reward-slabs', 'index')->name('room_reward_slabs')->middleware('isAllow:104,can_view');
        Route::get('room-reward-slabs/add', 'add')->name('room_reward_slabs.add')->middleware('isAllow:104,can_add');
        Route::post('room-reward-slabs/add', 'save')->name('room_reward_slabs.add')->middleware('isAllow:104,can_add');
        Route::get('room-reward-slabs/{id}', 'edit')->name('room_reward_slabs.edit')->middleware('isAllow:104,can_edit');
        Route::post('room-reward-slabs/{id}', 'update')->name('room_reward_slabs.edit')->middleware('isAllow:104,can_edit');
        Route::delete('room-reward-slabs', 'delete')->name('room_reward_slabs')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- App User Routes ----------------------------------------------------
    Route::controller(AppUserController::class)->group(function () {
        Route::get('user-list', 'index')->name('app-users')->middleware('isAllow:104,can_view');
        Route::get('user-details/{id}', 'userDetails')->name('user-details')->middleware('isAllow:104,can_view');
        Route::get('user/edit/{id}', 'edit')->name('user.edit')->middleware('isAllow:104,can_edit');
        Route::post('user/add/{id?}', 'save')->name('user.save')->middleware('isAllow:104,can_edit');
        Route::delete('delete-user', 'Delete')->name('delete.appuser')->middleware('isAllow:104,can_delete');


        Route::post('/user/disable', 'disable')->name('user.disable');
        Route::post('/user/activate', 'activate')->name('user.activate');
        Route::post('/user/blacklist', 'blacklist')->name('user.blacklist');
    });

    // ----------------------- Reports Routes ----------------------------------------------------
    Route::controller(ReportController::class)->group(function () {
        Route::get('post/report', 'postIndex')->name('post.report')->middleware('isAllow:104,can_view');
        Route::delete('post/delete', 'postDestroy')->name('post.destroy')->middleware('isAllow:104,can_delete');

        Route::get('user/report', 'userIndex')->name('user.report')->middleware('isAllow:104,can_view');
        Route::delete('user/delete', 'userDestroy')->name('user.destroy')->middleware('isAllow:104,can_delete');
    });

    // ----------------------- Feed Back Routes ----------------------------------------------------
    Route::controller(FeedBackController::class)->group(function () {
        Route::get('feedback', 'index')->name('feedback')->middleware('isAllow:104,can_view');
    });

    // ----------------------- Violation Routes ----------------------------------------------------
    Route::controller(ViolationController::class)->group(function () {
        Route::get('violation', 'index')->name('violation')->middleware('isAllow:104,can_view');
    });

    // ----------------------- Wallet Routes ----------------------------------------------------
    Route::controller(WalletController::class)->group(function () {
        Route::get('walleet', 'index')->name('walleet')->middleware('isAllow:104,can_view');
    });

    // ----------------------- User Album Routes ----------------------------------------------------
    Route::controller(UserAlbumController::class)->group(function () {
        Route::get('user-album', 'index')->name('user-album')->middleware('isAllow:104,can_view');
    });


    // ----------------------- User Album Routes ----------------------------------------------------
    Route::controller(AssetsController::class)->group(function () {
        Route::get('assets-user', 'index')->name('assets-user')->middleware('isAllow:104,can_view');
    });

    // ----------------------- User Badge Routes ----------------------------------------------------
    Route::controller(UserBadgeController::class)->group(function () {
        Route::get('user-badge', 'index')->name('user-badge')->middleware('isAllow:104,can_view');
    });

    // ----------------------- User Back Pack Routes ----------------------------------------------------
    Route::controller(UserBackpackController::class)->group(function () {
        Route::get('user-backpack', 'index')->name('user-backpack')->middleware('isAllow:104,can_view');
    });

    // ----------------------- User Music Routes ----------------------------------------------------
    Route::controller(UserMusicController::class)->group(function () {
        Route::get('user-music', 'index')->name('user-music')->middleware('isAllow:104,can_view');
    });

    // ----------------------- User Video Routes ----------------------------------------------------
    Route::controller(UserVideoController::class)->group(function () {
        Route::get('user-video', 'index')->name('user-video')->middleware('isAllow:104,can_view');
    });

    // ----------------------- User Speaker Routes ----------------------------------------------------
    Route::controller(UserSpeakerController::class)->group(function () {
        Route::get('speaker-list', 'index')->name(name: 'speaker-list')->middleware('isAllow:104,can_view');
    });

    // ----------------------- Blocking Device Routes ----------------------------------------------------
    Route::controller(AccountBanController::class)->group(function () {

        Route::get('blocking-device', 'index')
            ->name('blocking-device')
            ->middleware('isAllow:104,can_view');

        Route::get('blocking-device/devices', 'deviceList')
            ->name('blocking-device.devices');

        Route::get('blocking-device/ips', 'ipList')
            ->name('blocking-device.ips');
    });


    Route::any('setting/{id}', [SettingController::class, 'setting'])->name('setting')->middleware('isAllow:101,can_view');
    Route::get('database-backup', [SettingController::class, 'database_backup'])->name('database_backup')->middleware('isAllow:101,can_view');
    Route::get('server-control', [SettingController::class, 'serverControl'])->name('server-control')->middleware('isAllow:101,can_view');
    Route::post('server-control', [SettingController::class, 'serverControlSave'])->name('server-control')->middleware('isAllow:101,can_view');
});
