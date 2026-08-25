<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Exports\RegistrationsExport;
use App\Exports\CheckInsExport; // 👈 เพิ่มบรรทัดนี้
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function exportRegistrations(Request $request, Meeting $meeting)
    {
        $user = $request->user();

        // ບັງຄັບກວດສອບ Ownership (Admin ເຫັນທັງໝົດ, Organizer ເຫັນສະເພາະ Meeting ຂອງຕົນເອງ) - รักษาโค้ดเก่าเจ้าไว้
        if (!in_array($user->role, ['admin','super_admin']) && $meeting->created_by_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. ທ່ານไม่มีສິດ Export ຂໍ້ມູນຂອງກອງປະຊຸມນີ້.'
            ], 403);
        }

        // 👈 เพิ่มรองรับ ?format=csv|excel โดยยังรักษาโครงสร้างเดิม
        $format = $request->query('format', 'xlsx');
        $ext = $format === 'csv' ? 'csv' : 'xlsx';
        $fileName = 'registrations-meeting-' . $meeting->meeting_code . '-' . date('Y-m-d') . '.' . $ext;

        return Excel::download(new RegistrationsExport($meeting->id), $fileName);
    }

    // 👈 เพิ่ม Method ใหม่ แต่ใช้โครงสร้างเตือนแบบเดียวกันเป๊ะ
    public function exportCheckIns(Request $request, Meeting $meeting)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin','super_admin']) && $meeting->created_by_user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. ທ່ານไม่มีສິດ Export ຂໍ້ມູນ Check-in ຂອງກອງປະຊຸມນີ້.'
            ], 403);
        }

        $format = $request->query('format', 'xlsx');
        $ext = $format === 'csv' ? 'csv' : 'xlsx';
        $fileName = 'checkins-meeting-' . $meeting->meeting_code . '-' . date('Y-m-d') . '.' . $ext;

        return Excel::download(new CheckInsExport($meeting->id), $fileName);
    }
}
