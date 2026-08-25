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
    // GET /api/public/meetings/{token} - โชว์ meeting + registrations (สำหรับกรณี 2/3)
    public function showMeetingByToken($token)
    {
        // token อาจเป็น qr token หรือ meeting_code ก็ได้
        $qrCode = QRCode::with('meeting')->where('token', $token)->first();
        $meeting = null;

        if ($qrCode) {
            $meeting = $qrCode->meeting;
        } else {
            // ลองหาโดย meeting_code
            $meeting = Meeting::where('meeting_code', $token)->first();
            if (!$meeting) {
                // ลองหาโดย id
                $meeting = Meeting::find($token);
            }
        }

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Invalid QR Token / Meeting Code'], 404);
        }

        // ดึงรายชื่อผู้ถูกเชิญ (registrations) สำหรับกรณี 2/3
        $registrations = Registration::where('meeting_id', $meeting->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'meeting' => $meeting,
                'qr_code' => $qrCode,
                'registrations' => $registrations, // สำคัญสำหรับกรณี 2/3
            ],
            'meeting' => $meeting, // สำหรับ frontend เก่าที่เรียก res.data.data
            'registrations' => $registrations
        ]);
    }

    // POST /api/public/checkin/{token} - รับ 3 กรณี
    public function register(Request $request, $token)
    {
        $request->validate([
            'name' => 'required|string',
            'organization' => 'nullable|string',
            'position' => 'nullable|string',
            'phone' => 'nullable|string',
            'signature' => 'nullable|string',
            'checkin_type' => 'nullable|string', // walkin, preregistered, substitute
            'registration_id' => 'nullable|exists:registrations,id',
            'substitute_note' => 'nullable|string',
            'department' => 'nullable|string', // alias ของ organization
        ]);

        // หา meeting + qr_code
        $qrCode = QRCode::where('token', $token)->first();
        $meeting = null;
        if ($qrCode) {
            $meeting = $qrCode->meeting ?? Meeting::find($qrCode->meeting_id);
        } else {
            $meeting = Meeting::where('meeting_code', $token)->first() ?? Meeting::find($token);
            if ($meeting) {
                $qrCode = QRCode::where('meeting_id', $meeting->id)->first();
            }
        }

        if (!$meeting) {
            return response()->json(['success' => false, 'message' => 'Meeting not found'], 404);
        }

        // ถ้าไม่มี qr_code ให้สร้างชั่วคราว (สำหรับ dev)
        if (!$qrCode) {
            $qrCode = QRCode::create([
                'uuid' => (string) Str::uuid(),
                'meeting_id' => $meeting->id,
                'token' => Str::random(64),
                'created_by_user_id' => 1,
                'status' => 'active'
            ]);
        }

        $checkinType = $request->checkin_type ?? $request->type ?? 'walkin';
        $org = $request->organization ?? $request->department ?? $request->org ?? '';
        $registration = null;

        // กรณี 2: มีรายชื่อล่วงหน้า - ใช้ registration_id ที่มีอยู่
        if ($request->registration_id && in_array($checkinType, ['preregistered', 'substitute'])) {
            $registration = Registration::find($request->registration_id);

            // กัน duplicate check-in
            $existing = CheckIn::where('registration_id', $registration->id)
                ->where('meeting_id', $meeting->id)
                ->first();
            if ($existing) {
                return response()->json(['success' => false, 'message' => 'ທ່ານໄດ້ Check-in ໄປແລ້ວ'], 400);
            }

            // กรณี 3: มาแทน - อัพเดทโน้ตหรือสร้าง registration ใหม่ที่โยงกับคนเดิม
            if ($checkinType === 'substitute') {
                // สร้าง registration ใหม่สำหรับคนมาแทน แต่เก็บ reference
                $registration = Registration::create([
                    'uuid' => (string) Str::uuid(),
                    'meeting_id' => $meeting->id,
                    'qr_code_id' => $qrCode->id,
                    'name' => $request->name,
                    'lastname' => $request->lastname ?? '',
                    'phone' => $request->phone ?? '',
                    'email' => $request->email ?? null,
                    'organization' => $org,
                    'position' => $request->position ?? '',
                    'registration_type' => 'substitute',
                    'status' => 'registered',
                    'substitute_for' => $request->registration_id,
                    'substitute_note' => $request->substitute_note ?? '',
                ]);
            } else {
                // กรณี 2: อัพเดทข้อมูลล่าสุด (ถ้ามี)
                $registration->update([
                    'phone' => $request->phone ?? $registration->phone,
                    'organization' => $org ?: $registration->organization,
                    'position' => $request->position ?? $registration->position,
                ]);
            }
        } else {
            // กรณี 1: Walk-in - สร้าง registration ใหม่
            // กันเบอร์ซ้ำ
            if ($request->phone) {
                $dup = Registration::where('meeting_id', $meeting->id)
                    ->where('phone', $request->phone)
                    ->first();
                if ($dup) {
                    // ถ้าเคยลงทะเบียนแล้วแต่ยังไม่ check-in ให้ใช้ตัวเดิม
                    $existingCheck = CheckIn::where('registration_id', $dup->id)->first();
                    if ($existingCheck) {
                        return response()->json(['success' => false, 'message' => 'ເບີນີ້ Check-in ແລ້ວ'], 409);
                    }
                    $registration = $dup;
                }
            }

            if (!$registration) {
                $registration = Registration::create([
                    'uuid' => (string) Str::uuid(),
                    'meeting_id' => $meeting->id,
                    'qr_code_id' => $qrCode->id,
                    'name' => $request->name,
                    'lastname' => $request->lastname ?? '',
                    'phone' => $request->phone ?? '',
                    'email' => $request->email ?? null,
                    'organization' => $org,
                    'position' => $request->position ?? '',
                    'registration_type' => 'walkin',
                    'status' => 'registered',
                ]);
            }
        }

        // บันทึกลายเซ็น (ถ้ามี)
        $signaturePath = null;
        if ($request->signature) {
            $sig = $request->signature;
            if (str_contains($sig, ';base64,')) {
                $parts = explode(';base64,', $sig);
                $typeAux = explode('image/', $parts[0]);
                $imageType = $typeAux[1] ?? 'png';
                $imageData = base64_decode($parts[1]);
            } else {
                $imageType = 'png';
                $imageData = base64_decode($sig);
            }
            $fileName = 'signatures/' . Str::uuid() . '.' . $imageType;
            Storage::disk('public')->put($fileName, $imageData);
            $signaturePath = $fileName;
        }

        // สร้าง check_ins - นี่คือสิ่งที่ทำให้หน้า /checkins มีข้อมูล
        $checkIn = CheckIn::create([
            'uuid' => (string) Str::uuid(),
            'meeting_id' => $meeting->id,
            'qr_code_id' => $qrCode->id,
            'registration_id' => $registration->id,
            'checked_in_at' => now(),
            'signature_path' => $signaturePath,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'checkin_type' => $checkinType,
        ]);

        // อัพเดท registration status เป็น checked_in
        $registration->update(['status' => 'checked_in']);

        return response()->json([
            'success' => true,
            'message' => 'Check-in ສຳເລັດແລ້ວ',
            'data' => [
                'registration' => $registration,
                'checkin' => $checkIn->load('registration')
            ]
        ], 201);
    }

    // สำหรับ route เก่า saveSignature (ยังคงไว้เพื่อ backward compatible)
    public function saveSignature(Request $request, $token)
    {
        return $this->register($request, $token);
    }
}
