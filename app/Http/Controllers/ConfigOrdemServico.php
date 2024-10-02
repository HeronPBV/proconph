<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use Inertia\Inertia;
use App\Models\Office;
use App\Models\Cliente;
use App\Models\Veiculo;
use App\Models\Fornecedor;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\FornecedorPeca;
use App\Models\OrdemDeServico;
use App\Models\DisabledColumns;
use App\Models\OSFornecedorPeca;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ConfigOrdemServico extends Controller
{
    public function index(Request $request)
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("list.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $data = Session::all();

            if(!isset($data["ConfigOrdemServico"]) || empty($data["ConfigOrdemServico"])){
                session(["ConfigOrdemServico" => array("status"=>"0", "orderBy"=>array("column"=>"created_at","sorting"=>"1"),"limit"=>"10")]);
                $data = Session::all();
            }

            $Filtros = new Security;
            if($request->input()){
                $Limpar = false;
                if($request->input("limparFiltros") == true){
                    $Limpar = true;
                }

                $arrayFilter = $Filtros->TratamentoDeFiltros($request->input(), $Limpar, ["ConfigOrdemServico"]);
                if($arrayFilter){
                    session(["ConfigOrdemServico" => $arrayFilter]);
                    $data = Session::all();
                }
            }

            $columnsTable = DisabledColumns::whereRouteOfList("list.ConfigOrdemServico")
                ->first()
                ?->columns;

            $ConfigOrdemServico = OrdemDeServico::with(['veiculo', 'veiculo.proprietario']) // Carrega o veículo e o proprietário
                ->selectRaw("*, DATE_FORMAT(created_at, '%d/%m/%Y - %H:%i:%s') as data_final")
                ->where('deleted', '0');

            $sortableColumns = ['id_veiculo', 'descricao', 'situacao', 'status', 'created_at'];

            if (isset($data["ConfigOrdemServico"]["orderBy"])) {
                $Coluna = $data["ConfigOrdemServico"]["orderBy"]["column"];
                if (in_array($Coluna, $sortableColumns)) {
                    $ConfigOrdemServico = $ConfigOrdemServico->orderBy("ordens_servico.$Coluna", $data["ConfigOrdemServico"]["orderBy"]["sorting"] ? "asc" : "desc");
                } else {
                    // Se a coluna não for válida, a ordenação ocorre por uma coluna padrão
                    $ConfigOrdemServico = $ConfigOrdemServico->orderBy("ordens_servico.created_at", "desc");
                }
            } else {
                $ConfigOrdemServico = $ConfigOrdemServico->orderBy("ordens_servico.created_at", "desc");
            }


            $filterableColumns = ['nome', 'endereco', 'telefone', 'email', 'status', 'created_at'];

            foreach ($filterableColumns as $column) {
                if (isset($data["ConfigOrdemServico"][$column])) {
                    $AplicaFiltro = $data["ConfigOrdemServico"][$column];
                    $ConfigOrdemServico = $ConfigOrdemServico->where("ordens_servico.$column", "like", "%" . $AplicaFiltro . "%");
                }
            }

            $ConfigOrdemServico = $ConfigOrdemServico->paginate($data["ConfigOrdemServico"]["limit"] ?: 10)
                ->appends(["page", "orderBy", "searchBy", "limit"]);

            $Acao = "Acessou a listagem do Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao);
            $Registros = $this->Registros();

            return Inertia::render("ConfigOrdemServico/List", [
                "columnsTable" => $columnsTable,
                "ConfigOrdemServico" => $ConfigOrdemServico,

                "Filtros" => $data["ConfigOrdemServico"],
                "Registros" => $Registros,
            ]);

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);
            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }

    public function Registros()
    {

        $mes = date("m");
        $Total = DB::table("ordens_servico")
        ->where("ordens_servico.deleted", "0")
        ->count();

        $Ativos = DB::table("ordens_servico")
        ->where("ordens_servico.deleted", "0")
        ->where("ordens_servico.status", "0")
        ->count();

        $Inativos = DB::table("ordens_servico")
        ->where("ordens_servico.deleted", "0")
        ->where("ordens_servico.status", "1")
        ->count();

        $EsseMes = DB::table("ordens_servico")
        ->where("ordens_servico.deleted", "0")
        ->whereMonth("ordens_servico.created_at", $mes)
        ->count();


        $data = new stdClass;
        $data->total = number_format($Total, 0, ",", ".");
        $data->ativo = number_format($Ativos, 0, ",", ".");
        $data->inativo = number_format($Inativos, 0, ",", ".");
        $data->mes = number_format($EsseMes, 0, ",", ".");
        return $data;


    }

    public function create()
    {
        $Modulo = "ConfigOrdemServico";
        $permUser = Auth::user()->hasPermissionTo("create.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }
        try{
            $Acao = "Abriu a Tela de Cadastro do Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao);

            $veiculos = Veiculo::with('proprietario')->where('deleted', 0)->get();

            $fornecedorPeca = FornecedorPeca::with(['fornecedor', 'peca'])->get();

            return Inertia::render("ConfigOrdemServico/Create",[
                'Veiculos' => $veiculos,
                'FornecedorPeca' => $fornecedorPeca
            ]);

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);


            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);
            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }

    public function return_id($id)
    {
        $ConfigOrdemServico = DB::table("ordens_servico");
        $ConfigOrdemServico = $ConfigOrdemServico->where("deleted", "0");
        $ConfigOrdemServico = $ConfigOrdemServico->where("token", $id)->first();

        return $ConfigOrdemServico->id;
    }

    public function store(Request $request)
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("create.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $save = new stdClass;

            $save->id_veiculo = $request->id_veiculo;
            $save->descricao = $request->descricao;
            $save->situacao = $request->situacao;

            $save->status = $request->status;
            $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

            $save = collect($save)->toArray();
            DB::table("ordens_servico")->insert($save);
            $lastId = DB::getPdo()->lastInsertId();

            foreach ($request->fornecedor_pecas as $fornecedorPeca) {

                OSFornecedorPeca::create([
                    'id_ordem_servico' => $lastId,
                    'id_fornecedor_peca' => $fornecedorPeca['id_fornecedor_peca']
                ]);

            }

            $Acao = "Inseriu um Novo Registro no Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(2,$Modulo,$Acao,$lastId);

            return redirect()->route("list.ConfigOrdemServico");

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);
            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

        return redirect()->route("list.ConfigOrdemServico");

    }




    public function edit($IDConfigOrdemServico)
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("edit.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $AcaoID = $this->return_id($IDConfigOrdemServico);

            $ConfigOrdemServico = DB::table("ordens_servico")
            ->where("token", $IDConfigOrdemServico)
            ->first();

            $Acao = "Abriu a Tela de Edição do Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao,$AcaoID);

            $veiculos = Veiculo::with('proprietario')->where('deleted', 0)->get();

            $OSFornecedorPeca = OSFornecedorPeca::where('id_ordem_servico', $ConfigOrdemServico->id)->get();

            $FornecedorPeca = FornecedorPeca::with(['fornecedor', 'peca'])->get();

            return Inertia::render("ConfigOrdemServico/Edit", [
                "ConfigOrdemServico" => $ConfigOrdemServico,
                "OSFornecedorPecas" => $OSFornecedorPeca,
                "FornecedorPeca" => $FornecedorPeca,
                "Veiculos" => $veiculos
            ]);

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);
            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }


    public function update(Request $request, $id)
    {

        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("edit.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }


        try{

            $AcaoID = $this->return_id($id);

            $save = new stdClass;
            $save->id_veiculo = $request->id_veiculo;
            $save->descricao = $request->descricao;
            $save->situacao = $request->situacao;

            $save->status = $request->status;
            $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

            $save = collect($save)->filter(function ($value) {
                return !is_null($value);
            });
            $save = $save->toArray();

            DB::table("ordens_servico")
                ->where("token", $id)
                ->update($save);




            $OS = OrdemDeServico::where('token', $save['token'])->first();

            $OS->FornecedorPeca()->detach();

            foreach ($request->fornecedor_pecas as $fornecedor_peca) {
                $OS->FornecedorPeca()->attach($fornecedor_peca['id_fornecedor_peca']);
            }




            $Acao = "Editou um registro no Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(3,$Modulo,$Acao,$AcaoID);

            return redirect()->route("list.ConfigOrdemServico");

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);
            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }
    }

    public function delete($IDConfigOrdemServico)
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $AcaoID = $this->return_id($IDConfigOrdemServico);

            DB::table("ordens_servico")
                ->where("token", $IDConfigOrdemServico)
                ->update([
                    "deleted" => "1",
                ]);

            $Acao = "Excluiu um registro no Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);

            return redirect()->route("list.ConfigOrdemServico");

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);

            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }



    public function deleteSelected($IDConfigOrdemServico=null)
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $IDsRecebidos = explode(",",$IDConfigOrdemServico);
            $total = count(array_filter($IDsRecebidos));
            if($total > 0){
                foreach($IDsRecebidos as $id){
                    $AcaoID = $this->return_id($id);
                    DB::table("ordens_servico")
                        ->where("token", $id)
                        ->update([
                            "deleted" => "1",
                        ]);
                    $Acao = "Excluiu um registro no Módulo de ConfigOrdemServico";
                    $Logs = new logs;
                    $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);
                }
            }
            return redirect()->route("list.ConfigOrdemServico");

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);

            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }

    public function deletarTodos()
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            DB::table("ordens_servico")
                ->update([
                    "deleted" => "1",
                ]);
            $Acao = "Excluiu TODOS os registros no Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,0);

            return redirect()->route("list.ConfigOrdemServico");

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);

            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }

    public function RestaurarTodos()
    {
        $Modulo = "ConfigOrdemServico";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            DB::table("ordens_servico")
                ->update([
                    "deleted" => "0",
                ]);
            $Acao = "Restaurou TODOS os registros no Módulo de ConfigOrdemServico";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,0);

            return redirect()->route("list.ConfigOrdemServico");

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);

            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);

            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }

    public function DadosRelatorio(){
        $data = Session::all();

        $ConfigOrdemServico = DB::table("ordens_servico")

        ->select(DB::raw("ordens_servico.*, DATE_FORMAT(ordens_servico.created_at, '%d/%m/%Y - %H:%i:%s') as data_final"))
        ->where("ordens_servico.deleted","0");

        $filterableColumns = ['id_veiculo', 'descricao', 'situacao', 'status', 'created_at'];

        foreach ($filterableColumns as $column) {
            if (isset($data["ConfigOrdemServico"][$column])) {
                $AplicaFiltro = $data["ConfigOrdemServico"][$column];
                $ConfigOrdemServico = $ConfigOrdemServico->where("ordens_servico.$column", "like", "%" . $AplicaFiltro . "%");
            }
        }

        $ConfigOrdemServico = $ConfigOrdemServico->get();

        $DadosConfigOrdemServico = [];
        foreach($ConfigOrdemServico as $ordens_servico){
            if($ordens_servico->status == "0"){
                $ordens_servico->status = "Ativo";
            }
            if($ordens_servico->status == "1"){
                $ordens_servico->status = "Inativo";
            }
            $DadosConfigOrdemServico[] = [

                'id_veiculo' => $ordens_servico->id_veiculo,
                'descricao' => $ordens_servico->descricao,
                'situacao' => $ordens_servico->situacao,

                'status' => $ordens_servico->status,
                'data_final' => $ordens_servico->data_final
            ];
        }
        return $DadosConfigOrdemServico;
    }

    public function exportarRelatorioExcel(){

        $permUser = Auth::user()->hasPermissionTo("create.ConfigOrdemServico");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        $filePath = "Relatorio_ConfigOrdemServico.xlsx";

        if (Storage::disk("public")->exists($filePath)) {
            Storage::disk("public")->delete($filePath);
            // Arquivo foi deletado com sucesso
        }

        $cabecalhoAba1 = array('id_veiculo', 'descricao', 'situacao', 'status','Data de Cadastro');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $ordens_servico = $this->DadosRelatorio();

        // Define o título da primeira aba
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle("ConfigOrdemServico");

        // Adiciona os cabeçalhos da tabela na primeira aba
        $spreadsheet->getActiveSheet()->fromArray($cabecalhoAba1, null, "A1");

        // Adiciona os dados da tabela na primeira aba
        $spreadsheet->getActiveSheet()->fromArray($ordens_servico, null, "A2");

        // Definindo a largura automática das colunas na primeira aba
        foreach ($spreadsheet->getActiveSheet()->getColumnDimensions() as $col) {
            $col->setAutoSize(true);
        }

        // Habilita a funcionalidade de filtro para as células da primeira aba
        $spreadsheet->getActiveSheet()->setAutoFilter($spreadsheet->getActiveSheet()->calculateWorksheetDimension());


        // Define o nome do arquivo
        $nomeArquivo = "Relatorio_ConfigOrdemServico.xlsx";
        // Cria o arquivo
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($nomeArquivo);
        $barra = "'/'";
        $barra = str_replace("'","",$barra);
        $writer->save(storage_path("app".$barra."relatorio".$barra.$nomeArquivo));

        return redirect()->route("download2.files",["path"=>$nomeArquivo]);

    }
}
