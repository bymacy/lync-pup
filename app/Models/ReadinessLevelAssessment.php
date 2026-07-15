<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessLevelAssessment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assessment_id';

    protected $fillable = [
        'startup_id', 'evaluated_by', 'trl_score', 'mrl_score',
        'tmrl_score', 'srl_score', 'overall_score', 'remarks', 'assessment_date',
    ];

    protected function casts(): array
    {
        return ['assessment_date' => 'date'];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}