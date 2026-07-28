<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Models\ProgrammeDocument;
use App\Domains\Projects\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProgrammeDocumentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ServiceDelivery/Documents', [
            'programs' => Program::query()->select('id', 'title')->orderBy('title')->get(),
            'projects' => Project::query()->select('id', 'name')->orderBy('name')->get(),
            'documents' => ProgrammeDocument::query()
                ->with(['program:id,title', 'project:id,name', 'uploader:id,name'])
                ->latest('id')
                ->get()
                ->map(fn (ProgrammeDocument $document) => [
                    'id' => $document->id,
                    'program_id' => $document->program_id,
                    'program_title' => $document->program?->title,
                    'project_id' => $document->project_id,
                    'project_name' => $document->project?->name,
                    'category' => $document->category,
                    'name' => $document->name,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                    'uploaded_by_name' => $document->uploader?->name,
                    'download_url' => route('programme-documents.download', $document),
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'category' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('programme-documents');

        ProgrammeDocument::query()->create([
            'program_id' => $data['program_id'],
            'project_id' => $data['project_id'] ?? null,
            'category' => $data['category'],
            'name' => $data['name'],
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        return redirect()->back()->with('success', 'Programme document uploaded.');
    }

    public function download(ProgrammeDocument $programmeDocument)
    {
        $extension = pathinfo($programmeDocument->path, PATHINFO_EXTENSION);
        $downloadName = $programmeDocument->name;

        if ($extension !== '' && ! str_ends_with(strtolower($downloadName), '.'.strtolower($extension))) {
            $downloadName .= '.'.$extension;
        }

        return Storage::download($programmeDocument->path, $downloadName);
    }
}
