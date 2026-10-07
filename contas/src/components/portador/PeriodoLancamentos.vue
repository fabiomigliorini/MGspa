<script setup>
// Lançamentos do período (doc-4, R10) como extrato: agrupados por dia, uma linha do tempo à
// esquerda (hora e o ícone da origem) e, à direita, o valor e o saldo corrente sempre na mesma
// coluna (no celular, o saldo embaixo do valor). Pagamento (venda, título, vale) abre o
// pagamento; ajuste, transferência e item (entrada ou saída de item do caixa, em espécie) são
// movimento do portador (a transferência leva ao outro portador, no período onde o valor caiu). O
// borderô da maquineta de parceiro (em espécie) mostra "sem borderô" enquanto não tem a foto, e a
// câmera da linha vê e anexa. A confirmar em amarelo; cancelado riscado e fora do saldo, com a
// justificativa. Confirmar e cancelar ficam na linha.
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import BorderoFotosDialog from '@components/caixa/BorderoFotosDialog.vue'
import {
  formataNumero,
  formataData,
  formataDataCompleta,
  formataHora,
} from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const $q = useQuasar()
const store = periodoStore()
const { portador, pode, periodo, filtroOrigem } = storeToRefs(store)

// ajuste e transferência no período da tela, não fechado
const podeMovimentar = computed(
  () => !!pode.value.operar && !!periodo.value && periodo.value.situacao !== 'fechado',
)
// entrada de item: portador em espécie com item ativo
const temItens = computed(
  () => !!portador.value?.ehCaixa && (periodo.value?.itens ?? []).some((i) => !i.inativo),
)

// borderô da maquineta de parceiro: portador em espécie com maquineta ativa
const temMaquinetas = computed(
  () => !!portador.value?.ehCaixa && (periodo.value?.maquinetas ?? []).length > 0,
)

// as fotos do borderô da linha escolhida (a linha vem do período, atualiza ao anexar)
const fotosDe = ref(null)
const dialogFotos = ref(false)
const linhaFotos = computed(() =>
  (periodo.value?.lancamentos ?? []).find((l) => l.codportadormovimento === fotosDe.value),
)
function abrirFotos(l) {
  fotosDe.value = l.codportadormovimento
  dialogFotos.value = true
}

// os cancelados (riscados) só aparecem no toggle
const mostrarCancelados = ref(false)
const cancelados = computed(
  () => (periodo.value?.lancamentos ?? []).filter((l) => l.cancelado).length,
)
const lista = computed(() =>
  (periodo.value?.lancamentos ?? []).filter(
    (l) =>
      (!filtroOrigem.value || l.origem === filtroOrigem.value) &&
      (mostrarCancelados.value || !l.cancelado),
  ),
)
const filtro = computed(
  () => periodo.value?.resumo?.find((r) => r.origem === filtroOrigem.value)?.descricao,
)

// os lançamentos (já em ordem de transação) agrupados por dia
const dias = computed(() => {
  const ret = []
  for (const l of lista.value) {
    const data = formataData(l.transacao)
    if (ret.at(-1)?.data !== data) {
      const extenso = formataDataCompleta(l.transacao)
      ret.push({ data, titulo: extenso.charAt(0).toUpperCase() + extenso.slice(1), linhas: [] })
    }
    ret.at(-1).linhas.push(l)
  }
  return ret
})

// a origem do lançamento (V venda, T títulos e vales, I item do caixa, M maquineta de parceiro,
// X transferência, J ajuste, A taxa/tarifa/rendimento)
const ICONE = {
  V: 'shopping_cart',
  T: 'request_quote',
  I: 'inventory_2',
  M: 'point_of_sale',
  X: 'swap_horiz',
  J: 'tune',
  A: 'account_balance',
}

const temSaldoColuna = computed(() => $q.screen.gt.xs)

const cancelado = (l) => l.cancelado
const pendente = (l) => !cancelado(l) && l.estado === 'P'
// só o pagamento abre (ajuste e transferência não são pagamento)
const link = (l) =>
  l.codpagamento ? { name: 'pagamento-detalhe', params: { id: l.codpagamento } } : null
