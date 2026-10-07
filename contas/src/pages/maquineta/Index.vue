<script setup>
import { onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import MgInput from '@components/MgInput.vue'
import MgSelectFilial from '@components/MgSelectFilial.vue'
import MgSelectPessoa from '@components/MgSelectPessoa.vue'
import MgSelectMaquineta from '@components/MgSelectMaquineta.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { formataTimestamp } from '@components/formatters'
import { useMaquinetaStore } from 'src/stores/maquinetaStore'
import {
  MAQUINETA_INTEGRACAO_OPTIONS,
  maquinetaIntegracaoLabel,
  maquinetaIntegracaoColor,
} from 'src/constants/maquinetaIntegracao'

const $q = useQuasar()
const store = useMaquinetaStore()
const { items, loading, dialog, model, registro, salvando, isNovo, qr } = storeToRefs(store)
const { dialogParear, dialogJuntar, juntarDestino } = storeToRefs(store)

const columns = [
  { name: 'apelido', label: 'Maquineta', field: 'apelido', align: 'left' },
  { name: 'integracao', label: 'Integração', field: 'integracao', align: 'left' },
  { name: 'adquirente', label: 'Adquirente', field: 'adquirente', align: 'left' },
  { name: 'filial', label: 'Filial', field: 'filial', align: 'left' },
  { name: 'serial', label: 'Serial', field: 'serial', align: 'left' },
  { name: 'periodo', label: 'Período', field: 'periodos', align: 'left' },
  { name: 'inativo', label: 'Status', field: 'inativo', align: 'center' },
  { name: 'acoes', label: '', field: 'acoes', align: 'right' },
]

const excluir = (row) => {
  $q.dialog({
    title: 'Excluir',
    message: `Confirma excluir a maquineta "${row.apelido}"?`,
    ok: { label: 'Excluir', color: 'red-5', flat: true },
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
  }).onOk(() => store.excluir(row))
}

const submit = () => {
  if (isNovo.value && model.value.integracao === 'S') {
    if (qr.value.qrcode) {
      store.confirmarLeitura()
    } else {
      store.gerarQrCode()
    }
    return
  }
  store.salvar()
}

const carregarMais = async (index, done) => {
  await store.fetchItems(false)
  done(!store.hasMore)
}

onMounted(() => {
  store.fetchItems(true)
})
</script>

<template>
  <q-page>
    <q-infinite-scroll @load="carregarMais" :offset="250">
      <div class="q-pa-md" style="margin: auto; max-width: 1086px">
        <q-table
          :rows="items"
          :columns="columns"
          row-key="codmaquineta"
          flat
          bordered
          :loading="loading"
          hide-pagination
          :rows-per-page-options="[0]"
          :pagination="{ rowsPerPage: 0 }"
          no-data-label="Nenhuma maquineta encontrada"
        >
          <template #body-cell-apelido="props">
            <q-td :props="props" class="text-weight-medium">
              <router-link
                :to="{
                  name: 'maquineta-detalhe',
                  params: { codmaquineta: props.row.codmaquineta },
                }"
                class="text-primary"
              >
                {{ props.value }}
              </router-link>
            </q-td>
          </template>

          <!-- conferência do cartão com o borderô: o período aberto, os pendentes e os sem foto -->
          <template #body-cell-periodo="props">
            <q-td :props="props">
              <template v-if="props.row.periodos">
                <div v-if="props.row.periodos.aberto" class="text-caption text-grey-7">
                  Aberto desde {{ formataTimestamp(props.row.periodos.aberto, 2) }}
                </div>
                <q-badge
                  v-if="props.row.periodos.pendentes"
                  color="amber-8"
                  class="q-mr-xs"
                  :label="`${props.row.periodos.pendentes} pendente(s)`"
                />
                <q-badge
                  v-if="props.row.periodos.semBordero"
                  color="orange-8"
                  :label="`${props.row.periodos.semBordero} sem borderô`"
                />
              </template>
              <span v-else class="text-grey-5">—</span>
            </q-td>
          </template>

          <template #body-cell-integracao="props">
            <q-td :props="props">
              <q-badge :color="maquinetaIntegracaoColor(props.row.integracao)">
                {{ maquinetaIntegracaoLabel(props.row.integracao) }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-adquirente="props">
            <q-td :props="props" class="text-grey-8">{{ props.value || '—' }}</q-td>
          </template>

          <template #body-cell-filial="props">
            <q-td :props="props" class="text-grey-8">
              {{ props.row.filial || '—' }}
              <q-badge v-if="props.row.compartilhada" color="teal-7" class="q-ml-xs">
                Todas as filiais
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-serial="props">
            <q-td :props="props" class="text-grey-8">{{ props.value || '—' }}</q-td>
          </template>

          <template #body-cell-inativo="props">
            <q-td :props="props">
              <q-badge v-if="props.row.inativo" color="orange-7">Inativa</q-badge>
              <q-badge v-else color="green-6">Ativa</q-badge>
            </q-td>
          </template>

          <template #body-cell-acoes="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                icon="edit"
                @click="store.abrirEditar(props.row)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn
                v-if="props.row.integracao === 'S' && !props.row.inativo"
                flat
                round
                size="sm"
                color="grey-7"
                icon="qr_code_2"
                @click="store.abrirParear(props.row)"
              >
                <q-tooltip>Parear de novo</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!props.row.integracao"
                flat
                round
                size="sm"
                color="grey-7"
                icon="merge"
                @click="store.abrirJuntar(props.row)"
              >
                <q-tooltip>Juntar com outra</q-tooltip>
              </q-btn>
              <q-btn
                flat
                round
                size="sm"
                color="grey-7"
                :icon="props.row.inativo ? 'play_arrow' : 'pause'"
                @click="store.alternarInativo(props.row)"
              >
                <q-tooltip>{{ props.row.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
              </q-btn>
              <q-btn
                v-if="!props.row.integracao"
                flat
                round
                size="sm"
                color="grey-7"
                icon="delete"
                @click="excluir(props.row)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </div>

      <template #loading>
        <div class="row justify-center q-my-md">
          <q-spinner-dots color="primary" size="32px" />
        </div>
      </template>
    </q-infinite-scroll>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="add" color="primary" @click="store.abrirNovo()">
        <q-tooltip anchor="center left" self="center right">Nova Maquineta</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- cadastro -->
    <q-dialog v-model="dialog">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          {{ isNovo ? 'NOVA MAQUINETA' : 'EDITAR MAQUINETA' }}
        </q-card-section>
        <q-form @submit.prevent="submit">
          <q-separator inset />
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12">
                <q-select
                  v-model="model.integracao"
                  :options="MAQUINETA_INTEGRACAO_OPTIONS"
                  emit-value
                  map-options
                  outlined
                  label="Integração"
                  :readonly="!isNovo || !!qr.qrcode"
                  :tabindex="!isNovo || !!qr.qrcode ? -1 : undefined"
                />
              </div>

              <div class="col-12 col-sm-8">
                <MgInput
                  v-model="model.apelido"
                  label="Apelido"
                  maxlength="50"
                  autofocus
                  :readonly="!!qr.qrcode"
                  lazy-rules
                  :rules="[(v) => !!v && v.length >= 3]"
                />
              </div>

              <div class="col-12 col-sm-4">
                <MgSelectFilial
                  v-model="model.codfilial"
                  outlined
                  label="Filial"
                  :readonly="!!qr.qrcode"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>

              <div v-if="model.integracao !== 'S' || !isNovo" class="col-12 col-sm-6">
                <MgInput
                  v-model="model.serial"
                  label="Serial"
                  maxlength="50"
                  lazy-rules
                  :rules="[(v) => model.integracao !== 'P' || !!v]"
                />
              </div>

              <div v-if="model.integracao === 'M'" class="col-12 col-sm-6">
                <MgSelectPessoa
                  v-model="model.codpessoa"
                  label="Adquirente"
                  lazy-rules
                  :rules="[(v) => !!v]"
                />
              </div>

              <div v-if="model.integracao !== 'S'" class="col-12">
                <q-checkbox
                  v-model="model.compartilhada"
                  label="Compartilhada: aparece no PDV de todas as filiais (ex.: acesso de site)"
                />
              </div>

              <div v-if="isNovo && model.integracao === 'S'" class="col-12">
                <div v-if="qr.qrcode" class="column items-center">
                  <img :src="qr.qrcode" style="width: 220px; height: 220px" alt="QR Code" />
                  <div class="text-caption text-grey-7 q-mt-sm text-center">
                    Leia o QR Code no pinpad e clique em Confirmar leitura.
                  </div>
                </div>
                <div v-else class="text-caption text-grey-7">
                  Gera o QR Code que o pinpad lê para parear com a SafraPay.
                </div>
              </div>
            </div>
          </q-card-section>

          <q-card-section v-if="registro && !isNovo" class="q-pt-none">
            <MgInfoCriacao :registro="registro" />
          </q-card-section>

          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn
              v-if="isNovo && model.integracao === 'S'"
              flat
              :label="qr.qrcode ? 'Confirmar leitura' : 'Gerar QR Code'"
              type="submit"
              :loading="salvando"
            />
            <q-btn v-else flat label="Salvar" type="submit" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- parear de novo (SafraPay) -->
    <q-dialog v-model="dialogParear">
      <q-card flat style="width: 400px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">
          PAREAR {{ registro?.apelido }}
        </q-card-section>
        <q-separator inset />
        <q-card-section class="column items-center">
          <img
            v-if="qr.qrcode"
            :src="qr.qrcode"
            style="width: 220px; height: 220px"
            alt="QR Code"
          />
          <q-spinner-dots v-else-if="salvando" color="primary" size="32px" />
          <div v-else class="text-negative text-center">Não foi possível gerar o QR Code.</div>
          <div class="text-caption text-grey-7 q-mt-sm text-center">
            Leia o QR Code no pinpad novo e clique em Confirmar leitura. A maquineta do pinpad
            anterior é inativada.
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn
            v-if="!qr.qrcode"
            flat
            label="Gerar QR Code"
            :loading="salvando"
            @click="store.gerarQrCode(registro.codmaquineta)"
          />
          <q-btn
            v-else
            flat
            label="Confirmar leitura"
            :loading="salvando"
            @click="store.confirmarLeitura()"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- juntar -->
    <q-dialog v-model="dialogJuntar">
      <q-card flat style="width: 600px; max-width: 90vw">
        <q-card-section class="text-grey-9 text-overline">JUNTAR MAQUINETA</q-card-section>
        <q-form @submit.prevent="store.juntar()">
          <q-separator inset />
          <q-card-section>
            <div class="text-body2 q-mb-md">
              Os pagamentos de <b>{{ registro?.apelido }}</b> passam para a maquineta escolhida, e
              <b>{{ registro?.apelido }}</b> é excluída.
            </div>
            <MgSelectMaquineta
              v-model="juntarDestino"
              label="Juntar com"
              :codpessoa="registro?.codpessoa"
              :excluir="registro?.codmaquineta"
              autofocus
              lazy-rules
              :rules="[(v) => !!v]"
            />
          </q-card-section>
          <q-separator inset />
          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
            <q-btn flat label="Juntar" type="submit" :loading="salvando" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>
  </q-page>
</template>
