<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { rhStore } from 'src/stores/rh'
import { useAuthStore } from 'src/stores'
import { formataData, formataNumero, formataCnpjCpf } from '@components/formatters'
import { baixarArquivo } from '@components/baixarArquivo'
import { extrairErro } from 'src/utils/rhFormatters'
import MgTabelaValores from '@components/MgTabelaValores.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import DialogRecargaAvulsa from 'src/components/rh/DialogRecargaAvulsa.vue'

const MIME_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const sRh = rhStore()
const user = useAuthStore()

const loading = ref(false)
const empresas = ref([])
const previas = ref({}) // { [codempresa]: linhas }
const recargas = ref([])
const tab = ref(route.query.tab || null)

const dialogAvulsa = ref(false)
const empresaAvulsa = ref({})

const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))
const periodo = computed(
  () => sRh.periodos.find((p) => String(p.codperiodo) === String(route.params.codperiodo)) || {},
)

// Prefixo próprio: o PeriodoDashboard usa `un-<codunidadenegocio>` e as duas
// telas compartilham a querystring `?tab=` ao trocar de período.
const nomeTab = (codempresa) => 'emp-' + codempresa

// --- COLUNAS ---
// Só a tabela de itens do lote sobrou na tela; a situação por colaborador vive
// no diálogo de nova recarga.
const colunasLote = [
  { nome: 'setor', label: 'Setor', largura: '24%', vazio: 'Sem setor' },
  { nome: 'nome', label: 'Colaborador', largura: '40%' },
  { nome: 'cpf', label: 'CPF', tipo: 'slot', largura: '18%' },
  { nome: 'valor', label: 'Valor', tipo: 'numero', largura: '18%' },
]

// --- DERIVADOS ---
// A prévia traz o quadro INTEIRO da empresa — é dela que sai a lista do diálogo,
// e dá para recarregar quem ainda não tem acerto. Para os totais o que interessa
// é quem está envolvido em recarga; senão os cards contariam o quadro todo.
const previaDaEmpresa = (codempresa) => previas.value[codempresa] || []

const envolvido = (l) => Number(l.extrato) !== 0 || Number(l.recarga) !== 0

const soma = (linhas, campo) => linhas.reduce((s, l) => s + (Number(l[campo]) || 0), 0)

const saldoDaEmpresa = (codempresa) => soma(previaDaEmpresa(codempresa), 'saldo')

const pendentesDaEmpresa = (codempresa) =>
  previaDaEmpresa(codempresa).filter((l) => Number(l.saldo) > 0).length

const frasePendentes = (codempresa) => {
  const n = pendentesDaEmpresa(codempresa)
  if (!n) return 'Ninguém com saldo a receber'
  if (n === 1) return '1 colaborador espera receber o saldo'
  return n + ' colaboradores esperam receber o saldo'
}

const recargasDaEmpresa = (codempresa) =>
  recargas.value.filter((r) => Number(r.codempresa) === Number(codempresa))

// KPIs do período inteiro, como no PeriodoDashboard. Só os envolvidos: o quadro
// inteiro só existe para alimentar o diálogo.
const todasPrevias = computed(() => Object.values(previas.value).flat().filter(envolvido))
const totalExtrato = computed(() => soma(todasPrevias.value, 'extrato'))
const totalRecarga = computed(() => soma(todasPrevias.value, 'recarga'))
// Extrato − Recarga, com sinal: positivo é o que falta entregar, negativo é o
// que já foi entregue além do extrato. Somar os dois lados é a leitura certa do
// período — o detalhe por pessoa está no diálogo.
const totalSaldo = computed(() => soma(todasPrevias.value, 'saldo'))

const corSaldo = computed(() =>
  totalSaldo.value > 0 ? 'text-orange-9' : totalSaldo.value < 0 ? 'text-red-9' : 'text-grey',
)

const totalSemCartao = computed(() => todasPrevias.value.filter((l) => l.sem_cartao).length)
const lotesAtivos = computed(() => recargas.value.filter((r) => !r.inativo))

