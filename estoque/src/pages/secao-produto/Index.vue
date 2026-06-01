<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'

const $q = useQuasar()
const treeRef = ref(null)

// Config por nível: endpoint, campo do nome, FK pro pai, nível filho.
const CFG = {
  secao: {
    base: 'secao-produto',
    campo: 'secaoproduto',
    label: 'Seção',
    icon: 'widgets',
    color: 'brown-6',
    min: 2,
    child: 'familia',
  },
  familia: {
    base: 'familia-produto',
    campo: 'familiaproduto',
    label: 'Família',
    icon: 'account_tree',
    color: 'teal-7',
    min: 3,
    child: 'grupo',
    fk: 'codsecaoproduto',
  },
  grupo: {
    base: 'grupo-produto',
    campo: 'grupoproduto',
    label: 'Grupo',
    icon: 'folder',
    color: 'indigo-6',
    min: 3,
    child: 'subgrupo',
    fk: 'codfamiliaproduto',
  },
  subgrupo: {
    base: 'subgrupo-produto',
    campo: 'subgrupoproduto',
    label: 'Subgrupo',
    icon: 'label',
    color: 'deep-purple-5',
    min: 3,
    fk: 'codgrupoproduto',
  },
}

const nodes = ref([])
const loading = ref(false)
const filtro = ref('')

async function buscarFilhos(nivel, id = null) {
  const { data } = await api.get('v1/hierarquia-produto/filhos', { params: { nivel, id } })
  return data
}

async function carregarRaiz() {
  loading.value = true
  try {
    const raiz = await buscarFilhos('raiz')
    raiz.forEach((n) => (n._pai = null))
    nodes.value = raiz
  } catch (e) {
    notifyError(e, 'Erro ao carregar hierarquia')
  } finally {
    loading.value = false
  }
}

