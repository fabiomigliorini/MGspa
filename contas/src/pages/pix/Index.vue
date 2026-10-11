<script setup>
// PIX recebidos. Sem uso = PIX que ninguém usou num pagamento nem marcou (TASK-219): daqui se
// baixa títulos com ele (Receber Títulos), lança adiantamento com o valor dele ou marca como
// usado (já baixado antes do sistema pegar o PIX). O usado mostra o pagamento que o usou.
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { usePixStore } from 'src/stores/pixStore'
import { useAuthStore } from 'src/stores/auth'
import { notifyError, notifySuccess } from 'src/utils/notify'
import { contextoCobranca, FORMAS_ADIANTAMENTO } from 'src/utils/cobranca'
import MgAdiantamentoDialog from '@components/MgAdiantamentoDialog.vue'
import {
  formataCodigo,
  formataCpf,
  formataCnpj,
  formataNumero,
  formataTimestamp,
  tempoRelativo,
} from '@components/formatters'

const $q = useQuasar()
const store = usePixStore()
const auth = useAuthStore()

const descricaoPix = (pix) =>
  [`PIX de R$ ${formataNumero(pix.valor)}`, pix.nome, pix.portador, formataTimestamp(pix.horario)]
    .filter(Boolean)
    .join(' · ')

// adiantamento com o PIX inteiro, para a pessoa do CPF/CNPJ do pagador (quando cadastrada)
const dialogAdiantamento = ref(false)
const pixAdiantamento = ref(null)
const abrirAdiantamento = async (item) => {
  try {
    const pix = await store.carregarUso(item.codpix)
    if (pix.utilizacao) {
      notifyError('Este PIX já foi usado.')
      return
    }
    pixAdiantamento.value = {
      codpix: pix.codpix,
      livre: pix.valor,
      descricao: descricaoPix(pix),
      codpessoa: pix.codpessoa,
      entrada: true,
    }
    dialogAdiantamento.value = true
  } catch (error) {
    notifyError(error, 'Erro ao carregar o PIX')
  }
}
// o diálogo já avisa que lançou
const adiantamentoLancado = () => store.carregarUso(pixAdiantamento.value.codpix)

const marcar = (item) => {
  $q.dialog({
    title: 'Marcar como usado',
    message: `${descricaoPix(item)}: já foi baixado fora daqui?`,
    cancel: true,
  }).onOk(async () => {
    try {
      await store.marcar(item.codpix)
      notifySuccess('PIX marcado como usado!')
    } catch (error) {
      notifyError(error, 'Erro ao marcar o PIX')
    }
  })
}

const desmarcar = (item) => {
  $q.dialog({
    title: 'Desmarcar',
    message: `${descricaoPix(item)} volta a ficar sem uso.`,
    cancel: true,
  }).onOk(async () => {
    try {
      await store.desmarcar(item.codpix)
      notifySuccess('PIX desmarcado!')
    } catch (error) {
      notifyError(error, 'Erro ao desmarcar o PIX')
    }
  })
}

// Dialog consultar PIX
const dialogConsulta = ref(false)
const portadoresConsulta = ref([])
let consultaCancelada = false

const consultaFinalizada = computed(
  () =>
    portadoresConsulta.value.length > 0 &&
    portadoresConsulta.value.every((p) => p.concluido || p.erro),
)

const abrirNegocio = (codnegocio) => {
  window.open(process.env.MGSIS_URL + '/index.php?r=negocio/view&id=' + codnegocio, '_blank')
}

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

// Consultar PIX
const refresh = async () => {
  consultaCancelada = false
  portadoresConsulta.value = []
  dialogConsulta.value = true

  try {
    const { data } = await api.get('v1/pix/portadores')
    portadoresConsulta.value = data.map((p) => ({
      codportador: p.codportador,
      portador: p.portador,
      consultando: true,
      concluido: false,
      erro: null,
      processados: 0,
      paginaAtual: 0,
      totalPaginas: null,
      progresso: 0,
    }))
  } catch (error) {
    dialogConsulta.value = false
    notifyError(error, 'Erro ao buscar portadores')
    return
  }

  await Promise.all(portadoresConsulta.value.map((p) => consultarPortador(p)))

  if (consultaCancelada) return

  if (!portadoresConsulta.value.some((p) => p.erro)) {
    dialogConsulta.value = false
  }

  store.fetchItems(true)
}

