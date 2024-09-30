<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'config_clientes';

    protected $guarded = ['created_at', 'updated_at'];

    public function veiculos()
    {
        return $this->hasMany(Veiculo::class, 'id_proprietario');
    }
}
