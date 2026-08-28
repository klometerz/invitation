<?php

// app/Imports/GuestsImport.php
namespace App\Imports;

use App\Models\Guest;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Kalau excel ada header-nya

class GuestsImport implements ToModel {

    public function __construct($invitationId)
{
    $this->invitationId = $invitationId;
}
    public function model(array $row) {
        return new Guest([
            'name' => $row[1], // col2: Nama
            'city' => $row[2], // col3: Kota
            'category' => $row[3], // col4: Kategori
            'quota' => $row[4], // col5: Quota
            'invitation_id' => $this->invitationId,
        ]);
    }
}
