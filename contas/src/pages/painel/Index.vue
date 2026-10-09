<script setup>
// Painel da filial (TASK-203): tudo que está pendente na filial — caixas, maquinetas,
// ocorrências, negócios esquecidos abertos, confissões, vendas com diferença e PIX a confirmar —
// de qualquer usuário. Só mostra: cada item leva à tela que resolve. Pensada no celular.
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePainelStore } from 'src/stores/painelStore'
import { formataNumero, formataTimestamp, tempoRelativo } from '@components/formatters'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgEmptyState from '@components/MgEmptyState.vue'

const store = usePainelStore()
const route = useRoute()
const router = useRouter()

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
          titulo: o.descricao,
          subtitulo: [o.pdv, o.usuario, formataTimestamp(o.criacao, 0)].filter(Boolean).join(' · '),
          to: { name: 'ocorrencia' },
        })),
        ...(d.ocorrencias.total > d.ocorrencias.maiores.length
          ? [
              {
                chave: 'todas',
                titulo: `Ver todas as ${d.ocorrencias.total}`,
                to: { name: 'ocorrencia' },
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
      { tipo: 'duplicata', label: 'Confissões de dívida faltando', icone: 'draw', cor: 'indigo-6' },
      { tipo: 'venda', label: 'Vendas com diferença', icone: 'report_problem', cor: 'red-6' },
      { tipo: 'pix', label: 'PIX e depósitos a confirmar', icone: 'pix', cor: 'cyan-8' },
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

// a filial vem da URL; sem ela, a última aberta
const filialDaRota = () => (route.params.codfilial ? Number(route.params.codfilial) : null)

const trocarFilial = (codfilial) => {
  router.replace({ name: 'painel', params: { codfilial: codfilial || undefined } })
}

watch(
  () => route.params.codfilial,
  () => {
    store.codfilial = filialDaRota()
    store.buscar()
  },
)

onMounted(() => {
  if (!filialDaRota() && store.codfilial) {
    trocarFilial(store.codfilial)
    return
  }
  store.codfilial = filialDaRota()
  store.buscar()
})
</script>

<template>
  <q-page>
    <div class="q-pa-md" style="max-width: 1086px; margin: auto">
      <div class="row q-col-gutter-md items-center q-mb-md">
        <div class="col-grow">
          <MgSelectFilial
            :model-value="store.codfilial"
            label="Filial"
            @update:model-value="trocarFilial"
          />
        </div>
        <div class="col-auto">
          <q-btn
            flat
            round
            size="sm"
            color="primary"
            icon="refresh"
            :loading="store.carregando"
            :disable="!store.codfilial"
            @click="store.buscar"
          >
            <q-tooltip>Atualizar</q-tooltip>
          </q-btn>
        </div>
      </div>

      <q-card v-for="b in blocos" :key="b.chave" bordered flat class="q-mb-md">
        <q-card-section class="row items-center q-pb-sm">
          <q-icon :name="b.icone" :color="b.cor" size="sm" class="q-mr-sm" />
          <div class="text-subtitle1 col">{{ b.label }}</div>
          <q-badge :color="b.cor" :label="b.total" />
        </q-card-section>
        <q-list separator>
          <q-item
            v-for="i in b.itens"
            :key="i.chave"
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
        </q-list>
      </q-card>

      <MgEmptyState v-if="!store.codfilial" icon="store"> Escolha a filial. </MgEmptyState>
      <MgEmptyState v-else-if="!store.carregando && store.dados && !blocos.length" icon="task_alt">
        Nada pendente na filial.
      </MgEmptyState>
    </div>
  </q-page>
</template>
