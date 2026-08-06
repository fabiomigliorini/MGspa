<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { rhStore } from 'src/stores/rh'
import { useAuthStore } from 'src/stores'
import { extrairErro } from 'src/utils/rhFormatters'
import { formataNumero } from '@components/formatters'
import MGLayout from 'layouts/MGLayout.vue'
import RubricaForm from 'src/components/rh/RubricaForm.vue'
import MgInfoCriacao from '@components/MgInfoCriacao.vue'
import { useSelectCacheStore } from '@components/stores/selectCacheStore'

const $q = useQuasar()
const sRh = rhStore()
const user = useAuthStore()
const cache = useSelectCacheStore()

const { rubricas } = storeToRefs(sRh)

const podeEditar = computed(() => user.temPermissao('Recursos Humanos'))

const rubricasOrdenadas = computed(() =>
  [...rubricas.value].sort((a, b) => a.descricao.localeCompare(b.descricao)),
)

// Depois de mexer no catálogo, derruba os dois caches que guardam a descrição:
//  - o do MgSelectRubrica (padrão LOCAL, carrega uma vez por sessão);
//  - o de colaboradores, cujas rubricas trazem a descrição denormalizada e que as
//    telas só refazem quando a lista está vazia — sem isso o card do colaborador
//    segue exibindo o nome antigo até dar F5, mesmo com o backend já propagando.
const recarregar = async () => {
  cache.invalidate('rubrica')
  sRh.colaboradores = []
  await sRh.getRubricas()
}

// --- DIALOG ---
const dialog = ref(false)
const isNovo = ref(false)
const cad = ref({})
const salvando = ref(false)

const abrirNovo = () => {
  isNovo.value = true
  cad.value = {
    descricao: '',
    tipovalor: 'F',
    tipocondicao: null,
    valorpadrao: null,
    valorunitariopadrao: null,
    recorrente: true,
  }
  dialog.value = true
}

const editar = (r) => {
  isNovo.value = false
  cad.value = { ...r }
  dialog.value = true
}

const submit = async () => {
  if (salvando.value) return
  salvando.value = true
  try {
    if (isNovo.value) {
      await sRh.criarRubrica(cad.value)
    } else {
      await sRh.atualizarRubrica(cad.value.codrubrica, cad.value)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: isNovo.value ? 'Rubrica criada' : 'Rubrica atualizada',
    })
    dialog.value = false
    await recarregar()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao salvar rubrica'),
    })
  } finally {
    salvando.value = false
  }
}

const excluir = (r) => {
  $q.dialog({
    title: 'Excluir Rubrica',
    message: `Tem certeza que deseja excluir "${r.descricao}"?`,
    cancel: { label: 'Cancelar', color: 'grey-8', flat: true },
    ok: { label: 'Excluir', color: 'red-5', flat: true },
  }).onOk(async () => {
    try {
      await sRh.excluirRubrica(r.codrubrica)
      $q.notify({
        color: 'green-5',
        textColor: 'white',
        icon: 'done',
        message: 'Rubrica excluída',
      })
      await recarregar()
    } catch (error) {
      $q.notify({
        color: 'red-5',
        textColor: 'white',
        icon: 'error',
        message: extrairErro(error, 'Erro ao excluir rubrica'),
      })
    }
  })
}

const toggleInativo = async (r) => {
  try {
    if (r.inativo) {
      await sRh.ativarRubrica(r.codrubrica)
    } else {
      await sRh.inativarRubrica(r.codrubrica)
    }
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: r.inativo ? 'Rubrica ativada' : 'Rubrica inativada',
    })
    await recarregar()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao alterar situação da rubrica'),
    })
  }
}

const tipoValorLabel = (t) => {
  const map = { P: 'Percentual', F: 'Fixo', Q: 'Unitário × Qtd' }
  return map[t] || t
}

const tipoValorColor = (t) => {
  const map = { P: 'blue', F: 'purple', Q: 'teal' }
  return map[t] || 'grey'
}

const condicaoLabel = (t) => {
  const map = { M: 'Meta', R: 'Ranking' }
  return map[t] || '—'
}

