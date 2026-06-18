<script setup>
import { ref, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { useCadastro } from 'src/composables/useCadastro'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgContratoForm from 'components/MgContratoForm.vue'

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

const $q = useQuasar()
const cad = useCadastro('contrato', 'codcontrato', 'Contrato')
const contratos = ref([])
const naturezas = ref([])
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
  if (c.semlimite) return 0
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

function novo() {
  cad.abrirNovo({ tipo: 'FIXO', moeda: 'BRL' })
}
async function aposSalvar() {
  await recarregar()
  emit('changed')
}

async function alternarInativo(c) {
  try {
    if (c.inativo) await api.delete(`v1/contrato/${c.codcontrato}/inativo`)
    else await api.post(`v1/contrato/${c.codcontrato}/inativo`)
    await aposSalvar()
  } catch (e) {
    notifyError(e)
  }
}
function excluir(c) {
  $q.dialog({
    title: 'Excluir',
    message: `Excluir o contrato ${c.contrato}?`,
    cancel: true,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/contrato/${c.codcontrato}`)
      notifySuccess('Excluído!')
      await aposSalvar()
    } catch (e) {
      notifyError(e)
    }
  })
}

watch(
  () => props.online,
  (on) => {
    if (on && !contratos.value.length) recarregar()
  },
)

onMounted(async () => {
  if (!props.online) return
  try {
    const { data } = await api.get('v1/natureza-operacao')
    naturezas.value = data.data ?? data
  } catch {
    // naturezas é opcional (só pro form fiscal); não bloqueia a grid
  }
  await recarregar()
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
              <!-- Título: com quem o contrato foi feito (comprador) -->
              <q-item-label class="text-subtitle1 text-weight-medium">
                {{ c.Pessoa?.fantasia || c.Pessoa?.pessoa || '—' }}
              </q-item-label>
              <!-- Barra de entrega em kg (+ sacas) com o modo do contrato ao lado -->
              <q-item-label class="q-mt-sm">
                <q-linear-progress
                  :value="progresso(c)"
                  :indeterminate="!!c.semlimite"
                  color="green-6"
                  track-color="grey-3"
                  size="8px"
                  rounded
                />
                <div class="row items-center q-mt-xs">
                  <div class="text-caption text-grey-7">
                    {{ fmt(c.carregadokg) }} / {{ c.semlimite ? '∞' : fmt(c.contratadokg) }} kg
                    <span class="text-grey-5"
                      >(≈ {{ fmt(c.carregadosc, 1) }} /
                      {{ c.semlimite ? '∞' : fmt(c.quantidade) }} sc)</span
                    >
                  </div>
                  <q-chip
                    dense
                    square
                    :color="corTipo[c.tipo] || 'grey-7'"
                    text-color="white"
                    :label="c.tipo"
                    class="q-ml-sm q-my-none"
                  />
                </div>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap">
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="edit"
                  @click.stop.prevent="cad.editar(c)"
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="c.inativo ? 'play_arrow' : 'pause'"
                  @click.stop.prevent="alternarInativo(c)"
                >
                  <q-tooltip>{{ c.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="delete"
                  @click.stop.prevent="excluir(c)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-card>
      </div>
    </div>

    <q-banner v-else rounded class="bg-grey-2 text-grey-7">
      Nenhum contrato nesta safra. Crie o primeiro com o botão <q-icon name="add" />.
    </q-banner>

    <MgContratoForm
      :cad="cad"
      :naturezas="naturezas"
      :fixar="{ codsafra: props.codsafra, codcultura: props.codcultura }"
      @saved="aposSalvar"
    />
  </div>
</template>
