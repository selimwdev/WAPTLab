<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Value extends Model {
    protected $fillable = ['entity_id','attribute_id','value'];
    public function entity(){ return $this->belongsTo(Entity::class); }
    public function attribute(){ return $this->belongsTo(Attribute::class); }
}
