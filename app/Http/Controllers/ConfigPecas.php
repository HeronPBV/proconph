<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use App\Models\Peca;
use Inertia\Inertia;
use App\Models\Office;
use App\Models\Cliente;
use App\Models\Fornecedor;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\FornecedorPeca;
use App\Models\DisabledColumns;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ConfigPecas extends Controller
{
    public function index(Request $request)
    {
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("list.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $data = Session::all();

            if(!isset($data["ConfigPecas"]) || empty($data["ConfigPecas"])){
                session(["ConfigPecas" => array("status"=>"0", "orderBy"=>array("column"=>"created_at","sorting"=>"1"),"limit"=>"10")]);
                $data = Session::all();
            }

            $Filtros = new Security;
            if($request->input()){
                $Limpar = false;
                if($request->input("limparFiltros") == true){
                    $Limpar = true;
                }

                $arrayFilter = $Filtros->TratamentoDeFiltros($request->input(), $Limpar, ["ConfigPecas"]);
                if($arrayFilter){
                    session(["ConfigPecas" => $arrayFilter]);
                    $data = Session::all();
                }
            }

            $columnsTable = DisabledColumns::whereRouteOfList("list.ConfigPecas")
                ->first()
                ?->columns;

            $ConfigPecas = Peca::withCount('fornecedores')
                ->selectRaw("config_pecas.*, DATE_FORMAT(config_pecas.created_at, '%d/%m/%Y - %H:%i:%s') as data_final")
                ->where('config_pecas.deleted', '0');


            $sortableColumns = ['nome', 'descricao', 'status', 'created_at'];

            if (isset($data["ConfigPecas"]["orderBy"])) {
                $Coluna = $data["ConfigPecas"]["orderBy"]["column"];
                if (in_array($Coluna, $sortableColumns)) {
                    $ConfigPecas = $ConfigPecas->orderBy("config_pecas.$Coluna", $data["ConfigPecas"]["orderBy"]["sorting"] ? "asc" : "desc");
                } else {
                    // Se a coluna não for válida, a ordenação ocorre por uma coluna padrão
                    $ConfigPecas = $ConfigPecas->orderBy("config_pecas.created_at", "desc");
                }
            } else {
                $ConfigPecas = $ConfigPecas->orderBy("config_pecas.created_at", "desc");
            }


            $filterableColumns = ['nome', 'descricao', 'status', 'created_at'];

            foreach ($filterableColumns as $column) {
                if (isset($data["ConfigPecas"][$column])) {
                    $AplicaFiltro = $data["ConfigPecas"][$column];
                    $ConfigPecas = $ConfigPecas->where("config_pecas.$column", "like", "%" . $AplicaFiltro . "%");
                }
            }

            $ConfigPecas = $ConfigPecas->paginate($data["ConfigPecas"]["limit"] ?: 10)
                ->appends(["page", "orderBy", "searchBy", "limit"]);

            $Acao = "Acessou a listagem do Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao);
            $Registros = $this->Registros();

            return Inertia::render("ConfigPecas/List", [
                "columnsTable" => $columnsTable,
                "ConfigPecas" => $ConfigPecas,

                "Filtros" => $data["ConfigPecas"],
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
        $Total = DB::table("config_pecas")
        ->where("config_pecas.deleted", "0")
        ->count();

        $Ativos = DB::table("config_pecas")
        ->where("config_pecas.deleted", "0")
        ->where("config_pecas.status", "0")
        ->count();

        $Inativos = DB::table("config_pecas")
        ->where("config_pecas.deleted", "0")
        ->where("config_pecas.status", "1")
        ->count();

        $EsseMes = DB::table("config_pecas")
        ->where("config_pecas.deleted", "0")
        ->whereMonth("config_pecas.created_at", $mes)
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
        $Modulo = "ConfigPecas";
        $permUser = Auth::user()->hasPermissionTo("create.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }
        try{
            $Acao = "Abriu a Tela de Cadastro do Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao);

            $fornecedores = DB::table('config_fornecedores')->where('deleted', 0)->get();

            return Inertia::render("ConfigPecas/Create",[
                "Fornecedores" => $fornecedores
            ]);

        } catch (Exception $e) {

            $Error = $e->getMessage();
            $Error = explode("MESSAGE:",$Error);


            $Pagina = $_SERVER["REQUEST_URI"];

            $Erro = $Error[0];
            $Erro_Completo = $e->getMessage();
            $LogsErrors = new logsErrosController;
            $Registra = $LogsErrors->RegistraErro($Pagina,$Modulo,$Erro,$Erro_Completo);
            abort(403, "Erro localizado e enviado ao LOG de Erros");
        }

    }

    public function return_id($id)
    {
        $ConfigPecas = DB::table("config_pecas");
        $ConfigPecas = $ConfigPecas->where("deleted", "0");
        $ConfigPecas = $ConfigPecas->where("token", $id)->first();

        return $ConfigPecas->id;
    }

    public function store(Request $request)
    {
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("create.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $save = new stdClass;

            $save->nome = $request->nome;
            $save->descricao = $request->descricao;

            $save->status = $request->status;
            $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

            $save = collect($save)->toArray();
            DB::table("config_pecas")->insert($save);
            $lastId = DB::getPdo()->lastInsertId();

            foreach ($request->fornecedores as $fornecedor) {

                FornecedorPeca::create([
                    'id_fornecedor' => $fornecedor['id_fornecedor'],
                    'id_peca' => $lastId,
                    'preco' => $fornecedor['preco']
                ]);

            }


            $Acao = "Inseriu um Novo Registro no Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(2,$Modulo,$Acao,$lastId);

            return redirect()->route("list.ConfigPecas");

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

        return redirect()->route("list.ConfigPecas");

    }




    public function edit($IDConfigPecas)
    {
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("edit.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $AcaoID = $this->return_id($IDConfigPecas);

            $ConfigPecas = Peca::where('token', $IDConfigPecas)->first();

            $fornecedoresDaPeca = FornecedorPeca::where('id_peca', $ConfigPecas->id)->get();

            $fornecedores = Fornecedor::where('deleted', 0)->get();

            $Acao = "Abriu a Tela de Edição do Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao,$AcaoID);

            return Inertia::render("ConfigPecas/Edit", [
                "ConfigPecas" => $ConfigPecas,
                "FornecedoresPeca" => $fornecedoresDaPeca,
                "Fornecedores" => $fornecedores
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

        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("edit.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }


        try{

            $AcaoID = $this->return_id($id);

            $save = new stdClass;
            $save->nome = $request->nome;
            $save->descricao = $request->cpf;

            $save->status = $request->status;
            $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

            $save = collect($save)->filter(function ($value) {
                return !is_null($value);
            });
            $save = $save->toArray();

            DB::table("config_pecas")
                ->where("token", $id)
                ->update($save);

            $Peca = Peca::where('token', $save['token'])->first();
            $id_peca = $Peca->id;

            $fornecedoresExistentes = FornecedorPeca::where('id_peca', $id_peca)->get()->keyBy('id_fornecedor');

            foreach ($request->fornecedores as $fornecedor) {
                $idFornecedor = $fornecedor['id_fornecedor'];
                $preco = $fornecedor['preco'];

                if ($fornecedoresExistentes->has($idFornecedor)) {

                    $fornecedorExistente = $fornecedoresExistentes->get($idFornecedor);
                    $fornecedorExistente->preco = $preco;
                    $fornecedorExistente->save();

                    $fornecedoresExistentes->forget($idFornecedor);
                } else {
                    FornecedorPeca::create([
                        'id_fornecedor' => $idFornecedor,
                        'id_peca' => $id_peca,
                        'preco' => $preco,
                    ]);
                }
            }

            foreach ($fornecedoresExistentes as $fornecedorExistente) {
                $fornecedorExistente->delete();
            }


            $Acao = "Editou um registro no Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(3,$Modulo,$Acao,$AcaoID);

            return redirect()->route("list.ConfigPecas");

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

    public function delete($IDConfigPecas)
    {
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $AcaoID = $this->return_id($IDConfigPecas);

            DB::table("config_pecas")
                ->where("token", $IDConfigPecas)
                ->update([
                    "deleted" => "1",
                ]);

            $Acao = "Excluiu um registro no Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);

            return redirect()->route("list.ConfigPecas");

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



    public function deleteSelected($IDConfigPecas=null)
    {
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $IDsRecebidos = explode(",",$IDConfigPecas);
            $total = count(array_filter($IDsRecebidos));
            if($total > 0){
                foreach($IDsRecebidos as $id){
                    $AcaoID = $this->return_id($id);
                    DB::table("config_pecas")
                        ->where("token", $id)
                        ->update([
                            "deleted" => "1",
                        ]);
                    $Acao = "Excluiu um registro no Módulo de ConfigPecas";
                    $Logs = new logs;
                    $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);
                }
            }
            return redirect()->route("list.ConfigPecas");

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
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            DB::table("config_pecas")
                ->update([
                    "deleted" => "1",
                ]);
            $Acao = "Excluiu TODOS os registros no Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,0);

            return redirect()->route("list.ConfigPecas");

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
        $Modulo = "ConfigPecas";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            DB::table("config_pecas")
                ->update([
                    "deleted" => "0",
                ]);
            $Acao = "Restaurou TODOS os registros no Módulo de ConfigPecas";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,0);

            return redirect()->route("list.ConfigPecas");

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

        $ConfigPecas = DB::table("config_pecas")

        ->select(DB::raw("config_pecas.*, DATE_FORMAT(config_pecas.created_at, '%d/%m/%Y - %H:%i:%s') as data_final"))
        ->where("config_pecas.deleted","0");

        $filterableColumns = ['nome', 'cpf', 'email', 'telefone', 'endereco', 'status', 'created_at'];

        foreach ($filterableColumns as $column) {
            if (isset($data["ConfigPecas"][$column])) {
                $AplicaFiltro = $data["ConfigPecas"][$column];
                $ConfigPecas = $ConfigPecas->where("config_pecas.$column", "like", "%" . $AplicaFiltro . "%");
            }
        }

        $ConfigPecas = $ConfigPecas->get();

        $DadosConfigPecas = [];
        foreach($ConfigPecas as $config_pecas){
            if($config_pecas->status == "0"){
                $config_pecas->status = "Ativo";
            }
            if($config_pecas->status == "1"){
                $config_pecas->status = "Inativo";
            }
            $DadosConfigPecas[] = [

                'nome' => $config_pecas->nome,
                'descricao' => $config_pecas->cpf,

                'status' => $config_pecas->status,
                'data_final' => $config_pecas->data_final
            ];
        }
        return $DadosConfigPecas;
    }

    public function exportarRelatorioExcel(){

        $permUser = Auth::user()->hasPermissionTo("create.ConfigPecas");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        $filePath = "Relatorio_ConfigPecas.xlsx";

        if (Storage::disk("public")->exists($filePath)) {
            Storage::disk("public")->delete($filePath);
            // Arquivo foi deletado com sucesso
        }

        $cabecalhoAba1 = array('nome', 'descricao','status','Data de Cadastro');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $config_pecas = $this->DadosRelatorio();

        // Define o título da primeira aba
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle("ConfigPecas");

        // Adiciona os cabeçalhos da tabela na primeira aba
        $spreadsheet->getActiveSheet()->fromArray($cabecalhoAba1, null, "A1");

        // Adiciona os dados da tabela na primeira aba
        $spreadsheet->getActiveSheet()->fromArray($config_pecas, null, "A2");

        // Definindo a largura automática das colunas na primeira aba
        foreach ($spreadsheet->getActiveSheet()->getColumnDimensions() as $col) {
            $col->setAutoSize(true);
        }

        // Habilita a funcionalidade de filtro para as células da primeira aba
        $spreadsheet->getActiveSheet()->setAutoFilter($spreadsheet->getActiveSheet()->calculateWorksheetDimension());


        // Define o nome do arquivo
        $nomeArquivo = "Relatorio_ConfigPecas.xlsx";
        // Cria o arquivo
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($nomeArquivo);
        $barra = "'/'";
        $barra = str_replace("'","",$barra);
        $writer->save(storage_path("app".$barra."relatorio".$barra.$nomeArquivo));

        return redirect()->route("download2.files",["path"=>$nomeArquivo]);

    }
}
