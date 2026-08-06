<?php

namespace App\Helper;

use Closure;
use App\Models\Company;
use App\Models\Room;
use App\Models\RoomSeat;
use App\Models\AppUser;
use App\Models\PremiumNumber;
use App\Models\VipTransaction;
use App\Models\VipPrivilege;
use App\Models\StoreUids;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\DB;


class Helper
{

    function sendOtp($phoneNumber, $otp)
    {
        try {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $message = $twilio->messages->create(
                $phoneNumber,
                [
                    'from' => env('TWILIO_PHONE_NUMBER'),
                    'body' => "Your OTP is: {$otp}"
                ]
            );

            return $message->sid;
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function generateInviteCode($length = 8)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = substr(str_shuffle($characters), 0, $length);
        } while (AppUser::where('invite_code', $code)->exists());

        return $code;
    }
    public static function deleteFile($filename = '')
    {
        $filename = str_replace(asset('storage'), '', $filename);

        if (
            in_array($filename, [
                'application/favicon.png',
                'application/logo.png',
                'admin/avatar.png',
            ])
        ) {
            return true;
        }

        if (Storage::exists($filename)) {
            Storage::delete($filename);
        }

        return true;
    }

    public static function showImage(string|null $filename, bool $showDefault = false): string|null
    {
        if ($filename && file_exists(public_path('storage/' . $filename))) {
            return asset('storage/' . $filename);
        }

        return $showDefault ? asset('assets/img/default-vani.png') : null;
    }


    public static function routeis(string $expression): string
    {
        return in_array(request()->route()->getName(), explode(',', $expression)) ? 'true' : 'false';
    }

    public static function getGuardFromURL(Request $request, $type = true): string
    {
        if ($request->is('employee/*') || $request->is('employee')) {
            $route = 'employee';
        } else {
            $route = $type ? 'web' : '';
        }
        return $route;
    }

    public static function getTableFromURL(Request $request): string
    {
        if ($request->is('employee/*') || $request->is('employee')) {
            $route = 'employees';
        } else {
            $route = 'users';
        }
        return $route;
    }

    public static function checkRoute(string $route): bool
    {
        if (Route::has(implode('.', array_filter(explode('/', $route))))) {
            return true;
        } else {
            return false;
        }
    }

    public static function orderId(int|string $a, string $prefix = 'ORD', int $len = 10): string
    {
        $x = $len - (gettype($a) == 'string' ? strlen($a) : strlen((string) $a));
        for ($i = 1; $i <= (int) $x; $i++) {
            $a = "0" . $a;
        }
        return $prefix . $a;
    }

    public static function getTransactionDetails(string $prefix = "", array $data = [], int $mode = 1): string
    {
        if ($mode == 1) {
            return $prefix . " " . ($data['payment_type'] == 1 ? 'Credit' : 'Debit') . " : " . $data['particulars'];
        } else {
            return ($data['payment_type'] == 1 ? 'Send To ' : 'Take From ') . $prefix . " : " . $data['particulars'];
        }
    }

