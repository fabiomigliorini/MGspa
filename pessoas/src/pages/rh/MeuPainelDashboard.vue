<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { rhStore } from "src/stores/rh";
import { formataDataSemHora, formataFromNow } from "src/utils/formatador";
import {
  formataMoeda,
  formataPercentual,
  corProgresso,
  tipoIndicadorLabel,
  tipoIndicadorColor,
  extrairErro,
} from "src/utils/rhFormatters";
import CardIndicadores from "src/components/rh/CardIndicadores.vue";
import CardRubricas from "src/components/rh/CardRubricas.vue";
import CardSetores from "src/components/rh/CardSetores.vue";

const $q = useQuasar();
const route = useRoute();
const sRh = rhStore();

const loading = ref(false);
const dados = ref(null);

const colaborador = computed(() => dados.value?.colaborador || null);
const gestor = computed(() => dados.value?.gestor || false);
const equipe = computed(() => dados.value?.equipe || []);

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

// --- EQUIPE AGRUPADA POR SETOR ---

const equipeAgrupada = computed(() => {
  const setorMap = new Map();

  equipe.value.forEach((pc) => {
    const pcSetores = pc.periodo_colaborador_setor_s || [];
    pcSetores.forEach((pcs) => {
      const key = pcs.codsetor;
      if (!setorMap.has(key)) {
        setorMap.set(key, {
          codsetor: key,
          setor: pcs.setor?.setor || "—",
          unidade: pcs.setor?.unidade_negocio?.descricao || "",
          colaboradores: [],
        });
      }
      // Evita duplicação
      const grupo = setorMap.get(key);
      if (!grupo.colaboradores.find((c) => c.codperiodocolaborador === pc.codperiodocolaborador)) {
        grupo.colaboradores.push(pc);
      }
    });
  });

  const grupos = Array.from(setorMap.values());
  const vendasColaborador = (pc) => {
    const inds = indicadoresLinha(pc);
    if (!inds[0]) return 0;
    return Math.max(0, ...inds.map((i) => parseFloat(i.valoracumulado) || 0));
  };
  grupos.forEach((g) => {
    g.colaboradores.sort((a, b) => vendasColaborador(b) - vendasColaborador(a));
  });
  return grupos;
});

const nomeColaborador = (pc) => pc.colaborador?.pessoa?.fantasia || "—";

const indicadoresLinha = (pc) => {
  const inds = pc?.indicadores || [];
  const rubricas = pc?.colaborador_rubrica_s || [];
  const codsComRubrica = new Set(
    rubricas.filter((r) => r.codindicador).map((r) => r.codindicador)
  );
  const filtrados = inds.filter((ind) => codsComRubrica.has(ind.codindicador));
  return filtrados.length > 0 ? filtrados : [null];
};

const vendasInd = (ind) => (ind ? parseFloat(ind.valoracumulado) || 0 : null);
const metaInd = (ind) => (ind ? parseFloat(ind.meta) || null : null);

const atingimentoInd = (ind) => {
  const vendas = vendasInd(ind);
  const meta = metaInd(ind);
  if (!vendas || !meta) return null;
  return (vendas / meta) * 100;
};

// --- LIFECYCLE ---

const carregar = async () => {
  loading.value = true;
  try {
    dados.value = await sRh.getMeuPainel(route.params.codperiodo);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: extrairErro(error, "Erro ao carregar painel"),
    });
  } finally {
    loading.value = false;
  }
};

onMounted(() => carregar());
watch(() => route.params.codperiodo, () => {
  if (route.params.codperiodo) carregar();
});
</script>

<template>
  <div style="max-width: 1280px; margin: auto">
    <q-inner-loading :showing="loading" />

    <template v-if="!loading && colaborador">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar color="primary" text-color="white" size="80px" icon="person" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">{{ nome }}</div>
          <div class="text-body2 text-grey-7" v-if="cargo">{{ cargo }}</div>
          <div class="text-caption text-grey" v-if="colaborador.colaborador?.contratacao">
            Contratação: {{ formataDataSemHora(colaborador.colaborador.contratacao) }}
            ({{ formataFromNow(colaborador.colaborador.contratacao) }})
          </div>
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

        <!-- EQUIPE DO SETOR (apenas gestor) -->
        <template v-if="gestor && equipeAgrupada.length > 0">
          <q-separator class="q-my-lg" />

          <div class="text-overline text-grey-9 q-mb-md">
            EQUIPE DO SETOR
          </div>

          <q-card
            bordered
            flat
            class="q-mb-md"
            v-for="grupo in equipeAgrupada"
            :key="grupo.codsetor"
          >
            <q-card-section class="text-overline text-grey-7 q-py-sm">
              {{ grupo.setor }}
              <span class="text-caption text-grey" v-if="grupo.unidade">
                — {{ grupo.unidade }}
              </span>
            </q-card-section>

            <q-markup-table flat separator="horizontal">
              <thead>
                <tr>
                  <th class="text-left">Nome</th>
                  <th class="text-center">Tipo</th>
                  <th class="text-right">Vendas</th>
                  <th class="text-right">Meta</th>
                  <th class="text-center">Ating.</th>
                  <th class="text-right">Total Var.</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <template
                  v-for="pc in grupo.colaboradores"
                  :key="pc.codperiodocolaborador"
                >
                  <tr
                    v-for="(ind, idx) in indicadoresLinha(pc)"
                    :key="(pc.codperiodocolaborador + '-' + (ind?.codindicador || 'none'))"
                  >
                    <td v-if="idx === 0" :rowspan="indicadoresLinha(pc).length" class="text-weight-medium">
                      <router-link
                        :to="{ name: 'rhMeuPainelColaborador', params: { codperiodo: route.params.codperiodo, codperiodocolaborador: pc.codperiodocolaborador } }"
                        class="text-primary"
                      >
                        {{ nomeColaborador(pc) }}
                      </router-link>
                    </td>
                    <td class="text-center">
                      <q-badge
                        v-if="ind"
                        :color="tipoIndicadorColor(ind.tipo)"
                        :label="tipoIndicadorLabel(ind.tipo)"
                      />
                      <span v-else class="text-grey">—</span>
                    </td>
                    <td class="text-right">
                      {{ vendasInd(ind) != null ? formataMoeda(vendasInd(ind)) : "—" }}
                    </td>
                    <td class="text-right">
                      {{ metaInd(ind) != null ? formataMoeda(metaInd(ind)) : "—" }}
                    </td>
                    <td class="text-center">
                      <span
                        v-if="atingimentoInd(ind) != null"
                        :class="'text-' + corProgresso(atingimentoInd(ind))"
                        class="text-weight-bold"
                      >
                        {{ formataPercentual(atingimentoInd(ind)) }}
                      </span>
                      <span v-else class="text-grey">—</span>
                    </td>
                    <td v-if="idx === 0" :rowspan="indicadoresLinha(pc).length" class="text-right text-weight-bold">
                      {{ formataMoeda(pc.valortotal) }}
                    </td>
                    <td v-if="idx === 0" :rowspan="indicadoresLinha(pc).length" class="text-center">
                      <q-badge
                        :color="pc.status === 'A' ? 'green' : 'blue'"
                        :label="pc.status === 'A' ? 'Aberto' : 'Encerrado'"
                      />
                    </td>
                  </tr>
                </template>
              </tbody>
            </q-markup-table>
          </q-card>
        </template>
      </div>
    </template>
  </div>
</template>
