<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\AppUser;
use App\Models\Room;
use App\Models\Family;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['storeIndex', 'privacyPolicy', 'deleteAccount']);
    }

    public function index(Request $request): View
    {
        $user = AppUser::where('status', 1)->count();
        $room = Room::where('status', 1)->count();
        $family = Family::where('status', 1)->count();
        return view('home', compact('user', 'room', 'family'));
    }

    public function storeIndex(Request $request): View
    {
        return view('store_index');
    }

    public function customerSupport()
    {
        return view('customer_support.index');
    }


    public function privacyPolicy()
    {
        $data = [
            'app_name' => 'Vani Voice Chat',
            'company_name' => 'Vani Voice',
            'support_email' => 'voxiemeet@gmail.com',
            'website_url' => url('https://vanivoicechat.com/invite-index'),
            'last_updated' => now()->format('d M Y'),
        ];

        return view('privacy-policy', compact('data'));
    }


    public function deleteAccount()
    {
        $data = [
            'app_name' => 'Vani Voice Chat',
            'company_name' => 'Vani Voice',
            'support_email' => 'voxiemeet@gmail.com',
            'website_url' => url('https://vanivoicechat.com/invite-index'),
            'last_updated' => now()->format('d M Y'),
        ];

        return view('delete-account', compact('data'));
    }
}
