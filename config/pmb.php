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

];
