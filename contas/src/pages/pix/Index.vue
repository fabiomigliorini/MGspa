<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/services/api'
import { usePixStore } from 'src/stores/pixStore'
import { notifyError } from 'src/utils/notify'
import { formataCPF, formataCNPJ, formatMoney, tempoRelativo } from 'src/utils/formatters'

const store = usePixStore()

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
      <div class="q-pa-md" style="max-width: 850px; margin: auto">
        <q-list v-if="store.items.length > 0" bordered separator class="bg-white rounded-borders">
          <q-item v-for="item in store.items" :key="item.e2eid || item.codpix || item.codpixcob">
            <!-- Avatar status -->
            <q-item-section avatar>
              <q-avatar
                v-if="item.codpix == null"
                icon="hourglass_empty"
                color="red"
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
                {{ formataCPF(item.cpf) }}
                {{ formataCNPJ(item.cnpj) }}
              </q-item-label>
              <q-item-label caption>{{ item.portador }}</q-item-label>
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
              <q-item-label v-if="item.txid" caption>{{ item.txid }}</q-item-label>
              <q-item-label v-if="item.e2eid" caption>{{ item.e2eid }}</q-item-label>
            </q-item-section>

            <!-- Col 3: Valor e tempo -->
            <q-item-section side>
              <q-item-label>
                <span class="text-grey text-caption">R$</span>
                <span class="text-weight-bold">
                  {{ formatMoney(item.valor).replace('R$', '').trim() }}
                </span>
              </q-item-label>
              <q-item-label caption>
                <q-tooltip>
                  {{ new Date(item.horario).toLocaleString('pt-BR') }}
                </q-tooltip>
                {{ tempoRelativo(item.horario) }}
              </q-item-label>
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

    <!-- Dialog Consultar PIX -->
    <q-dialog v-model="dialogConsulta" persistent>
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
