<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QRCode;
use App\Models\Meeting;
use App\Models\Registration;
use App\Models\CheckIn;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicCheckInController extends Controller
{
    public function showMeetingByToken($token)
    {
        $qrCode = QRCode::with('meeting')->where('token', $token)->first();
        $meeting = $qrCode?->meeting ?? Meeting::where('meeting_code', $token)->first() ?? Meeting::find($token);
        if (!$meeting) return response()->json(['success'=>false,'message'=>'Invalid Token'],404);
        return response()->json(['success'=>true,'data'=>['meeting'=>$meeting,'qr_code'=>$qrCode],'meeting'=>$meeting]);
    }

    public function register(Request $request, $token)
    {
        $request->validate([
            'name'=>'required|string',
            'lastname'=>'required|string',
            'organization'=>'required|string',
            'position'=>'required|string',
            'phone'=>'required|string',
            'signature'=>'required|string',
        ]);

        $qrCode = QRCode::where('token',$token)->first();
        $meeting = $qrCode?->meeting ?? Meeting::where('meeting_code',$token)->first() ?? Meeting::find($token);
        if(!$meeting) return response()->json(['success'=>false,'message'=>'Meeting not found'],404);

        if(!$qrCode){
            $qrCode = QRCode::create([
                'uuid'=>(string)Str::uuid(),
                'meeting_id'=>$meeting->id,
                'token'=>Str::random(64),
                'created_by_user_id'=>1,
                'status'=>'active'
            ]);
        }

        $fullPhone = $request->phone;
        $existingReg = Registration::where('meeting_id',$meeting->id)->where('phone',$fullPhone)->first();
        if($existingReg){
            $alreadyChecked = CheckIn::where('registration_id',$existingReg->id)->where('status','success')->first();
            if($alreadyChecked){
                return response()->json(['success'=>false,'message'=>'ເບີນີ້ Check-in ແລ້ວ'],409);
            }
        }

        $registration = Registration::create([
            'uuid'=> (string)Str::uuid(),
            'meeting_id'=>$meeting->id,
            'qr_code_id'=>$qrCode->id,
            'name'=>$request->name,
            'lastname'=>$request->lastname,
            'phone'=>$fullPhone,
            'organization'=>$request->organization,
            'position'=>$request->position,
            'registration_type'=>'walkin',
            'status'=>'checked_in',
            'substitute_for'=>null,
            'substitute_note'=>null,
        ]);

        $signaturePath=null;
        if($request->signature){
            $sig=$request->signature;
            $img = str_contains($sig,';base64,') ? explode(';base64,',$sig)[1] : $sig;
            $data = base64_decode($img);
            $file='signatures/'.Str::uuid().'.png';
            Storage::disk('public')->put($file,$data);
            $signaturePath=$file;
        }

        $checkIn = CheckIn::create([
            'uuid'=>(string)Str::uuid(),
            'meeting_id'=>$meeting->id,
            'qr_code_id'=>$qrCode->id,
            'registration_id'=>$registration->id,
            'checked_in_at'=>now(),
            'signature_path'=>$signaturePath,
            'status'=>'success',
            'checkin_type'=>'walkin',
            'substitute_note'=>null,
            'ip_address'=>$request->ip(),
            'user_agent'=>$request->userAgent(),
        ]);

        return response()->json(['success'=>true,'message'=>'Check-in สำเร็จ','data'=>['registration'=>$registration,'checkin'=>$checkIn]],201);
    }
}
