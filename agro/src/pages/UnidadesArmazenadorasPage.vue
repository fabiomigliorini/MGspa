<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/services/api'
import { notifySuccess, notifyError } from 'src/utils/notify'
import MgInputValor from '@components/MgInputValor.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'

const TIPOS = [
  { value: 'PROPRIO', label: 'Próprio', icon: 'home', color: 'green-7' },
  { value: 'TERCEIRO', label: 'Terceiro', icon: 'handshake', color: 'blue-grey-7' },
  { value: 'SILOBAG', label: 'Silo bag', icon: 'inventory', color: 'amber-8' },
]
function meta(tipo) {
  return TIPOS.find((t) => t.value === tipo) || { label: tipo, icon: 'warehouse', color: 'grey-7' }
}

const itens = ref([])
const carregando = ref(false)

async function carregar() {
  carregando.value = true
  try {
    const { data } = await api.get('v1/unidade-armazenadora', { params: { inativo: 9 } })
    itens.value = data.data ?? data
  } catch (e) {
    notifyError(e)
  } finally {
    carregando.value = false
  }
}

const dialog = ref(false)
const form = ref({})
const isNovo = computed(() => !form.value.codunidadearmazenadora)
const salvando = ref(false)

function novo() {
  form.value = {
    codunidadearmazenadora: null,
    unidadearmazenadora: '',
    tipo: 'PROPRIO',
    codpessoa: null,
    capacidadesacas: null,
    observacao: null,
  }
  dialog.value = true
}
function editar(u) {
  form.value = JSON.parse(JSON.stringify(u))
  dialog.value = true
}

async function submit() {
  salvando.value = true
  try {
    if (isNovo.value) {
      await api.post('v1/unidade-armazenadora', form.value)
    } else {
      await api.put(`v1/unidade-armazenadora/${form.value.codunidadearmazenadora}`, form.value)
    }
    notifySuccess('Unidade salva')
    dialog.value = false
    await carregar()
  } catch (e) {
    notifyError(e)
  } finally {
    salvando.value = false
  }
}

async function alternarInativo(u) {
  try {
    if (u.inativo) {
      await api.delete(`v1/unidade-armazenadora/${u.codunidadearmazenadora}/inativo`)
    } else {
      await api.post(`v1/unidade-armazenadora/${u.codunidadearmazenadora}/inativo`)
    }
    await carregar()
  } catch (e) {
    notifyError(e)
  }
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1086px; margin: auto">
      <div class="row items-center q-mb-md">
        <div class="text-h5 col">Unidades Armazenadoras</div>
        <q-btn flat round size="sm" color="primary" icon="add" @click="novo">
          <q-tooltip>Nova unidade</q-tooltip>
        </q-btn>
      </div>

      <q-card bordered flat>
        <q-list separator>
          <q-item v-for="u in itens" :key="u.codunidadearmazenadora">
            <q-item-section avatar>
              <q-icon :name="meta(u.tipo).icon" :color="meta(u.tipo).color" size="sm" />
            </q-item-section>
            <q-item-section>
              <q-item-label :class="{ 'text-strike text-grey-6': u.inativo }">
                {{ u.unidadearmazenadora }}
              </q-item-label>
              <q-item-label caption>
                {{ meta(u.tipo).label }}
                <span v-if="u.Pessoa"> · {{ u.Pessoa.fantasia || u.Pessoa.pessoa }}</span>
                <span v-if="u.capacidadesacas">
                  · {{ Number(u.capacidadesacas).toLocaleString('pt-BR') }} sc</span
                >
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <div class="row items-center no-wrap q-gutter-xs">
                <MgInfoCriacao :registro="u" />
                <q-btn
                  flat
                  round
                  size="sm"
                  color="grey-7"
                  :icon="u.inativo ? 'play_arrow' : 'pause'"
                  @click="alternarInativo(u)"
                >
                  <q-tooltip>{{ u.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                </q-btn>
                <q-btn flat round size="sm" color="grey-7" icon="edit" @click="editar(u)">
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>

          <q-item v-if="!itens.length && !carregando">
            <q-item-section class="text-grey-6 text-center q-pa-md">
              Nenhuma unidade armazenadora cadastrada
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>
    </div>

    <q-dialog v-model="dialog">
      <q-card flat bordered style="width: 500px; max-width: 90vw">
        <q-form @submit.prevent="submit">
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">{{ isNovo ? 'Nova unidade' : 'Editar unidade' }}</div>
          </q-card-section>
          <q-card-section class="q-pt-md">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="form.unidadearmazenadora"
                  label="Nome"
                  outlined
                  autofocus
                  maxlength="60"
                  :rules="[(v) => !!v || 'Informe o nome']"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-select
                  v-model="form.tipo"
                  label="Tipo"
                  outlined
                  :options="TIPOS"
                  option-value="value"
                  option-label="label"
                  emit-value
                  map-options
                />
              </div>
              <div class="col-12 col-sm-6">
                <MgInputValor
                  v-model="form.capacidadesacas"
                  :decimals="0"
                  suffix="sc"
                  label="Capacidade"
                />
              </div>
              <div v-if="form.tipo === 'TERCEIRO'" class="col-12">
                <MgSelectPessoa v-model="form.codpessoa" label="Dono (armazém de terceiro)" />
              </div>
              <div class="col-12">
                <q-input
                  v-model="form.observacao"
                  label="Observação"
                  type="textarea"
                  autogrow
                  outlined
                />
              </div>
            </div>
          </q-card-section>
          <q-card-actions align="right">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn type="submit" unelevated label="Salvar" color="primary" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
