<?php
namespace App\Exports;

use App\Models\CheckIn;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CheckInsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $meetingId;
    public function __construct($meetingId) { $this->meetingId = $meetingId; }

    public function collection(): Collection
    {
        return CheckIn::where('meeting_id', $this->meetingId)->with(['registration','meeting'])->get();
    }

    public function headings(): array
    {
        return ['ID','ຊື່ຜູ້ເຂົ້າຮ່ວມ','ເວລາ Check-in','IP','ລາຍເຊັນ'];
    }

    public function map($checkIn): array
    {
        return [
            $checkIn->id,
            $checkIn->registration->name ?? '',
            $checkIn->checked_in_at,
            $checkIn->ip_address,
            $checkIn->signature_path ?? '-',
        ];
    }
}
