<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmMain extends Model
{
    protected $table = 'crm_main';
    protected $fillable = ['source_db', 'source_row_id', 'data'];
    protected $casts = [
        'data' => 'array', // عشان نقدر نتعامل مع JSON كـ array
    ];
}
