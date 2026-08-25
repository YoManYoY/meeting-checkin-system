<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    public function createForMeeting(Meeting $meeting, int $userId): QrCode
    {
        $token = Str::random(40);
        $uuid = (string) Str::uuid();
        $fileName = "qrcodes/meeting-{$meeting->id}-{$token}.png";
        $checkInUrl = url("/checkin/{$token}");

        $size = 400;
        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $size, $size, $white);
        imagerectangle($image, 10, 10, $size-10, $size-10, $black);
        imagestring($image, 3, 20, $size/2 - 20, "Meeting: {$meeting->id}", $black);
        imagestring($image, 3, 20, $size/2, substr($token,0,20), $black);
        imagestring($image, 3, 20, $size/2+20, $checkInUrl, $black);

        ob_start();
        imagepng($image);
        $pngData = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($fileName, $pngData);

        return QrCode::create([
            'uuid' => $uuid,
            'meeting_id' => $meeting->id,
            'token' => $token,
            'file_path' => $fileName,
            'expires_at' => $meeting->end_time,
            'created_by_user_id' => $userId,
        ]);
    }
}
