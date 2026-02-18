<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import { useQuasar } from "quasar";
import { pessoaStore } from "stores/pessoa";
import { dependenteStore } from "stores/dependente";
import { guardaToken } from "src/stores";
import { formataDataSemHora } from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";
import SelectPessoa from "components/select/SelectPessoa.vue";

const $q = useQuasar();
const route = useRoute();
const sPessoa = pessoaStore();
const sDependente = dependenteStore();
const user = guardaToken();

const filtroResponsavel = ref("ativos");
const filtroDependenteDe = ref("ativos");

const dialogDependente = ref(false);
const isNovo = ref(true);
const modoAdicao = ref("responsavel"); // 'responsavel' ou 'dependente'

const modelInicial = {
  coddependente: null,
  codpessoaselecionada: null,
  tipdep: null,
  datainicio: null,
  datafim: null,
  motivofim: null,
  depirrf: false,
  depsfam: false,
  depplano: false,
  incsocfam: false,
  guardajudicial: false,
  pensaoalimenticia: false,
  pensaovalor: null,
  pensaopercentual: null,
  pensaobeneficiario: null,
  pensaocpfbeneficiario: null,
  pensaobanco: null,
  pensaoagencia: null,
  pensaoconta: null,
  observacao: null,
};

const model = ref({ ...modelInicial });

const tiposDependente = [
  { value: "01", label: "Cônjuge" },
  { value: "02", label: "Companheiro(a) com filho ou união >5 anos" },
  { value: "03", label: "Filho(a) ou enteado(a) até 21 anos" },
  { value: "04", label: "Filho(a) ou enteado(a) universitário até 24 anos" },
  {
    value: "06",
    label: "Irmão(ã), neto(a) ou bisneto(a) sob guarda até 21 anos",
  },
  {
    value: "07",
    label: "Irmão(ã), neto(a) ou bisneto(a) universitário até 24 anos",
  },
  { value: "09", label: "Pais, avós e bisavós" },
  { value: "10", label: "Menor pobre até 21 anos sob guarda judicial" },
  { value: "11", label: "Pessoa incapaz (tutor/curador)" },
  { value: "12", label: "Ex-cônjuge que recebe pensão alimentícia" },
  { value: "99", label: "Agregado/Outros" },
];

// Configuração dos cards para evitar duplicação
const cardsConfig = computed(() => [
  {
    key: "responsavel",
    titulo: "MEUS DEPENDENTES",
    filtro: filtroResponsavel,
    dados: filtroResponsavel.value === "ativos"
      ? (sPessoa.item?.DependenteResponsavelS || []).filter((x) => !x.inativo)
      : sPessoa.item?.DependenteResponsavelS || [],
    icon: "people",
    modo: "responsavel",
    tooltipAdd: "Adicionar dependente",
    emptyMessage: "Nenhum dependente cadastrado",
    getNome: (dep) => dep.dependente,
    getCodPessoaLink: (dep) => dep.codpessoa,
  },
  {
    key: "dependente",
    titulo: "SOU DEPENDENTE DE",
    filtro: filtroDependenteDe,
    dados: filtroDependenteDe.value === "ativos"
      ? (sPessoa.item?.DependenteS || []).filter((x) => !x.inativo)
      : sPessoa.item?.DependenteS || [],
    icon: "supervisor_account",
    modo: "dependente",
    tooltipAdd: "Adicionar responsável",
    emptyMessage: "Não é dependente de ninguém",
    getNome: (dep) => dep.responsavel,
    getCodPessoaLink: (dep) => dep.codpessoaresponsavel,
  },
]);

const labelPessoaSelecionada = computed(() => {
  if (modoAdicao.value === "responsavel") {
    return "Pessoa Dependente";
  }
  return "Pessoa Responsável";
});

const resetModel = () => {
  model.value = { ...modelInicial };
};

const abrirNovo = (modo) => {
  resetModel();
  isNovo.value = true;
  modoAdicao.value = modo;
  dialogDependente.value = true;
};

