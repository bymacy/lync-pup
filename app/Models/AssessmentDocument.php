<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentDocument extends Model
{
    protected $primaryKey = 'assessment_document_id';

    protected $fillable = [
        'startup_id', 'stage', 'document_number', 'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'document_number' => 'integer',
        ];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}
