<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helper\Helper;
use App\Models\AppUser;
use App\Models\CommentLike;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostGift;
use App\Models\PostLike;
use App\Models\PostMedia;
use App\Models\HiddenPost;
use App\Models\PostReport;
use App\Models\Topic;
use App\Models\TopicLike;
use App\Models\ItemDelivery;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Gift;
use App\Models\Notification;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\FirebaseService;

class MomentController extends Controller
{
    public function topiclist()
    {
        $userId = Auth::id();
        $topicLists = Topic::where('status', 1)->orderByDesc('created_at')->get();

        $topicLists = $topicLists->map(function ($list) use ($userId) {

            $isLiked = false;

            if ($userId) {
                $isLiked = TopicLike::where('topic_id', $list->id)
                    ->where('user_id', $userId)
                    ->exists();
            }

            return [
                'id' => $list->id,
                'topic_name' => $list->name,
                'description' => $list->description,
                'image' => Helper::showImage($list->icon, true),
                'is_liked' => $isLiked,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Topic List get successfully',
            'data' => $topicLists
        ]);
    }

    public function toggleTopicLike(Request $request)
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id'
        ]);

        $userId = Auth::id();
        $topicId = $request->topic_id;

        $like = TopicLike::where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->first();

        if ($like) {
            $like->delete();

            return response()->json([
                'status' => true,
                'message' => 'Topic unliked successfully',
                'liked' => false
            ]);
        }

        TopicLike::create([
            'user_id' => $userId,
            'topic_id' => $topicId
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Topic liked successfully',
            'liked' => true
        ]);
    }


    public function store(Request $request)
    {
        $user = Auth::user();

        $validate = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4|max:30240'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validate->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $post = Post::create([
                'user_id' => $user->id,
                'topic_id' => $request->topic_id,
                'description' => $request->description,
                'country' => ucwords(strtolower($user->country))
            ]);

            $imageForNotification = null;

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {

                    $path = Helper::saveFile($file, 'post_media');
                    $fileType = $file->getClientMimeType();

                    PostMedia::create([
                        'post_id' => $post->id,
                        'file_path' => $path,
                        'file_type' => $fileType
                    ]);

                    // Save only first image for notification
                    if ($imageForNotification === null && str_contains($fileType, 'image')) {
                        $imageForNotification = $path;
                    }
                }
            }

            Notification::create([
                'sender_id' => $user->id,
                'receiver_id' => null,
                'type' => 'post',
                'title' => 'New Post',
                'message' => 'A new post has been created',
                'image' => $imageForNotification
            ]);

            $firebase = new FirebaseService();

            $tokens = AppUser::whereNotNull('fcm_token')
                ->select('id', 'fcm_token')
                ->get();

            foreach ($tokens as $token) {

                $firebase->sendNotification(
                    $token->fcm_token,
                    "New Post",
                    "A new post has been created",
                    $imageForNotification ? Helper::showImage($imageForNotification, true) : null
                );
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Post created successfully',
                // 'data' => $post->load('media')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function hotPosts(Request $request)
    {
        $userId = Auth::id();
        $authUser = Auth::user();
        $oneWeekAgo = now()->subWeek();

        $posts = Post::with(['user.levelInfo', 'media', 'topic'])
            ->withCount([
                'comments as weekly_comments_count' => function ($query) use ($oneWeekAgo) {
                    $query->where('created_at', '>=', $oneWeekAgo);
                },
                'likes as weekly_likes_count' => function ($query) use ($oneWeekAgo) {
                    $query->where('created_at', '>=', $oneWeekAgo);
                },
                'comments',
                'likes'
            ])
            ->whereRaw('LOWER(country) = ?', [strtolower($authUser->country)])
            ->orderByRaw("
                CASE 
                    WHEN created_at >= ? THEN 0
                    ELSE 1
                END
            ", [$oneWeekAgo])
            ->orderByDesc('weekly_comments_count')
            ->orderByDesc('weekly_likes_count')
            ->orderByDesc('comments_count')
            ->orderByDesc('likes_count')
            ->orderByDesc('created_at')
            ->get();

        $posts = $posts->map(function ($post) use ($userId) {

            $isLiked = false;

            if ($userId) {
                $isLiked = PostLike::where('post_id', $post->id)
                    ->where('user_id', $userId)
                    ->exists();
            }

            $frame = ItemDelivery::with('frame')
                ->where('recipient', $post->user_id)
                ->where('type', 'frame')
                ->whereDate('end_at', '>=', now())
                ->orderByDesc('created_at')
                ->first();

            return [
                'id' => $post->id,
                'topic_name' => $post->topic?->name,
                'description' => $post->description,
                'comments_count' => $post->comments_count,
                'likes_count' => $post->likes_count,
                'time_ago' => $post->created_at->diffForHumans(),
                'is_liked' => $isLiked,
                'user' => [
                    'name' => $post->user?->name,
                    'gender' => $post->user?->gender,
                    'image' => $post->user?->image
                        ? Helper::showImage($post->user->image, true)
                        : null,
                    'frame_image' => $frame?->frame?->icon
                        ? Helper::showImage($frame->frame->icon, true)
                        : null,
                    'user_level_icon' => $post->user?->levelInfo?->icon
                        ? Helper::showImage($post->user->levelInfo->icon, true)
                        : null,
                ],

                'media' => $post->media->map(function ($media) {
                    return [
                        'file_type' => $media->file_type,
                        'file_url' => Helper::showImage($media->file_path, true),
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Hot posts fetched successfully',
            'data' => $posts
        ]);
    }

    public function newPosts(Request $request)
    {
        $userId = Auth::id();
        $authUser = Auth::user();

        $posts = Post::with(['user.levelInfo', 'media'])
            ->withCount(['comments', 'likes'])
            ->whereRaw('LOWER(country) = ?', [strtolower($authUser->country)])
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('post_id')
                    ->from('hidden_posts')
                    ->where('user_id', $userId);
            })
            // ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->get();

        $posts = $posts->map(function ($post) use ($userId) {
            $isLiked = false;

            if ($userId) {
                $isLiked = PostLike::where('post_id', $post->id)
                    ->where('user_id', $userId)
                    ->exists();
            }

            $frame = ItemDelivery::with('frame')
                ->where('recipient', $post->user_id)
                ->where('type', 'frame')
                ->whereDate('end_at', '>=', now())
                ->orderByDesc('created_at')
                ->first();

            return [
                'id' => $post->id,
                'topic_name' => $post->topic?->name,
                'description' => $post->description,
                'comments_count' => $post->comments_count,
                'likes_count' => $post->likes_count,
                'time_ago' => $post->created_at->diffForHumans(),
                'is_liked' => $isLiked,

                'user' => [
                    'name' => $post->user?->name,
                    'gender' => $post->user?->gender,
                    'image' => $post->user?->image
                        ? Helper::showImage($post->user->image, true)
                        : null,
                    'frame_image' => $frame?->frame?->icon
                        ? Helper::showImage($frame->frame->icon, true)
                        : null,
                    'user_level_icon' => $post->user?->levelInfo?->icon
                        ? Helper::showImage($post->user->levelInfo->icon, true)
                        : null,
                ],

                'media' => $post->media->map(function ($media) {
                    return [
                        'file_type' => $media->file_type,
                        'file_url' => Helper::showImage($media->file_path, true),
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'New posts fetched successfully',
            'data' => $posts
        ]);
    }

    public function topicPosts(Request $request, $topicId)
    {
        $userId = Auth::id();
        $authUser = Auth::user();
        $topic = Topic::find($topicId);

        if (!$topic) {
            return response()->json([
                'status' => false,
                'message' => 'Topic not found'
            ], 404);
        }

        $posts = Post::with(['user.levelInfo', 'media'])
            ->where('topic_id', $topicId)
            ->whereRaw('LOWER(country) = ?', [strtolower($authUser->country)])
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('post_id')
                    ->from('hidden_posts')
                    ->where('user_id', $userId);
            })
            ->withCount(['comments', 'likes'])
            ->orderByDesc('created_at')
            ->get();

        $posts = $posts->map(function ($post) use ($userId) {
            $isLiked = false;

            if ($userId) {
                $isLiked = PostLike::where('post_id', $post->id)
                    ->where('user_id', $userId)
                    ->exists();
            }
            $frame = ItemDelivery::with('frame')
                ->where('recipient', $post->user_id)
                ->where('type', 'frame')
                ->whereDate('end_at', '>=', now())
                ->orderByDesc('created_at')
                ->first();
            return [
                'id' => $post->id,
                'description' => $post->description,
                'comments_count' => $post->comments_count,
                'likes_count' => $post->likes_count,
                'time_ago' => $post->created_at->diffForHumans(),
                'is_liked' => $isLiked,

                'user' => [
                    'name' => $post->user?->name,
                    'gender' => $post->user?->gender,
                    'image' => $post->user?->image
                        ? Helper::showImage($post->user->image, true)
                        : null,
                    'frame_image' => $frame?->frame?->icon
                        ? Helper::showImage($frame->frame->icon, true)
                        : null,
                    'user_level_icon' => $post->user?->levelInfo?->icon
                        ? Helper::showImage($post->user->levelInfo->icon, true)
                        : null,
                ],

                'media' => $post->media->map(function ($media) {
                    return [
                        'file_type' => $media->file_type,
                        'file_url' => Helper::showImage($media->file_path, true),
                    ];
                })
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Topic posts fetched successfully',
            'topic' => [
                'id' => $topic->id,
                'name' => $topic->name,
                'description' => $topic->description,
            ],
            'posts' => $posts
        ]);
    }

    public function addComment(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'comment' => 'required|string',
            'parent_id' => 'nullable|exists:post_comments,id'
        ]);

        $comment = PostComment::create([
            'post_id' => $request->post_id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'parent_id' => $request->parent_id
        ]);

        $comment->load('user');

        return response()->json([
            'status' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'parent_id' => $comment->parent_id,
                'created_at' => $comment->created_at->diffForHumans()
            ]
        ]);
    }

    public function deleteComment(Request $request)
    {
        $request->validate([
            'comment_id' => 'required|exists:post_comments,id'
        ]);

        $comment = PostComment::find($request->comment_id);

        if ($comment->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to delete this comment'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    public function togglePostLike(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id'
        ]);

        $userId = Auth::id();
        $postId = $request->post_id;

        $like = PostLike::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();

            return response()->json([
                'status' => true,
                'message' => 'Post unliked successfully',
                'liked' => false
            ]);
        }

        PostLike::create([
            'post_id' => $postId,
            'user_id' => $userId
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post liked successfully',
            'liked' => true
        ]);
    }

    public function postDetails($postId)
    {
        $userId = Auth::id();

        $post = Post::with(['user.levelInfo', 'media', 'topic'])
            ->withCount(['comments', 'likes'])
            ->find($postId);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $isLiked = false;
        if ($userId) {
            $isLiked = PostLike::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->exists();
        }

        $comments = PostComment::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with([
                'user',
                'replies.user',
                'likes' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ])
            ->withCount(['likes'])
            ->latest()
            ->get()
            ->map(function ($comment) use ($userId) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'time_ago' => $comment->created_at->diffForHumans(),

                    'likes_count' => $comment->likes_count,

                    // Auth user liked or not
                    'is_comment_like' => $comment->likes->count() > 0,

                    'user' => [
                        'id' => $comment->user?->id,
                        'name' => $comment->user?->name,
                        'image' => $comment->user?->image
                            ? Helper::showImage($comment->user->image, true)
                            : null,

                    ],

                    // Replies
                    'replies' => $comment->replies->map(function ($reply) use ($userId) {
                        return [
                            'id' => $reply->id,
                            'comment' => $reply->comment,
                            'time_ago' => $reply->created_at->diffForHumans(),

                            'likes_count' => $reply->likes()->count(),

                            //  Auth user liked reply or not
                            'is_comment_like' => CommentLike::where('comment_id', $reply->id)
                                ->where('user_id', $userId)
                                ->exists(),

                            'user' => [
                                'id' => $reply->user?->id,
                                'name' => $reply->user?->name,
                                'image' => $reply->user?->image
                                    ? Helper::showImage($reply->user->image, true)
                                    : null
                            ]
                        ];
                    })
                ];
            });

        $frame = ItemDelivery::with('frame')
            ->where('recipient', $post->user_id)
            ->where('type', 'frame')
            ->whereDate('end_at', '>=', now())
            ->orderByDesc('created_at')
            ->first();
        // $likedUsers = PostLike::where('post_id', $post->id)
        //     ->with('user')
        //     ->get()
        //     ->map(function ($like) {
        //         return [
        //             'id' => $like->user?->id,
        //             'name' => $like->user?->name,
        //             'gender' => $like->user?->gender,
        //             'image' => $like->user?->image
        //                 ? Helper::showImage($like->user->image, true)
        //                 : null
        //         ];
        //     });


        $postData = [
            'id' => $post->id,
            'topic_name' => $post->topic?->name,
            'description' => $post->description,
            'time_ago' => $post->created_at->diffForHumans(),

            'comments_count' => $post->comments_count,
            'likes_count' => $post->likes_count,

            'is_liked' => $isLiked,

            'user' => [
                'id' => $post->user?->id,
                'name' => $post->user?->name,
                'gender' => $post->user?->gender,
                'image' => $post->user?->image
                    ? Helper::showImage($post->user->image, true)
                    : null,

                'frame_image' => $frame?->frame?->icon
                    ? Helper::showImage($frame->frame->icon, true)
                    : null,
                'user_level_icon' => $post->user?->levelInfo?->icon
                    ? Helper::showImage($post->user->levelInfo->icon, true)
                    : null,
            ],

            'media' => $post->media->map(function ($media) {
                return [
                    'file_type' => $media->file_type,
                    'file_url' => Helper::showImage($media->file_path, true),
                ];
            }),

            'comments' => $comments,

            // 'liked_users' => $likedUsers,

            // 'gift_list' => []
        ];

        return response()->json([
            'status' => true,
            'message' => 'Post details fetched successfully',
            'data' => $postData
        ]);
    }

    public function hidePost(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id'
        ]);

        $userId = Auth::id();
        $postId = $request->post_id;

        $alreadyHidden = HiddenPost::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();

        if ($alreadyHidden) {
            return response()->json([
                'status' => false,
                'message' => 'Post already hidden'
            ]);
        }

        HiddenPost::create([
            'user_id' => $userId,
            'post_id' => $postId
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post hidden successfully'
        ]);
    }

    public function reportPost(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'reason'  => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $postId = $request->post_id;

        // $alreadyReported = PostReport::where('user_id', $userId)
        //     ->where('post_id', $postId)
        //     ->exists();

        // if ($alreadyReported) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'You have already reported this post'
        //     ]);
        // }

        PostReport::create([
            'user_id' => $userId,
            'post_id' => $postId,
            'reason'  => $request->reason,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post reported successfully'
        ]);
    }

    public function toggleCommentLike(Request $request)
    {
        $request->validate([
            'comment_id' => 'required|exists:post_comments,id'
        ]);

        $userId = Auth::id();

        $like = CommentLike::where('comment_id', $request->comment_id)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();

            return response()->json([
                'status' => true,
                'message' => 'Comment unliked',
                'liked' => false
            ]);
        }

        CommentLike::create([
            'comment_id' => $request->comment_id,
            'user_id' => $userId
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Comment liked',
            'liked' => true
        ]);
    }

    public function postGift(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'post_id'       => 'required|exists:posts,id',
            'gift_id'       => 'required|exists:gifts,id',
            'multiplier'    => 'nullable|integer|min:1|max:999'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()
            ]);
        }

        $sender = Auth::user();
        $multiplier = $request->multiplier ?? 1;

        DB::beginTransaction();

        try {
            $gift = Gift::where('id', $request->gift_id)
                ->where('status', 1)
                ->first();

            if (!$gift) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gift not available'
                ], 400);
            }

            $post = Post::with('user')->findOrFail($request->post_id);
            $receiverId = $post->user_id;

            if ($receiverId == $sender->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot send gift to your own post'
                ], 403);
            }

            $totalCost = $gift->price * $multiplier;

            if ($sender->total_points < $totalCost) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient balance'
                ], 400);
            }

            $transactions = PostGift::create([
                'post_id'     => $request->post_id,
                'sender_id'   => $sender->id,
                'receiver_id' => $receiverId,
                'gift_id'     => $gift->id,
                'gift_value'  => $gift->price,
                'quantity'  => $multiplier,
                'total_coins'  => $gift->price * $multiplier,
            ]);

            // AppUser::where('id', $receiverId)
            //     ->increment('total_points', $gift->price * $multiplier);

            AppUser::where('id', $receiverId)->update([
                'total_points' => DB::raw('total_points + ' . ($gift->price * $multiplier)),
                'total_value'  => DB::raw('total_value + ' . ($gift->price * $multiplier)),
            ]);


            $user = AppUser::select('id', 'total_value', 'user_level')
                ->where('id', $receiverId)
                ->first();


            $level = UserLevel::where('experience_cap', '<=', $user->total_value)
                ->orderByDesc('experience_cap')
                ->first();
            // dd($level->grade);

            if ($level && $user->user_level != $level->grade) {
                AppUser::where('id', $receiverId)
                    ->update(['user_level' => $level->grade]);
            }


            $sender->decrement('total_points', $totalCost);

            $familyId = FamilyMember::where('user_id', $sender->id)
                ->whereNull('left_at')
                ->value('family_id');

            if ($familyId) {
                Family::where('id', $familyId)
                    ->increment('total_points', $totalCost);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Gift sent successfully',
                'multiplier' => $multiplier,
                'total_cost' => $totalCost,
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