const corAvatar = (l) =>
  cancelado(l) ? 'grey-2' : pendente(l) ? 'amber-2' : l.valor < 0 ? 'red-1' : 'green-1'
const corIcone = (l) =>
  cancelado(l) ? 'grey-5' : pendente(l) ? 'amber-9' : l.valor < 0 ? 'red-8' : 'green-8'
const corValor = (l) =>
  cancelado(l) ? 'text-strike text-grey-5' : l.valor < 0 ? 'text-red-8' : 'text-green-8'
const valor = (l) => (l.valor > 0 ? '+' : '') + formataNumero(l.valor)

const justificar = (title, ok, fn) =>
  $q
    .dialog({
      title,
      message: 'Motivo:',
      prompt: {
        model: '',
        type: 'text',
        outlined: true,
        isValid: (v) => (v || '').trim().length >= 5,
      },
      cancel: { label: 'Voltar', color: 'grey-8', flat: true },
      ok: { label: ok, color: 'negative', flat: true },
    })
    .onOk(fn)

// ajuste, item e transferência pelo movimento; taxa/tarifa/rendimento pelo pagamento
function cancelar(l) {
  const titulo = l.tipo === 'T' ? 'Cancelar transferência' : 'Cancelar lançamento'
  justificar(titulo, titulo, (j) =>
    l.tipo === 'P'
      ? store.cancelarTaxa(l.codpagamento, j)
      : store.cancelarMovimento(l.codportadormovimento, j),
  )
}
</script>

