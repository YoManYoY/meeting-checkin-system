<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Meeting;
use Carbon\Carbon;

class MeetingController extends Controller
{
    public function index(){
        // ✅ แก้: นับเฉพาะ invited เท่านั้น เพื่อให้ปุ่มนอก = ข้างใน
        $meetings = Meeting::with(['creator'])
            ->withCount(['registrations as registrations_count' => function($q){
                $q->where('registration_type','invited');
            }])
            ->latest('id')->get();
        $meetings->each(function($m){
            if($m->start_date){
                $m->start_date = $m->start_date instanceof \DateTimeInterface ? $m->start_date->format('Y-m-d') : substr($m->start_date,0,10);
            }
            if($m->end_date){
                $m->end_date = $m->end_date instanceof \DateTimeInterface ? $m->end_date->format('Y-m-d') : substr($m->end_date,0,10);
            }
        });
        return response()->json(['success'=>true,'data'=>$meetings]);
    }

    private function checkOverlap($request, $ignoreId = null)
    {
        $query = Meeting::where('location', $request->location)
            ->where('status', '!=', 'completed')
            ->whereDate('start_date', '<=', $request->end_date)
            ->whereDate('end_date', '>=', $request->start_date);
        if($ignoreId) $query->where('id','!=',$ignoreId);
        $existing = $query->get();
        foreach($existing as $ex){
            try{
                $exStartDate = $ex->start_date instanceof \DateTimeInterface ? $ex->start_date->format('Y-m-d') : substr($ex->getRawOriginal('start_date'),0,10);
                $exEndDate = $ex->end_date instanceof \DateTimeInterface ? $ex->end_date->format('Y-m-d') : substr($ex->getRawOriginal('end_date'),0,10);
                $exStart = Carbon::parse($exStartDate . ' ' . $ex->start_time, 'Asia/Vientiane');
                $exEnd = Carbon::parse($exEndDate . ' ' . $ex->end_time, 'Asia/Vientiane');
                $newStart = Carbon::parse($request->start_date . ' ' . $request->start_time, 'Asia/Vientiane');
                $newEnd = Carbon::parse($request->end_date . ' ' . $request->end_time, 'Asia/Vientiane');
                if($newStart->lt($exEnd) && $newEnd->gt($exStart)){ return $ex; }
            }catch(\Exception $e){ continue; }
        }
        return null;
    }

    public function store(Request $request)
    {
        $today = Carbon::today('Asia/Vientiane')->toDateString();
        $messages = [
            'title.required'=>'ກະລຸນາໃສ່ຫົວຂໍ້ກອງປະຊຸມ',
            'location.required'=>'ກະລຸນາເລືອກຫ້ອງປະຊຸມ',
            'start_date.required'=>'ກະລຸນາເລືອກວັນເລີ່ມ',
            'end_date.required'=>'ກະລຸນາເລືອກວັນສິ້ນສຸດ',
            'start_date.after_or_equal'=>'ວັນເລີ່ມຕ້ອງເປັນມື້ນີ້ ຫຼື ຫຼັງມື້ນີ້',
            'end_date.after_or_equal'=>'ວັນສິ້ນສຸດຕ້ອງຫຼັງ ຫຼື ເທົ່າກັບວັນເລີ່ມ',
            'start_time.required'=>'ກະລຸນາໃສ່ເວລາເລີ່ມ',
            'end_time.required'=>'ກະລຸນາໃສ່ເວລາສິ້ນສຸດ',
            'end_time.after'=>'ເວລາສິ້ນສຸດຕ້ອງຫຼັງເວລາເລີ່ມ',
        ];
        $request->validate([
            'title'=>'required|string|max:255',
            'type'=>'required|in:meeting,seminar,training,other',
            'description'=>'nullable|string',
            'location'=>'required|in:ຫ້ອງສຳມະນາ1,ຫ້ອງສຳມະນາ4,ຫ້ອງ 403,ຫ້ອງປະຊຸມໃຫຍ່ ຊັ້ນ 5,ຫ້ອງ 101,ຫ້ອງ 103',
            'start_date'=>'required|date|after_or_equal:'.$today,
            'end_date'=>'required|date|after_or_equal:start_date',
            'start_time'=>'required',
            'end_time'=>'required|after:start_time',
        ], $messages);

        if($request->start_date == $today){
            $now = Carbon::now('Asia/Vientiane')->format('H:i');
            if($request->start_time < $now){
                return response()->json(['success'=>false,'message'=>"ເວລາເລີ່ມຫ້າມຍ້ອນຫຼັງເວລາປັດຈຸບັນ ($now)"],422);
            }
        }
        $overlap = $this->checkOverlap($request);
        if($overlap){
            $sDate = $overlap->start_date instanceof \DateTimeInterface ? $overlap->start_date->format('d/m/Y') : Carbon::parse($overlap->getRawOriginal('start_date'))->format('d/m/Y');
            return response()->json(['success'=>false,'message'=>"ຫ້ອງ {$request->location} ຖືກໃຊ້ແລ້ວ ⏰ {$sDate} {$overlap->start_time} (ຫົວຂໍ້: {$overlap->title})"],422);
        }

        $meeting = Meeting::create([
            'uuid' => (string) Str::uuid(),
            'meeting_code' => 'MEET-' . time() . rand(100,999),
            'title'=>$request->title,
            'description'=>$request->description,
            'type'=>$request->type,
            'location'=>$request->location,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            'start_time'=>Carbon::parse($request->start_time)->format('H:i:s'),
            'end_time'=>Carbon::parse($request->end_time)->format('H:i:s'),
            'status'=>'scheduled',
            'created_by_user_id'=> auth()->id() ?? 1,
        ]);
        return response()->json(['success'=>true,'data'=>$meeting],201);
    }

