<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = ['uuid','meeting_id','token','file_path','expires_at','created_by_user_id','status'];
    protected $casts = ['expires_at'=>'datetime'];

    public function getRouteKeyName(){ return 'id'; } // ບັງຄັບໃຊ້ id ເລກ

    public function meeting(){ return $this->belongsTo(Meeting::class); }
    public function creator(){ return $this->belongsTo(User::class,'created_by_user_id'); }
}
