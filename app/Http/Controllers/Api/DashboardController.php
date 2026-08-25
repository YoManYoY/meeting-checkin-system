<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        // 1. 4 Cards - นับจาก Type ใน meetings
        $totalMeetings = Meeting::where('type', 'meeting')->count();
        $totalSeminars = Meeting::where('type', 'seminar')->count();
        $totalTrainings = Meeting::where('type', 'training')->count();
        $totalCheckins = CheckIn::count(); // เช็คอินทั้งหมด

        // 2. กองประชุมล่าสุด 5 รายการที่กำลังดำเนินการ (ongoing)
        $now = Carbon::now();
        $latestMeetings = Meeting::withCount(['checkIns'])
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(10) // ดึงมา 10 แล้วกรอง ongoing
            ->get()
            ->map(function($m) use ($now){
                try{
                    $start = Carbon::parse(($m->start_date?->format('Y-m-d') ?? substr($m->start_date,0,10)) . ' ' . substr($m->start_time,0,5));
                    $end = Carbon::parse(($m->end_date?->format('Y-m-d') ?? substr($m->end_date,0,10)) . ' ' . substr($m->end_time,0,5));
                    if($m->status === 'completed') $auto = 'completed';
                    elseif($now->lt($start)) $auto = 'scheduled';
                    elseif($now->between($start, $end)) $auto = 'ongoing';
                    else $auto = 'completed';
                }catch(\Exception $e){ $auto = $m->status ?? 'scheduled'; }
                $m->auto_status = $auto;
                return $m;
            })
            ->filter(fn($m)=> $m->auto_status === 'ongoing')
            ->take(5)
            ->values();

        // ถ้าไม่มี ongoing ให้เอา 5 รายการล่าสุดแทน
        if($latestMeetings->isEmpty()){
            $latestMeetings = Meeting::withCount(['checkIns'])
                ->orderBy('created_at','desc')
                ->limit(5)
                ->get()
                ->map(function($m){
                    $m->auto_status = 'scheduled';
                    return $m;
                });
        }

        return response()->json([
            'cards' => [
                'meetings' => $totalMeetings,
                'seminars' => $totalSeminars,
                'trainings' => $totalTrainings,
                'checkins' => $totalCheckins,
            ],
            'latest_meetings' => $latestMeetings,
            'success' => true
        ]);
    }
}
