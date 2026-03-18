<?php

use App\Modules\Worktime\Parsers\KnupRE1032Parser;

return [
    'name' => 'Worktime',

    'clock_devices' => [
        [
            'name' => 'Knup RE-1032',
            'slug' => 'knup-re-1032',
        ],
    ],

    'employee_identifier_column' => 'registration',

    'device_parsers' => [
        'knup-re-1032' => KnupRE1032Parser::class,
    ],
];
