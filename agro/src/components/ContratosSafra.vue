<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { notifyError } from 'src/utils/notify'
import ContratoForm from 'components/ContratoForm.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

// Grid de contratos de UMA safra (encapsula tudo: lista + dialog novo/editar +
// inativar/excluir). O contrato sempre pertence à safra, então o vínculo
// (codsafra/codcultura) é forçado no salvar e os totais comerciais ficam no
// cabeçalho da SafraDetailPage. Online-only (contrato não é offline-first).
const props = defineProps({
  codsafra: { type: Number, required: true },
  codcultura: { type: Number, default: null },
  online: { type: Boolean, default: true },
})
const emit = defineEmits(['changed'])

const router = useRouter()
const cad = useCadastro('contrato', 'codcontrato', 'Contrato')
const contratos = ref([])
const carregando = ref(false)

const corTipo = { FIXO: 'green-7', FIXAR: 'orange-8', BARTER: 'deep-purple-6' }

function fmt(v, dec = 0) {
  if (v === null || v === undefined || v === '') return '0'
  return Number(v).toLocaleString('pt-BR', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec,
  })
}
// Progresso físico em kg (carregadokg / contratadokg). Sem limite = sem alvo.
function progresso(c) {
  if (c.volumeemaberto) return 0
  const q = Number(c.contratadokg) || 0
  return q > 0 ? Math.min(1, (Number(c.carregadokg) || 0) / q) : 0
}

async function recarregar() {
  if (!props.online) return
  carregando.value = true
  try {
    const { data } = await api.get('v1/contrato', { params: { codsafra: props.codsafra } })
    contratos.value = data.data ?? data
  } catch (e) {
    notifyError(e)
  } finally {
    carregando.value = false
  }
}

async function novo() {
  // Rascunho: tipo neutro (a fixar). O tipo real e o resto da configuração ficam
  // pra tela do contrato — o form de criação só pede a identificação.
  const hoje = new Date()
  const datacontrato = `${hoje.getFullYear()}-${String(hoje.getMonth() + 1).padStart(2, '0')}-${String(
    hoje.getDate(),
  ).padStart(2, '0')}`
  // Nº Nosso sugerido (CULTURA-AA/AA-NNNN) — editável. Best-effort: se falhar, o
  // backend numera no salvar quando o campo vier vazio.
  let contrato
  try {
    const { data } = await api.get(`v1/safra/${props.codsafra}/proximo-numero-contrato`)
    contrato = data.numero
  } catch {
    // sugestão é opcional; não bloqueia a criação
  }
  cad.abrirNovo({
    tipo: 'FIXAR',
    moeda: 'BRL',
    operacao: 'VENDA',
    comissaotipo: 'SACA',
    datacontrato,
    contrato,
  })
}
async function aposSalvar(saved) {
  // Contrato recém-criado → abre a tela dele pra terminar a configuração.
  if (saved?.codcontrato) {
    router.push({ name: 'contrato-detalhe', params: { codcontrato: saved.codcontrato } })
    return
  }
  await recarregar()
  emit('changed')
}

watch(
  () => props.online,
  (on) => {
    if (on && !contratos.value.length) recarregar()
  },
)

onMounted(() => {
  if (props.online) recarregar()
})
</script>

<template>
  <div>
    <div class="row items-center q-mb-sm">
      <div class="col text-subtitle1 text-weight-medium">Contratos de venda</div>
      <q-btn v-if="online" flat round size="sm" color="primary" icon="add" @click="novo">
        <q-tooltip>Novo contrato</q-tooltip>
      </q-btn>
    </div>

    <q-banner v-if="!online" rounded class="bg-grey-2 text-grey-7">
      <template #avatar><q-icon name="cloud_off" color="grey-6" /></template>
      Contratos indisponíveis offline.
    </q-banner>

    <div v-else-if="contratos.length" class="row q-col-gutter-md">
      <div v-for="c in contratos" :key="c.codcontrato" class="col-12 col-sm-6">
        <q-card flat bordered class="overflow-hidden" :class="{ 'bg-grey-2': c.inativo }">
          <q-item
            clickable
            v-ripple
            :to="{ name: 'contrato-detalhe', params: { codcontrato: c.codcontrato } }"
          >
            <q-item-section avatar>
              <q-avatar
                :color="corTipo[c.tipo] || 'grey-7'"
                text-color="white"
                icon="description"
              />
            </q-item-section>
            <q-item-section>
              <!-- Nº Nosso (CULTURA-AA/AA-NNNN) -->
              <q-item-label v-if="c.contrato" caption class="text-overline">
                {{ c.contrato }}
              </q-item-label>
              <!-- Título: contraparte + modo do contrato (chip) ao lado -->
              <q-item-label class="text-subtitle1 text-weight-medium row items-center no-wrap">
                <span class="ellipsis">
                  {{ c.Pessoa?.fantasia || c.Pessoa?.pessoa || '—' }}
                </span>
                <q-chip
                  dense
                  square
                  :color="corTipo[c.tipo] || 'grey-7'"
                  text-color="white"
                  :label="c.tipo"
                  class="q-ml-sm q-my-none"
                />
              </q-item-label>
              <!-- Barra de entrega, total só em sacas (mesmo cadastrado em kg):
                   com teto = carregado / contratado SC; em aberto = só carregado. -->
              <q-item-label class="q-mt-sm">
                <!-- Sem limite: barra cheia riscada (estática, sem loop); com
                     teto: progresso real da entrega. -->
                <q-linear-progress
                  :value="c.volumeemaberto ? 1 : progresso(c)"
                  :stripe="!!c.volumeemaberto"
                  color="green-6"
                  track-color="grey-3"
                  size="8px"
                  rounded
                />
                <div class="text-caption text-grey-7 q-mt-xs">
                  {{ fmt(c.carregadosc)
                  }}<template v-if="!c.volumeemaberto"> / {{ fmt(c.quantidade) }}</template> SC
                </div>
              </q-item-label>
            </q-item-section>
            <!-- Ações (editar/inativar/excluir) ficam só na tela do contrato. -->
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-5" />
            </q-item-section>
          </q-item>
        </q-card>
      </div>
    </div>

    <MgEmptyState v-else icon="description">
      Nenhum contrato nesta safra. Crie o primeiro com o botão <q-icon name="add" />.
    </MgEmptyState>

    <ContratoForm
      :cad="cad"
      :fixar="{ codsafra: props.codsafra, codcultura: props.codcultura }"
      @saved="aposSalvar"
    />
  </div>
</template>
