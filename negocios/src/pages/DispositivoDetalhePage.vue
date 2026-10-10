<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import {
  formataCodigo,
  formataData,
  formataHora,
  formataNumero,
  formataTimestamp,
  tempoRelativo,
} from '@components/formatters'
import { tituloPagamento } from '@components/cobranca/pagamento.js'
import { dispositivoStore } from 'stores/dispositivo'
import { sincronizacaoStore } from 'stores/sincronizacao'
import { statusDispositivo } from 'src/utils/dispositivo'
import { useAuth } from 'src/composables/useAuth'
import DialogDispositivo from 'components/pdv/DialogDispositivo.vue'

// Página do dispositivo (TASK-46): status e ações no cabeçalho; os cards com os mesmos grupos, a
// mesma ordem e os mesmos nomes do formulário (DialogDispositivo) — dispositivo, negócios,
// pagamento e monitoramento —; e os últimos negócios, pagamentos, ocorrências e localizações
// (IP e posição de cada sincronização). Serve a lista
// (/dispositivo/:codpdv) e o "Meu Dispositivo" (/dispositivo/meu), atalho para o deste
// navegador; sem cadastro, ele mostra o Cadastrar. O servidor diz o que o usuário pode fazer
// aqui (`pode`); a tela só mostra os botões. Sem login (quiosque) a página só mostra.

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const { estaAutenticado, expiresAt, login } = useAuth()
const sDispositivo = dispositivoStore()
const sSinc = sincronizacaoStore()
const { dispositivo: d, pode, registros, erro } = storeToRefs(sDispositivo)

const codpdv = computed(() => (route.params.codpdv ? Number(route.params.codpdv) : null))
const status = computed(() => statusDispositivo(d.value))

const dialogEditar = ref(false)

const vazio = (valor) => valor ?? '—'

const mapa = (l) =>
  l.latitude ? `https://maps.google.com/maps?q=${l.latitude},${l.longitude}` : undefined

// cada linha do histórico é um período no mesmo IP e posição: da 1ª à última sincronização
const periodo = (l) => {
  const inicio = formataTimestamp(l.criacao)
  const ultima = formataTimestamp(l.alteracao)
  // no mesmo minuto, "14:46 → 14:46" não diz nada
  if (l.sincronizacoes <= 1 || !l.alteracao || ultima === inicio) {
    return inicio
  }
  const fim =
    formataData(l.alteracao) === formataData(l.criacao) ? formataHora(l.alteracao) : ultima
  return `${inicio} → ${fim}`
}

// os mesmos grupos, a mesma ordem e os mesmos nomes do formulário (DialogDispositivo); no
// dispositivo, depois do que se edita, o que o navegador informou na última sincronização
const grupos = computed(() => [
  {
    titulo: 'Dispositivo',
    legenda: 'E o navegador, da última sincronização',
    cor: 'blue-grey',
    icone: 'devices',
    campos: [
      { label: 'Apelido', valor: vazio(d.value.apelido), icone: 'badge' },
      { label: 'Observações', valor: vazio(d.value.observacoes), icone: 'notes', classe: '' },
      { label: 'UUID', valor: d.value.uuid, icone: 'fingerprint', classe: 'text-caption' },
      {
        label: 'Navegador',
        valor: `${d.value.plataforma} ${d.value.navegador} ${d.value.versaonavegador}`,
        icone: d.value.desktop ? 'desktop_windows' : 'smartphone',
      },
      {
        label: 'Última sincronização completa',
        valor: d.value.sincronizacaocompleta
          ? `${formataTimestamp(d.value.sincronizacaocompleta)} · ${tempoRelativo(d.value.sincronizacaocompleta)}`
          : '—',
        icone: 'sync',
      },
    ],
  },
  {
    titulo: 'Negócios',
    legenda: 'Padrões dos negócios deste PDV',
    cor: 'blue',
    icone: 'storefront',
    campos: [
      { label: 'Local de Estoque', valor: vazio(d.value.estoquelocal), icone: 'inventory_2' },
      { label: 'Setor', valor: vazio(d.value.setor), icone: 'groups' },
      {
        label: 'Natureza da Operação',
        valor: vazio(d.value.naturezaoperacao),
        icone: 'swap_horiz',
      },
      { label: 'Impressora', valor: vazio(d.value.impressora), icone: 'print' },
    ],
  },
  {
    titulo: 'Pagamento',
    legenda: 'Pré-selecionados no recebimento',
    cor: 'green',
    icone: 'payments',
    campos: [
      {
        label: 'Portador da gaveta de dinheiro',
        valor: vazio(d.value.portador),
        icone: 'mdi-cash',
      },
      { label: 'Maquineta padrão', valor: vazio(d.value.maquineta), icone: 'point_of_sale' },
      { label: 'PIX padrão', valor: vazio(d.value.portadorpix), icone: 'pix' },
    ],
  },
  {
    titulo: 'Monitoramento',
    legenda: 'Livro de ocorrências',
    cor: 'orange',
    icone: 'policy',
    campos: [
      {
        label: 'Monitorar a partir de',
        valor: d.value.monitoramento ? formataData(d.value.monitoramento) : 'Não monitora',
        icone: 'event',
      },
      {
        label: 'Minutos até o negócio esquecido',
        valor: vazio(d.value.minutosesquecido),
        icone: 'timer',
      },
    ],
  },
])

