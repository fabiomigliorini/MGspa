<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const treeRef = ref(null)

const nodes = ref([])
const loading = ref(false)
const filtro = ref('')

function formataNcm(ncm) {
  const d = String(ncm || '').replace(/\D/g, '')
  if (d.length === 8) return `${d.slice(0, 4)}.${d.slice(4, 6)}.${d.slice(6, 8)}`
  return String(ncm || '')
}

async function buscarFilhos(id = null) {
  const { data } = await api.get('v1/ncm/arvore', { params: id ? { id } : {} })
  return data
}

async function carregarRaiz() {
  loading.value = true
  try {
    const raiz = await buscarFilhos(null)
    raiz.forEach((n) => (n._pai = null))
    nodes.value = raiz
  } catch (e) {
    notifyError(e, 'Erro ao carregar NCM')
  } finally {
    loading.value = false
  }
}

async function onLazyLoad({ node, done, fail }) {
  try {
    const filhos = await buscarFilhos(node.codigo)
    filhos.forEach((n) => (n._pai = node))
    done(filhos)
  } catch (e) {
    notifyError(e, 'Erro ao carregar itens')
    fail()
  }
}

// ─────────────────────────── Dialog criar/editar ──────────────────────────
const dialog = ref(false)
const saving = ref(false)
const isNovo = ref(true)
const editNode = ref(null)
const parentNode = ref(null)
const form = ref({ ncm: '', descricao: '' })

const abrirNovoRaiz = () => {
  isNovo.value = true
  parentNode.value = null
  editNode.value = null
  form.value = { ncm: '', descricao: '' }
  dialog.value = true
}

const abrirNovoFilho = (node) => {
  isNovo.value = true
  parentNode.value = node
  editNode.value = null
  form.value = { ncm: '', descricao: '' }
  dialog.value = true
}

const abrirEditar = (node) => {
  isNovo.value = false
  parentNode.value = node._pai
  editNode.value = node
  form.value = { ncm: node.ncm, descricao: node.descricao }
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  const payload = {
    ncm: form.value.ncm,
    descricao: form.value.descricao,
    codncmpai: parentNode.value ? parentNode.value.codigo : null,
  }
  saving.value = true
  try {
    const { data } = await api.post('v1/ncm', payload)
    const novo = {
      key: `ncm-${data.codncm}`,
      codigo: data.codncm,
      ncm: data.ncm,
      descricao: data.descricao,
      inativo: data.inativo ?? null,
      lazy: false,
      _pai: parentNode.value,
    }
    const pai = parentNode.value
    if (pai) {
      if (Array.isArray(pai.children)) pai.children.push(novo)
      pai.lazy = true
      if (treeRef.value) treeRef.value.setExpanded(pai.key, true)
    } else {
      nodes.value.push(novo)
    }
    notifySuccess('NCM criado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao criar NCM')
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  const node = editNode.value
  const payload = {
    ncm: form.value.ncm,
    descricao: form.value.descricao,
    codncmpai: node._pai ? node._pai.codigo : null,
  }
  saving.value = true
  try {
    const { data } = await api.put(`v1/ncm/${node.codigo}`, payload)
    node.ncm = data.ncm
    node.descricao = data.descricao
    notifySuccess('NCM atualizado')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar NCM')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (node) => {
  try {
    const { data } = node.inativo
      ? await api.delete(`v1/ncm/${node.codigo}/inativo`)
      : await api.post(`v1/ncm/${node.codigo}/inativo`)
    node.inativo = data.inativo ?? null
    notifySuccess(node.inativo ? 'NCM inativado' : 'NCM reativado')
  } catch (e) {
    notifyError(e, 'Erro ao alterar situação')
  }
}

const excluir = (node) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir o NCM ${formataNcm(node.ncm)}? Filhos ou produtos vinculados podem impedir.`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await api.delete(`v1/ncm/${node.codigo}`)
      const lista = node._pai ? node._pai.children : nodes.value
      const idx = lista.findIndex((n) => n.key === node.key)
      if (idx >= 0) lista.splice(idx, 1)
      notifySuccess('NCM excluído')
    } catch (e) {
      notifyError(e, 'Erro ao excluir NCM')
    }
  })
}

onMounted(carregarRaiz)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="text-grey-9 text-overline row items-center">
          NCM — NOMENCLATURA COMUM DO MERCOSUL
          <q-space />
          <span class="text-caption text-grey-6">Árvore por capítulo › posição › item</span>
        </q-card-section>

        <q-separator inset />

        <q-card-section>
          <q-input
            v-model="filtro"
            outlined
            clearable
            :bottom-slots="false"
            label="Filtrar (itens já carregados)"
            class="q-mb-md"
          >
            <template #prepend><q-icon name="search" /></template>
          </q-input>

          <div v-if="loading" class="row justify-center q-my-lg">
            <q-spinner-dots color="primary" size="32px" />
          </div>

          <q-tree
            v-else
            ref="treeRef"
            :nodes="nodes"
            node-key="key"
            label-key="descricao"
            children-key="children"
            :filter="filtro"
            no-transition
            @lazy-load="onLazyLoad"
          >
            <template #default-header="prop">
              <div class="row items-center full-width no-wrap">
                <q-badge color="blue-grey-6" class="q-mr-sm text-weight-medium">
                  {{ formataNcm(prop.node.ncm) }}
                </q-badge>
                <div
                  class="col ellipsis"
                  :class="prop.node.inativo ? 'text-strike text-grey-5' : ''"
                >
                  {{ prop.node.descricao }}
                  <q-badge
                    v-if="prop.node.inativo"
                    color="orange-7"
                    class="q-ml-xs"
                    label="Inativo"
                  />
                </div>
                <div class="row no-wrap">
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="primary"
                    icon="add"
                    @click.stop="abrirNovoFilho(prop.node)"
                  >
                    <q-tooltip>Adicionar NCM filho</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    icon="edit"
                    @click.stop="abrirEditar(prop.node)"
                  >
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    :icon="prop.node.inativo ? 'play_arrow' : 'pause'"
                    @click.stop="toggleInativo(prop.node)"
                  >
                    <q-tooltip>{{ prop.node.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    size="sm"
                    color="grey-7"
                    icon="delete"
                    @click.stop="excluir(prop.node)"
                  >
                    <q-tooltip>Excluir</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </template>
          </q-tree>
        </q-card-section>
      </q-card>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="abrirNovoRaiz">
        <q-tooltip anchor="center left" self="center right">Novo NCM raiz</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 520px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVO NCM' : 'EDITAR NCM' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div v-if="parentNode && isNovo" class="text-caption text-grey-6 q-mb-sm">
              Sob: {{ formataNcm(parentNode.ncm) }} — {{ parentNode.descricao }}
            </div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-5">
                <q-input
                  v-model="form.ncm"
                  outlined
                  label="Código NCM"
                  hint="Só dígitos (2 a 8)"
                  autofocus
                  @update:model-value="form.ncm = String(form.ncm || '').replace(/\D/g, '')"
                  :rules="[(v) => (!!v && v.length >= 2) || 'Mínimo 2 dígitos']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="form.descricao"
                  outlined
                  type="textarea"
                  autogrow
                  label="Descrição"
                  :rules="[(v) => (!!v && v.length >= 3) || 'Mínimo 3 caracteres']"
                />
              </div>
            </div>
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Salvar" type="submit" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
