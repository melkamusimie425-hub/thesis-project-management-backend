<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /**
     * Upload a document (SUC-10). Polymorphic: project / proposal / thesis.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'work_type' => ['required', Rule::in(['IndustrialProjectGroup', 'Proposal', 'Thesis'])],
            'work_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:51200'], // 50 MB
            'category' => ['nullable', Rule::in(['draft', 'final', 'evaluation', 'other'])],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'local');

        $document = Document::create([
            'uploaded_by' => $request->user()->id,
            'work_type' => $data['work_type'],
            'work_id' => $data['work_id'],
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category' => $data['category'] ?? 'draft',
            'is_published' => $data['is_published'] ?? false,
            'description' => $data['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => $document,
        ], 201);
    }

    /**
     * List documents for a work, or published documents for the repository.
     */
    public function index(Request $request)
    {
        $query = Document::with(['uploader:id,first_name,middle_name,last_name', 'reviews'])
            ->latest();

        if ($request->filled('work_type') && $request->filled('work_id')) {
            $query->where('work_type', $request->get('work_type'))
                  ->where('work_id', $request->get('work_id'));
        }

        // Repository: published works viewable by guests (SUC-20).
        if ($request->boolean('repository')) {
            $query->where('is_published', true)
                  ->where('category', 'final');
        }

        return response()->json(['documents' => $query->paginate(20)]);
    }

    /**
     * Download a document (SUC-11).
     */
    public function download(Request $request, Document $document)
    {
        // Published documents are public; otherwise the user must be authenticated.
        if (! $document->is_published && ! $request->user()) {
            abort(403, 'You are not authorized to download this document.');
        }

        if (! Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function destroy(Request $request, Document $document)
    {
        if ($request->user()->id !== $document->uploaded_by
            && ! in_array($request->user()->role, ['Administrator', 'UGPC', 'GPC'])) {
            return response()->json(['message' => 'You are not authorized to delete this document.'], 403);
        }

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}