const linkPagamento = (pag) => `${process.env.CONTAS_URL}/pagamento/${pag.codpagamento}`

function confirmar(titulo, mensagem, acao) {
  $q.dialog({
    title: titulo,
    message: mensagem,
    ok: { label: titulo, color: 'primary', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(acao)
}

// ativar é autorizar: o dispositivo passa a vender e sincronizar
const ativar = () =>
  confirmar('Ativar', `Ativar o dispositivo "${d.value.apelido}"?`, () =>
    sDispositivo.ativar(d.value),
  )

const inativar = () =>
  confirmar('Inativar', `Inativar o dispositivo "${d.value.apelido}"?`, () =>
    sDispositivo.inativar(d.value),
  )

const cadastrando = ref(false)

// cadastrar exige usuário logado: sem ele (ou com a sessão vencida) avisa e abre o login; depois
// de entrar, o Cadastrar é outro clique
async function cadastrar() {
  const vencida = expiresAt.value && new Date(expiresAt.value) < new Date()
  if (!estaAutenticado.value || vencida) {
    $q.notify({
      type: 'negative',
      message: vencida
        ? 'Sua sessão expirou. Entre de novo para cadastrar o dispositivo.'
        : 'Entre com seu usuário para cadastrar o dispositivo.',
    })
    login()
    return
  }
  cadastrando.value = true
  const ok = await sSinc.cadastrar()
  cadastrando.value = false
  if (ok) {
    router.replace(`/dispositivo/${sSinc.pdv.codpdv}`)
  }
}

async function carregar() {
  // Meu Dispositivo: abre a página do dispositivo deste navegador, se já tiver cadastro
  if (!codpdv.value) {
    if (sSinc.pdv.codpdv) {
      router.replace(`/dispositivo/${sSinc.pdv.codpdv}`)
    }
    return
  }
  await sDispositivo.buscar(codpdv.value)
  if (d.value) {
    sDispositivo.carregarRegistros(codpdv.value)
  }
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md bg-grey-2">
    <div style="max-width: 1086px; margin: auto">
      <MgEmptyState v-if="!codpdv && !sSinc.pdv.codpdv" icon="devices">
        Este navegador ainda não está cadastrado como dispositivo.
        <div class="q-mt-md">
          <q-btn
            flat
            color="primary"
            icon="add"
            label="Cadastrar"
            :loading="cadastrando"
            @click="cadastrar"
          />
        </div>
      </MgEmptyState>

      <MgEmptyState v-else-if="codpdv && erro" icon="block">{{ erro }}</MgEmptyState>

      <template v-else-if="codpdv && d">
        <!-- CABEÇALHO -->
        <q-card flat bordered class="q-mb-md">
          <q-card-section class="row items-center">
            <div class="col-12 col-sm row items-center no-wrap">
              <q-avatar :icon="status.icone" :color="status.cor" text-color="white" />
              <div class="col q-ml-md" style="min-width: 0">
                <div class="text-h6 ellipsis" :class="{ 'text-strike text-grey-6': d.inativo }">
                  {{ d.apelido || 'Sem apelido' }}
                </div>
                <div class="text-caption text-grey-7">
                  <q-badge :color="status.cor" :label="status.label" class="q-mr-xs" />
                  {{ formataCodigo(d.codpdv) }} · {{ d.filial }} · {{ d.setor }}
                  <template v-if="sDispositivo.proprio"> · este dispositivo</template>
                </div>
              </div>
            </div>
            <div
              class="col-12 col-sm-auto row items-center justify-end no-wrap"
              :class="{ 'q-mt-sm': $q.screen.lt.sm }"
            >
              <MgInfoCriacao v-if="estaAutenticado" :registro="d" />
              <q-btn
                v-if="pode.editar"
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="dialogEditar = true"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="pode.ativar"
                flat
                round
                size="sm"
                color="grey-7"
                :icon="d.inativo ? 'play_arrow' : 'pause'"
                :disable="sDispositivo.salvando"
                @click="d.inativo ? ativar() : inativar()"
              >
                <q-tooltip>{{ d.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
            </div>
          </q-card-section>
        </q-card>

        <q-banner
          v-if="sDispositivo.proprio && d.inativo"
          rounded
          class="bg-orange-1 text-orange-10 q-mb-md"
        >
          <template #avatar>
            <q-icon name="hourglass_empty" color="orange-8" />
          </template>
          Este dispositivo está inativo: não vende nem sincroniza. Peça a um administrador para
          ativá-lo aqui nesta página ou na lista de Dispositivos, pelo UUID {{ d.uuid }}.
        </q-banner>

        <!-- os mesmos grupos e a mesma ordem do formulário -->
        <div class="row q-col-gutter-md q-mb-md">
          <div v-for="g in grupos" :key="g.titulo" class="col-12 col-md-6">
            <q-card flat bordered class="full-height">
              <q-item>
                <q-item-section avatar>
                  <q-avatar :color="`${g.cor}-1`" :text-color="`${g.cor}-8`" :icon="g.icone" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-subtitle1 text-weight-medium">{{
                    g.titulo
                  }}</q-item-label>
                  <q-item-label caption>{{ g.legenda }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-separator />
              <q-list>
                <q-item v-for="c in g.campos" :key="c.label">
                  <q-item-section avatar>
                    <q-icon :name="c.icone" color="grey-6" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label caption>{{ c.label }}</q-item-label>
                    <q-item-label :class="c.classe ?? 'ellipsis'">{{ c.valor }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>

        <!-- últimos registros, dois por linha: localizações e ocorrências; pagamentos e negócios -->
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <!-- LOCALIZAÇÕES: o que o navegador informou em cada sincronização. Desktop sem GPS é
            localizado pelo IP público (cidade, não a loja): a precisão diz o quanto confiar -->
            <q-card flat bordered class="full-height">
              <q-item>
                <q-item-section avatar>
                  <q-avatar color="blue-grey-1" text-color="blue-grey-8" icon="place" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-subtitle1 text-weight-medium"
                    >Localizações</q-item-label
                  >
                  <q-item-label caption>Linha nova quando muda o IP ou a posição</q-item-label>
                </q-item-section>
              </q-item>
              <q-separator />
              <MgEmptyState v-if="!registros.localizacoes.length" plain icon="place">
                Nenhuma sincronização registrada.
              </MgEmptyState>
              <q-list v-else separator>
                <q-item
                  v-for="l in registros.localizacoes"
                  :key="l.codpdvlocalizacao"
                  :clickable="!!mapa(l)"
                  :href="mapa(l)"
                  target="_blank"
                >
                  <q-item-section>
                    <q-item-label class="ellipsis">{{ periodo(l) }}</q-item-label>
                    <q-item-label caption class="ellipsis">
                      {{ l.ip || 'Sem IP' }} ·
                      {{ l.latitude ? `${l.latitude}, ${l.longitude}` : 'Sem localização' }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ l.sincronizacoes }}
                      {{ l.sincronizacoes == 1 ? 'sincronização' : 'sincronizações' }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section v-if="l.precisao" side>
                    <q-item-label>± {{ formataNumero(l.precisao, 0) }} m</q-item-label>
                    <q-item-label caption>Precisão</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
          <div class="col-12 col-md-6">
            <!-- ÚLTIMAS OCORRÊNCIAS -->
            <q-card flat bordered class="full-height">
              <q-item>
                <q-item-section avatar>
                  <q-avatar color="orange-1" text-color="orange-8" icon="report" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-subtitle1 text-weight-medium">
                    Últimas ocorrências
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator />
              <MgEmptyState v-if="!registros.ocorrencias.length" plain icon="report">
                Nenhuma ocorrência neste dispositivo.
              </MgEmptyState>
              <q-list v-else separator>
                <q-item
                  v-for="o in registros.ocorrencias"
                  :key="o.codocorrencia"
                  :clickable="!!o.codnegocio"
                  :to="o.codnegocio ? `/negocio/${o.codnegocio}` : undefined"
                >
                  <q-item-section>
                    <q-item-label class="ellipsis">{{ o.tipodescricao }}</q-item-label>
                    <q-item-label caption class="ellipsis">
                      {{ formataTimestamp(o.criacao) }} · {{ o.descricao }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label>{{ formataNumero(o.valor) }}</q-item-label>
                    <q-item-label caption>{{
                      o.conferencia ? 'Conferida' : 'Pendente'
                    }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
          <div class="col-12 col-md-6">
            <!-- ÚLTIMOS PAGAMENTOS -->
            <q-card flat bordered class="full-height">
              <q-item>
                <q-item-section avatar>
                  <q-avatar color="green-1" text-color="green-8" icon="payments" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-subtitle1 text-weight-medium">
                    Últimos pagamentos
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-separator />
              <MgEmptyState v-if="!registros.pagamentos.length" plain icon="payments">
                Nenhum pagamento neste dispositivo.
              </MgEmptyState>
              <q-list v-else separator>
                <q-item
                  v-for="p in registros.pagamentos"
                  :key="p.codpagamento"
                  clickable
                  :href="linkPagamento(p)"
                  target="_blank"
                >
                  <q-item-section>
                    <q-item-label class="ellipsis">
                      {{ tituloPagamento(p) }}
                      <template v-if="p.fantasia"> · {{ p.fantasia }}</template>
                    </q-item-label>
                    <q-item-label caption class="ellipsis">
                      {{ formataTimestamp(p.transacao) }}
                      <template v-if="p.codnegocio">
                        · Negócio {{ formataCodigo(p.codnegocio) }}</template
                      >
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label :class="{ 'text-strike': p.estado === 'C' }">
                      {{ formataNumero(p.total) }}
                    </q-item-label>
                    <q-item-label caption>{{ p.estadodescricao }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
          <div class="col-12 col-md-6">
            <!-- ÚLTIMOS NEGÓCIOS -->
            <q-card flat bordered class="full-height">
              <q-item>
                <q-item-section avatar>
                  <q-avatar color="indigo-1" text-color="indigo-8" icon="receipt_long" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-subtitle1 text-weight-medium"
                    >Últimos negócios</q-item-label
                  >
                </q-item-section>
              </q-item>
              <q-separator />
              <MgEmptyState v-if="!registros.negocios.length" plain icon="receipt_long">
                Nenhum negócio neste dispositivo.
              </MgEmptyState>
              <q-list v-else separator>
                <q-item
                  v-for="n in registros.negocios"
                  :key="n.codnegocio"
                  clickable
                  :to="`/negocio/${n.codnegocio}`"
                >
                  <q-item-section>
                    <q-item-label class="ellipsis">
                      {{ n.naturezaoperacao }} · {{ n.fantasia || 'Sem pessoa' }}
                    </q-item-label>
                    <q-item-label caption class="ellipsis">
                      {{ formataCodigo(n.codnegocio) }} · {{ formataTimestamp(n.lancamento) }} ·
                      {{ n.usuario }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-item-label :class="{ 'text-strike': n.codnegociostatus == 3 }">
                      {{ formataNumero(n.valortotal) }}
                    </q-item-label>
                    <q-item-label caption>{{ n.negociostatus }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>
      </template>
    </div>

    <DialogDispositivo v-if="d" v-model="dialogEditar" :pdv="d" />
  </q-page>
</template>
