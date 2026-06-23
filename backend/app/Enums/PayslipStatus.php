<?php

namespace App\Enums;

enum PayslipStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
}
