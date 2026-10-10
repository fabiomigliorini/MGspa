<script setup>
// Painel (TASK-203). Sem filial na URL: as filiais que o usuário vê (Financeiro e Administrador,
// todas; Gerente, as dele), um card por empresa, com o total de pendências de cada filial. Com filial: tudo que está
// pendente nela — caixas, maquinetas, ocorrências, negócios esquecidos abertos, confissões, vendas
// com diferença e PIX a confirmar — de qualquer usuário. Só mostra: cada item leva à tela que
// resolve. Pensada no celular.
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePainelStore } from 'src/stores/painelStore'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'
import { formataNumero, formataTimestamp, tempoRelativo } from '@components/formatters'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgOcorrenciaItem from '@components/MgOcorrenciaItem.vue'

const store = usePainelStore()
const cache = useSelectCacheStore()
const route = useRoute()

const nomeFilial = computed(
  () => (cache.entities.filial?.items || []).find((f) => f.value === store.codfilial)?.label,
)

const urlNegocio = (codnegocio) => `${process.env.NEGOCIOS_URL}/negocio/${codnegocio}`

const situacaoCaixa = (p) => {
  const partes = []
  if (p.sessao?.aberta) {
    partes.push(
      `Aberto desde ${formataTimestamp(p.sessao.inicio, 0)}` +
        (p.sessao.usuarioabertura ? ` por ${p.sessao.usuarioabertura}` : ''),
    )
  }
  if (p.pendentes) partes.push(`${p.pendentes} período(s) pendente(s)`)
  if (p.chegando.quantidade) {
    partes.push(`${p.chegando.quantidade} chegando · R$ ${formataNumero(p.chegando.valor)}`)
  }
  if (p.saindo.quantidade) {
    partes.push(`${p.saindo.quantidade} saindo · R$ ${formataNumero(p.saindo.valor)}`)
  }
  return partes.join(' · ')
}

// as ocorrências pendentes da filial, no livro de ocorrências
const ocorrenciasDaFilial = () => ({
  name: 'ocorrencia',
  query: { codfilial: store.codfilial, situacao: 'pendente' },
})

const conferencia = (tipo) => (store.dados?.conferencia || []).filter((p) => p.tipo === tipo)

// cada bloco: contagem, os itens e para onde cada item leva
const blocos = computed(() => {
  const d = store.dados
  if (!d) return []
  return [
    {
      chave: 'caixas',
      label: 'Caixas',
      icone: 'point_of_sale',
      cor: 'green-8',
      total: d.caixas.length,
      itens: d.caixas.map((p) => ({
        chave: p.codportador,
        titulo: p.portador,
        subtitulo: situacaoCaixa(p),
        to: { name: 'portador-detalhe', params: { codportador: p.codportador } },
      })),
    },
    {
      chave: 'maquinetas',
      label: 'Maquinetas para conciliar',
      icone: 'credit_card',
      cor: 'deep-purple-6',
      total: d.maquinetas.length,
      itens: d.maquinetas.map((m) => ({
        chave: m.codmaquineta,
        titulo: m.apelido,
        subtitulo: [
          m.pendentes ? `${m.pendentes} período(s) pendente(s)` : null,
          m.semBordero ? `${m.semBordero} sem borderô` : null,
        ]
          .filter(Boolean)
          .join(' · '),
        to: { name: 'maquineta-detalhe', params: { codmaquineta: m.codmaquineta } },
      })),
    },
    {
      chave: 'ocorrencias',
      label: 'Ocorrências para conferir',
      icone: 'policy',
      cor: 'red-7',
      total: d.ocorrencias.total,
      itens: [
        ...d.ocorrencias.maiores.map((o) => ({
          chave: o.codocorrencia,
          ocorrencia: o,
          to: { name: 'ocorrencia-detalhe', params: { codocorrencia: o.codocorrencia } },
        })),
        ...(d.ocorrencias.total > d.ocorrencias.maiores.length
          ? [
              {
                chave: 'todas',
                titulo: `Ver todas as ${d.ocorrencias.total}`,
                to: ocorrenciasDaFilial(),
              },
            ]
          : []),
      ],
    },
    {
      chave: 'negocios',
      label: 'Negócios esquecidos abertos',
      icone: 'shopping_cart',
      cor: 'orange-8',
      total: d.negocios.length,
      itens: d.negocios.map((n) => ({
        chave: n.codnegocio,
        titulo: `Negócio #${n.codnegocio} · R$ ${formataNumero(n.valortotal)}`,
        subtitulo: [n.pdv, n.usuario, `parado ${tempoRelativo(n.alteracao)}`]
          .filter(Boolean)
          .join(' · '),
        href: urlNegocio(n.codnegocio),
      })),
    },
    ...[
      { tipo: 'pix', label: 'PIX e depósitos a confirmar', icone: 'pix', cor: 'cyan-8' },
      { tipo: 'duplicata', label: 'Confissões de dívida faltando', icone: 'draw', cor: 'indigo-6' },
      { tipo: 'venda', label: 'Vendas com diferença', icone: 'report_problem', cor: 'red-6' },
    ].map((g) => {
      const itens = conferencia(g.tipo)
      return {
        chave: g.tipo,
        label: g.label,
        icone: g.icone,
        cor: g.cor,
        total: itens.length,
        itens: itens.map((p) => ({
          chave: p.id,
          titulo: p.titulo,
          subtitulo: p.subtitulo,
          ...(g.tipo === 'duplicata' ? { href: urlNegocio(p.id) } : {}),
          ...(g.tipo === 'venda' ? { to: { name: 'fechamento-venda', params: { id: p.id } } } : {}),
          ...(g.tipo === 'pix'
            ? { to: { name: 'titulo-detalhe', params: { codtitulo: p.id } } }
            : {}),
        })),
      }
    }),
  ].filter((b) => b.total)
})

