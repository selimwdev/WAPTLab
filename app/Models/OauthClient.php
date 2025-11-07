<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OauthClient extends Model {
    protected $guarded = [];
    protected $casts = ['redirect_uris' => 'array']; // نستخدم JSON array
}
