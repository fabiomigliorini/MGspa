<script setup>
// Formulário da carga aberta (era o CargaDialog, agora inline no centro da
// tela como o negócio no PDV). Trabalha numa CÓPIA local da carga e emite
// salvar/avancar/cancelar — quem persiste é a página/store. As ações ficam em
// FABs no canto (padrão do PDV); a página dispara F3/F4 via defineExpose.
//
// Os campos ficam em 4 blocos (Caminhão/Pontos/Pesagem/Classificação), cada um
// com dialog próprio de edição (Cancelar/Salvar) — só este componente conhece
// `local`; os blocos recebem a MESMA referência via prop e persistem através
// de `persistirBloco` (provide), que reaproveita o caminho já existente
// (CargaPage.persistir: troca de safra + erro tratado), sem duplicar nada.
import { ref, computed, watch, provide } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { calcularCarga, sacas } from 'src/utils/desconto'
import {
  ETAPA_META,
  pontoCompleto,
  semearPontos,
  aplicarSentido,
  distribuirPercentual,
  pontosPorPapel,
  somaPercBate,
  proximaEtapa,
  cargaFinalizada,
  cargaPesada,
  sentidoMeta,
  fmtNumero as fmt,
} from 'src/utils/carga'
import { imprimirTicket } from 'src/utils/ticket'
import CargaBlocoOperacao from './CargaBlocoOperacao.vue'
import CargaBlocoPontos from './CargaBlocoPontos.vue'
import CargaBlocoPesagem from './CargaBlocoPesagem.vue'
import CargaBlocoClassificacao from './CargaBlocoClassificacao.vue'

const props = defineProps({
  carga: { type: Object, default: null },
  // true = carga nova (só "Registrar", entra na 1ª etapa sem avançar)
  novo: { type: Boolean, default: false },
  // Função de persistência da página (CargaPage::persistir) — os blocos salvam
  // através dela (via persistirBloco), nunca chamando a store diretamente.
  persistir: { type: Function, default: null },
})
const emit = defineEmits(['salvar', 'avancar', 'cancelar'])

const $q = useQuasar()
const store = useCargaStore()
const { culturaAtiva, safraAtiva } = storeToRefs(store)

const formRef = ref(null)

// Conferência do fechamento (detalhe em `avancar`). Declarado aqui em cima
// porque o watcher imediato da carga, logo abaixo, já o zera.
const revisando = ref(false)

// Cópia local — clonada só quando MUDA a carga (uuid). A store recarrega o
// Dexie em background (sync) e isso não pode apagar o que o operador está
// digitando; os campos que o servidor devolve (codcarga/sync) entram por
// watcher separado, sem tocar no resto.
const local = ref(null)
watch(
  () => props.carga?.uuid,
  () => {
    const c = props.carga
    if (!c) {
      local.value = null
      return
    }
    const carga = normalizarPontos(JSON.parse(JSON.stringify(c)))
    if (props.novo) semearPontos(carga)
    local.value = carga
    revisando.value = false
  },
  { immediate: true },
)
watch(
  () => [props.carga?.codcarga, props.carga?.sincronizado, props.carga?.syncerro],
  ([codcarga, sincronizado, syncerro]) => {
    if (!local.value || !props.carga || props.carga.uuid !== local.value.uuid) return
    Object.assign(local.value, { codcarga, sincronizado, syncerro })
  },
)

// Compat: cargas antigas gravaram kg por ponto e não têm `percentual`. Reconstrói
// o % a partir do kg (proporção sobre o líquido) ou divide igualmente. Linha
// única → 100%. Cargas novas já vêm com percentual (não mexe).
function normalizarPontos(carga) {
  for (const papel of ['ORIGEM', 'DESTINO']) {
    const grupo = (carga.pontos || []).filter((p) => p.papel === papel)
    if (!grupo.length) continue
    if (!grupo.some((p) => p.percentual == null)) continue
    const liq = Number(carga.liquido)
    const temKg = liq > 0 && grupo.every((p) => Number(p.liquido) > 0)
    if (temKg) {
      grupo.forEach((p) => {
        p.percentual = Math.round((Number(p.liquido) / liq) * 1000) / 10
      })
    } else {
      distribuirPercentual(grupo)
    }
  }
  return carga
}

