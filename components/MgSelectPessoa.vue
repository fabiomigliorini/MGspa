<script setup>
import { ref, onMounted, watch } from 'vue'
import { api } from 'src/services/api'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { formataCnpjCpf } from '@components/formatters'
import MgSelect from '@components/MgSelect.vue'

// ===== REFERÊNCIA do padrão REMOTE (entidade grande) =====
// Busca no backend (?busca=, debounce) com PAGINAÇÃO 20/20 via scroll infinito
// no dropdown; cacheia resultados por id; resolve o valor atual por v1/select/pessoa/{id}.
// Self-contained: cada app importa seu próprio `api` (baseURL = .../api/).
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  label: { type: String, default: 'Pessoa' },
  placeholder: { type: String, default: null },
  bottomSlots: { type: Boolean, default: true },
  customClass: { type: String, default: '' },
  disable: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  inativos: { type: Boolean, default: false },
  somenteVendedores: { type: Boolean, default: false },
  clearable: { type: Boolean, default: false },
  // Quando setado (>= 11 dígitos), busca automática e abre o popup.
  searchCnpj: { type: String, default: null },
  // Busca sem resultado: (busca, { erro }) => [{ acao, label, icon?, color? }].
  // Viram OPÇÕES da lista (as setas e o Enter chegam nelas, o que um slot não
  // permite); escolher uma emite `acao` (acao, busca, { erro }) em vez de mudar
  // o valor. `erro` = a busca falhou (sem rede) em vez de voltar vazia.
  acoesSemResultado: { type: Function, default: null },
})

const emit = defineEmits(['update:modelValue', 'clear', 'select', 'acao'])

const cache = useSelectCacheStore()
const ENTITY = 'pessoa'
const ENDPOINT = 'v1/select/pessoa'
const PER_PAGE = 20

const options = ref([])
const loading = ref(false)
const selectRef = ref(null)
const optionsFromCnpj = ref([])

// estado da paginação da busca corrente
let buscaAtual = ''
let pagina = 1
let temMais = false

function mapPessoa(item) {
  return {
    label: item.fantasia || item.pessoa,
    sublabel: item.pessoa !== item.fantasia ? item.pessoa : null,
    value: item.codpessoa,
    cnpj: item.cnpj,
    ie: item.ie,
    fisica: item.fisica,
    cidade: item.cidade,
    uf: item.sigla || item.uf,
    inativo: item.inativo,
    codgrupoeconomico: item.codgrupoeconomico,
    grupoeconomico: item.grupoeconomico,
  }
}

async function buscar(busca, page) {
  const { data } = await api.get(ENDPOINT, {
    params: {
      busca,
      page,
      inativos: props.inativos ? 1 : 0,
      somenteVendedores: props.somenteVendedores ? 1 : 0,
    },
  })
  const rows = (Array.isArray(data) ? data : data?.data || []).map(mapPessoa)
  cache.mergeById(ENTITY, rows)
  return rows
}

async function carregarPorId(codpessoa) {
  if (!codpessoa) return
  if (options.value.find((o) => o.value === codpessoa)) return
  const cached = cache.getById(ENTITY, codpessoa)
  if (cached) {
    options.value = [cached]
    return
  }
  try {
    const { data } = await api.get(`${ENDPOINT}/${codpessoa}`)
    const row = Array.isArray(data) ? data[0] : data?.data || data
    if (row && (row.codpessoa != null || row.value != null)) {
      const mapped = row.value != null ? row : mapPessoa(row)
      cache.mergeById(ENTITY, [mapped])
      options.value = [mapped]
    }
  } catch {
    // sem registro: deixa o select sem opção resolvida
  }
}

onMounted(() => {
  if (props.modelValue) carregarPorId(props.modelValue)
})

watch(
  () => props.modelValue,
  (newValue, oldValue) => {
    if (newValue && newValue !== oldValue && !options.value.find((o) => o.value === newValue)) {
      carregarPorId(newValue)
    }
  },
)

