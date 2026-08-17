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
const gerando = ref(null)
const empresas = ref([])
const previas = ref({}) // { [codempresa]: linhas }
const recargas = ref([])
const tab = ref(route.query.tab || null)

const dialogAvulsa = ref(false)
const empresaAvulsa = ref({})
const seedAvulsa = ref([])

const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))
const periodo = computed(
  () => sRh.periodos.find((p) => String(p.codperiodo) === String(route.params.codperiodo)) || {},
)

// Prefixo próprio: o PeriodoDashboard usa `un-<codunidadenegocio>` e as duas
// telas compartilham a querystring `?tab=` ao trocar de período.
const nomeTab = (codempresa) => 'emp-' + codempresa
const empresaAtiva = computed(() =>
  empresas.value.find((e) => nomeTab(e.codempresa) === tab.value),
)

// --- COLUNAS ---
// `tipo: 'numero'` já dá alinhamento à direita, formatação e o valor do rodapé.
// A coluna Ações só existe quando dá para editar — declarar aqui (e não com
// v-if no <th>/<td>) é o que mantém o colgroup alinhado.
const colunas = computed(() => {
  const editando = podeEditar.value
  const cols = [
    { nome: 'setor', label: 'Setor', largura: editando ? '18%' : '20%', vazio: 'Sem setor' },
    { nome: 'nome', label: 'Colaborador', tipo: 'slot', largura: editando ? '27%' : '30%' },
    { nome: 'cpf', label: 'CPF', tipo: 'slot', largura: editando ? '14%' : '16%' },
    { nome: 'extrato', label: 'Extrato', tipo: 'numero', largura: '11%' },
    { nome: 'recarga', label: 'Recarga', tipo: 'numero', largura: '11%' },
    {
      nome: 'saldo',
      label: 'Saldo',
      tipo: 'numero',
      largura: '12%',
      classe: 'text-weight-bold',
      // Negativo = recarregado além do extrato; vermelho para não passar por
      // "nada a fazer".
      cor: (valor) => (valor > 0 ? 'orange-9' : valor < 0 ? 'red-7' : 'grey-6'),
    },
  ]
  if (editando) {
    cols.push({ nome: 'acoes', label: '', tipo: 'slot', align: 'right', largura: '7%' })
  }
  return cols
})

const colunasLote = [
  { nome: 'setor', label: 'Setor', largura: '24%', vazio: 'Sem setor' },
  { nome: 'nome', label: 'Colaborador', largura: '40%' },
  { nome: 'cpf', label: 'CPF', tipo: 'slot', largura: '18%' },
  { nome: 'valor', label: 'Valor', tipo: 'numero', largura: '18%' },
]

// --- DERIVADOS ---
const previaDaEmpresa = (codempresa) => previas.value[codempresa] || []

const soma = (linhas, campo) => linhas.reduce((s, l) => s + (Number(l[campo]) || 0), 0)

const rodapeEmpresa = (codempresa) => {
  const linhas = previaDaEmpresa(codempresa)
  if (!linhas.length) return []
  return [
    {
      valores: {
        setor: 'Subtotal',
        extrato: soma(linhas, 'extrato'),
        recarga: soma(linhas, 'recarga'),
        saldo: soma(linhas, 'saldo'),
      },
    },
  ]
}

// Só o que ainda há para pagar. Sem o max, um adiantado de -1000 anularia dez
// pendências de +100 e a tela diria "0,00" — e isto alimenta o badge da aba e o
// `:disable` do botão, que por sua vez só considera saldo > 0.
const saldoDaEmpresa = (codempresa) =>
  previaDaEmpresa(codempresa).reduce((s, l) => s + Math.max(Number(l.saldo) || 0, 0), 0)

const recargasDaEmpresa = (codempresa) =>
  recargas.value.filter((r) => Number(r.codempresa) === Number(codempresa))