// ---- Operação (tipo de romaneio) — muda até FINALIZAR; depois, nunca mais ----
// Trocar reposiciona a carga na 1ª etapa do novo fluxo (aplicarSentido), mas os
// pesos já lidos ficam — cada bloco decide sozinho se ainda mostra o que já foi
// preenchido (mesmo fora da etapa "natural" do novo fluxo). O sinal do extrato
// vem de papel+contatipo, não do sentido, então o servidor não se importa com
// a troca.
//
// Depois da 1ª pesagem a troca pede confirmação: aí já existe dado a perder de
// vista (a carga sai do lugar no fluxo). Antes disso não há o que perder, então
// aplica direto — a TASK-141 abriu essa edição de propósito e isto NÃO a fecha
// de novo. A guarda mora aqui, no dono de `local`, pra não depender de quem
// chama (o bloco de Operação só renderiza o toggle).
const finalizada = computed(() => cargaFinalizada(local.value))

async function aplicarTroca(sentido) {
  aplicarSentido(local.value, sentido)
  // A revisão era do fluxo antigo: sem zerar, o FAB seguiria verde escrito
  // "Salvar" enquanto o clique só avançaria uma etapa do fluxo novo.
  revisando.value = false
  // Persiste na hora, como os demais blocos: sem isso o drawer da direita (que
  // lê da store) seguiria mostrando a operação antiga. Se a troca deixou ponto
  // incompleto, o aviso do `entradaValida` é a informação certa na hora certa —
  // re-escolher origem/destino costuma ser mesmo necessário depois de trocar.
  try {
    await persistirBloco()
  } catch {
    // erro já notificado por quem persiste (CargaPage); a troca segue em `local`
  }
}

function trocarOperacao(sentido) {
  if (!sentido || !local.value || sentido === local.value.sentido) return
  if (finalizada.value) return
  if (!cargaPesada(local.value)) {
    aplicarTroca(sentido)
    return
  }
  const meta = sentidoMeta(sentido)
  $q.dialog({
    title: 'Trocar a operação',
    message:
      `Esta carga já foi pesada. Ao trocar para <b>${meta.label}</b>, ` +
      `a carga volta para a 1ª etapa do fluxo de ${meta.label} e a origem/destino ` +
      'ainda não preenchida é redefinida. Os pesos já lidos ficam.',
    html: true,
    cancel: { label: 'Voltar', flat: true, color: 'grey-8' },
    ok: { label: 'Trocar', flat: true, color: 'primary' },
    persistent: true,
  }).onOk(() => aplicarTroca(sentido))
}

// Sem o chip do topo, isto alimenta só o rótulo/ícone do FAB principal e a
// mensagem de erro do `avancar` — a etapa em si aparece na barra de progresso
// do drawer da direita (CargaEtapaProgresso), com mais informação.
const etapaMeta = computed(() => ETAPA_META[local.value?.etapa] || {})

// ---- Pontos (origens / destinos) — usados na validação/impressão; a edição
// em si mora no CargaBlocoPontos. ----
const origens = computed(() => pontosPorPapel(local.value, 'ORIGEM'))
const destinos = computed(() => pontosPorPapel(local.value, 'DESTINO'))

// ---- Classificação ----
// Parâmetros da cultura da safra da carga, na ordem da cascata.
const itensCarga = computed(() => store.parametrosDaCarga(local.value || {}))

// Estado vazio da classificação. Sem parâmetro cadastrado o desconto sairia 0 em
// silêncio e o líquido viria igual ao bruto — o operador precisa saber ONDE isso
// se resolve, não só que está vazio.
const avisoClassificacao = computed(() => {
  if (!local.value || itensCarga.value.length) return null
  return {
    titulo: `Nenhum parâmetro de classificação ativo para ${culturaAtiva.value?.cultura || 'a cultura desta safra'}.`,
    dica: 'Cadastre em Culturas › Classificação (umidade, impureza, avariados) — sem parâmetro não há desconto e o líquido sairia igual ao bruto.',
  }
})

// Trava do botão CLASSIFICAR. Só vale NA etapa CLASSIFICACAO: nas seguintes
// (TARA/FINALIZADO) a carga já passou por aqui, e travar de novo impediria
// corrigir um romaneio cujo parâmetro foi inativado depois.
const erroClassificacao = computed(() =>
  local.value?.etapa === 'CLASSIFICACAO' ? avisoClassificacao.value?.titulo || null : null,
)

const calc = computed(() => (local.value ? calcularCarga(local.value, itensCarga.value) : {}))

