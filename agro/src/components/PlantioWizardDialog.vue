<script setup>
import { ref, computed, watch } from 'vue'
import { PALETA_TALHAO, corTalhao } from 'src/utils/coresTalhao'
import MgInputValor from '@components/MgInputValor.vue'
import MapaTalhoes from 'components/MapaTalhoes.vue'

// Wizard de plantar talhão numa safra. Três passos:
//  1) escolher a fazenda (grid de cards com mini-mapa dos talhões base)
//  2) escolher o talhão base da fazenda (mapa clicável + lista)
//  3) confirmar/ajustar o polígono no mapa (já pré-preenchido do talhão base)
// Editar um plantio existente entra direto no passo 3.
const props = defineProps({
  modelValue: { type: Boolean, default: false }, // abertura do dialog
  cad: { type: Object, required: true }, // useCadastro do plantio (form/isNovo/salvar)
  fazendas: { type: Array, default: () => [] }, // todas as fazendas
  talhoesBase: { type: Array, default: () => [] }, // layout base de todas as fazendas
  variedades: { type: Array, default: () => [] }, // variedades da cultura desta safra
  plantios: { type: Array, default: () => [] }, // plantios já lançados na safra
})
const emit = defineEmits(['update:modelValue'])

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})
const form = computed(() => props.cad.form)
const passo = ref(1)
// O mapa emite a área recalculada já na montagem; ao abrir p/ editar, ignoramos
// essa 1ª emissão pra não apagar uma área plantada ajustada na mão.
let primeiraArea = true

// Expectativa de colheita: o usuário pensa em sc/ha (âncora); o total =
// sc/ha × área plantada e é o que vai pro banco (expectativasacas). Os dois
// campos são editáveis e sincronizados; se a área mudar, o total acompanha.
const expectativaha = ref(0)
const expectativaTotal = computed({
  get: () => (expectativaha.value || 0) * (Number(form.value.areaplantada) || 0),
  set: (v) => {
    const a = Number(form.value.areaplantada) || 0
    expectativaha.value = a > 0 ? (Number(v) || 0) / a : 0
  },
})
watch(expectativaTotal, (v) => {
  form.value.expectativasacas = Math.round((v || 0) * 100) / 100
})

// Só fazendas que têm algum talhão base desenhado (sem desenho do zero).
const fazendasComTalhoes = computed(() =>
  props.fazendas.filter((f) => talhoesDaFazenda(f.codfazenda).length),
)
function talhoesDaFazenda(codfazenda) {
  return props.talhoesBase.filter((t) => t.codfazenda === codfazenda && !t.inativo && t.geometria)
}
const talhoesDaFazendaSel = computed(() => talhoesDaFazenda(form.value.codfazenda))
const nomeFazenda = computed(
  () => props.fazendas.find((f) => f.codfazenda === form.value.codfazenda)?.fazenda || '',
)

// Talhões base já lançados nesta safra (badge "já lançado" — ainda clicáveis,
// pois um mesmo talhão pode ser subdividido em variedades diferentes).
const usados = computed(() => {
  const s = new Set()
  for (const p of props.plantios) if (p.codtalhao) s.add(p.codtalhao)
  return s
})

// No passo 3, os outros plantios da mesma fazenda em cinza (sem rótulo) p/ o
// usuário encolher o polígono na fatia livre e não sobrepor.
const cinza = computed(() =>
  props.plantios
    .filter(
      (p) =>
        p.codfazenda === form.value.codfazenda &&
        p.geometria &&
        p.codplantio !== form.value.codplantio,
    )
    .map((p) => ({ codfazenda: p.codfazenda, geometria: p.geometria })),
)

const podeSalvar = computed(
  () =>
    !!form.value.codfazenda &&
    !!form.value.talhao &&
    !!form.value.codvariedade &&
    Number(form.value.areaplantada) > 0,
)

// Ao abrir: novo plantio começa no passo 1 (ou 2 se já veio com fazenda),
// editar plantio existente vai direto pro mapa.
function onShow() {
  primeiraArea = true
  // Reconstrói a expectativa por hectare a partir do total salvo (área inalterada
  // entre sessões devolve o mesmo sc/ha).
  const a = Number(form.value.areaplantada) || 0
  expectativaha.value = a > 0 ? (Number(form.value.expectativasacas) || 0) / a : 0
  passo.value = props.cad.isNovo ? (form.value.codfazenda ? 2 : 1) : 3
}