watch(
  () => props.searchCnpj,
  async (cnpj) => {
    if (!cnpj || cnpj.length < 11) return
    try {
      loading.value = true
      buscaAtual = cnpj
      pagina = 1
      const results = await buscar(cnpj, 1)
      temMais = results.length === PER_PAGE
      optionsFromCnpj.value = results
      options.value = results
      if (results.length > 0 && selectRef.value) {
        await new Promise((resolve) => setTimeout(resolve, 100))
        selectRef.value.focus()
        selectRef.value.showPopup()
      }
    } catch (error) {
      console.error('Erro ao buscar pessoa por CNPJ:', error)
      optionsFromCnpj.value = []
      options.value = []
    } finally {
      loading.value = false
    }
  },
)

// ===== Dropdown estável dentro de q-dialog =====
// Sendo o último campo de um dialog, o menu abre PRA CIMA. Ao rolar até o limite,
// o wheel "vaza" pro dialog atrás → o dialog rola → a âncora sobe → o position-engine
// do Quasar encolhe o menu (maxHeight→0) e ele "some". Conter o overscroll (preventDefault
// no limite, e sempre que a lista não rola) impede o dialog de se mexer e o menu fica firme.
// O menu é teleportado pra fora do componente, então acho o elemento pela classe no popup-show.
let popupEl = null
function onPopupWheel(e) {
  const el = e.currentTarget
  const rola = el.scrollHeight > el.clientHeight
  const noTopo = el.scrollTop <= 0
  const noFim = Math.ceil(el.scrollTop + el.clientHeight) >= el.scrollHeight
  if (!rola || (e.deltaY < 0 && noTopo) || (e.deltaY > 0 && noFim)) {
    e.preventDefault()
  }
}
const onPopupShow = () => {
  let tentativas = 0
  const procurar = () => {
    popupEl = document.querySelector('.mg-select-pessoa-popup')
    if (popupEl) popupEl.addEventListener('wheel', onPopupWheel, { passive: false })
    else if (tentativas++ < 5) requestAnimationFrame(procurar)
  }
  requestAnimationFrame(procurar)
}
const onPopupHide = () => {
  if (popupEl) {
    popupEl.removeEventListener('wheel', onPopupWheel)
    popupEl = null
  }
}

// Prefixo do value das opções de ação — nunca colide com codpessoa (número).
const PREFIXO_ACAO = '__acao:'
function opcoesDeAcao(busca, { erro = false } = {}) {
  return (props.acoesSemResultado?.(busca, { erro }) || []).map((a) => ({
    ...a,
    value: PREFIXO_ACAO + a.acao,
    busca,
    erro,
  }))
}

const filterPessoa = (val, update) => {
  if (optionsFromCnpj.value.length > 0 && (!val || val.trim().length < 2)) {
    update(() => {
      options.value = optionsFromCnpj.value
    })
    return
  }
  if (!val || val.length < 2) {
    update(() => {
      options.value = []
    })
    return
  }
  // Busca no backend FORA do update; só chama update() (síncrono) com o
  // resultado pronto — senão o Quasar fecha o ciclo de filtro com a lista vazia.
  buscaAtual = val
  pagina = 1
  loading.value = true
  buscar(val, 1)
    .then((rows) => {
      temMais = rows.length === PER_PAGE
      update(() => {
        options.value = rows.length ? rows : opcoesDeAcao(val.trim())
      })
    })
    .catch((error) => {
      console.error('Erro ao buscar pessoa:', error)
      update(() => {
        options.value = opcoesDeAcao(val.trim(), { erro: true })
      })
    })
    .finally(() => {
      loading.value = false
    })
}

// Scroll infinito: ao chegar perto do fim, pede a próxima página e dá append.
// Usa `index` (posição REAL do scroll), não `to` (fim do slice renderizado pelo
// virtual-scroll): com `to` a 1ª página já vinha "no fim" e disparava todas as
// páginas sozinha — em buscas curtas/genéricas (ex. "co") isso virava milhares
// de requisições em loop.
const onScroll = async ({ index }) => {
  if (!temMais || loading.value || !buscaAtual) return
  if (index < options.value.length - 4) return
  try {
    loading.value = true
    pagina += 1
    const rows = await buscar(buscaAtual, pagina)
    temMais = rows.length === PER_PAGE
    options.value = [...options.value, ...rows]
  } catch {
    temMais = false
  } finally {
    loading.value = false
  }
}