// Garante uma linha de leitura por parâmetro (preserva o já digitado). Fica
// aqui (dono de `local`) e não no bloco, porque precisa valer mesmo com o
// dialog de Classificação fechado — `calc`/imprimir dependem dela sempre.
watch(
  itensCarga,
  (itens) => {
    if (!local.value) return
    if (!Array.isArray(local.value.classificacao)) local.value.classificacao = []
    const existentes = new Set(local.value.classificacao.map((c) => c.codparametroclassificacao))
    for (const it of itens || []) {
      if (!existentes.has(it.codparametroclassificacao)) {
        local.value.classificacao.push({
          codparametroclassificacao: it.codparametroclassificacao,
          leitura: null,
          desconto: null,
        })
      }
    }
  },
  { immediate: true },
)
function linhaDe(codparam) {
  return (
    (local.value?.classificacao || []).find((c) => c.codparametroclassificacao === codparam) || {}
  )
}
function descontoParam(codparam) {
  return (
    (calc.value.classificacao || []).find((c) => c.codparametroclassificacao === codparam)
      ?.desconto || null
  )
}

const pesosaca = computed(() => culturaAtiva.value?.pesosaca || 60)
const sacasLiquido = computed(() => sacas(calc.value.liquido, pesosaca.value))

// kg estimado de um ponto — só depois de pesar (líquido da carga × %). Usado
// só pelo ticket de impressão aqui; o bloco de Pontos tem a própria cópia
// (mesma fórmula) porque mostra isso na tela o tempo todo.
function kgDoPonto(p) {
  const liq = Number(calc.value.liquido)
  if (!(liq > 0)) return null
  return Math.round((liq * (Number(p.percentual) || 0)) / 100)
}

// ---- Validações de coleção (sem campo pra destacar) ----
function entradaValida() {
  if (!origens.value.length && !destinos.value.length) {
    $q.notify({ type: 'warning', message: 'Informe ao menos uma origem ou destino.' })
    return false
  }
  // Linha sem entidade seria DESCARTADA em silêncio (o filtro `pontoCompleto` do
  // store.salvar e o `contaDoPonto` do backend fazem o mesmo corte).
  if ((local.value?.pontos || []).some((p) => !pontoCompleto(p))) {
    $q.notify({
      type: 'negative',
      message: 'Selecione o talhão/unidade/contrato de cada origem e destino, ou remova a linha.',
    })
    return false
  }
  return true
}
function validarFinalizacao() {
  if (!origens.value.length || !destinos.value.length) {
    $q.notify({ type: 'negative', message: 'Informe ao menos uma origem e um destino.' })
    return false
  }
  if (!entradaValida()) return false
  if (!somaPercBate(origens.value) || !somaPercBate(destinos.value)) {
    $q.notify({ type: 'negative', message: 'A soma dos % de origem e de destino deve ser 100.' })
    return false
  }
  if (!(Number(calc.value.liquido) > 0)) {
    $q.notify({ type: 'negative', message: 'Peso líquido inválido (pbt − tara − desconto).' })
    return false
  }
  return true
}

// ---- Ações ----
// O q-form barra o submit em SILÊNCIO quando uma `:rules` falha — a única pista é
// o campo vermelho, que pode estar fora da tela. Sem este aviso o operador só vê
// "o botão não faz nada". `comp` é o primeiro campo inválido, que o q-form já foca.
function onErroValidacao(comp) {
  const campo = comp?.$props?.label
  $q.notify({
    type: 'warning',
    message: campo ? `Confira o campo “${campo}”.` : 'Confira os campos destacados.',
    caption: 'Há dado obrigatório faltando ou fora da faixa permitida.',
  })
}

function salvar() {
  if (finalizada.value ? !validarFinalizacao() : !entradaValida()) return
  emit('salvar', local.value)
}
// Salva na etapa ATUAL, sem avançar — pra corrigir um dado sem empurrar a carga.
function salvarSemAvancar() {
  if (!entradaValida()) return
  emit('salvar', local.value)
}
function cancelarCarga() {
  const sincronizada = !!local.value.codcarga || !!local.value.sincronizado
  $q.dialog({
    title: 'Cancelar carga',
    message: sincronizada
      ? `Cancelar a carga${local.value.codcarga ? ' #' + local.value.codcarga : ''}? Sai do pátio e estorna o estoque.`
      : 'Descartar esta carga pendente? Ela ainda não foi enviada ao servidor.',
    cancel: { label: 'Voltar', flat: true, color: 'grey-8' },
    ok: { label: 'Cancelar carga', flat: true, color: 'negative' },
    persistent: true,
  }).onOk(() => emit('cancelar', local.value))
}