const consultarPortador = async (p) => {
  try {
    while (!consultaCancelada) {
      const { data: ret } = await api.post('v1/pix/' + p.codportador + '/consultar', {
        pagina: p.paginaAtual,
      })
      p.processados += (ret.pix || []).length

      const pag = ret.parametros?.paginacao ?? null
      if (pag) {
        p.totalPaginas = pag.quantidadeDePaginas
        p.progresso = (pag.paginaAtual + 1) / pag.quantidadeDePaginas
        if (pag.paginaAtual < pag.quantidadeDePaginas - 1) {
          p.paginaAtual = pag.paginaAtual + 1
        } else {
          break
        }
      } else {
        p.progresso = 1
        break
      }
    }
    p.concluido = true
    p.consultando = false
  } catch (error) {
    p.consultando = false
    p.progresso = 1
    p.erro = error.response?.data?.message || error.message
  }
}

const cancelarConsulta = () => {
  consultaCancelada = true
  dialogConsulta.value = false
  store.fetchItems(true)
}

onMounted(() => {
  store.fetchItems(true)
})
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="max-width: 1086px; margin: auto">
        <q-list v-if="store.items.length > 0" bordered separator class="bg-white rounded-borders">
          <q-item v-for="item in store.items" :key="item.e2eid || item.codpix || item.codpixcob">
            <!-- Avatar status: sem uso ou usado -->
            <q-item-section avatar>
              <q-avatar
                v-if="item.codpix == null"
                icon="hourglass_empty"
                color="red"
                text-color="white"
                size="40px"
              />
              <q-avatar
                v-else-if="!item.utilizacao"
                icon="hourglass_empty"
                color="orange"
                text-color="white"
                size="40px"
              />
              <q-avatar v-else icon="done" color="green" text-color="white" size="40px" />
            </q-item-section>

            <!-- Col 1: Nome, CPF/CNPJ, Portador -->
            <q-item-section>
              <q-item-label>
                <template v-if="item.codpix == null">Aguardando Pagamento</template>
                <template v-else-if="!item.nome">Sem Identificacao de Nome</template>
                <template v-else>{{ item.nome }}</template>
              </q-item-label>
              <q-item-label caption>
                {{ formataCpf(item.cpf) }}
                {{ formataCnpj(item.cnpj) }}
              </q-item-label>
              <q-item-label caption>{{ item.portador }}</q-item-label>
              <q-item-label v-if="!item.utilizacao" caption class="text-orange-9">
                Sem uso
              </q-item-label>
              <q-item-label v-else-if="item.codpagamento" caption>
                <router-link
                  :to="{ name: 'pagamento-detalhe', params: { id: item.codpagamento } }"
                  class="text-primary"
                  style="text-decoration: none"
                >
                  Pagamento {{ formataCodigo(item.codpagamento) }}
                </router-link>
              </q-item-label>
              <q-item-label v-else caption>
                Marcado como usado
                <template v-if="item.usuarioutilizacao"> por {{ item.usuarioutilizacao }}</template>
                em {{ formataTimestamp(item.utilizacao) }}
              </q-item-label>
            </q-item-section>

            <!-- Col 2: Negocio, TXID, E2EID (hidden on xs) -->
            <q-item-section class="gt-xs">
              <q-item-label
                v-if="item.codnegocio"
                caption
                class="cursor-pointer text-primary"
                @click="abrirNegocio(item.codnegocio)"
              >
                Negocio #{{ String(item.codnegocio).padStart(8, '0') }}
              </q-item-label>
              <q-item-label v-if="item.codpixcob" caption>
                QR Code
                <template v-if="item.usuariocobranca"> de {{ item.usuariocobranca }}</template>
                <template v-if="item.pdv"> · {{ item.pdv }}</template>
                <template v-if="item.cobrancacriacao">
                  · {{ formataTimestamp(item.cobrancacriacao) }}
                </template>
              </q-item-label>
              <q-item-label v-if="item.txid" caption>{{ item.txid }}</q-item-label>
              <q-item-label v-if="item.e2eid" caption>{{ item.e2eid }}</q-item-label>
            </q-item-section>

            <!-- Col 3: Valor e tempo -->
            <q-item-section side>
              <q-item-label>
                <span class="text-grey text-caption">R$</span>
                <span class="text-weight-bold">
                  {{ formataNumero(item.valor).replace('R$', '').trim() }}
                </span>
              </q-item-label>
              <q-item-label caption>
                <q-tooltip>
                  {{ new Date(item.horario).toLocaleString('pt-BR') }}
                </q-tooltip>
                {{ tempoRelativo(item.horario) }}
              </q-item-label>
            </q-item-section>

            <!-- Usar o PIX: baixar títulos, adiantamento ou marcar; o marcado à mão desmarca -->
            <q-item-section side>
              <div class="row no-wrap">
                <template v-if="!item.utilizacao">
                  <q-btn
                    flat
                    round
                    size="sm"
                    icon="receipt_long"
                    color="grey-7"
                    :to="{ name: 'pagamento-novo', query: { codpix: item.codpix } }"
                  >
                    <q-tooltip>Baixar títulos</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    round
                    size="sm"
                    icon="savings"
                    color="grey-7"
                    @click="abrirAdiantamento(item)"
                  >
                    <q-tooltip>Adiantamento</q-tooltip>
                  </q-btn>
                  <q-btn flat round size="sm" icon="done" color="grey-7" @click="marcar(item)">
                    <q-tooltip>Marcar como usado</q-tooltip>
                  </q-btn>
                </template>
                <q-btn
                  v-else-if="!item.codpagamento"
                  flat
                  round
                  size="sm"
                  icon="remove_done"
                  color="grey-7"
                  @click="desmarcar(item)"
                >
                  <q-tooltip>Desmarcar</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-else-if="!store.loading" class="text-center text-grey-6 q-pa-xl">
          Nenhum Pix encontrado
        </div>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <!-- FAB Refresh -->
    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="refresh" color="primary" :disable="dialogConsulta" @click="refresh()">
        <q-tooltip>Consultar Pix nos bancos</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <MgAdiantamentoDialog
      v-model="dialogAdiantamento"
      :formas="FORMAS_ADIANTAMENTO"
      :contexto="contextoCobranca"
      :finalizar="{ url: 'v1/titulo/adiantamento', extras: {} }"
      com-data
      com-filial
      :filial-padrao="auth.usuario?.codfilial ?? null"
      :pagamento="pixAdiantamento"
      @finalizado="adiantamentoLancado"
    />

    <!-- Dialog Consultar PIX -->
    <q-dialog v-model="dialogConsulta">
      <q-card bordered flat style="width: 500px; max-width: 90vw">
        <q-card-section>
          <div class="text-h6">Consultar PIX</div>
        </q-card-section>
        <q-card-section>
          <div v-for="p in portadoresConsulta" :key="p.codportador" class="q-mb-md">
            <div class="row items-center no-wrap q-mb-xs">
              <div class="col text-body2 ellipsis">{{ p.portador }}</div>
              <div class="col-auto q-ml-sm text-caption text-grey-7">
                <template v-if="p.erro">Erro</template>
                <template v-else-if="p.concluido">{{ p.processados }} pix</template>
                <template v-else-if="p.consultando">
                  Pg. {{ p.paginaAtual + 1 }}
                  <template v-if="p.totalPaginas"> / {{ p.totalPaginas }}</template>
                </template>
              </div>
            </div>
            <q-linear-progress
              :value="p.progresso"
              :color="p.erro ? 'red-5' : p.concluido ? 'green-5' : 'primary'"
              :indeterminate="p.consultando && p.totalPaginas === null"
              rounded
              size="8px"
            />
            <div v-if="p.erro" class="text-caption text-red q-mt-xs">{{ p.erro }}</div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            v-if="!consultaFinalizada"
            flat
            label="Cancelar"
            color="red"
            @click="cancelarConsulta()"
          />
          <q-btn v-else flat label="Fechar" color="grey-8" @click="dialogConsulta = false" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>
