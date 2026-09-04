<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Domains\Documents\Models\DocumentFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDiagnosticCriterion extends Model
{
    protected $table = 'enterprise_diagnostic_criteria';

    protected $fillable = [
        'enterprise_diagnostic_id',
        'criterion_id',
        'dimension_id',
        'criterion_code',
        'criterion_name',
        'dimension_code',
        'dimension_name',
        'criterion_weighting',
        'dimension_weighting',
        'evidence_required',
        'required',
        'maturity_status',
        'maturity_score',
        'assessor_observation',
        'evidence_document_file_id',
        'evidence_label',
        'verified_at',
        'verified_by',
        'expires_at',
    ];

    protected $casts = [
        'criterion_weighting' => 'decimal:2',
        'dimension_weighting' => 'decimal:2',
        'evidence_required' => 'boolean',
        'required' => 'boolean',
        'maturity_score' => 'integer',
        'verified_at' => 'date',
        'expires_at' => 'date',
    ];

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDiagnostic::class, 'enterprise_diagnostic_id');
    }

    public function evidenceFile(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'evidence_document_file_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