const proxima = computed(() => proximaEtapa(local.value))
// Transição p/ FINALIZADO: ativa as :rules de "soma fecha" dos campos de líquido
// (no bloco de Pontos, via `finalizando` injetado).
const finalizando = computed(() => proxima.value === 'FINALIZADO')

// Revisão antes de fechar (`revisando`, declarado no topo): o clique da ÚLTIMA
// etapa (pesar tara, no recebimento) não grava mais direto — ele valida, mostra a
// conta fechada e vira "Salvar". O operador confere bruto/desconto/líquido/sacas
// com o caminhão ainda na balança, e só o segundo clique finaliza o romaneio.
//
// Mexeu num número, o que ele conferiu não vale mais: volta pra revisão. Só os
// campos que mudam a conta — um retorno do sync (codcarga/sincronizado) não pode
// derrubar a revisão no meio da conferência.
// A assinatura é STRING de propósito: um getter que devolve array cria um objeto
// novo a cada reavaliação e o watch dispararia mesmo com os números iguais —
// derrubando a revisão sozinho no meio da conferência.
watch(
  () => `${local.value?.pbt}|${local.value?.tara}|${calc.value?.desconto}|${calc.value?.liquido}`,
  () => {
    revisando.value = false
  },
)

function avancar() {
  if (!entradaValida()) return
  if (erroClassificacao.value) {
    $q.notify({ type: 'negative', message: erroClassificacao.value })
    return
  }
  const prox = proxima.value
  // Etapa fora do fluxo do sentido (ex.: romaneio gravado como CLASSIFICACAO e
  // depois virado Expedição, que não tem essa etapa): sem aviso o botão ficava
  // MUDO pra sempre e a carga não saía do lugar.
  if (!prox) {
    $q.notify({
      type: 'negative',
      message: `A etapa “${etapaMeta.value.label || local.value.etapa}” não faz parte do fluxo de ${sentidoMeta(local.value.sentido).label}.`,
      caption: 'Ajuste o tipo de romaneio ou avise o suporte.',
    })
    return
  }
  if (prox === 'FINALIZADO') {
    if (!validarFinalizacao()) return
    // 1º clique: para aqui e mostra a operação fechada. O 2º é que grava.
    if (!revisando.value) {
      revisando.value = true
      $q.notify({
        type: 'info',
        icon: 'fact_check',
        message: 'Confira a operação.',
        caption: 'O próximo clique salva e finaliza o romaneio.',
      })
      return
    }
  }
  local.value.etapa = prox
  emit('avancar', local.value)
}

// Botão principal (FAB): registrar (nova), salvar (finalizada) ou avançar etapa.
function onSubmit() {
  if (props.novo || finalizada.value) salvar()
  else avancar()
}
const rotuloPrincipal = computed(() => {
  if (props.novo) return 'Registrar'
  if (finalizada.value || revisando.value) return 'Salvar'
  return etapaMeta.value.acao
})
const iconePrincipal = computed(() => {
  if (props.novo) return 'add'
  if (finalizada.value || revisando.value) return 'save'
  return etapaMeta.value.icon
})
// Verde no clique que fecha o romaneio — é o único que não tem volta.
const corPrincipal = computed(() => (revisando.value ? 'positive' : 'primary'))

function fazendaNome() {
  for (const p of origens.value) {
    if (p.contatipo === 'PLANTIO') {
      const f = store.plantioPorId(p.codplantio)?.Fazenda?.fazenda
      if (f) return f
    }
  }
  return 'MG Agro'
}

