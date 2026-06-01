<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgAutocomplete from 'src/components/MgAutocomplete.vue'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => route.name === 'produto-editar')
const codproduto = computed(() => route.params.id)

const saving = ref(false)
const loading = ref(false)

// Selects pequenos carregados uma vez
const tipos = ref([])
const tributacoes = ref([])
const depositos = ref([])

// Opções iniciais (pra exibir o valor atual nos autocompletes ao editar/duplicar)
const opt = ref({
  marca: null,
  ncm: null,
  cest: null,
  unidade: null,
  subgrupo: null,
})

const model = ref(novoModelo())

function novoModelo() {
  return {
    produto: '',
    referencia: '',
    preco: null,
    codunidademedida: null,
    codtipoproduto: null,
    abc: 'C',
    codmarca: null,
    codsubgrupoproduto: null,
    codncm: null,
    codtributacao: null,
    codcest: null,
    codestoquelocal: null,
    estoque: true,
    conferenciaperiodica: false,
    importado: false,
    site: false,
    altura: null,
    largura: null,
    profundidade: null,
    peso: null,
    titulosite: '',
    descricaosite: '',
    observacoes: '',
  }
}

const abcOptions = [
  { label: 'A — Contínuo', value: 'A' },
  { label: 'B — Alto Giro', value: 'B' },
  { label: 'C — Comum', value: 'C' },
  { label: 'D — Sazonal', value: 'D' },
]

function formataNcm(ncm) {
  const d = String(ncm || '').replace(/\D/g, '')
  if (d.length === 8) return `${d.slice(0, 4)}.${d.slice(4, 6)}.${d.slice(6, 8)}`
  return String(ncm || '')
}

async function carregarSelects() {
  const [t, tr, d] = await Promise.all([
    api.get('v1/select/tipo-produto', { params: { busca: '' } }),
    api.get('v1/select/tributacao'),
    api.get('v1/select/estoque-local'),
  ])
  tipos.value = t.data.map((x) => ({ label: x.tipoproduto, value: x.codtipoproduto }))
  tributacoes.value = tr.data.map((x) => ({ label: x.tributacao, value: x.codtributacao }))
  depositos.value = d.data
}

function preencher(p) {
  model.value = {
    produto: p.produto || '',
    referencia: p.referencia || '',
    preco: p.preco,
    codunidademedida: p.codunidademedida,
    codtipoproduto: p.codtipoproduto,
    abc: p.abc || 'C',
    codmarca: p.codmarca,
    codsubgrupoproduto: p.codsubgrupoproduto,
    codncm: p.codncm,
    codtributacao: p.codtributacao,
    codcest: p.codcest,
    codestoquelocal: p.codestoquelocal,
    estoque: !!p.estoque,
    conferenciaperiodica: !!p.conferenciaperiodica,
    importado: !!p.importado,
    site: !!p.site,
    altura: p.altura,
    largura: p.largura,
    profundidade: p.profundidade,
    peso: p.peso,
    titulosite: p.titulosite || '',
    descricaosite: p.descricaosite || '',
    observacoes: p.observacoes || '',
  }
  opt.value = {
    marca: p.codmarca ? { label: p.marca, value: p.codmarca } : null,
    ncm: p.codncm ? { label: `${formataNcm(p.ncm)} — ${p.Ncm?.descricao || ''}`, value: p.codncm } : null,
    cest: p.codcest ? { label: p.cest, value: p.codcest } : null,
    unidade: p.codunidademedida ? { label: p.unidademedida, value: p.codunidademedida } : null,
    subgrupo: p.codsubgrupoproduto ? { label: p.subgrupoproduto, value: p.codsubgrupoproduto } : null,
  }
}

