<script setup>
// Escolha de plantio (talhão de uma safra) NO MAPA, na sequência da operação:
// cultura/safra → fazenda → clique no talhão. Lê o cache offline da store de
// carga (safras, culturas, plantios com geometria), então funciona no pátio
// sem internet — só a imagem de satélite depende de rede.
//
// Emite `select` com o plantio (codplantio, codsafra, rotulo...) — quem usa
// decide o que fazer com a safra (a carga segue a safra do talhão escolhido).
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { corTalhao } from 'src/utils/coresTalhao'
import MapaTalhoes from 'components/MapaTalhoes.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  codplantio: { type: Number, default: null },
  codsafra: { type: Number, default: null },
})
const emit = defineEmits(['update:modelValue', 'select'])

const $q = useQuasar()
const store = useCargaStore()
const { safrasAtivas, culturas } = storeToRefs(store)

const show = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const safraSel = ref(null)
const fazendaSel = ref(null)

// Passo 1 — cultura/safra: uma opção por safra ativa, com o emoji da cultura.
const opcoesSafra = computed(() =>
  safrasAtivas.value.map((s) => {
    const cultura = culturas.value.find((c) => c.codcultura === s.codcultura)
    return {
      codsafra: s.codsafra,
      safra: s.safra,
      cultura: cultura?.cultura || 'Cultura',
      icone: cultura?.icone || null,
    }
  }),
)

const plantiosSafra = computed(() => store.plantiosPorSafra(safraSel.value))

// Passo 2 — fazendas que têm plantio nessa safra (derivadas do próprio plantio,
// que já vem com a relação Fazenda do servidor).
const fazendas = computed(() => {
  const mapa = new Map()
  for (const p of plantiosSafra.value) {
    if (!mapa.has(p.codfazenda)) {
      mapa.set(p.codfazenda, {
        codfazenda: p.codfazenda,
        fazenda: p.Fazenda?.fazenda || `Fazenda ${p.codfazenda}`,
        qtd: 0,
      })
    }
    mapa.get(p.codfazenda).qtd++
  }
  return [...mapa.values()].sort((a, b) => a.fazenda.localeCompare(b.fazenda))
})

// Passo 3 — talhões da fazenda no mapa; as demais fazendas ficam em cinza
// como contexto. Sem fazenda escolhida mostra a safra inteira.
const talhoesMapa = computed(() =>
  fazendaSel.value
    ? plantiosSafra.value.filter((p) => p.codfazenda === fazendaSel.value)
    : plantiosSafra.value,
)
const outrasFazendas = computed(() =>
  fazendaSel.value
    ? plantiosSafra.value
        .filter((p) => p.codfazenda !== fazendaSel.value && p.geometria)
        .map((p) => ({
          codfazenda: p.codfazenda,
          fazenda: p.Fazenda?.fazenda || null,
          geometria: p.geometria,
        }))
    : [],
)
const temGeometria = computed(() => talhoesMapa.value.some((p) => p.geometria))

// Apoio ao mapa: o mesmo conjunto de talhões num select com busca. Era uma
// lista de botões, que numa fazenda com dezenas de talhões (Renascer tem 40)
// virava uma parede colorida ilegível e comia a altura do mapa.
const talhaoFiltro = ref('')

const opcoesTalhao = computed(() => {
  const termo = talhaoFiltro.value.trim().toLowerCase()
  if (!termo) return talhoesMapa.value
  return talhoesMapa.value.filter((p) => (p.rotulo || '').toLowerCase().includes(termo))
})

// Display e cor saem do talhão atual, não das opções: com busca ativa a opção
// escolhida pode estar fora da lista filtrada e o campo ficaria em branco.
const talhaoAtual = computed(
  () => talhoesMapa.value.find((p) => p.codplantio === props.codplantio) || null,
)

function filtrarTalhoes(termo, update) {
  update(() => {
    talhaoFiltro.value = termo || ''
  })
}

// Ao abrir, parte do que a carga já tem (safra e talhão atuais); sem isso,
// primeira safra e a única fazenda (se só houver uma).
watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return
    const atual = props.codplantio ? store.plantioPorId(props.codplantio) : null
    safraSel.value =
      atual?.codsafra ||
      (opcoesSafra.value.some((s) => s.codsafra === props.codsafra) ? props.codsafra : null) ||
      opcoesSafra.value[0]?.codsafra ||
      null
    fazendaSel.value = atual?.codfazenda || null
    autoFazenda()
  },
)
watch(safraSel, () => {
  fazendaSel.value = null
  autoFazenda()
})
function autoFazenda() {
  if (!fazendaSel.value && fazendas.value.length === 1) {
    fazendaSel.value = fazendas.value[0].codfazenda
  }
}

function escolher(codplantio) {
  const p = plantiosSafra.value.find((x) => x.codplantio === codplantio)
  if (!p) return
  emit('select', p)
  show.value = false
}
</script>