function escolherFazenda(f) {
  form.value.codfazenda = f.codfazenda
  passo.value = 2
}

function escolherTalhao(codtalhao) {
  const t = props.talhoesBase.find((x) => x.codtalhao === codtalhao)
  if (!t) return
  // Parte do talhão base: traz polígono, nome, área, cor e centro como início.
  form.value.codtalhao = t.codtalhao
  form.value.talhao = t.talhao
  form.value.geometria = t.geometria || null
  form.value.areaplantada = Number(t.area) || 0
  form.value.cor = corTalhao(t)
  if (t.latitude) {
    form.value.latitude = t.latitude
    form.value.longitude = t.longitude
  }
  passo.value = 3
}

function onCentro(c) {
  form.value.latitude = c.lat
  form.value.longitude = c.lng
}

// Área do desenho alimenta a área plantada (campo único). Na 1ª emissão ao abrir
// p/ editar, preserva uma área plantada que tenha sido ajustada na mão.
function onArea(ha) {
  if (primeiraArea && !props.cad.isNovo) {
    primeiraArea = false
    return
  }
  primeiraArea = false
  form.value.areaplantada = ha
}

function salvar() {
  props.cad.salvar((f) => ({
    codfazenda: f.codfazenda,
    codtalhao: f.codtalhao,
    talhao: f.talhao,
    codvariedade: f.codvariedade,
    areaplantada: f.areaplantada,
    expectativasacas: f.expectativasacas,
    geometria: f.geometria,
    cor: f.cor,
    latitude: f.latitude,
    longitude: f.longitude,
  }))
}

// Remonta o mapa do passo 3 a cada talhão escolhido (recentraliza no polígono).
const mapaKey = computed(() => form.value.codplantio || `base-${form.value.codtalhao}` || 'novo')
</script>

