<?php
namespace App\Exports;

use App\Models\Registration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RegistrationsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $meetingId;
    public function __construct($meetingId) { $this->meetingId = $meetingId; }

    public function collection(): Collection
    {
        return Registration::where('meeting_id', $this->meetingId)
            ->with(['checkIn', 'meeting'])
            ->get();
    }

    public function headings(): array
    {
        return ['ID','ຊື່','ນາມສະກຸນ','ເບີໂທລະສັບ','ອີເມວ','ອົງກອນ','ຕຳແໜ່ງ','ປະເພດການລົງທະບຽນ','ສະຖານະ Check-in','ເວລາ Check-in'];
    }

    public function map($registration): array
    {
        return [
            $registration->id,
            $registration->name,
            $registration->lastname,
            $registration->phone,
            $registration->email ?? '-',
            $registration->organization ?? '-',
            $registration->position ?? '-',
            $registration->registration_type,
            $registration->checkIn ? 'Check-in ແລ້ວ' : 'ຍັງບໍ່ທັນ Check-in',
            $registration->checkIn ? $registration->checkIn->checked_in_at : '-',
        ];
    }
}
