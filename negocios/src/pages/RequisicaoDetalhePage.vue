<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgHistorico from '@components/MgHistorico.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgInput from '@components/MgInput.vue'
import MgSelect from '@components/MgSelect.vue'
import ListaOpcoes from '@components/cobranca/ListaOpcoes.vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import {
  formataCodigo,
  formataNumeroInteligente,
  formataTelefone,
  formataTimestamp,
} from '@components/formatters'
import { requisicaoStore, statusDe, MOTIVOS, PRIORIDADES, PRIORIDADE } from 'stores/requisicao'
import { negocioStore } from 'stores/negocio'
import { RESULTADOS, MOTIVOS as MOTIVOS_ENTREGA } from 'stores/entrega'

// Detalhe da requisição (TASK-55, doc-6). O código de retirada inteiro não vem para a
// tela: sai só impresso na via do cliente; aqui aparecem as pontas (77XX82).
const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const sRequisicao = requisicaoStore()
const sNegocio = negocioStore()
const { requisicao: r } = storeToRefs(sRequisicao)

const status = computed(() => statusDe(r.value))
const prioridadeAtual = computed(() => PRIORIDADE[r.value?.prioridade] || PRIORIDADE[2])
const itensPedidos = computed(() => r.value?.itens || [])

// atendido x pedido de um item (na menor embalagem envolvida)
const situacaoItem = (i) => {
  const e = i.exibicao
  if (!e) return { cor: 'grey-7', texto: '' }
  const dif = Math.round((e.atendido - e.pedido) * 1000) / 1000
  const cor = dif === 0 ? 'green-7' : dif < 0 ? 'orange-8' : 'deep-orange-7'
  return {
    cor,
    texto: `${formataNumeroInteligente(e.atendido)} de ${formataNumeroInteligente(e.pedido)} ${e.sigla}`,
  }
}
const nomeMotivo = (m) => MOTIVOS.find((x) => x.value === m)?.label

const STATUS_NEGOCIO = { 1: 'Aberto', 2: 'Fechado', 3: 'Cancelado' }

// os negócios de separação ainda abertos: o que foi bipado neles só conta quando fecharem
const partesAbertas = computed(() => (r.value?.partes || []).filter((p) => p.codnegociostatus == 1))

// Separar (TASK-55): abre um negócio no PDV já ligado a esta requisição; o negócio novo vai
// ao servidor uma vez só, já ligado (o ligar é que sincroniza). Só no PDV da loja que atende,
// com a requisição aberta: quem pede não tem o botão.
async function separar() {
  // já em separação neste PDV: abre o negócio em andamento
  const andamento = await sNegocio.negocioAbertoDaRequisicao(r.value.codrequisicao)
  if (andamento) {
    router.push('/offline/' + andamento.uuid)
    return
  }
  const neg = await sNegocio.carregarPrimeiroVazioOuCriar(false)
  if (!neg) return
  if (await sNegocio.ligarRequisicao(r.value.codrequisicao, r.value.codestoquelocalrequisitante)) {
    router.push('/offline/' + neg.uuid)
  }
}

// justificar um item: motivo, observação e substituto (um dos produtos fora do pedido)
const dialogJustificar = ref(false)
const justificativa = ref({
  item: null,
  motivo: null,
  observacao: '',
  codprodutobarrasubstituto: null,
})
const substitutos = computed(() =>
  (r.value?.foradopedido || []).map((f) => ({
    value: f.produto.codprodutobarra,
    label: f.produto.descricao,
  })),
)
function abrirJustificar(i) {
  if (!r.value.podefinalizar) return
  justificativa.value = {
    item: i,
    motivo: i.motivo,
    observacao: i.observacao || '',
    codprodutobarrasubstituto: i.codprodutobarrasubstituto,
  }
  dialogJustificar.value = true
}
async function justificar() {
  const { item, ...dados } = justificativa.value
  if (await sRequisicao.justificarItem(item, dados)) dialogJustificar.value = false
}

