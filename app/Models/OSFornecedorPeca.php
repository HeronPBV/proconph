<?php

namespace App\Models;

use App\Models\Peca;
use App\Models\Servico;
use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OSFornecedorPeca extends Model
{
    use HasFactory;

    protected $table = 'ordem_servico_pecas';

    protected $guarded = ['id'];

}
