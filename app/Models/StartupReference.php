<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StartupReference extends Model
{
    use HasFactory;

    protected $fillable = ['info_sheet_id', 'name', 'contact', 'email', 'address'];

    public function informationSheet()
    {
        return $this->belongsTo(InformationSheet::class, 'info_sheet_id');
    }
}