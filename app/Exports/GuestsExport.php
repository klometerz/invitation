<?php

namespace App\Exports;

use App\Models\Guest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GuestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function collection()
    {
        return Guest::orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Kota',
            'Kategori',
            'Kode Undangan',
            'Quota',
            'RSVP',
            'Konfirmasi Kehadiran',
            'Ucapan / Doa',
            'Status Check-in',
            'Waktu Check-in',
            'Jumlah Tamu Datang',
            'Petugas Check-in',
        ];
    }

    public function map($guest): array
    {
        return [
            $guest->name,
            $guest->city ?? '-',
            $guest->category ?? '-',
            $guest->guest_code,
            $guest->quota,
            $guest->is_rsvp ? 'Sudah RSVP' : 'Belum RSVP',
            $guest->attendance ?? '-',
            $guest->message ?? '-',
            $guest->checkin_status === 'arrived' ? 'Hadir' : 'Belum Hadir',
            $guest->checked_in_at?->format('d/m/Y H:i') ?? '-',
            $guest->actual_guest_count ?? '-',
            $guest->checked_in_by ?? '-',
        ];
    }
}