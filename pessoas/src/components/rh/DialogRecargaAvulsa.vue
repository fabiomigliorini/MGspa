<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'
import { formataNumero, formataCnpjCpf } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

/**
 * Monta um lote de recarga escolhendo quem entra e com quanto.
 *
 * É o único caminho de criação: a tela não tem mais um botão que paga todo
 * mundo de uma vez. Abre com quem tem saldo já marcado pelo valor cheio (o lote
 * do mês é confirmar), e daí dá para desmarcar, mudar valor, ou marcar alguém
 * que já foi pago — segunda recarga do mesmo mês é caso de uso, não erro.
 *
 * Daqui NUNCA sai saldo: só codperiodocolaborador e valor. O saldo mostrado ao
 * lado de cada linha é referência visual; quem manda é o banco.
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  codperiodo: { type: [Number, String], required: true },
  empresa: { type: Object, default: () => ({}) },

  // Linhas da prévia daquela empresa, vindas da página. Não busca sozinho de
  // propósito: a página já é dona desses dados, e um segundo fetch deixaria o
  // diálogo mostrar um saldo diferente da tela atrás dele.
  linhas: { type: Array, default: () => [] },

  // codperiodocolaborador a marcar na abertura. A ação de linha manda um; o
  // botão do cabeçalho manda vazio e marca todo mundo que tem saldo.
  seed: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'gerado'])

const $q = useQuasar()
const sRh = rhStore()

const dialog = ref(false)
const salvando = ref(false)
const busca = ref('')
const marcados = ref({})
const valores = ref({})
const dia = ref(null)
const observacao = ref('')
const codportador = ref(null)

const hojeIso = () => {
  // Local, não toISOString(): à noite o UTC já virou o dia seguinte.
  const d = new Date()
  const mes = String(d.getMonth() + 1).padStart(2, '0')
  const dias = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${mes}-${dias}`
}

const semAcento = (texto) =>
  (texto || '')
    .toString()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()

// Quem tem o que receber. Não é trava: qualquer um pode ser marcado (pagar de
// novo depois do acerto é adiantamento, e é permitido). Só decide quem vem
// pré-marcado e o que o "Marcar todos" pega.
const temSaldo = (l) => Number(l.saldo) > 0

// Valor acima do que o acerto justifica — vira saldo negativo na tela do
// período, marcado como "Adiantado".
const adiantamentoDaLinha = (l) =>
  Math.max((Number(valores.value[l.codperiodocolaborador]) || 0) - Math.max(Number(l.saldo), 0), 0)

const filtradas = computed(() => {
  const termo = semAcento(busca.value).trim()
  if (!termo) return props.linhas
  return props.linhas.filter(
    (l) =>
      semAcento(l.nome).includes(termo) ||
      semAcento(l.setor).includes(termo) ||
      String(l.cpf || '').includes(termo.replace(/\D/g, '')),
  )
})

const marcada = (l) => !!marcados.value[l.codperiodocolaborador]

const selecionadas = computed(() => props.linhas.filter(marcada))

const total = computed(() =>
  selecionadas.value.reduce((s, l) => s + (Number(valores.value[l.codperiodocolaborador]) || 0), 0),
)

// Marcado com valor zerado geraria uma linha que o servidor recusa — melhor
// travar o botão e dizer o porquê do que levar um 422 previsível.
const semValor = computed(() =>
  selecionadas.value.filter((l) => !(Number(valores.value[l.codperiodocolaborador]) > 0)),
)

const totalAdiantamento = computed(() =>
  selecionadas.value.reduce((s, l) => s + adiantamentoDaLinha(l), 0),
)

const comSaldo = computed(() => props.linhas.filter(temSaldo).length)

const alternar = (l, valor) => {
  marcados.value = { ...marcados.value, [l.codperiodocolaborador]: valor }
  // Marcar prefila o saldo (zero quando já foi pago — aí o RH digita), desmarcar
  // limpa. Mesma semântica do AcertoModal.
  valores.value = {
    ...valores.value,
    [l.codperiodocolaborador]: valor && temSaldo(l) ? Number(l.saldo) : null,
  }
}

const atualizarValor = (l, valor) => {
  // Só piso em zero — não há teto. O MgInputValor já garante o mínimo no emit,
  // mas setas e colagem passam por caminho diferente (o AcertoModal duplica
  // pelo mesmo motivo).
  valores.value = {
    ...valores.value,
    [l.codperiodocolaborador]: Math.max(Number(valor) || 0, 0),
  }
}

const marcarTodos = () => {
  const novos = { ...marcados.value }
  const novosValores = { ...valores.value }
  for (const l of filtradas.value) {
    if (!temSaldo(l)) continue
    novos[l.codperiodocolaborador] = true
    novosValores[l.codperiodocolaborador] = Number(l.saldo)
  }
  marcados.value = novos
  valores.value = novosValores
}

const limpar = () => {
  marcados.value = {}
  valores.value = {}
}

const preparar = () => {
  busca.value = ''
  dia.value = hojeIso()
  observacao.value = ''
  codportador.value = null
  marcados.value = {}
  valores.value = {}

  // Sem seed, abre como o lote do mês: todo mundo que tem saldo, pelo cheio.
  // Com seed (o "+" de uma linha), só aquela pessoa — mesmo que já esteja paga,
  // porque é justamente o caso de recarregar de novo.
  const alvo = props.seed.length
    ? props.linhas.filter((l) => props.seed.includes(l.codperiodocolaborador))
    : props.linhas.filter(temSaldo)

  for (const l of alvo) {
    marcados.value[l.codperiodocolaborador] = true
    valores.value[l.codperiodocolaborador] = temSaldo(l) ? Number(l.saldo) : null
  }
}

watch(
  () => props.modelValue,
  (v) => {
    dialog.value = v
    if (v) preparar()
  },
)
watch(dialog, (v) => emit('update:modelValue', v))

const submit = async () => {
  if (salvando.value || !selecionadas.value.length || semValor.value.length) return
  salvando.value = true
  try {
    const ret = await sRh.gerarRecarga(props.codperiodo, {
      codempresa: props.empresa.codempresa,
      codportador: codportador.value || null,
      dia: dia.value,
      observacao: observacao.value || null,
      itens: selecionadas.value.map((l) => ({
        codperiodocolaborador: l.codperiodocolaborador,
        valor: valores.value[l.codperiodocolaborador],
      })),
    })
    dialog.value = false
    emit('gerado', ret.data.data)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao gerar a recarga'),
    })
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="dialog" :maximized="$q.screen.lt.md">
    <q-card flat style="width: 900px; max-width: 95vw">
      <q-card-section class="text-grey-9 text-overline row items-center">
        NOVA RECARGA
        <span v-if="empresa.empresa" class="text-grey-6 q-ml-xs">— {{ empresa.empresa }}</span>
        <q-space />
        <span class="text-caption text-grey-7">
          {{ selecionadas.length }} marcado(s) · {{ comSaldo }} com saldo · {{ linhas.length }} no
          período
        </span>
      </q-card-section>

      <q-separator inset />

      <q-card-section class="q-pb-none">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-4">
            <q-input outlined dense clearable v-model="busca" label="Buscar colaborador" autofocus>
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-sm-4">
            <MgSelectPortador v-model="codportador" clearable label="Debitar do portador" />
          </div>
          <div class="col-6 col-sm-2">
            <MgInputData v-model="dia" dense label="Data do crédito" />
          </div>
          <div class="col-6 col-sm-2">
            <q-input outlined dense v-model="observacao" label="Observação" maxlength="200">
              <q-tooltip>Vai para o histórico do lote e do título de adiantamento</q-tooltip>
            </q-input>
          </div>
        </div>
      </q-card-section>

      <q-card-section class="row items-center q-py-sm">
        <q-btn
          flat
          dense
          size="sm"
          color="primary"
          label="Marcar quem tem saldo"
          @click="marcarTodos()"
        />
        <q-btn flat dense size="sm" color="grey-7" label="Limpar" @click="limpar()" />
      </q-card-section>

      <q-separator />

      <div class="scroll" style="max-height: 45vh">
        <div
          v-for="l in filtradas"
          :key="l.codperiodocolaborador"
          class="row items-center q-px-md q-py-xs"
          :class="marcada(l) ? 'bg-blue-1' : ''"
        >
          <div class="col-auto">
            <q-checkbox
              dense
              :model-value="marcada(l)"
              @update:model-value="(v) => alternar(l, v)"
            />
          </div>

          <div class="col q-pl-sm">
            <div class="text-body2" :class="temSaldo(l) ? 'text-grey-9' : 'text-grey-6'">
              {{ l.nome }}
              <q-icon v-if="l.sem_cartao" name="warning" color="orange-7" size="xs" class="q-ml-xs">
                <q-tooltip>Sem cartão-benefício ativo cadastrado</q-tooltip>
              </q-icon>
              <q-icon
                v-else-if="l.cartoes > 1"
                name="info"
                color="blue-7"
                size="xs"
                class="q-ml-xs"
              >
                <q-tooltip
                  >{{ l.cartoes }} cartões-benefício ativos — a operadora usa o CPF</q-tooltip
                >
              </q-icon>
            </div>
            <div class="text-caption text-grey-6">
              {{ l.setor || 'Sem setor' }} · {{ l.filial }} · {{ formataCnpjCpf(l.cpf, l.fisica) }}
            </div>
          </div>

          <div class="col-2 text-right text-caption">
            <div :class="temSaldo(l) ? 'text-orange-9' : 'text-grey-6'">
              {{ formataNumero(l.saldo) }}
            </div>
            <div v-if="adiantamentoDaLinha(l)" class="text-red-7">
              +{{ formataNumero(adiantamentoDaLinha(l)) }} adiantado
            </div>
            <div v-else class="text-grey-5">saldo</div>
          </div>

          <div class="col-3 q-pl-md">
            <MgInputValor
              dense
              :model-value="valores[l.codperiodocolaborador] ?? null"
              :min="0"
              :disable="!marcada(l)"
              :bg-color="adiantamentoDaLinha(l) ? 'red-1' : ''"
              @update:model-value="(v) => atualizarValor(l, v)"
            />
          </div>
        </div>

        <div v-if="!filtradas.length" class="q-pa-lg text-center text-grey">
          Nenhum colaborador encontrado.
        </div>
      </div>

      <q-separator />

      <q-card-actions align="right" class="text-primary">
        <div class="text-caption text-orange-9 q-mr-md" v-if="semValor.length">
          {{ semValor.length }} marcado(s) sem valor
        </div>
        <div class="text-caption text-red-7 q-mr-md" v-else-if="totalAdiantamento">
          {{ formataNumero(totalAdiantamento) }} acima do extrato
        </div>
        <div class="text-subtitle1 text-grey-9 q-mr-md">{{ formataNumero(total) }}</div>
        <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
        <q-btn
          flat
          :label="'Gerar Recarga (' + selecionadas.length + ')'"
          :disable="!selecionadas.length || semValor.length > 0"
          :loading="salvando"
          @click="submit()"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
