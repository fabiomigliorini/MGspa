<script setup>
// Tela do caixa (M13 do plano doc-3): uma só, no PDV (negocios /caixa, gaveta do PDV) e no
// contas (Fechamentos → caixa). Abre contando cédulas, moedas e o estoque dos itens; durante a
// sessão mantém os itens dos parceiros, os avulsos e as transferências; fecha quem estiver com o
// dinheiro (caixa ou gerente), contando o que fica na gaveta. Fechar é a conferência: um ajuste
// se o contado difere do sistema e os títulos de repasse dos itens. Reabrir: gerente.
import { ref, computed, watch, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { formataNumero, formataTimestamp, formataCodigo } from '@components/formatters'
import MgInput from '@components/MgInput.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import ContagemCaixa from '@components/caixa/ContagemCaixa.vue'
import ItemCaixaDialog from '@components/caixa/ItemCaixaDialog.vue'
import AvulsoCaixaDialog from '@components/caixa/AvulsoCaixaDialog.vue'
import TransferirCaixaDialog from '@components/caixa/TransferirCaixaDialog.vue'
import { caixaSessaoStore, DOCUMENTOS } from '@components/stores/caixaSessaoStore'

const props = defineProps({
  // PDV: a gaveta (mostra a sessão aberta, ou abre uma nova)
  codportador: { type: Number, default: null },
  // contas: uma sessão
  codportadorperiodo: { type: Number, default: null },
  codpdv: { type: Number, default: null },
  impressora: { type: String, default: null },
})

const $q = useQuasar()
const store = caixaSessaoStore()

const sessao = computed(() => store.sessao)
const aberta = computed(() => !!sessao.value?.aberta)
const podeOperar = computed(() => sessao.value?.podeOperar ?? true)
const podeAbrir = computed(() => !!props.codportador && !aberta.value && !store.carregando)

const itensC = (itens) => (itens || []).filter((i) => i.modo === 'C')

// contagem: abrir (itens ativos da filial) ou fechar (itens da sessão, com o rascunho de quem já
// contou e reabriu)
const contagem = ref({ contagem: {}, itens: {} })
const observacoes = ref('')

const prepararContagem = () => {
  if (aberta.value) {
    contagem.value = {
      contagem: { ...(sessao.value.contagemfechamento || {}) },
      itens: Object.fromEntries(
        itensC(sessao.value.itens).map((i) => [i.codcaixaitem, i.valorfechamento]),
      ),
    }
  } else {
    contagem.value = { contagem: {}, itens: {} }
  }
  observacoes.value = ''
}

const payload = () => ({
  contagem: Object.fromEntries(
    Object.entries(contagem.value.contagem).filter(([, q]) => Number(q) > 0),
  ),
  itens: Object.fromEntries(
    Object.entries(contagem.value.itens).map(([cod, v]) => [cod, Number(v) || 0]),
  ),
  observacoes: observacoes.value || null,
})

const refContagem = ref(null)

async function abrir() {
  if (await store.abrir(payload())) prepararContagem()
}

function fechar() {
  $q.dialog({
    title: 'Fechar o caixa',
    message: `Fechar com R$ ${formataNumero(refContagem.value?.total ?? 0)} contados na gaveta (o envelope de amanhã)? A diferença para o sistema vira ajuste.`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Fechar', color: 'primary', flat: true },
  }).onOk(async () => {
    if (await store.fechar(payload())) prepararContagem()
  })
}

function reabrir() {
  $q.dialog({
    title: 'Reabrir o caixa',
    message:
      'O caixa volta a ficar aberto: o ajuste do fechamento é desfeito e os títulos de repasse dos itens são estornados. Continuar?',
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Reabrir', color: 'primary', flat: true },
  }).onOk(async () => {
    if (await store.reabrir()) prepararContagem()
  })
}

function editarItem(i) {
  if (!aberta.value || !podeOperar.value) return
  store.item = i
  store.dialogItem = true
}

function excluirAvulso(a) {
  $q.dialog({
    title: 'Excluir lançamento',
    message: `Excluir "${a.observacoes}" de R$ ${formataNumero(a.valor)}?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'negative', flat: true },
  }).onOk(() => store.cancelarAvulso(a.codpagamento))
}

const COR_ESTADO = { P: 'amber-8', E: 'green-7', C: 'red-7' }
const ESTADO = { P: 'A confirmar', E: 'Efetivada', C: 'Cancelada' }

function cancelarTransferencia(t) {
  $q.dialog({
    title: 'Cancelar transferência',
    message: 'Valor diferente? Cancele e registre outra. Motivo:',
    prompt: {
      model: '',
      type: 'text',
      outlined: true,
      isValid: (v) => (v || '').trim().length >= 5,
    },
    cancel: { label: 'Voltar', color: 'grey-8', flat: true },
    ok: { label: 'Cancelar transferência', color: 'negative', flat: true },
  }).onOk((justificativa) => store.acaoTransferencia(t.codpagamento, 'cancelar', { justificativa }))
}

const corValor = (v) => (v < 0 ? 'text-red-8' : v > 0 ? 'text-green-8' : 'text-grey-7')
const resumo = computed(() => sessao.value?.dinheiro)

async function carregar() {
  store.contexto = { codpdv: props.codpdv, impressora: props.impressora }
  if (props.codportadorperiodo) {
    await store.carregarSessao(props.codportadorperiodo)
  } else if (props.codportador) {
    await store.carregarGaveta(props.codportador)
  }
  prepararContagem()
}

onMounted(carregar)
watch(() => [props.codportador, props.codportadorperiodo], carregar)
</script>

<template>
  <div>
    <!-- cabeçalho -->
    <q-card v-if="store.gaveta" flat bordered class="q-mb-md">
      <q-card-section class="row items-center">
        <div class="col">
          <div class="text-h6">{{ store.gaveta.portador }}</div>
          <div v-if="sessao" class="text-caption text-grey-7">
            Aberto por {{ sessao.usuarioabertura }} em {{ formataTimestamp(sessao.inicio, 2) }}
            <template v-if="sessao.fim">
              · fechado por {{ sessao.usuariofechamento }} em
              {{ formataTimestamp(sessao.fim, 2) }}
            </template>
          </div>
        </div>
        <q-badge :color="aberta ? 'green-7' : 'grey-7'" :label="aberta ? 'Aberto' : 'Fechado'" />
      </q-card-section>
    </q-card>

    <!-- abrir -->
    <q-card v-if="podeAbrir" flat bordered class="q-mb-md">
      <q-form @submit.prevent="abrir">
        <q-card-section>
          <div class="text-subtitle2 q-mb-sm">
            Contagem para abrir
            <span class="text-caption text-grey-7">
              · envelope R$ {{ formataNumero(store.envelope) }}
            </span>
          </div>
          <ContagemCaixa v-model="contagem" :itens="itensC(store.itensAtivos)" autofocus />
          <MgInput
            v-model="observacoes"
            label="Observação"
            type="textarea"
            autogrow
            maxlength="250"
            class="q-mt-md"
          />
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            unelevated
            color="primary"
            icon="lock_open"
            label="Abrir caixa"
            type="submit"
            :loading="store.salvando"
          />
        </q-card-actions>
      </q-form>
    </q-card>

    <MgEmptyState v-if="!store.carregando && !sessao && !podeAbrir" icon="point_of_sale">
      Nenhuma sessão de caixa.
    </MgEmptyState>

    <template v-if="sessao">
      <div v-if="!aberta && podeAbrir" class="text-subtitle2 text-grey-7 q-mb-sm">
        Último fechamento
      </div>

      <!-- itens do caixa -->
      <q-card v-if="sessao.itens.length" flat bordered class="q-mb-md">
        <q-card-section class="text-subtitle2 q-pb-sm">Itens do caixa</q-card-section>
        <q-list separator>
          <q-item
            v-for="i in sessao.itens"
            :key="i.codcaixaitem"
            :clickable="aberta && podeOperar"
            @click="editarItem(i)"
          >
            <q-item-section>
              <q-item-label>{{ i.item }}</q-item-label>
              <q-item-label caption>
                <template v-if="i.modo === 'C'">
                  abertura {{ formataNumero(i.valorabertura ?? 0) }}
                  <template v-if="i.valorentrada">
                    · recebido {{ formataNumero(i.valorentrada) }}</template
                  >
                  <template v-if="i.valorsaida">
                    · devolvido {{ formataNumero(i.valorsaida) }}</template
                  >
                  <template v-if="i.valorfechamento !== null">
                    · fechamento {{ formataNumero(i.valorfechamento) }}</template
                  >
                </template>
                <template v-else>
                  <template v-if="i.valorvendido !== null">
                    vendido {{ formataNumero(i.valorvendido) }} ·
                  </template>
                  entrou {{ formataNumero(i.valorentrada) }}
                  <template v-if="i.valorsaida"> · saiu {{ formataNumero(i.valorsaida) }}</template>
                </template>
              </q-item-label>
              <q-item-label v-if="i.observacoes" caption>{{ i.observacoes }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label v-if="i.liquido !== null" class="text-weight-bold">
                R$ {{ formataNumero(i.liquido) }}
              </q-item-label>
              <q-item-label caption>
                <template v-if="i.titulo">título {{ i.titulo }}</template>
                <template v-else-if="!i.parceiro">sem parceiro</template>
                <template v-else-if="i.liquido !== null">a repassar</template>
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- avulsos -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-subtitle2 col">Lançamentos avulsos</div>
          <q-btn
            v-if="aberta && podeOperar"
            flat
            round
            size="sm"
            color="primary"
            icon="add"
            @click="store.dialogAvulso = true"
          >
            <q-tooltip>Lançamento avulso (entrada ou saída)</q-tooltip>
          </q-btn>
        </q-card-section>
        <q-list v-if="sessao.avulsos.length" separator>
          <q-item v-for="a in sessao.avulsos" :key="a.codpagamento">
            <q-item-section>
              <q-item-label :class="a.estado === 'C' ? 'text-strike text-grey-6' : ''">
                {{ a.motivodescricao }} · {{ a.observacoes }}
              </q-item-label>
              <q-item-label caption>
                {{ formataCodigo(a.codpagamento) }} · {{ formataTimestamp(a.transacao, 2) }} ·
                {{ a.usuariocriacao }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label
                class="text-weight-bold"
                :class="a.estado === 'C' ? 'text-strike text-grey-6' : corValor(a.valor)"
              >
                R$ {{ formataNumero(a.valor) }}
              </q-item-label>
            </q-item-section>
            <q-item-section v-if="a.podeExcluir" side>
              <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluirAvulso(a)">
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-list>
        <q-card-section v-else class="text-grey-7 text-caption q-pt-none">
          Nenhum lançamento avulso.
        </q-card-section>
      </q-card>

      <!-- transferências (sangria, suprimento) -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="row items-center q-pb-sm">
          <div class="text-subtitle2 col">Transferências</div>
          <q-btn
            v-if="aberta && podeOperar"
            flat
            round
            size="sm"
            color="primary"
            icon="add"
            @click="store.dialogTransferir = true"
          >
            <q-tooltip>Transferir (sangria, suprimento)</q-tooltip>
          </q-btn>
        </q-card-section>
        <q-list v-if="sessao.transferencias.length" separator>
          <q-item v-for="t in sessao.transferencias" :key="t.codpagamento">
            <q-item-section avatar>
              <q-icon
                :name="t.codportadordestino === sessao.codportador ? 'south_west' : 'north_east'"
                :color="COR_ESTADO[t.estado]"
              />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="t.estado === 'C' ? 'text-strike text-grey-6' : ''">
                {{ t.portadororigem }} → {{ t.portadordestino }}
              </q-item-label>
              <q-item-label caption>
                {{ formataCodigo(t.codpagamento) }} · {{ formataTimestamp(t.transacao, 2) }} ·
                {{ t.usuariocriacao }}
              </q-item-label>
              <q-item-label v-if="t.observacoes" caption>{{ t.observacoes }}</q-item-label>
              <q-item-label v-if="t.estado === 'C'" caption class="text-negative">
                {{ t.justificativa }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-item-label
                class="text-weight-bold"
                :class="t.estado === 'C' ? 'text-strike text-grey-6' : ''"
              >
                R$ {{ formataNumero(t.total) }}
              </q-item-label>
              <q-badge :color="COR_ESTADO[t.estado]" :label="ESTADO[t.estado]" />
            </q-item-section>
            <q-item-section v-if="t.podeConfirmar || t.podeCancelar" side>
              <div class="row no-wrap">
                <q-btn
                  v-if="t.podeConfirmar"
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  icon="done"
                  @click="store.acaoTransferencia(t.codpagamento, 'confirmar')"
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
                  @click="cancelarTransferencia(t)"
                >
                  <q-tooltip>Cancelar</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
        <q-card-section v-else class="text-grey-7 text-caption q-pt-none">
          Nenhuma transferência nesta sessão.
        </q-card-section>
      </q-card>

      <!-- dinheiro: envelope, movimento, sistema, contado e ajustes -->
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="text-subtitle2 q-pb-sm">Dinheiro</q-card-section>
        <q-markup-table flat separator="horizontal">
          <tbody>
            <tr>
              <td>Envelope (sessão anterior)</td>
              <td class="text-right">{{ formataNumero(sessao.saldoinicial) }}</td>
            </tr>
            <tr v-for="d in resumo.documentos" :key="d.documento">
              <td>{{ DOCUMENTOS[d.documento] }} ({{ d.quantidade }})</td>
              <td class="text-right" :class="corValor(d.entrada - d.saida)">
                {{ formataNumero(d.entrada - d.saida) }}
              </td>
            </tr>
            <tr class="text-weight-bold">
              <td>Sistema</td>
              <td class="text-right">{{ formataNumero(resumo.sistema) }}</td>
            </tr>
            <tr v-if="sessao.ajusteabertura">
              <td>Diferença na abertura (no sistema)</td>
              <td class="text-right" :class="corValor(sessao.ajusteabertura)">
                {{ formataNumero(sessao.ajusteabertura) }}
              </td>
            </tr>
            <template v-if="!aberta">
              <tr>
                <td>Contado no fechamento (envelope de amanhã)</td>
                <td class="text-right">{{ formataNumero(sessao.saldofinal) }}</td>
              </tr>
              <tr class="text-weight-bold">
                <td>Diferença no fechamento (no sistema)</td>
                <td class="text-right" :class="corValor(sessao.ajustefechamento)">
                  {{ formataNumero(sessao.ajustefechamento) }}
                </td>
              </tr>
            </template>
          </tbody>
        </q-markup-table>
        <q-card-section v-if="sessao.dinheiro && sessao.informativo.meios.length" class="q-pt-sm">
          <div class="text-caption text-grey-7">
            Outros meios nos PDVs deste caixa (informação):
            <span v-for="m in sessao.informativo.meios" :key="m.meio" class="q-mr-md">
              {{ m.descricao }} {{ formataNumero(m.valor) }} ({{ m.quantidade }})
            </span>
          </div>
        </q-card-section>
      </q-card>

      <!-- fechar -->
      <q-card v-if="aberta && podeOperar" flat bordered class="q-mb-md">
        <q-form @submit.prevent="fechar">
          <q-card-section>
            <div class="text-subtitle2 q-mb-sm">
              Contagem para fechar
              <span class="text-caption text-grey-7"
                >· o que fica na gaveta, depois da sangria</span
              >
            </div>
            <ContagemCaixa ref="refContagem" v-model="contagem" :itens="itensC(sessao.itens)" />
            <MgInput
              v-model="observacoes"
              label="Observação"
              type="textarea"
              autogrow
              maxlength="250"
              class="q-mt-md"
            />
          </q-card-section>
          <q-card-actions align="right">
            <q-btn
              unelevated
              color="deep-orange-7"
              icon="lock"
              label="Fechar caixa"
              type="submit"
              :loading="store.salvando"
            />
          </q-card-actions>
        </q-form>
      </q-card>

      <!-- caixa fechado: borderô e reabrir -->
      <q-card v-if="!aberta" flat bordered class="q-mb-md">
        <q-card-actions align="right">
          <q-btn
            flat
            color="primary"
            icon="print"
            label="Borderô"
            @click="store.imprimirBordero(sessao.codportadorperiodo)"
          />
          <q-btn
            flat
            color="primary"
            icon="picture_as_pdf"
            label="Ver borderô"
            @click="store.abrirBordero(sessao.codportadorperiodo)"
          />
          <q-btn
            v-if="sessao.podeReabrir"
            flat
            color="primary"
            icon="lock_open"
            label="Reabrir caixa"
            @click="reabrir"
          />
        </q-card-actions>
      </q-card>

      <MgInfoCriacao :registro="sessao" />
    </template>

    <ItemCaixaDialog />
    <AvulsoCaixaDialog />
    <TransferirCaixaDialog />
  </div>
</template>