<template>
  <q-dialog v-model="dialog" maximized @show="onShow">
    <q-card class="relative-position" style="width: 100vw; height: 100vh">
      <!-- PASSO 1 — escolher fazenda -->
      <div v-if="passo === 1" class="fit column no-wrap">
        <q-card-section class="row items-center no-wrap bg-grey-2">
          <q-btn flat round icon="close" color="grey-8" v-close-popup tabindex="-1" />
          <div class="text-h6 q-ml-sm">Em qual fazenda?</div>
        </q-card-section>
        <q-separator />
        <q-scroll-area class="col">
          <div class="q-pa-md row q-col-gutter-md">
            <div
              v-for="f in fazendasComTalhoes"
              :key="f.codfazenda"
              class="col-12 col-sm-6 col-md-4"
            >
              <q-card bordered flat class="cursor-pointer" @click="escolherFazenda(f)">
                <MapaTalhoes
                  :talhoes="talhoesDaFazenda(f.codfazenda)"
                  id-key="codtalhao"
                  height="200px"
                  estatico
                />
                <q-separator />
                <q-card-section class="row items-center no-wrap">
                  <q-icon name="agriculture" color="green-7" size="sm" class="q-mr-sm" />
                  <div class="text-subtitle1 col ellipsis">{{ f.fazenda }}</div>
                  <q-badge
                    color="grey-6"
                    :label="`${talhoesDaFazenda(f.codfazenda).length} talhões`"
                  />
                </q-card-section>
              </q-card>
            </div>
            <div v-if="!fazendasComTalhoes.length" class="col-12 text-grey-6 text-center q-pa-xl">
              Nenhuma fazenda com talhões base cadastrados. Cadastre o layout dos talhões na fazenda
              primeiro.
            </div>
          </div>
        </q-scroll-area>
      </div>

      <!-- PASSO 2 — escolher talhão base -->
      <div v-else-if="passo === 2" class="fit column no-wrap">
        <q-card-section class="row items-center no-wrap bg-grey-2">
          <q-btn flat round icon="arrow_back" color="grey-8" @click="passo = 1" />
          <div class="text-h6 q-ml-sm">
            Qual talhão?
            <span class="text-subtitle2 text-grey-7">· {{ nomeFazenda }}</span>
          </div>
        </q-card-section>
        <q-separator />
        <div class="col">
          <MapaTalhoes
            :key="`mapa2-${form.codfazenda}`"
            :talhoes="talhoesDaFazendaSel"
            id-key="codtalhao"
            height="100%"
            @select="escolherTalhao"
          />
        </div>
        <q-separator />
        <div class="q-pa-sm row q-gutter-sm" style="max-height: 28vh; overflow: auto">
          <q-btn
            v-for="t in talhoesDaFazendaSel"
            :key="t.codtalhao"
            outline
            no-caps
            color="grey-8"
            @click="escolherTalhao(t.codtalhao)"
          >
            <q-avatar size="20px" :style="{ backgroundColor: corTalhao(t) }" class="q-mr-sm" />
            {{ t.talhao }}
            <q-badge
              v-if="usados.has(t.codtalhao)"
              color="orange-6"
              label="já lançado"
              class="q-ml-sm"
            />
          </q-btn>
        </div>
      </div>

      <!-- PASSO 3 — confirmar/ajustar o polígono -->
      <template v-else>
        <MapaTalhoes
          :key="mapaKey"
          modo="editar"
          :geometria="form.geometria"
          :cor="form.cor"
          :outras="cinza"
          height="100%"
          :offset-inferior="210"
          @update:geometria="form.geometria = $event"
          @update:centro="onCentro"
          @update:area="onArea"
        />

        <!-- Bottom sheet: campos do plantio centralizados na base -->
        <div class="absolute-bottom q-pa-md q-mb-sm" style="z-index: 1000">
          <q-card flat bordered class="q-pa-sm" style="margin: 0 auto; max-width: 760px">
            <!-- Voltar pra escolha do talhão + fazenda -->
            <div v-if="cad.isNovo" class="row items-center q-mb-xs">
              <q-btn
                flat
                dense
                no-caps
                size="sm"
                color="grey-8"
                icon="arrow_back"
                label="Trocar talhão"
                @click="passo = 2"
              />
              <q-space />
              <div class="text-caption text-grey-6">{{ nomeFazenda }}</div>
            </div>
            <q-form @submit.prevent="salvar">
              <div class="row items-center no-wrap q-gutter-sm">
                <q-btn
                  round
                  :style="{ backgroundColor: form.cor }"
                  text-color="white"
                  icon="palette"
                >
                  <q-tooltip>Cor do talhão</q-tooltip>
                  <q-popup-proxy>
                    <q-color
                      v-model="form.cor"
                      :palette="PALETA_TALHAO"
                      default-view="palette"
                      no-header
                      no-footer
                    />
                  </q-popup-proxy>
                </q-btn>
                <q-input
                  v-model="form.talhao"
                  label="Talhão (nome / número)"
                  outlined
                  bg-color="white"
                  class="col"
                />
                <q-select
                  v-model="form.codvariedade"
                  :options="variedades"
                  option-value="codvariedade"
                  option-label="variedade"
                  emit-value
                  map-options
                  outlined
                  bg-color="white"
                  label="Variedade"
                  class="col"
                />
              </div>
              <div class="row items-center no-wrap q-gutter-sm q-mt-sm">
                <MgInputValor
                  v-model="form.areaplantada"
                  :decimals="2"
                  suffix="ha"
                  label="Área plantada"
                  bg-color="white"
                  class="col"
                />
                <MgInputValor
                  v-model="expectativaha"
                  :decimals="2"
                  suffix="sc/ha"
                  label="Expectativa"
                  bg-color="white"
                  class="col"
                />
                <MgInputValor
                  v-model="expectativaTotal"
                  :decimals="0"
                  suffix="sc"
                  label="Expectativa total"
                  bg-color="white"
                  class="col"
                />
                <q-btn
                  type="submit"
                  round
                  color="primary"
                  icon="save"
                  :disable="!podeSalvar"
                  :loading="cad.salvando"
                >
                  <q-tooltip>Salvar plantio</q-tooltip>
                </q-btn>
              </div>
            </q-form>
          </q-card>
        </div>

        <!-- FAB fechar (topo-direita) — passo 3 não tem barra de cabeçalho -->
        <q-btn
          fab
          round
          icon="close"
          color="white"
          text-color="grey-9"
          v-close-popup
          tabindex="-1"
          class="absolute"
          style="right: 16px; top: 16px; z-index: 1000"
        >
          <q-tooltip>Fechar</q-tooltip>
        </q-btn>
      </template>
    </q-card>
  </q-dialog>
</template>
