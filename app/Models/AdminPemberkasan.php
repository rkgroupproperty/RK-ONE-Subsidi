<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPemberkasan extends Model
{
    use HasFactory;
    protected $table    = 'admin_pemberkasan';
    protected $fillable = [
        'nama_lengkap',
        'no_telp',
        'foto',
        'status',
    ];

    public $timestamps = false;
}
