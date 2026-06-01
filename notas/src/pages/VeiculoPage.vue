<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { formataCodigo } from '@components/formatters'
import {
  useVeiculoStore,
  TIPO_RODADO_OPTIONS,
  TIPO_CARROCERIA_OPTIONS,
} from '../stores/veiculoStore'
import veiculoService from '../services/veiculoService'
import { notificarSucesso, notificarErro } from '../utils/notify'
import VeiculoDialog from '../components/dialogs/VeiculoDialog.vue'
import VeiculoConjuntoDialog from '../components/dialogs/VeiculoConjuntoDialog.vue'
import VeiculoTipoDialog from '../components/dialogs/VeiculoTipoDialog.vue'

const $q = useQuasar()
const store = useVeiculoStore()

const loading = computed(() => store.loading)

const veiculoDialog = ref(false)
const veiculoEdit = ref(null)
const conjuntoDialog = ref(false)
const conjuntoEdit = ref(null)
const tipoDialog = ref(false)
const tipoEdit = ref(null)

const labelDe = (options, value) => options.find((o) => o.value === value)?.label ?? ''
const rodadoLabel = (v) => labelDe(TIPO_RODADO_OPTIONS, v)
const carroceriaLabel = (v) => labelDe(TIPO_CARROCERIA_OPTIONS, v)

onMounted(() => {
  store.fetchAll().catch((e) => notificarErro(e, 'Falha ao carregar veículos'))
})

// ---- abrir dialogs ----
const novoVeiculo = () => {
  veiculoEdit.value = null
  veiculoDialog.value = true
}
const editarVeiculo = (v) => {
  veiculoEdit.value = v
  veiculoDialog.value = true
}
const novoConjunto = () => {
  conjuntoEdit.value = null
  conjuntoDialog.value = true
}
const editarConjunto = (c) => {
  conjuntoEdit.value = c
  conjuntoDialog.value = true
}
const novoTipo = () => {
  tipoEdit.value = null
  tipoDialog.value = true
}
const editarTipo = (t) => {
  tipoEdit.value = t
  tipoDialog.value = true
}

// ---- ações genéricas (inativar/ativar/excluir) ----
const toggleInativo = async (entidade, registro) => {
  const cfg = {
    veiculo: {
      id: registro.codveiculo,
      ativar: veiculoService.ativarVeiculo,
      inativar: veiculoService.inativarVeiculo,
      upsert: store.upsertVeiculo,
    },
    conjunto: {
      id: registro.codveiculoconjunto,
      ativar: veiculoService.ativarConjunto,
      inativar: veiculoService.inativarConjunto,
      upsert: store.upsertConjunto,
    },
    tipo: {
      id: registro.codveiculotipo,
      ativar: veiculoService.ativarTipo,
      inativar: veiculoService.inativarTipo,
      upsert: store.upsertTipo,
    },
  }[entidade]
  try {
    const atualizado = registro.inativo ? await cfg.ativar(cfg.id) : await cfg.inativar(cfg.id)
    cfg.upsert(atualizado)
    notificarSucesso(registro.inativo ? 'Reativado!' : 'Inativado!')
  } catch (error) {
    notificarErro(error, 'Falha ao alterar situação')
  }
}

const excluir = (entidade, registro) => {
  const cfg = {
    veiculo: {
      id: registro.codveiculo,
      nome: registro.placa,
      delete: veiculoService.deleteVeiculo,
      remove: store.removeVeiculo,
    },
    conjunto: {
      id: registro.codveiculoconjunto,
      nome: registro.veiculoconjunto,
      delete: veiculoService.deleteConjunto,
      remove: store.removeConjunto,
    },
    tipo: {
      id: registro.codveiculotipo,
      nome: registro.veiculotipo,
      delete: veiculoService.deleteTipo,
      remove: store.removeTipo,
    },
  }[entidade]
  $q.dialog({
    title: 'Confirmar exclusão',
    message: `Deseja realmente excluir "${cfg.nome}"?`,
    cancel: { label: 'Cancelar', flat: true },
    ok: { label: 'Excluir', color: 'negative' },
  }).onOk(async () => {
    try {
      await cfg.delete(cfg.id)
      cfg.remove(cfg.id)
      notificarSucesso('Excluído!')
    } catch (error) {
      notificarErro(error, 'Falha ao excluir (já está em uso?)')
    }
  })
}
</script>

