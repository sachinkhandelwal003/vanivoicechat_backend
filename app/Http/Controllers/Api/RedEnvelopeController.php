<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Room;
use App\Models\RoomMessage;
use App\Models\RedEnvelope;
use App\Models\RedEnvelopeClaim;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helper\Helper;
use App\Events\RedEnvelopeCreated;
use App\Events\RoomRedEnvelopeCreated;

class RedEnvelopeController extends Controller
{
    public function config(): JsonResponse
    {
        $imageUrl = asset('storage/red_envelope.png');
        $bgImageUrl = asset('storage/red_envelope_bg.png');
        $goldenPlate = asset('storage/golden_plate.png');
        $goldOptions = [
            [
                'id' => 1,
                'amount' => 1999,
                'is_default' => true,
            ],
            [
                'id' => 2,
                'amount' => 9999,
                'is_default' => false,
            ],
            [
                'id' => 3,
                'amount' => 29999,
                'is_default' => false,
            ],
            [
                'id' => 4,
                'amount' => 99999,
                'is_default' => false,
            ],
        ];

        $userOptions = [
            [
                'id' => 1,
                'count' => 1,
                'is_default' => true,
            ],
            [
                'id' => 2,
                'count' => 20,
                'is_default' => false,
            ],
            [
                'id' => 3,
                'count' => 40,
                'is_default' => false,
            ],
            [
                'id' => 4,
                'count' => 60,
                'is_default' => false,
            ],
        ];
        $rules = '1.Sending Red Envelopes Consumes Coins: Sendinq a red envelope will consume a certair amount of coins. The person opening the rec envelope will receive these coins.
                  2. Random Coin Distribution: When you open a red envelope, you will receive a random amount of coins.
                3. Refund Policy: If all red envelopes remain unopened within 30 minutes of being sent, the coins inside the unopened envelopes will be refunded to the sender Coins from opened envelopes will not be refunded';

        return response()->json([
            'status' => true,
            'message' => 'Red envelope config fetched successfully',
            'data' => [
                // 'title' => 'Red envelopes',
                'currency_label' => 'Golds',
                'number_label' => 'Number',
                'button_text' => 'Send',
                'bgImage' => $bgImageUrl,
                'goldenPlate' => $goldenPlate,
                'gold_options' => $goldOptions,
                'user_options' => $userOptions,
                'rules' => $rules
            ],
        ]);
    }


