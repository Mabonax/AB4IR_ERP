<?php

namespace App\Domains\Organization\Notifications;

use App\Domains\Organization\Models\OrganizationDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrganizationDocumentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected OrganizationDocument $document,
        protected string $context,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'organization_document_published',
            'title' => 'New organization document available',
            'message' => $this->context,
            'organization_document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'document_type' => $this->document->document_type,
            'audience_scope' => $this->document->audience_scope,
            'url' => '/organization/documents',
        ];
    }
}
