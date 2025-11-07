<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XffLog extends Model
{
    protected $table = 'xff_logs';

    protected $fillable = [
        'x_forwarded_for',
        'first_xff',
    ];

    public $timestamps = true;
}