<template>
  <q-page class="q-pa-md">
    <div style="max-width: 800px; margin: auto">
      <q-tabs v-model="store.tab" class="bg-primary text-white shadow-2 rounded-borders">
        <q-tab name="veiculo" icon="local_shipping" label="Veículos" />
        <q-tab name="conjunto" icon="commute" label="Conjuntos" />
        <q-tab name="tipo" icon="handyman" label="Tipos" />
      </q-tabs>

      <div v-if="loading" class="row justify-center q-mt-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <q-tab-panels v-else v-model="store.tab" animated class="bg-transparent q-mt-md">
        <!-- ===== Veículos ===== -->
        <q-tab-panel name="veiculo" class="q-pa-none">
          <q-card flat bordered>
            <q-list separator>
              <q-item v-for="v in store.veiculos" :key="v.codveiculo" :class="{ 'bg-red-1': v.inativo }">
                <q-item-section avatar>
                  <q-avatar icon="local_shipping" color="primary" text-color="white" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ v.placa }} · {{ v.veiculo }}</q-item-label>
                  <q-item-label caption>
                    {{ formataCodigo(v.codveiculo) }} · {{ store.tipoLabel(v.codveiculotipo) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn flat round size="sm" color="grey-7" icon="edit" @click="editarVeiculo(v)">
                      <q-tooltip>Editar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      :icon="v.inativo ? 'play_arrow' : 'pause'"
                      @click="toggleInativo('veiculo', v)"
                    >
                      <q-tooltip>{{ v.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                    </q-btn>
                    <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir('veiculo', v)">
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-section v-if="!store.veiculos.length" class="text-center text-grey-6">
              Nenhum veículo cadastrado
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <!-- ===== Conjuntos ===== -->
        <q-tab-panel name="conjunto" class="q-pa-none">
          <q-card flat bordered>
            <q-list separator>
              <q-item
                v-for="c in store.conjuntos"
                :key="c.codveiculoconjunto"
                :class="{ 'bg-red-1': c.inativo }"
              >
                <q-item-section avatar>
                  <q-avatar icon="commute" color="primary" text-color="white" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ c.veiculoconjunto }}</q-item-label>
                  <q-item-label caption>
                    <q-chip
                      v-for="vc in c.veiculos"
                      :key="vc.codveiculo"
                      size="sm"
                      :icon="vc.tracao ? 'agriculture' : 'rv_hookup'"
                    >
                      {{ vc.placa }}
                    </q-chip>
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn flat round size="sm" color="grey-7" icon="edit" @click="editarConjunto(c)">
                      <q-tooltip>Editar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      :icon="c.inativo ? 'play_arrow' : 'pause'"
                      @click="toggleInativo('conjunto', c)"
                    >
                      <q-tooltip>{{ c.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                    </q-btn>
                    <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir('conjunto', c)">
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-section v-if="!store.conjuntos.length" class="text-center text-grey-6">
              Nenhum conjunto cadastrado
            </q-card-section>
          </q-card>
        </q-tab-panel>

        <!-- ===== Tipos ===== -->
        <q-tab-panel name="tipo" class="q-pa-none">
          <q-card flat bordered>
            <q-list separator>
              <q-item v-for="t in store.tipos" :key="t.codveiculotipo" :class="{ 'bg-red-1': t.inativo }">
                <q-item-section avatar>
                  <q-avatar :icon="t.tracao ? 'agriculture' : 'rv_hookup'" color="primary" text-color="white" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ t.veiculotipo }}</q-item-label>
                  <q-item-label caption>
                    {{ formataCodigo(t.codveiculotipo) }} · {{ rodadoLabel(t.tiporodado) }} ·
                    {{ carroceriaLabel(t.tipocarroceria) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row no-wrap">
                    <q-btn flat round size="sm" color="grey-7" icon="edit" @click="editarTipo(t)">
                      <q-tooltip>Editar</q-tooltip>
                    </q-btn>
                    <q-btn
                      flat
                      round
                      size="sm"
                      color="grey-7"
                      :icon="t.inativo ? 'play_arrow' : 'pause'"
                      @click="toggleInativo('tipo', t)"
                    >
                      <q-tooltip>{{ t.inativo ? 'Reativar' : 'Inativar' }}</q-tooltip>
                    </q-btn>
                    <q-btn flat round size="sm" color="grey-7" icon="delete" @click="excluir('tipo', t)">
                      <q-tooltip>Excluir</q-tooltip>
                    </q-btn>
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-section v-if="!store.tipos.length" class="text-center text-grey-6">
              Nenhum tipo cadastrado
            </q-card-section>
          </q-card>
        </q-tab-panel>
      </q-tab-panels>
    </div>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn v-if="store.tab === 'veiculo'" fab icon="add" color="primary" @click="novoVeiculo">
        <q-tooltip>Novo Veículo</q-tooltip>
      </q-btn>
      <q-btn v-else-if="store.tab === 'conjunto'" fab icon="add" color="primary" @click="novoConjunto">
        <q-tooltip>Novo Conjunto</q-tooltip>
      </q-btn>
      <q-btn v-else fab icon="add" color="primary" @click="novoTipo">
        <q-tooltip>Novo Tipo</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <VeiculoDialog v-model="veiculoDialog" :veiculo="veiculoEdit" @saved="store.upsertVeiculo" />
    <VeiculoConjuntoDialog
      v-model="conjuntoDialog"
      :conjunto="conjuntoEdit"
      @saved="store.upsertConjunto"
    />
    <VeiculoTipoDialog v-model="tipoDialog" :tipo="tipoEdit" @saved="store.upsertTipo" />
  </q-page>
</template>