    public function createRedEnvelope(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'room_id'      => 'required|integer|exists:rooms,id',
            'total_amount' => 'required|integer',
            'total_users'  => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            /** @var AppUser $authUser */
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            $allowedAmounts = [1999, 9999, 29999, 99999];
            $allowedUsers   = [10, 20, 40, 60];

            $totalAmount = (int) $request->total_amount;
            $totalUsers  = (int) $request->total_users;
            $roomId      = (int) $request->room_id;

            if (!in_array($totalAmount, $allowedAmounts, true)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid red envelope amount selected',
                ], 422);
            }

            if (!in_array($totalUsers, $allowedUsers, true)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid red envelope user count selected',
                ], 422);
            }

            $room = Room::query()
                ->select('id', 'user_id', 'room_id', 'status', 'country')
                ->where('id', $roomId)
                ->lockForUpdate()
                ->first();

            if (!$room) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Room not found',
                ], 404);
            }

            if ((int) $room->status !== 1) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Room is not active',
                ], 422);
            }

            $sender = AppUser::query()
                ->where('id', $authUser->id)
                ->lockForUpdate()
                ->first();

            if (!$sender) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ((int) $sender->total_points < $totalAmount) {
                DB::rollBack();

                return response()->json([
                    'status'  => false,
                    'message' => 'Insufficient balance',
                ], 422);
            }

            // coins deduct
            $sender->total_points = (int) $sender->total_points - $totalAmount;
            $sender->save();

            $systemCutPercent = 20;
            $systemCutAmount  = (int) floor(($totalAmount * $systemCutPercent) / 100);
            $claimableAmount  = (int) ($totalAmount - $systemCutAmount);

            // fixed random type
            $redEnvelope = RedEnvelope::create([
                'room_id'          => $room->id,
                'sender_user_id'   => $sender->id,
                'country'          => ucfirst(strtolower($sender->country ?? $room->country)) ?? '',
                'type'             => 'random',
                'total_amount'     => $claimableAmount,
                'total_users'      => $totalUsers,
                'claimed_amount'   => 0,
                'claimed_users'    => 0,
                'remaining_amount' => $claimableAmount,
                'remaining_users'  => $totalUsers,
                'status'           => 'active',
                'expires_at'       => now()->addMinutes(30),
            ]);

            $imageUrl = asset('storage/red_envelope.png');
            // $imageUrl = asset('storage/red_envelope.svga');

            $roomMessage = RoomMessage::create([
                'room_id'        => $room->id,
                'user_id'        => $sender->id,
                'message'        => 'sent a red envelope',
                'message_type'   => 'red_envelope',
                'meta_json' => [
                    'red_envelope' => [
                        'id' => $redEnvelope->id,
                        'image' => $imageUrl,
                        'total_amount' => (int) $claimableAmount,
                        'total_users' => (int) $redEnvelope->total_users,
                        'remaining_users' => (int) $redEnvelope->remaining_users,
                        'remaining_amount' => (int) $redEnvelope->remaining_amount,
                        'expires_at' => $redEnvelope->expires_at,
                    ],

                    'sender' => [
                        'id'    => $sender->id,
                        'name'  => $sender->name,
                        'image' => $sender->image ? Helper::showImage($sender->image, true) : null,
                    ],
                ],
            ]);

            DB::commit();

            event(new RoomRedEnvelopeCreated([
                'room_id' => $room->id,
                'message_id' => $roomMessage->id,

                'red_envelope' => [
                    'id' => $redEnvelope->id,
                    'image' => asset('storage/red_envelope.png'),
                    'svgaUrl'         => asset('storage/red_envelope.svga'),
                    'total_amount' => (int) $claimableAmount,
                    'total_users' => (int) $redEnvelope->total_users,
                ],

                'sender' => [
                    'id'    => $sender->id,
                    'name'  => $sender->name,
                    'image' => $sender->image ? Helper::showImage($sender->image, true) : null,
                ],

                'message' => 'sent a red envelope'
            ]));

            $imageUrl = asset('storage/red_envelope.svga');

            event(new RedEnvelopeCreated([
                'red_envelope_id' => $redEnvelope->id,
                'room_id'         => $room->id,
                'room_uid'        => $room->room_id,
                'country'         => ucfirst(strtolower($redEnvelope->country)),

                'image'           => $imageUrl,

                'sender' => [
                    'id'    => $sender->id,
                    'name'  => $sender->name,
                    'image' => $sender->image ? Helper::showImage($sender->image, true) : null,
                ],

                'title'   => 'Red Envelope',
                // 'message' => 'Tap to enter and grab coins',
                'message' => 'sent a red envelope'
            ]));

            return response()->json([
                'status'  => true,
                'message' => 'Red envelope created successfully',
                'data'    => [

                    'id'               => $redEnvelope->id,
                    'room_id'          => $redEnvelope->room_id,
                    'sender_user_id'   => $redEnvelope->sender_user_id,
                    'country'          => $redEnvelope->country,
                    // 'type'             => $redEnvelope->type,
                    'total_amount'     => (int) $claimableAmount,
                    'total_users'      => (int) $redEnvelope->total_users,
                    'imageUrl'         => asset('storage/red_envelope.svga'),
                    // 'claimed_amount'   => (int) $redEnvelope->claimed_amount,
                    // 'claimed_users'    => (int) $redEnvelope->claimed_users,
                    // 'remaining_amount' => (int) $redEnvelope->remaining_amount,
                    // 'remaining_users'  => (int) $redEnvelope->remaining_users,
                    // 'status'           => $redEnvelope->status,
                    'expires_at'       => $redEnvelope->expires_at,
                    // 'sender_balance'   => (int) $sender->total_points,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function claimRedEnvelope(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'red_envelope_id' => 'required|integer|exists:red_envelopes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();

            $envelope = RedEnvelope::where('id', $request->red_envelope_id)
                ->lockForUpdate()
                ->first();

            $sender = AppUser::where('id', $envelope->sender_user_id)
                ->first();

            if (!$envelope) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Red envelope not found',
                ], 404);
            }

            // 1. Already claimed check
            $alreadyClaimed = RedEnvelopeClaim::where([
                'red_envelope_id' => $envelope->id,
                'user_id' => $user->id
            ])->exists();

            if ($alreadyClaimed) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'You already claimed this envelope',
                ], 422);
            }

            // 2. Status check
            if ($envelope->status !== 'active') {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                     'sender' => [
                        'id'    => $sender->id,
                        'name'  => $sender->name,
                        'image' => $sender->image ? Helper::showImage($sender->image, true) : null,
                    ],
                    'message' => 'The red envelope has been fully claimed',
                ], 422);
            }

            // 3. Expiry check
            if ($envelope->expires_at && now()->greaterThan($envelope->expires_at)) {
                $envelope->status = 'expired';
                $envelope->save();

                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'sender' => [
                        'id'    => $sender->id,
                        'name'  => $sender->name,
                        'image' => $sender->image ? Helper::showImage($sender->image, true) : null,
                    ],
                    'message' => ' The red envelope has expired. Claimed amounts are in the coin history',
                ], 422);
            }

            // 4. Remaining check
            if ($envelope->remaining_users <= 0 || $envelope->remaining_amount <= 0) {
                $envelope->status = 'completed';
                $envelope->save();

                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'sender' => [
                        'id'    => $sender->id,
                        'name'  => $sender->name,
                        'image' => $sender->image ? Helper::showImage($sender->image, true) : null,
                    ],
                    'message' => ' The red envelope has been fully claimed',
                ], 422);
            }

            // 5. Random Amount Logic

            if ($envelope->remaining_users == 1) {
                // last user
                $amount = $envelope->remaining_amount;
            } else {

                $min = 1;

                $max = floor($envelope->remaining_amount / $envelope->remaining_users * 2);

                if ($max < $min) {
                    $max = $min;
                }

                $amount = rand($min, $max);

                // safety
                if ($amount > $envelope->remaining_amount) {
                    $amount = $envelope->remaining_amount;
                }
            }

            // 6. Claim record
            RedEnvelopeClaim::create([
                'red_envelope_id' => $envelope->id,
                'user_id' => $user->id,
                'room_id' => $envelope->room_id,
                'amount' => $amount,
                'claimed_at' => now(),
            ]);

            // 7. Update envelope
            $envelope->claimed_amount += $amount;
            $envelope->claimed_users += 1;
            $envelope->remaining_amount -= $amount;
            $envelope->remaining_users -= 1;

            // completed check
            if ($envelope->remaining_users <= 0 || $envelope->remaining_amount <= 0) {
                $envelope->status = 'completed';
            }

            $envelope->save();

            // 8. Credit user wallet
            $user->total_points += $amount;
            $user->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Claim successful',
                'data' => [
                    'amount' => $amount,
                    'remaining_amount' => (int) $envelope->remaining_amount,
                    'remaining_users' => (int) $envelope->remaining_users,
                    'status' => $envelope->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function redEnvelopeDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'red_envelope_id' => 'required|integer|exists:red_envelopes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $authUser = Auth::user();

            $redEnvelope = RedEnvelope::query()
                ->with([
                    'sender:id,name,uid,image',
                    'claims' => function ($query) {
                        $query->with('user:id,name,uid,image')
                            ->orderByDesc('claimed_at');
                    }
                ])
                ->where('id', $request->red_envelope_id)
                ->first();

            if (!$redEnvelope) {
                return response()->json([
                    'status' => false,
                    'message' => 'Red envelope not found',
                ], 404);
            }

            $myClaim = $redEnvelope->claims
                ->where('user_id', $authUser->id)
                ->first();

            $claimUsersList = $redEnvelope->claims->map(function ($claim) {
                return [
                    'claim_id'    => $claim->id,
                    'user_id'     => $claim->user_id,
                    'name'        => optional($claim->user)->name,
                    'uid'         => optional($claim->user)->uid,
                    'image'       => optional($claim->user)->image
                        ? \App\Helper\Helper::showImage($claim->user->image, true)
                        : null,
                    'amount'      => (int) $claim->amount,
                    'claimed_at'  => $claim->claimed_at
                        ? $claim->claimed_at->format('Y/m/d H:i')
                        : null,
                ];
            })->values();

            return response()->json([
                'status' => true,
                'message' => 'Red envelope details fetched successfully',
                'data' => [
                    'id' => $redEnvelope->id,
                    'room_id' => $redEnvelope->room_id,
                    'status' => $redEnvelope->status,
                    'expires_at' => $redEnvelope->expires_at,

                    // 🔥 TOP SECTION → envelope creator detail
                    'sender' => [
                        'id'    => optional($redEnvelope->sender)->id,
                        'name'  => optional($redEnvelope->sender)->name,
                        'uid'   => optional($redEnvelope->sender)->uid,
                        'image' => optional($redEnvelope->sender)->image
                            ? \App\Helper\Helper::showImage($redEnvelope->sender->image, true)
                            : null,
                    ],

                    'title' => optional($redEnvelope->sender)->name
                        ? optional($redEnvelope->sender)->name . "'s red envelope"
                        : 'Red envelope',

                    // 🔥 MIDDLE SECTION → current login user claim info
                    'my_claim' => [
                        'amount' => $myClaim ? (int) $myClaim->amount : 0,
                        'text'   => $myClaim ? 'Credited to your account' : null,
                        'claimed_at' => $myClaim && $myClaim->claimed_at
                            ? $myClaim->claimed_at->format('Y/m/d H:i')
                            : null,
                    ],

                    // 🔥 SUMMARY
                    'summary' => [
                        'claimed_users'    => (int) $redEnvelope->claimed_users,
                        'total_users'      => (int) $redEnvelope->total_users,
                        'total_amount'     => (int) $redEnvelope->total_amount,
                        'claimed_amount'   => (int) $redEnvelope->claimed_amount,
                        'remaining_amount' => (int) $redEnvelope->remaining_amount,
                        'remaining_users'  => (int) $redEnvelope->remaining_users,
                    ],

                    // 🔥 BOTTOM LIST → all claim users
                    'claim_users' => $claimUsersList,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
