<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helper\Helper;
use App\Models\OfficialNotification;
use App\Models\AppUser;
use App\Models\Country;
use Yajra\DataTables\Facades\DataTables;
use App\Services\FirebaseService;

class OfficialNotificationController extends Controller
{
    // INDEX - List All Notifications
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = OfficialNotification::with('user')->latest();

            if ($request->country) {
                $data->where('country', 'like', "%{$request->country}%");
            }

            return DataTables::of($data)
                ->addIndexColumn()

                ->addColumn('user_name', function ($row) {
                    return optional($row->user)->name ?? 'All Users';
                })

                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })

                ->addColumn('action', function ($row) {

                    $btn = '<div class="dropdown text-center">
                                <button class="btn btn-sm btn-light border-0 shadow-sm rounded-circle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                        style="width:34px;height:34px;">
                                    <i class="fas fa-ellipsis-v text-secondary"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2"
                                     style="min-width:160px;">';

                    if (Helper::userCan(158, 'can_edit')) {
                        $btn .= '<a class="dropdown-item d-flex align-items-center rounded-2 mb-1"
                                    href="' . route('official_notifications.edit', $row->id) . '"
                                    style="transition:0.2s;">
                                        <i class="fas fa-pen me-2 text-warning"></i>
                                        <span>Edit</span>
                                 </a>';
                    }

                    if (Helper::userCan(158, 'can_delete')) {
                        $btn .= '<button class="dropdown-item d-flex align-items-center rounded-2 text-danger deleteNotification"
                                        data-id="' . $row->id . '"
                                        style="transition:0.2s;">
                                        <i class="fas fa-trash me-2"></i>
                                        <span>Delete</span>
                                 </button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns(['action'])
                ->make(true);
        }

        return view('official_notifications.index');
    }

    // CREATE FORM
    public function create()
    {
        $users = AppUser::all();
        $countries = Country::orderBy('name')->get();
        return view('official_notifications.form', compact('users', 'countries'));
    }

    // STORE NEW NOTIFICATION
    public function store(Request $request)
    {
        $request->validate([
            'user_ids'     => 'nullable|string',
            'country'      => 'nullable',
            'notification' => 'required|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'url'          => 'nullable|url'
        ]);

        $notificationText = $request->notification;
        $userIdsInput     = $request->user_ids;
        $countryId        = $request->country;
        $url              = $request->url;

        $targetUserIds = [];

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = Helper::saveFile($request->file('image'), 'official_note_images');
        }

        // If UID provided → priority
        if (!empty($userIdsInput)) {

            $uids = collect(explode(',', $userIdsInput))
                ->map(fn($id) => trim($id))
                ->filter(fn($id) => is_numeric($id))
                ->toArray();

            if (empty($uids)) {
                return back()->withErrors([
                    'user_ids' => 'Please enter valid UID(s).'
                ])->withInput();
            }

            $targetUserIds = AppUser::whereIn('uid', $uids)
                ->select('id', 'fcm_token')
                ->get();

            if (empty($targetUserIds)) {
                return back()->withErrors([
                    'user_ids' => 'No matching users found for given UID(s).'
                ])->withInput();
            }
        }

        // If no UID but country selected
        elseif (!empty($countryId)) {

            $country = Country::find($countryId);

            if (!$country) {
                return back()->withErrors([
                    'country' => 'Invalid country selected.'
                ])->withInput();
            }

            $targetUserIds = AppUser::where('country', $country->nicename)
                ->select('id', 'fcm_token')
                ->get();

            if (empty($targetUserIds)) {
                return back()->withErrors([
                    'country' => 'No users found for selected country.'
                ])->withInput();
            }
        }

        $firebase = new FirebaseService();
        // Insert notifications
        foreach ($targetUserIds as $user) {
            OfficialNotification::create([
                'user_id'      => $user->id,
                'country'      => $country->nicename ?? null,
                'notification' => $notificationText,
                'image'        => $imagePath,
                'url'          => $url,
            ]);

            if (!empty($user->fcm_token)) {

                $firebase->sendNotification(
                    $user->fcm_token,
                    "Official Notification",
                    $notificationText,
                    $imagePath ? Helper::showImage($imagePath, true) : null
                );
            }
        }

        return redirect()
            ->route('official_notifications.index')
            ->with('success', 'Notification sent successfully.');
    }

    // EDIT FORM
    public function edit($id)
    {
        $notification = OfficialNotification::findOrFail($id);
        $users = AppUser::all();
        $countries = Country::orderBy('name')->get();

        return view('official_notifications.form', compact('notification', 'users', 'countries'));
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_ids'      => 'nullable|string',
            'country'       => 'nullable|string|max:255',
            'notification'  => 'required|string',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'url'           => 'nullable|url'
        ]);

        $notification = OfficialNotification::findOrFail($id);

        $userIdsInput = $request->user_ids;

        $imagePath = $notification->image;

        if ($request->hasFile('image')) {

            if ($notification->image && \Storage::disk('public')->exists($notification->image)) {
                \Storage::disk('public')->delete($notification->image);
            }

            $imagePath = Helper::saveFile($request->file('image'), 'official_note_images');
        }

        $url = $request->url;

        $notification->delete();

        if (!$userIdsInput) {

            OfficialNotification::create([
                'user_id'      => null,
                'country'      => $request->country,
                'notification' => $request->notification,
                'image'        => $imagePath,
                'url'          => $url,
            ]);

            return redirect()
                ->route('official_notifications.index')
                ->with('success', 'Notification updated for all users.');
        }

        // Convert comma separated UID
        $uids = collect(explode(',', $userIdsInput))
            ->map(fn($id) => trim($id))
            ->filter(fn($id) => is_numeric($id))
            ->toArray();

        if (empty($uids)) {
            return back()->withErrors([
                'user_ids' => 'Please enter valid UID(s).'
            ])->withInput();
        }

        $validUsers = AppUser::whereIn('uid', $uids)
            ->pluck('id')
            ->toArray();

        if (empty($validUsers)) {
            return back()->withErrors([
                'user_ids' => 'No matching users found.'
            ])->withInput();
        }

        foreach ($validUsers as $userId) {
            OfficialNotification::create([
                'user_id'      => $userId,
                'country'      => $request->country,
                'notification' => $request->notification,
                'image'        => $imagePath,
                'url'          => $url,
            ]);
        }

        return redirect()
            ->route('official_notifications.index')
            ->with('success', 'Notification updated successfully.');
    }

    // DELETE
    public function destroy($id)
    {
        $notification = OfficialNotification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Notification deleted successfully.'
        ]);
    }
}
