<script setup>
import { ref } from "vue";
import { useQuasar } from "quasar";
import { useRouter } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { formataDocumetos } from "src/stores/formataDocumentos";
import { formataData, formataDataSemHora, formataCPF, formataCNPJ, formataPisPasep, formataTitulo, verificaIdade, verificaPassadoFuturo, dataFormatoSql, localeBrasil } from "src/utils/formatador";
import { guardaToken } from "stores/index";
import SelectGrupoEconomico from "components/pessoa/SelectGrupoEconomico.vue";
import SelectCidade from "components/pessoa/SelectCidade.vue";
import SelectEstado from "components/pessoa/SelectEstado.vue";
import InputIe from "components/pessoa/InputIe.vue";
import InputFiltered from "components/InputFiltered.vue";
import SelectPessoa from "components/select/SelectPessoa.vue";
import SelectEstadoCivil from "components/pessoa/SelectEstadoCivil.vue";
import SelectEtnia from "components/pessoa/SelectEtnia.vue";
import SelectGrauInstrucao from "components/pessoa/SelectGrauInstrucao.vue";

const $q = useQuasar();
const router = useRouter();
const sPessoa = pessoaStore();
const Documentos = formataDocumetos();
const user = guardaToken();

const DialogDetalhes = ref(false);
const DialogMercos = ref(false);
const modelPessoa = ref({});
const options = ref([]);
const mercosTransferir = ref({ mercosid: null, codpessoanova: null });


const inativar = async (codpessoa) => {
  try {
    const ret = await sPessoa.inativarPessoa(codpessoa);
    if (ret.data) {
      sPessoa.item = ret.data.data;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "negative",
      textColor: "white",
      icon: "error",
      message: error.response.data,
    });
  }
};

const ativar = async (codpessoa) => {
  try {
    const ret = await sPessoa.ativarPessoa(codpessoa);
    if (ret.data) {
      sPessoa.item = ret.data.data;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "negative",
      textColor: "white",
      icon: "error",
      message: error.response.data,
    });
  }
};

const removerPessoa = (codpessoa, pessoa) => {
  $q.dialog({
    title: "Excluir pessoa",
    message: "Tem certeza que deseja excluir " + pessoa + "?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sPessoa.removePessoa(codpessoa);
      if (ret.data.result === true) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Removido",
        });
        router.push("/pessoa");
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "warning",
        message: error.response.data.message,
      });
    }
  });
};

const editarDetalhes = async () => {
  DialogDetalhes.value = true;
  modelPessoa.value = {
    cnpj: sPessoa.item.fisica
      ? String(sPessoa.item.cnpj).padStart(11, "0")
      : String(sPessoa.item.cnpj).padStart(14, "0"),
    rntrc: sPessoa.item.rntrc,
    ie: sPessoa.item.ie,
    fantasia: sPessoa.item.fantasia,
    pessoa: sPessoa.item.pessoa,
    tipotransportador: sPessoa.item.tipotransportador,
    fisica: sPessoa.item.fisica,
    cliente: sPessoa.item.cliente,
    fornecedor: sPessoa.item.fornecedor,
    vendedor: sPessoa.item.vendedor,
    observacoes: sPessoa.item.observacoes,
    codgrupoeconomico: sPessoa.item.codgrupoeconomico,
    codcidade:
      sPessoa.item.PessoaEnderecoS?.find((item) => item.nfe === true)
        ?.codcidade ?? null,
    rg: sPessoa.item.rg,
    nascimento: sPessoa.item.nascimento
      ? formataDataSemHora(sPessoa.item.nascimento)
      : null,
    pai: sPessoa.item.pai,
    mae: sPessoa.item.mae,
    codcidadenascimento: sPessoa.item.codcidadenascimento,
    pispasep: sPessoa.item.pispasep,
    tituloeleitor: sPessoa.item.tituloeleitor,
    titulozona: sPessoa.item.titulozona,
    titulosecao: sPessoa.item.titulosecao,
    ctps: sPessoa.item.ctps,
    seriectps: sPessoa.item.seriectps,
    emissaoctps: sPessoa.item.emissaoctps
      ? formataDataSemHora(sPessoa.item.emissaoctps)
      : null,
    codestadoctps: sPessoa.item.codestadoctps,
    codestadocivil: sPessoa.item.codestadocivil,
    codetnia: sPessoa.item.codetnia,
    codgrauinstrucao: sPessoa.item.codgrauinstrucao,
  };
  const ret = await sPessoa.consultaCidade(sPessoa.item.codcidadenascimento);
  options.value = [ret.data[0]];
};

