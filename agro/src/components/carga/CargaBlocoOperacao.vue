<script setup>
// Bloco "Operação": o que a carga É (tipo de romaneio + safra) e a ficha do
// caminhão. O card só exibe; tudo se edita no modal do `+`/lápis — 6 campos em
// 2 linhas de 3. Trocar a operação depois da 1ª pesagem pede confirmação, e
// quem decide isso é o CargaForm (`trocarOperacao`), não este bloco.
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
  () => !!(carga.value.placa || carga.value.placacarreta || carga.value.motorista),
)

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
const camposMotoristaRef = ref(null)
const carretaRef = ref(null)

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
    placa: carga.value.placa,
    codveiculo: carga.value.codveiculo,
    placacarreta: carga.value.placacarreta,
    codpessoamotorista: carga.value.codpessoamotorista,
    motorista: carga.value.motorista,
    ...Object.fromEntries(CAMPOS_MOTORISTA_SEM_CADASTRO.map((c) => [c, carga.value[c] ?? null])),
    cadastrarMotorista: false,
  }
  // Nome digitado sem cadastro (ou offline, sem busca de pessoa) abre direto
  // nos campos do motorista novo; carga antiga sem CPF/telefone/endereço passa
  // a pedir o que falta aqui.
  modoMotorista.value =
    !edicao.value.codpessoamotorista && (edicao.value.motorista || !online.value)
      ? 'novo'
      : 'pesquisa'
  placaBusca.value = edicao.value.placa || ''
  dialogAberto.value = true
}
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
// Placa escolhida (clique ou Enter): o foco segue pra Carreta quando o menu
// fecha — o operador já decidiu, não precisa de outro clique.
let focarCarreta = false
function onPlacaEscolhida(val) {
  if (val === ACAO_PLACA.cadastrar) {
    cadastroCaminhao.value = true
    return
  }
  resolverPlaca(val === ACAO_PLACA.semcadastro ? placaBusca.value : val)
  focarCarreta = !!edicao.value.placa
}
function onPlacaPopupHide() {
  if (!focarCarreta) return
  focarCarreta = false
  nextTick(() => carretaRef.value?.focus())
}
function onPlacaBlur() {
  if (placaBusca.value && placaBusca.value !== edicao.value.placa) resolverPlaca(placaBusca.value)
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
function acoesMotorista(busca) {
  if ((busca || '').length < 2) return []
  const acoes = [
    { acao: 'semcadastro', label: `Usar “${busca}” sem cadastro, só nesta carga`, icon: 'edit_note' },
  ]
  if (online.value) {
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
async function onAcaoMotorista(acao, busca) {
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
function onMotoristaExistente(p) {
  selecionarPessoa(p)
  $q.notify({ type: 'info', message: `CPF já cadastrado: ${p.fantasia || p.pessoa} — selecionado.` })
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
      placacarreta: edicao.value.placacarreta,
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
        <div class="col-6 col-sm-4">
          <div class="text-caption text-grey-6">Carreta</div>
          <div class="row items-center no-wrap">
            <q-icon name="link" color="blue-grey-6" size="20px" class="q-mr-sm" />
            <span class="text-body1 text-weight-medium">{{ carga.placacarreta || '—' }}</span>
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
    <q-card flat class="column no-wrap" :class="{ 'carga-dialog': !$q.screen.lt.sm }">
      <q-form class="col column no-wrap" @submit="salvar">
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
            />
            <q-select
              v-model="edicao.codsafra"
              :options="opcoesSafra"
              option-value="codsafra"
              option-label="safra"
              emit-value
              map-options
              outlined
              label="Safra"
              :disable="finalizada"
              class="col-12 col-sm-4"
              lazy-rules
              :rules="[regraSafra]"
            />
            <MgInputData
              v-model="edicao.data"
              type="timestamp"
              label="Chegada"
              :max="dataMax"
              class="col-12 col-sm-4"
            />

            <q-select
              :model-value="edicao.placa"
              :options="placaOptions"
              label="Placa"
              outlined
              use-input
              fill-input
              hide-selected
              clearable
              input-debounce="200"
              new-value-mode="add-unique"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              class="col-12 col-sm-4"
              autofocus
              lazy-rules
              :rules="[() => !!edicao.placa || 'Informe a placa.', () => regraPlaca(edicao.placa)]"
              @filter="filtrarPlaca"
              @update:model-value="onPlacaEscolhida"
              @popup-hide="onPlacaPopupHide"
              @blur="onPlacaBlur"
            >
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

            <MgInput
              ref="carretaRef"
              v-model="edicao.placacarreta"
              label="Carreta"
              mask="AAA#X##"
              class="col-12 col-sm-4"
              lazy-rules
              :rules="[regraPlaca]"
            />

            <MgSelectPessoa
              v-if="modoMotorista === 'pesquisa'"
              v-model="edicao.codpessoamotorista"
              label="Motorista"
              placeholder="Nome ou CPF"
              clearable
              :acoes-sem-resultado="acoesMotorista"
              class="col-12 col-sm-4"
              @select="onMotoristaSelect"
              @clear="onMotoristaClear"
              @acao="onAcaoMotorista"
            />
            <MgInput
              v-else
              :model-value="edicao.cadastrarMotorista ? 'Novo cadastro' : 'Sem cadastro'"
              label="Motorista"
              readonly
              bottom-slots
              class="col-12 col-sm-4"
            >
              <template v-if="online" #append>
                <q-icon name="close" class="cursor-pointer" tabindex="-1" @click="voltarPesquisa">
                  <q-tooltip>Pesquisar no cadastro</q-tooltip>
                </q-icon>
              </template>
            </MgInput>

            <CargaMotoristaCampos
              v-if="modoMotorista === 'novo'"
              ref="camposMotoristaRef"
              :dados="edicao"
              :online="online"
              @existente="onMotoristaExistente"
            />
          </div>
          <div v-if="finalizada" class="text-caption text-grey-6">
            Romaneio finalizado — a operação e a safra não mudam mais.
          </div>
        </q-card-section>
        <q-card-actions align="right" class="col-auto">
          <q-btn label="Cancelar" flat color="grey-8" v-close-popup tabindex="-1" />
          <q-btn label="Salvar" type="submit" flat color="primary" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <CaminhaoDialog v-model="cadastroCaminhao" :placa="placaBusca" @criado="onCaminhaoCriado" />
</template>
