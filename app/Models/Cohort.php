<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cohort extends Model
{
    use HasFactory;

    protected $primaryKey = 'cohort_id';

    protected $fillable = ['number', 'label', 'status'];

    public function startups()
    {
        return $this->hasMany(Startup::class, 'cohort_id', 'cohort_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: "Cohort {$this->number}";
    }
}
