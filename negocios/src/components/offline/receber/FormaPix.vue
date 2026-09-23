<script setup>
// Passo do wizard: PIX. Etapa 1 = QR Code (cobrança no banco) ou chave manual;
// etapa 2 (só QR) = conta que recebe. QR emite 'cobranca' para o wizard abrir o dialog do PixCob.
import { ref, computed, onMounted } from 'vue'
import { negocioStore } from 'stores/negocio'
import { pixStore } from 'stores/pix'
import { db } from 'boot/db'
import ListaOpcoes from './ListaOpcoes.vue'
import { VISUAL } from '../../../utils/pagamento.js'

const emit = defineEmits(['concluido', 'cobranca'])

const sNegocio = negocioStore()
const sPix = pixStore()

const listaRef = ref(null)
const etapa = ref('modo')
const portadores = ref([])
const codfilial = ref(null)
const criando = ref(false)

const valor = computed(() => sNegocio.receber.valor)

onMounted(async () => {
  const loc = await db.estoqueLocal.get(sNegocio.negocio.codestoquelocal)
  if (loc?.codfilial) {
    codfilial.value = loc.codfilial
    portadores.value = await sPix.carregarPortadores(loc.codfilial)
  }
})

const opcoesModo = computed(() => {
  let motivoQr = null
  if (!sNegocio.negocio.sincronizado) {
    motivoQr = 'Negócio ainda não sincronizado com o servidor'
  } else if (!portadores.value.length) {
    motivoQr = 'Nenhuma conta PIX disponível (sem conexão?)'
  }
  return [
    {
      tecla: 1,
      valor: 'qr',
      label: 'QR Code',
      caption: 'Gera a cobrança no banco e confirma sozinho',
      icone: 'qr_code',
      cor: VISUAL.pix.cor,
      desabilitado: !!motivoQr,
      motivo: motivoQr,
    },
    {
      tecla: 2,
      valor: 'chave',
      label: 'Pela chave (manual)',
      caption: 'Cliente já transferiu; lança como depósito',
      icone: 'key',
      cor: VISUAL.pix.cor,
    },
  ]
})

const opcoesConta = computed(() => {
  const padraoCod = sNegocio.padrao?.codportador
  const localCodfilial = codfilial.value

  // separa por filial
  const daFilial = portadores.value.filter((p) => p.codfilial === localCodfilial)
  const outras = portadores.value.filter((p) => p.codfilial !== localCodfilial)

  // encontra o portador padrao (pode ser de outra filial)
  const padraoPortador = padraoCod
    ? portadores.value.find((p) => p.codportador === padraoCod)
    : null

  // tenta identificar Banco do Brasil entre os da filial local
  const isBancoDoBrasil = (p) => {
    if (!p) return false
    if (p.codbanco) {
      const cb = String(p.codbanco)
      if (cb === '1' || cb === '001') return true
    }
    if (p.banco && typeof p.banco === 'string') {
      return /brasil/i.test(p.banco) || /banco do brasil/i.test(p.banco) || /bb\b/i.test(p.banco)
    }
    return false
  }

  const bbLocal = daFilial.find((p) => isBancoDoBrasil(p)) || null

  // Construir lista: padrao (se existir) -> bbLocal (se existir e diferente do padrao) -> demais da filial -> outras filiais
  const used = new Set()
  const ordered = []

  if (padraoPortador) {
    ordered.push(padraoPortador)
    used.add(padraoPortador.codportador)
  }

  if (bbLocal && !used.has(bbLocal.codportador)) {
    ordered.push(bbLocal)
    used.add(bbLocal.codportador)
  }

  // demais da filial
  for (const p of daFilial) {
    if (!used.has(p.codportador)) {
      ordered.push(p)
      used.add(p.codportador)
    }
  }

  // então as outras filiais
  for (const p of outras) {
    if (!used.has(p.codportador)) {
      ordered.push(p)
      used.add(p.codportador)
    }
  }

  const agrupar = daFilial.length > 0 && outras.length > 0

  return ordered.map((p, i) => ({
    tecla: i < 9 ? i + 1 : null,
    valor: p.codportador,
    label: p.banco,
    caption: (() => {
      if (!p.filial) return `Conta ${p.conta}-${p.contadigito}`
      // mostrar filial explicitamente para o bbLocal mesmo quando for da filial local
      if (p === bbLocal) return `Conta ${p.conta}-${p.contadigito} · ${p.filial}`
      return p.codfilial === localCodfilial
        ? `Conta ${p.conta}-${p.contadigito}`
        : `Conta ${p.conta}-${p.contadigito} · ${p.filial}`
    })(),
    logo: `/bancos/${p.codbanco}.svg`,
    icone: 'account_balance',
    cor: VISUAL.pix.cor,
    grupo: (() => {
      if (i < 2 && (padraoPortador || bbLocal)) return 'Principais PIX'
      if (!agrupar) return null
      return p.codfilial === localCodfilial ? 'Da filial' : 'Outras filiais'
    })(),
  }))
})

const escolherModo = (opcao) => {
  if (opcao.valor === 'chave') {
    chave()
    return
  }
  if (portadores.value.length === 1) {
    qr(portadores.value[0].codportador)
    return
  }
  etapa.value = 'conta'
}

const chave = async () => {
  await sNegocio.adicionarPagamento({
    codformapagamento: parseInt(process.env.CODFORMAPAGAMENTO_PIXCHAVE),
    tipo: 16, // Depósito Bancário
    valorpagamento: valor.value,
    parcelas: 1,
    valorparcela: valor.value,
  })
  emit('concluido')
}

const qr = async (codportador) => {
  if (criando.value) {
    return
  }
  criando.value = true
  const cob = await sNegocio.criarPixCob(valor.value, codportador)
  criando.value = false
  if (!cob) {
    return
  }
  emit('cobranca', { tipo: 'pix', dados: cob })
}

// devolve true quando consumiu a tecla
const tecla = (e) => {
  if (e.key === 'Escape') {
    if (etapa.value === 'conta') {
      etapa.value = 'modo'
      return true
    }
    return false
  }
  return !!listaRef.value?.tecla(e)
}

defineExpose({ tecla })
</script>
<template>
  <div>
    <template v-if="etapa === 'modo'">
      <lista-opcoes ref="listaRef" :opcoes="opcoesModo" @escolher="escolherModo" />
    </template>
    <template v-else>
      <lista-opcoes
        ref="listaRef"
        :opcoes="opcoesConta"
        :inicial="sNegocio.padrao.codportador"
        @escolher="(o) => qr(o.valor)"
      />
    </template>
    <q-inner-loading :showing="criando" />
  </div>
</template>