    public function update(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);
        $request->validate([
            'title'=>'required|string|max:255',
            'type'=>'required|in:meeting,seminar,training,other',
            'location'=>'required|in:ຫ້ອງສຳມະນາ1,ຫ້ອງສຳມະນາ4,ຫ້ອງ 403,ຫ້ອງປະຊຸມໃຫຍ່ ຊັ້ນ 5,ຫ້ອງ 101,ຫ້ອງ 103',
            'start_date'=>'required|date',
            'end_date'=>'required|date|after_or_equal:start_date',
            'start_time'=>'required',
            'end_time'=>'required|after:start_time',
        ], [
            'end_date.after_or_equal'=>'ວັນສິ້ນສຸດຕ້ອງຫຼັງຫຼືເທົ່າກັບວັນເລີ່ມ',
            'end_time.after'=>'ເວລາສິ້ນສຸດຕ້ອງຫຼັງເວລາເລີ່ມ',
        ]);

        if($meeting->status !== 'scheduled' && $meeting->status !== 'ongoing'){
            if($meeting->status === 'completed'){
                return response()->json(['success'=>false,'message'=>'ກອງປະຊຸມສຳເລັດແລ້ວ ບໍ່ສາມາດແກ້ໄຂໄດ້'],422);
            }
        }

        $overlap = $this->checkOverlap($request, $id);
        if($overlap){
            return response()->json(['success'=>false,'message'=>"ຫ້ອງ {$request->location} ຖືກໃຊ້ແລ້ວເວລານັ້ນ"],422);
        }

        $meeting->update([
            'title'=>$request->title,
            'description'=>$request->description,
            'type'=>$request->type,
            'location'=>$request->location,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            'start_time'=>Carbon::parse($request->start_time)->format('H:i:s'),
            'end_time'=>Carbon::parse($request->end_time)->format('H:i:s'),
        ]);
        return response()->json(['success'=>true,'data'=>$meeting]);
    }

    public function complete($id){
        $m = Meeting::findOrFail($id);
        $now = Carbon::now('Asia/Vientiane');
        $m->update([
            'status'=>'completed',
            'end_date' => Carbon::today('Asia/Vientiane')->toDateString(),
            'end_time' => $now->format('H:i:s')
        ]);
        return response()->json(['success'=>true,'data'=>$m, 'message'=>'ສຳເລັດກອງປະຊຸມແລ້ວ ຫ້ອງວ່າງພ້ອມໃຫ້ຜູ້ອື່ນໃຊ້']);
    }

    public function show($id){
        // ✅ แก้ show ด้วย
        $m = Meeting::withCount(['registrations as registrations_count' => function($q){
                $q->where('registration_type','invited');
            }])->where('id',$id)->orWhere('meeting_code',$id)->firstOrFail();
        return response()->json(['success'=>true,'data'=>$m]);
    }
    public function destroy($id){ Meeting::findOrFail($id)->delete(); return response()->json(['success'=>true]); }
}
