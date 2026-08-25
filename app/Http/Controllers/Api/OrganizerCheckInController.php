<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;
use App\Models\CheckIn;
use App\Models\QRCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OrganizerCheckInController extends Controller
{
    public function getData($id)
    {
        $checked = CheckIn::with('registration')
            ->where('meeting_id',$id)
            ->where('status','success')
            ->whereHas('registration', function($q){
                $q->whereIn('registration_type',['invited','substituted']);
            })
            ->orderByDesc('checked_in_at')->get();
        $checkedRegIds = $checked->pluck('registration_id')->toArray();
        $substitutedRegs = Registration::where('meeting_id',$id)->where('status','substituted')->pluck('id')->toArray();
        $allExcluded = array_merge($checkedRegIds, $substitutedRegs);
        $pending = Registration::where('meeting_id',$id)
            ->where('registration_type','invited')
            ->whereNotIn('id',$allExcluded)
            ->where('status','!=','cancelled')
            ->where('status','!=','substituted')
            ->where('status','!=','checked_in')
            ->orderBy('name')->get();
        return response()->json(['pending'=>$pending,'checked'=>$checked]);
    }

    public function lookup(Request $request, $id)
    {
        $token = trim($request->get('token',''));
        if(!$token) return response()->json(['message'=>'ໃສ່ ຊື່ / ເບີໂທ / ລະຫັດ'],400);
        $reg = Registration::where('meeting_id',$id)
            ->where('registration_type','invited')
            ->where(function($q) use ($token){
                $q->where('name','like',"%$token%")
                  ->orWhere('lastname','like',"%$token%")
                  ->orWhere('phone','like',"%$token%")
                  ->orWhere('organization','like',"%$token%")
                  ->orWhere('id',$token);
            })->where('status','!=','substituted')->first();
        if(!$reg){
            $qr = QRCode::where('token',$token)->first();
            if($qr) $reg = Registration::where('qr_code_id',$qr->id)->where('registration_type','invited')->where('status','!=','substituted')->first();
        }
        if(!$reg) return response()->json(['message'=>'ບໍ່ພົບລາຍຊື່'],404);
        return response()->json(['data'=>$reg]);
    }

    public function checkin(Request $request, $id)
    {
        $request->validate([
            'registration_id'=>'required|exists:registrations,id',
            'is_substitute'=>'nullable|boolean',
            'substitute_name'=>'required|string',
            'substitute_lastname'=>'nullable|string',
            'substitute_phone'=>'required|string',
            'substitute_position'=>'nullable|string',
            'substitute_organization'=>'nullable|string',
            'substitute_note'=>'nullable|string',
            'signature'=>'required|string',
        ]);

        $original = Registration::findOrFail($request->registration_id);
        $exists = CheckIn::where('registration_id',$original->id)->where('meeting_id',$id)->where('status','success')->first();
        if($exists && !$request->is_substitute){
            return response()->json(['message'=>'ທ່ານໄດ້ Check-in ໄປແລ້ວ'],400);
        }
        if($original->status === 'substituted'){
            return response()->json(['message'=>'ລາຍຊື່ນີ້ຖືກມາແທນແລ້ວ'],400);
        }

        $qr = QRCode::where('meeting_id',$id)->first();
        if(!$qr) $qr = QRCode::create(['uuid'=>(string)Str::uuid(),'meeting_id'=>$id,'token'=>Str::random(64),'created_by_user_id'=>1,'status'=>'active']);

        $targetReg = $original;
        $checkinType = 'invited';
        $note = null;

        if($request->is_substitute){
            $note = $request->substitute_note ?: "ມາແທນ {$original->name} {$original->lastname}";
            $targetReg = Registration::create([
                'uuid'=>(string)Str::uuid(),
                'meeting_id'=>$id,
                'qr_code_id'=>$qr->id,
                'name'=>$request->substitute_name,
                'lastname'=>$request->substitute_lastname ?? '',
                'phone'=>$request->substitute_phone,
                'organization'=>$request->substitute_organization ?? $original->organization,
                'position'=>$request->substitute_position ?? $original->position,
                'registration_type'=>'substituted',
                'substitute_for'=>$original->id,
                'substitute_note'=>$note,
                'status'=>'checked_in',
            ]);
            $checkinType = 'substituted';
            $original->update(['status'=>'substituted']);
        } else {
            $checkinType = 'invited';
            $original->update(['status'=>'checked_in','registration_type'=>'invited']);
        }

        $sigPath=null;
        if($request->signature){
            $sig=$request->signature;
            $img = str_contains($sig,';base64,') ? explode(';base64,',$sig)[1] : $sig;
            $data = base64_decode($img);
            $file='signatures/'.Str::uuid().'.png';
            Storage::disk('public')->put($file,$data);
            $sigPath=$file;
        }

        $checkin = CheckIn::create([
            'uuid'=>(string)Str::uuid(),
            'meeting_id'=>$id,
            'qr_code_id'=>$qr->id,
            'registration_id'=>$targetReg->id,
            'checked_in_at'=>now(),
            'signature_path'=>$sigPath,
            'status'=>'success',
            'checkin_type'=>$checkinType,
            'substitute_note'=>$note,
            'ip_address'=>$request->ip(),
            'user_agent'=>$request->userAgent(),
        ]);

        return response()->json(['success'=>true,'message'=>'Check-in สำเร็จ','data'=>$checkin->load('registration')]);
    }
}