// Finalizar (TASK-55): acabou a separação, o que não foi não vai mais. Item que não bateu
// sem motivo, ou fora do pedido sem destino, o servidor recusa dizendo qual justificar
function finalizar() {
  $q.dialog({
    title: 'Finalizar separação',
    message:
      'A separação acaba aqui: o que não foi não vai mais. Cada item que não bateu precisa estar justificado.',
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Finalizar', color: 'primary', flat: true },
  }).onOk(() => sRequisicao.finalizar(r.value))
}

// Prioridade (TASK-258): depois de criada, só muda aqui, com o motivo (vai para o Histórico)
const dialogPrioridade = ref(false)
const trocaPrioridade = ref({ prioridade: null, motivo: '' })
const opcoesPrioridade = PRIORIDADES.map((p) => ({ value: p.value, label: p.label }))
function abrirPrioridade() {
  trocaPrioridade.value = { prioridade: r.value.prioridade, motivo: '' }
  dialogPrioridade.value = true
}
async function salvarPrioridade() {
  const { prioridade, motivo } = trocaPrioridade.value
  if (await sRequisicao.trocarPrioridade(r.value, prioridade, motivo.trim())) {
    dialogPrioridade.value = false
  }
}

// Reabrir: o Gerente da loja que atende (ou Administrador) desfaz o Finalizar, com o motivo (vai para o Histórico)
const dialogReabrir = ref(false)
const motivoReabrir = ref('')
function abrirReabrir() {
  motivoReabrir.value = ''
  dialogReabrir.value = true
}
async function reabrir() {
  if (await sRequisicao.reabrir(r.value, motivoReabrir.value.trim())) {
    dialogReabrir.value = false
  }
}

// Entregar (TASK-55): retirada/entrega ao cliente com o código de cada requisição das partes
// (ou justificativa sem ele); reposição é o recebido da loja que pediu
const dialogEntregar = ref(false)
const entrega = ref({})
function abrirEntregar() {
  entrega.value = {
    codnegocios: r.value.partespendentes.map((p) => p.codnegocio),
    resultado: 'E',
    motivo: null,
    recebedor: r.value.nomes?.[0] || '',
    observacao: '',
    codigos: {},
    semCodigo: false,
    justificativa: '',
  }
  dialogEntregar.value = true
}
const pedeCodigo = computed(() => r.value?.comcodigo && entrega.value.resultado !== 'N')
// as requisições das partes escolhidas: o código de cada uma
const requisicoesDasPartes = computed(() => [
  ...new Set(
    (r.value?.partespendentes || [])
      .filter((p) => entrega.value.codnegocios?.includes(p.codnegocio))
      .flatMap((p) => p.requisicoes),
  ),
])
watch(
  () => entrega.value.resultado,
  () => (entrega.value.motivo = null),
)
async function entregar() {
  const { semCodigo, codigos, justificativa, ...dados } = entrega.value
  if (pedeCodigo.value) {
    if (semCodigo) dados.justificativa = justificativa
    else dados.codigos = codigos
  }
  if (await sRequisicao.entregar(r.value, dados)) dialogEntregar.value = false
}

// Entrega no endereço: o entregador da loja que pediu busca na loja que atende, que registra
// a retirada (quem buscou, sem código); depois a loja que pediu entrega ao cliente
const entregaNoEndereco = computed(() => r.value?.destino === 'E')
const dialogRetirar = ref(false)
const retirada = ref({})
function abrirRetirar() {
  retirada.value = {
    codnegocios: r.value.partesaretirar.map((p) => p.codnegocio),
    recebedor: '',
    observacao: '',
  }
  dialogRetirar.value = true
}
async function retirar() {
  if (await sRequisicao.retirar(r.value, retirada.value)) dialogRetirar.value = false
}

// Impressão em etapas, no formato do wizard de pagamento do PDV: a via, o
// formato, a janela do PDF (com o botão do cabeçalho, igual à DANFE da NFC-e) e,
// ao imprimir, a impressora. Cada loja imprime a sua via (a do cliente só na loja
// que pediu); quando só existe uma via, a primeira etapa não aparece.
const dialogImprimir = ref(false)
const etapa = ref(1)
const impressao = ref({ via: 'requisitada', formato: 'termica' })
const cardImprimir = ref(null)
const listaImprimir = ref(null)

