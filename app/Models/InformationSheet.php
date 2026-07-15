<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformationSheet extends Model
{
    use HasFactory;

    protected $primaryKey = 'info_sheet_id';

    protected $fillable = [
        'startup_id', 'business_description', 'target_market',
        'problem_statement', 'solution_offered', 'submission_date',
        'approval_status', 'evaluator_remarks',
    ];

    protected function casts(): array
    {
        return ['submission_date' => 'date'];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}