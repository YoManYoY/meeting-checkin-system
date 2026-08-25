<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\QrCode; // <-- ຕ້ອງເປັນ QrCode r ນ້ອຍ
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class QRCodeController extends Controller
{
    use AuthorizesRequests;

    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    // ດຶງລາຍຊື່ QR Code ຂອງ Meeting
    public function index(Request $request, Meeting $meeting)
    {
        $this->authorize('view', $meeting);
        $qrCodes = $meeting->qrCodes()->latest()->get();
        return response()->json(['success' => true, 'data' => $qrCodes]);
    }

    // ສ້າງ QR Code ໃໝ່ໃຫ້ Meeting - ແກ້ບັນຫາ $service
    public function store(Request $request, Meeting $meeting)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $meeting->created_by_user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // ໃຊ້ $this->qrCodeService ບໍ່ແມ່ນ $service
        $qrCode = $this->qrCodeService->createForMeeting($meeting, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'ສ້າງ QR Code ສຳເລັດແລ້ວ',
            'data' => $qrCode
        ], 201);
    }

    // ດາວໂຫຼດ QR Code - Ownership Validation
    public function download(QrCode $qrCode)
    {
        $this->authorize('download', $qrCode);

        if (!\Storage::disk('public')->exists($qrCode->file_path)) {
            abort(404, 'QR file missing: '.$qrCode->file_path);
        }

        $path = \Storage::disk('public')->path($qrCode->file_path);
        return response()->download($path, "qrcode-meeting-{$qrCode->meeting_id}.png");
    }
}
