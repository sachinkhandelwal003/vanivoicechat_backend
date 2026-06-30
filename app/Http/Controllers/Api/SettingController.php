<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $feedbacks = Feedback::latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'Feedback list fetched successfully',
            'data' => $feedbacks
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'feedback_type' => 'required|string|max:55',
            'describe'      => 'required|string',
            'user_contact'  => 'nullable|string|max:55',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback = Feedback::create([
            'user_id'       => Auth::id(),
            'feedback_type' => $request->feedback_type,
            'describe'      => $request->describe,
            'user_contact'  => $request->user_contact,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feedback submitted successfully',
            'data' => $feedback
        ], 201);
    }
}