// --- CARREGAMENTO ---
// Poucas empresas e poucos lotes por período: carrega tudo de uma vez, e trocar
// de tab não dispara request nenhum.
const carregar = async () => {
  loading.value = true
  try {
    const [retEmpresas, retRecargas] = await Promise.all([
      sRh.getEmpresasRecarga(route.params.codperiodo),
      sRh.getRecargas(route.params.codperiodo),
    ])
    empresas.value = retEmpresas.data
    recargas.value = retRecargas.data.data

    previas.value = {}
    await Promise.all(empresas.value.map((e) => carregarPrevia(e.codempresa)))

    if (!empresas.value.some((e) => nomeTab(e.codempresa) === tab.value)) {
      tab.value = empresas.value.length ? nomeTab(empresas.value[0].codempresa) : null
    }
  } catch (error) {
    notificaErro(error, 'Erro ao carregar as recargas')
  } finally {
    loading.value = false
  }
}

const carregarPrevia = async (codempresa) => {
  const ret = await sRh.getPreviaRecarga(route.params.codperiodo, codempresa)
  previas.value = { ...previas.value, [codempresa]: ret.data }
}

const notificaErro = (error, padrao) => {
  $q.notify({
    color: 'red-5',
    textColor: 'white',
    icon: 'error',
    message: extrairErro(error, padrao),
  })
}

const notificaOk = (message) => {
  $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message })
}

// --- AÇÕES ---
// Os dois caminhos de criação (lote do mês e lote avulso) terminam aqui, senão
// divergem: quem gera precisa aparecer na lista e zerar o saldo na prévia.
//
// O download NÃO sai daqui: a planilha é do botão do card, quando o RH quiser.
// Baixar sozinho ao gerar atropela quem só queria lançar o lote.
const registrarLote = async (recarga, mensagem) => {
  recargas.value.unshift(recarga)
  await carregarPrevia(recarga.codempresa)
  notificaOk(mensagem)
}

// O adiantamento à Beevale é do Financeiro: a baixa acontece na tela de
// Liquidação, no app de contas. Mesmo padrão do ColaboradorDetalhe.
const urlTitulo = (codtitulo) =>
  codtitulo ? `${process.env.CONTAS_URL}/titulo/${codtitulo}` : null

const abrirAvulsa = (empresa) => {
  empresaAvulsa.value = empresa
  dialogAvulsa.value = true
}

const avulsaGerada = (recarga) => registrarLote(recarga, 'Recarga gerada')

const baixarPlanilha = async (recarga) => {
  try {
    const ret = await sRh.baixarPlanilhaRecarga(route.params.codperiodo, recarga.codbeerecarga)
    baixarArquivo(ret.data, 'recarga-bee-' + recarga.codbeerecarga + '.xlsx', MIME_XLSX)
  } catch (error) {
    notificaErro(error, 'Erro ao baixar a planilha')
  }
}

const substituir = (recarga) => {
  const i = recargas.value.findIndex((r) => r.codbeerecarga === recarga.codbeerecarga)
  if (i >= 0) recargas.value[i] = recarga
}

const confirmar = async (recarga) => {
  try {
    const ret = await sRh.confirmarRecarga(route.params.codperiodo, recarga.codbeerecarga)
    substituir(ret.data.data)
    notificaOk('Recarga confirmada')
  } catch (error) {
    notificaErro(error, 'Erro ao confirmar a recarga')
  }
}

const desconfirmar = async (recarga) => {
  try {
    const ret = await sRh.desconfirmarRecarga(route.params.codperiodo, recarga.codbeerecarga)
    substituir(ret.data.data)
    notificaOk('Confirmação desfeita')
  } catch (error) {
    notificaErro(error, 'Erro ao desconfirmar')
  }
}

const inativar = (recarga) => {
  $q.dialog({
    title: 'Inativar recarga',
    message:
      'O título de adiantamento será estornado e os ' +
      recarga.colaboradores.length +
      ' colaboradores voltam a ter saldo pendente. Continuar?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const ret = await sRh.inativarRecarga(route.params.codperiodo, recarga.codbeerecarga)
      substituir(ret.data.data)
      await carregarPrevia(recarga.codempresa)
      notificaOk('Recarga inativada e título estornado')
    } catch (error) {
      notificaErro(error, 'Erro ao inativar a recarga')
    }
  })
}

watch(tab, (novo) => {
  if (novo && route.query.tab !== novo) {
    router.replace({ query: { ...route.query, tab: novo } })
  }
})

watch(() => route.params.codperiodo, carregar)

onMounted(carregar)
</script>

