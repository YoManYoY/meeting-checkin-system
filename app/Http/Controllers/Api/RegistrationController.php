<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Registration;
use App\Models\QrCode;

class RegistrationController extends Controller
{
    public function index($meetingId){
        $regs = Registration::where('meeting_id',$meetingId)
            ->where('registration_type','invited')
            ->latest()
            ->get();
        return response()->json(['data' => $regs]);
    }

    public function store(Request $request, $meetingId){
        $request->validate([
            'name' => 'required',
            'organization' => 'required',
            'position' => 'required',
            'phone' => 'required',
        ]);

        $qr = QrCode::where('meeting_id',$meetingId)->first();
        if(!$qr){
            $qr = QrCode::create([
                'uuid' => (string) Str::uuid(),
                'meeting_id' => $meetingId,
                'token' => Str::random(64),
                'created_by_user_id' => auth()->id() ?? 1,
                'status' => 'active'
            ]);
        }

        $reg = Registration::create([
            'uuid' => (string) Str::uuid(),
            'meeting_id' => $meetingId,
            'qr_code_id' => $qr->id,
            'name' => $request->name,
            'lastname' => $request->lastname ?? '',
            'organization' => $request->organization,
            'position' => $request->position,
            'phone' => $request->phone,
            'registration_type' => 'invited',
            'status' => 'registered'
        ]);

        return response()->json(['data'=>$reg],201);
    }

    public function update(Request $request, $meetingId, $id){
        $reg = Registration::where('meeting_id',$meetingId)->findOrFail($id);
        $reg->update([
            'name' => $request->name ?? $reg->name,
            'lastname' => $request->lastname ?? $reg->lastname ?? '',
            'organization' => $request->organization ?? $reg->organization,
            'position' => $request->position ?? $reg->position,
            'phone' => $request->phone ?? $reg->phone,
        ]);
        return response()->json(['data'=>$reg]);
    }

    public function destroy($meetingId, $id){
        Registration::where('meeting_id',$meetingId)->findOrFail($id)->delete();
        return response()->json(['success'=>true]);
    }
}
