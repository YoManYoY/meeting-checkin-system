<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\QRCodeController;
use App\Http\Controllers\Api\PublicCheckInController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\OrganizerCheckInController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('users', UserController::class);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('meetings', MeetingController::class);
    Route::post('meetings/{id}/complete', [MeetingController::class, 'complete']);
    Route::put('meetings/{id}/complete', [MeetingController::class, 'complete']);
    Route::patch('meetings/{id}/complete', [MeetingController::class, 'complete']);

    Route::get('meetings/{meeting}/registrations', [RegistrationController::class, 'index']);
    Route::post('meetings/{meeting}/registrations', [RegistrationController::class, 'store']);
    Route::put('meetings/{meeting}/registrations/{regId}', [RegistrationController::class, 'update']);
    Route::delete('meetings/{meeting}/registrations/{regId}', [RegistrationController::class, 'destroy']);

    Route::get('checkins', [CheckInController::class, 'index']);
    Route::post('meetings/{meeting}/checkin/{registration}', [CheckInController::class, 'checkin']);
    Route::post('checkins/{id}/checkin', [CheckInController::class, 'checkin']);

    Route::get('dashboard/stats', [DashboardController::class, 'index']);

    // Organizer กรณี 2/3
    Route::get('meetings/{id}/checkins-data', [OrganizerCheckInController::class, 'getData']);
    Route::get('meetings/{id}/lookup', [OrganizerCheckInController::class, 'lookup']);
    Route::post('meetings/{id}/organizer-checkin', [OrganizerCheckInController::class, 'checkin']);
});

// Public checkin - no auth
Route::get('/public/meetings/{token}', [PublicCheckInController::class, 'showMeetingByToken']);
Route::post('/public/checkin/{token}', [PublicCheckInController::class, 'register']);
Route::post('/public/checkin/{token}/signature', [PublicCheckInController::class, 'saveSignature']);

// Dev routes without auth (for localhost:8000) - สำคัญ! ให้ /admin/checkins ดึงได้
Route::get('/meetings', [MeetingController::class, 'index']);
Route::get('/meetings/{id}', [MeetingController::class, 'show']);
Route::post('/meetings', [MeetingController::class, 'store']);
Route::put('/meetings/{id}', [MeetingController::class, 'update']);
Route::delete('/meetings/{id}', [MeetingController::class, 'destroy']);
Route::post('/meetings/{id}/complete', [MeetingController::class, 'complete']);

Route::get('meetings/{meeting}/registrations', [RegistrationController::class, 'index']);
Route::post('meetings/{meeting}/registrations', [RegistrationController::class, 'store']);
Route::delete('meetings/{meeting}/registrations/{regId}', [RegistrationController::class, 'destroy']);

Route::get('meetings/{meeting}/export', [RegistrationController::class, 'export']);
Route::get('meetings/{meeting}/export/csv', [RegistrationController::class, 'exportCsv']);

// ✅ เพิ่ม dev routes สำหรับ Organizer ให้หน้า /admin/checkins ทำงานได้
Route::get('meetings/{id}/checkins-data', [OrganizerCheckInController::class, 'getData']);
Route::get('meetings/{id}/lookup', [OrganizerCheckInController::class, 'lookup']);
Route::post('meetings/{id}/organizer-checkin', [OrganizerCheckInController::class, 'checkin']);

Route::get('dashboard/stats', [DashboardController::class, 'index']);
Route::get('checkins', [CheckInController::class, 'index']);
Route::get('users', [UserController::class, 'index']);
