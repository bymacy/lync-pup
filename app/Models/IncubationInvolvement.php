<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncubationInvolvement extends Model
{
    use HasFactory;

    protected $fillable = ['info_sheet_id', 'organization_name_address', 'date_from', 'date_to', 'number_of_hours', 'incubation_program_focus'];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date'];
    }

    public function informationSheet()
    {
        return $this->belongsTo(InformationSheet::class, 'info_sheet_id');
    }
}