<template>
  <div>
    <q-inner-loading :showing="loading" />

    <template v-if="!loading">
      <!-- HEADER -->
      <q-item class="q-pt-lg q-pb-sm">
        <q-item-section avatar>
          <q-avatar color="amber" text-color="white" size="80px" icon="credit_card" />
        </q-item-section>
        <q-item-section>
          <div class="text-h4 text-grey-9">Recarga Bee</div>
          <div class="text-caption text-grey" v-if="periodo.codperiodo">
            {{ formataData(periodo.periodoinicial) }} a {{ formataData(periodo.periodofinal) }}
          </div>
        </q-item-section>
        <q-item-section side top>
          <div>
            <q-btn
              flat
              round
              icon="paid"
              size="sm"
              color="grey-7"
              :to="{ name: 'rhDashboard', params: { codperiodo: route.params.codperiodo } }"
            >
              <q-tooltip>Metas &amp; Variáveis do período</q-tooltip>
            </q-btn>
          </div>
        </q-item-section>
      </q-item>

      <div class="q-pa-md">
        <MgEmptyState v-if="!empresas.length" icon="credit_card">
          Nenhum colaborador neste período.
        </MgEmptyState>

        <template v-else>
          <!-- CARDS RESUMO -->
          <div class="row q-col-gutter-md q-mb-md items-stretch">
            <div class="col-xs-4 col-sm">
              <q-card bordered flat class="full-height">
                <q-card-section class="text-center">
                  <div class="text-caption text-grey">Colaboradores</div>
                  <div class="text-h5 text-grey-9">{{ todasPrevias.length }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card bordered flat class="full-height">
                <q-card-section class="text-center">
                  <div class="text-caption text-grey">Extrato</div>
                  <div class="text-h5 text-grey-9">{{ formataNumero(totalExtrato) }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card bordered flat class="full-height">
                <q-card-section class="text-center">
                  <div class="text-caption text-grey">Recarga</div>
                  <div class="text-h5 text-grey-9">{{ formataNumero(totalRecarga) }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card
                bordered
                flat
                class="full-height"
                :class="totalSaldo > 0 ? 'bg-orange-1' : totalSaldo < 0 ? 'bg-red-1' : ''"
              >
                <q-card-section class="text-center" style="cursor: help">
                  <div class="text-caption" :class="corSaldo">Saldo</div>
                  <div class="text-h5" :class="totalSaldo ? corSaldo : 'text-grey-9'">
                    {{ formataNumero(totalSaldo) }}
                  </div>
                  <q-tooltip>
                    Extrato menos Recarga. Positivo é o que falta entregar; negativo é o que já foi
                    entregue além do extrato.
                  </q-tooltip>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card bordered flat class="full-height">
                <q-card-section class="text-center">
                  <div class="text-caption text-grey">Lotes</div>
                  <div class="text-h5 text-grey-9">{{ lotesAtivos.length }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card
                bordered
                flat
                class="full-height"
                :class="totalSemCartao ? 'bg-orange-1' : ''"
              >
                <q-card-section class="text-center" style="cursor: help">
                  <div class="text-caption" :class="totalSemCartao ? 'text-orange-9' : 'text-grey'">
                    Sem Cartão
                  </div>
                  <div class="text-h5" :class="totalSemCartao ? 'text-orange-9' : 'text-grey-9'">
                    {{ totalSemCartao }}
                  </div>
                  <q-tooltip>
                    Entram na planilha mesmo assim — a operadora usa o CPF, não o número do cartão.
                  </q-tooltip>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <!-- TABS: UMA POR EMPRESA MÃE -->
          <q-tabs
            v-model="tab"
            align="left"
            active-color="primary"
            indicator-color="primary"
            class="text-grey-7"
            no-caps
          >
            <q-tab
              v-for="e in empresas"
              :key="e.codempresa"
              :name="nomeTab(e.codempresa)"
              :label="e.empresa"
            />
          </q-tabs>
          <q-separator />

          <q-tab-panels v-model="tab" animated class="bg-grey-2">
            <q-tab-panel
              v-for="e in empresas"
              :key="e.codempresa"
              :name="nomeTab(e.codempresa)"
              class="q-pa-none q-mt-md"
            >
              <!-- RESUMO DA EMPRESA -->
              <q-card bordered flat class="q-mb-md">
                <q-card-section class="row items-start no-wrap">
                  <div class="col">
                    <div class="text-h6 text-grey-9">{{ e.empresa }}</div>
                    <div class="text-caption text-grey-7 q-mt-md">
                      Saldo — extrato menos recarga
                    </div>
                    <div class="text-h4 text-grey-9">
                      {{ formataNumero(saldoDaEmpresa(e.codempresa)) }}
                    </div>
                    <div class="text-caption text-grey-7 q-mt-xs">
                      {{ frasePendentes(e.codempresa) }}
                    </div>
                  </div>
                  <div class="col-auto">
                    <q-btn
                      v-if="podeEditar"
                      flat
                      round
                      icon="add"
                      size="sm"
                      color="primary"
                      @click="abrirAvulsa(e)"
                    >
                      <q-tooltip>Nova recarga — entrega o saldo nos cartões</q-tooltip>
                    </q-btn>
                  </div>
                </q-card-section>
              </q-card>

              <!-- LOTES JÁ GERADOS -->
              <q-card
                v-for="r in recargasDaEmpresa(e.codempresa)"
                :key="r.codbeerecarga"
                bordered
                flat
                class="q-mb-md"
                :class="r.inativo ? 'bg-grey-3' : ''"
              >
                <q-card-section class="text-grey-9 text-overline row items-center">
                  Pagamento {{ formataData(r.dia) }}
                  <q-badge
                    :color="r.inativo ? 'grey' : r.status === 'OK' ? 'green' : 'orange'"
                    :label="r.inativo ? 'Inativa' : r.status_descricao"
                    class="q-ml-sm"
                  />
                  <a
                    :href="urlTitulo(r.codtitulo)"
                    target="_blank"
                    class="q-ml-md text-primary text-body1 text-weight-bold"
                  >
                    Título - {{ r.codtitulo }}
                    <q-tooltip>Abrir o título no app de Contas</q-tooltip>
                  </a>
                  <q-badge v-if="r.portador" outline color="blue-8" class="q-ml-sm">
                    {{ r.portador }}
                    <q-tooltip>Portador de onde o adiantamento sai</q-tooltip>
                  </q-badge>
                  <q-space />
                  <div class="text-h6 text-grey-9 q-mr-md">{{ formataNumero(r.valor) }}</div>
                  <q-btn
                    flat
                    round
                    dense
                    icon="download"
                    size="sm"
                    color="grey-7"
                    @click="baixarPlanilha(r)"
                  >
                    <q-tooltip>Baixar planilha</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-if="podeEditar && !r.inativo && r.status !== 'OK'"
                    flat
                    round
                    dense
                    icon="done"
                    size="sm"
                    color="green-7"
                    class="q-ml-sm"
                    @click="confirmar(r)"
                  >
                    <q-tooltip>Confirmar que o saldo caiu nos cartões</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-if="podeEditar && !r.inativo && r.status === 'OK'"
                    flat
                    round
                    dense
                    icon="undo"
                    size="sm"
                    color="grey-7"
                    class="q-ml-sm"
                    @click="desconfirmar(r)"
                  >
                    <q-tooltip>Desfazer a confirmação (destrava a inativação)</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-if="podeEditar && !r.inativo && r.status !== 'OK'"
                    flat
                    round
                    dense
                    icon="block"
                    size="sm"
                    color="red-7"
                    class="q-ml-sm"
                    @click="inativar(r)"
                  >
                    <q-tooltip>Inativar e estornar o título</q-tooltip>
                  </q-btn>
                  <MgInfoCriacao :registro="r" class="q-ml-sm" />
                </q-card-section>

                <div v-if="r.observacao" class="text-caption text-grey-7 q-px-md q-pb-sm">
                  {{ r.observacao }}
                </div>

                <q-expansion-item
                  expand-separator
                  :label="r.colaboradores.length + ' colaboradores'"
                  header-class="text-grey-7"
                >
                  <MgTabelaValores :colunas="colunasLote" :linhas="r.colaboradores" chave="cpf">
                    <template #celula-cpf="{ linha }">
                      {{ formataCnpjCpf(linha.cpf, linha.fisica) }}
                    </template>
                  </MgTabelaValores>
                </q-expansion-item>
              </q-card>
            </q-tab-panel>
          </q-tab-panels>
        </template>
      </div>
    </template>

    <DialogRecargaAvulsa
      v-model="dialogAvulsa"
      :codperiodo="route.params.codperiodo"
      :empresa="empresaAvulsa"
      :linhas="previaDaEmpresa(empresaAvulsa.codempresa)"
      @gerado="avulsaGerada"
    />
  </div>
</template>