// Rótulo usado pelo Quasar pra ecoar a opção destacada DENTRO do campo (seta
// com `fill-input`). Nas ações ("Usar sem cadastro"/"Cadastrar") isso faria a
// frase inteira substituir o texto digitado enquanto o operador só está
// navegando — aqui devolve o texto ORIGINAL da busca, então passar por cima
// da ação não muda visualmente o campo. O rótulo bonito (ícone + frase) segue
// só no slot #option, que não usa isto.
function optionLabelFn(opt) {
  return opt?.acao ? opt.busca : opt?.label
}

const handleUpdate = (value) => {
  if (typeof value === 'string' && value.startsWith(PREFIXO_ACAO)) {
    const opcao = options.value.find((o) => o.value === value)
    if (opcao) emit('acao', opcao.acao, opcao.busca, { erro: opcao.erro })
    return
  }
  emit('update:modelValue', value)
  if (value === null) {
    emit('clear')
  } else {
    const pessoaSelecionada = options.value.find((o) => o.value === value)
    if (pessoaSelecionada) emit('select', pessoaSelecionada)
    optionsFromCnpj.value = []
  }
}
</script>

<template>
  <MgSelect
    ref="selectRef"
    :model-value="modelValue"
    @update:model-value="handleUpdate"
    :label="label"
    outlined
    :clearable="clearable"
    :options="options"
    option-value="value"
    :option-label="optionLabelFn"
    emit-value
    map-options
    use-input
    fill-input
    hide-selected
    input-debounce="500"
    @filter="filterPessoa"
    @virtual-scroll="onScroll"
    @popup-show="onPopupShow"
    @popup-hide="onPopupHide"
    popup-content-class="mg-select-pessoa-popup"
    :placeholder="placeholder"
    :bottom-slots="bottomSlots"
    :class="customClass"
    :disable="disable"
    :readonly="readonly"
    :loading="loading"
  >
    <template v-slot:option="scope">
      <q-item v-if="scope.opt.acao" v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon :name="scope.opt.icon || 'add'" :color="scope.opt.color || 'grey-7'" />
        </q-item-section>
        <q-item-section :class="scope.opt.color ? `text-${scope.opt.color}` : ''">
          {{ scope.opt.label }}
        </q-item-section>
      </q-item>
      <q-item v-else v-bind="scope.itemProps">
        <q-item-section avatar>
          <q-icon
            :name="scope.opt.fisica ? 'person' : 'business'"
            :color="scope.opt.fisica ? 'blue' : 'purple'"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label :class="scope.opt.inativo ? 'text-strike text-grey-6' : ''">
            {{ scope.opt.label }}
          </q-item-label>
          <q-item-label caption class="text-grey-7">
            {{ formataCnpjCpf(scope.opt.cnpj, scope.opt.fisica) }}
            <span v-if="scope.opt.ie"> | IE: {{ scope.opt.ie }} </span>
            <span v-if="scope.opt.cidade">
              | {{ scope.opt.cidade }}<span v-if="scope.opt.uf">/{{ scope.opt.uf }}</span>
            </span>
          </q-item-label>
          <q-item-label v-if="scope.opt.sublabel" caption class="text-grey-6">
            {{ scope.opt.sublabel }}
          </q-item-label>
        </q-item-section>
      </q-item>
    </template>

    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          {{ options.length === 0 ? 'Digite ao menos 2 caracteres' : 'Nenhum resultado' }}
        </q-item-section>
      </q-item>
    </template>

    <template v-if="$slots.prepend" v-slot:prepend>
      <slot name="prepend" />
    </template>

    <template v-if="$slots.append" v-slot:append>
      <slot name="append" />
    </template>
  </MgSelect>
</template>

<!-- não-scoped: o menu é teleportado pra fora do componente -->
<style>
.mg-select-pessoa-popup {
  /* !important vence o max-height inline que o position-engine escreve a cada reposição */
  max-height: 50vh !important;
  /* belt p/ touch: contém o scroll do dropdown sem vazar pro dialog atrás */
  overscroll-behavior: contain;
}
</style>
