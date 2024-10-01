<?php

	namespace App\Http\Controllers;
	use Exception;
	use App\Models\DisabledColumns;
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

	class ConfigClientes extends Controller
	{
		public function index(Request $request)
		{
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("list.ConfigClientes");

			if (!$permUser) {
				return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			try{

                $data = Session::all();

                if(!isset($data["ConfigClientes"]) || empty($data["ConfigClientes"])){
                    session(["ConfigClientes" => array("status"=>"0", "orderBy"=>array("column"=>"created_at","sorting"=>"1"),"limit"=>"10")]);
                    $data = Session::all();
                }

                $Filtros = new Security;
                if($request->input()){
                    $Limpar = false;
                    if($request->input("limparFiltros") == true){
                        $Limpar = true;
                    }

                    $arrayFilter = $Filtros->TratamentoDeFiltros($request->input(), $Limpar, ["ConfigClientes"]);
                    if($arrayFilter){
                        session(["ConfigClientes" => $arrayFilter]);
                        $data = Session::all();
                    }
                }

                $columnsTable = DisabledColumns::whereRouteOfList("list.ConfigClientes")
                    ->first()
                    ?->columns;

                $ConfigClientes = DB::table("config_clientes")

                ->select(DB::raw("config_clientes.*, DATE_FORMAT(config_clientes.created_at, '%d/%m/%Y - %H:%i:%s') as data_final

                "));

                if(isset($data["ConfigClientes"]["orderBy"])){
                    $Coluna = $data["ConfigClientes"]["orderBy"]["column"];
                    $ConfigClientes =  $ConfigClientes->orderBy("config_clientes.$Coluna",$data["ConfigClientes"]["orderBy"]["sorting"] ? "asc" : "desc");
                } else {
                    $ConfigClientes =  $ConfigClientes->orderBy("config_clientes.created_at", "desc");
                }

                if(isset($data["ConfigClientes"]["nome"])){
                    $AplicaFiltro = $data["ConfigClientes"]["nome"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.nome",  "like", "%" . $AplicaFiltro . "%");
                }
                if(isset($data["ConfigClientes"]["cpf"])){
                    $AplicaFiltro = $data["ConfigClientes"]["cpf"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.cpf",  "like", "%" . $AplicaFiltro . "%");
                }
                if(isset($data["ConfigClientes"]["email"])){
                    $AplicaFiltro = $data["ConfigClientes"]["email"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.email",  "like", "%" . $AplicaFiltro . "%");
                }
                if(isset($data["ConfigClientes"]["telefone"])){
                    $AplicaFiltro = $data["ConfigClientes"]["telefone"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.telefone",  "like", "%" . $AplicaFiltro . "%");
                }
                if(isset($data["ConfigClientes"]["endereco"])){
                    $AplicaFiltro = $data["ConfigClientes"]["endereco"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.endereco",  "like", "%" . $AplicaFiltro . "%");
                }
                if(isset($data["ConfigClientes"]["status"])){
                    $AplicaFiltro = $data["ConfigClientes"]["status"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.status",  "like", "%" . $AplicaFiltro . "%");
                }
                if(isset($data["ConfigClientes"]["created_at"])){
                    $AplicaFiltro = $data["ConfigClientes"]["created_at"];
                    $ConfigClientes = $ConfigClientes->Where("config_clientes.created_at",  "like", "%" . $AplicaFiltro . "%");
                }

                $ConfigClientes = $ConfigClientes->where("config_clientes.deleted", "0");

                $ConfigClientes = $ConfigClientes->paginate(($data["ConfigClientes"]["limit"] ?: 10))
                    ->appends(["page", "orderBy", "searchBy", "limit"]);

                $Acao = "Acessou a listagem do Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(1,$Modulo,$Acao);
                $Registros = $this->Registros();

                return Inertia::render("ConfigClientes/List", [
                    "columnsTable" => $columnsTable,
                    "ConfigClientes" => $ConfigClientes,

                    "Filtros" => $data["ConfigClientes"],
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
			$Total = DB::table("config_clientes")
			->where("config_clientes.deleted", "0")
			->count();

			$Ativos = DB::table("config_clientes")
			->where("config_clientes.deleted", "0")
			->where("config_clientes.status", "0")
			->count();

			$Inativos = DB::table("config_clientes")
			->where("config_clientes.deleted", "0")
			->where("config_clientes.status", "1")
			->count();

			$EsseMes = DB::table("config_clientes")
			->where("config_clientes.deleted", "0")
			->whereMonth("config_clientes.created_at", $mes)
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
			$Modulo = "ConfigClientes";
			$permUser = Auth::user()->hasPermissionTo("create.ConfigClientes");

			if (!$permUser) {
                return redirect()->route("list.Dashboard",["id"=>"1"]);
			}
			try{
                $Acao = "Abriu a Tela de Cadastro do Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(1,$Modulo,$Acao);

                return Inertia::render("ConfigClientes/Create",[ ]);

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
			$ConfigClientes = DB::table("config_clientes");
			$ConfigClientes = $ConfigClientes->where("deleted", "0");
			$ConfigClientes = $ConfigClientes->where("token", $id)->first();

			return $ConfigClientes->id;
		}

		public function store(Request $request)
		{
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("create.ConfigClientes");

			if (!$permUser) {
                return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			try{

                $save = new stdClass;

                $save->nome = $request->nome;
                $save->cpf = $request->cpf;
                $save->email = $request->email;
                $save->telefone = $request->telefone;
                $save->endereco = $request->endereco;

                $save->status = $request->status;
                $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

                $save = collect($save)->toArray();
                DB::table("config_clientes")->insert($save);
                $lastId = DB::getPdo()->lastInsertId();

                $Acao = "Inseriu um Novo Registro no Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(2,$Modulo,$Acao,$lastId);

                return redirect()->route("list.ConfigClientes");

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

			return redirect()->route("list.ConfigClientes");

		}




		public function edit($IDConfigClientes)
		{
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("edit.ConfigClientes");

			if (!$permUser) {
                return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			try{

                $AcaoID = $this->return_id($IDConfigClientes);

                $ConfigClientes = DB::table("config_clientes")
                ->where("token", $IDConfigClientes)
                ->first();

                $Acao = "Abriu a Tela de Edição do Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(1,$Modulo,$Acao,$AcaoID);

                return Inertia::render("ConfigClientes/Edit", [
                    "ConfigClientes" => $ConfigClientes,
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

			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("edit.ConfigClientes");

			if (!$permUser) {
                return redirect()->route("list.Dashboard",["id"=>"1"]);
			}


			try{

				$AcaoID = $this->return_id($id);

				$save = new stdClass;
				$save->nome = $request->nome;
                $save->cpf = $request->cpf;
                $save->email = $request->email;
                $save->telefone = $request->telefone;
                $save->endereco = $request->endereco;

                $save->status = $request->status;
                $save->token = md5(date("Y-m-d H:i:s").rand(0,999999999));

				$save = collect($save)->filter(function ($value) {
					return !is_null($value);
				});
				$save = $save->toArray();

				DB::table("config_clientes")
					->where("token", $id)
					->update($save);

				$Acao = "Editou um registro no Módulo de ConfigClientes";
				$Logs = new logs;
				$Logs->RegistraLog(3,$Modulo,$Acao,$AcaoID);

			    return redirect()->route("list.ConfigClientes");

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

		public function delete($IDConfigClientes)
		{
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("delete.ConfigClientes");

			if (!$permUser) {
                return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			try{

                $AcaoID = $this->return_id($IDConfigClientes);

                DB::table("config_clientes")
                    ->where("token", $IDConfigClientes)
                    ->update([
                        "deleted" => "1",
                    ]);

                $Acao = "Excluiu um registro no Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);

			    return redirect()->route("list.ConfigClientes");

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



		public function deleteSelected($IDConfigClientes=null)
		{
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("delete.ConfigClientes");

			if (!$permUser) {
				return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			try{

                $IDsRecebidos = explode(",",$IDConfigClientes);
                $total = count(array_filter($IDsRecebidos));
                if($total > 0){
                    foreach($IDsRecebidos as $id){
                        $AcaoID = $this->return_id($id);
                        DB::table("config_clientes")
                            ->where("token", $id)
                            ->update([
                                "deleted" => "1",
                            ]);
                        $Acao = "Excluiu um registro no Módulo de ConfigClientes";
                        $Logs = new logs;
                        $Logs->RegistraLog(4,$Modulo,$Acao,$AcaoID);
                    }
                }
                return redirect()->route("list.ConfigClientes");

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
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("delete.ConfigClientes");

			if (!$permUser) {
				return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			try{

                DB::table("config_clientes")
                    ->update([
                        "deleted" => "1",
                    ]);
                $Acao = "Excluiu TODOS os registros no Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(4,$Modulo,$Acao,0);

			    return redirect()->route("list.ConfigClientes");

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
			$Modulo = "ConfigClientes";

			$permUser = Auth::user()->hasPermissionTo("delete.ConfigClientes");

			if (!$permUser) {
				return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

            try{

                DB::table("config_clientes")
                    ->update([
                        "deleted" => "0",
                    ]);
                $Acao = "Restaurou TODOS os registros no Módulo de ConfigClientes";
                $Logs = new logs;
                $Logs->RegistraLog(4,$Modulo,$Acao,0);

                return redirect()->route("list.ConfigClientes");

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

			$ConfigClientes = DB::table("config_clientes")

			->select(DB::raw("config_clientes.*, DATE_FORMAT(config_clientes.created_at, '%d/%m/%Y - %H:%i:%s') as data_final"))
			->where("config_clientes.deleted","0");

            if(isset($data["ConfigClientes"]["nome"])){
                $AplicaFiltro = $data["ConfigClientes"]["nome"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.nome",  "like", "%" . $AplicaFiltro . "%");
            }
            if(isset($data["ConfigClientes"]["cpf"])){
                $AplicaFiltro = $data["ConfigClientes"]["cpf"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.cpf",  "like", "%" . $AplicaFiltro . "%");
            }
            if(isset($data["ConfigClientes"]["email"])){
                $AplicaFiltro = $data["ConfigClientes"]["email"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.email",  "like", "%" . $AplicaFiltro . "%");
            }
            if(isset($data["ConfigClientes"]["telefone"])){
                $AplicaFiltro = $data["ConfigClientes"]["telefone"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.telefone",  "like", "%" . $AplicaFiltro . "%");
            }
            if(isset($data["ConfigClientes"]["endereco"])){
                $AplicaFiltro = $data["ConfigClientes"]["endereco"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.endereco",  "like", "%" . $AplicaFiltro . "%");
            }
            if(isset($data["ConfigClientes"]["status"])){
                $AplicaFiltro = $data["ConfigClientes"]["status"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.status",  "like", "%" . $AplicaFiltro . "%");
            }
            if(isset($data["ConfigClientes"]["created_at"])){
                $AplicaFiltro = $data["ConfigClientes"]["created_at"];
                $ConfigClientes = $ConfigClientes->Where("config_clientes.created_at",  "like", "%" . $AplicaFiltro . "%");
            }

			$ConfigClientes = $ConfigClientes->get();

			$DadosConfigClientes = [];
			foreach($ConfigClientes as $config_clientes){
				if($config_clientes->status == "0"){
					$config_clientes->status = "Ativo";
				}
				if($config_clientes->status == "1"){
					$config_clientes->status = "Inativo";
				}
				$DadosConfigClientes[] = [

                    'nome' => $config_clientes->nome,
                    'cpf' => $config_clientes->cpf,
                    'email' => $config_clientes->email,
                    'telefone' => $config_clientes->telefone,
                    'endereco' => $config_clientes->endereco,

                    'status' => $config_clientes->status,
                    'data_final' => $config_clientes->data_final
				];
			}
			return $DadosConfigClientes;
		}

		public function exportarRelatorioExcel(){

			$permUser = Auth::user()->hasPermissionTo("create.ConfigClientes");

			if (!$permUser) {
				return redirect()->route("list.Dashboard",["id"=>"1"]);
			}

			$filePath = "Relatorio_ConfigClientes.xlsx";

			if (Storage::disk("public")->exists($filePath)) {
				Storage::disk("public")->delete($filePath);
				// Arquivo foi deletado com sucesso
			}

			$cabecalhoAba1 = array('nome','cpf','email','telefone','endereco','status','Data de Cadastro');

			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$config_clientes = $this->DadosRelatorio();

			// Define o título da primeira aba
			$spreadsheet->setActiveSheetIndex(0);
			$spreadsheet->getActiveSheet()->setTitle("ConfigClientes");

			// Adiciona os cabeçalhos da tabela na primeira aba
			$spreadsheet->getActiveSheet()->fromArray($cabecalhoAba1, null, "A1");

			// Adiciona os dados da tabela na primeira aba
			$spreadsheet->getActiveSheet()->fromArray($config_clientes, null, "A2");

			// Definindo a largura automática das colunas na primeira aba
			foreach ($spreadsheet->getActiveSheet()->getColumnDimensions() as $col) {
				$col->setAutoSize(true);
			}

			// Habilita a funcionalidade de filtro para as células da primeira aba
			$spreadsheet->getActiveSheet()->setAutoFilter($spreadsheet->getActiveSheet()->calculateWorksheetDimension());


			// Define o nome do arquivo
			$nomeArquivo = "Relatorio_ConfigClientes.xlsx";
			// Cria o arquivo
			$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
			$writer->save($nomeArquivo);
			$barra = "'/'";
			$barra = str_replace("'","",$barra);
			$writer->save(storage_path("app".$barra."relatorio".$barra.$nomeArquivo));

			return redirect()->route("download2.files",["path"=>$nomeArquivo]);

		}
	}