const abrirDialogMercos = () => {
  mercosTransferir.value.codpessoanova = null;
  mercosTransferir.value.mercosid =
    sPessoa.item.mercosId?.length > 0 ? sPessoa.item.mercosId[0] : null;
  DialogMercos.value = true;
};

const salvarMercos = (evt) => {
  if (evt) evt.preventDefault();
  $q.dialog({
    title: "Confirma",
    message: "Tem certeza que deseja confirmar a transferência do Mercos ID?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sPessoa.transferirMercosId(
        sPessoa.item.codpessoa,
        mercosTransferir.value.mercosid,
        mercosTransferir.value.codpessoanova
      );
      sPessoa.item = ret.data.data;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "MercosID Transferido",
      });
      DialogMercos.value = false;
    } catch (error) {
      console.log(error);
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao transferir MercosID!",
      });
    }
  });
};

const salvarDetalhes = async () => {
  const editar = { ...modelPessoa.value };
  if (editar.nascimento) {
    editar.nascimento = dataFormatoSql(editar.nascimento);
  }
  if (editar.emissaoctps) {
    editar.emissaoctps = dataFormatoSql(editar.emissaoctps);
  }
  try {
    const ret = await sPessoa.clienteSalvar(sPessoa.item.codpessoa, editar);
    sPessoa.item = ret.data.data;
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Alterado",
      });
      DialogDetalhes.value = false;
    }
  } catch (error) {
    if (error.response.data.errors?.cnpj) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.errors.cnpj,
      });
    } else if (error.response.data.errors?.ie) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.errors.ie,
      });
    } else {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  }
};
</script>

