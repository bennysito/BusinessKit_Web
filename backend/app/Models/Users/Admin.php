<?php


namespace App\Models;


use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Admin extends User
{
    use HasFactory, Notifiable, HasApiTokens;

    private string $name = 'Admin';
}
