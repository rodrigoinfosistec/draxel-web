<?php

namespace App\Modules\Worktime\Enums;

enum EmployeeEventTimeMode: string
{
    case Day = 'day';
    case Partial = 'partial';
}
