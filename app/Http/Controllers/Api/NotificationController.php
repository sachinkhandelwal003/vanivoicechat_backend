<?php

namespace App\Http\Controllers\Api;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    protected $userId;
    protected $user;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);

        $this->middleware(function ($request, $next) {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $this->userId = $user->id;
            $this->user = $user;

            return $next($request);
        });
    }

    public function getNotifications11()
    {
        $userId = Auth::id();

        $notifications = Notification::where(function ($query) use ($userId) {
            $query->where('receiver_id', $userId)
                ->orWhereNull('receiver_id');
        })
            ->latest()
            ->paginate(20);

        $notifications->getCollection()->transform(function ($notification) {

            $notification->icon = $notification->icon
                ? asset($notification->icon)
                : null;
            $notification->image = $notification->image
                ? Helper::showImage($notification->image, true)
                : null;

            $notification->formatted_time = \Carbon\Carbon::parse($notification->created_at)
                ->format('m/d H:i');

            return $notification;
        });

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    public function getNotifications()
    {
        $user = Auth::user();

        $userCountry = strtolower(trim($user->country));

        $notifications = Notification::where(

            function ($query) use ($user, $userCountry) {

                // Personal notifications

                $query->where('receiver_id', $user->id);

                // Country notifications

                $query->orWhere(function ($q)
                use ($userCountry) {

                    $q->whereNull('receiver_id')
                        ->whereRaw('LOWER(country) = ?', [$userCountry]);
                });
            }
        )->latest()
            ->paginate(20);

        $notifications
            ->getCollection()
            ->transform(function ($notification) {

                $notification->icon = $notification->icon ? asset($notification->icon) : null;
                $notification->image = $notification->image ? Helper::showImage($notification->image, true) : null;
                $notification->formatted_time = \Carbon\Carbon::parse($notification->created_at)->format('m/d H:i');
                return $notification;
            });

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }
}