async function onLazyLoad({ node, done, fail }) {
  try {
    const filhos = await buscarFilhos(node.nivel, node.codigo)
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
const dialogNivel = ref('secao')
const isNovo = ref(true)
const editNode = ref(null)
const parentNode = ref(null)
const nome = ref('')

const dialogCfg = () => CFG[dialogNivel.value]

const abrirNovaSecao = () => {
  isNovo.value = true
  dialogNivel.value = 'secao'
  parentNode.value = null
  editNode.value = null
  nome.value = ''
  dialog.value = true
}

const abrirNovoFilho = (node) => {
  const childNivel = CFG[node.nivel].child
  if (!childNivel) return
  isNovo.value = true
  dialogNivel.value = childNivel
  parentNode.value = node
  editNode.value = null
  nome.value = ''
  dialog.value = true
}

const abrirEditar = (node) => {
  isNovo.value = false
  dialogNivel.value = node.nivel
  parentNode.value = node._pai
  editNode.value = node
  nome.value = node.label
  dialog.value = true
}

const submit = () => (isNovo.value ? criar() : atualizar())

const criar = async () => {
  const cfg = dialogCfg()
  const payload = { [cfg.campo]: nome.value }
  if (cfg.fk) payload[cfg.fk] = parentNode.value.codigo
  saving.value = true
  try {
    const { data } = await api.post(`v1/${cfg.base}`, payload)
    const codigo = idDe(cfg, data)
    const novo = {
      key: `${dialogNivel.value}-${codigo}`,
      codigo,
      nivel: dialogNivel.value,
      label: data[cfg.campo],
      inativo: data.inativo ?? null,
      lazy: dialogNivel.value !== 'subgrupo',
      _pai: parentNode.value,
    }
    const pai = parentNode.value
    if (pai) {
      // Se o pai já teve filhos carregados, inserimos direto; senão, expandir
      // dispara o lazy-load que já trará o novo nó.
      if (Array.isArray(pai.children)) pai.children.push(novo)
      if (treeRef.value) treeRef.value.setExpanded(pai.key, true)
    } else {
      nodes.value.push(novo)
    }
    notifySuccess(`${cfg.label} criada`)
    dialog.value = false
  } catch (e) {
    notifyError(e, `Erro ao criar ${cfg.label.toLowerCase()}`)
  } finally {
    saving.value = false
  }
}

const atualizar = async () => {
  const cfg = dialogCfg()
  const node = editNode.value
  const payload = { [cfg.campo]: nome.value }
  if (cfg.fk && node._pai) payload[cfg.fk] = node._pai.codigo
  saving.value = true
  try {
    const { data } = await api.put(`v1/${cfg.base}/${node.codigo}`, payload)
    node.label = data[cfg.campo]
    notifySuccess(`${cfg.label} atualizada`)
    dialog.value = false
  } catch (e) {
    notifyError(e, `Erro ao atualizar ${cfg.label.toLowerCase()}`)
  } finally {
    saving.value = false
  }
}

const toggleInativo = async (node) => {
  const cfg = CFG[node.nivel]
  try {
    const { data } = node.inativo
      ? await api.delete(`v1/${cfg.base}/${node.codigo}/inativo`)
      : await api.post(`v1/${cfg.base}/${node.codigo}/inativo`)
    node.inativo = data.inativo ?? null
    notifySuccess(node.inativo ? `${cfg.label} inativada` : `${cfg.label} reativada`)
  } catch (e) {
    notifyError(e, 'Erro ao alterar situação')
  }
}

const excluir = (node) => {
  const cfg = CFG[node.nivel]
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir "${node.label}"? Itens filhos podem impedir a exclusão.`,
    cancel: true,
  }).onOk(async () => {
    try {
      await api.delete(`v1/${cfg.base}/${node.codigo}`)
      const lista = node._pai ? node._pai.children : nodes.value
      const idx = lista.findIndex((n) => n.key === node.key)
      if (idx >= 0) lista.splice(idx, 1)
      notifySuccess(`${cfg.label} excluída`)
    } catch (e) {
      notifyError(e, 'Erro ao excluir')
    }
  })
}

// O backend devolve `codigo`, então usamos ele direto pro novo nó.
function idDe(cfg, data) {
  return data[`cod${cfg.base.split('-').join('')}`] ?? data.codigo ?? null
}

onMounted(carregarRaiz)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <q-card bordered flat>
        <q-card-section class="text-grey-9 text-overline row items-center">
          HIERARQUIA DE PRODUTOS
          <q-space />
          <span class="text-caption text-grey-6 q-mr-sm">Seção › Família › Grupo › Subgrupo</span>
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
            label-key="label"
            children-key="children"
            :filter="filtro"
            no-transition
            @lazy-load="onLazyLoad"
          >
            <template #default-header="prop">
              <div class="row items-center full-width no-wrap">
                <q-icon
                  :name="CFG[prop.node.nivel].icon"
                  :color="CFG[prop.node.nivel].color"
                  size="20px"
                  class="q-mr-sm"
                />
                <div
                  class="col"
                  :class="prop.node.inativo ? 'text-strike text-grey-5' : ''"
                >
                  {{ prop.node.label }}
                  <q-badge
                    v-if="prop.node.inativo"
                    color="orange-7"
                    class="q-ml-xs"
                    label="Inativo"
                  />
                </div>
                <div class="row no-wrap mg-tree-actions">
                  <q-btn
                    v-if="CFG[prop.node.nivel].child"
                    flat
                    dense
                    round
                    size="sm"
                    color="primary"
                    icon="add"
                    @click.stop="abrirNovoFilho(prop.node)"
                  >
                    <q-tooltip>Adicionar {{ CFG[CFG[prop.node.nivel].child].label }}</q-tooltip>
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
      <q-btn fab icon="add" color="primary" @click="abrirNovaSecao">
        <q-tooltip anchor="center left" self="center right">Nova Seção</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA' : 'EDITAR' }} {{ dialogCfg().label.toUpperCase() }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div
              v-if="parentNode && isNovo"
              class="text-caption text-grey-6 q-mb-sm"
            >
              Em: {{ parentNode.label }}
            </div>
            <q-input
              v-model="nome"
              outlined
              :label="dialogCfg().label"
              maxlength="50"
              autofocus
              :rules="[(v) => (!!v && v.length >= dialogCfg().min) || `Mínimo ${dialogCfg().min} caracteres`]"
            />
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
