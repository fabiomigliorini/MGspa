<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from 'src/services/api'
import { goBack } from 'src/utils/goBack'
import { notifySuccess, notifyError } from 'src/utils/notify'

const route = useRoute()
const router = useRouter()

const marca = ref(null)
const loading = ref(true)
const gerando = ref(false)

const dialog = ref(false)
const saving = ref(false)
const model = ref(null)

const codFormatado = (v) => '#' + String(v).padStart(6, '0')
const formataMoeda = (v) =>
  (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
const formataData = (v) => (v ? new Date(v).toLocaleDateString('pt-BR') : '—')

const carregar = async () => {
  loading.value = true
  try {
    const { data } = await api.get(`v1/marca/${route.params.id}/detalhes`)
    marca.value = data
  } catch (e) {
    notifyError(e, 'Erro ao carregar marca')
  } finally {
    loading.value = false
  }
}

const abrirEditar = () => {
  model.value = {
    marca: marca.value.marca,
    site: !!marca.value.site,
    abcignorar: !!marca.value.abcignorar,
    controlada: !!marca.value.controlada,
    descricaosite: marca.value.descricaosite || '',
  }
  dialog.value = true
}

const salvar = async () => {
  saving.value = true
  try {
    const { data } = await api.put(`v1/marca/${marca.value.codmarca}`, model.value)
    marca.value = { ...marca.value, ...data }
    notifySuccess('Marca atualizada')
    dialog.value = false
  } catch (e) {
    notifyError(e, 'Erro ao atualizar marca')
  } finally {
    saving.value = false
  }
}

const toggleInativo = async () => {
  try {
    const { data } = marca.value.inativo
      ? await api.delete(`v1/marca/${marca.value.codmarca}/inativo`)
      : await api.post(`v1/marca/${marca.value.codmarca}/inativo`)
    marca.value = { ...marca.value, ...data }
    notifySuccess(marca.value.inativo ? 'Marca inativada' : 'Marca reativada')
  } catch (e) {
    notifyError(e, 'Erro ao alterar situação')
  }
}

const gerarPlanilha = async (tipo) => {
  const url =
    tipo === 'pedido'
      ? `v1/marca/${marca.value.codmarca}/planilha/pedido`
      : `v1/marca/${marca.value.codmarca}/planilha/distribuicao-saldo-deposito`
  gerando.value = true
  try {
    const { data } = await api.put(url)
    notifySuccess('Planilha gerada')
    if (typeof data === 'string' && /^https?:\/\//.test(data)) {
      window.open(data, '_blank')
    }
  } catch (e) {
    notifyError(e, 'Erro ao gerar planilha')
  } finally {
    gerando.value = false
  }
}

onMounted(carregar)
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 1080px; margin: auto">
      <div class="row items-center q-mb-md">
        <q-btn flat round dense icon="arrow_back" @click="goBack(router)" />
        <div class="text-h5 q-ml-sm">{{ marca?.marca || 'Marca' }}</div>
        <q-badge v-if="marca?.inativo" color="orange-7" class="q-ml-sm">Inativo</q-badge>
        <q-space />
        <q-btn flat round dense color="grey-7" icon="edit" @click="abrirEditar">
          <q-tooltip>Editar</q-tooltip>
        </q-btn>
        <q-btn
          flat
          round
          dense
          color="grey-7"
          :icon="marca?.inativo ? 'play_arrow' : 'pause'"
          @click="toggleInativo"
        >
          <q-tooltip>{{ marca?.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
        </q-btn>
      </div>

      <div v-if="loading" class="row justify-center q-my-xl">
        <q-spinner-dots color="primary" size="40px" />
      </div>

      <div v-else-if="marca" class="row q-col-gutter-md">
        <!-- Coluna esquerda: logo + identificação -->
        <div class="col-xs-12 col-md-4">
          <q-card bordered flat>
            <q-card-section class="column items-center">
              <q-avatar rounded size="160px" color="grey-3" text-color="grey-7">
                <img v-if="marca.imagem && marca.imagem.url" :src="marca.imagem.url" />
                <q-icon v-else name="sell" size="64px" />
              </q-avatar>
              <div class="text-caption text-grey-6 q-mt-md">{{ codFormatado(marca.codmarca) }}</div>
              <q-rating
                :model-value="marca.abccategoria || 0"
                max="3"
                size="22px"
                color="amber-7"
                icon="star_border"
                icon-selected="star"
                readonly
                class="q-mt-xs"
              />
              <div v-if="marca.abcposicao" class="text-caption text-grey-6">
                Posição ABC: {{ marca.abcposicao }}
              </div>
            </q-card-section>
            <q-separator inset />
            <q-list>
              <q-item>
                <q-item-section>No site</q-item-section>
                <q-item-section side>
                  <q-icon
                    :name="marca.site ? 'check_circle' : 'cancel'"
                    :color="marca.site ? 'green-6' : 'grey-5'"
                  />
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Controlada</q-item-section>
                <q-item-section side>
                  <q-icon
                    :name="marca.controlada ? 'check_circle' : 'cancel'"
                    :color="marca.controlada ? 'green-6' : 'grey-5'"
                  />
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Ignora curva ABC</q-item-section>
                <q-item-section side>
                  <q-icon
                    :name="marca.abcignorar ? 'check_circle' : 'cancel'"
                    :color="marca.abcignorar ? 'amber-8' : 'grey-5'"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </div>

        <!-- Coluna direita: métricas + descrição + ações -->
        <div class="col-xs-12 col-md-8">
          <q-card bordered flat>
            <q-card-section class="text-grey-9 text-overline">Vendas</q-card-section>
            <q-separator inset />
            <div class="row q-pa-md q-col-gutter-md">
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Bimestre</div>
                <div class="text-subtitle1">{{ formataMoeda(marca.vendabimestrevalor) }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Semestre</div>
                <div class="text-subtitle1">{{ formataMoeda(marca.vendasemestrevalor) }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Ano</div>
                <div class="text-subtitle1">{{ formataMoeda(marca.vendaanovalor) }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">% do total/ano</div>
                <div class="text-subtitle1">
                  {{ marca.vendaanopercentual ? Number(marca.vendaanopercentual).toFixed(2) : '0' }}%
                </div>
              </div>
            </div>
          </q-card>

          <q-card bordered flat class="q-mt-md">
            <q-card-section class="text-grey-9 text-overline">Estoque</q-card-section>
            <q-separator inset />
            <div class="row q-pa-md q-col-gutter-md">
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Itens faltando</div>
                <div class="text-subtitle1 text-red-6">{{ marca.itensabaixominimo || 0 }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Itens sobrando</div>
                <div class="text-subtitle1 text-orange-8">{{ marca.itensacimamaximo || 0 }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Última compra</div>
                <div class="text-subtitle1">{{ formataData(marca.dataultimacompra) }}</div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="text-caption text-grey-6">Estoque (dias)</div>
                <div class="text-subtitle1">
                  {{ marca.estoqueminimodias || 0 }}–{{ marca.estoquemaximodias || 0 }}
                </div>
              </div>
            </div>
          </q-card>

          <q-card v-if="marca.descricaosite" bordered flat class="q-mt-md">
            <q-card-section class="text-grey-9 text-overline">Descrição do site</q-card-section>
            <q-separator inset />
            <q-card-section class="text-body2 text-grey-8" style="white-space: pre-line">
              {{ marca.descricaosite }}
            </q-card-section>
          </q-card>

          <q-card bordered flat class="q-mt-md">
            <q-card-section class="text-grey-9 text-overline">Relatórios</q-card-section>
            <q-separator inset />
            <q-card-actions>
              <q-btn
                flat
                color="primary"
                icon="print"
                label="Planilha de Pedido"
                :loading="gerando"
                @click="gerarPlanilha('pedido')"
              />
              <q-btn
                flat
                color="primary"
                icon="print"
                label="Distribuição de Saldo"
                :loading="gerando"
                @click="gerarPlanilha('distribuicao')"
              />
            </q-card-actions>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Dialog de edição -->
    <q-dialog v-model="dialog">
      <q-card bordered flat style="width: 460px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">EDITAR MARCA</q-card-section>
        <q-form @submit.prevent="salvar">
          <q-separator inset />
          <q-card-section v-if="model">
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-input
                  v-model="model.marca"
                  outlined
                  label="Marca"
                  maxlength="100"
                  autofocus
                  :rules="[(v) => (!!v && v.length >= 2) || 'Mínimo 2 caracteres']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="model.descricaosite"
                  outlined
                  type="textarea"
                  autogrow
                  label="Descrição para o site"
                />
              </div>
              <div class="col-12"><q-toggle v-model="model.site" label="Disponível no site" /></div>
              <div class="col-12">
                <q-toggle v-model="model.controlada" label="Marca controlada" />
              </div>
              <div class="col-12">
                <q-toggle v-model="model.abcignorar" label="Ignorar na curva ABC" />
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
