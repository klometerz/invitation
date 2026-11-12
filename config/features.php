<?php

/**
 * Single registry of package features (AGENTS.md 5.4).
 *
 * The Packages GUI (P3-T1) renders its toggles from this file, and
 * `Invitation::featureFlags()` uses the keys as the source of truth. Order
 * here is the order shown in the GUI.
 */
return [

    'rsvp' => [
        'label' => 'RSVP online',
        'description' => 'Tamu dapat mengonfirmasi kehadiran lewat undangan.',
    ],

    'gallery' => [
        'label' => 'Galeri foto',
        'description' => 'Bagian galeri foto pada undangan.',
    ],

    'music' => [
        'label' => 'Musik latar',
        'description' => 'Pemutar musik di halaman undangan.',
    ],

    'info_section' => [
        'label' => 'Info & pengumuman',
        'description' => 'Bagian informasi, dress code, dan pengumuman.',
    ],

    'guestbook' => [
        'label' => 'Buku tamu & scanner',
        'description' => 'Scanner check-in dan ekspor data kehadiran.',
    ],

    'qr_cards' => [
        'label' => 'Kartu QR',
        'description' => 'Cetak label QR dan unduh QR per tamu.',
    ],

    'photobooth' => [
        'label' => 'Photobooth',
        'description' => 'Photobooth yang terbuka untuk tamu yang sudah check-in.',
    ],

    'memory_book' => [
        'label' => 'Memory book',
        'description' => 'Halaman cetak buku kenangan dari foto photobooth.',
    ],

];
