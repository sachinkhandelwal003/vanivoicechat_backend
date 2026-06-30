<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AppPageController extends Controller
{
    public function privacyPolicy(): JsonResponse
    {
        $page = StaticPage::where('type', 'privacy_policy')->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Privacy policy not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Privacy policy fetched successfully',
            'data' => [
                'title' => $page->title,
                'description' => $page->description,
            ],
        ]);
    }
    public function userAgreement(): JsonResponse
    {
        $page = StaticPage::where('type', 'user_agreement')->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'User Agreement not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'User Agreement fetched successfully',
            'data' => [
                'title' => $page->title,
                'description' => $page->description,
            ],
        ]);
    }
    public function paymentAgreement(): JsonResponse
    {
        $page = StaticPage::where('type', 'payment_agreement')->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Payment Agreement not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment Agreement fetched successfully',
            'data' => [
                'title' => $page->title,
                'description' => $page->description,
            ],
        ]);
    }
    public function aboutUs(): JsonResponse
    {
        $page = StaticPage::where('type', 'about_us')->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'About Us not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'About Us fetched successfully',
            'data' => [
                'title' => $page->title,
                'description' => $page->description,
            ],
        ]);
    }
}