const padraoLabel = (r) => {
  const valor = r.tipovalor === 'Q' ? r.valorunitariopadrao : r.valorpadrao
  return valor != null ? formataNumero(valor) : '—'
}

const carregar = async () => {
  try {
    await sRh.getRubricas()
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: extrairErro(error, 'Erro ao carregar rubricas'),
    })
  }
}

onMounted(() => {
  carregar()
})
</script>

<template>
  <!-- DIALOG RUBRICA -->
  <q-dialog v-model="dialog">
    <q-card flat style="width: 600px; max-width: 90vw">
      <q-card-section class="text-grey-9 text-overline">
        <template v-if="isNovo">NOVA RUBRICA</template>
        <template v-else>EDITAR RUBRICA</template>
      </q-card-section>

      <q-form @submit.prevent="submit()">
        <q-separator inset />

        <q-card-section>
          <RubricaForm :cad="cad" />
        </q-card-section>

        <q-separator inset />

        <q-card-actions align="right" class="text-primary">
          <MgInfoCriacao v-if="!isNovo" :registro="cad" />
          <q-space />
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" color="grey-8" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MGLayout>
    <template #tituloPagina>
      <span class="q-pl-sm">Catálogo de Rubricas</span>
    </template>

    <template #content>
      <q-page>
        <div style="max-width: 1100px; margin: auto" class="q-pa-md">
          <q-card bordered flat>
            <q-card-section class="text-grey-9 text-overline row items-center">
              RUBRICAS
              <q-space />
              <q-btn
                flat
                round
                icon="add"
                size="sm"
                color="primary"
                v-if="podeEditar"
                @click="abrirNovo()"
              >
                <q-tooltip>Nova Rubrica</q-tooltip>
              </q-btn>
            </q-card-section>

            <q-markup-table flat separator="horizontal" v-if="rubricas.length > 0">
              <thead>
                <tr class="text-left">
                  <th>Descrição</th>
                  <th>Tipo</th>
                  <th class="text-right">Padrão</th>
                  <th>Condição</th>
                  <th>Recorrente</th>
                  <th>Ativo</th>
                  <th class="text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="r in rubricasOrdenadas"
                  :key="r.codrubrica"
                  :class="r.inativo ? 'text-grey-5' : ''"
                >
                  <td>{{ r.descricao }}</td>
                  <td>
                    <q-badge
                      :color="tipoValorColor(r.tipovalor)"
                      :label="tipoValorLabel(r.tipovalor)"
                    />
                  </td>
                  <td class="text-right">{{ padraoLabel(r) }}</td>
                  <td>{{ condicaoLabel(r.tipocondicao) }}</td>
                  <td>
                    <q-icon v-if="r.recorrente" name="check" color="green" />
                    <span v-else>—</span>
                  </td>
                  <td>
                    <q-badge
                      :color="r.inativo ? 'grey' : 'green'"
                      :label="r.inativo ? 'Inativo' : 'Ativo'"
                    />
                  </td>
                  <td class="text-right">
                    <template v-if="podeEditar">
                      <q-btn flat round icon="edit" size="sm" color="grey-7" @click="editar(r)">
                        <q-tooltip>Editar</q-tooltip>
                      </q-btn>
                      <q-btn
                        flat
                        round
                        size="sm"
                        color="grey-7"
                        :icon="r.inativo ? 'play_arrow' : 'pause'"
                        @click="toggleInativo(r)"
                      >
                        <q-tooltip>{{ r.inativo ? 'Ativar' : 'Inativar' }}</q-tooltip>
                      </q-btn>
                      <q-btn flat round icon="delete" size="sm" color="grey-7" @click="excluir(r)">
                        <q-tooltip>Excluir</q-tooltip>
                      </q-btn>
                      <MgInfoCriacao :registro="r" />
                    </template>
                  </td>
                </tr>
              </tbody>
            </q-markup-table>
            <div v-else class="q-pa-md text-center text-grey">Nenhuma rubrica cadastrada</div>
          </q-card>
        </div>
      </q-page>
    </template>
  </MGLayout>
</template>
