<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Country;
use App\Helper\Helper;
use App\Models\Frame;
use App\Models\LuckyGiftWinningSetting;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Models\Post;
use App\Models\PostMedia;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class MomentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = TopicCategory::latest();

            return DataTables::of($query)
                ->addIndexColumn()

                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })

                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('topic.category.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('moments.topic_category.index');
    }

    public function add(): View
    {
        return view('moments.topic_category.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name'            => 'required|string|max:255',
            'status'          => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            TopicCategory::create([
                'name'            => $request->name,
                'status'          => $request->status,
            ]);

            return redirect()
                ->route('topic.category')
                ->with('success', 'Topic Category added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $topicCategory = TopicCategory::find($id);

        if (!$topicCategory) {
            return to_route('topic.category')->withError('Topic Category Not Found!');
        }
        return view('moments.topic_category.edit', compact('topicCategory'));
    }

    public function update(Request $request, $id)
    {
        $topicCategory = TopicCategory::findOrFail($id);

        $rules = [
            'name'            => 'required|string|max:255',
            'status'          => 'required|in:0,1',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $topicCategory) {

            $data = [
                'name'            => $request->name,
                '' => $request->visibility_type,
                'status'          => $request->status,
            ];

            $topicCategory->update($data);

            return redirect()->route('topic.category')->with('success', 'Topic Category updated successfully');
        });
    }


    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new TopicCategory, $request->id);
    }


    public function topicIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Topic::with('topicCat')->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('category', function ($row) {
                    return $row->topicCat ? $row->topicCat->name : 'N/A';
                })
                ->editColumn('icon', function ($row) {
                    return '<img src="' . asset('storage/' . $row->icon) . '" width="40">';
                })
                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('topic.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['icon', 'status', 'action'])
                ->make(true);
        }

        return view('moments.topic.index');
    }

    public function topicAdd(): View
    {
        $category = TopicCategory::where('status', 1)->get();
        return view('moments.topic.add', compact('category'));
    }


    public function topicSave(Request $request)
    {
        $rules = [
            'category'        => 'required',
            'name'            => 'required|string|max:255',
            'description'     => 'required',
            'icon'            => 'required|image|mimes:png,jpg,jpeg,webp',
            'status'          => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            $icon = Helper::saveFile($request->file('icon'), 'topic_images');

            Topic::create([
                'category'            => $request->category,
                'name'            => $request->name,
                'description' => $request->description,
                'status'          => $request->status,
                'icon'             => $icon,
            ]);

            return redirect()
                ->route('topic')
                ->with('success', 'Topic added successfully');
        });
    }

    public function topicEdit($id): View|RedirectResponse
    {
        $topic = Topic::find($id);

        if (!$topic) {
            return to_route('topic')->withError('Topic Not Found!');
        }
        $category = TopicCategory::where('status', 1)->get();
        return view('moments.topic.edit', compact('topic', 'category'));
    }

    public function topicUpdate(Request $request, $id)
    {
        $topic = Topic::findOrFail($id);

        $rules = [
            'category'        => 'required',
            'name'            => 'required|string|max:255',
            'description'     => 'required',
            'icon'            => 'nullable|image|mimes:png,jpg,jpeg,webp',
            'status'          => 'required|in:0,1',
        ];

        $request->validate($rules);

        return DB::transaction(function () use ($request, $topic) {

            $data = [
                'category'            => $request->category,
                'name'            => $request->name,
                'description' => $request->description,
                'status'          => $request->status,
            ];

            if ($request->hasFile('icon')) {

                if ($topic->icon && file_exists(public_path($topic->icon))) {
                    @unlink(public_path($topic->icon));
                }

                $data['icon'] = Helper::saveFile($request->file('icon'), 'topic_images');
            }


            $topic->update($data);

            return redirect()->route('topic')->with('success', 'Topic updated successfully');
        });
    }


    public function topicDelete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Topic, $request->id);
    }

    public function postIndex(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = Post::with([
                'user:id,name,uid,image',
                'media:id,post_id,file_path,file_type',
            ])->withCount(['likes', 'comments'])->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                
                ->addColumn('user_info', function ($row) {

                    if (!$row->user) {return '-';}

                    $image = $row->user->image
                        ? Helper::showImage($row->user->image, true)
                        : asset('assets/img/avatar.png');

                    return '
                        <div class="d-flex align-items-center gap-2 user-profile-trigger"
                             data-user-id="'.$row->user->id.'" style="cursor:pointer;">

                            <img src="'.$image.'" width="40" height="40" class="rounded-circle">

                            <div>
                                <div class="fw-bold">'.e($row->user->name).'</div>
                                <small class="text-muted">'.e($row->user->uid).'</small>
                            </div>

                        </div>
                    ';
                })

                ->addColumn('post_type', function ($row) {
                    $hasText = !empty($row->description);
                    $hasMedia = $row->media && $row->media->count() > 0;

                    if ($hasText && $hasMedia) {
                        return 'text+picture';
                    } elseif ($hasText) {
                        return 'text';
                    } elseif ($hasMedia) {
                        $first = $row->media->first();
                        return Str::startsWith($first->file_type, 'video/') ? 'video' : 'picture';
                    }

                    return '-';
                })

                ->addColumn('title_text', function ($row) {
                    return e(Str::limit($row->description ?? '-', 45));
                })

                ->addColumn('picture_preview', function ($row) {
                    if (!$row->media || $row->media->isEmpty()) {
                        return '-';
                    }

                    $firstMedia = $row->media->first();
                    $url = \Helper::showImage($firstMedia->file_path, true);

                    if (Str::startsWith($firstMedia->file_type, 'image/')) {
                        return '<img src="' . $url . '" width="70" height="95" style="object-fit:cover;border-radius:4px;">';
                    }

                    if (Str::startsWith($firstMedia->file_type, 'video/')) {
                        return '
                            <video width="70" height="95" style="object-fit:cover;border-radius:4px;" muted>
                                <source src="' . $url . '" type="' . e($firstMedia->file_type) . '">
                            </video>
                        ';
                    }

                    return '-';
                })

                ->addColumn('location_name', function ($row) {
                    return e($row->country ?? '-');
                })

                ->addColumn('likes_count_show', function ($row) {
                    return $row->likes_count ?? 0;
                })

                ->addColumn('comments_count_show', function ($row) {
                    return $row->comments_count ?? 0;
                })

                // ->addColumn('recommended_value', function ($row) {
                //     return 0;
                // })

                // ->addColumn('state_text', function ($row) {
                //     $status = $row->status ?? 1;

                //     return match ((int)$status) {
                //         1 => 'Approved by the review',
                //         0 => 'Pending review',
                //         2 => 'Rejected',
                //         default => 'Approved by the review',
                //     };
                // })

                ->addColumn('submission_time', function ($row) {
                    return optional($row->created_at)->format('Y-m-d H:i');
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a href="' . route('posts.details', $row->id) . '" class="dropdown-item">Details</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })

                ->rawColumns([
                    'user_info',
                    'picture_preview',
                    'action',
                ])
                ->make(true);
        }

        return view('moments.index');
    }

    public function details($id)
    {
        $post = Post::with([
            'user:id,name,uid,image,country',
            'topic:id,name',
            'media:id,post_id,file_path,file_type',
        ])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        return view('moments.post_details', compact('post'));
    }

    public function postDelete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:posts,id',
        ]);

        DB::beginTransaction();

        try {
            $post = Post::with('media')->find($request->id);

            if (!$post) {
                return response()->json([
                    'status' => false,
                    'message' => 'Post not found.',
                ], 404);
            }

            // delete media files from storage
            if ($post->media && $post->media->count()) {
                foreach ($post->media as $media) {
                    if (!empty($media->file_path) && Storage::disk('public')->exists($media->file_path)) {
                        Storage::disk('public')->delete($media->file_path);
                    }
                }

                // delete media records
                PostMedia::where('post_id', $post->id)->delete();
            }

            // delete post
            $post->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Post deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Post delete failed', [
                'post_id' => $request->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
