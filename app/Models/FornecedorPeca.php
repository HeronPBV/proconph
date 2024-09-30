<?php

namespace App\Models;

use App\Models\Fornecedor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FornecedorPeca extends Model
{
    use HasFactory;

    protected $table = 'fornecedor_peca';

    protected $guarded = ['created_at', 'updated_at'];

    public function fornecedor()
    {
        return $this->hasOne(Fornecedor::class, 'id', 'id_fornecedor');
    }
}