const editar = (dep, modo) => {
  isNovo.value = false;
  modoAdicao.value = modo;

  model.value = {
    coddependente: dep.coddependente,
    codpessoaselecionada:
      modo === "responsavel" ? dep.codpessoa : dep.codpessoaresponsavel,
    tipdep: dep.tipdep,
    datainicio: dep.datainicio,
    datafim: dep.datafim,
    motivofim: dep.motivofim,
    depirrf: dep.depirrf || false,
    depsfam: dep.depsfam || false,
    depplano: dep.depplano || false,
    incsocfam: dep.incsocfam || false,
    guardajudicial: dep.guardajudicial || false,
    pensaoalimenticia: dep.pensaoalimenticia || false,
    pensaovalor: dep.pensaovalor,
    pensaopercentual: dep.pensaopercentual,
    pensaobeneficiario: dep.pensaobeneficiario,
    pensaocpfbeneficiario: dep.pensaocpfbeneficiario,
    pensaobanco: dep.pensaobanco,
    pensaoagencia: dep.pensaoagencia,
    pensaoconta: dep.pensaoconta,
    observacao: dep.observacao,
  };

  dialogDependente.value = true;
};

const submit = async () => {
  const codpessoaAtual = route.params.id;

  // Monta o payload conforme o modo
  let payload = {
    tipdep: model.value.tipdep,
    datainicio: model.value.datainicio,
    datafim: model.value.datafim,
    motivofim: model.value.motivofim,
    depirrf: model.value.depirrf,
    depsfam: model.value.depsfam,
    depplano: model.value.depplano,
    incsocfam: model.value.incsocfam,
    guardajudicial: model.value.guardajudicial,
    pensaoalimenticia: model.value.pensaoalimenticia,
    pensaovalor: model.value.pensaovalor,
    pensaopercentual: model.value.pensaopercentual,
    pensaobeneficiario: model.value.pensaobeneficiario,
    pensaocpfbeneficiario: model.value.pensaocpfbeneficiario,
    pensaobanco: model.value.pensaobanco,
    pensaoagencia: model.value.pensaoagencia,
    pensaoconta: model.value.pensaoconta,
    observacao: model.value.observacao,
  };

  if (isNovo.value) {
    if (modoAdicao.value === "responsavel") {
      payload.codpessoaresponsavel = parseInt(codpessoaAtual);
      payload.codpessoa = model.value.codpessoaselecionada;
    } else {
      payload.codpessoa = parseInt(codpessoaAtual);
      payload.codpessoaresponsavel = model.value.codpessoaselecionada;
    }
  }

  try {
    if (isNovo.value) {
      await sDependente.criar(payload);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Dependente cadastrado com sucesso",
      });
    } else {
      await sDependente.alterar(model.value.coddependente, payload);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Dependente alterado com sucesso",
      });
    }

    dialogDependente.value = false;
    await sPessoa.get(codpessoaAtual);
  } catch (error) {
    const errors = error.response?.data?.errors;
    if (errors) {
      Object.values(errors).forEach((mensagens) => {
        mensagens.forEach((msg) => {
          $q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: msg,
            timeout: 5000,
          });
        });
      });
    } else {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.message || "Erro ao salvar",
      });
    }
  }
};

const excluir = (coddependente) => {
  $q.dialog({
    title: "Excluir Dependente",
    message: "Tem certeza que deseja excluir este registro?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await sDependente.excluir(coddependente);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Excluído com sucesso",
      });
      await sPessoa.get(route.params.id);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.message || "Erro ao excluir",
      });
    }
  });
};

const inativar = async (coddependente) => {
  try {
    await sDependente.inativar(coddependente);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Inativado com sucesso",
    });
    await sPessoa.get(route.params.id);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || "Erro ao inativar",
    });
  }
};

const ativar = async (coddependente) => {
  try {
    await sDependente.ativar(coddependente);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Ativado com sucesso",
    });
    await sPessoa.get(route.params.id);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || "Erro ao ativar",
    });
  }
};
</script>

