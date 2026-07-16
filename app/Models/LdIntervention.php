<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LdIntervention extends Model
{
    use HasFactory;

    protected $fillable = ['info_sheet_id', 'title', 'date_from', 'date_to', 'number_of_hours', 'conducted_sponsored_by'];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date'];
    }

    public function informationSheet()
    {
        return $this->belongsTo(InformationSheet::class, 'info_sheet_id');
    }
}