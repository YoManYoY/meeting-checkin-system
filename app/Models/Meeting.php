<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid','meeting_code','title','description','type','location',
        'start_date','end_date','start_time','end_time','status','created_by_user_id'
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    protected $appends = ['auto_status'];

    // Relationship ที่ต้องเพิ่มเพื่อให้นับจำนวนผู้ถูกเชิญได้
    public function registrations()
    {
        return $this->hasMany(Registration::class, 'meeting_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getStartDateForApiAttribute()
    {
        return $this->start_date ? $this->start_date->format('Y-m-d') : null;
    }

    public function getEndDateForApiAttribute()
    {
        return $this->end_date ? $this->end_date->format('Y-m-d') : null;
    }

    public function getAutoStatusAttribute()
    {
        $now = Carbon::now('Asia/Vientiane');
        try {
            $startDate = $this->getRawOriginal('start_date') ?? $this->attributes['start_date'] ?? $this->start_date;
            $endDate = $this->getRawOriginal('end_date') ?? $this->attributes['end_date'] ?? $this->end_date;

            if($startDate instanceof \DateTimeInterface){
                $startDate = $startDate->format('Y-m-d');
            } else if(is_string($startDate)){
                $startDate = substr($startDate,0,10);
            }

            if($endDate instanceof \DateTimeInterface){
                $endDate = $endDate->format('Y-m-d');
            } else if(is_string($endDate)){
                $endDate = substr($endDate,0,10);
            }

            $start = Carbon::parse($startDate . ' ' . $this->start_time, 'Asia/Vientiane');
            $end = Carbon::parse($endDate . ' ' . $this->end_time, 'Asia/Vientiane');

            if ($this->status === 'completed') return 'completed';
            if ($now->lt($start)) return 'scheduled';
            if ($now->gte($start) && $now->lte($end)) return 'ongoing';
            return 'completed';
        } catch (\Exception $e) {
            return $this->status ?? 'scheduled';
        }
    }
}
