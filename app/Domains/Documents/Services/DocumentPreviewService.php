<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Models\DocumentFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentPreviewService
{
    public function describe(DocumentFile $document): array
    {
        $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));
        $kind = $this->kindFor($document->mime_type, $extension);

        return [
            'kind' => $kind,
            'inline_url' => in_array($kind, ['pdf', 'image', 'text'], true)
                ? route('organization.document-library.files.preview', $document)
                : null,
            'excerpt' => $this->extractExcerpt($document, $kind),
            'thumbnail_label' => strtoupper($extension ?: ($kind === 'text' ? 'TXT' : 'FILE')),
        ];
    }

    public function previewResponse(DocumentFile $document)
    {
        $extension = strtolower(pathinfo($document->original_name, PATHINFO_EXTENSION));
        $kind = $this->kindFor($document->mime_type, $extension);

        abort_unless(in_array($kind, ['pdf', 'image', 'text'], true), 404);

        $headers = [
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
        ];

        return Storage::disk($document->disk)->response($document->file_path, $document->original_name, $headers);
    }

    protected function kindFor(?string $mimeType, string $extension): string
    {
        if ($extension === 'pdf' || str_contains((string) $mimeType, 'pdf')) {
            return 'pdf';
        }

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'], true) || str_starts_with((string) $mimeType, 'image/')) {
            return 'image';
        }

        if (in_array($extension, ['txt', 'md', 'csv', 'json', 'xml'], true) || str_starts_with((string) $mimeType, 'text/')) {
            return 'text';
        }

        if (in_array($extension, ['doc', 'docx'], true)) {
            return 'word';
        }

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            return 'spreadsheet';
        }

        if (in_array($extension, ['ppt', 'pptx'], true)) {
            return 'presentation';
        }

        return 'download';
    }

    protected function extractExcerpt(DocumentFile $document, string $kind): ?string
    {
        if (! Storage::disk($document->disk)->exists($document->file_path)) {
            return null;
        }

        return match ($kind) {
            'text' => Str::limit((string) Storage::disk($document->disk)->get($document->file_path), 1200),
            'word' => $this->extractOfficeXmlText($document->disk, $document->file_path, 'word/document.xml'),
            'spreadsheet' => $this->extractOfficeXmlText($document->disk, $document->file_path, 'xl/sharedStrings.xml'),
            'presentation' => $this->extractPresentationText($document->disk, $document->file_path),
            default => null,
        };
    }

    protected function extractOfficeXmlText(string $disk, string $path, string $entry): ?string
    {
        $zipPath = Storage::disk($disk)->path($path);
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $content = $zip->getFromName($entry) ?: null;
        $zip->close();

        if ($content === null) {
            return null;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? ''), 1200);
    }

    protected function extractPresentationText(string $disk, string $path): ?string
    {
        $zipPath = Storage::disk($disk)->path($path);
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $buffer = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (! str_starts_with((string) $name, 'ppt/slides/slide')) {
                continue;
            }

            $content = $zip->getFromIndex($i);

            if ($content !== false) {
                $buffer[] = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');
            }
        }

        $zip->close();

        $text = trim(implode(' ', array_filter($buffer)));

        return $text !== '' ? Str::limit($text, 1200) : null;
    }
}
