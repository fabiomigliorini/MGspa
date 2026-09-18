<script setup>
// Detalhe de um pagamento lançado no negócio: tudo que não cabe na linha do drawer + Excluir
import { computed } from 'vue'
import { Dialog } from 'quasar'
import { negocioStore } from 'stores/negocio'
import { formataData, formataNumero } from '@components/formatters'
import { iconePagamento, logoBandeira, tituloPagamento } from '../../utils/pagamento.js'

const sNegocio = negocioStore()

const pag = computed(() => sNegocio.pagamentoDetalhe)

const campos = computed(() => {
  const p = pag.value
  if (!p) {
    return []
  }
  const parcelado = p.parcelas > 1 ? `${p.parcelas}x de ${formataNumero(p.valorparcela)}` : null
  return [
    { label: 'Forma', valor: p.formapagamento },
    { label: 'Tipo', valor: p.nometipo },
    { label: 'Parceiro', valor: p.parceiro },
    { label: 'Bandeira', valor: p.nomebandeira },
    { label: 'Autorização', valor: p.autorizacao },
    { label: 'Maquininha', valor: p.serialmaquineta },
    { label: 'Parcelas', valor: parcelado },
    { label: 'Prazo', valor: p.dias ? `${p.dias} dias` : null },
    { label: 'Emitente', valor: p.chequeemitente },
    { label: 'Bom para', valor: p.chequevencimento ? formataData(p.chequevencimento) : null },
    { label: 'CMC7', valor: p.cmc7 },
    { label: 'Pagamento', valor: p.valorjuros ? formataNumero(p.valorpagamento) : null },
    { label: 'Juros', valor: p.valorjuros ? formataNumero(p.valorjuros) : null },
    { label: 'Troco', valor: p.valortroco ? formataNumero(p.valortroco) : null },
  ].filter((c) => c.valor)
})

const urlTitulo = computed(() => process.env.CONTAS_URL + '/titulo/' + pag.value?.codtitulo)

const podeExcluir = computed(() => pag.value && !pag.value.integracao && sNegocio.podeEditar)

const excluir = () => {
  Dialog.create({
    title: 'Excluir pagamento',
    message: `Excluir ${pag.value.formapagamento} de R$ ${formataNumero(pag.value.valortotal)}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'negative', flat: true },
  }).onOk(async () => {
    await sNegocio.excluirPagamento(pag.value.uuid)
    sNegocio.dialog.pagamento = false
  })
}
</script>
<template>
  <q-dialog v-model="sNegocio.dialog.pagamento">
    <q-card flat style="width: 400px; max-width: 90vw" v-if="pag">
      <q-item class="q-pt-md">
        <q-item-section avatar>
          <q-img v-if="logoBandeira(pag)" :src="logoBandeira(pag)" width="40px" :ratio="64 / 40" />
          <q-avatar v-else color="grey-3" text-color="grey-8" :icon="iconePagamento(pag)" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-subtitle1">{{ tituloPagamento(pag) }}</q-item-label>
          <q-item-label caption v-if="pag.integracao">Pagamento integrado</q-item-label>
        </q-item-section>
        <q-item-section side class="text-h6 text-weight-bold text-grey-9">
          {{ formataNumero(pag.valortotal) }}
        </q-item-section>
      </q-item>

      <q-separator inset />

      <q-list>
        <q-item v-for="c in campos" :key="c.label">
          <q-item-section>
            <q-item-label caption>{{ c.label }}</q-item-label>
          </q-item-section>
          <q-item-section side class="text-grey-9">{{ c.valor }}</q-item-section>
        </q-item>
        <q-item v-if="pag.codtitulo" :href="urlTitulo" target="_blank" clickable>
          <q-item-section>
            <q-item-label caption>Título</q-item-label>
          </q-item-section>
          <q-item-section side class="text-primary">{{ pag.codtitulo }}</q-item-section>
        </q-item>
      </q-list>

      <q-card-actions align="right">
        <q-btn v-if="podeExcluir" flat label="Excluir" color="negative" @click="excluir()" />
        <q-btn flat label="Fechar" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>
