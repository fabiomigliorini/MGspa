<script setup>
import { ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { rhStore } from 'src/stores/rh'
import { useAuthStore } from 'src/stores'
import { corProgresso, extrairErro } from 'src/utils/rhFormatters'
import { formataNumero, formataPercentual } from '@components/formatters'
import MgInputValor from '@components/MgInputValor.vue'

const $q = useQuasar()
const route = useRoute()
const sRh = rhStore()
const user = useAuthStore()

const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))

const resumo = computed(() => sRh.resumo || {})
const periodoStatus = computed(() => resumo.value.periodo?.status)
const unidades = computed(() => resumo.value.unidades || [])

const atingimento = (u) => {
  if (u.atingimento != null) return parseFloat(u.atingimento)
  if (!u.meta) return null
  return ((parseFloat(u.vendas) || 0) / parseFloat(u.meta)) * 100
}

const pctVendas = (u) => {
  if (u.pctvendas != null) return parseFloat(u.pctvendas)
  const vendas = parseFloat(u.vendas) || 0
  if (!vendas) return null
  return ((parseFloat(u.total) || 0) / vendas) * 100
}

const grupos = computed(() => {
  const mapa = {}
  for (const u of unidades.value) {
    const key = u.codempresa || 0
    if (!mapa[key]) {
      mapa[key] = { codempresa: u.codempresa, empresa: u.empresa || 'Sem empresa', unidades: [] }
    }
    mapa[key].unidades.push(u)
  }
  const result = Object.values(mapa)
  for (const g of result) {
    g.unidades.sort((a, b) => {
      const fa = a.codfilial || 0
      const fb = b.codfilial || 0
      if (fa !== fb) return fa - fb
      return (a.descricao || '').localeCompare(b.descricao || '')
    })
  }
  return result
})

const somaGrupo = (grupo) => {
  const r = { vendas: 0, salario: 0, adicional: 0, encargos: 0, variaveis: 0, total: 0 }
  for (const u of grupo.unidades) {
    r.vendas += parseFloat(u.vendas) || 0
    r.salario += parseFloat(u.totalsalario) || 0
    r.adicional += parseFloat(u.totaladicional) || 0
    r.encargos += parseFloat(u.totalencargos) || 0
    r.variaveis += parseFloat(u.totalvariaveis) || 0
    r.total += parseFloat(u.total) || 0
  }
  return r
}

const totalVendas = computed(() =>
  unidades.value.reduce((s, u) => s + (parseFloat(u.vendas) || 0), 0),
)

// --- DIALOG EDITAR META ---

const dialogMeta = ref(false)
const modelMeta = ref({ codindicador: null, meta: null })

const editarMeta = (u) => {
  if (!u.codindicador) return
  modelMeta.value = { codindicador: u.codindicador, meta: u.meta }
  dialogMeta.value = true
}

const salvarMeta = async () => {
  dialogMeta.value = false
  try {
    await sRh.atualizarMeta(modelMeta.value.codindicador, { meta: modelMeta.value.meta })
    $q.notify({ color: 'green-5', textColor: 'white', icon: 'done', message: 'Meta atualizada' })
    await sRh.getResumo(route.params.codperiodo)
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao atualizar meta'),
    })
  }
}
</script>

