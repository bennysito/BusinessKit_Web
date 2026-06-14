<?php


namespace App\Models;


use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SuperAdmin extends Admin
{
    use HasFactory, Notifiable, HasApiTokens;

    private string $name = 'Super Admin';
}
