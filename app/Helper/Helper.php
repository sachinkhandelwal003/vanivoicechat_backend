<?php

namespace App\Helper;

use Closure;
use App\Models\Company;
use App\Models\Room;
use App\Models\RoomSeat;
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

}
