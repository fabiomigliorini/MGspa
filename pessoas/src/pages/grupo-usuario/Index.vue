<template>
  <MGLayout drawer>
    <template #tituloPagina>Grupo Usuários</template>

    <template #content v-if="user.temPermissao('Administrador')">
      <q-page>
        <div class="q-pa-md" style="max-width: 1086px; margin: auto">
          <!-- Loading inicial -->
          <div
            v-if="loading && sGrupoUsuario.grupoUsuarios.length === 0"
            class="row justify-center q-py-xl"
          >
            <q-spinner color="primary" size="3em" />
          </div>

          <!-- Vazio -->
          <MgEmptyState v-else-if="sGrupoUsuario.grupoUsuarios.length === 0" icon="groups">
            Nenhum grupo de usuário encontrado.
          </MgEmptyState>

          <q-infinite-scroll v-else @load="scrollGrupoUsuario" :disable="loading" :offset="250">
            <q-card flat bordered>
              <q-list separator>
                <q-item
                  v-for="grupoUsuario in sGrupoUsuario.grupoUsuarios"
                  :key="grupoUsuario.codgrupousuario"
                  clickable
                  :to="'/grupo-usuarios/' + grupoUsuario.codgrupousuario"
                >
                  <q-item-section avatar>
                    <q-avatar
                      :color="grupoUsuario.inativo ? 'grey-4' : 'grey-8'"
                      text-color="grey-2"
                      size="md"
                    >
                      {{ grupoUsuario.grupousuario?.slice(0, 1)?.toUpperCase() }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <q-item-label
                      class="text-weight-medium ellipsis"
                      :class="{ 'text-grey-6': grupoUsuario.inativo }"
                    >
                      {{ grupoUsuario.grupousuario }}
                    </q-item-label>
                    <q-item-label caption class="ellipsis">
                      {{ grupoUsuario.observacoes }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-item-label class="text-weight-medium text-primary">
                      {{ grupoUsuario.Usuarios?.length || 0 }}
                      <q-icon name="person" />
                    </q-item-label>
                    <q-item-label caption>
                      {{ grupoUsuario.Usuarios?.length == 1 ? 'usuário ativo' : 'usuários ativos' }}
                    </q-item-label>
                    <q-badge v-if="grupoUsuario.inativo" color="red" label="Inativo" />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </q-infinite-scroll>

          <!-- o respiro para o botão de novo não cobrir o fim da lista -->
          <div class="q-py-xl" />
        </div>

        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab icon="add" color="primary" @click="sGrupoUsuario.novoGrupo()">
            <q-tooltip anchor="center left" self="center right">Novo Grupo</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-page>

      <dialog-grupo-usuario />
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>

    <template #drawer v-if="user.temPermissao('Administrador')">
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Grupo Usuário
              <q-btn icon="replay" @click="buscarGruposUsuarios()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <q-form @change="buscarGruposUsuarios()">
          <div class="q-pa-md q-gutter-md">
            <MgInput
              outlined
              v-model="sGrupoUsuario.filtroGrupoUsuarioPesquisa.grupo"
              label="Grupo Usuário"
            />

            <MgSelect
              outlined
              v-model="sGrupoUsuario.filtroGrupoUsuarioPesquisa.inativo"
              label="Ativo / Inativo"
              :options="[
                { label: 'Ativos', value: 1 },
                { label: 'Inativos', value: 2 },
              ]"
              map-options
              emit-value
              clearable
            />
          </div>
        </q-form>
      </div>
    </template>
  </MGLayout>
</template>

<script setup>
import MgInput from '@components/MgInput.vue'
import MgEmptyState from '@components/MgEmptyState.vue'
import MgSelect from '@components/MgSelect.vue'
// 1. Imports do Vue
import { ref, onMounted, watch } from 'vue'

// 2. Imports de stores
import { grupoUsuarioStore } from 'src/stores/grupo-usuario'
import { useAuthStore } from 'src/stores'

// 3. Imports de utilitários
import { useQuasar, debounce } from 'quasar'

// 4. Imports de componentes
import MGLayout from 'layouts/MGLayout.vue'
import NaoAutorizado from 'components/NaoAutorizado.vue'
import DialogGrupoUsuario from 'components/usuario/DialogGrupoUsuario.vue'

// 5. Instâncias
const sGrupoUsuario = grupoUsuarioStore()
const user = useAuthStore()
const $q = useQuasar()

// 6. Refs
const loading = ref(true)

// 7. Funções
const buscarGruposUsuarios = debounce(async () => {
  $q.loadingBar.start()
  sGrupoUsuario.filtroGrupoUsuarioPesquisa.page = 1
  try {
    await sGrupoUsuario.todosGruposUsuarios()
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
    $q.loadingBar.stop()
  }
}, 500)

const scrollGrupoUsuario = async (_index, done) => {
  loading.value = true
  sGrupoUsuario.filtroGrupoUsuarioPesquisa.page++
  const ret = await sGrupoUsuario.todosGruposUsuarios()
  loading.value = false
  if (ret.data.data.length === 0) {
    loading.value = true
  }
  await done()
}

// 8. Watchers
watch(
  () => sGrupoUsuario.filtroGrupoUsuarioPesquisa.inativo,
  () => buscarGruposUsuarios(),
)

// 9. Lifecycle
onMounted(() => {
  if (sGrupoUsuario.grupoUsuarios.length === 0) {
    buscarGruposUsuarios()
  }
})
</script>