<template>
  <!-- DIALOG EDITAR DETALHES -->
  <q-dialog v-model="DialogDetalhes">
    <q-card bordered flat style="width: 800px; max-width: 80vw">
      <q-form @submit="salvarDetalhes()">
        <q-card-section class="text-grey-9 text-overline row">
          EDITAR DETALHES
        </q-card-section>

        <q-separator inset />

        <q-card-section class="row q-col-gutter-md">
          <q-input
            :class="
              modelPessoa.fisica
                ? 'col-md-3 col-sm-6 col-xs-12'
                : 'col-md-4 col-sm-6 col-xs-12'
            "
            outlined
            v-model="modelPessoa.cnpj"
            :label="modelPessoa.fisica ? 'CPF' : 'CNPJ'"
            :mask="modelPessoa.fisica ? '###.###.###-##' : '##.###.###/####-##'"
            unmasked-value
            disable
          />
          <input-ie
            :class="
              modelPessoa.fisica
                ? 'col-md-3 col-sm-6 col-xs-12'
                : 'col-md-4 col-sm-6 col-xs-12'
            "
            v-model="modelPessoa.ie"
            label="Inscrição Estadual"
            disable
          />
          <q-input
            :class="
              modelPessoa.fisica
                ? 'col-md-3 col-sm-6 col-xs-12'
                : 'col-md-4 col-sm-6 col-xs-12'
            "
            outlined
            v-model="modelPessoa.nascimento"
            mask="##/##/####"
            :label="modelPessoa.fisica ? 'Nascimento' : 'Fundação'"
          >
            <template v-slot:append>
              <q-icon name="event" class="cursor-pointer">
                <q-popup-proxy
                  cover
                  transition-show="scale"
                  transition-hide="scale"
                >
                  <q-date
                    v-model="modelPessoa.nascimento"
                    :locale="localeBrasil"
                    mask="DD/MM/YYYY"
                  >
                    <div class="row items-center justify-end">
                      <q-btn
                        v-close-popup
                        label="Fechar"
                        color="primary"
                        flat
                      />
                    </div>
                  </q-date>
                </q-popup-proxy>
              </q-icon>
            </template>
          </q-input>
          <q-input
            class="col-md-3 col-sm-6 col-xs-12"
            outlined
            v-model="modelPessoa.rg"
            v-if="modelPessoa.fisica == true"
            label="RG"
            unmasked-value
          />
          <input-filtered
            outlined
            v-model="modelPessoa.fantasia"
            label="Fantasia"
            :rules="[
              (val) => (val && val.length > 0) || 'Nome Fantasia é Obrigatório',
            ]"
            autofocus
            class="col-md-4 col-sm-6 col-xs-12"
          />
          <input-filtered
            outlined
            v-model="modelPessoa.pessoa"
            label="Razão Social"
            :rules="[
              (val) => (val && val.length > 0) || 'Razão Social é Obrigatório',
            ]"
            class="col-md-4 col-sm-6 col-xs-12"
          />
          <select-grupo-economico
            class="col-md-4 col-sm-6 col-xs-12"
            v-model="modelPessoa.codgrupoeconomico"
            label="Grupo Econômico"
            :permite-adicionar="true"
          />

          <template v-if="modelPessoa.fisica">
            <select-cidade
              class="col-md-4 col-sm-6 col-xs-12"
              v-model="modelPessoa.codcidadenascimento"
              :cidadeEditar="options"
              label="Nascido em"
            />
            <input-filtered
              class="col-md-4 col-sm-6 col-xs-12"
              outlined
              v-model="modelPessoa.pai"
              label="Nome do Pai"
            />
            <input-filtered
              class="col-md-4 col-sm-6 col-xs-12"
              outlined
              v-model="modelPessoa.mae"
              label="Nome da Mãe"
            />
            <q-input
              class="col-md-4 col-sm-6 col-xs-12"
              outlined
              v-model="modelPessoa.tituloeleitor"
              mask="####.####.####"
              label="Título de Eleitor"
              unmasked-value
            />
            <q-input
              class="col-md-2 col-sm-3 col-xs-6"
              outlined
              v-model="modelPessoa.titulozona"
              label="Zona"
              mask="###"
              unmasked-value
            />
            <q-input
              class="col-md-2 col-sm-3 col-xs-6"
              outlined
              v-model="modelPessoa.titulosecao"
              label="Seção"
              mask="####"
              unmasked-value
            />
            <q-input
              class="col-md-4 col-sm-4 col-xs-12"
              outlined
              v-model="modelPessoa.pispasep"
              label="PIS/PASEP"
              mask="###.#####.##-#"
              unmasked-value
            />
            <q-input
              class="col-md-4 col-sm-3 col-xs-12"
              outlined
              v-model="modelPessoa.ctps"
              label="CTPS"
              inputmode="numeric"
              mask="#######"
              unmasked-value
            />
            <q-input
              class="col-md-2 col-sm-2 col-xs-6"
              outlined
              v-model="modelPessoa.seriectps"
              label="Série"
              mask="####"
              inputmode="numeric"
              unmasked-value
            />
            <select-estado
              class="col-md-2 col-sm-3 col-xs-6"
              v-model="modelPessoa.codestadoctps"
              label="UF"
            />
            <q-input
              class="col-md-4 col-sm-6 col-xs-12"
              outlined
              v-model="modelPessoa.emissaoctps"
              mask="##/##/####"
              label="Emissão CTPS"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-date
                      v-model="modelPessoa.emissaoctps"
                      :locale="localeBrasil"
                      mask="DD/MM/YYYY"
                    >
                      <div class="row items-center justify-end">
                        <q-btn
                          v-close-popup
                          label="Fechar"
                          color="primary"
                          flat
                        />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>
            <select-estado-civil
              class="col-md-4 col-sm-6 col-xs-12"
              v-model="modelPessoa.codestadocivil"
            />
            <select-etnia
              class="col-md-4 col-sm-6 col-xs-12"
              v-model="modelPessoa.codetnia"
            />
            <select-grau-instrucao
              class="col-md-4 col-sm-6 col-xs-12"
              v-model="modelPessoa.codgrauinstrucao"
            />
          </template>

          <q-input
            class="col-md-4 col-sm-6 col-xs-12"
            outlined
            v-model="modelPessoa.rntrc"
            label="RNTRC"
            mask="#########"
            unmasked-value
          />
          <q-select
            class="col-md-8 col-sm-12 col-xs-12"
            outlined
            v-model="modelPessoa.tipotransportador"
            label="Tipo Transportador"
            :options="[
              { label: 'Nenhum', value: 0 },
              { label: 'ETC - Empresa', value: 1 },
              { label: 'TAC - Autônomo', value: 2 },
              { label: 'CTC - Cooperativa', value: 3 },
            ]"
            map-options
            emit-value
            clearable
          />
          <q-input
            outlined
            borderless
            autogrow
            v-model="modelPessoa.observacoes"
            label="Observações"
            type="textarea"
            class="col-12"
          />
          <div class="col-12">
            <q-toggle v-model="modelPessoa.cliente" label="Cliente" />
            &nbsp;
            <q-toggle v-model="modelPessoa.fornecedor" label="Fornecedor" />
            &nbsp;
            <q-toggle v-model="modelPessoa.vendedor" label="Vendedor" />
          </div>
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- DIALOG MERCOS -->
  <q-dialog v-model="DialogMercos">
    <q-card bordered flat style="width: 300px">
      <q-form @submit="salvarMercos">
        <q-card-section class="text-grey-9 text-overline row">
          TRANSFERIR MERCOS ID
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-select
            outlined
            v-model="mercosTransferir.mercosid"
            label="Mercos ID"
            :rules="[(val) => val > 1 || 'Obrigatório']"
            :options="sPessoa.item.mercosId"
          />
          <select-pessoa
            autofocus
            outlined
            v-model="mercosTransferir.codpessoanova"
            label="Transferir para Pessoa"
            somente-ativos
            :rules="[(val) => val > 1 || 'Obrigatório']"
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- CARD PRINCIPAL -->
  <q-card bordered flat>
    <q-card-section class="text-grey-9 text-overline row items-center">
      DETALHES DA PESSOA
      <q-space />
      <template v-if="user.verificaPermissaoUsuario('Publico')">
        <q-btn
          flat
          round
          dense
          icon="edit"
          size="sm"
          color="grey-7"
          @click="editarDetalhes()"
        >
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="delete"
          size="sm"
          color="grey-7"
          @click="removerPessoa(sPessoa.item.codpessoa, sPessoa.item.pessoa)"
        >
          <q-tooltip>Excluir</q-tooltip>
        </q-btn>
        <q-btn
          v-if="!sPessoa.item.inativo"
          flat
          round
          dense
          icon="pause"
          size="sm"
          color="grey-7"
          @click="inativar(sPessoa.item.codpessoa)"
        >
          <q-tooltip>Inativar</q-tooltip>
        </q-btn>
        <q-btn
          v-else
          flat
          round
          dense
          icon="play_arrow"
          size="sm"
          color="grey-7"
          @click="ativar(sPessoa.item.codpessoa)"
        >
          <q-tooltip>Ativar</q-tooltip>
        </q-btn>
        <q-btn flat round dense icon="info" size="sm" color="grey-7">
          <q-tooltip>
            <div>
              Criado por {{ sPessoa.item.usuariocriacao }} em
              {{ formataData(sPessoa.item.criacao) }}
            </div>
            <div>
              Alterado por {{ sPessoa.item.usuarioalteracao }} em
              {{ formataData(sPessoa.item.alteracao) }}
            </div>
          </q-tooltip>
        </q-btn>
      </template>
    </q-card-section>

    <!-- Info Grid -->
    <div class="row q-col-gutter-sm q-pa-md">
      <div class="col-6">
        <div class="text-overline text-grey-7">Codigo</div>
        <div class="text-body2">
          #{{ String(sPessoa.item.codpessoa).padStart(8, "0") }}
        </div>
      </div>

      <div class="col-6" v-for="mid in sPessoa.item.mercosId" :key="mid">
        <div class="text-overline text-grey-7">
          Mercos Id
          <q-btn
            dense
            flat
            icon="launch"
            size="xs"
            color="primary"
            :href="'https://app.mercos.com/354041/clientes/' + mid"
            target="_blank"
          >
            <q-tooltip>Ir para Mercos!</q-tooltip>
          </q-btn>
          <q-btn
            dense
            flat
            icon="manage_accounts"
            size="xs"
            color="primary"
            @click="abrirDialogMercos"
          >
            <q-tooltip>Transferir MercosID</q-tooltip>
          </q-btn>
        </div>
        <div class="text-body2">{{ mid }}</div>
      </div>

      <div class="col-6">
        <div class="text-overline text-grey-7">Documentos</div>
        <div class="text-body2">
          {{
            sPessoa.item.fisica
              ? formataCPF(sPessoa.item.cnpj)
              : formataCNPJ(sPessoa.item.cnpj)
          }}
          <span v-if="sPessoa.item.ie">
            / {{ Documentos.formataIePorSigla(sPessoa.item.ie) }}
          </span>
          <span v-if="sPessoa.item.rg"> / {{ sPessoa.item.rg }} </span>
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.nascimento">
        <div class="text-overline text-grey-7">Idade / Nascimento</div>
        <div class="text-body2">
          {{ verificaIdade(sPessoa.item.nascimento) }} anos
          <template v-if="sPessoa.item.codcidadenascimento">
            , nascido em {{ sPessoa.item.cidadenascimento }}/{{
              sPessoa.item.ufnascimento
            }}
          </template>
          , {{ formataDataSemHora(sPessoa.item.nascimento) }}
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.codgrupoeconomico">
        <div class="text-overline text-grey-7">Grupo Economico</div>
        <div class="text-body2">
          <router-link
            :to="'/grupoeconomico/' + sPessoa.item.codgrupoeconomico"
            class="text-primary"
          >
            {{ sPessoa.item.GrupoEconomico.grupoeconomico }}
          </router-link>
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.pai || sPessoa.item.mae">
        <div class="text-overline text-grey-7">Filiacao</div>
        <div class="text-body2">
          {{ sPessoa.item.pai }}
          <template v-if="sPessoa.item.pai && sPessoa.item.mae"> e </template>
          {{ sPessoa.item.mae }}
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.pispasep">
        <div class="text-overline text-grey-7">PIS/PASEP</div>
        <div class="text-body2">
          {{ formataPisPasep(sPessoa.item.pispasep) }}
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.tituloeleitor">
        <div class="text-overline text-grey-7">
          Titulo Eleitor / Zona / Secao
        </div>
        <div class="text-body2">
          {{ formataTitulo(sPessoa.item.tituloeleitor) }} /
          {{ sPessoa.item.titulozona }} / {{ sPessoa.item.titulosecao }}
        </div>
      </div>

      <div class="col-6" v-if="sPessoa.item.ctps">
        <div class="text-overline text-grey-7">CTPS</div>
        <div class="text-body2">
          {{ sPessoa.item.ctps }} / Serie {{ sPessoa.item.seriectps }} /
          {{ sPessoa.item.ufctpsS }} /
          {{ formataDataSemHora(sPessoa.item.emissaoctps) }}
        </div>
      </div>

      <div
        class="col-6"
        v-if="
          sPessoa.item.fisica &&
          (sPessoa.item.estadocivil ||
            sPessoa.item.etnia ||
            sPessoa.item.grauinstrucao)
        "
      >
        <div class="text-overline text-grey-7">
          Estado Civil / Etnia / Instrucao
        </div>
        <div class="text-body2">
          <template v-if="sPessoa.item.estadocivil">
            {{ sPessoa.item.estadocivil }}
          </template>
          <template v-if="sPessoa.item.etnia">
            {{ sPessoa.item.estadocivil ? " / " : "" }}{{ sPessoa.item.etnia }}
          </template>
          <template v-if="sPessoa.item.grauinstrucao">
            {{ sPessoa.item.estadocivil || sPessoa.item.etnia ? " / " : ""
            }}{{ sPessoa.item.grauinstrucao }}
          </template>
        </div>
      </div>

      <div
        class="col-6"
        v-if="sPessoa.item.rntrc || sPessoa.item.tipotransportador"
      >
        <div class="text-overline text-grey-7">RNTRC</div>
        <div class="text-body2">
          <template v-if="sPessoa.item.tipotransportador">
            <span v-if="sPessoa.item.tipotransportador == 1"
              >ETC - Empresa</span
            >
            <span v-if="sPessoa.item.tipotransportador == 2"
              >TAC - Autonomo</span
            >
            <span v-if="sPessoa.item.tipotransportador == 3"
              >CTC - Cooperativa</span
            >
            |
          </template>
          {{ sPessoa.item.rntrc }}
        </div>
      </div>

      <div class="col-12" v-if="sPessoa.item.observacoes">
        <div class="text-overline text-grey-7">Observacoes</div>
        <div
          class="text-body2 bg-grey-2 rounded-borders q-pa-sm"
          style="white-space: pre-line"
        >
          {{ sPessoa.item.observacoes }}
        </div>
      </div>
    </div>
  </q-card>
</template>

<style scoped></style>
