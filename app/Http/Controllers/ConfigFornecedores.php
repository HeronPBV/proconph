<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Exception;
use App\Models\DisabledColumns;
use App\Models\Fornecedor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use stdClass;

class ConfigFornecedores extends Controller
{
    public function index(Request $request)
    {
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("list.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $data = Session::all();

            if(!isset($data["ConfigFornecedores"]) || empty($data["ConfigFornecedores"])){
                session(["ConfigFornecedores" => array("status"=>"0", "orderBy"=>array("column"=>"created_at","sorting"=>"1"),"limit"=>"10")]);
                $data = Session::all();
            }

            $Filtros = new Security;
            if($request->input()){
                $Limpar = false;
                if($request->input("limparFiltros") == true){
                    $Limpar = true;
                }

                $arrayFilter = $Filtros->TratamentoDeFiltros($request->input(), $Limpar, ["ConfigFornecedores"]);
                if($arrayFilter){
                    session(["ConfigFornecedores" => $arrayFilter]);
                    $data = Session::all();
                }
            }

            $columnsTable = DisabledColumns::whereRouteOfList("list.ConfigFornecedores")
                ->first()
                ?->columns;

            $ConfigFornecedores = Fornecedor::selectRaw("*, DATE_FORMAT(created_at, '%d/%m/%Y - %H:%i:%s') as data_final")
                ->where('deleted', '0');


            $sortableColumns = ['nome', 'endereco', 'telefone', 'email', 'status', 'created_at'];

            if (isset($data["ConfigFornecedores"]["orderBy"])) {
                $Coluna = $data["ConfigFornecedores"]["orderBy"]["column"];
                if (in_array($Coluna, $sortableColumns)) {
                    $ConfigFornecedores = $ConfigFornecedores->orderBy("config_fornecedores.$Coluna", $data["ConfigFornecedores"]["orderBy"]["sorting"] ? "asc" : "desc");
                } else {
                    // Se a coluna não for válida, a ordenação ocorre por uma coluna padrão
                    $ConfigFornecedores = $ConfigFornecedores->orderBy("config_fornecedores.created_at", "desc");
                }
            } else {
                $ConfigFornecedores = $ConfigFornecedores->orderBy("config_fornecedores.created_at", "desc");
            }


            $filterableColumns = ['nome', 'endereco', 'telefone', 'email', 'status', 'created_at'];

            foreach ($filterableColumns as $column) {
                if (isset($data["ConfigFornecedores"][$column])) {
                    $AplicaFiltro = $data["ConfigFornecedores"][$column];
                    $ConfigFornecedores = $ConfigFornecedores->where("config_fornecedores.$column", "like", "%" . $AplicaFiltro . "%");
                }
            }

            $ConfigFornecedores = $ConfigFornecedores->paginate($data["ConfigFornecedores"]["limit"] ?: 10)
                ->appends(["page", "orderBy", "searchBy", "limit"]);

            $Acao = "Acessou a listagem do Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao);
            $Registros = $this->Registros();

            return Inertia::render("ConfigFornecedores/List", [
                "columnsTable" => $columnsTable,
                "ConfigFornecedores" => $ConfigFornecedores,

                "Filtros" => $data["ConfigFornecedores"],
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
        $Total = DB::table("config_fornecedores")
        ->where("config_fornecedores.deleted", "0")
        ->count();

        $Ativos = DB::table("config_fornecedores")
        ->where("config_fornecedores.deleted", "0")
        ->where("config_fornecedores.status", "0")
        ->count();

        $Inativos = DB::table("config_fornecedores")
        ->where("config_fornecedores.deleted", "0")
        ->where("config_fornecedores.status", "1")
        ->count();

        $EsseMes = DB::table("config_fornecedores")
        ->where("config_fornecedores.deleted", "0")
        ->whereMonth("config_fornecedores.created_at", $mes)
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
        $Modulo = "ConfigFornecedores";
        $permUser = Auth::user()->hasPermissionTo("create.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }
        try{
            $Acao = "Abriu a Tela de Cadastro do Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao);

            return Inertia::render("ConfigFornecedores/Create",[ ]);

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
        $ConfigFornecedores = DB::table("config_fornecedores");
        $ConfigFornecedores = $ConfigFornecedores->where("deleted", "0");
        $ConfigFornecedores = $ConfigFornecedores->where("token", $id)->first();

        return $ConfigFornecedores->id;
    }

    public function store(Request $request)
    {
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("create.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $save = new stdClass;

            $save->nome = $request->nome;
            $save->endereco = $request->endereco;
            $save->telefone = $request->telefone;
            $save->email = $request->email;

            $save->status = $request->status;
            $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

            $save = collect($save)->toArray();
            DB::table("config_fornecedores")->insert($save);
            $lastId = DB::getPdo()->lastInsertId();

            $Acao = "Inseriu um Novo Registro no Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(2,$Modulo,$Acao,$lastId);

            return redirect()->route("list.ConfigFornecedores");

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

        return redirect()->route("list.ConfigFornecedores");

    }




    public function edit($IDConfigFornecedores)
    {
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("edit.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $AcaoID = $this->return_id($IDConfigFornecedores);

            $ConfigFornecedores = DB::table("config_fornecedores")
            ->where("token", $IDConfigFornecedores)
            ->first();

            $Acao = "Abriu a Tela de Edição do Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(1,$Modulo,$Acao,$AcaoID);

            return Inertia::render("ConfigFornecedores/Edit", [
                "ConfigFornecedores" => $ConfigFornecedores,
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

        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("edit.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }


        try{

            $AcaoID = $this->return_id($id);

            $save = new stdClass;
            $save->nome = $request->nome;
            $save->endereco = $request->endereco;
            $save->telefone = $request->telefone;
            $save->email = $request->email;

            $save->status = $request->status;
            $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

            $save = collect($save)->filter(function ($value) {
                return !is_null($value);
            });
            $save = $save->toArray();

            DB::table("config_fornecedores")
                ->where("token", $id)
                ->update($save);

            $Acao = "Editou um registro no Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(3,$Modulo,$Acao,$AcaoID);

            return redirect()->route("list.ConfigFornecedores");

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

    public function delete($IDConfigFornecedores)
    {
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $AcaoID = $this->return_id($IDConfigFornecedores);

            DB::table("config_fornecedores")
                ->where("token", $IDConfigFornecedores)
                ->update([
                    "deleted" => "1",
                ]);

            $Acao = "Excluiu um registro no Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);

            return redirect()->route("list.ConfigFornecedores");

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



    public function deleteSelected($IDConfigFornecedores=null)
    {
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            $IDsRecebidos = explode(",",$IDConfigFornecedores);
            $total = count(array_filter($IDsRecebidos));
            if($total > 0){
                foreach($IDsRecebidos as $id){
                    $AcaoID = $this->return_id($id);
                    DB::table("config_fornecedores")
                        ->where("token", $id)
                        ->update([
                            "deleted" => "1",
                        ]);
                    $Acao = "Excluiu um registro no Módulo de ConfigFornecedores";
                    $Logs = new logs;
                    $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);
                }
            }
            return redirect()->route("list.ConfigFornecedores");

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
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            DB::table("config_fornecedores")
                ->update([
                    "deleted" => "1",
                ]);
            $Acao = "Excluiu TODOS os registros no Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,0);

            return redirect()->route("list.ConfigFornecedores");

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
        $Modulo = "ConfigFornecedores";

        $permUser = Auth::user()->hasPermissionTo("delete.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        try{

            DB::table("config_fornecedores")
                ->update([
                    "deleted" => "0",
                ]);
            $Acao = "Restaurou TODOS os registros no Módulo de ConfigFornecedores";
            $Logs = new logs;
            $Logs->RegistraLog(4,$Modulo,$Acao,0);

            return redirect()->route("list.ConfigFornecedores");

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

        $ConfigFornecedores = DB::table("config_fornecedores")

        ->select(DB::raw("config_fornecedores.*, DATE_FORMAT(config_fornecedores.created_at, '%d/%m/%Y - %H:%i:%s') as data_final"))
        ->where("config_fornecedores.deleted","0");

        $filterableColumns = ['nome', 'endereco', 'telefone', 'email', 'status', 'created_at'];

        foreach ($filterableColumns as $column) {
            if (isset($data["ConfigFornecedores"][$column])) {
                $AplicaFiltro = $data["ConfigFornecedores"][$column];
                $ConfigFornecedores = $ConfigFornecedores->where("config_fornecedores.$column", "like", "%" . $AplicaFiltro . "%");
            }
        }

        $ConfigFornecedores = $ConfigFornecedores->get();

        $DadosConfigFornecedores = [];
        foreach($ConfigFornecedores as $config_fornecedores){
            if($config_fornecedores->status == "0"){
                $config_fornecedores->status = "Ativo";
            }
            if($config_fornecedores->status == "1"){
                $config_fornecedores->status = "Inativo";
            }
            $DadosConfigFornecedores[] = [

                'nome' => $config_fornecedores->nome,
                'endereco' => $config_fornecedores->endereco,
                'telefone' => $config_fornecedores->telefone,
                'email' => $config_fornecedores->email,

                'status' => $config_fornecedores->status,
                'data_final' => $config_fornecedores->data_final
            ];
        }
        return $DadosConfigFornecedores;
    }

    public function exportarRelatorioExcel(){

        $permUser = Auth::user()->hasPermissionTo("create.ConfigFornecedores");

        if (!$permUser) {
            return redirect()->route("list.Dashboard",["id"=>"1"]);
        }

        $filePath = "Relatorio_ConfigFornecedores.xlsx";

        if (Storage::disk("public")->exists($filePath)) {
            Storage::disk("public")->delete($filePath);
            // Arquivo foi deletado com sucesso
        }

        $cabecalhoAba1 = array('nome','endereco','telefone','email','status','Data de Cadastro');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $config_fornecedores = $this->DadosRelatorio();

        // Define o título da primeira aba
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle("ConfigFornecedores");

        // Adiciona os cabeçalhos da tabela na primeira aba
        $spreadsheet->getActiveSheet()->fromArray($cabecalhoAba1, null, "A1");

        // Adiciona os dados da tabela na primeira aba
        $spreadsheet->getActiveSheet()->fromArray($config_fornecedores, null, "A2");

        // Definindo a largura automática das colunas na primeira aba
        foreach ($spreadsheet->getActiveSheet()->getColumnDimensions() as $col) {
            $col->setAutoSize(true);
        }

        // Habilita a funcionalidade de filtro para as células da primeira aba
        $spreadsheet->getActiveSheet()->setAutoFilter($spreadsheet->getActiveSheet()->calculateWorksheetDimension());


        // Define o nome do arquivo
        $nomeArquivo = "Relatorio_ConfigFornecedores.xlsx";
        // Cria o arquivo
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($nomeArquivo);
        $barra = "'/'";
        $barra = str_replace("'","",$barra);
        $writer->save(storage_path("app".$barra."relatorio".$barra.$nomeArquivo));

        return redirect()->route("download2.files",["path"=>$nomeArquivo]);

    }
}
