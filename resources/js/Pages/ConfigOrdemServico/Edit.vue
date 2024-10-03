<template>
    <PageTitle title="Ordem de serviço" />
    <Nav class="mt-4 text-sm text-gray-400 list-none bg-white p-3 px-10 rounded-sm flex space-x-10 shadow-sm">
        <button class="font-bold" :class="{ 'text-primary': currentNav == 1 }" @click="currentNav = 1">
            Informações Gerais
        </button>
    </Nav>
    <form @submit.prevent="submit">

        <section class="mt-6 bg-white rounded-sm p-10 shadow-sm" v-if="currentNav == 1">

            <h2 :class="{
                    'bg-green-100 text-green-600 font-bold text-lg p-2 rounded-md': situacaoInicial === 'Aberta',
                    'bg-yellow-100 text-yellow-600 font-bold text-lg p-2 rounded-md': situacaoInicial === 'Fechada',
                    'bg-red-100 text-red-600 font-bold text-lg p-2 rounded-md': situacaoInicial === 'Cancelada'
                }">
                {{ situacaoInicial }}
            </h2>
            <br>
            <p>Peças: R$ {{ totalPecas.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
            <p>Mão de obra: R$ {{ totalServicos.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
            <br>
            <hr>
            <br>
            <p>Total: R$ {{ totalCusto.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>

        </section>


        <section class="mt-6 bg-white rounded-sm p-10 shadow-sm" v-if="currentNav == 1">
            <div class="flex flex-col space-y-1">
                <SectionTitle class="text-xs text-gray-600 font-bold uppercase">INFORMAÇÕES GERAIS</SectionTitle>
                <SectionTitle class="text-xs text-gray-600">Informações essenciais para edição de ordens de serviço no sistema.
                </SectionTitle>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 max-md:grid-cols-1">

                <div>
                    <span class="p-float-label">
                        <Dropdown class="w-full" id="veiculo" v-model="form.id_veiculo" :options="veiculos"
                            optionLabel="name" dataKey="value" required filter="true" />
                        <label for="veiculo" class="text-sm">Veículo</label>
                    </span>
                </div>
                <div>
                    <span class="p-float-label">
                        <Textarea v-model="form.descricao" id="descricao" :autoResize="true" rows="5" cols="60" class="w-full"
                            required />
                        <label for="descricao" class="text-sm">Descrição</label>
                    </span>
                </div>
                <div>
                    <span class="p-float-label">
                        <Dropdown class="w-full" id="situacao" v-model="form.situacao" :options="situacao"
                            required filter="true" />
                        <label for="situacao" class="text-sm">Situação</label>
                    </span>
                </div>
                <div>
                    <span class="p-float-label">
                        <Dropdown class="w-full" v-model="form.status" :options="statusOption" optionLabel="name"
                            dataKey="value" required />
                        <label for="status" class="text-sm">Status</label>
                    </span>
                </div>

            </div>

        </section>
        <section class="mt-6 bg-white rounded-sm p-10 shadow-sm" v-if="currentNav == 1">
            <div class="flex flex-col space-y-1">
                <SectionTitle class="text-xs text-gray-600 font-bold uppercase">Peças</SectionTitle>
                <SectionTitle class="text-xs text-gray-600">Peças utilizadas na OS em questão.</SectionTitle>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 max-md:grid-cols-1">

                <div>
                    <span class="p-float-label">
                        <Dropdown class="w-full" v-model="formFornecedorPeca.id_fornecedor_peca" :options="pecas_fornecedor" optionLabel="name"
                            dataKey="value" filter="true" />
                        <label for="status" class="text-sm">Peça</label>
                    </span>
                </div>

                <div class="mb-2">
                    <button class="p-2 flex rounded-md bg-lime-300 px-6 text-sm font-medium items-center" @click.prevent="addFornecedorPeca"> Adicionar </button>
                </div>

                <div v-for="fornecedor_peca in fornecedor_pecas_added" :key="fornecedor_peca.id" class="flex flex-row items-center">

                    <button class="bg-red-500 hover:bg-red-400 text-white font-bold p-1 rounded inline-flex items-center w-fit" @click.prevent="removeFornecedorPeca(fornecedor_peca)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                    <h1 class="text-[16px] ml-2">
                         {{ fornecedor_peca.nome }}
                    </h1>
                </div>

            </div>

        </section>

        <section class="mt-6 bg-white rounded-sm p-10 shadow-sm" v-if="currentNav == 1">
            <div class="flex flex-col space-y-1">
                <SectionTitle class="text-xs text-gray-600 font-bold uppercase">Serviços</SectionTitle>
                <SectionTitle class="text-xs text-gray-600">Serviços realizados na OS em questão.</SectionTitle>
            </div>

            <div class="mt-10 grid grid-cols-1 gap-6 max-md:grid-cols-1">

                <div>
                    <span class="p-float-label">
                        <Dropdown class="w-full" id="servicos" v-model="formServicos.id_servico" :options="servicos" optionLabel="name"
                            dataKey="value" filter="true" />
                        <label for="servicos" class="text-sm">Serviço</label>
                    </span>
                </div>

                <div class="mb-2">
                    <button class="p-2 flex rounded-md bg-lime-300 px-6 text-sm font-medium items-center" @click.prevent="addServico"> Adicionar </button>
                </div>

                <div v-for="servico in servicos_added" :key="servico.id_servico" class="flex flex-row items-center">

                    <button class="bg-red-500 hover:bg-red-400 text-white font-bold p-1 rounded inline-flex items-center w-fit" @click.prevent="removeServico(servico)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </button>
                    <h1 class="text-[16px] ml-2">
                        R$ {{ servico.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} - {{ servico.nome }}
                    </h1>
                </div>

            </div>

        </section>

        <section class="mt-6 bg-white rounded-sm p-10 shadow-sm" v-if="currentNav == 1">
            <div class="flex space-x-5">
                <button type="submit" :disabled="sending"
                    class="p-2 flex rounded-md bg-primary text-white px-6 text-sm font-medium items-center"
                    :class="{ 'bg-opacity-80 cursor-not-allowed': submited }">
                    <svg role="status" v-show="submited" class="mr-2 w-4 h-4 animate-spin fill-primary"
                        viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                            fill="currentColor"></path>
                        <path
                            d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0403 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                            fill="currentFill"></path>
                    </svg>
                    Salvar
                </button>
                <Link :href="route('list.ConfigOrdemServico')" as="button" type="button"
                    class="p-2 rounded-md bg-secundary text-white px-6 text-sm font-medium">
                Voltar
                </Link>
            </div>
        </section>
    </form>
</template>

<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import moment from "moment";
import { ref, computed, defineProps } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import MultiSelect from "primevue/multiselect";
import Dropdown from "primevue/dropdown";
import { useToast } from "vue-toastification";

const totalPecas = ref(0);
const totalServicos = ref(0);

const totalCusto = computed(() => totalPecas.value + totalServicos.value);

const props = defineProps({
    errorBags: Object,
    ConfigOrdemServico: Object,
    OSFornecedorPecas: Object,
    FornecedorPeca: Object,
    Veiculos: Object,
    Servicos: Object,
    ServicosNaOS: Object
});

const situacaoInicial = $propsPage?.value.ConfigOrdemServico?.situacao;

const servicos = $propsPage?.value?.Servicos?.map((val) => {
    return { name: `R$ ${val.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} - ${val.nome}`, valor: val.valor, value: val.id }
});

const formServicos = ref({
    id_servico: null
});

const servicos_added = ref([]);

const getNomeServico = (id) => {
    const tipo = servicos.find(t => t.value == id);
    return tipo ? tipo.name : 'Desconhecido';
};

const getValorServico = (id) => {
    const tipo = servicos.find(t => t.value == id);
    return tipo ? tipo.valor : 'Desconhecido';
};

function addServico() {

    if (formServicos.value.id_servico) {

        const valorServico = Number(getValorServico(formServicos.value.id_servico.value));

        servicos_added.value.push({

            id_servico: formServicos.value.id_servico.value,

            nome: getNomeServico(formServicos.value.id_servico.value),

            valor: valorServico

        });

        totalServicos.value += valorServico;

        // Limpa os campos após adicionar
        formServicos.value.id_servico = null;
        toast.success("Serviço adicionado com sucesso!");
    }
}

function removeServico(Servico) {
    const index = servicos_added.value.findIndex(f =>
        f.id_servico === Servico.id_servico
    );

    if (index !== -1) {
        servicos_added.value.splice(index, 1);

        totalServicos.value -= Number(Servico.valor);

        toast.success("Serviço removido com sucesso!");
    } else {
        toast.error("Serviço não encontrado!");
    }
}

$propsPage?.value?.ServicosNaOS.forEach(ServicoNaOS => {
        servicos_added.value.push({

            id_servico: ServicoNaOS.id,

            nome: ServicoNaOS.nome,

            valor: ServicoNaOS.valor

        });

        totalServicos.value += Number(ServicoNaOS.valor);

});

const pecas_fornecedor = $propsPage?.value?.FornecedorPeca?.map((val) => {
    return { name: `${val.peca.nome} - ${val.fornecedor.nome} - R$ ${val.preco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`, value: val.id, preco: val.preco }
});

const getFornecedorPeca = (id) => {
    const tipo = pecas_fornecedor.find(t => t.value == id);
    return tipo ? tipo.name : 'Desconhecido';
};

const getPrecoFornecedorPeca = (id) => {
    const tipo = pecas_fornecedor.find(t => t.value == id);
    return tipo ? tipo.preco : 0;
};

const formFornecedorPeca = ref({
    id_fornecedor_peca: null
});

const fornecedor_pecas_added = ref([]);

$propsPage?.value?.OSFornecedorPecas.forEach(OSFornecedorPeca => {
        fornecedor_pecas_added.value.push({

            id_fornecedor_peca: OSFornecedorPeca.id_fornecedor_peca,

            nome: getFornecedorPeca(OSFornecedorPeca.id_fornecedor_peca)

        });

        let precoPeca = Number( getPrecoFornecedorPeca(OSFornecedorPeca.id_fornecedor_peca) )

        totalPecas.value += precoPeca;
});

function addFornecedorPeca() {

    if (formFornecedorPeca.value.id_fornecedor_peca) {

        const precoPeca = Number( getPrecoFornecedorPeca(formFornecedorPeca.value.id_fornecedor_peca.value) );

        fornecedor_pecas_added.value.push({

            id_fornecedor_peca: formFornecedorPeca.value.id_fornecedor_peca.value,

            nome: getFornecedorPeca(formFornecedorPeca.value.id_fornecedor_peca.value)

        });

        totalPecas.value += precoPeca;

        // Limpa os campos após adicionar
        formFornecedorPeca.value.id_fornecedor_peca = null;
        toast.success("Peça adicionada com sucesso!");
    }
}

function removeFornecedorPeca(FornecedorPeca) {

    const index = fornecedor_pecas_added.value.findIndex(f =>
        f.id_fornecedor_peca === FornecedorPeca.id_fornecedor_peca
    );

    if (index !== -1) {
        fornecedor_pecas_added.value.splice(index, 1);

        const precoPeca = Number( getPrecoFornecedorPeca(FornecedorPeca.id_fornecedor_peca) );
        totalPecas.value -= precoPeca;

        toast.success("Peça removida com sucesso!");
    } else {
        toast.error("Peça não encontrada!");
    }
}

const veiculos = $propsPage?.value?.Veiculos?.map((val) => {
    return { name: `${val.modelo} - ${val.proprietario.nome}`, value: val.id }
});

const situacao = ['Aberta', 'Fechada', 'Cancelada'];

const toast = useToast();

const sendable = ref(false);

const currentNav = ref(1);

const statusOption = [
    { name: "Ativo", value: "0" },
    { name: "Inativo", value: "1" },
];

const submited = ref(false);

const form = useForm({
    token: $propsPage?.value.ConfigOrdemServico?.token,

    id_veiculo: { value: $propsPage?.value.ConfigOrdemServico?.id_veiculo },

    descricao: $propsPage?.value.ConfigOrdemServico?.descricao,

    fornecedor_pecas: fornecedor_pecas_added,

    servicos: servicos_added,

    situacao: $propsPage?.value.ConfigOrdemServico?.situacao,

    status: { value: $propsPage?.value.ConfigOrdemServico?.status },

    created_at: $propsPage?.value.ConfigOrdemServico?.created_at,
});

function getFormFiltered() {
    const newForm = {};
    for (let [key, value] of Object.entries(form)) {
        if (typeof value == "object" && value?.value) {
            newForm[key] = value.value;
        } else {
            newForm[key] = value;
        }
    }
    return newForm;
}

function validateForm() {
    if (typeof form.status !== "undefined") {
        if (!form.status) {
            throw new Error(toast.error("O campo Stauts não pode ser enviado vazio."));
        }
    }
}

function submit() {
    validateForm();
    submited.value = true;
    const submitForm = getFormFiltered();

    submitForm.post(
        route("update.ConfigOrdemServico", { id: $propsPage.value?.ConfigOrdemServico?.token }),
        {
            preserveState: true,
            onError: (errors) => {
                if (Array.isArray(errors)) {
                    errors.forEach((error) => {
                        toast.error(error);
                    });
                } else {
                    toast.error(errors.msg);
                }
            },
            onSuccess: () => {
                toast.success("Salvo com sucesso!");
            },
            onFinish: () => (submited.value = false),
        }
    );
}

function attachAvatar(e) {
    form.anexo = e.target.files[0];
}
</script>

<style scoped>
.file-input {
    display: inline-block;
    text-align: left;
    background: #fff;
    width: 100%;
    position: relative;
    border-radius: 3px;
}

.file-input>[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    z-index: 10;
    cursor: pointer;
}

.file-input>.button {
    display: inline-block;
    cursor: pointer;
    background: #eee;
    padding: 8px 16px;
    border-radius: 2px;
    margin-right: 8px;
}

.file-input:hover>.button {
    background: rgb(25, 25, 112);
    color: white;
    border-radius: 6px;
    transition: all 0.2s;
}

.file-input>.label {
    color: #333;
    white-space: nowrap;
    opacity: 0.3;
}

.file-input.-chosen>.label {
    opacity: 1;
}
</style>