    public static function userCan(array|int $module_id = [], string $type = "can_view"): bool
    {
        try {
            $module = gettype($module_id) == 'array' ? (array) $module_id : [$module_id];
            $permission = request()->permission;

            if (!$permission)
                return false;
            if (!$permission->count())
                return false;

            $module_permission = $permission->whereIn('module_id', $module)->filter(function ($row) use ($type) {
                return $row['allow_all'] == 1 || $row[$type] == 1;
            });

            return $module_permission->count() > 0 ? true : false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public static function userAllowed(int $module_id = 0, array $type = ['can_edit', 'can_delete']): bool
    {
        $permission = request()->permission;

        if (!$permission)
            return false;
        if (!$permission->count())
            return false;

        $module_permission = request()->permission->firstWhere('module_id', $module_id);
        if (!$module_permission)
            return false;
        if ($module_permission->allow_all == 1)
            return true;

        if (collect($type)->filter(fn($row) => $module_permission[$row] == 1)->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function saveFile(UploadedFile|null $image, $folder = 'admin'): null|string
    {
        if ($image) {
            $filename = time() . '_' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();

            $storagePath = storage_path("app/public/{$folder}");
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $image->move($storagePath, $filename);

            $from = $storagePath . '/' . $filename;
            $publicPath = public_path("storage/{$folder}");
            $to = $publicPath . '/' . $filename;

            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            copy($from, $to);

            return "{$folder}/{$filename}";
        }

        return null;
    }


    public static function checkValid(array $validation, Closure $closure): JsonResponse
    {
        $validator = Validator::make(request()->all(), $validation, [
            'mobile.regex' => "Please enter valid indian mobile number."
        ]);

        if ($validator->fails()) {
            $err = array();
            foreach ($validator->errors()->toArray() as $key => $value) {
                $err[$key] = $value[0];
            }

            return response()->json([
                'status' => false,
                'message' => "Invalid Input values.",
                'data' => $err
            ]);
        } else {
            return is_callable($closure) ? $closure($validator) : response()->json([
                'status' => false,
                'message' => "Invalid Closure function.",
                'data' => []
            ]);
        }
    }

    public static function deleteRecord(Model $model, int $id = 0, Closure $check = null): JsonResponse
    {
        $data = $model::find($id);
        if (!$data) {
            return response()->json([
                'status' => true,
                'message' => 'No Record Found..!!',
            ]);
        }

        if (!$check || (is_callable($check) && $check($data))) {
            $data->delete();
            return response()->json([
                'status' => true,
                'message' => 'Record Deleted Successfully.!!',
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => "Record Can't be deleted.!!",
            ]);
        }
    }

    public static function downloadExcel(string $fileName, Spreadsheet $spreadsheet): StreamedResponse
    {
        $spreadsheet->getProperties()
            ->setCreator(config('excel.exports.properties.creator', ''))
            ->setLastModifiedBy(config('excel.exports.properties.lastModifiedBy', ''))
            ->setTitle(config('excel.exports.properties.title', ''))
            ->setSubject(config('excel.exports.properties.subject', ''))
            ->setDescription(config('excel.exports.properties.description', ''))
            ->setKeywords(config('excel.exports.properties.keywords', ''))
            ->setCategory(config('excel.exports.properties.category', ''));

        $response = response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        return $response->send();
    }

    public static function getUserRoleBadges($userId)
    {
        $badges = [];

        // if (\App\Models\AdminAccount::where('user_id', $userId)
        //     ->where('status', 1)
        //     ->exists()
        // ) {
        //     $badges[] = [
        //         'type' => 'admin',
        //         'title' => 'Admin',
        //         'icon' => asset('role_badge/admin.png')
        //     ];
        // }

        if (\App\Models\BdUser::where('user_id', $userId)
            ->where('status', 1)
            ->where('is_dashboard_access', 1)
            ->exists()
        ) {
            $badges[] = [
                'type' => 'bd',
                'title' => 'BD',
                'icon' => asset('storage/role_badge/bd.webp')
            ];
        }

        if (\App\Models\Agency::where('user_id', $userId)
            ->where('status', 1)
            ->exists()
        ) {
            $badges[] = [
                'type' => 'agency',
                'title' => 'Agency',
                'icon' => asset('storage/role_badge/agency.webp')
            ];
        }

        if (
            \App\Models\Host::where('user_id', $userId)
            ->where('status', 1)
            ->where('is_dashboard_access', 1)
            ->exists()
        ) {
            $badges[] = [
                'type' => 'host',
                'title' => 'Host',
                'icon' => asset('storage/role_badge/host.webp')
            ];
        }

        $coinSeller = \App\Models\CoinSeller::where('user_id', $userId)
            ->where('status', 1)
            ->first();

        if ($coinSeller) {

            if ((int) $coinSeller->is_merchant === 1) {
                $badges[] = [
                    'type' => 'merchant',
                    'title' => 'Merchant',
                    'icon' => asset('storage/role_badge/merchant.webp')
                ];
            } else {
                $badges[] = [
                    'type' => 'coinseller',
                    'title' => 'Coin Seller',
                    'icon' => asset('storage/role_badge/coinseller.webp')
                ];
            }
        }

        return $badges;
    }

    public static function findUserByAnyUid($uid)
    {
        // System UID
        $user = AppUser::where('uid', $uid)->first();

        if ($user) {
            return $user;
        }

        // Premium UID
        $premium = PremiumNumber::where('premium_number', $uid)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->latest()
            ->first();

        if ($premium) {
            $user = AppUser::find($premium->user_id);

            if ($user) {
                return $user;
            }
        }

        // Store UID
        $storeUid = StoreUids::where('unique_id', $uid)->first();

        if ($storeUid) {

            $delivery = DB::table('item_deliveries')
                ->where('type', 'id')
                ->where('item_id', $storeUid->id)
                ->where(function ($q) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', now());
                })
                ->latest()
                ->first();

            if ($delivery) {
                return AppUser::find($delivery->recipient);
            }

            $gift = DB::table('item_gift_transactions')
                ->where('type', 'id')
                ->where('item_id', $storeUid->id)
                ->where(function ($q) {
                    $q->whereNull('end_at')
                        ->orWhere('end_at', '>', now());
                })
                ->latest()
                ->first();

            if ($gift) {
                return AppUser::find($gift->receiver_id);
            }
        }

        return null;
    }

    public static function getDisplayUidData($user)
    {
        if (!$user) {
            return [
                'uid' => null,
                'system_uid' => null,
                'badge' => null,
                'badge_color' => null,
            ];
        }

        $response = [
            'uid' => $user->uid,
            'system_uid' => $user->uid,
            'badge' => null,
            'badge_color' => null,
        ];

        // Premium
        $premium = PremiumNumber::where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->latest()
            ->first();

        if ($premium) {
            $response['uid'] = $premium->premium_number;
             $response['system_uid'] = $user->uid;
            $response['badge'] = asset('storage/1000175794.png');
            $response['badge_color'] = '#fcd01c';

            return $response;
        }

        // Store UID
        if ($user->active_uid_id) {

            $storeUid = StoreUids::find($user->active_uid_id);

            if ($storeUid) {

                $valid = DB::table('item_deliveries')
                    ->where('recipient', $user->id)
                    ->where('type', 'id')
                    ->where('item_id', $storeUid->id)
                    ->where('end_at', '>', now())
                    ->exists()

                    ||

                    DB::table('item_gift_transactions')
                    ->where('receiver_id', $user->id)
                    ->where('type', 'id')
                    ->where('item_id', $storeUid->id)
                    ->where('end_at', '>', now())
                    ->exists();

                if ($valid) {
                    $response['uid'] = $storeUid->unique_id;
                    $response['system_uid'] = $user->uid;
                    $response['badge'] = !empty($storeUid->rank_badge)
                        ? Helper::showImage($storeUid->rank_badge, true)
                        : null;
                    $response['badge_color'] = $storeUid->badge_color;
                }
            }
        }

        return $response;
    }

    public static function hasVipPrivilege($userId, $slug)
    {
        return VipTransaction::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->whereHas(
                'vip.privileges',
                function ($q) use ($slug) {
                    $q->where('slug', $slug)
                        ->where('status', 1);
                }
            )
            ->exists();
    }

    public static function getActiveVipLevel($userId)
    {
        $vip = VipTransaction::with('vip')
            ->where('user_id', $userId)
            ->where('end_at', '>=', now())
            ->latest('vip_id')
            ->first();

        return $vip?->vip_id ?? 0;
    }

    public static function getNicknameMeta($userId)
    {
        $activeVip = VipTransaction::with('vip')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->latest()
            ->first();

        if (!$activeVip || !$activeVip->vip) {
            return [
                'animated' => false,
                'color' => null,
                'effect' => null,
            ];
        }

        $privilege = VipPrivilege::where(
            'vip_id',
            $activeVip->vip_id
        )
            ->where('status', 1)
            ->whereIn('slug', [
                'emerald_animated_nickname',
                'sapphire_animated_nickname',
                'amethyst_animated_nickname',
                'ruby_animated_nickname',
                'diamond_animated_nickname',
                'celestial_rainbow_animated_nickname',
            ])
            ->first();

        if (!$privilege) {
            return [
                'animated' => false,
                'color' => null,
                'effect' => null,
            ];
        }

        return [
            'animated' => true,
            'color' => $activeVip->vip->username,
            'effect' => $privilege->slug,
        ];
    }

    public static function getWealthExpMultiplier($userId)
    {
        if (self::hasVipPrivilege($userId, 'level_accelerator_10')) {
            return 1.10;
        }

        if (self::hasVipPrivilege($userId, 'level_accelerator_7')) {
            return 1.07;
        }

        if (self::hasVipPrivilege($userId, 'level_accelerator_5')) {
            return 1.05;
        }

        if (self::hasVipPrivilege($userId, 'level_accelerator_3')) {
            return 1.03;
        }

        return 1;
    }
}
