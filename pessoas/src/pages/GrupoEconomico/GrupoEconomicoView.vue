<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import { GrupoEconomicoStore } from "src/stores/GrupoEconomico";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";
import MGLayout from "layouts/MGLayout.vue";
import CardPessoas from "components/pessoa/CardPessoas.vue";
import GraficoNegocios from "components/grupoEconomico/GraficoNegocios.vue";
import GraficoTop10Produtos from "components/grupoEconomico/GraficoTop10Produtos.vue";
import TabelaTotaisNegocios from "components/grupoEconomico/TabelaTotaisNegocios.vue";
import TabelaTitulosAbertos from "components/grupoEconomico/TabelaTitulosAbertos.vue";
import TabelaNfeTerceiro from "components/grupoEconomico/TabelaNfeTerceiro.vue";

const $q = useQuasar();
const route = useRoute();
const router = useRouter();
const sGrupoEconomico = GrupoEconomicoStore();
const user = guardaToken();

const grupoEconomico = ref({});
const pessoasGrupo = ref([]);
const pessoasOrdenadas = computed(() =>
  [...pessoasGrupo.value].sort((a, b) =>
    (a.fantasia || "").localeCompare(b.fantasia || "")
  )
);

const dialog = ref(false);
const model = ref({});

const editar = () => {
  dialog.value = true;
  model.value = {
    grupoeconomico: grupoEconomico.value.grupoeconomico,
    observacoes: grupoEconomico.value.observacoes,
  };
};

const salvar = async () => {
  try {
    const ret = await sGrupoEconomico.salvarGrupoEconomico(
      route.params.id,
      model.value
    );
    if (ret.data.data) {
      grupoEconomico.value = ret.data.data;
      dialog.value = false;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Alterado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || error.message,
    });
  }
};

const excluir = () => {
  $q.dialog({
    title: "Excluir Grupo",
    message: "Tem certeza que deseja excluir esse grupo econômico?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sGrupoEconomico.excluirGrupoEconomico(route.params.id);
      if (ret) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Grupo removido!",
        });
        router.push("/grupoeconomico");
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.message || error.message,
      });
    }
  });
};

const inativar = async () => {
  try {
    const ret = await sGrupoEconomico.inativarGrupo(route.params.id);
    if (ret.data) {
      grupoEconomico.value = ret.data.data;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || error.message,
    });
  }
};

const ativar = async () => {
  try {
    const ret = await sGrupoEconomico.ativarGrupo(route.params.id);
    if (ret.data) {
      grupoEconomico.value = ret.data.data;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || error.message,
    });
  }
};

const carregarGrupo = async (id) => {
  try {
    const ret = await sGrupoEconomico.getGrupoEconomico(id);
    grupoEconomico.value = ret.data.data;
    pessoasGrupo.value = ret.data.data.PessoasdoGrupo;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response?.data?.message || error.message,
    });
  }
};

onMounted(() => {
  carregarGrupo(route.params.id);
});

watch(
  () => route.params.id,
  (novoId) => {
    if (novoId) carregarGrupo(novoId);
  }
);
</script>

<template>
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">Grupo Econômico</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        dense
        round
        :to="{ name: 'grupoeconomicoindex' }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div
          v-if="grupoEconomico.codgrupoeconomico"
          style="max-width: 1280px; margin: auto"
          class="q-pa-md"
        >
          <!-- HEADER -->
          <q-item class="q-pt-lg q-pb-sm">
            <q-item-section avatar>
              <q-avatar
                color="primary"
                text-color="white"
                size="80px"
                icon="groups"
              />
            </q-item-section>
            <q-item-section>
              <div
                class="text-h4 text-grey-9"
                :class="grupoEconomico.inativo ? 'text-strike text-red-14' : ''"
              >
                {{ grupoEconomico.grupoeconomico }}
                <icone-info-criacao
                  :usuariocriacao="grupoEconomico.usuariocriacao"
                  :criacao="grupoEconomico.criacao"
                  :usuarioalteracao="grupoEconomico.usuarioalteracao"
                  :alteracao="grupoEconomico.alteracao"
                />
              </div>
              <div
                v-if="grupoEconomico.observacoes"
                class="text-body1 text-grey-7"
                style="white-space: pre"
              >
                {{ grupoEconomico.observacoes }}
              </div>
              <div
                v-if="grupoEconomico.inativo"
                class="text-caption text-red-14"
              >
                Inativo desde: {{ formataData(grupoEconomico.inativo) }}
              </div>
            </q-item-section>
            <q-item-section
              side
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <div class="row">
                <q-btn
                  flat
                  round
                  dense
                  icon="edit"
                  color="grey-7"
                  @click="editar()"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  dense
                  icon="delete"
                  color="grey-7"
                  @click="excluir()"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="!grupoEconomico.inativo"
                  flat
                  round
                  dense
                  icon="pause"
                  color="grey-7"
                  @click="inativar()"
                >
                  <q-tooltip>Inativar</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="grupoEconomico.inativo"
                  flat
                  round
                  dense
                  icon="play_arrow"
                  color="grey-7"
                  @click="ativar()"
                >
                  <q-tooltip>Ativar</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>

          <!-- GRAFICO NEGOCIOS -->
          <div class="row q-py-md">
            <div class="col-12">
              <grafico-negocios />
            </div>
          </div>

          <div class="row q-col-gutter-md">
            <!-- PESSOAS DO GRUPO -->
            <div
              class="col-md-4 col-sm-6 col-xs-12"
              v-for="pessoa in pessoasOrdenadas"
              :key="pessoa.codpessoa"
            >
              <card-pessoas :listagempessoas="pessoa" />
            </div>
          </div>

          <div class="row q-col-gutter-md q-py-md">
            <!-- TABELA TOTAIS NEGOCIOS -->
            <div class="col-md-8 col-xs-12">
              <tabela-totais-negocios />
            </div>

            <!-- GRAFICO TOP 10 PRODUTOS -->
            <div class="col-md-4 col-xs-12">
              <grafico-top-10-produtos />
            </div>

            <!-- TITULOS ABERTOS -->
            <div class="col-12">
              <tabela-titulos-abertos
                :codgrupoeconomico="grupoEconomico.codgrupoeconomico"
              />
            </div>

            <!-- NFE TERCEIRO -->
            <div class="col-12">
              <tabela-nfe-terceiro />
            </div>
          </div>
        </div>
      </q-page>

      <!-- DIALOG EDITAR GRUPO ECONOMICO -->
      <q-dialog v-model="dialog">
        <q-card bordered flat style="width: 600px; max-width: 90vw">
          <q-form @submit="salvar()">
            <q-card-section class="text-grey-9 text-overline row items-center">
              EDITAR GRUPO ECONÔMICO
            </q-card-section>

            <q-separator inset />

            <q-card-section>
              <q-input
                outlined
                v-model="model.grupoeconomico"
                label="Grupo Econômico"
                class="q-mb-md"
              />
              <q-input
                outlined
                v-model="model.observacoes"
                label="Observações"
                type="textarea"
                autogrow
              />
            </q-card-section>

            <q-separator inset />

            <q-card-actions align="right" class="text-primary">
              <q-btn
                flat
                label="Cancelar"
                color="grey-8"
                v-close-popup
                tabindex="-1"
              />
              <q-btn flat label="Salvar" type="submit" />
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </template>
  </MGLayout>
</template>
