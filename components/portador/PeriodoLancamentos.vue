<script setup>
// Lançamentos do período (doc-4, R10) como extrato: agrupados por dia, uma linha do tempo à
// esquerda (hora e o ícone da origem) e, à direita, o valor e o saldo corrente sempre na mesma
// coluna (no celular, o saldo embaixo do valor). Pagamento leva ao negócio (venda) ou, sem negócio
// (título, vale), ao pagamento; ajuste, transferência e item (entrada ou saída de item do caixa, em
// espécie) são movimento do portador (a transferência leva ao outro portador, no período onde o
// valor caiu). O borderô da maquineta de parceiro (em espécie) mostra "sem borderô" enquanto não
// tem a foto, e a câmera da linha vê e anexa. A confirmar em amarelo; cancelado riscado e fora do
// saldo, com a justificativa. Confirmar, cancelar e alterar a data (TASK-204: a linha vai para o
// período da data, com justificativa; a data alterada aparece embaixo) ficam na linha. A venda com
// o pagamento noutro dia mostra a data da venda (a da NFC-e).
// Lançar é um FAB no canto da tela que abre a lista do que fazer (LancarCaixaDialog). No caixa do
// PDV (negocios) o que é do contas (pagamento, outro portador) abre por :href.
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import BorderoFotosDialog from '@components/caixa/BorderoFotosDialog.vue'
import AlterarDataDialog from '@components/portador/AlterarDataDialog.vue'
import {
  formataNumero,
  formataData,
  formataDataCompleta,
  formataHora,
  formataTimestamp,
} from '@components/formatters'
import { periodoStore } from '@components/stores/periodoStore'

const $q = useQuasar()
const store = periodoStore()
const { portador, pode, periodo, filtroOrigem, pdv } = storeToRefs(store)

// lançar (o FAB) no período da tela, não fechado
const podeMovimentar = computed(
  () => !!pode.value.operar && !!periodo.value && periodo.value.situacao !== 'fechado',
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
      (!filtroOrigem.value ||
        l.origem === filtroOrigem.value ||
        `${l.origem}:${l.codcaixaitem}` === filtroOrigem.value) &&
      (mostrarCancelados.value || !l.cancelado),
  ),
)
// o rótulo do filtro: a linha do quadro (na espécie: a origem ou a movimentação de um item ou
// maquineta, "I:cod"/"M:cod") ou a origem do resumo
const filtro = computed(() => {
  const f = filtroOrigem.value
  if (!f) return null
  return (
    periodo.value?.quadro?.linhas?.find((l) => l.filtro === f)?.rotulo ??
    periodo.value?.resumo?.find((r) => r.origem === f)?.descricao
  )
})

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
// só o pagamento abre (ajuste e transferência não são pagamento): o negócio, se tiver; senão o
// pagamento. Dentro do app :to, no outro :href em outra aba
const link = (l) => {
  if (l.codnegocio) {
    return pdv.value
      ? { to: `/negocio/${l.codnegocio}` }
      : { href: `${process.env.NEGOCIOS_URL}/negocio/${l.codnegocio}`, target: '_blank' }
  }
  if (l.codpagamento) {
    return pdv.value
      ? { href: `${process.env.CONTAS_URL}/pagamento/${l.codpagamento}`, target: '_blank' }
      : { to: { name: 'pagamento-detalhe', params: { id: l.codpagamento } } }
  }
  return {}
}
// a transferência: o outro lado, no período onde o valor caiu (no contas)
const contraparte = (c) =>
  pdv.value
    ? {
        href: `${process.env.CONTAS_URL}/portador/${c.codportador}/${c.codportadorperiodo}`,
        target: '_blank',
      }
    : {
        to: {
          name: 'portador-detalhe',
          params: { codportador: c.codportador, codportadorperiodo: c.codportadorperiodo },
        },
      }
const corAvatar = (l) =>
  cancelado(l) ? 'grey-2' : pendente(l) ? 'amber-2' : l.valor < 0 ? 'red-1' : 'green-1'
const corIcone = (l) =>
  cancelado(l) ? 'grey-5' : pendente(l) ? 'amber-9' : l.valor < 0 ? 'red-8' : 'green-8'
const corValor = (l) =>
  cancelado(l) ? 'text-strike text-grey-5' : l.valor < 0 ? 'text-red-8' : 'text-green-8'
const valor = (l) => (l.valor > 0 ? '+' : '') + formataNumero(l.valor)

// alterar a data da linha escolhida
const dataDe = ref(null)
const dialogData = ref(false)
function abrirData(l) {
  dataDe.value = l
  dialogData.value = true
}
async function alterarData(dados) {
  if (await store.alterarData(dataDe.value.codportadormovimento, dados)) dialogData.value = false
}

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
    </q-card-section>
    <!-- o filtro e os cancelados numa linha própria, sem apertar o título -->
    <q-card-section v-if="filtro || cancelados" class="row items-center q-gutter-sm q-pt-none">
      <q-chip
        v-if="filtro"
        removable
        color="blue-1"
        text-color="primary"
        icon="filter_alt"
        :label="filtro"
        @remove="filtroOrigem = null"
      />
      <q-space />
      <q-toggle
        v-if="cancelados"
        v-model="mostrarCancelados"
        :label="`Mostrar cancelados (${cancelados})`"
        color="primary"
      />
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
            :clickable="!!(link(l).to || link(l).href)"
            v-bind="link(l)"
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
                <q-badge
                  v-if="l.dataVenda"
                  color="orange-8"
                  :label="`venda de ${formataData(l.dataVenda)}`"
                >
                  <q-tooltip>
                    A venda (e a NFC-e) é de {{ formataTimestamp(l.dataVenda) }}; o pagamento está
                    noutra data
                  </q-tooltip>
                </q-badge>
                <q-btn
                  v-if="l.fotos?.length || l.podeAnexar"
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
                  v-bind="contraparte(l.contraparte)"
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
                  v-if="l.podeAlterarData"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="event"
                  @click.stop.prevent="abrirData(l)"
                >
                  <q-tooltip>Alterar a data</q-tooltip>
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
              <q-item-label v-if="l.dataAlterada" caption class="text-grey-7">
                <q-icon name="event" size="xs" />
                era {{ formataTimestamp(l.dataAlterada.de) }}: {{ l.dataAlterada.justificativa }}
                <template v-if="l.dataAlterada.usuario"> · {{ l.dataAlterada.usuario }}</template>
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

    <AlterarDataDialog
      v-model="dialogData"
      :data="dataDe?.transacao"
      :salvando="store.salvando"
      @salvar="alterarData"
    />
    <BorderoFotosDialog
      v-model="dialogFotos"
      :codportadormovimento="fotosDe"
      :fotos="linhaFotos?.fotos ?? []"
      :pode-anexar="!!linhaFotos?.podeAnexar"
    />
  </q-card>

  <!-- o respiro para o FAB não cobrir o fim da lista -->
  <div v-if="podeMovimentar" class="q-py-xl" />
  <q-page-sticky v-if="podeMovimentar" position="bottom-right" :offset="[18, 18]">
    <q-btn fab icon="add" color="primary" @click="store.dialogLancar = true">
      <q-tooltip anchor="center left" self="center right">Lançar</q-tooltip>
    </q-btn>
  </q-page-sticky>
</template>
