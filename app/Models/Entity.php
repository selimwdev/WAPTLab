<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Entity extends Model {
    protected $fillable = ['namespace','entity_type'];
    public function values(){ return $this->hasMany(Value::class); }
}
