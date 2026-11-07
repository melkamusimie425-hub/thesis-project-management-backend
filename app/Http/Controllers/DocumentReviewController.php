<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentReview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentReviewController extends Controller
{
    /**
     * List reviews for a document (EUC-09 view document review).
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'document_id' => ['required', 'exists:documents,id'],
        ]);

        $reviews = DocumentReview::with('reviewer:id,first_name,middle_name,last_name,role')
            ->where('document_id', $data['document_id'])
            ->latest()
            ->get();

        return response()->json(['reviews' => $reviews]);
    }

    /**
     * Review a project / thesis document (EUC-10 / EUC-21). Advisor only.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'document_id' => ['required', 'exists:documents,id'],
            'comment' => ['required', 'string'],
            'recommendation' => ['required', Rule::in(['Approved', 'Needs Revision', 'Rejected'])],
        ]);

        $document = Document::findOrFail($data['document_id']);

        $review = DocumentReview::create([
            'document_id' => $document->id,
            'reviewer_id' => $request->user()->id,
            'comment' => $data['comment'],
            'recommendation' => $data['recommendation'],
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'review' => $review->load('reviewer:id,first_name,middle_name,last_name,role'),
        ], 201);
    }
}