// KPIs do período inteiro, como no PeriodoDashboard.
const todasPrevias = computed(() => Object.values(previas.value).flat())
const totalExtrato = computed(() => soma(todasPrevias.value, 'extrato'))
const totalRecarregado = computed(() => soma(todasPrevias.value, 'recarga'))
const totalSaldo = computed(() =>
  todasPrevias.value.reduce((s, l) => s + Math.max(Number(l.saldo) || 0, 0), 0),
)
// Recarregado além do extrato — acontece quando um acerto é inativado depois de
// o lote já ter sido gerado. Só aparece quando existe.
const totalAdiantado = computed(() =>
  todasPrevias.value.reduce((s, l) => s + Math.min(Number(l.saldo) || 0, 0), 0),
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
// divergem: quem gera precisa aparecer na lista, zerar o saldo na prévia e
// baixar a planilha.
const registrarLote = async (recarga, mensagem) => {
  recargas.value.unshift(recarga)
  await carregarPrevia(recarga.codempresa)
  notificaOk(mensagem)
  await baixarPlanilha(recarga)
}

const gerar = async (empresa) => {
  gerando.value = empresa.codempresa
  try {
    const ret = await sRh.gerarRecarga(route.params.codperiodo, {
      codempresa: empresa.codempresa,
    })
    await registrarLote(ret.data.data, 'Recarga gerada para ' + empresa.empresa)
  } catch (error) {
    notificaErro(error, 'Erro ao gerar a recarga')
  } finally {
    gerando.value = null
  }
}

const abrirAvulsa = (empresa, seed = []) => {
  empresaAvulsa.value = empresa
  seedAvulsa.value = seed
  dialogAvulsa.value = true
}

const avulsaGerada = (recarga) => registrarLote(recarga, 'Recarga avulsa gerada')

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
          Nenhum acerto com forma "Recarga Bee" neste período.
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
                  <div class="text-caption text-grey">Recarregado</div>
                  <div class="text-h5 text-grey-9">{{ formataNumero(totalRecarregado) }}</div>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card bordered flat class="full-height" :class="totalSaldo ? 'bg-orange-1' : ''">
                <q-card-section class="text-center">
                  <div class="text-caption" :class="totalSaldo ? 'text-orange-9' : 'text-grey'">
                    Saldo
                  </div>
                  <div class="text-h5" :class="totalSaldo ? 'text-orange-9' : 'text-grey-9'">
                    {{ formataNumero(totalSaldo) }}
                  </div>
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
            <div class="col-xs-4 col-sm" v-if="totalAdiantado">
              <q-card bordered flat class="full-height bg-red-1">
                <q-card-section class="text-center" style="cursor: help">
                  <div class="text-caption text-red-9">Adiantado</div>
                  <div class="text-h5 text-red-9">{{ formataNumero(-totalAdiantado) }}</div>
                  <q-tooltip>
                    Já recarregado além do extrato — acerto inativado depois do lote.
                  </q-tooltip>
                </q-card-section>
              </q-card>
            </div>
            <div class="col-xs-4 col-sm">
              <q-card bordered flat class="full-height" :class="totalSemCartao ? 'bg-orange-1' : ''">
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

          <!-- TOOLBAR DE AÇÕES DA EMPRESA ATIVA -->
          <div class="row items-center q-gutter-sm q-mb-md" v-if="podeEditar">
            <q-space />
            <q-btn
              flat
              icon="credit_card"
              :label="'Gerar Recarga ' + (empresaAtiva ? empresaAtiva.empresa : '')"
              color="primary"
              :disable="!empresaAtiva || !saldoDaEmpresa(empresaAtiva.codempresa)"
              :loading="gerando === empresaAtiva?.codempresa"
              @click="gerar(empresaAtiva)"
            >
              <q-tooltip>Cria o título de adiantamento à Beevale e baixa a planilha</q-tooltip>
            </q-btn>
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
            <q-tab v-for="e in empresas" :key="e.codempresa" :name="nomeTab(e.codempresa)">
              <div class="row items-center no-wrap">
                <span>{{ e.empresa }}</span>
                <q-badge v-if="saldoDaEmpresa(e.codempresa)" color="orange" rounded class="q-ml-xs">
                  <q-tooltip>
                    {{ formataNumero(saldoDaEmpresa(e.codempresa)) }} a recarregar
                  </q-tooltip>
                </q-badge>
              </div>
            </q-tab>
          </q-tabs>
          <q-separator />

          <q-tab-panels v-model="tab" animated class="bg-grey-2">
            <q-tab-panel
              v-for="e in empresas"
              :key="e.codempresa"
              :name="nomeTab(e.codempresa)"
              class="q-pa-none q-mt-md"
            >
              <!-- COLABORADORES DA EMPRESA -->
              <q-card bordered flat class="q-mb-md">
                <q-card-section class="text-grey-9 text-overline row items-center">
                  {{ e.empresa }}
                  <q-space />
                  <div class="text-caption text-grey-7">
                    {{ previaDaEmpresa(e.codempresa).length }} colaboradores
                  </div>
                  <q-btn
                    v-if="podeEditar"
                    flat
                    round
                    icon="add"
                    size="sm"
                    color="primary"
                    class="q-ml-sm"
                    @click="abrirAvulsa(e)"
                  >
                    <q-tooltip>Nova recarga — escolher quem entra e com quanto</q-tooltip>
                  </q-btn>
                </q-card-section>

                <MgTabelaValores
                  :colunas="colunas"
                  :linhas="previaDaEmpresa(e.codempresa)"
                  :rodape="rodapeEmpresa(e.codempresa)"
                  chave="codperiodocolaborador"
                  vazio="Nenhum acerto com forma &quot;Recarga Bee&quot; nesta empresa no período."
                >
                  <template #celula-nome="{ linha }">
                    {{ linha.nome }}
                    <q-icon
                      v-if="linha.sem_cartao"
                      name="warning"
                      color="orange-7"
                      size="xs"
                      class="q-ml-xs"
                    >
                      <q-tooltip>Sem cartão-benefício ativo cadastrado</q-tooltip>
                    </q-icon>
                    <q-tooltip v-else>
                      {{ linha.filial }} — cartão {{ linha.cartao }}, val. {{ linha.validade }}
                    </q-tooltip>
                  </template>
                  <template #celula-cpf="{ linha }">
                    {{ formataCnpjCpf(linha.cpf, linha.fisica) }}
                  </template>
                  <template #celula-acoes="{ linha }">
                    <q-btn
                      v-if="linha.saldo > 0"
                      flat
                      round
                      dense
                      icon="add_card"
                      size="sm"
                      color="primary"
                      @click="abrirAvulsa(e, [linha.codperiodocolaborador])"
                    >
                      <q-tooltip>Recarregar só {{ linha.nome }}</q-tooltip>
                    </q-btn>
                  </template>
                </MgTabelaValores>
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
                  <q-badge outline color="grey-7" class="q-ml-sm">
                    Título {{ r.titulo }} — {{ r.filial }}
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
                    v-if="podeEditar && !r.inativo"
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
      :seed="seedAvulsa"
      @gerado="avulsaGerada"
    />
  </div>
</template>
