<script setup>
// Receber ou Pagar Títulos: a tela de baixa compartilhada com o PDV (MgBaixaTitulos), com as
// formas do financeiro (cartão com bandeira, autorização, parcelas e maquineta; banco; dinheiro
// do cofre, troco, caixa ou gaveta; cheque; cartão da empresa; compensação). Maquinetas de
// todas as filiais (o financeiro conserta lançamento de qualquer loja).
// Com ?codpix= (vindo da tela PIX, TASK-219), usa o PIX inteiro, na data e na conta dele.
// Com ?duplicar= (o Duplicar do detalhe, TASK-236), abre com os títulos, valores, pessoa, data,
// "referente a" e observação do pagamento cancelado; a forma se escolhe de novo. O original que
// ainda vale é recusado: os títulos dele continuam baixados (cancelar é outro botão).
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Notify } from 'quasar'
import { api } from 'src/services/api'
import { formataNumero, formataTimestamp, formataTimestampIso } from '@components/formatters'
import MgBaixaTitulos from '@components/MgBaixaTitulos.vue'
import { contextoCobranca, FORMAS_TITULOS } from 'src/utils/cobranca'

const route = useRoute()
const router = useRouter()

// o PIX que veio para ser usado
const pix = ref(null)
// a baixa do pagamento duplicado
const inicial = ref(null)
const pronto = ref(!route.query.codpix && !route.query.duplicar)

const avisar = (message) =>
  Notify.create({ type: 'negative', message, color: 'red-5', icon: 'error' })

const carregarPix = async () => {
  try {
    const { data } = await api.get(`v1/pix/${route.query.codpix}`)
    const p = data.data
    if (p.utilizacao) {
      avisar('Este PIX já foi usado.')
      return
    }
    pix.value = {
      codpix: p.codpix,
      valor: p.valor,
      descricao: [
        `PIX de R$ ${formataNumero(p.valor)}`,
        p.nome,
        p.portador,
        formataTimestamp(p.horario),
      ]
        .filter(Boolean)
        .join(' · '),
      codpessoa: p.codpessoa,
    }
  } finally {
    pronto.value = true
  }
}

const carregarDuplicar = async () => {
  try {
    const { data } = await api.get(`v1/pagamento/${route.query.duplicar}`)
    const p = data.data
    if (!p.duplicavel) {
      avisar(`O pagamento #${p.codpagamento} não é uma baixa de títulos.`)
      return
    }
    if (p.estado !== 'C') {
      avisar(
        `Cancele o pagamento #${p.codpagamento} antes de duplicar: os títulos dele continuam baixados.`,
      )
      return
    }
    inicial.value = {
      codpessoa: p.codpessoa,
      transacao: formataTimestampIso(p.transacao).slice(0, 16),
      referente: p.referente ?? '',
      observacao: p.observacoes ?? '',
      // no formato do seletor: saldo = o capital baixado
      titulos: p.movimentos
        .filter((m) => m.duplicavel && m.titulo)
        .map((m) => ({
          codtitulo: m.codtitulo,
          numero: m.titulo.numero,
          fatura: m.titulo.fatura,
          vencimento: m.titulo.vencimento,
          codpessoa: m.titulo.codpessoa,
          fantasia: m.titulo.fantasia,
          codfilial: m.titulo.codfilial,
          filial: m.titulo.filial,
          gerencial: m.titulo.gerencial,
          boleto: m.titulo.boleto,
          operacao: m.titulo.operacao,
          saldo: m.principal,
          juros: m.juros || null,
          multa: m.multa || null,
          desconto: m.desconto || null,
          total: m.total,
        })),
    }
  } catch (e) {
    avisar(e?.response?.data?.message ?? 'Erro ao carregar o pagamento.')
  } finally {
    pronto.value = true
  }
}

onMounted(async () => {
  if (route.query.codpix) await carregarPix()
  if (route.query.duplicar) await carregarDuplicar()
})

const finalizado = (pags) =>
  router.replace({ name: 'pagamento-detalhe', params: { id: pags[0].codpagamento } })
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-item class="q-pb-md q-px-none">
        <q-item-section avatar>
          <q-btn flat round icon="arrow_back" :to="{ name: 'pagamento' }" aria-label="Voltar" />
        </q-item-section>
        <q-item-section>
          <div class="text-h5 text-grey-9">Receber ou Pagar Títulos</div>
        </q-item-section>
      </q-item>

      <MgBaixaTitulos
        v-if="pronto"
        :formas="FORMAS_TITULOS"
        :contexto="contextoCobranca"
        :finalizar="{ url: 'v1/pagamento', extras: {} }"
        :pix="pix"
        :inicial="inicial"
        com-data
        @finalizado="finalizado"
      />
    </div>
  </q-page>
</template>
