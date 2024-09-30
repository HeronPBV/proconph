<?php

namespace App\Models;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Veiculo extends Model
{
    use HasFactory;

    protected $table = 'config_veiculos';

    protected $guarded = ['created_at', 'updated_at'];

    public function proprietario()
    {
        return $this->belongsTo(Cliente::class, 'id_proprietario');
    }
}
