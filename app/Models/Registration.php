<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $fillable = [
        'uuid','meeting_id','qr_code_id','name','lastname','phone','email',
        'organization','position','registration_type','status',
        'checked_in_at','substitute_for','substitute_note'
    ];

    protected $casts = [
        'checked_in_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function($model){
            if(empty($model->uuid)){
                $model->uuid = (string) Str::uuid();
            }
            if(empty($model->lastname)){
                $model->lastname = '';
            }
            if(empty($model->qr_code_id)){
                $qr = QrCode::where('meeting_id', $model->meeting_id)->first();
                if($qr){
                    $model->qr_code_id = $qr->id;
                } else {
                    $newQr = QrCode::create([
                        'uuid' => (string) Str::uuid(),
                        'meeting_id' => $model->meeting_id,
                        'token' => Str::random(64),
                        'created_by_user_id' => auth()->id() ?? 1,
                        'status' => 'active'
                    ]);
                    $model->qr_code_id = $newQr->id;
                }
            }
        });
    }

    public function meeting(){ return $this->belongsTo(Meeting::class); }
    public function checkIn(){ return $this->hasOne(CheckIn::class); }
}
