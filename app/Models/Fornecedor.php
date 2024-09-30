<?php

namespace App\Models;

use App\Models\Peca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'config_fornecedores';

    protected $guarded = ['created_at', 'updated_at'];

    public function pecas()
    {
        return $this->belongsToMany(Peca::class, 'fornecedor_peca', 'fornecedor_id', 'peca_id')
                    ->withPivot('preco');
    }

}