function imprimir() {
  if (!local.value || !finalizada.value) return
  const c = calc.value
  const veic = store.veiculoPorId(local.value.codveiculo)
  const itensFonte = local.value.sentido === 'SAIDA' ? destinos.value : origens.value
  const ok = imprimirTicket({
    titulo:
      local.value.sentido === 'SAIDA'
        ? 'ROMANEIO DE EXPEDIÇÃO'
        : local.value.sentido === 'TRANSFERENCIA'
          ? 'ROMANEIO DE TRANSFERÊNCIA'
          : 'ROMANEIO DE RECEBIMENTO',
    rotuloItens: local.value.sentido === 'SAIDA' ? 'Destinos' : 'Origens',
    assinaturas:
      local.value.sentido === 'SAIDA'
        ? ['Conferente', 'Motorista', 'Expedidor']
        : ['Classificador', 'Motorista', 'Recebedor'],
    numero: local.value.codcarga,
    data: local.value.data,
    fazenda: fazendaNome(),
    cultura: culturaAtiva.value?.cultura,
    safra: safraAtiva.value?.safra,
    placa: local.value.placa,
    placacarreta: local.value.placacarreta,
    veiculo: veic?.veiculo || null,
    motorista: local.value.motorista,
    itens: itensFonte.map((p) => ({ rotulo: p.rotulo, kg: kgDoPonto(p) })),
    pbt: local.value.pbt,
    tara: local.value.tara,
    bruto: c.bruto,
    classificacao: itensCarga.value
      .map((it) => ({
        nome: it.parametroclassificacao,
        leitura: linhaDe(it.codparametroclassificacao)?.leitura,
        desconto: descontoParam(it.codparametroclassificacao),
      }))
      .filter((x) => x.leitura != null && x.leitura !== ''),
    desconto: c.desconto,
    liquido: c.liquido,
    sacas: sacasLiquido.value,
    pesosaca: pesosaca.value,
  })
  if (!ok) $q.notify({ type: 'warning', message: 'Permita pop-ups para imprimir o romaneio.' })
}

// ---- Ponte pros blocos de edição ----
// Cada bloco patcha seu pedaço em `local` (mesma referência recebida como
// `carga`) e chama isto pra persistir — reaproveita o MESMO caminho que
// "salvar sem avançar" já usa (troca de safra + erro tratado em
// CargaPage::persistir), sem duplicar nada em cada bloco novo. Enquanto a
// carga é nova (`novo`), nenhum bloco persiste — só edita `local` em memória;
// nada é criado no Dexie/servidor antes do clique em "Registrar".
async function persistirBloco() {
  if (props.novo) return true
  if (!entradaValida()) return false
  await props.persistir(local.value)
  return true
}
provide('persistirBloco', persistirBloco)
provide('trocarOperacao', trocarOperacao)
provide('calc', calc)
provide('itensCarga', itensCarga)
provide('sacasLiquido', sacasLiquido)
provide('avisoClassificacao', avisoClassificacao)
provide('finalizando', finalizando)

// Atalhos da página (F3 = principal com validação do q-form; F4 = imprimir).
defineExpose({
  submit: () => formRef.value?.submit(),
  imprimir,
})
</script>

<template>
  <q-form v-if="local" ref="formRef" @submit.prevent="onSubmit" @validation-error="onErroValidacao">
    <div class="q-pa-md q-gutter-y-md carga-form">
      <CargaBlocoOperacao :carga="local" :novo="novo" />
      <CargaBlocoPontos :carga="local" :novo="novo" />
      <CargaBlocoPesagem :carga="local" :novo="novo" />
      <CargaBlocoClassificacao :carga="local" :novo="novo" />

      <q-banner v-if="revisando" dense rounded class="bg-green-1 text-green-9">
        <template #avatar><q-icon name="fact_check" color="green-8" /></template>
        Confira a operação: <b>{{ fmt(calc.liquido) }} kg</b> líquidos ·
        {{ fmt(sacasLiquido, 1) }} sacas.
        <div class="text-caption">
          <b>Salvar</b> fecha o romaneio e libera a tela pro próximo caminhão.
        </div>
      </q-banner>

      <q-input
        v-model="local.observacao"
        label="Observação"
        type="textarea"
        autogrow
        outlined
        bg-color="white"
      />

      <!-- Espaço pros FABs não cobrirem o último campo -->
      <div class="q-py-xl" />
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row items-center q-gutter-sm">
        <q-btn v-if="!novo" fab icon="delete" color="negative" @click="cancelarCarga">
          <q-tooltip>Cancelar carga</q-tooltip>
        </q-btn>
        <q-btn v-if="finalizada" fab icon="print" color="accent" @click="imprimir">
          <q-tooltip>Imprimir romaneio (F4)</q-tooltip>
        </q-btn>
        <q-btn v-if="!novo && !finalizada" fab icon="save" color="grey-7" @click="salvarSemAvancar">
          <q-tooltip>Salvar sem avançar</q-tooltip>
        </q-btn>
        <q-btn
          type="submit"
          fab
          :icon="iconePrincipal"
          :label="rotuloPrincipal"
          :color="corPrincipal"
        >
          <q-tooltip>F3</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>
  </q-form>
</template>

<style scoped>
.carga-form {
  max-width: 1000px;
  margin: 0 auto;
}
</style>
