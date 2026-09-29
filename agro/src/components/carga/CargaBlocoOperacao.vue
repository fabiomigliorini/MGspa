<script setup>
// Bloco "Operação": o que a carga É (tipo de romaneio + safra) e a ficha do
// caminhão. O card só exibe; tudo se edita no modal do `+`/lápis, numa ÚNICA
// grade (`row`) que sempre fecha as linhas em 12 — nunca sobra nem falta
// coluna, então nada empurra, nem espreme, nem deixa vazio:
//   Operação(4) Safra(4) Chegada(4)                          = 12
//   SAIDA:     Placa(4) Reboque1(4) Reboque2(4)               = 12
//              Motorista(12, sozinho — o que vem depois (CPF…) começa do zero)
//   demais:    Placa(6) Motorista(6)                          = 12
//   sempre:    CPF(4) Nome(8) | Telefone(4) Endereço(8) | Bairro(4) CEP(4) Cidade(4)
// (as larguras do CargaMotoristaCampos já fecham 12 sozinhas — só dependem de
// começar do zero, o que as contas acima garantem). Largura de Placa/Motorista
// muda com `mostrarReboque`: dá pra trocar a Operação no próprio modal e a
// grade realinha sozinha. Reboque é coisa de caminhão saindo (bitrem/rodotrem)
// — some do formulário e é zerado ao salvar quando a operação não é SAIDA.
// Trocar a operação depois da 1ª pesagem pede confirmação, e quem decide isso
// é o CargaForm (`trocarOperacao`), não este bloco.
//
// Placa e motorista podem estar cadastrados ou não (como o CPF na nota do
// /negocios): placa sem cadastro fica só em texto; motorista sem cadastro
// grava na carga CPF, nome, telefone e endereço. Os dois podem ser cadastrados
// daqui mesmo quando há conexão. As ações "sem cadastro"/"cadastrar" são
// OPÇÕES dos selects (não um slot solto) pra que as setas cheguem nelas.
import { ref, computed, inject, nextTick } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { useCargaStore } from 'src/stores/carga'
import { useSincronizacaoStore } from 'src/stores/sincronizacao'
import {
  agoraLocal,
  cargaFinalizada,
  sentidoMeta,
  PLACA_RE,
  normalizarPlaca,
  CAMPOS_MOTORISTA_SEM_CADASTRO,
} from 'src/utils/carga'
import { cadastrarMotorista } from 'src/utils/motorista'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { formataTimestamp, formataCpf, formataTelefone } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgInputData from '@components/MgInputData.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import CaminhaoDialog from 'components/CaminhaoDialog.vue'
import SelectSentido from './SelectSentido.vue'
import CargaMotoristaCampos from './CargaMotoristaCampos.vue'

const props = defineProps({
  carga: { type: Object, required: true },
  novo: { type: Boolean, default: false },
})

const $q = useQuasar()
const store = useCargaStore()
const { veiculosAtivos, safras, safrasAtivas } = storeToRefs(store)
const { online } = storeToRefs(useSincronizacaoStore())

// Provido pelo CargaForm.vue — reaproveita o MESMO caminho de persistência que
// "salvar sem avançar" já usava (erro tratado na página); este bloco não
// importa a store pra persistir, só pra dado de apoio (placa/veículo/safra).
const persistirBloco = inject('persistirBloco')
// Troca do tipo de romaneio — a confirmação mora LÁ (CargaForm), não aqui: a
// guarda não pode depender de quem chama. Devolve se aplicou.
const trocarOperacao = inject('trocarOperacao')
// Salvou: o CargaForm abre o próximo modal que falta (Origem/Destino, balança).
const proximoPasso = inject('proximoPasso')

// Indireção (padrão ContratoForm/SafraForm): muta o objeto reativo compartilhado
// sem disparar vue/no-mutating-props — `carga` é a MESMA referência que o
// CargaForm.vue conhece como `local`, a mutação já é o mecanismo de sincronismo.
const carga = computed(() => props.carga)

// Máximo do campo de chegada = agora (não deixa lançar no futuro).
const dataMax = agoraLocal()

// Finalizada: o romaneio fechado não muda mais de operação nem de safra
// (TASK-141 abriu a troca ATÉ finalizar, não depois).
const finalizada = computed(() => cargaFinalizada(carga.value))