async function carregarProduto(id, duplicar = false) {
  loading.value = true
  try {
    const { data } = await api.get(`v1/produto/${id}`)
    const p = data.data || data
    preencher(p)
    if (duplicar) {
      model.value.produto = ''
    }
  } catch (e) {
    notifyError(e, 'Erro ao carregar produto')
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`v1/produto/${codproduto.value}`, model.value)
      notifySuccess('Produto atualizado')
      router.push({ name: 'produto-detalhe', params: { id: codproduto.value } })
    } else {
      const { data } = await api.post('v1/produto', model.value)
      const novo = data.data || data
      notifySuccess('Produto criado')
      router.push({ name: 'produto-detalhe', params: { id: novo.codproduto } })
    }
  } catch (e) {
    notifyError(e, 'Erro ao salvar produto')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await carregarSelects()
  if (isEdit.value) {
    await carregarProduto(codproduto.value)
  } else if (route.query.duplicar) {
    await carregarProduto(route.query.duplicar, true)
  }
})
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="row items-center q-gutter-sm">
          <q-btn flat round dense icon="arrow_back" :to="{ name: 'produto' }" />
          <div class="text-h6">{{ isEdit ? 'Editar Produto' : 'Novo Produto' }}</div>
        </q-card-section>

        <q-separator />

        <q-inner-loading :showing="loading">
          <q-spinner-dots color="primary" size="40px" />
        </q-inner-loading>

        <q-form @submit.prevent="submit" v-show="!loading">
          <q-card-section class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model="model.produto"
                outlined
                label="Descrição"
                maxlength="100"
                counter
                autofocus
                :rules="[(v) => (!!v && v.length >= 10) || 'Mínimo 10 caracteres']"
              />
            </div>

            <div class="col-12 col-sm-4">
              <q-input
                v-model.number="model.preco"
                outlined
                type="number"
                step="0.01"
                label="Preço"
                prefix="R$"
                :rules="[(v) => (v > 0) || 'Maior que zero']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <MgAutocomplete
                v-model="model.codunidademedida"
                endpoint="v1/unidade-medida/autocompletar"
                search-param="unidademedida"
                label="Unidade"
                :initial-option="opt.unidade"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="model.referencia" outlined label="Referência" maxlength="50" />
            </div>

            <div class="col-12 col-sm-4">
              <q-select
                v-model="model.codtipoproduto"
                :options="tipos"
                emit-value
                map-options
                outlined
                label="Tipo"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-select
                v-model="model.abc"
                :options="abcOptions"
                emit-value
                map-options
                outlined
                label="Curva ABC"
              />
            </div>
            <div class="col-12 col-sm-4">
              <MgAutocomplete
                v-model="model.codmarca"
                endpoint="v1/marca/autocompletar"
                search-param="marca"
                label="Marca"
                :initial-option="opt.marca"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>

            <div class="col-12">
              <MgAutocomplete
                v-model="model.codsubgrupoproduto"
                endpoint="v1/subgrupo-produto/autocompletar"
                search-param="subgrupoproduto"
                label="Subgrupo (classificação)"
                :initial-option="opt.subgrupo"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>

            <div class="col-12 col-sm-4">
              <MgAutocomplete
                v-model="model.codncm"
                endpoint="v1/ncm/autocompletar"
                search-param="busca"
                label="NCM"
                :initial-option="opt.ncm"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <q-select
                v-model="model.codtributacao"
                :options="tributacoes"
                emit-value
                map-options
                outlined
                label="Tributação"
                :rules="[(v) => !!v || 'Obrigatório']"
              />
            </div>
            <div class="col-12 col-sm-4">
              <MgAutocomplete
                v-model="model.codcest"
                endpoint="v1/cest/autocompletar"
                search-param="busca"
                label="CEST (opcional)"
                :initial-option="opt.cest"
                :extra-params="{ codncm: model.codncm }"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-select
                v-model="model.codestoquelocal"
                :options="depositos"
                emit-value
                map-options
                outlined
                clearable
                label="Depósito padrão"
              />
            </div>
            <div class="col-12 col-sm-6 row items-center q-gutter-md">
              <q-toggle v-model="model.estoque" label="Controla estoque" />
              <q-toggle v-model="model.conferenciaperiodica" label="Conferência periódica" />
            </div>

            <div class="col-12 row q-gutter-md">
              <q-toggle v-model="model.site" label="No site" />
              <q-toggle v-model="model.importado" label="Importado" />
            </div>
          </q-card-section>

          <q-separator inset />
          <q-card-section class="text-grey-9 text-overline">DIMENSÕES E PESO</q-card-section>
          <q-card-section class="row q-col-gutter-md">
            <div class="col-6 col-sm-3">
              <q-input v-model.number="model.altura" outlined type="number" label="Altura (cm)" />
            </div>
            <div class="col-6 col-sm-3">
              <q-input v-model.number="model.largura" outlined type="number" label="Largura (cm)" />
            </div>
            <div class="col-6 col-sm-3">
              <q-input
                v-model.number="model.profundidade"
                outlined
                type="number"
                label="Profund. (cm)"
              />
            </div>
            <div class="col-6 col-sm-3">
              <q-input v-model.number="model.peso" outlined type="number" label="Peso (kg)" />
            </div>
          </q-card-section>

          <q-separator inset />
          <q-card-section class="text-grey-9 text-overline">SITE / OBSERVAÇÕES</q-card-section>
          <q-card-section class="row q-col-gutter-md">
            <div class="col-12">
              <q-input v-model="model.titulosite" outlined label="Título no site" />
            </div>
            <div class="col-12">
              <q-input
                v-model="model.descricaosite"
                outlined
                type="textarea"
                autogrow
                label="Descrição no site"
              />
            </div>
            <div class="col-12">
              <q-input
                v-model="model.observacoes"
                outlined
                type="textarea"
                autogrow
                label="Observações internas"
              />
            </div>
          </q-card-section>

          <q-separator />
          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat label="Cancelar" color="grey-8" :to="{ name: 'produto' }" />
            <q-btn unelevated label="Salvar" color="primary" type="submit" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </div>
  </q-page>
</template>
