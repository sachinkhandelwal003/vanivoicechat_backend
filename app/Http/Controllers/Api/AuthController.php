<?php


namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Models\Room;
use App\Models\RoomFollow;
use App\Models\RoomMember;
use App\Models\RoomPresence;
use App\Models\RoomSeat;
use App\Models\RoomVisit;
use App\Models\Family;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\TopicLike;
use App\Models\SupportMessage;
use App\Models\SupportConversation;
use App\Models\CommentLike;
use App\Models\FamilyJoinRequest;
use App\Models\FamilyMember;
use App\Models\AdminAccount;
use App\Models\Agency;
use App\Models\BdUser;
use App\Models\Host;
use App\Models\CoinSeller;
use App\Models\PremiumNumber;
use App\Models\StoreUids;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Mail\EmailOtpMail;
use Illuminate\Support\Facades\Mail;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'google_id' => 'required',
            'email' => 'required|email',
            'name' => 'required',
        ]);

        return DB::transaction(function () use ($request) {

            $user = AppUser::where('email', $request->email)->lockForUpdate()->first();

            if ($user) {

                if ($user->is_blacklisted) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your account has been permanently blacklisted. Contact support.'
                    ], 403);
                }

                if ($user->is_disabled) {

                    if ($user->disabled_until && now()->lt(\Carbon\Carbon::parse($user->disabled_until))) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Your account is disabled until ' .
                                \Carbon\Carbon::parse($user->disabled_until)->format('Y-m-d H:i')
                        ], 403);
                    }

                    $user->update([
                        'is_disabled' => false,
                        'disabled_until' => null,
                        'disable_reason' => null
                    ]);
                }
            }

            $generateInviteCode = function ($length = 8) {
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

                do {
                    $code = substr(str_shuffle($characters), 0, $length);
                } while (AppUser::where('invite_code', $code)->exists());

                return $code;
            };

            if (!$user) {
                $user = AppUser::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'google_id' => $request->google_id,
                    'image' => $request->image ?? null,
                    'timezone' => $request->timezone,
                    'fcm_token' => $request->fcm_token
                ]);

                $baseUid = 10000000;
                $user->uid = $baseUid + $user->id;

                $user->invite_code = $generateInviteCode();

                $user->save();
            } else {
                $user->update([
                    'timezone' => $request->timezone ?? $user->timezone,
                    'fcm_token' => $request->fcm_token
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $genderFilled = !is_null($user->gender);

            $roles = [];

            // BD override (highest priority)
            if (BdUser::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
                $roles = ['bd'];
            } else {

                if (AdminAccount::where('user_id', $user->id)->where('status', 1)->exists()) {
                    $roles[] = 'admin';
                }

                if (Agency::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                    $roles[] = 'agency';
                }

                if (Host::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
                    $roles[] = 'host';
                }

                // Coin Seller / Merchant logic
                $coinSeller = CoinSeller::where('user_id', $user->id)
                    ->where('status', 1)
                    ->first();

                if ($coinSeller) {
                    if ($coinSeller->is_merchant == 1) {
                        $roles[] = 'merchant';
                    } else {
                        $roles[] = 'coinseller';
                    }
                }

                if (empty($roles)) {
                    $roles[] = 'user';
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'gender_filled' => $genderFilled,
                'invite_code' => $user->invite_code,
                'user' => [
                    'id' => $user->id,
                    'uid' => $user->uid,
                    'name' => $user->name,
                    'image' => Helper::showImage($user->image, true),
                    'email' => $user->email,
                    'gender' => $user->gender ?? null,
                    'password' => $user->password,
                    'user_roles' => $roles
                ]
            ]);
        });
    }
    public function deleteAccount(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        DB::beginTransaction();

        try {
            $userId = $user->id;

            $user->tokens()->delete();
            TopicLike::where('user_id', $user->id)->delete();
            Post::where('user_id', $user->id)->delete();
            PostComment::where('user_id', $user->id)->delete();
            PostLike::where('user_id', $user->id)->delete();
            CommentLike::where('user_id', $user->id)->delete();
            Room::where('user_id', $userId)->delete();
            RoomFollow::where('user_id', $userId)->delete();
            RoomMember::where('user_id', $userId)->delete();
            RoomPresence::where('user_id', $userId)->delete();
            RoomSeat::where('user_id', $userId)->delete();
            RoomVisit::where('user_id', $userId)->delete();
            Family::where('leader_id', $userId)->delete();
            FamilyJoinRequest::where('leader_id', $userId)->delete();
            FamilyMember::where('leader_id', $userId)->delete();
            SupportConversation::where('user_id', $userId)->delete();
            SupportMessage::where('sender_id', $userId)->delete();

            $user->forceDelete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Account deleted successfully',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete account',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function setPassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'password' => 'required|min:6'
        ], [
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ]);
        }

        $user = Auth::user();
        // dd($user);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ]);
        }
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password set successfully',
        ]);
    }

    public function loginByUid(Request $request)
    {
        $uid = $request->uid;

        // $hasUid = AppUser::where('uid', $uid)->first();
        // if (!$hasUid) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'User ID does not exist'
        //     ]);
        // }

        $uid = trim($request->uid);


        $hasUid = null;

        /*
|--------------------------------------------------------------------------
| 1. Premium UID Login
|--------------------------------------------------------------------------
*/
        $premiumUid = PremiumNumber::where('premium_number', $uid)
            ->where('end_at', '>', now())
            ->latest()
            ->first();

        if ($premiumUid) {
            $hasUid = AppUser::find($premiumUid->user_id);
        }



        /*
|--------------------------------------------------------------------------
| 2. Store UID Login
|--------------------------------------------------------------------------
*/
        if (!$hasUid) {

            $storeUid = StoreUids::where('unique_id', $uid)->first();

            if ($storeUid) {

                $activeUser = AppUser::where('active_uid_id', $storeUid->id)
                    ->first();

                if ($activeUser) {

                    $hasValidPurchase = DB::table('item_deliveries')
                        ->where('recipient', $activeUser->id)
                        ->where('type', 'id')
                        ->where('item_id', $storeUid->id)
                        ->where('end_at', '>', now())
                        ->exists();

                    $hasValidGift = DB::table('item_gift_transactions')
                        ->where('receiver_id', $activeUser->id)
                        ->where('type', 'id')
                        ->where('item_id', $storeUid->id)
                        ->where('end_at', '>', now())
                        ->exists();

                    if ($hasValidPurchase || $hasValidGift) {
                        $hasUid = $activeUser;
                    }
                }
            }
        }

        /*
|--------------------------------------------------------------------------
| 3. System Generated UID Login
|--------------------------------------------------------------------------
*/
        if (!$hasUid) {
            $hasUid = AppUser::where('uid', $uid)->first();
        }

        if (!$hasUid) {
            return response()->json([
                'status' => false,
                'message' => 'User ID does not exist'
            ]);
        }
        // dd($hasUid);
        if ($hasUid->is_blacklisted) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been permanently blacklisted. Contact support.'
            ], 403);
        }

        if ($hasUid->is_disabled) {
            if ($hasUid->disabled_until && now()->lt(\Carbon\Carbon::parse($hasUid->disabled_until))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is disabled until ' . \Carbon\Carbon::parse($hasUid->disabled_until)->format('Y-m-d')
                ], 403);
            }

            $hasUid->update([
                'is_disabled' => false,
                'disabled_until' => null,
                'disable_reason' => null
            ]);
        }

        if (!$hasUid->password || !Hash::check($request->password, $hasUid->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password',
            ], 401);
        }


        $hasUid->timezone = $request->timezone;
        $hasUid->fcm_token = $request->fcm_token;
        $hasUid->save();

        $token = $hasUid->createToken('auth_token')->plainTextToken;

        $roles = [];

        // BD override (highest priority)
        if (BdUser::where('user_id', $hasUid->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
            $roles = ['bd'];
        } else {

            if (AdminAccount::where('user_id', $hasUid->id)->where('status', 1)->exists()) {
                $roles[] = 'admin';
            }

            if (Agency::where('user_id', $hasUid->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                $roles[] = 'agency';
            }

            if (Host::where('user_id', $hasUid->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
                $roles[] = 'host';
            }

            // Coin Seller / Merchant logic
            $coinSeller = CoinSeller::where('user_id', $hasUid->id)
                ->where('status', 1)
                ->first();

            if ($coinSeller) {
                if ($coinSeller->is_merchant == 1) {
                    $roles[] = 'merchant';
                } else {
                    $roles[] = 'coinseller';
                }
            }

            if (empty($roles)) {
                $roles[] = 'user';
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Login Successful',
            'token' => $token,
            'user' => [
                'id' => $hasUid->id,
                'uid' => $hasUid->uid,
                'name' => $hasUid->name,
                'image' => Helper::showImage($hasUid->image, true),
                'email' => $hasUid->email,
                'gender' => $hasUid->gender ?? null,
                'password' => $hasUid->password,
                'user_roles' => $roles
            ]
        ]);
    }

    public function bindEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Invalid email address',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingUser = AppUser::where('email', $request->email)->first();

        if (!$existingUser) {
            return response()->json([
                'status' => false,
                'message' => 'Email not found',
            ], 404);
        }

        $user = Auth::user();

        $otp = rand(100000, 999999);

        $user->email_otp = $otp;
        $user->email_otp_expires_at = Carbon::now()->addMinutes(5);

        $user->save();

        Mail::to($request->email)->send(new EmailOtpMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to email',
        ]);
    }

    public function verifyBindEmail(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = Auth::user();

        if (
            !$user->email_otp ||
            $user->email_otp !== $request->otp
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (Carbon::now()->gt($user->email_otp_expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
            ], 400);
        }

        $user->email_otp = null;
        $user->email_otp_expires_at = null;
        $user->is_email_bind = 1;

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully',
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function sendEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Invalid email address',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = AppUser::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Email not found',
            ], 404);
        }

        if (is_null($user->is_email_bind) || $user->is_email_bind != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Please bind email first',
            ], 403);
        }


        $otp = rand(100000, 999999);


        $user->email_otp = $otp;
        $user->email_otp_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        Mail::to($user->email)->send(new EmailOtpMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to email',
        ]);
    }


    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Invalid email address',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = AppUser::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->is_blacklisted) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been permanently blacklisted. Contact support.'
            ], 403);
        }

        //  DISABLE CHECK
        if ($user->is_disabled) {

            if ($user->disabled_until && now()->lt(\Carbon\Carbon::parse($user->disabled_until))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is disabled until ' .
                        \Carbon\Carbon::parse($user->disabled_until)->format('Y-m-d H:i')
                ], 403);
            }

            //  Auto-reactivate if expired
            $user->update([
                'is_disabled' => false,
                'disabled_until' => null,
                'disable_reason' => null
            ]);
        }

        if (!$user->email_otp || !$user->email_otp_expires_at) {
            return response()->json([
                'status' => false,
                'message' => 'OTP not found',
            ], 400);
        }

        if ((string) $user->email_otp !== (string) $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (Carbon::now()->gt($user->email_otp_expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
            ], 400);
        }

        $user->email_otp = null;
        $user->email_otp_expires_at = null;
        $user->timezone = $request->timezone;
        $user->fcm_token = $request->fcm_token;

        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        $roles = [];

        // BD override (highest priority)
        if (BdUser::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
            $roles = ['bd'];
        } else {

            if (AdminAccount::where('user_id', $user->id)->where('status', 1)->exists()) {
                $roles[] = 'admin';
            }

            if (Agency::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                $roles[] = 'agency';
            }

            if (Host::where('user_id', $user->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
                $roles[] = 'host';
            }

            // Coin Seller / Merchant logic
            $coinSeller = CoinSeller::where('user_id', $user->id)
                ->where('status', 1)
                ->first();

            if ($coinSeller) {
                if ($coinSeller->is_merchant == 1) {
                    $roles[] = 'merchant';
                } else {
                    $roles[] = 'coinseller';
                }
            }

            if (empty($roles)) {
                $roles[] = 'user';
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Otp verified successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'image' => Helper::showImage($user->image, true),
                'email' => $user->email,
                'gender' => $user->gender ?? null,
                'password' => $user->password,
                'user_roles' => $roles
            ]
        ]);
    }

    public function loginByPhone(Request $request)
    {
        $phone = $request->phone;

        $hasPhone = AppUser::where('phone', $phone)->first();
        if (!$hasPhone) {
            return response()->json([
                'status' => false,
                'message' => 'Phone Number does not exist'
            ]);
        }

        if ($hasPhone->is_blacklisted) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been permanently blacklisted. Contact support.'
            ], 403);
        }

        //  DISABLE CHECK (Temporary Block)
        if ($hasPhone->is_disabled) {

            if ($hasPhone->disabled_until && now()->lt(\Carbon\Carbon::parse($hasPhone->disabled_until))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is disabled until ' .
                        \Carbon\Carbon::parse($hasPhone->disabled_until)->format('Y-m-d H:i')
                ], 403);
            }

            //  Auto-reactivate if disable expired
            $hasPhone->update([
                'is_disabled' => false,
                'disabled_until' => null,
                'disable_reason' => null
            ]);
        }

        if (!$hasPhone->phone_password || !Hash::check($request->password, $hasPhone->phone_password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        $hasPhone->timezone = $request->timezone;
        $hasPhone->fcm_token = $request->fcm_token;
        $hasPhone->save();

        $token = $hasPhone->createToken('auth_token')->plainTextToken;

        $roles = [];

        // BD override (highest priority)
        if (BdUser::where('user_id', $hasPhone->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
            $roles = ['bd'];
        } else {

            if (AdminAccount::where('user_id', $hasPhone->id)->where('status', 1)->exists()) {
                $roles[] = 'admin';
            }

            if (Agency::where('user_id', $hasPhone->id)->where('invite_status', 'accept')->where('status', 1)->exists()) {
                $roles[] = 'agency';
            }

            if (Host::where('user_id', $hasPhone->id)->where('invite_status', 'accept')->where('status', 1)->where('is_dashboard_access', 1)->exists()) {
                $roles[] = 'host';
            }

            // Coin Seller / Merchant logic
            $coinSeller = CoinSeller::where('user_id', $hasPhone->id)
                ->where('status', 1)
                ->first();

            if ($coinSeller) {
                if ($coinSeller->is_merchant == 1) {
                    $roles[] = 'merchant';
                } else {
                    $roles[] = 'coinseller';
                }
            }

            if (empty($roles)) {
                $roles[] = 'user';
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Login Successful',
            'token' => $token,
            'user' => [
                'id' => $hasPhone->id,
                'uid' => $hasPhone->uid,
                'name' => $hasPhone->name,
                'image' => Helper::showImage($hasPhone->image, true),
                'email' => $hasPhone->email,
                'gender' => $user->gender ?? null,
                'password' => $hasPhone->password,
                'user_roles' => $roles
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized user',
            ], 401);
        }

        try {
            $user->tokens()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateOnlineStatus()
    {
        Auth::user()->update([
            'user_last_seen' => now()
        ]);

        return response()->json([
            'status' => true
        ]);
    }
}