// Nada do caminhão informado: o botão do bloco é o + de "informar", não o lápis.
// Operação e chegada não contam — nascem preenchidas com a carga.
const temDados = computed(
  () =>
    !!(
      carga.value.placa ||
      carga.value.placacarreta ||
      carga.value.placacarreta2 ||
      carga.value.motorista
    ),
)
// "REB1234 / REB5678" — só o(s) que existir(em); "—" se nenhum.
const reboques = computed(() =>
  [carga.value.placacarreta, carga.value.placacarreta2].filter(Boolean).join(' / '),
)
// Reboque só existe na Expedição (bitrem/rodotrem saindo da fazenda).
const ehExpedicao = computed(() => carga.value.sentido === 'SAIDA')

const sentido = computed(() => sentidoMeta(carga.value.sentido))
const nomeSafra = (codsafra) => safras.value.find((s) => s.codsafra === codsafra)?.safra || null
const motoristaSemCadastro = computed(
  () => !!carga.value.motorista && !carga.value.codpessoamotorista,
)
// Legenda do motorista sem cadastro no card: "CPF … · telefone · sem cadastro".
const legendaMotorista = computed(() =>
  [
    carga.value.cpfmotorista && `CPF ${formataCpf(carga.value.cpfmotorista)}`,
    carga.value.telefonemotorista && formataTelefone(carga.value.telefonemotorista),
    'sem cadastro',
  ]
    .filter(Boolean)
    .join(' · '),
)

// ---- Modal ----
const dialogAberto = ref(false)
const salvando = ref(false)
const edicao = ref({})
// 'pesquisa' = select de pessoa; 'novo' = motorista sem cadastro ou a cadastrar
// (CPF, nome, telefone, endereço — CargaMotoristaCampos).
const modoMotorista = ref('pesquisa')
const formRef = ref(null)
const safraRef = ref(null)
const camposMotoristaRef = ref(null)
const placaRef = ref(null)
const reboque1Ref = ref(null)

// Qualquer q-select deste modal (Operação, Safra, Placa, Motorista), com a
// lista FECHADA: Enter salva o modal em vez do padrão do Quasar (reabrir a
// lista ou reprocessar o texto digitado como "novo valor" — o Quasar SEMPRE
// intercepta e para a propagação do Enter, então um listener lá fora do campo
// nunca pegaria essa tecla; tem que ser aqui, no próprio select). `qKeyEvent`
// faz o Quasar pular o tratamento dele — o emit do keydown vem antes; mesmo
// truque do MgInputData. Com a lista ABERTA, sai sem mexer: Enter continua
// escolhendo a opção destacada (busca da Placa, "usar sem cadastro" etc.).
// `sel` (só Safra) também abre a lista no ↑, como o ↓ do Quasar já faz.
function tecladoSelect(e, sel) {
  if (e.target?.getAttribute('aria-expanded') === 'true') return
  if (e.key === 'ArrowUp' && sel) {
    e.qKeyEvent = true
    e.preventDefault()
    sel.showPopup()
  } else if (e.key === 'Enter') {
    e.qKeyEvent = true
    formRef.value?.submit(e)
  }
}
// A busca de pessoa falhou por rede mesmo com `online` ainda true (o flag só
// muda no ciclo de sync): trata como offline dali em diante neste modal.
const semConexao = ref(false)
const offline = computed(() => !online.value || semConexao.value)

const placaOptions = ref([])
const placaBusca = ref('')
const cadastroCaminhao = ref(false)

const semCadastroVazio = () =>
  Object.fromEntries(CAMPOS_MOTORISTA_SEM_CADASTRO.map((c) => [c, null]))