<template>
  <q-dialog v-model="show" :maximized="$q.screen.lt.md">
    <q-card flat class="column no-wrap plantio-mapa-card">
      <q-card-section class="row items-center bg-primary text-white q-py-sm">
        <q-icon name="map" size="sm" class="q-mr-sm" />
        <div class="text-h6">Escolher talhão no mapa</div>
        <q-space />
        <q-btn flat round dense icon="close" v-close-popup tabindex="-1" />
      </q-card-section>

      <!-- Passo 1: cultura / safra -->
      <q-card-section class="q-py-sm">
        <div class="text-caption text-grey-7 q-mb-xs">1. Cultura / safra</div>
        <div class="row q-gutter-xs">
          <q-chip
            v-for="s in opcoesSafra"
            :key="s.codsafra"
            clickable
            :color="safraSel === s.codsafra ? 'primary' : 'grey-3'"
            :text-color="safraSel === s.codsafra ? 'white' : 'grey-9'"
            @click="safraSel = s.codsafra"
          >
            <span v-if="s.icone" class="q-mr-xs">{{ s.icone }}</span>
            <q-icon v-else name="grass" class="q-mr-xs" />
            {{ s.cultura }} · {{ s.safra }}
          </q-chip>
          <div v-if="!opcoesSafra.length" class="text-grey-6 q-pa-xs">
            Nenhuma safra ativa — sincronize.
          </div>
        </div>
      </q-card-section>

      <!-- Passo 2: fazenda -->
      <q-card-section class="q-py-sm">
        <div class="text-caption text-grey-7 q-mb-xs">2. Fazenda</div>
        <div class="row q-gutter-xs">
          <q-chip
            v-for="f in fazendas"
            :key="f.codfazenda"
            clickable
            icon="agriculture"
            :color="fazendaSel === f.codfazenda ? 'green-7' : 'grey-3'"
            :text-color="fazendaSel === f.codfazenda ? 'white' : 'grey-9'"
            @click="fazendaSel = fazendaSel === f.codfazenda ? null : f.codfazenda"
          >
            {{ f.fazenda }}
            <q-badge
              :color="fazendaSel === f.codfazenda ? 'white' : 'grey-6'"
              :text-color="fazendaSel === f.codfazenda ? 'green-8' : 'white'"
              class="q-ml-xs"
              :label="f.qtd"
            />
          </q-chip>
          <div v-if="safraSel && !fazendas.length" class="text-grey-6 q-pa-xs">
            Nenhum plantio cadastrado nesta safra.
          </div>
        </div>
      </q-card-section>

      <!-- Passo 3: talhão (mapa clicável + select de apoio) -->
      <div class="text-caption text-grey-7 q-px-md">
        3. Talhão — clique no polígono ou escolha abaixo
      </div>
      <div class="col relative-position q-ma-sm rounded-borders overflow-hidden bg-grey-3">
        <MapaTalhoes
          v-if="temGeometria"
          :key="`${safraSel}-${fazendaSel}`"
          :talhoes="talhoesMapa"
          :outras="outrasFazendas"
          :selecionado="codplantio"
          id-key="codplantio"
          height="100%"
          @select="escolher"
        />
        <div v-else class="absolute-center text-center text-grey-6">
          <q-icon name="map" size="48px" color="grey-5" />
          <div class="q-mt-sm">Nenhum talhão com polígono aqui. Escolha no campo abaixo.</div>
        </div>
      </div>

      <q-card-section class="q-pt-none q-pb-sm">
        <q-select
          outlined
          dense
          use-input
          input-debounce="0"
          label="Talhão"
          :model-value="codplantio"
          :display-value="talhaoAtual?.rotulo || ''"
          :options="opcoesTalhao"
          option-value="codplantio"
          option-label="rotulo"
          emit-value
          :disable="!talhoesMapa.length"
          @filter="filtrarTalhoes"
          @update:model-value="escolher"
          @popup-hide="talhaoFiltro = ''"
        >
          <template #prepend>
            <q-icon
              v-if="talhaoAtual"
              name="circle"
              size="xs"
              :style="{ color: corTalhao(talhaoAtual) }"
            />
            <q-icon v-else name="grass" size="xs" color="grey-6" />
          </template>

          <template #option="scope">
            <q-item v-bind="scope.itemProps">
              <q-item-section avatar>
                <q-icon name="circle" size="xs" :style="{ color: corTalhao(scope.opt) }" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ scope.opt.rotulo }}</q-item-label>
                <q-item-label v-if="!fazendaSel" caption>
                  {{ scope.opt.Fazenda?.fazenda }}
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>

          <template #no-option>
            <q-item>
              <q-item-section class="text-grey-6">Nenhum talhão encontrado.</q-item-section>
            </q-item>
          </template>
        </q-select>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.plantio-mapa-card {
  width: 960px;
  max-width: 96vw;
  height: 86vh;
}
</style>
