<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Helper\Helper;
use App\Models\AboutUs;
use App\Models\Faq;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class HelpAndSupportController extends Controller
{
    public function index()
    {
        $about = AboutUs::first();
        return view('help_and_support.about', compact('about'));
    }

    // Save or update the store location
    public function save(Request $request)
    {
        $request->validate([
            'content' => 'required',
        ]);

        $about = AboutUs::first();
        if (!$about) {
            $about = new AboutUs();
        }

        $about->content = $request->content;
        $about->save();

        return redirect()->back()->with('success', 'About Us saved successfully!');
    }



    public function faqIndex(Request $request)
    {
        if ($request->ajax()) {
            $data = Faq::orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->editColumn('status', function ($row) {
                    return $row->status === 1
                        ? '<span class="badge fw-semi-bold rounded-pill status badge-light-success">Active</span>'
                        : '<span class="badge fw-semi-bold rounded-pill status badge-light-danger">Inactive</span>';
                })
                ->editColumn('answer', function ($row) {
                    $fullAnswer = $row->answer;
                    $words = explode(' ', $fullAnswer);
                    $shortAnswer = count($words) > 5 ? implode(' ', array_slice($words, 0, 5)) . '...' : $fullAnswer;

                    return '<span answer="' . e($fullAnswer) . '">' . e($shortAnswer) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="text-600 btn-reveal dropdown-toggle btn btn-link btn-sm" id="drop' . $row->id . '" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="fas fa-ellipsis-h fs--1"></span>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="drop' . $row->id . '">';

                    if (Helper::userCan(104, 'can_edit')) {
                        $btn .= '<a class="dropdown-item" href="' . route('faq.edit', $row->id) . '">Edit</a>';
                    }

                    if (Helper::userCan(104, 'can_delete')) {
                        $btn .= '<a class="dropdown-item text-danger delete" href="javascript:void(0)" data-id="' . $row->id . '">Delete</a>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['status','answer', 'action'])
                ->make(true);
        }

        return view('help_and_support.faq-index');
    }

    public function faqAdd()
    {
        return view('help_and_support.faq-add');
    }

    public function saveFaq(Request $request)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'status' => 'required',
        ]);



        $faq = new Faq();


        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->status = $request->status;
        $faq->save();

        return redirect()->route('faq.index')->with('success', 'FAQs saved successfully!');
    }

    public function faqEdit($id): View|RedirectResponse
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return to_route('faq.index')->withError('faq not found!');
        }

        return view('help_and_support.faq-edit', compact('faq'));
    }

    public function faqUpdate(Request $request, $id)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'status' => 'required',
        ]);



        $faq = Faq::find($id);


        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->status = $request->status;
        $faq->save();

        return redirect()->route('faq.index')->with('success', 'FAQs saved successfully!');
    }

    public function delete(Request $request): JsonResponse
    {
        return Helper::deleteRecord(new Faq, $request->id);
    }
}