function abrir() {
  edicao.value = {
    sentido: carga.value.sentido,
    codsafra: carga.value.codsafra,
    data: carga.value.data,
    // Placa antiga pode ter sido gravada com hífen ("ABC-1234"): a máscara
    // mostraria limpa e a regra reprovaria um valor que parece certo.
    placa: normalizarPlaca(carga.value.placa),
    codveiculo: carga.value.codveiculo,
    placacarreta: normalizarPlaca(carga.value.placacarreta),
    placacarreta2: normalizarPlaca(carga.value.placacarreta2),
    codpessoamotorista: carga.value.codpessoamotorista,
    motorista: carga.value.motorista,
    ...Object.fromEntries(CAMPOS_MOTORISTA_SEM_CADASTRO.map((c) => [c, carga.value[c] ?? null])),
    cadastrarMotorista: false,
  }
  // Motorista já gravado sem cadastro abre direto nos campos dele (carga antiga
  // sem CPF/telefone/endereço passa a pedir o que falta). Sem motorista, abre
  // na busca — mesmo offline: motorista não é obrigatório, e a busca que falha
  // oferece "usar sem cadastro".
  modoMotorista.value =
    !edicao.value.codpessoamotorista && edicao.value.motorista ? 'novo' : 'pesquisa'
  // Offline o select não consegue buscar o nome do motorista cadastrado pelo
  // id — sem isto o campo abriria vazio. Semeia o cache com o nome da carga.
  const cache = useSelectCacheStore()
  if (
    !online.value &&
    edicao.value.codpessoamotorista &&
    edicao.value.motorista &&
    !cache.getById('pessoa', edicao.value.codpessoamotorista)
  ) {
    cache.mergeById('pessoa', [
      { value: edicao.value.codpessoamotorista, label: edicao.value.motorista },
    ])
  }
  semConexao.value = false
  inativoAvisado = null
  placaBusca.value = edicao.value.placa || ''
  dialogAberto.value = true
}
// Reativo ao sentido EM EDIÇÃO (não ao já salvo): trocar pra Expedição no
// próprio modal já revela os campos, sem precisar salvar e reabrir.
const mostrarReboque = computed(() => edicao.value.sentido === 'SAIDA')
// O CargaForm abre este modal quando o Registrar esbarra na safra em branco.
defineExpose({ abrir })

// Safra: as ativas, mais a da carga se ela já estiver inativa (senão o select
// ficaria em branco numa carga antiga).
const opcoesSafra = computed(() => {
  const ativas = safrasAtivas.value
  if (!carga.value.codsafra || ativas.some((s) => s.codsafra === carga.value.codsafra)) {
    return ativas
  }
  const atual = safras.value.find((s) => s.codsafra === carga.value.codsafra)
  return atual ? [...ativas, atual] : ativas
})

const culturaDaSafra = (codsafra) => safras.value.find((s) => s.codsafra === codsafra)?.codcultura
const temLeitura = computed(() =>
  (carga.value.classificacao || []).some(
    (c) => c.leitura !== null && c.leitura !== undefined && c.leitura !== '',
  ),
)
// A safra da carga manda nos talhões de origem e nos parâmetros da
// classificação — trocar não pode deixar os dois apontando pra outra safra.
function regraSafra(codsafra) {
  if (!codsafra) return 'Informe a safra.'
  for (const p of carga.value.pontos || []) {
    if (p.papel !== 'ORIGEM' || p.contatipo !== 'PLANTIO' || !p.codplantio) continue
    const plantio = store.plantioPorId(p.codplantio)
    if (plantio && plantio.codsafra !== codsafra) {
      return `O talhão ${plantio.rotulo} é da safra ${nomeSafra(plantio.codsafra)} — troque a origem antes.`
    }
  }
  if (
    temLeitura.value &&
    carga.value.codsafra &&
    culturaDaSafra(codsafra) !== culturaDaSafra(carga.value.codsafra)
  ) {
    return `Carga já classificada como ${store.culturaDaCarga(carga.value)?.cultura || 'outra cultura'}.`
  }
  return true
}

