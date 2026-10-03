<script setup>
// Caixas (M11 doc-3): os portadores em espécie da filial — saldo, sessão da gaveta, transferências
// chegando e saindo — e as transferências entre portadores (a confirmar e histórico), com a Nova
// transferência de → para. Saldo de gaveta só depois da conferência do gerente (às cegas, M9).
// M12, aba Períodos (Financeiro/Admin): o financeiro fecha cofre, troco, banco e adquirente pela
// data de corte, reabre do mais novo para o mais antigo e faz lançamento avulso (taxa, tarifa,
// rendimento, ajuste — o de implantação no primeiro período). Sessão de gaveta abre o Fechamentos.
// M13, aba Itens: o que chips, ingressos e maquinetas de parceiros movimentaram por sessão, com
// os totais e os títulos de repasse, para o acerto com o parceiro.
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/services/api'
import { useQuasar } from 'quasar'
import { useCaixaStore } from 'src/stores/caixaStore'
import { useAuth } from 'src/composables/useAuth'
import { formataNumero, formataTimestamp, formataCodigo, formataData } from '@components/formatters'
import MgInputData from '@components/MgInputData.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInput from '@components/MgInput.vue'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPortador from '@components/MgSelectPortador.vue'

const $q = useQuasar()
const store = useCaixaStore()

const COR_ESTADO = { P: 'amber-8', E: 'green-7', C: 'red-7' }
const ESTADO = { P: 'A confirmar', E: 'Efetivada', C: 'Cancelada' }

const estadoSessao = (c) => {
  if (!c.ehGaveta) return null
  if (!c.sessao) return { label: 'Nunca aberto', cor: 'grey-6' }
  if (c.sessao.aberta) return { label: 'Aberto', cor: 'green-7' }
  return { label: 'Fechado', cor: 'grey-7' }
}

// ---- nova transferência ----
const vazio = () => ({
  codportadororigem: null,
  codportadordestino: null,
  valor: null,
  observacoes: '',
})
const form = ref(vazio())
const bloqueios = ref({})

const novaTransferencia = async () => {
  form.value = vazio()
  await store.buscarCaixas()
  bloqueios.value = Object.fromEntries(
    store.caixas.filter((c) => c.bloqueio).map((c) => [c.codportador, c.bloqueio]),
  )
  store.dialogTransferir = true
}

const salvar = async () => {
  const pag = await store.transferir({
    ...form.value,
    observacoes: form.value.observacoes || null,
  })
  if (pag) store.dialogTransferir = false
}

const cancelar = (t) => {
  $q.dialog({
    title: 'Cancelar transferência',
    message: 'Desfaz os dois lançamentos. Valor diferente? Cancele e registre outra. Motivo:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (v) => (v || '').trim().length >= 5,
    },
    ok: { label: 'Cancelar transferência', color: 'negative', flat: true },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
  }).onOk((justificativa) => store.cancelar(t.codpagamento, justificativa))
}

// ---- períodos (M12) ----
const auth = useAuth()
const financeiro = computed(() => auth.temPermissao('Financeiro'))

const estadoPeriodo = (p) => {
  if (!p.aberto) return { label: 'Fechado', cor: 'grey-7' }
  if (p.corrente) return { label: 'Corrente', cor: 'green-7' }
  return { label: 'Reaberto', cor: 'amber-8' }
}

const iso = (d) =>
  [
    d.getFullYear(),
    String(d.getMonth() + 1).padStart(2, '0'),
    String(d.getDate()).padStart(2, '0'),
  ].join('-')

// corte padrão: último dia do mês anterior
const corte = ref(null)
const dialogFechar = ref(false)
const periodoFechar = ref(null)
const pedirFechar = (p) => {
  periodoFechar.value = p
  const hoje = new Date()
  corte.value = iso(new Date(hoje.getFullYear(), hoje.getMonth(), 0))
  dialogFechar.value = true
}
const fechar = async () => {
  const p = periodoFechar.value
  if (await store.fecharPeriodo(p.codportadorperiodo, p.corrente ? corte.value : null)) {
    dialogFechar.value = false
  }
}

