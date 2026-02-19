<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { metaStore } from "src/stores/meta";
import { guardaToken } from "src/stores";
import { formataDataSemHora } from "src/utils/formatador";
import MGLayout from "layouts/MGLayout.vue";
import SelectUnidadeNegocio from "src/components/select/SelectUnidadeNegocio.vue";
import CardUnidadeMeta from "src/components/meta/CardUnidadeMeta.vue";

const $q = useQuasar();
const route = useRoute();
const sMeta = metaStore();
const user = guardaToken();

const loading = ref(false);

const dash = computed(() => sMeta.dashboard || {});
const config = computed(() => sMeta.item || {});
const podeEditar = computed(
  () =>
    user.verificaPermissaoUsuario("Recursos Humanos") &&
    config.value.status !== "F"
);

const unidades = computed(() => {
  const cfgUnidades = config.value.unidades || [];
  const projUnidades = dash.value.unidades || [];
  return cfgUnidades.map((cfg) => {
    const proj =
      projUnidades.find(
        (p) => p.codunidadenegocio === cfg.codunidadenegocio
      ) || {};
    const totalvendas = proj.totalvendas || 0;
    const valormeta = parseFloat(cfg.valormeta) || 0;
    const percentualatingimento =
      valormeta > 0 ? (totalvendas / valormeta) * 100 : null;
    return {
      ...cfg,
      totalvendas,
      percentualatingimento,
      metaatingida: percentualatingimento >= 100,
      rankingprovisorio: proj.rankingprovisorio || [],
    };
  });
});

const totalVendas = computed(() =>
  (dash.value.unidades || []).reduce(
    (acc, u) => acc + (parseFloat(u.totalvendas) || 0),
    0
  )
);
const totalMeta = computed(() =>
  unidades.value.reduce(
    (acc, u) => acc + (parseFloat(u.valormeta) || 0),
    0
  )
);

const formataMoeda = (valor) => {
  return new Intl.NumberFormat("pt-BR", {
    style: "currency",
    currency: "BRL",
  }).format(parseFloat(valor) || 0);
};

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

// --- DIALOG ADD UNIDADE ---

const dialogAddUnidade = ref(false);
const modelAddUnidade = ref({});

const abrirAddUnidade = () => {
  modelAddUnidade.value = { codunidadenegocio: null };
  dialogAddUnidade.value = true;
};

const salvarAddUnidade = async () => {
  dialogAddUnidade.value = false;
  try {
    await sMeta.criarUnidade(route.params.codmeta, modelAddUnidade.value);
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Unidade adicionada",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao adicionar unidade"),
    });
  }
};

// --- LIFECYCLE ---

const carregar = async (codmeta) => {
  if (!codmeta) return;
  loading.value = true;
  try {
    await Promise.all([sMeta.getDashboard(codmeta), sMeta.get(codmeta)]);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar dashboard"),
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  carregar(route.params.codmeta);
});

watch(
  () => route.params.codmeta,
  (novoId) => {
    if (novoId) carregar(novoId);
  }
);
</script>

<template>
  <!-- DIALOG ADICIONAR UNIDADE -->
  <q-dialog v-model="dialogAddUnidade">
    <q-card bordered flat style="width: 400px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        ADICIONAR UNIDADE
      </q-card-section>

      <q-form @submit="salvarAddUnidade()">
        <q-separator inset />

        <q-card-section>
          <SelectUnidadeNegocio
            v-model="modelAddUnidade.codunidadenegocio"
            outlined
            label="Unidade de Negocio"
            :rules="[(v) => !!v || 'Obrigatorio']"
          />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn
            flat
            label="Cancelar"
            v-close-popup
            tabindex="-1"
            color="grey-8"
          />
          <q-btn flat label="Adicionar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">
        Dashboard da Meta
        <span
          v-if="config.periodoinicial"
          class="text-caption text-grey-6 q-ml-sm"
        >
          {{ formataDataSemHora(config.periodoinicial) }} a
          {{ formataDataSemHora(config.periodofinal) }}
        </span>
      </span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        round
        :to="{ name: 'metaIndex' }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1280px; margin: auto" class="q-pa-md">
          <q-inner-loading :showing="loading" />

          <template v-if="!loading">
            <!-- RESUMO GERAL -->
            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-xs-12 col-sm-6">
                <q-card bordered flat>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey">Total Vendas</div>
                    <div class="text-h5 text-grey-9">
                      {{ formataMoeda(totalVendas) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
              <div class="col-xs-12 col-sm-6">
                <q-card bordered flat>
                  <q-card-section class="text-center">
                    <div class="text-caption text-grey">Total Meta</div>
                    <div class="text-h5 text-grey-9">
                      {{ formataMoeda(totalMeta) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <!-- CARD POR UNIDADE -->
            <CardUnidadeMeta
              v-for="unidade in unidades"
              :key="unidade.codunidadenegocio"
              :unidade="unidade"
              :codmeta="route.params.codmeta"
              :pode-editar="podeEditar"
              :periodoinicial="config.periodoinicial"
              :periodofinal="config.periodofinal"
            />

            <!-- ADICIONAR UNIDADE -->
            <div class="text-center q-mt-md" v-if="podeEditar">
              <q-btn
                flat
                dense
                icon="add"
                label="Adicionar Unidade"
                color="primary"
                @click="abrirAddUnidade()"
              />
            </div>
          </template>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