// ---- Placa ----
// Placa completa que não bate com nenhum caminhão cadastrado vira duas opções
// no fim da lista: usar assim mesmo (só texto na carga) ou cadastrar.
const ACAO_PLACA = { semcadastro: '__placa:semcadastro', cadastrar: '__placa:cadastrar' }
function filtrarPlaca(val, update) {
  placaBusca.value = normalizarPlaca(val) || ''
  update(() => {
    const termo = placaBusca.value
    const achados = veiculosAtivos.value
      .filter((v) => (v.placa || '').toUpperCase().includes(termo))
      .slice(0, 50)
      .map((v) => ({ label: v.placa, value: v.placa }))
    const acoes = []
    if (PLACA_RE.test(termo) && !achados.some((o) => o.value === termo)) {
      acoes.push({
        label: `Usar “${termo}” sem cadastro`,
        value: ACAO_PLACA.semcadastro,
        acao: true,
        icon: 'edit_note',
      })
      if (online.value) {
        acoes.push({
          label: `Cadastrar “${termo}”`,
          value: ACAO_PLACA.cadastrar,
          acao: true,
          icon: 'add',
          color: 'primary',
        })
      }
    }
    placaOptions.value = [...achados, ...acoes]
  })
}
function resolverPlaca(placa) {
  const p = normalizarPlaca(placa)
  edicao.value.placa = p
  edicao.value.codveiculo = p
    ? veiculosAtivos.value.find((v) => (v.placa || '').toUpperCase() === p)?.codveiculo || null
    : null
  // Sem isto o blur logo depois reaplicaria o texto parcial digitado ("QCJ")
  // por cima da placa escolhida na lista ("QCJ8I48").
  placaBusca.value = p || ''
}
// Placa escolhida (clique ou Enter): o foco segue pra Carreta — o operador já
// decidiu, não precisa de outro clique. Só se o foco ainda estiver na placa:
// com Tab o navegador já leva pra Carreta sozinho, e focar de novo aqui faria o
// Tab pular um campo.
function onPlacaEscolhida(val) {
  if (val === ACAO_PLACA.cadastrar) {
    cadastroCaminhao.value = true
    return
  }
  resolverPlaca(val === ACAO_PLACA.semcadastro ? placaBusca.value : val)
  if (!edicao.value.placa) return
  setTimeout(() => {
    if (placaRef.value?.$el?.contains(document.activeElement)) reboque1Ref.value?.focus()
  })
}
// Saiu do campo sem escolher na lista: vale o que está ESCRITO nele (não o
// último termo filtrado — depois de um Esc o Quasar volta o texto pra placa
// atual, e o termo cancelado não pode trocar a placa).
function onPlacaBlur() {
  const texto = normalizarPlaca(placaRef.value?.$el?.querySelector('input')?.value)
  if (texto && texto !== edicao.value.placa) resolverPlaca(texto)
}
function limparPlaca() {
  resolverPlaca(null)
}
async function onCaminhaoCriado(veiculo) {
  await store.adicionarVeiculo(veiculo)
  edicao.value.codveiculo = veiculo.codveiculo
  edicao.value.placa = veiculo.placa
  placaBusca.value = veiculo.placa
}
const regraPlaca = (v) => !v || PLACA_RE.test(v) || 'Placa inválida (ABC1234 ou ABC1D23).'

// ---- Motorista ----
function onMotoristaSelect(opt) {
  Object.assign(edicao.value, { motorista: opt?.label || null, ...semCadastroVazio() })
}
function onMotoristaClear() {
  edicao.value.motorista = null
}
// Busca sem resultado: as duas saídas viram opções do select (setas + Enter).
// Busca que FALHOU (sem rede) só oferece o "sem cadastro".
function acoesMotorista(busca, { erro = false } = {}) {
  if ((busca || '').length < 2) return []
  const acoes = [
    { acao: 'semcadastro', label: `Usar “${busca}” sem cadastro, só nesta carga`, icon: 'edit_note' },
  ]
  if (!offline.value && !erro) {
    acoes.push({
      acao: 'cadastrar',
      label: `Cadastrar “${busca}” como motorista`,
      icon: 'person_add',
      color: 'primary',
    })
  }
  return acoes
}
// Texto da busca que não achou ninguém: dígitos viram CPF, o resto vira nome.
function separarBusca(busca) {
  const b = (busca || '').trim()
  return /\d/.test(b) && !/[a-zA-Z]/.test(b)
    ? { cpfmotorista: b.replace(/\D/g, ''), motorista: null }
    : { cpfmotorista: null, motorista: b || null }
}
async function onAcaoMotorista(acao, busca, { erro = false } = {}) {
  if (erro) semConexao.value = true
  Object.assign(edicao.value, {
    codpessoamotorista: null,
    ...semCadastroVazio(),
    ...separarBusca(busca),
    cadastrarMotorista: acao === 'cadastrar',
  })
  modoMotorista.value = 'novo'
  await nextTick()
  camposMotoristaRef.value?.focar()
}
function voltarPesquisa() {
  Object.assign(edicao.value, {
    codpessoamotorista: null,
    motorista: null,
    ...semCadastroVazio(),
    cadastrarMotorista: false,
  })
  modoMotorista.value = 'pesquisa'
}
// Motorista que já existe no cadastro (achado pelo CPF ou recém-cadastrado):
// volta pro select com ele escolhido e os dados "sem cadastro" limpos.
function selecionarPessoa(p) {
  Object.assign(edicao.value, {
    codpessoamotorista: p.codpessoa,
    motorista: p.fantasia || p.pessoa,
    ...semCadastroVazio(),
    cadastrarMotorista: false,
  })
  modoMotorista.value = 'pesquisa'
}
// CPF de quem já está no cadastro. Ativo: usa a pessoa. Inativo: não dá pra
// escolher no select nem cadastrar de novo — segue sem cadastro, avisando.
// A verificação pode chegar duas vezes (blur + Salvar): avisa uma só.
let inativoAvisado = null
function onMotoristaExistente(p) {
  const nome = p.fantasia || p.pessoa
  if (p.inativo) {
    edicao.value.cadastrarMotorista = false
    if (inativoAvisado === p.codpessoa) return
    inativoAvisado = p.codpessoa
    $q.notify({
      type: 'warning',
      message: `CPF de ${nome}, que está INATIVO no cadastro.`,
      caption: 'O motorista fica sem cadastro nesta carga. Para usar o cadastro, reative no app Pessoas.',
    })
    return
  }
  if (modoMotorista.value === 'pesquisa' && edicao.value.codpessoamotorista === p.codpessoa) return
  selecionarPessoa(p)
  $q.notify({ type: 'info', message: `CPF já cadastrado: ${nome} — selecionado.` })
}
async function cadastrarNoServidor() {
  try {
    const p = await cadastrarMotorista(edicao.value)
    selecionarPessoa(p)
    $q.notify({ type: 'positive', message: `Motorista ${p.fantasia} cadastrado.` })
    return true
  } catch (e) {
    const msg = e?.response?.data?.message || 'Falha ao cadastrar o motorista.'
    $q.notify({ type: 'negative', message: msg })
    return false
  }
}

