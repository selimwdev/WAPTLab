<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OauthToken extends Model {
    protected $guarded = [];
    protected $dates = ['expires_at'];
}