const vias = computed(() =>
  [
    r.value?.ehrequisitante && {
      valor: 'requisitante',
      label: r.value.comcodigo ? 'Via do cliente' : 'Via da loja que pede',
      caption: r.value.comcodigo ? 'Com o código de retirada' : null,
      icone: 'key',
    },
    {
      valor: 'requisitada',
      label: 'Via de separação',
      caption: 'Com o QR da separação',
      icone: 'shopping_cart',
    },
  ]
    .filter(Boolean)
    .map((via, i) => ({ ...via, cor: 'blue-grey-5', tecla: i + 1 })),
)
const FORMATOS = [
  {
    valor: 'termica',
    label: 'Térmica 80 mm',
    icone: 'receipt_long',
    cor: 'blue-grey-5',
    tecla: 1,
  },
  { valor: 'a5', label: 'A5', icone: 'description', cor: 'blue-grey-5', tecla: 2 },
]

function abrirImprimir() {
  impressao.value = {
    via: r.value.ehrequisitante ? 'requisitante' : 'requisitada',
    formato: 'termica',
  }
  etapa.value = vias.value.length > 1 ? 1 : 2
  dialogImprimir.value = true
}

// o foco mora no card: é dele que as teclas descem para a lista
const focarImprimir = () => nextTick(() => cardImprimir.value?.$el?.focus())

function escolherImpressao(opcao) {
  if (etapa.value === 1) {
    impressao.value.via = opcao.valor
    etapa.value = 2
    return
  }
  impressao.value.formato = opcao.valor
  dialogImprimir.value = false
  sRequisicao.verPdf(r.value, impressao.value.via, impressao.value.formato, abrirImpressora)
}

function voltarImprimir() {
  if (etapa.value === 2 && vias.value.length > 1) {
    etapa.value = 1
    return
  }
  dialogImprimir.value = false
}

// A tecla que a lista usou não segue adiante: ao fechar, o q-dialog devolve o
// foco ao botão de imprimir do cabeçalho, e o mesmo Enter o clicaria, reabrindo
// a janela.
function teclaImprimir(e) {
  if (listaImprimir.value?.tecla(e)) {
    e.preventDefault()
    e.stopPropagation()
    return
  }
  if (e.key === 'Escape') voltarImprimir()
}

// Última etapa: em qual impressora sai o papel. Começa na impressora deste PDV,
// quando o dispositivo tem uma; o filtro, no cabeçalho, acha as outras pelo nome
// e só a lista rola (o mesmo desenho do TransferirCaixaDialog).
const cacheSelect = useSelectCacheStore()
const dialogImpressora = ref(false)
const cardImpressora = ref(null)
const listaImpressoras = ref(null)
const filtroImpressoraRef = ref(null)
const impressoras = ref([])
const filtroImpressora = ref('')
const impressorasFiltradas = computed(() => {
  const texto = filtroImpressora.value.trim().toLowerCase()
  if (!texto) return impressoras.value
  return impressoras.value.filter((i) =>
    [i.label, i.caption].some((c) => c && c.toLowerCase().includes(texto)),
  )
})

const TIPOS_IMPRESSORA = { T: 'Térmica', A: 'A5' }

// a lista chega antes da janela abrir: quem clicou vê o botão girando, não uma
// lista vazia (o cache deixa as próximas aberturas instantâneas)
// a impressora deste PDV vem no topo, marcada; as outras seguem pelo nome
async function abrirImpressora() {
  const itens = await cacheSelect.loadList('impressora_T,A', 'v1/select/impressora?tipo=T,A')
  const doPdv = (i) => i.value == sNegocio.padrao.codimpressora
  impressoras.value = [...itens.filter(doPdv), ...itens.filter((i) => !doPdv(i))].map((i) => ({
    valor: i.value,
    label: i.label,
    caption: [TIPOS_IMPRESSORA[i.tipo], doPdv(i) && 'Deste PDV'].filter(Boolean).join(' · '),
    icone: 'print',
    cor: 'blue-grey-5',
  }))
  filtroImpressora.value = ''
  dialogImpressora.value = true
}

