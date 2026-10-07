<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { api } from 'src/services/api'
import { useSafraStore } from 'src/stores/safra'
import { corTalhao } from 'src/utils/coresTalhao'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MapaTalhoes from 'components/MapaTalhoes.vue'
import PlantioWizardDialog from 'components/PlantioWizardDialog.vue'
import PlantioCargas from 'components/PlantioCargas.vue'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()
const codsafra = Number(route.params.codsafra)
const codplantio = Number(route.params.codplantio)

// "Centro" de um plantio: dados + mapa + ações + colhido. Domínio safra é dono do
// plantio (store por domínio); as médias vêm do rollup comercial da safra.
const store = useSafraStore()
const { plantio, plantios, comercial } = storeToRefs(store)

// Listas de referência p/ o wizard de edição (mesmas da SafraDetailPage).
const fazendas = ref([])
const variedades = ref([])
const talhoesBase = ref([])
const codcultura = computed(
  () => plantio.value?.Safra?.codcultura ?? plantio.value?.Safra?.Cultura?.codcultura,
)
// `cultura` minúsculo: relação aninhada sai em snake_case do Eloquent.
const pesosaca = computed(
  () =>
    Number(plantio.value?.Safra?.cultura?.pesosaca ?? plantio.value?.Safra?.Cultura?.pesosaca) ||
    60,
)
const variedadesDaCultura = computed(() =>
  variedades.value.filter((v) => v.codcultura === codcultura.value && !v.inativo),
)

// Adapter useCadastro p/ o PlantioWizardDialog (igual à SafraDetailPage); ao salvar,
// recarrega o plantio único + comercial (KPIs).
const plantioCad = reactive({
  get form() {
    return store.formPlantio
  },
  get isNovo() {
    return !store.formPlantio.codplantio
  },
  get salvando() {
    return store.salvandoPlantio
  },
  async salvar(transform) {
    if (transform) {
      const pk = store.formPlantio.codplantio
      store.formPlantio = { ...transform({ ...store.formPlantio }), codplantio: pk }
    }
    await store.salvarPlantio(codsafra)
    await store.carregarPlantio(codsafra, codplantio)
  },
})

// KPIs — área/previsão do plantio; médias esp/real e colhido(sc) prontos do backend.
const area = computed(() => Number(plantio.value?.areaplantada) || 0)
const expectativa = computed(() => Number(plantio.value?.expectativasacas) || 0)
const media = computed(() => comercial.value?.plantios?.[codplantio] || {})
const esperada = computed(
  () => media.value.esperada ?? (area.value > 0 ? expectativa.value / area.value : null),
)
const realizada = computed(() => media.value.realizada ?? null)
const colhidoSc = computed(() => Number(media.value.colhido) || 0)

const periodo = computed(() => {
  const s = plantio.value?.Safra
  if (!s || !s.anoplantio) return ''
  return s.anocolheita && s.anocolheita !== s.anoplantio
    ? `${s.anoplantio}/${s.anocolheita}`
    : `${s.anoplantio}`
})

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '—'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}

// ---- Talhão finalizado? — check que grava hacolhido = área (ou 0) ----
async function alternarFinalizado(valor) {
  await store.marcarFinalizado(codsafra, plantio.value, valor)
  await store.carregarPlantio(codsafra, codplantio)
}

// ---- Ações (cabeçalho) ----
function editarPlantio() {
  store.editarPlantio({ ...plantio.value, cor: corTalhao(plantio.value) })
}
async function alternarInativo() {
  await store.inativarPlantio(codsafra, plantio.value)
  await store.carregarPlantio(codsafra, codplantio)
}
function excluir() {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir o talhão ${plantio.value?.talhao || codplantio}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await store.removerPlantio(codsafra, codplantio)
      notifySuccess('Excluído!')
      router.push({ name: 'safra-detalhe', params: { codsafra } })
    } catch (e) {
      notifyError(e)
    }
  })
}

