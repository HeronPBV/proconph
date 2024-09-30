<?php

namespace App\Models;

use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Peca extends Model
{
    use HasFactory;

    protected $table = 'config_pecas';

    protected $guarded = ['created_at', 'updated_at'];

    public function fornecedores()
    {
        return $this->belongsToMany(Fornecedor::class, 'fornecedor_peca', 'peca_id', 'fornecedor_id')
                    ->withPivot('preco');
    }

}
