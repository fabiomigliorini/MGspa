<script setup>
import { onMounted, computed } from 'vue'
import { useEstoqueSaldoStore } from 'src/stores/estoqueSaldoStore'

const store = useEstoqueSaldoStore()

const formataNum = (v) => (Number(v) || 0).toLocaleString('pt-BR', { maximumFractionDigits: 2 })
const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

const agrupamentoLabels = {
  secaoproduto: 'Seção',
  familiaproduto: 'Família',
  grupoproduto: 'Grupo',
  subgrupoproduto: 'Subgrupo',
  marca: 'Marca',
  produto: 'Produto',
  variacao: 'Variação',
}

const agrupamentoRaizOptions = [
  { label: 'Seção', value: 'secaoproduto' },
  { label: 'Marca', value: 'marca' },
  { label: 'Produto', value: 'produto' },
]

const podeNavegar = (item) => !!item.proximo

const totalFisico = computed(() =>
  store.itens.reduce((s, i) => s + Number(i.fisico.saldovalor || 0), 0),
)
const totalFiscal = computed(() =>
  store.itens.reduce((s, i) => s + Number(i.fiscal.saldovalor || 0), 0),
)

onMounted(() => store.fetchItems())
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1280px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="row items-center q-gutter-md">
          <div class="text-overline text-grey-8">SALDOS DE ESTOQUE</div>
          <q-space />
          <q-btn-toggle
            :model-value="store.filters.valor"
            :options="[
              { label: 'Custo', value: 'custo' },
              { label: 'Venda', value: 'venda' },
            ]"
            outline
            color="primary"
            @update:model-value="
              (v) => {
                store.filters.valor = v
                store.fetchItems()
              }
            "
          />
          <q-select
            :model-value="store.agrupamento"
            :options="agrupamentoRaizOptions"
            emit-value
            map-options
            outlined
            dense
            label="Agrupar por"
            style="min-width: 150px"
            @update:model-value="store.setAgrupamentoRaiz"
          />
        </q-card-section>

        <q-separator />

        <!-- Breadcrumb de drill-down -->
        <q-card-section class="q-py-sm">
          <q-breadcrumbs class="text-grey-7" active-color="primary">
            <q-breadcrumbs-el
              label="Início"
              icon="home"
              class="cursor-pointer"
              @click="store.voltarPara(0)"
            />
            <q-breadcrumbs-el
              v-for="(d, i) in store.drill"
              :key="i"
              :label="d.label"
              class="cursor-pointer"
              @click="store.voltarPara(i + 1)"
            />
            <q-breadcrumbs-el :label="agrupamentoLabels[store.agrupamento]" />
          </q-breadcrumbs>
        </q-card-section>

        <q-separator />

        <q-inner-loading :showing="store.loading"><q-spinner-dots color="primary" /></q-inner-loading>

        <q-markup-table flat>
          <thead>
            <tr>
              <th class="text-left">{{ agrupamentoLabels[store.agrupamento] }}</th>
              <th class="text-right">Físico (qtd)</th>
              <th class="text-right">Físico (R$)</th>
              <th class="text-right">Fiscal (qtd)</th>
              <th class="text-right">Fiscal (R$)</th>
              <th class="text-right">Mín/Máx</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in store.itens"
              :key="item.coditem"
              :class="podeNavegar(item) ? 'cursor-pointer' : ''"
              @click="podeNavegar(item) && store.drillInto(item)"
            >
              <td>{{ item.item }}</td>
              <td class="text-right">{{ formataNum(item.fisico.saldoquantidade) }}</td>
              <td class="text-right">{{ formataMoeda(item.fisico.saldovalor) }}</td>
              <td class="text-right">{{ formataNum(item.fiscal.saldoquantidade) }}</td>
              <td class="text-right">{{ formataMoeda(item.fiscal.saldovalor) }}</td>
              <td class="text-right text-caption text-grey-6">
                {{ formataNum(item.fisico.estoqueminimo) }}/{{ formataNum(item.fisico.estoquemaximo) }}
              </td>
              <td class="text-right">
                <q-icon v-if="podeNavegar(item)" name="chevron_right" color="grey-6" />
              </td>
            </tr>
            <tr v-if="!store.itens.length && !store.loading">
              <td colspan="7" class="text-center text-grey-6 q-pa-lg">Nenhum saldo encontrado</td>
            </tr>
          </tbody>
          <tfoot v-if="store.itens.length">
            <tr class="text-weight-bold bg-grey-1">
              <td>Total ({{ store.itens.length }})</td>
              <td></td>
              <td class="text-right">{{ formataMoeda(totalFisico) }}</td>
              <td></td>
              <td class="text-right">{{ formataMoeda(totalFiscal) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </q-markup-table>
      </q-card>
    </div>
  </q-page>
</template>