// ao abrir: o foco no filtro (as setas sobem dele para o card e descem à lista);
// à mão porque a lista, ao montar, puxa o foco para o card
const focarImpressora = () => filtroImpressoraRef.value?.focus()

async function escolherImpressora(opcao) {
  dialogImpressora.value = false
  await sRequisicao.imprimir(r.value, impressao.value.via, opcao.valor)
}

// setas e Enter vão para a lista; o resto é digitação no filtro
function teclaImpressora(e) {
  if (e.key === 'Escape') {
    dialogImpressora.value = false
    return
  }
  if (!['ArrowUp', 'ArrowDown', 'Enter'].includes(e.key)) return
  if (!listaImpressoras.value?.tecla(e)) return
  e.preventDefault()
  e.stopPropagation()
}

// tirar a justificativa: achou o produto (tem saldo) depois de reabrir; o enviado a mais sai
// da requisição e o produto volta ao fora do pedido
function tirarJustificativa(i) {
  $q.dialog({
    title: 'Tirar a justificativa',
    message:
      Number(i.quantidade) === 0
        ? `${i.produto.descricao} sai da requisição e volta ao fora do pedido.`
        : `Tirar "${nomeMotivo(i.motivo)}" de ${i.produto.descricao}? O item volta a esperar a separação.`,
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Tirar', color: 'primary', flat: true },
  }).onOk(() => sRequisicao.tirarJustificativa(i))
}

function cancelar() {
  $q.dialog({
    title: 'Cancelar requisição',
    message: `Cancelar a requisição ${formataCodigo(r.value.codrequisicao)}?`,
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar requisição', color: 'red-5', flat: true },
  }).onOk(() => sRequisicao.cancelar(r.value))
}