onMounted(async () => {
  const [, , { data: f }, { data: v }, { data: t }] = await Promise.all([
    store.carregarPlantio(codsafra, codplantio),
    store.carregarComercial(codsafra),
    api.get('v1/fazenda'),
    api.get('v1/variedade'),
    api.get('v1/talhao'),
  ])
  fazendas.value = f.data ?? f
  variedades.value = v.data ?? v
  talhoesBase.value = t.data ?? t
  await store.carregarPlantios(codsafra)
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <!-- Cabeçalho -->
      <q-card bordered flat class="q-mb-md">
        <q-card-section class="row items-center">
          <div class="col-12 col-sm row items-center no-wrap">
            <q-btn
              flat
              round
              size="sm"
              color="grey-7"
              icon="arrow_back"
              :to="{ name: 'safra-detalhe', params: { codsafra } }"
            />
            <q-avatar
              text-color="white"
              icon="place"
              class="q-ml-sm"
              :style="{ backgroundColor: corTalhao(plantio || {}) }"
            />
            <div class="col q-ml-md">
              <div class="text-h6">Talhão {{ plantio?.talhao || codplantio }}</div>
              <div class="text-caption text-grey-7">
                {{ plantio?.Variedade?.variedade || 'sem variedade' }}
                <span v-if="plantio?.Fazenda"> · {{ plantio.Fazenda.fazenda }}</span>
                <span v-if="plantio?.Safra"> · {{ plantio.Safra.safra }}</span>
                <span v-if="periodo"> · {{ periodo }}</span>
              </div>
            </div>
          </div>
          <div
            class="col-12 col-sm-auto row items-center justify-end no-wrap"
            :class="{ 'q-mt-sm': $q.screen.lt.sm }"
          >
            <MgInfoCriacao :registro="plantio" />
            <q-btn flat dense round size="sm" color="grey-7" icon="edit" @click="editarPlantio">
              <q-tooltip>Editar / desenhar</q-tooltip>
            </q-btn>
            <q-btn
              flat
              dense
              round
              size="sm"
              color="grey-7"
              :icon="plantio?.inativo ? 'play_arrow' : 'pause'"
              @click="alternarInativo"
            >
              <q-tooltip>{{ plantio?.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
            </q-btn>
            <q-btn flat dense round size="sm" color="grey-7" icon="delete" @click="excluir">
              <q-tooltip>Excluir</q-tooltip>
            </q-btn>
          </div>
        </q-card-section>
      </q-card>

      <template v-if="plantio">
        <!-- KPIs -->
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Área plantada</div>
                <div class="text-h6">{{ fmt(area, 1) }} ha</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Previsão</div>
                <div class="text-h6">{{ fmt(expectativa) }} sc</div>
                <div class="text-caption text-grey-6">{{ fmt(esperada, 1) }} sc/ha esperada</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Colhido</div>
                <div class="text-h6">{{ fmt(colhidoSc) }} sc</div>
                <div v-if="realizada != null" class="text-caption text-green-8">
                  {{ fmt(realizada, 1) }} sc/ha real
                </div>
                <div v-else class="text-caption text-grey-5">— sc/ha real</div>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-6 col-md-3">
            <q-card flat bordered class="full-height">
              <q-card-section>
                <div class="text-caption text-grey-7">Talhão finalizado?</div>
                <q-checkbox
                  :model-value="!!plantio.finalizado"
                  checked-icon="check_circle"
                  unchecked-icon="radio_button_unchecked"
                  color="green-6"
                  size="lg"
                  dense
                  label="Sim"
                  class="text-h6"
                  @update:model-value="alternarFinalizado"
                />
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Mapa do talhão -->
        <q-card bordered flat class="q-mb-md overflow-hidden">
          <q-item>
            <q-item-section avatar>
              <q-avatar color="blue-grey-1" text-color="blue-grey-8" icon="map" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-subtitle1">Mapa</q-item-label>
              <q-item-label caption>Polígono plantado deste talhão</q-item-label>
            </q-item-section>
          </q-item>
          <q-separator />
          <MapaTalhoes
            v-if="plantio.geometria"
            :talhoes="[plantio]"
            id-key="codplantio"
            height="360px"
            estatico
          />
          <q-card-section v-else>
            <MgEmptyState plain icon="map"
              >Este talhão ainda não tem polígono desenhado.</MgEmptyState
            >
          </q-card-section>
        </q-card>

        <!-- Cargas: o extrato do talhão, embutido no show do plantio -->
        <PlantioCargas :movimentos="plantio.MovimentoGraoS || []" :pesosaca="pesosaca" />
      </template>

      <q-inner-loading v-else showing />
    </div>

    <!-- Wizard de edição (mesmo da safra) -->
    <PlantioWizardDialog
      v-model="store.dialogPlantio"
      :cad="plantioCad"
      :safra="plantio?.Safra || {}"
      :fazendas="fazendas"
      :talhoes-base="talhoesBase"
      :variedades="variedadesDaCultura"
      :plantios="plantios"
    />
  </q-page>
</template>
