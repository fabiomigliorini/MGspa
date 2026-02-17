<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { metaStore } from "src/stores/meta";
import { guardaToken } from "src/stores";
import MGLayout from "layouts/MGLayout.vue";
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

const unidade = computed(() => {
  const codun = parseInt(route.params.codunidadenegocio);
  const cfgUnidades = config.value.unidades || [];
  const projUnidades = dash.value.unidades || [];

  const cfg = cfgUnidades.find((u) => u.codunidadenegocio === codun);
  if (!cfg) return null;

  const proj = projUnidades.find((p) => p.codunidadenegocio === codun) || {};
  return {
    ...cfg,
    totalvendas: proj.totalvendas || 0,
    percentualatingimento: proj.percentualatingimento || null,
    metaatingida: proj.metaatingida || false,
    rankingprovisorio: proj.rankingprovisorio || [],
  };
});

const extrairErro = (error, fallback) => {
  const data = error.response?.data;
  if (!data) return fallback;
  if (data.errors) {
    const primeiro = Object.values(data.errors).flat()[0];
    if (primeiro) return primeiro;
  }
  return data.mensagem || data.message || fallback;
};

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
  <MGLayout back-button>
    <template #tituloPagina>
      <span class="q-pl-sm">{{ unidade?.descricao || "Unidade" }}</span>
    </template>

    <template #botaoVoltar>
      <q-btn
        flat
        round
        :to="{
          name: 'metaDashboard',
          params: { codmeta: route.params.codmeta },
        }"
        icon="arrow_back"
        aria-label="Voltar"
      />
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1280px; margin: auto" class="q-pa-md">
          <q-inner-loading :showing="loading" />

          <template v-if="!loading && unidade">
            <CardUnidadeMeta
              :unidade="unidade"
              :codmeta="route.params.codmeta"
              :pode-editar="podeEditar"
              :periodoinicial="config.periodoinicial"
              :periodofinal="config.periodofinal"
            />
          </template>

          <div
            v-if="!loading && !unidade"
            class="text-center text-grey q-pa-xl"
          >
            Unidade nao encontrada nesta meta.
          </div>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