<template>
  <!-- DIALOG NOVO/EDITAR DEPENDENTE -->
  <q-dialog v-model="dialogDependente" persistent>
    <q-card bordered flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        <template v-if="isNovo">NOVO DEPENDENTE</template>
        <template v-else>EDITAR DEPENDENTE</template>
      </q-card-section>

      <q-form @submit="submit()">
        <q-separator inset />

        <q-card-section class="q-pt-none scroll" style="max-height: 60vh">
          <select-pessoa
            v-model="model.codpessoaselecionada"
            outlined
            :label="labelPessoaSelecionada"
            :rules="[(val) => !!val || 'Selecione uma pessoa']"
          />

          <q-select
            v-model="model.tipdep"
            :options="tiposDependente"
            outlined
            emit-value
            map-options
            label="Tipo de Dependente"
            :rules="[(val) => !!val || 'Selecione o tipo']"
          />

          <div class="row q-col-gutter-md">
            <div class="col-6">
              <q-input
                v-model="model.datainicio"
                outlined
                type="date"
                label="Data Início"
                input-class="text-center"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="model.datafim"
                outlined
                type="date"
                label="Data Fim"
                input-class="text-center"
              />
            </div>
          </div>

          <q-input
            v-if="model.datafim"
            v-model="model.motivofim"
            outlined
            label="Motivo do Fim"
            maxlength="50"
          />

          <div class="row q-py-md">
            <div class="col-6">
              <q-toggle v-model="model.depirrf" label="Dependente IRRF" />
            </div>
            <div class="col-6">
              <q-toggle v-model="model.depsfam" label="Salário-Família" />
            </div>
            <div class="col-6">
              <q-toggle v-model="model.depplano" label="Plano de Saúde" />
            </div>
            <div class="col-6">
              <q-toggle
                v-model="model.incsocfam"
                label="Incapaz para trabalho"
              />
            </div>
            <div class="col-6">
              <q-toggle
                v-model="model.guardajudicial"
                label="Guarda Judicial"
              />
            </div>
            <div class="col-6">
              <q-toggle
                v-model="model.pensaoalimenticia"
                label="Pensão em folha"
              />
            </div>
          </div>

          <!-- Seção: Pensão Alimentícia -->
          <q-slide-transition>
            <div
              v-if="model.pensaoalimenticia"
              class="row q-col-gutter-md q-mb-sm"
            >
              <div class="col-6">
                <q-input
                  v-model.number="model.pensaovalor"
                  min="0"
                  step="0.01"
                  outlined
                  type="number"
                  label="Valor Fixo "
                  prefix="R$"
                  input-class="text-right"
                  :disable="!!model.pensaopercentual"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model.number="model.pensaopercentual"
                  outlined
                  min="0"
                  step="0.01"
                  type="number"
                  label="Percentual"
                  suffix="%"
                  input-class="text-right"
                  :disable="!!model.pensaovalor"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="model.pensaobeneficiario"
                  outlined
                  label="Nome do Beneficiário"
                  maxlength="100"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="model.pensaocpfbeneficiario"
                  outlined
                  label="CPF do Beneficiário"
                  mask="###.###.###-##"
                  unmasked-value
                />
              </div>

              <div class="col-4">
                <q-input
                  v-model="model.pensaobanco"
                  outlined
                  label="Banco"
                  maxlength="3"
                />
              </div>
              <div class="col-4">
                <q-input
                  v-model="model.pensaoagencia"
                  outlined
                  label="Agência"
                  maxlength="10"
                />
              </div>
              <div class="col-4">
                <q-input
                  v-model="model.pensaoconta"
                  outlined
                  label="Conta"
                  maxlength="20"
                />
              </div>
            </div>
          </q-slide-transition>

          <!-- Seção: Observações -->
          <div class="text-subtitle2 q-mb-sm">Observações</div>
          <q-input
            v-model="model.observacao"
            outlined
            type="textarea"
            rows="2"
            maxlength="500"
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup color="grey-8" tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- CARDS DE DEPENDENTES (unificado) -->
  <q-card
    v-for="card in cardsConfig"
    :key="card.key"
    bordered
    flat
    :class="{ 'q-mb-md': card.key === 'responsavel' }"
  >
    <q-card-section class="text-grey-9 text-overline row items-center">
      {{ card.titulo }}
      <q-space />
      <q-btn-toggle
        v-model="card.filtro.value"
        color="grey-3"
        toggle-color="primary"
        text-color="grey-7"
        toggle-text-color="grey-3"
        unelevated
        dense
        no-caps
        size="sm"
        :options="[
          { label: 'Ativos', value: 'ativos' },
          { label: 'Todos', value: 'todos' },
        ]"
      />
      <q-btn
        v-if="user.verificaPermissaoUsuario('Publico')"
        flat
        round
        dense
        icon="add"
        size="sm"
        color="primary"
        @click="abrirNovo(card.modo)"
      >
        <q-tooltip>{{ card.tooltipAdd }}</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-list v-if="card.dados && card.dados.length > 0">
      <template v-for="dep in card.dados" :key="dep.coddependente">
        <q-separator inset />
        <q-item>
          <q-item-section avatar>
            <q-btn
              round
              flat
              :icon="card.icon"
              color="primary"
              :to="{
                name: 'pessoaView',
                params: { id: card.getCodPessoaLink(dep) },
              }"
            />
          </q-item-section>

          <q-item-section>
            <q-item-label :class="dep.inativo ? 'text-strike' : null">
              {{ card.getNome(dep) }}
              <q-badge v-if="dep.inativo" color="red" class="q-ml-sm">
                Inativo
              </q-badge>

              <!-- INFO -->
              <icone-info-criacao
                :usuariocriacao="dep.usuariocriacao"
                :criacao="dep.criacao"
                :usuarioalteracao="dep.usuarioalteracao"
                :alteracao="dep.alteracao"
              />
            </q-item-label>

            <q-item-label caption>
              {{ dep.tipdepdescricao }}
            </q-item-label>

            <q-item-label
              caption
              v-if="dep.datainicio || dep.datafim"
            >
              Início: {{ formataDataSemHora(dep.datainicio) }}
              <template v-if="dep.datafim">
                | Fim: {{ formataDataSemHora(dep.datafim) }}
              </template>
            </q-item-label>

            <q-item-label caption v-if="dep.depirrf || dep.depsfam || dep.depplano || dep.incsocfam || dep.guardajudicial || dep.pensaoalimenticia">
              <div class="row q-gutter-xs">
                <q-badge v-if="dep.depirrf" color="grey" outline>
                  Dependente IRRF
                </q-badge>
                <q-badge v-if="dep.depsfam" color="grey" outline>
                  Salário-Família
                </q-badge>
                <q-badge v-if="dep.depplano" color="grey" outline>
                  Plano de Saúde
                </q-badge>
                <q-badge v-if="dep.incsocfam" color="grey" outline>
                  Incapaz para trabalho
                </q-badge>
                <q-badge v-if="dep.guardajudicial" color="grey" outline>
                  Guarda Judicial
                </q-badge>
                <q-badge v-if="dep.pensaoalimenticia" color="grey" outline>
                  Pensão em folha
                </q-badge>
              </div>
            </q-item-label>

            <q-item-label
              caption
              style="white-space: pre-line"
              v-if="dep.observacao"
            >
              {{ dep.observacao }}
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <q-item-label
              caption
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <!-- EDITAR -->
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="sm"
                color="grey-7"
                @click="editar(dep, card.modo)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- INATIVAR -->
              <q-btn
                v-if="!dep.inativo"
                flat
                dense
                round
                icon="pause"
                size="sm"
                color="grey-7"
                @click="inativar(dep.coddependente)"
              >
                <q-tooltip>Inativar</q-tooltip>
              </q-btn>

              <!-- ATIVAR -->
              <q-btn
                v-if="dep.inativo"
                flat
                dense
                round
                icon="play_arrow"
                size="sm"
                color="grey-7"
                @click="ativar(dep.coddependente)"
              >
                <q-tooltip>Ativar</q-tooltip>
              </q-btn>

              <!-- EXCLUIR -->
              <q-btn
                flat
                dense
                round
                icon="delete"
                size="sm"
                color="grey-7"
                @click="excluir(dep.coddependente)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>

    <div v-else class="q-pa-md text-center text-grey">
      {{ card.emptyMessage }}
    </div>
  </q-card>
</template>

<style scoped></style>