watch(
  () => route.params.codrequisicao,
  (cod) => cod && sRequisicao.carregar(cod),
  { immediate: true },
)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div v-if="sRequisicao.carregando && !r" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <template v-else-if="r">
        <!-- CABEÇALHO -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <q-avatar :icon="status.icone" :color="status.cor" text-color="white" class="q-mr-md" />
            <div class="col" style="min-width: 0">
              <div class="text-h5 ellipsis">Requisição {{ formataCodigo(r.codrequisicao) }}</div>
              <div class="text-caption text-grey-7">
                {{ r.requisitante }} pede para {{ r.requisitada || 'Compras' }} ·
                {{ r.destinodescricao }}
              </div>
            </div>
            <div class="col-12 col-sm-auto row items-center justify-end no-wrap">
              <MgInfoCriacao :registro="r" />
              <MgHistorico tabela="tblrequisicao" :codigo="r.codrequisicao" />
              <q-btn
                v-if="r.podeprioridade"
                flat
                round
                size="sm"
                color="grey-7"
                icon="flag"
                @click="abrirPrioridade"
              >
                <q-tooltip>Prioridade</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podeseparar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="shopping_cart"
                @click="separar"
              >
                <q-tooltip>Separar (abre um negócio no PDV ligado a esta requisição)</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.poderetirar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="handshake"
                @click="abrirRetirar"
              >
                <q-tooltip>Retirado pelo entregador</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podeentregar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="local_shipping"
                @click="abrirEntregar"
              >
                <q-tooltip>{{ r.comcodigo ? 'Entregar ao cliente' : 'Recebido' }}</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podefinalizar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="done_all"
                @click="finalizar"
              >
                <q-tooltip>Finalizar separação</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podereabrir"
                flat
                round
                size="sm"
                color="grey-7"
                icon="lock_open"
                @click="abrirReabrir"
              >
                <q-tooltip>Reabrir (desfaz o Finalizar)</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="refresh"
                @click="sRequisicao.reprocessar(r)"
              >
                <q-tooltip>Reprocessar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podeeditar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                :to="`/requisicao/${r.codrequisicao}/editar`"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn flat round size="sm" color="grey-7" icon="print" @click="abrirImprimir">
                <q-tooltip>Imprimir</q-tooltip>
              </q-btn>
              <q-btn
                v-if="r.podecancelar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="block"
                @click="cancelar"
              >
                <q-tooltip>Cancelar requisição</q-tooltip>
              </q-btn>
            </div>
          </q-card-section>
          <q-separator />
          <q-list>
            <q-item>
              <q-item-section avatar>
                <q-icon :name="status.icone" :color="status.cor" />
              </q-item-section>
              <q-item-section>
                <q-item-label caption>Situação</q-item-label>
                <q-item-label>{{ status.label }}</q-item-label>
                <q-item-label v-if="r.cancelamento" caption>
                  Cancelada em {{ formataTimestamp(r.cancelamento) }} por
                  {{ r.usuariocancelamento }}
                </q-item-label>
                <q-item-label v-if="r.finalizacao" caption>
                  Finalizada em {{ formataTimestamp(r.finalizacao) }} por {{ r.usuariofinalizacao }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar>
                <q-icon name="flag" :color="prioridadeAtual.cor" />
              </q-item-section>
              <q-item-section>
                <q-item-label caption>Prioridade</q-item-label>
                <q-item-label>{{ prioridadeAtual.label }}</q-item-label>
              </q-item-section>
            </q-item>
            <!-- só as pontas do código (77XX82, o XX em cinza), com os dígitos espaçados (1,8x
                 a largura do dígito), para conferir com o do cliente; o inteiro sai só na via
                 do cliente e a entrega pede ele digitado -->
            <!-- na entrega no endereço quem atende não confere código: entrega ao entregador -->
            <q-item v-if="entregaNoEndereco">
              <q-item-section avatar>
                <q-icon name="delivery_dining" color="teal-7" />
              </q-item-section>
              <q-item-section>
                <q-item-label caption>Entrega no endereço</q-item-label>
                <q-item-label>
                  O entregador de {{ r.requisitante }} busca em {{ r.requisitada }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item
              v-if="
                r.comcodigo && r.codigoretiradamascarado && (r.ehrequisitante || !entregaNoEndereco)
              "
            >
              <q-item-section avatar><q-icon name="key" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label caption>
                  {{
                    r.ehrequisitante
                      ? 'Código de retirada (inteiro só na via do cliente)'
                      : 'Código de retirada (confira com o do cliente)'
                  }}
                </q-item-label>
                <q-item-label class="text-h5 text-weight-bold" style="letter-spacing: 0.8ch">
                  <span
                    v-for="(digito, i) in r.codigoretiradamascarado"
                    :key="i"
                    :class="digito === 'X' ? 'text-grey-5' : 'text-primary'"
                    >{{ digito }}</span
                  >
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="r.nomes?.length || r.pessoa">
              <q-item-section avatar><q-icon name="person" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Cliente</q-item-label>
                <q-item-label v-if="!r.nomes?.length">{{ r.pessoa }}</q-item-label>
                <q-item-label v-for="(nome, i) in r.nomes" :key="i">
                  {{ nome }}
                  <span class="text-grey-7">· {{ formataTelefone(r.telefones[i]) }}</span>
                </q-item-label>
                <q-item-label v-if="r.endereco" caption>{{ r.endereco }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-if="r.codnegocio" clickable :to="`/negocio/${r.codnegocio}`">
              <q-item-section avatar><q-icon name="shopping_cart" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Venda de origem</q-item-label>
                <q-item-label>{{ formataCodigo(r.codnegocio) }}</q-item-label>
              </q-item-section>
              <q-item-section side><q-icon name="chevron_right" /></q-item-section>
            </q-item>
            <q-item v-if="r.observacoes">
              <q-item-section avatar><q-icon name="notes" color="grey-7" /></q-item-section>
              <q-item-section>
                <q-item-label caption>Observações</q-item-label>
                <q-item-label style="white-space: pre-line">{{ r.observacoes }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- ITENS -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none"
            >Produtos pedidos</q-card-section
          >
          <q-item
            v-for="p in partesAbertas"
            :key="p.codnegocio"
            clickable
            :to="`/negocio/${p.codnegocio}`"
            class="text-blue-grey-8"
          >
            <q-item-section avatar><q-icon name="hourglass_top" /></q-item-section>
            <q-item-section>
              <q-item-label>Em separação no negócio {{ formataCodigo(p.codnegocio) }}</q-item-label>
              <q-item-label caption>Os produtos contam quando ele for fechado</q-item-label>
            </q-item-section>
            <q-item-section side><q-icon name="chevron_right" /></q-item-section>
          </q-item>
          <q-list separator>
            <q-item
              v-for="i in itensPedidos"
              :key="i.codrequisicaoitem"
              :clickable="r.podefinalizar"
              @click="abrirJustificar(i)"
            >
              <q-item-section avatar>
                <q-avatar rounded size="48px">
                  <img v-if="i.produto.imagem" :src="i.produto.imagem" />
                  <q-icon v-else name="inventory_2" color="grey-5" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ i.produto.descricao }}</q-item-label>
                <q-item-label caption>{{ i.produto.barras }}</q-item-label>
                <q-item-label caption v-if="i.motivo" class="text-deep-orange-7">
                  {{ nomeMotivo(i.motivo) }}
                  <template v-if="i.substituto">
                    · substituído por {{ i.substituto.descricao }}</template
                  >
                  <template v-if="i.observacao"> · {{ i.observacao }}</template>
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-item-label class="text-subtitle1">
                  {{ formataNumeroInteligente(i.quantidade) }} {{ i.produto.sigla }}
                </q-item-label>
                <q-item-label caption :class="'text-' + situacaoItem(i).cor">
                  {{ situacaoItem(i).texto }}
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="r.podefinalizar && i.motivo">
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="backspace"
                  @click.stop="tirarJustificativa(i)"
                >
                  <q-tooltip>Tirar a justificativa (achou o produto)</q-tooltip>
                </q-btn>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- FORA DO PEDIDO -->
        <q-card v-if="r.foradopedido?.length" flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none"
            >Fora do pedido</q-card-section
          >
          <q-list separator>
            <q-item v-for="(f, indice) in r.foradopedido" :key="indice">
              <q-item-section>
                <q-item-label>{{ f.produto.descricao }}</q-item-label>
                <q-item-label caption>
                  {{ formataNumeroInteligente(f.quantidade) }} {{ f.sigla }} no negócio
                  {{ formataCodigo(f.codnegocio) }}
                  <template v-if="f.resolvido"> · substituto de um item</template>
                </q-item-label>
              </q-item-section>
              <q-item-section side v-if="r.podefinalizar && !f.resolvido">
                <q-btn
                  flat
                  color="primary"
                  label="Enviado a mais"
                  @click="sRequisicao.incluirEnviadoAMais(r, f.produto.codprodutobarra)"
                />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card>

        <!-- PARTES (negócios de separação) -->
        <q-card v-if="r.partes?.length" flat bordered class="q-mb-md">
          <q-card-section class="text-grey-9 text-overline q-pb-none">Partes</q-card-section>
          <q-list separator>
            <q-item
              v-for="p in r.partes"
              :key="p.codnegocio"
              clickable
              :to="`/negocio/${p.codnegocio}`"
            >
              <q-item-section>
                <q-item-label>Negócio {{ formataCodigo(p.codnegocio) }}</q-item-label>
                <q-item-label caption>
                  {{ STATUS_NEGOCIO[p.codnegociostatus] }} · {{ formataTimestamp(p.lancamento) }}
                  <template v-if="entregaNoEndereco">
                    <template v-if="p.entregue">
                      · retirado pelo entregador {{ formataTimestamp(p.entregue) }}</template
                    >
                    <template v-if="p.entreguecliente">
                      · entregue {{ formataTimestamp(p.entreguecliente) }}</template
                    >
                  </template>
                  <template v-else-if="p.entregue">
                    · entregue {{ formataTimestamp(p.entregue) }}</template
                  >
                </q-item-label>
              </q-item-section>
              <q-item-section side><q-icon name="chevron_right" /></q-item-section>
            </q-item>
          </q-list>
        </q-card>
      </template>
    </div>

    <q-dialog v-model="dialogRetirar">
      <q-card flat style="width: 600px; max-width: 95vw">
        <q-form @submit.prevent="retirar">
          <q-card-section class="text-grey-9 text-overline text-uppercase">
            Retirado pelo entregador
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <div class="text-caption text-grey-7">Partes</div>
                <q-option-group
                  v-model="retirada.codnegocios"
                  type="checkbox"
                  :options="
                    (r?.partesaretirar || []).map((p) => ({
                      value: p.codnegocio,
                      label: `Negócio ${formataCodigo(p.codnegocio)} · ${formataTimestamp(p.lancamento)}`,
                    }))
                  "
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="retirada.recebedor"
                  :label="`Quem buscou (entregador de ${r?.requisitante})`"
                  maxlength="100"
                  autofocus
                  :rules="[(v) => !!(v || '').trim() || 'Informe quem buscou']"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="retirada.observacao"
                  label="Observação"
                  maxlength="500"
                  autogrow
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn
              flat
              label="Registrar"
              color="primary"
              type="submit"
              :disable="!retirada.codnegocios?.length"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogEntregar">
      <q-card flat style="width: 600px; max-width: 95vw">
        <q-form @submit.prevent="entregar">
          <q-card-section class="text-grey-9 text-overline text-uppercase">
            {{ r?.comcodigo ? 'Entregar ao cliente' : 'Recebido' }}
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <div class="text-caption text-grey-7">Partes</div>
                <q-option-group
                  v-model="entrega.codnegocios"
                  type="checkbox"
                  :options="
                    (r?.partespendentes || []).map((p) => ({
                      value: p.codnegocio,
                      label: `Negócio ${formataCodigo(p.codnegocio)} · ${formataTimestamp(p.lancamento)}`,
                    }))
                  "
                />
              </div>
              <div class="col-12">
                <q-option-group v-model="entrega.resultado" :options="RESULTADOS" type="radio" />
              </div>
              <div class="col-12" v-if="MOTIVOS_ENTREGA[entrega.resultado]">
                <div class="text-caption text-grey-7">Motivo</div>
                <q-option-group
                  v-model="entrega.motivo"
                  :options="MOTIVOS_ENTREGA[entrega.resultado]"
                  type="radio"
                  inline
                />
              </div>
              <template v-if="pedeCodigo">
                <template v-if="!entrega.semCodigo">
                  <div class="col-12 col-sm-6" v-for="cod in requisicoesDasPartes" :key="cod">
                    <MgInput
                      v-model="entrega.codigos[cod]"
                      :label="`Código de retirada ${formataCodigo(cod)}`"
                      maxlength="6"
                      inputmode="numeric"
                      autofocus
                    />
                  </div>
                </template>
                <div class="col-12">
                  <q-toggle v-model="entrega.semCodigo" label="O cliente está sem o código" />
                </div>
                <div class="col-12" v-if="entrega.semCodigo">
                  <MgInput
                    v-model="entrega.justificativa"
                    label="Justificativa (quem conferiu e como)"
                    maxlength="200"
                    :rules="[(v) => (v || '').trim().length >= 15 || 'Mínimo 15 letras']"
                  />
                </div>
              </template>
              <div class="col-12" v-if="entrega.resultado !== 'N'">
                <MgInput
                  v-model="entrega.recebedor"
                  label="Quem recebeu"
                  maxlength="100"
                  :rules="[(v) => !!v || 'Informe quem recebeu']"
                />
              </div>
              <div class="col-12">
                <MgInput v-model="entrega.observacao" label="Observação" maxlength="500" autogrow />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn
              flat
              label="Registrar"
              color="primary"
              type="submit"
              :disable="
                !entrega.codnegocios?.length ||
                (!!MOTIVOS_ENTREGA[entrega.resultado] && !entrega.motivo)
              "
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogPrioridade">
      <q-card flat style="width: 400px; max-width: 95vw">
        <q-form @submit.prevent="salvarPrioridade">
          <q-card-section class="text-h6">Prioridade</q-card-section>
          <q-card-section class="q-pt-none">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-option-group
                  v-model="trocaPrioridade.prioridade"
                  :options="opcoesPrioridade"
                  type="radio"
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="trocaPrioridade.motivo"
                  label="Motivo"
                  maxlength="300"
                  autofocus
                  :rules="[(v) => !!v?.trim() || 'Informe o motivo']"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Gravar" color="primary" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogReabrir">
      <q-card flat style="width: 400px; max-width: 95vw">
        <q-form @submit.prevent="reabrir">
          <q-card-section class="text-h6">Reabrir</q-card-section>
          <q-card-section class="q-pt-none">
            <div class="text-body2 text-grey-8 q-mb-md">
              Desfaz o Finalizar: a requisição volta a pendente e pode receber outro negócio. As
              justificativas dos itens ficam.
            </div>
            <MgInput
              v-model="motivoReabrir"
              label="Motivo"
              maxlength="300"
              autofocus
              :rules="[(v) => !!v?.trim() || 'Informe o motivo']"
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Reabrir" color="primary" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogJustificar">
      <q-card flat style="width: 600px; max-width: 95vw">
        <q-form @submit.prevent="justificar">
          <q-card-section class="text-grey-9 text-overline text-uppercase">
            {{ justificativa.item?.produto.descricao }}
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <div class="text-caption text-grey-7">Motivo</div>
                <q-option-group v-model="justificativa.motivo" :options="MOTIVOS" type="radio" />
              </div>
              <div class="col-12">
                <MgSelect
                  v-model="justificativa.codprodutobarrasubstituto"
                  :options="substitutos"
                  label="Substituído por (produto que foi no lugar)"
                  emit-value
                  map-options
                  clearable
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="justificativa.observacao"
                  label="Observação"
                  maxlength="500"
                  autogrow
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" tabindex="-1" v-close-popup />
            <q-btn flat label="Gravar" color="primary" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogImprimir" no-esc-dismiss @show="focarImprimir">
      <q-card
        ref="cardImprimir"
        flat
        tabindex="0"
        class="no-outline"
        style="width: 480px; max-width: 95vw"
        @keydown="teclaImprimir"
      >
        <q-card-section class="q-pb-none">
          <div class="text-grey-9 text-overline text-uppercase">Imprimir</div>
          <div class="text-caption text-grey-7">
            {{ etapa === 1 ? 'Qual via' : 'Formato do papel' }}
          </div>
        </q-card-section>
        <q-card-section class="q-pt-sm">
          <ListaOpcoes
            :key="etapa"
            ref="listaImprimir"
            :opcoes="etapa === 1 ? vias : FORMATOS"
            :inicial="etapa === 1 ? impressao.via : impressao.formato"
            @escolher="escolherImpressao"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            :label="etapa === 2 && vias.length > 1 ? 'Voltar (Esc)' : 'Cancelar (Esc)'"
            tabindex="-1"
            @click="voltarImprimir"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogImpressora" no-esc-dismiss @show="focarImpressora">
      <q-card
        ref="cardImpressora"
        flat
        tabindex="0"
        class="no-outline column no-wrap"
        style="width: 480px; max-width: 95vw; height: 80vh"
        @keydown="teclaImpressora"
      >
        <!-- CABEÇALHO: título e filtro; não rola com a lista -->
        <q-card-section>
          <div class="text-grey-9 text-overline text-uppercase">Imprimir</div>
          <div class="text-caption text-grey-7">Em qual impressora</div>
          <MgInput
            ref="filtroImpressoraRef"
            v-model="filtroImpressora"
            label="Filtrar impressora"
            autofocus
            class="q-mt-sm"
          >
            <template #prepend>
              <q-icon name="search" />
            </template>
          </MgInput>
        </q-card-section>
        <q-separator inset />

        <!-- só a lista rola: as bordas arredondadas do card ficam inteiras -->
        <q-card-section class="col scroll">
          <ListaOpcoes
            v-if="impressorasFiltradas.length"
            ref="listaImpressoras"
            :opcoes="impressorasFiltradas"
            :inicial="sNegocio.padrao.codimpressora"
            @escolher="escolherImpressora"
          />
          <div v-else class="text-grey-6 text-italic q-pa-sm">Nada encontrado</div>
        </q-card-section>

        <q-separator inset />
        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            label="Cancelar (Esc)"
            tabindex="-1"
            @click="dialogImpressora = false"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
