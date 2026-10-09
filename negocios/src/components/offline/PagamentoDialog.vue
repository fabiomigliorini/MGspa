<script setup>
// Detalhe de um pagamento lançado no negócio (ou de um grupo de parcelas a prazo): tudo que não
// cabe na linha do drawer + Excluir
import { computed } from 'vue'
import { Dialog } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { formataData, formataNumero } from '@components/formatters'
import {
  CONDICOES,
  tituloPagamento,
  valorExibido,
  visualCondicao,
  visualPagamento,
} from '@components/cobranca/pagamento.js'
import LogoPagamento from '@components/cobranca/LogoPagamento.vue'
import OcorrenciaMotivoDialog from './OcorrenciaMotivoDialog.vue'

const sNegocio = negocioStore()

const det = computed(() => sNegocio.pagamentoDetalhe)

// grupo de parcelas tem `condicao`; pagamento tem `meio`
const ehParcelas = computed(() => !!det.value?.condicao)

const titulo = computed(() =>
  ehParcelas.value ? CONDICOES[det.value.condicao] : tituloPagamento(det.value),
)

const visual = computed(() =>
  ehParcelas.value ? visualCondicao(det.value.condicao) : visualPagamento(det.value),
)

const valor = computed(() => (ehParcelas.value ? det.value.valor : valorExibido(det.value)))

const campos = computed(() => {
  const p = det.value
  if (!p || ehParcelas.value) {
    return []
  }
  const parcelado =
    p.parcelas > 1 ? `${p.parcelas}x de ${formataNumero(p.total / p.parcelas)}` : null
  const temAjuste = p.juros || p.desconto || p.valortroco
  return [
    { label: 'Parceiro', valor: p.parceiro },
    { label: 'Bandeira', valor: p.nomebandeira },
    { label: 'Autorização', valor: p.autorizacao },
    { label: 'Maquininha', valor: p.maquineta },
    { label: 'Parcelas', valor: parcelado },
    { label: 'Emitente', valor: p.chequeemitente },
    { label: 'Bom para', valor: p.chequevencimento ? formataData(p.chequevencimento) : null },
    { label: 'CMC7', valor: p.cmc7 },
    { label: 'Principal', valor: temAjuste ? formataNumero(p.principal) : null },
    { label: 'Juros', valor: p.juros ? formataNumero(p.juros) : null },
    { label: 'Desconto', valor: p.desconto ? formataNumero(p.desconto) : null },
    { label: 'Total', valor: temAjuste ? formataNumero(p.total) : null },
    { label: 'Troco', valor: p.valortroco ? formataNumero(p.valortroco) : null },
  ].filter((c) => c.valor)
})

const urlTitulo = (codtitulo) => process.env.CONTAS_URL + '/titulo/' + codtitulo

const podeExcluir = computed(() => {
  if (!det.value || !sNegocio.podeEditar) {
    return false
  }
  if (ehParcelas.value) {
    return det.value.parcelas.some((np) => !np.codtitulo)
  }
  return !det.value.integracao
})

const excluir = () => {
  // PDV monitorado: o caixa diz o motivo e vai para o livro de ocorrencias (TASK-205)
  if (!ehParcelas.value && sNegocio.monitorado) {
    Dialog.create({
      component: OcorrenciaMotivoDialog,
      componentProps: {
        titulo: 'Excluir pagamento',
        mensagem: `${titulo.value} de R$ ${formataNumero(valor.value)}`,
        okLabel: 'Excluir',
        contexto: 'pagamento',
      },
    }).onOk(async (motivo) => {
      await sNegocio.excluirPagamento(det.value.uuid, motivo)
      sNegocio.dialog.pagamento = false
    })
    return
  }
  Dialog.create({
    title: 'Excluir pagamento',
    message: `Excluir ${titulo.value} de R$ ${formataNumero(valor.value)}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'negative', flat: true },
  }).onOk(async () => {
    if (ehParcelas.value) {
      await sNegocio.excluirParcelas(det.value.condicao)
    } else {
      await sNegocio.excluirPagamento(det.value.uuid)
    }
    sNegocio.dialog.pagamento = false
  })
}
</script>
<template>
  <q-dialog v-model="sNegocio.dialog.pagamento">
    <q-card flat style="width: 400px; max-width: 90vw" v-if="det">
      <q-item class="q-pt-md">
        <q-item-section avatar>
          <logo-pagamento v-bind="visual" size="44px" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-subtitle1">{{ titulo }}</q-item-label>
          <q-item-label caption v-if="det.integracao">Pagamento integrado</q-item-label>
        </q-item-section>
        <q-item-section side class="text-h6 text-weight-bold text-grey-9">
          {{ formataNumero(valor) }}
        </q-item-section>
      </q-item>

      <q-separator inset />

      <q-list v-if="ehParcelas">
        <q-item v-for="np in det.parcelas" :key="np.uuid">
          <q-item-section>
            <q-item-label caption> Parcela {{ np.numero }}/{{ det.parcelas.length }} </q-item-label>
            <q-item-label>vence {{ formataData(np.vencimento) }}</q-item-label>
          </q-item-section>
          <q-item-section side class="text-grey-9">
            {{ formataNumero(np.valor) }}
            <a v-if="np.codtitulo" :href="urlTitulo(np.codtitulo)" target="_blank">
              {{ np.titulonumero ?? np.codtitulo }}
            </a>
          </q-item-section>
        </q-item>
      </q-list>

      <q-list v-else>
        <q-item v-for="c in campos" :key="c.label">
          <q-item-section>
            <q-item-label caption>{{ c.label }}</q-item-label>
          </q-item-section>
          <q-item-section side class="text-grey-9">{{ c.valor }}</q-item-section>
        </q-item>
        <q-item v-if="det.codtitulo" :href="urlTitulo(det.codtitulo)" target="_blank" clickable>
          <q-item-section>
            <q-item-label caption>Título</q-item-label>
          </q-item-section>
          <q-item-section side class="text-primary">{{ det.codtitulo }}</q-item-section>
        </q-item>
      </q-list>

      <q-card-actions align="right">
        <q-btn v-if="podeExcluir" flat label="Excluir" color="negative" @click="excluir()" />
        <q-btn flat label="Fechar" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
