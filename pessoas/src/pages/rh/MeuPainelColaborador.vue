<script setup>
import { ref, computed, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute, useRouter } from "vue-router";
import { rhStore } from "src/stores/rh";
import { formataDataSemHora, formataFromNow } from "src/utils/formatador";
import { extrairErro } from "src/utils/rhFormatters";
import CardIndicadores from "src/components/rh/CardIndicadores.vue";
import CardRubricas from "src/components/rh/CardRubricas.vue";
import CardSetores from "src/components/rh/CardSetores.vue";

const $q = useQuasar();
const route = useRoute();
const router = useRouter();
const sRh = rhStore();

const loading = ref(false);
const colaborador = ref(null);

const nome = computed(
  () => colaborador.value?.colaborador?.pessoa?.fantasia || "—"
);
const cargo = computed(() => {
  const cargos = colaborador.value?.colaborador?.colaborador_cargo_s || [];
  return cargos.length > 0 ? cargos[0].cargo?.cargo : null;
});
const setores = computed(
  () => colaborador.value?.periodo_colaborador_setor_s || []
);
const rubricas = computed(() =>
  (colaborador.value?.colaborador_rubrica_s || [])
    .slice()
    .sort((a, b) => a.descricao.localeCompare(b.descricao, "pt-BR"))
);
const indicadores = computed(() => colaborador.value?.indicadores || []);

const carregar = async () => {
  loading.value = true;
  try {
    const ret = await sRh.getMeuPainelColaborador(
      route.params.codperiodo,
      route.params.codperiodocolaborador
    );
    colaborador.value = ret.colaborador;
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar colaborador"),
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => carregar());
</script>

<template>
  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && colaborador">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar color="grey-5" text-color="white" size="80px" icon="person" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">{{ nome }}</div>
          <div class="text-body2 text-grey-7" v-if="cargo">{{ cargo }}</div>
          <div class="text-caption text-grey" v-if="colaborador.colaborador?.contratacao">
            Contratação: {{ formataDataSemHora(colaborador.colaborador.contratacao) }}
            ({{ formataFromNow(colaborador.colaborador.contratacao) }})
          </div>
        </q-item-section>
        <q-item-section side>
          <q-btn
            flat
            dense
            round
            icon="arrow_back"
            color="grey-7"
            @click="router.back()"
          >
            <q-tooltip>Voltar</q-tooltip>
          </q-btn>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
        <div class="row q-col-gutter-md">
          <!-- COLUNA ESQUERDA -->
          <div class="col-xs-12 col-md-8">
            <CardRubricas
              :rubricas="rubricas"
              :valortotal="colaborador.valortotal"
              :status="colaborador.status"
              :codtitulo="colaborador.codtitulo"
              :podeEditar="false"
            />
          </div>

          <!-- COLUNA DIREITA -->
          <div class="col-xs-12 col-md-4">
            <CardSetores
              :setores="setores"
              :diasUteisPeriodo="0"
              :podeEditar="false"
              :status="colaborador.status"
            />

            <CardIndicadores
              :indicadores="indicadores"
              :rubricas="colaborador.colaborador_rubrica_s || []"
              :codperiodo="route.params.codperiodo"
              nomeRotaExtrato="rhMeuPainelExtrato"
              :podeEditar="false"
              :status="colaborador.status"
              :somenteComRubrica="true"
            />
          </div>
        </div>
      </div>
    </template>

    <div
      v-else-if="!loading && !colaborador"
      class="q-pa-xl text-center text-grey"
    >
      Colaborador não encontrado
    </div>
  </div>
</template>
