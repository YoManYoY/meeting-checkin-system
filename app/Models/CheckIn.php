<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CheckIn extends Model
{
    protected $table = 'check_ins';

    protected $fillable = [
        'uuid',
        'meeting_id',
        'qr_code_id',
        'registration_id',
        'checked_in_at',
        'signature_path',
        'ip_address',
        'user_agent',
        'status',
        'checkin_type',
        'substitute_note'
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
        });
    }

    public function meeting(){ return $this->belongsTo(Meeting::class); }
    public function registration(){ return $this->belongsTo(Registration::class); }
    public function qrCode(){ return $this->belongsTo(QrCode::class); }
}