<template>
  <!-- DIALOG EDITAR META -->
  <q-dialog v-model="dialogMeta">
    <q-card flat style="width: 400px; max-width: 90vw">
      <q-form @submit.prevent="salvarMeta()">
        <q-card-section class="text-grey-9 text-overline">EDITAR META</q-card-section>

        <q-separator inset />

        <q-card-section>
          <MgInputValor v-model="modelMeta.meta" label="Meta" prefix="R$" autofocus />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- TABELAS POR EMPRESA -->
  <template v-for="grupo in grupos" :key="grupo.codempresa">
    <q-card bordered flat class="q-mb-md">
      <q-card-section class="text-grey-9 text-overline row items-center">
        {{ grupo.empresa }}
      </q-card-section>

      <q-markup-table flat separator="horizontal" style="table-layout: fixed">
        <colgroup>
          <col style="width: 14%" />
          <col style="width: 10%" />
          <col style="width: 10%" />
          <col style="width: 6%" />
          <col style="width: 10%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 9%" />
          <col style="width: 5%" />
        </colgroup>
        <thead>
          <tr class="text-left">
            <th>Unidade</th>
            <th class="text-right">Vendas</th>
            <th class="text-right">Meta</th>
            <th class="text-right">Ating.</th>
            <th>Progresso</th>
            <th class="text-right">Salário</th>
            <th class="text-right">Adicional</th>
            <th class="text-right">Encargos</th>
            <th class="text-right">Variáveis</th>
            <th class="text-right">Total</th>
            <th class="text-right">% Vendas</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in grupo.unidades" :key="u.codunidadenegocio">
            <td class="text-weight-medium">{{ u.descricao }}</td>
            <td class="text-right">{{ formataNumero(u.vendas) }}</td>
            <td class="text-right">
              <template v-if="u.meta">{{ formataNumero(u.meta) }}</template>
              <template v-else>—</template>
              <q-btn
                v-if="podeEditar && u.codindicador && periodoStatus === 'A'"
                flat
                round
                icon="edit"
                size="xs"
                color="grey-7"
                @click="editarMeta(u)"
              >
                <q-tooltip>Editar Meta</q-tooltip>
              </q-btn>
            </td>
            <td class="text-right">
              {{ atingimento(u) != null ? formataPercentual(atingimento(u)) : '—' }}
            </td>
            <td>
              <q-linear-progress
                v-if="atingimento(u) != null"
                :value="Math.min(atingimento(u) / 100 || 0, 1)"
                size="8px"
                stripe
                rounded
                :color="corProgresso(atingimento(u))"
              />
              <span v-else class="text-grey">—</span>
            </td>
            <td class="text-right">{{ formataNumero(u.totalsalario) }}</td>
            <td class="text-right">{{ formataNumero(u.totaladicional) }}</td>
            <td class="text-right">{{ formataNumero(u.totalencargos) }}</td>
            <td class="text-right">{{ formataNumero(u.totalvariaveis) }}</td>
            <td class="text-right text-weight-medium">{{ formataNumero(u.total) }}</td>
            <td class="text-right text-weight-medium">
              {{ pctVendas(u) != null ? formataPercentual(pctVendas(u)) : '—' }}
            </td>
          </tr>
        </tbody>
        <tfoot v-if="grupo.unidades.length > 1">
          <tr class="text-weight-bold">
            <td>Subtotal</td>
            <td class="text-right">{{ formataNumero(somaGrupo(grupo).vendas) }}</td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-right">{{ formataNumero(somaGrupo(grupo).salario) }}</td>
            <td class="text-right">{{ formataNumero(somaGrupo(grupo).adicional) }}</td>
            <td class="text-right">{{ formataNumero(somaGrupo(grupo).encargos) }}</td>
            <td class="text-right">{{ formataNumero(somaGrupo(grupo).variaveis) }}</td>
            <td class="text-right">{{ formataNumero(somaGrupo(grupo).total) }}</td>
            <td class="text-right">
              {{
                somaGrupo(grupo).vendas
                  ? formataPercentual((somaGrupo(grupo).total / somaGrupo(grupo).vendas) * 100)
                  : '—'
              }}
            </td>
          </tr>
        </tfoot>
      </q-markup-table>
    </q-card>
  </template>

  <!-- TOTAL GERAL -->
  <q-card bordered flat class="q-mb-md" v-if="grupos.length > 1">
    <q-markup-table flat separator="horizontal" style="table-layout: fixed">
      <colgroup>
        <col style="width: 14%" />
        <col style="width: 10%" />
        <col style="width: 10%" />
        <col style="width: 6%" />
        <col style="width: 10%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 9%" />
        <col style="width: 5%" />
      </colgroup>
      <tbody>
        <tr class="text-weight-bold">
          <td>Total Geral</td>
          <td class="text-right">{{ formataNumero(totalVendas) }}</td>
          <td></td>
          <td></td>
          <td></td>
          <td class="text-right">{{ formataNumero(resumo.totalsalario) }}</td>
          <td class="text-right">{{ formataNumero(resumo.totaladicional) }}</td>
          <td class="text-right">{{ formataNumero(resumo.totalencargos) }}</td>
          <td class="text-right">{{ formataNumero(resumo.totalvariaveis) }}</td>
          <td class="text-right">{{ formataNumero(resumo.total) }}</td>
          <td class="text-right">
            {{ totalVendas ? formataPercentual((resumo.total / totalVendas) * 100) : '—' }}
          </td>
        </tr>
      </tbody>
    </q-markup-table>
  </q-card>

  <div v-if="unidades.length === 0" class="q-pa-md text-center text-grey">
    Nenhuma unidade encontrada
  </div>
</template>