// a filial vem da URL; sem ela, a lista das filiais
const filialDaRota = () => (route.params.codfilial ? Number(route.params.codfilial) : null)

const abrir = () => {
  // não mostra o painel da filial anterior enquanto carrega o da nova
  if (store.codfilial !== filialDaRota()) store.dados = null
  store.codfilial = filialDaRota()
  store.buscar()
}

watch(() => route.params.codfilial, abrir)

onMounted(() => {
  cache.loadList('filial', 'v1/select/filial').catch(() => {})
  abrir()
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div class="row items-center q-col-gutter-x-sm q-mb-md">
        <div v-if="store.codfilial" class="col-auto">
          <q-btn flat round icon="arrow_back" color="grey-7" :to="{ name: 'painel' }" />
        </div>
        <div class="col" style="min-width: 0">
          <div v-if="store.codfilial" class="text-h5 text-grey-9 ellipsis">{{ nomeFilial }}</div>
          <div v-else class="text-subtitle1 text-grey-8">Pendências por filial</div>
        </div>
        <div class="col-auto">
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="refresh"
            :loading="store.carregando"
            @click="store.buscar"
          >
            <q-tooltip>Atualizar</q-tooltip>
          </q-btn>
        </div>
      </div>

      <div v-if="!store.codfilial" class="row q-col-gutter-md">
        <div v-for="e in store.filiais" :key="e.codempresa" class="col-12 col-sm-6 col-md-4">
          <q-card flat bordered class="full-height">
            <q-item>
              <q-item-section>
                <q-item-label class="text-subtitle1 text-weight-medium">{{
                  e.empresa
                }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-separator />
            <q-list separator>
              <q-item
                v-for="f in e.filiais"
                :key="f.codfilial"
                clickable
                :to="{ name: 'painel', params: { codfilial: f.codfilial } }"
              >
                <q-item-section>
                  <q-item-label class="ellipsis">{{ f.filial }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge v-if="f.total" color="red-7" :label="f.total" />
                  <q-icon v-else name="task_alt" color="green-6" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>
      </div>

      <div v-if="store.codfilial && blocos.length" class="row q-col-gutter-md">
        <div v-for="b in blocos" :key="b.chave" class="col-12 col-md-6">
          <q-card bordered flat>
            <q-card-section class="row items-center q-pb-sm">
              <q-icon :name="b.icone" :color="b.cor" size="sm" class="q-mr-sm" />
              <div class="text-subtitle1 col">{{ b.label }}</div>
              <q-badge :color="b.cor" :label="b.total" />
            </q-card-section>
            <q-list separator>
              <template v-for="i in b.itens" :key="i.chave">
                <MgOcorrenciaItem
                  v-if="i.ocorrencia"
                  :ocorrencia="i.ocorrencia"
                  clickable
                  :to="i.to"
                />
                <q-item
                  v-else
                  clickable
                  :to="i.to"
                  :href="i.href"
                  :target="i.href ? '_blank' : undefined"
                >
                  <q-item-section>
                    <q-item-label class="ellipsis">{{ i.titulo }}</q-item-label>
                    <q-item-label v-if="i.subtitulo" caption class="ellipsis">
                      {{ i.subtitulo }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-icon :name="i.href ? 'open_in_new' : 'chevron_right'" color="grey-6" />
                  </q-item-section>
                </q-item>
              </template>
            </q-list>
          </q-card>
        </div>
      </div>

      <MgEmptyState
        v-if="store.codfilial && !store.carregando && store.dados && !blocos.length"
        icon="task_alt"
      >
        Nada pendente na filial.
      </MgEmptyState>
    </div>
  </q-page>
</template>