async function salvar() {
  salvando.value = true
  try {
    // CPF digitado por último: a checagem do blur pode não ter voltado (ou nem
    // ter rodado, com Enter). Confere aqui antes de gravar "sem cadastro" ou
    // tentar cadastrar alguém que já existe.
    if (modoMotorista.value === 'novo') {
      const existente = await camposMotoristaRef.value?.verificarCpf()
      if (existente) onMotoristaExistente(existente)
    }
    // Cadastro do motorista antes de tudo: se o servidor recusar (CPF já
    // cadastrado, sem conexão), nada da carga foi mexido e o modal fica aberto.
    if (modoMotorista.value === 'novo' && edicao.value.cadastrarMotorista) {
      if (!(await cadastrarNoServidor())) return
    }
    // Troca de operação: se o operador desistir na confirmação, o modal fica.
    if (!(await trocarOperacao(edicao.value.sentido))) return
    const semCadastro = modoMotorista.value === 'novo'
    Object.assign(carga.value, {
      codsafra: edicao.value.codsafra,
      data: edicao.value.data,
      placa: edicao.value.placa,
      codveiculo: edicao.value.codveiculo,
      // Reboque só é coisa de Expedição — some do formulário e não grava se a
      // operação salva não for SAIDA (mesmo que o campo tenha ficado com texto
      // de antes de trocar o tipo de romaneio no próprio modal).
      placacarreta: mostrarReboque.value ? edicao.value.placacarreta : null,
      placacarreta2: mostrarReboque.value ? edicao.value.placacarreta2 : null,
      codpessoamotorista: semCadastro ? null : edicao.value.codpessoamotorista,
      motorista: edicao.value.motorista,
      ...Object.fromEntries(
        CAMPOS_MOTORISTA_SEM_CADASTRO.map((c) => [c, semCadastro ? edicao.value[c] : null]),
      ),
    })
    // Fecha mesmo quando `persistirBloco` recusa (origem/destino incompleto —
    // comum logo depois de trocar a operação): o aviso já saiu e a correção é
    // no bloco de Origem/Destino, não aqui; a gravação vem junto com ela.
    await persistirBloco()
    dialogAberto.value = false
    proximoPasso('operacao')
  } catch {
    // erro já notificado por quem persiste (CargaPage) — mantém o dialog aberto
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-card flat bordered>
    <q-card-section>
      <div class="row items-center q-mb-sm">
        <div class="text-subtitle2 text-grey-8">Operação</div>
        <q-space />
        <q-btn
          flat
          round
          dense
          size="sm"
          :icon="temDados ? 'edit' : 'add'"
          :color="temDados ? 'grey-7' : 'primary'"
          @click="abrir"
        />
      </div>

      <div class="row q-col-gutter-md">
        <div class="col-6 col-sm-4">
          <div class="text-caption text-grey-6">Operação</div>
          <div class="row items-center no-wrap">
            <q-icon :name="sentido.icon" :color="sentido.color" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">{{ sentido.label }}</span>
          </div>
        </div>
        <div class="col-6 col-sm-4">
          <div class="text-caption text-grey-6">Safra</div>
          <div class="row items-center no-wrap">
            <q-icon name="eco" color="light-green-8" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium ellipsis">
              {{ nomeSafra(carga.codsafra) || '—' }}
            </span>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="text-caption text-grey-6">Chegada</div>
          <div class="row items-center no-wrap">
            <q-icon name="schedule" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">
              {{ carga.data ? formataTimestamp(carga.data) : '—' }}
            </span>
          </div>
        </div>

        <div class="col-6 col-sm-4">
          <div class="text-caption text-grey-6">Placa</div>
          <div class="row items-center no-wrap">
            <q-icon name="local_shipping" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">{{ carga.placa || '—' }}</span>
          </div>
        </div>
        <div v-if="ehExpedicao" class="col-6 col-sm-4">
          <div class="text-caption text-grey-6">Reboque</div>
          <div class="row items-center no-wrap">
            <q-icon name="link" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">{{ reboques || '—' }}</span>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="text-caption text-grey-6">Motorista</div>
          <div class="row items-center no-wrap">
            <q-icon name="person" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium ellipsis">{{ carga.motorista || '—' }}</span>
          </div>
          <div v-if="motoristaSemCadastro" class="text-caption text-grey-6">
            {{ legendaMotorista }}
          </div>
        </div>
      </div>
    </q-card-section>
  </q-card>

  <q-dialog v-model="dialogAberto" :maximized="$q.screen.lt.sm">
    <!-- F3 aqui confirma ESTE dialog; o .stop segura o F3 da página. -->
    <q-card
      flat
      class="column no-wrap"
      :class="{ 'carga-dialog': !$q.screen.lt.sm }"
      @keydown.f3.prevent.stop="formRef.submit($event)"
    >
      <q-form ref="formRef" class="col column no-wrap" @submit="salvar">
        <q-card-section class="col scroll">
          <div class="text-subtitle1 q-mb-md">Operação</div>
          <div class="row q-col-gutter-x-md">
            <!-- `bottom-slots` nos campos sem :rules: reservam o mesmo espaço de
                 mensagem dos vizinhos (senão, empilhados no celular, encostam). -->
            <SelectSentido
              v-model="edicao.sentido"
              :disable="finalizada"
              bottom-slots
              class="col-12 col-sm-4"
              @keydown="tecladoSelect($event, null)"
            />
            <!-- Cursor nasce aqui: a safra é o 1º dado da carga nova. Desabilitado
                 (finalizada) o select não tem alvo de foco. -->
            <q-select
              ref="safraRef"
              v-model="edicao.codsafra"
              :options="opcoesSafra"
              option-value="codsafra"
              option-label="safra"
              emit-value
              map-options
              outlined
              label="Safra"
              :disable="finalizada"
              :autofocus="!finalizada"
              class="col-12 col-sm-4"
              lazy-rules
              :rules="[regraSafra]"
              @keydown="tecladoSelect($event, safraRef)"
            />
            <MgInputData
              v-model="edicao.data"
              type="timestamp"
              label="Chegada"
              :max="dataMax"
              class="col-12 col-sm-4"
            />

            <!-- Sem `clearable`: o X do Quasar entra na ordem do Tab (tabindex 0 fixo)
                 e o Tab pararia nele em vez de ir pra Carreta — X próprio no #append,
                 como o MgInput faz. -->
            <q-select
              ref="placaRef"
              :model-value="edicao.placa"
              :options="placaOptions"
              label="Placa"
              outlined
              use-input
              fill-input
              hide-selected
              input-debounce="200"
              new-value-mode="add-unique"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              class="col-12"
              :class="mostrarReboque ? 'col-sm-4' : 'col-sm-6'"
              lazy-rules
              :rules="[() => !!edicao.placa || 'Informe a placa.', () => regraPlaca(edicao.placa)]"
              @filter="filtrarPlaca"
              @update:model-value="onPlacaEscolhida"
              @blur="onPlacaBlur"
              @keydown="tecladoSelect($event, null)"
            >
              <template v-if="edicao.placa" #append>
                <q-icon
                  name="cancel"
                  class="cursor-pointer"
                  tabindex="-1"
                  @click.stop="limparPlaca"
                />
              </template>
              <template #option="{ opt, itemProps }">
                <q-item v-bind="itemProps">
                  <q-item-section avatar>
                    <q-icon
                      :name="opt.acao ? opt.icon : 'local_shipping'"
                      :color="opt.color || 'grey-7'"
                    />
                  </q-item-section>
                  <q-item-section :class="opt.color ? `text-${opt.color}` : ''">
                    {{ opt.label }}
                  </q-item-section>
                </q-item>
              </template>
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey-6">
                    {{ placaBusca ? 'Continue digitando — ABC1234 ou ABC1D23' : 'Digite a placa…' }}
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <!-- Reboque: só na Expedição (bitrem/rodotrem saindo da fazenda) —
                 Recebimento e Transferência não mostram. Quando aparece, são 2
                 (nenhum obrigatório — regraPlaca aceita vazio), sempre lado a
                 lado, mesmo no celular: placa cabe fácil em meia largura. -->
            <template v-if="mostrarReboque">
              <MgInput
                ref="reboque1Ref"
                v-model="edicao.placacarreta"
                label="Reboque 1"
                mask="AAA#X##"
                class="col-6 col-sm-4"
                lazy-rules
                :rules="[regraPlaca]"
              />
              <MgInput
                v-model="edicao.placacarreta2"
                label="Reboque 2"
                mask="AAA#X##"
                class="col-6 col-sm-4"
                lazy-rules
                :rules="[regraPlaca]"
              />
            </template>

            <!-- Motorista: na Expedição a linha da Placa já fechou os 12
                 sozinha (Placa+Reboque1+Reboque2), então o Motorista ocupa a
                 SUA linha inteira (12) — garante que o que vem depois (CPF…)
                 também comece do zero, sem sobra pra disputar. Nos outros
                 tipos, Placa é col-6 e o Motorista fecha o par (6+6=12). -->
            <MgSelectPessoa
              v-if="modoMotorista === 'pesquisa'"
              v-model="edicao.codpessoamotorista"
              label="Motorista"
              placeholder="Nome ou CPF"
              clearable
              :acoes-sem-resultado="acoesMotorista"
              class="col-12"
              :class="mostrarReboque ? '' : 'col-sm-6'"
              @select="onMotoristaSelect"
              @clear="onMotoristaClear"
              @acao="onAcaoMotorista"
              @keydown="tecladoSelect($event, null)"
            />
            <MgInput
              v-else
              :model-value="edicao.cadastrarMotorista ? 'Novo cadastro' : 'Sem cadastro'"
              label="Motorista"
              readonly
              bottom-slots
              class="col-12"
              :class="mostrarReboque ? '' : 'col-sm-6'"
            >
              <template #append>
                <q-icon name="close" class="cursor-pointer" tabindex="-1" @click="voltarPesquisa">
                  <q-tooltip>Pesquisar no cadastro</q-tooltip>
                </q-icon>
              </template>
            </MgInput>

            <CargaMotoristaCampos
              v-if="modoMotorista === 'novo'"
              ref="camposMotoristaRef"
              :dados="edicao"
              :online="!offline"
              @existente="onMotoristaExistente"
            />
          </div>
          <div v-if="finalizada" class="text-caption text-grey-6">
            Romaneio finalizado — a operação e a safra não mudam mais.
          </div>
        </q-card-section>
        <q-card-actions class="col-auto">
          <!-- Cadastrar ou não é decisão de quem salva: fica no rodapé, junto do
               Salvar. Offline não há como cadastrar — o motorista fica só nesta
               carga (o tooltip vai num span: botão desabilitado não recebe hover). -->
          <span v-if="modoMotorista === 'novo'">
            <q-toggle
              v-model="edicao.cadastrarMotorista"
              :disable="offline"
              label="Cadastrar motorista no sistema"
            />
            <q-tooltip v-if="offline">Sem conexão: o motorista fica só nesta carga.</q-tooltip>
          </span>
          <q-space />
          <q-btn label="Cancelar (Esc)" flat color="grey-8" v-close-popup tabindex="-1" />
          <q-btn label="Salvar (Enter)" type="submit" flat color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <CaminhaoDialog v-model="cadastroCaminhao" :placa="placaBusca" @criado="onCaminhaoCriado" />
</template>
