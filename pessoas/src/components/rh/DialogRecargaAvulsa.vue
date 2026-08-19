<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { rhStore } from 'src/stores/rh'
import { extrairErro } from 'src/utils/rhFormatters'
import { formataNumero, formataCnpjCpf } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'
import MgInputData from '@components/MgInputData.vue'

/**
 * Monta um lote de recarga escolhendo quem entra e com quanto.
 *
 * Abre com quem tem saldo já marcado pelo valor cheio — o lote do mês é abrir e
 * confirmar; daí dá para desmarcar ou reduzir valores.
 *
 * Os três valores por linha:
 *   Extrato — tudo a que o colaborador tem direito no período;
 *   Recarga — o que já foi para o cartão dele;
 *   Saldo   — a diferença. Positivo é o que falta entregar; negativo é o que
 *             foi entregue além do extrato.
 *
 * O valor digitado pode ficar abaixo do saldo (recarga parcial), nunca acima: a
 * recarga entrega um direito que já existe, e o direito nasce de rubrica ou
 * título em aberto, pela via do acerto. Para entregar mais, sobe-se o extrato
 * pelo acerto — é lá que a decisão do valor pertence.
 *
 * Daqui NUNCA sai saldo: só codperiodocolaborador e valor. O saldo mostrado ao
 * lado de cada linha é referência visual; quem manda é o banco, que revalida
 * dentro do lock.
 */
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  codperiodo: { type: [Number, String], required: true },
  empresa: { type: Object, default: () => ({}) },

  // Linhas da prévia daquela empresa, vindas da página. Não busca sozinho de
  // propósito: a página já é dona desses dados, e um segundo fetch deixaria o
  // diálogo mostrar um saldo diferente da tela atrás dele.
  linhas: { type: Array, default: () => [] },
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

// Quem tem o que receber. É trava: sem saldo não há o que entregar, e o valor
// digitado tem o saldo por teto.
const temSaldo = (l) => Number(l.saldo) > 0

// Separa os dois motivos de não ter saldo — nunca teve direito, ou já recebeu
// tudo. A trava é a mesma; o que o RH precisa fazer a seguir é diferente.
const temExtrato = (l) => Number(l.extrato) > 0

// Quem aparece: tem extrato ou já recebeu recarga. Quem não tem nem um nem
// outro não tem nada a ver com a recarga do período.
const envolvidas = computed(() =>
  props.linhas.filter((l) => Number(l.extrato) !== 0 || Number(l.recarga) !== 0),
)

const filtradas = computed(() => {
  const termo = semAcento(busca.value).trim()
  if (!termo) return envolvidas.value
  return envolvidas.value.filter(
    (l) =>
      semAcento(l.nome).includes(termo) ||
      semAcento(l.setor).includes(termo) ||
      String(l.cpf || '').includes(termo.replace(/\D/g, '')),
  )
})

const marcada = (l) => !!marcados.value[l.codperiodocolaborador]

const selecionadas = computed(() => envolvidas.value.filter(marcada))

const total = computed(() =>
  selecionadas.value.reduce((s, l) => s + (Number(valores.value[l.codperiodocolaborador]) || 0), 0),
)

// Marcado com valor zerado geraria uma linha que o servidor recusa — melhor
// travar o botão e dizer o porquê do que levar um 422 previsível.
const semValor = computed(() =>
  selecionadas.value.filter((l) => !(Number(valores.value[l.codperiodocolaborador]) > 0)),
)

const comSaldo = computed(() => envolvidas.value.filter(temSaldo).length)

const alternar = (l, valor) => {
  marcados.value = { ...marcados.value, [l.codperiodocolaborador]: valor }
  // Marcar prefila o saldo, desmarcar limpa — mesma semântica do AcertoModal.
  valores.value = {
    ...valores.value,
    [l.codperiodocolaborador]: valor ? Number(l.saldo) : null,
  }
}

const atualizarValor = (l, valor) => {
  // O MgInputValor já faz clamp pelo :max no emit, mas as setas e a colagem
  // passam por caminho diferente — o AcertoModal duplica pelo mesmo motivo.
  const num = Number(valor) || 0
  valores.value = {
    ...valores.value,
    [l.codperiodocolaborador]: Math.min(Math.max(num, 0), Number(l.saldo)),
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

  // Abre como o lote do mês: todo mundo que tem saldo, pelo valor cheio. Daí o
  // RH desmarca ou reduz o que quiser.
  const alvo = envolvidas.value.filter(temSaldo)

  for (const l of alvo) {
    marcados.value[l.codperiodocolaborador] = true
    valores.value[l.codperiodocolaborador] = Number(l.saldo)
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
          {{ selecionadas.length }} marcado(s) · {{ comSaldo }} com saldo a receber
        </span>
      </q-card-section>

      <q-separator inset />

      <q-card-section class="q-pb-none">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-sm-4">
            <q-input outlined clearable v-model="busca" label="Buscar colaborador" autofocus>
              <template #prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-6 col-sm-4">
            <MgInputData v-model="dia" label="Data do crédito" />
          </div>
          <div class="col-6 col-sm-4">
            <q-input outlined v-model="observacao" label="Observação" maxlength="200">
              <q-tooltip>Vai para o histórico do lote e do título de adiantamento</q-tooltip>
            </q-input>
          </div>
        </div>
      </q-card-section>

      <q-card-section class="row items-center q-py-sm q-mt-md">
        <q-btn
          flat
          dense
          size="sm"
          color="primary"
          label="Marcar quem tem saldo"
          @click="marcarTodos()"
        />
        <q-space />
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
              :disable="!temSaldo(l)"
              @update:model-value="(v) => alternar(l, v)"
            >
              <q-tooltip v-if="!temSaldo(l)">
                {{
                  temExtrato(l)
                    ? 'Sem saldo — o extrato deste período já foi entregue no cartão'
                    : 'Sem extrato no período — lance a rubrica e o acerto antes de recarregar'
                }}
              </q-tooltip>
            </q-checkbox>
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

          <div class="col-2 text-right text-caption text-grey-7">
            <div>{{ formataNumero(l.extrato) }}</div>
            <div class="text-grey-5">extrato</div>
          </div>

          <div class="col-2 text-right text-caption text-grey-7">
            <div>{{ formataNumero(l.recarga) }}</div>
            <div class="text-grey-5">recarga</div>
          </div>

          <div class="col-2 text-right text-caption">
            <div
              :class="
                Number(l.saldo) < 0 ? 'text-red-7' : temSaldo(l) ? 'text-orange-9' : 'text-grey-6'
              "
            >
              {{ formataNumero(l.saldo) }}
            </div>
            <div class="text-grey-5">saldo</div>
          </div>

          <div class="col-3 q-pl-md">
            <MgInputValor
              dense
              :model-value="valores[l.codperiodocolaborador] ?? null"
              :min="0"
              :max="Number(l.saldo)"
              :disable="!marcada(l)"
              @update:model-value="(v) => atualizarValor(l, v)"
            />
          </div>
        </div>

        <div v-if="!filtradas.length" class="q-pa-lg text-center text-grey">
          {{
            busca
              ? 'Nenhum colaborador encontrado.'
              : 'Ninguém desta empresa tem acerto Bee no período. Use o adiantamento para criar o direito.'
          }}
        </div>
      </div>

      <q-separator />

      <q-card-actions align="right" class="text-primary">
        <div class="text-caption text-orange-9 q-mr-md" v-if="semValor.length">
          {{ semValor.length }} marcado(s) sem valor
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