const reabrir = (p) => {
  $q.dialog({
    title: 'Reabrir período',
    message: `Reabrir ${p.portador}, ${p.descricao}? Reabre-se do mais novo para o mais antigo.`,
    ok: { label: 'Reabrir', color: 'primary', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(() => store.reabrirPeriodo(p.codportadorperiodo))
}

const MOTIVOS = [
  { label: 'Ajuste (inclusive saldo de implantação)', value: 'A' },
  { label: 'Taxa', value: 'T' },
  { label: 'Tarifa', value: 'F' },
  { label: 'Rendimento', value: 'R' },
]
const lancamentoVazio = () => ({
  codportador: null,
  motivo: 'A',
  sentido: 1,
  valor: null,
  transacao: iso(new Date()),
  observacoes: '',
})
const lancamento = ref(lancamentoVazio())
const novoLancamento = (codportador = null) => {
  lancamento.value = { ...lancamentoVazio(), codportador }
  store.dialogLancamento = true
}
const salvarLancamento = async () => {
  const l = lancamento.value
  const ok = await store.lancar({
    codportador: l.codportador,
    motivo: l.motivo,
    valor: l.sentido * l.valor,
    transacao: l.transacao,
    observacoes: l.observacoes || null,
  })
  if (ok) store.dialogLancamento = false
}

// itens do caixa (M13): a lista do filtro
const opcoesItem = ref([])
onMounted(async () => {
  store.atualizar(financeiro.value)
  try {
    const { data } = await api.get('v1/caixa-item')
    opcoesItem.value = data.data.map((i) => ({ value: i.codcaixaitem, label: i.item }))
  } catch {
    opcoesItem.value = []
  }
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <q-tabs
        v-model="store.aba"
        align="left"
        no-caps
        class="text-grey-8 q-mb-md"
        active-color="primary"
      >
        <q-tab name="portadores" label="Portadores" icon="savings" />
        <q-tab name="transferencias" label="Transferências" icon="sync_alt">
          <q-badge v-if="store.pendentes.length" color="amber-8" floating>
            {{ store.pendentes.length }}
          </q-badge>
        </q-tab>
        <q-tab v-if="financeiro" name="periodos" label="Períodos" icon="date_range" />
        <q-tab name="itens" label="Itens" icon="inventory_2" />
      </q-tabs>

      <q-tab-panels v-model="store.aba" animated keep-alive>
        <!-- portadores em espécie -->
        <q-tab-panel name="portadores" class="q-pa-none">
          <q-card bordered flat v-if="store.caixas.length">
            <q-list separator>
              <q-item v-for="c in store.caixas" :key="c.codportador">
                <q-item-section avatar>
                  <q-icon
                    :name="c.ehGaveta ? 'point_of_sale' : 'savings'"
                    :color="c.ehGaveta ? 'green-7' : 'amber-8'"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ c.portador }}</q-item-label>
                  <q-item-label caption>
                    {{ c.filial }}
                    <template v-if="c.ehGaveta">· gaveta</template>
                    <template v-else-if="c.financeiro">· financeiro</template>
                    <template v-else>· cofre / troco</template>
                  </q-item-label>
                  <q-item-label v-if="c.ehGaveta && c.sessao" caption>
                    {{ c.sessao.aberta ? 'aberto em' : 'fechado em' }}
                    {{ formataTimestamp(c.sessao.aberta ? c.sessao.inicio : c.sessao.fim, 2) }}
                  </q-item-label>
                  <q-item-label v-if="c.chegando.quantidade" caption class="text-amber-9">
                    {{ c.chegando.quantidade }} chegando a confirmar · R$
                    {{ formataNumero(c.chegando.valor) }}
                  </q-item-label>
                  <q-item-label v-if="c.saindo.quantidade" caption class="text-amber-9">
                    {{ c.saindo.quantidade }} saindo a confirmar · R$
                    {{ formataNumero(c.saindo.valor) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label v-if="c.saldo !== null" class="text-weight-bold text-grey-9">
                    R$ {{ formataNumero(c.saldo) }}
                  </q-item-label>
                  <q-item-label v-else caption>
                    {{ c.ehGaveta ? 'saldo após a conferência' : '—' }}
                  </q-item-label>
                  <q-badge
                    v-if="estadoSessao(c)"
                    :color="estadoSessao(c).cor"
                    :label="estadoSessao(c).label"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
          <MgEmptyState v-else-if="!store.carregandoCaixas" icon="savings">
            Nenhum portador em espécie nesta filial.
          </MgEmptyState>
        </q-tab-panel>

        <!-- transferências -->
        <q-tab-panel name="transferencias" class="q-pa-none">
          <template
            v-for="grupo in [
              { titulo: 'A confirmar', itens: store.pendentes },
              { titulo: 'Histórico do período', itens: store.historico },
            ]"
            :key="grupo.titulo"
          >
            <div class="text-subtitle1 q-mb-sm">{{ grupo.titulo }}</div>
            <q-card bordered flat class="q-mb-md">
              <q-list v-if="grupo.itens.length" separator>
                <q-item
                  v-for="t in grupo.itens"
                  :key="t.codpagamento"
                  :to="{ name: 'pagamento-detalhe', params: { id: t.codpagamento } }"
                >
                  <q-item-section>
                    <q-item-label :class="t.estado === 'C' ? 'text-strike text-grey-6' : ''">
                      {{ t.portadororigem }} → {{ t.portadordestino }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ formataCodigo(t.codpagamento) }} · {{ formataTimestamp(t.transacao, 2) }} ·
                      {{ t.meiodescricao }} · {{ t.usuariocriacao }}
                    </q-item-label>
                    <q-item-label v-if="t.observacoes" caption>{{ t.observacoes }}</q-item-label>
                    <q-item-label v-if="t.estado === 'C'" caption class="text-negative">
                      {{ t.justificativa }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label
                      class="text-weight-bold"
                      :class="t.estado === 'C' ? 'text-strike text-grey-6' : 'text-grey-9'"
                    >
                      R$ {{ formataNumero(t.total) }}
                    </q-item-label>
                    <q-badge :color="COR_ESTADO[t.estado]" :label="ESTADO[t.estado]" />
                  </q-item-section>
                  <q-item-section side v-if="t.podeConfirmar || t.podeCancelar">
                    <div class="row no-wrap">
                      <q-btn
                        v-if="t.podeConfirmar"
                        flat
                        round
                        size="sm"
                        color="grey-7"
                        icon="done"
                        :loading="store.salvando"
                        @click.prevent.stop="store.confirmar(t.codpagamento)"
                      >
                        <q-tooltip>Confirmar o recebimento</q-tooltip>
                      </q-btn>
                      <q-btn
                        v-if="t.podeCancelar"
                        flat
                        round
                        size="sm"
                        color="grey-7"
                        icon="block"
                        @click.prevent.stop="cancelar(t)"
                      >
                        <q-tooltip>Cancelar</q-tooltip>
                      </q-btn>
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
              <MgEmptyState v-else plain icon="sync_alt">Nenhuma transferência.</MgEmptyState>
            </q-card>
          </template>
        </q-tab-panel>

        <!-- períodos (M12) -->
        <q-tab-panel v-if="financeiro" name="periodos" class="q-pa-none">
          <q-card bordered flat v-if="store.periodos.length">
            <q-list separator>
              <q-item
                v-for="p in store.periodos"
                :key="p.codportadorperiodo"
                clickable
                :to="
                  p.ehGaveta && !p.corrente
                    ? { name: 'fechamento-sessao', params: { id: p.codportadorperiodo } }
                    : undefined
                "
                @click="!p.ehGaveta && store.abrirPeriodo(p.codportadorperiodo)"
              >
                <q-item-section avatar>
                  <q-icon
                    :name="
                      p.ehGaveta ? 'point_of_sale' : p.tipo === 'B' ? 'account_balance' : 'savings'
                    "
                    :color="p.ehGaveta ? 'green-7' : 'grey-7'"
                  />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ p.portador }}</q-item-label>
                  <q-item-label caption>
                    {{ formataData(p.inicio) }} a
                    {{ p.fim ? formataData(p.fim) : 'hoje' }}
                    <template v-if="p.filial">· {{ p.filial }}</template>
                  </q-item-label>
                  <q-item-label v-if="p.fechamento" caption>
                    fechado por {{ p.usuariofechamento }} em {{ formataTimestamp(p.fechamento, 2) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <template v-if="p.saldofinal !== null">
                    <q-item-label caption>
                      inicial {{ formataNumero(p.saldoinicial) }}
                    </q-item-label>
                    <q-item-label class="text-weight-bold text-grey-9">
                      {{ p.aberto ? 'até agora' : 'final' }} {{ formataNumero(p.saldofinal) }}
                    </q-item-label>
                  </template>
                  <q-item-label v-else caption>saldos após a conferência</q-item-label>
                  <q-badge
                    v-if="!p.ehGaveta"
                    :color="estadoPeriodo(p).cor"
                    :label="estadoPeriodo(p).label"
                  />
                  <q-badge v-else color="green-7" label="Sessão do caixa" />
                </q-item-section>
                <q-item-section side v-if="!p.ehGaveta">
                  <div class="row no-wrap">
                    <q-btn
                      v-if="p.aberto"
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      icon="lock"
                      @click.prevent.stop="pedirFechar(p)"
                    >
                      <q-tooltip>Fechar</q-tooltip>
                    </q-btn>
                    <q-btn
                      v-else
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      icon="lock_open"
                      @click.prevent.stop="reabrir(p)"
                    >
                      <q-tooltip>Reabrir</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
          <MgEmptyState v-else-if="!store.carregandoPeriodos" icon="date_range">
            Nenhum período no intervalo. O corrente nasce no primeiro lançamento do portador.
          </MgEmptyState>
        </q-tab-panel>
        <!-- itens do caixa (M13): acerto com o parceiro -->
        <q-tab-panel name="itens" class="q-pa-none">
          <div class="row q-col-gutter-md q-mb-md">
            <div class="col-12 col-sm-6">
              <q-select
                v-model="store.codcaixaitem"
                :options="opcoesItem"
                label="Item"
                outlined
                emit-value
                map-options
                clearable
                @update:model-value="store.buscarItens()"
              />
            </div>
          </div>
          <q-card v-if="store.itens.totais.length" bordered flat class="q-mb-md">
            <q-list separator>
              <q-item v-for="t in store.itens.totais" :key="t.codcaixaitem">
                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ t.item }}</q-item-label>
                  <q-item-label caption>
                    {{ t.sessoes }} sessão(ões) · entrada {{ formataNumero(t.valorentrada) }} ·
                    saída {{ formataNumero(t.valorsaida) }}
                    <template v-if="t.modo === 'M'">
                      · vendido {{ formataNumero(t.valorvendido) }}
                    </template>
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-weight-bold">
                    R$ {{ formataNumero(t.liquido) }}
                  </q-item-label>
                  <q-item-label caption>líquido</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
          <q-card v-if="store.itens.linhas.length" bordered flat>
            <q-list separator>
              <q-item
                v-for="l in store.itens.linhas"
                :key="l.codcaixaitemlancamento"
                :to="{ name: 'fechamento-sessao', params: { id: l.codportadorperiodo } }"
              >
                <q-item-section>
                  <q-item-label>{{ l.item }} · {{ l.portador }}</q-item-label>
                  <q-item-label caption>
                    {{ formataTimestamp(l.inicio, 2) }} · {{ l.filial }}
                    <template v-if="!l.fim"> · aberto</template>
                  </q-item-label>
                  <q-item-label caption>
                    <template v-if="l.modo === 'C'">
                      abertura {{ formataNumero(l.valorabertura ?? 0) }} · fechamento
                      {{ l.valorfechamento === null ? '—' : formataNumero(l.valorfechamento) }} ·
                    </template>
                    <template v-else-if="l.valorvendido !== null">
                      vendido {{ formataNumero(l.valorvendido) }} ·
                    </template>
                    entrada {{ formataNumero(l.valorentrada) }} · saída
                    {{ formataNumero(l.valorsaida) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-item-label class="text-weight-bold">
                    {{ l.liquido === null ? '—' : 'R$ ' + formataNumero(l.liquido) }}
                  </q-item-label>
                  <q-item-label v-if="l.titulo" caption>
                    <a :href="`/titulo/${l.codtitulo}`" target="_blank" class="text-primary">{{
                      l.titulo
                    }}</a>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
          <MgEmptyState v-else-if="!store.carregandoItens" icon="inventory_2">
            Nenhum movimento de item do caixa no período.
          </MgEmptyState>
        </q-tab-panel>
      </q-tab-panels>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <div class="row q-gutter-sm items-end">
        <q-btn
          v-if="financeiro"
          fab-mini
          color="deep-purple-4"
          icon="post_add"
          @click="novoLancamento()"
        >
          <q-tooltip anchor="top middle" self="bottom middle">
            Lançamento avulso (taxa, tarifa, rendimento, ajuste)
          </q-tooltip>
        </q-btn>
        <q-btn fab icon="sync_alt" color="primary" @click="novaTransferencia">
          <q-tooltip anchor="top middle" self="bottom middle">Nova transferência</q-tooltip>
        </q-btn>
      </div>
    </q-page-sticky>

    <!-- fechar período -->
    <q-dialog v-model="dialogFechar">
      <q-card flat style="width: 400px; max-width: 90vw">
        <q-form @submit.prevent="fechar">
          <q-card-section class="text-h6">Fechar {{ periodoFechar?.portador }}</q-card-section>
          <q-card-section v-if="periodoFechar?.corrente">
            <MgInputData
              v-model="corte"
              label="Corte (o que vier depois vai para o período novo)"
              :rules="[(v) => !!v]"
              lazy-rules
              autofocus
            />
          </q-card-section>
          <q-card-section v-else>
            Fechar o {{ periodoFechar?.descricao }}? Fecha-se do mais antigo para o mais novo.
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn flat label="Fechar" color="primary" type="submit" :loading="store.salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- detalhe do período: lançamentos -->
    <q-dialog v-model="store.dialogPeriodo">
      <q-card flat style="width: 600px; max-width: 95vw">
        <q-card-section v-if="store.periodo" class="row items-center">
          <div class="col">
            <div class="text-h6">{{ store.periodo.portador }}</div>
            <div class="text-caption text-grey-7">{{ store.periodo.descricao }}</div>
          </div>
          <q-btn
            v-if="store.periodo.aberto"
            flat
            round
            size="sm"
            color="grey-7"
            icon="post_add"
            @click="novoLancamento(store.periodo.codportador)"
          >
            <q-tooltip>Lançamento avulso</q-tooltip>
          </q-btn>
        </q-card-section>
        <q-card-section v-if="store.periodo" class="row q-col-gutter-md text-center">
          <div class="col">
            <div class="text-caption text-grey-7">Inicial</div>
            <div>{{ formataNumero(store.periodo.saldoinicial) }}</div>
          </div>
          <div class="col">
            <div class="text-caption text-grey-7">Lançamentos</div>
            <div>{{ formataNumero(store.periodo.movimento) }}</div>
          </div>
          <div class="col">
            <div class="text-caption text-grey-7">
              {{ store.periodo.aberto ? 'Até agora' : 'Final' }}
            </div>
            <div class="text-weight-bold">{{ formataNumero(store.periodo.saldofinal) }}</div>
          </div>
        </q-card-section>
        <q-list v-if="store.periodo?.lancamentos?.length" separator>
          <q-item
            v-for="l in store.periodo.lancamentos"
            :key="l.codportadormovimento"
            :to="{ name: 'pagamento-detalhe', params: { id: l.codpagamento } }"
          >
            <q-item-section>
              <q-item-label :class="l.inativo ? 'text-strike text-grey-6' : ''">
                {{ l.documento }}
              </q-item-label>
              <q-item-label caption>
                {{ formataCodigo(l.codpagamento) }} · {{ l.origemdescricao }} ·
                {{ l.meiodescricao }}
                <template v-if="l.estado === 'P'"> · a confirmar</template>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label
                class="text-weight-bold"
                :class="
                  l.inativo
                    ? 'text-strike text-grey-6'
                    : l.valor > 0
                      ? 'text-green-8'
                      : 'text-red-8'
                "
              >
                {{ l.valor > 0 ? '+' : '' }}{{ formataNumero(l.valor) }}
              </q-item-label>
              <q-item-label caption>{{ formataTimestamp(l.transacao, 2) }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
        <MgEmptyState v-else-if="store.periodo" plain icon="receipt_long">
          Nenhum lançamento.
        </MgEmptyState>
        <q-card-actions align="right">
          <q-btn flat label="Fechar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- lançamento avulso -->
    <q-dialog v-model="store.dialogLancamento">
      <q-card flat style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="salvarLancamento">
          <q-card-section class="text-h6">Lançamento avulso</q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectPortador
                  v-model="lancamento.codportador"
                  label="Portador"
                  :tipos="['E', 'B', 'A', 'C']"
                  sem-gaveta
                  autofocus
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <q-select
                  v-model="lancamento.motivo"
                  :options="MOTIVOS"
                  emit-value
                  map-options
                  outlined
                  label="Motivo"
                />
              </div>
              <div class="col-12">
                <q-btn-toggle
                  v-model="lancamento.sentido"
                  spread
                  no-caps
                  unelevated
                  toggle-color="primary"
                  color="grey-3"
                  text-color="grey-9"
                  :options="[
                    { label: 'Entrou', value: 1 },
                    { label: 'Saiu', value: -1 },
                  ]"
                />
              </div>
              <div class="col-6">
                <MgInputValor
                  v-model="lancamento.valor"
                  label="Valor"
                  :rules="[(v) => v > 0]"
                  lazy-rules
                />
              </div>
              <div class="col-6">
                <MgInputData
                  v-model="lancamento.transacao"
                  label="Data"
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="lancamento.observacoes"
                  label="Observação"
                  type="textarea"
                  autogrow
                  maxlength="300"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn flat label="Lançar" color="primary" type="submit" :loading="store.salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="store.dialogTransferir">
      <q-card flat style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="salvar">
          <q-card-section class="text-h6">Nova transferência</q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <MgSelectPortador
                  v-model="form.codportadororigem"
                  label="De"
                  :tipos="['E', 'B']"
                  agrupar
                  :codfilial="store.filtros.codfilial"
                  :bloqueios="bloqueios"
                  autofocus
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgSelectPortador
                  v-model="form.codportadordestino"
                  label="Para"
                  :tipos="['E', 'B']"
                  agrupar
                  :codfilial="store.filtros.codfilial"
                  :excluir="form.codportadororigem ? [form.codportadororigem] : null"
                  :bloqueios="bloqueios"
                  :rules="[(v) => !!v]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgInputValor
                  v-model="form.valor"
                  label="Valor"
                  :rules="[(v) => v > 0]"
                  lazy-rules
                />
              </div>
              <div class="col-12">
                <MgInput
                  v-model="form.observacoes"
                  label="Observação"
                  type="textarea"
                  autogrow
                  maxlength="300"
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup />
            <q-btn
              flat
              label="Transferir"
              color="primary"
              type="submit"
              :loading="store.salvando"
            />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
