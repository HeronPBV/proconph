<?php

namespace App\Models;

use App\Models\Peca;
use App\Models\Servico;
use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrdemDeServico extends Model
{
    use HasFactory;

    protected $table = 'ordens_servico';

    protected $guarded = ['created_at', 'updated_at'];

    public function veiculo()
    {
        return $this->hasOne(Veiculo::class, 'id', 'id_veiculo');
    }

    public function servicos()
    {
        return $this->belongsToMany(Servico::class, 'ordem_servico_servicos', 'id_ordem_servico', 'id_servico');
    }

    public function FornecedorPeca()
    {
        return $this->belongsToMany(FornecedorPeca::class, 'ordem_servico_pecas', 'id_ordem_servico', 'id_fornecedor_peca');
    }



    public function ValorTotalServicos() : float
    {
        return $this->servicos->sum('valor');
    }

    public function PrecoTotalPecas() : float
    {
        return $this->FornecedorPeca->sum('preco');
    }

    public function CustoTotal() : float
    {
        return ($this->ValorTotalServicos() + $this->PrecoTotalPecas());
    }

}
