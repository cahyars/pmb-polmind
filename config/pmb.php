<?php

return [

    /*
    | Akun admin awal yang dibuat oleh AdminUserSeeder. Jika password kosong,
    | seeder membuat password acak dan menampilkannya sekali di terminal.
    */
    'admin' => [
        'name' => env('PMB_ADMIN_NAME', 'Admin PMB'),
        'email' => env('PMB_ADMIN_EMAIL', 'admin@polmind.ac.id'),
        'password' => env('PMB_ADMIN_PASSWORD'),
    ],

    /*
    | Nomor WhatsApp admin PMB untuk tombol chat camaba. Nomor ditulis dalam
    | format internasional tanpa tanda "+" (contoh 6282113296897).
    */
    'admin_whatsapp' => [
        'number' => env('PMB_ADMIN_WHATSAPP', '6282113296897'),
        'display' => env('PMB_ADMIN_WHATSAPP_DISPLAY', '+62 821-1329-6897'),
    ],

];