<template>
  <q-card v-if="periodo" flat bordered>
    <q-card-section class="row items-center q-pb-sm">
      <div class="col text-subtitle1 text-weight-medium">Lançamentos</div>
      <q-chip
        v-if="filtro"
        removable
        color="blue-1"
        text-color="primary"
        icon="filter_alt"
        :label="filtro"
        @remove="filtroOrigem = null"
      />
      <q-toggle
        v-if="cancelados"
        v-model="mostrarCancelados"
        :label="`Mostrar cancelados (${cancelados})`"
        color="primary"
      />
      <template v-if="podeMovimentar">
        <q-btn flat round size="sm" color="primary" icon="add" @click="store.dialogAvulso = true">
          <q-tooltip>{{
            portador.ehCaixa ? 'Ajuste' : 'Ajuste, taxa, tarifa, rendimento'
          }}</q-tooltip>
        </q-btn>
        <q-btn
          v-if="temItens"
          flat
          round
          size="sm"
          color="grey-7"
          icon="style"
          @click="store.abrirItem()"
        >
          <q-tooltip>Entrada de item</q-tooltip>
        </q-btn>
        <q-btn
          v-if="temMaquinetas"
          flat
          round
          size="sm"
          color="grey-7"
          icon="point_of_sale"
          @click="store.dialogMaquineta = true"
        >
          <q-tooltip>Borderô de maquineta</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          size="sm"
          color="grey-7"
          icon="swap_horiz"
          @click="store.dialogTransferir = true"
        >
          <q-tooltip>{{ portador.ehCaixa ? 'Reforço / Sangria' : 'Transferir' }}</q-tooltip>
        </q-btn>
      </template>
    </q-card-section>

    <q-card-section v-if="lista.length" class="q-pt-none">
      <!-- cabeçalho das colunas: o mesmo esqueleto da linha, para alinhar -->
      <div v-if="temSaldoColuna" class="row no-wrap text-caption text-grey-7">
        <div style="width: 92px" />
        <div class="col row no-wrap q-px-sm">
          <div class="col" />
          <div class="text-right q-pl-md" style="width: 112px">Valor</div>
          <div class="text-right q-pl-md" style="width: 112px">Saldo</div>
        </div>
      </div>

      <div v-for="dia in dias" :key="dia.data" class="q-mt-sm">
        <div class="text-caption text-weight-bold text-grey-8 q-py-xs">{{ dia.titulo }}</div>

        <div v-for="(l, i) in dia.linhas" :key="l.codportadormovimento" class="row no-wrap">
          <!-- hora -->
          <div
            class="row items-center justify-end text-caption text-grey-7 q-pr-sm"
            style="width: 48px; height: 56px"
          >
            {{ formataHora(l.transacao) }}
          </div>

          <!-- trilho: o ícone da origem, ligado ao anterior e ao próximo do dia -->
          <div class="column items-center no-wrap" style="width: 36px">
            <div style="width: 2px; height: 10px" :class="i ? 'bg-grey-3' : ''" />
            <q-avatar
              size="36px"
              font-size="20px"
              :color="corAvatar(l)"
              :text-color="corIcone(l)"
              :icon="ICONE[l.origem]"
            />
            <div
              class="col"
              style="width: 2px; min-height: 10px"
              :class="i < dia.linhas.length - 1 ? 'bg-grey-3' : ''"
            />
          </div>

          <!-- o lançamento -->
          <q-item
            :clickable="!!link(l)"
            :to="link(l)"
            class="col rounded-borders q-px-sm q-ml-sm q-mb-xs"
            :class="pendente(l) ? 'bg-amber-1' : ''"
            style="min-height: 56px"
          >
            <q-item-section>
              <q-item-label :class="cancelado(l) ? 'text-strike text-grey-6' : ''">
                {{ l.texto }}
              </q-item-label>
              <q-item-label caption class="row items-center q-gutter-x-xs">
                <span v-if="l.detalhe">{{ l.detalhe }}</span>
                <q-badge v-if="pendente(l)" color="amber-8" label="a confirmar" />
                <q-badge v-if="cancelado(l)" color="grey-5" label="cancelado" />
                <q-badge v-if="l.semBordero" color="orange-8" label="sem borderô" />
                <q-btn
                  v-if="l.tipo === 'M' && (l.fotos?.length || l.podeAnexar)"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="photo_camera"
                  @click.stop.prevent="abrirFotos(l)"
                >
                  <q-tooltip>{{
                    l.fotos?.length ? 'Ver a foto do borderô' : 'Anexar a foto'
                  }}</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="l.contraparte"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="open_in_new"
                  :to="{
                    name: 'portador-detalhe',
                    params: {
                      codportador: l.contraparte.codportador,
                      codportadorperiodo: l.contraparte.codportadorperiodo,
                    },
                  }"
                  @click.stop
                >
                  <q-tooltip>Ver em {{ l.contraparte.portador }}</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="l.podeConfirmar"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="done"
                  @click.stop.prevent="store.confirmarTransferencia(l.codportadormovimento)"
                >
                  <q-tooltip>Confirmar o recebimento</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="l.podeCancelar"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="block"
                  @click.stop.prevent="cancelar(l)"
                >
                  <q-tooltip>Cancelar</q-tooltip>
                </q-btn>
              </q-item-label>
              <q-item-label v-if="cancelado(l) && l.justificativa" caption class="text-grey-7">
                {{ l.justificativa }}
                <template v-if="l.usuariocancelamento"> · {{ l.usuariocancelamento }}</template>
              </q-item-label>
            </q-item-section>

            <q-item-section side top style="width: 112px">
              <q-item-label class="text-weight-bold" :class="corValor(l)">
                {{ valor(l) }}
              </q-item-label>
              <q-item-label v-if="!temSaldoColuna && l.saldo !== null" caption>
                saldo {{ formataNumero(l.saldo) }}
              </q-item-label>
            </q-item-section>

            <q-item-section v-if="temSaldoColuna" side top style="width: 112px">
              <q-item-label :class="l.saldo < 0 ? 'text-red-8' : 'text-grey-8'">
                {{ l.saldo !== null ? formataNumero(l.saldo) : '' }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </div>
      </div>
    </q-card-section>

    <MgEmptyState v-else plain icon="receipt_long">Nenhum lançamento.</MgEmptyState>

    <BorderoFotosDialog
      v-model="dialogFotos"
      :codportadormovimento="fotosDe"
      :fotos="linhaFotos?.fotos ?? []"
      :pode-anexar="!!linhaFotos?.podeAnexar"
      :anexar="(b) => store.anexarFotoBordero(fotosDe, b)"
    />
  </q-card>
</template>
