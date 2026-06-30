<?php

namespace App\Http\Controllers;


use App\Helper\Helper;
use App\Models\Voice;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {

            $query = Voice::latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('voice', function ($row) {
                    if (!$row->voice) {
                        return '-';
                    }

                    return '
                        <audio controls style="width: 180px;">
                            <source src="' . asset('storage/' . $row->voice) . '" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    ';
                })

                ->editColumn('status', function ($row) {
                    return $row['status'] == 1 ? '<small class="badge fw-semi-bold rounded-pill status badge-light-success"> Enable</small>' : '<small class="badge fw-semi-bold rounded-pill status badge-light-danger"> Disable</small>';
                })
                ->editColumn('validity', function ($row) {
                    return $row['validity'] ? $row['validity'] : '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                    <button class="btn btn-sm btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('voice.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(105, 'can_delete')) {
                        $btn .= '<button class="dropdown-item text-danger delete" data-id="' . $row->id . '">Delete</button>';
                    }

                    $btn .= '</div></div>';

                    return $btn;
                })
                ->rawColumns(['voice', 'status', 'action'])
                ->make(true);
        }

        return view('voice.index');
    }

    public function add(): View
    {
        return view('voice.add');
    }


    public function save(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'short_tag' => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'icon' => 'required|image|mimes:png,jpg,jpeg,webp|max:5120',
            'gif' => 'nullable',
            'status' => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin'] = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity'] = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return DB::transaction(function () use ($request) {

            // upload files
            $iconFile = Helper::saveFile($request->file('icon'), 'icon');
            $gifFile = $request->hasFile('gif')
                ? Helper::saveFile($request->file('gif'), 'gif')
                : null;

            Voice::create([
                'name' => $request->name,
                'short_tag' => $request->short_tag,
                'visibility_type' => $request->visibility_type,
                'status' => $request->status,

                'icon' => $iconFile,
                'gif' => $gifFile,

                'needcoin' => $request->visibility_type === 'in_app'
                    ? array_values($request->needcoin)
                    : null,

                'validity' => $request->visibility_type === 'in_app'
                    ? array_values($request->validity)
                    : null,
            ]);

            return redirect()
                ->route('voice')
                ->with('success', 'Voice added successfully');
        });
    }

    public function edit($id): View|RedirectResponse
    {
        $voice = Voice::find($id);

        if (!$voice) {
            return to_route('voice')->withError('Voice Not Found!');
        }
        return view('voice.edit', compact('voice'));
    }

    public function update(Request $request, $id)
    {
        $voice = Voice::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'visibility_type' => 'required|in:backend,in_app',
            'voice' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'status' => 'required|in:0,1',
        ];

        if ($request->visibility_type == 'in_app') {

            $rules['needcoin'] = 'required|array|min:1';
            $rules['needcoin.*'] = 'required|integer|min:1|max:9999999';

            $rules['validity'] = 'required|array|min:1';
            $rules['validity.*'] = 'required|integer|min:1';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $voice) {

            $data = [
                'name' => $request->name,
                'visibility_type' => $request->visibility_type,
                'status' => $request->status,
            ];

            if ($request->visibility_type === 'in_app') {
                $data['needcoin'] = array_values($request->needcoin);
                $data['validity'] = array_values($request->validity);
            } else {
                $data['needcoin'] = null;
                $data['validity'] = null;
            }

            if ($request->hasFile('voice')) {

                if ($voice->voice && file_exists(public_path('storage/' . $voice->voice))) {
                    @unlink(public_path('storage/' . $voice->voice));
                }

                $data['voice'] = Helper::saveFile($request->file('voice'), 'voice');
            }

            $voice->update($data);

            return redirect()
                ->route('voice')
                ->with('success', 'Voice updated successfully');
        });
    }



    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Voice, $request->id);
    }
}
