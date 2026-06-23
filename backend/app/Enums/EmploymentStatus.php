<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Probationary = 'probationary';
    case Resigned = 'resigned';
    case Terminated = 'terminated';
}
