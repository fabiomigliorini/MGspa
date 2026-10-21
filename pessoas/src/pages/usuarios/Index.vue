<template>
  <MGLayout drawer>
    <template #tituloPagina>Usuários</template>

    <template #content v-if="user.temPermissao('Administrador')">
      <q-page>
        <div class="q-pa-md" style="max-width: 1086px; margin: auto">
          <!-- Loading inicial -->
          <div v-if="loading && sUsuario.usuarios.length === 0" class="row justify-center q-py-xl">
            <q-spinner color="primary" size="3em" />
          </div>

          <!-- Vazio -->
          <MgEmptyState v-else-if="sUsuario.usuarios.length === 0" icon="person">
            Nenhum usuário encontrado.
          </MgEmptyState>

          <q-infinite-scroll v-else @load="scrollUsuario" :disable="loading" :offset="250">
            <q-card flat bordered>
              <q-list separator>
                <q-item
                  v-for="usuario in sUsuario.usuarios"
                  :key="usuario.codusuario"
                  clickable
                  :to="'/usuarios/' + usuario.codusuario"
                >
                  <q-item-section avatar>
                    <q-avatar
                      :color="usuario.inativo ? 'grey-4' : 'grey-8'"
                      text-color="grey-2"
                      size="md"
                    >
                      {{ usuario.usuario?.slice(0, 1)?.toUpperCase() }}
                    </q-avatar>
                  </q-item-section>

                  <q-item-section>
                    <!-- full-width: sem ela o texto quebra na medição e a linha fica mais alta -->
                    <div class="row items-start full-width">
                      <!-- Usuário, com a pessoa embaixo -->
                      <div class="q-pr-sm col-12 col-sm-5 col-md-4">
                        <div
                          class="text-weight-medium ellipsis"
                          :class="{ 'text-grey-6': usuario.inativo }"
                        >
                          {{ usuario.usuario }}
                        </div>
                        <div class="text-caption text-grey-7 ellipsis">
                          {{ usuario.Pessoa?.pessoa }}
                        </div>
                      </div>

                      <!-- Grupos, com a quantidade embaixo -->
                      <div class="q-pr-sm col-12 col-sm-7 col-md-8">
                        <div class="text-caption ellipsis">
                          {{ usuario.permissoes?.map((p) => p.grupousuario).join(', ') }}
                        </div>
                        <div class="text-caption text-grey-7 ellipsis">
                          {{ usuario.permissoes?.length || 0 }}
                          {{ usuario.permissoes?.length == 1 ? 'grupo' : 'grupos' }}
                        </div>
                      </div>
                    </div>
                  </q-item-section>

                  <q-item-section side v-if="usuario.inativo">
                    <q-badge color="red" label="Inativo" />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </q-infinite-scroll>

          <!-- o respiro para o botão de novo não cobrir o fim da lista -->
          <div class="q-py-xl" />
        </div>

        <q-page-sticky position="bottom-right" :offset="[18, 18]">
          <q-btn fab icon="add" color="primary" :to="{ name: 'usuarionovo' }">
            <q-tooltip anchor="center left" self="center right">Novo Usuário</q-tooltip>
          </q-btn>
        </q-page-sticky>
      </q-page>
    </template>

    <template #content v-else>
      <nao-autorizado />
    </template>

    <template #drawer v-if="user.temPermissao('Administrador')">
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Usuário
              <q-btn icon="replay" @click="buscarUsuarios()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <q-form @change="buscarUsuarios()">
          <div class="q-pa-md q-gutter-md">
            <MgInput outlined v-model="sUsuario.filtroUsuarioPesquisa.usuario" label="Usuário" />

            <MgSelect
              outlined
              v-model="sUsuario.filtroUsuarioPesquisa.inativo"
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
import { usuarioStore } from 'src/stores/usuario'
import { useAuthStore } from 'src/stores'

// 3. Imports de utilitários
import { useQuasar, debounce } from 'quasar'

// 4. Imports de componentes
import MGLayout from 'layouts/MGLayout.vue'
import NaoAutorizado from 'components/NaoAutorizado.vue'

// 5. Instâncias
const sUsuario = usuarioStore()
const user = useAuthStore()
const $q = useQuasar()

// 6. Refs
const loading = ref(true)

// 7. Funções
const buscarUsuarios = debounce(async () => {
  $q.loadingBar.start()
  sUsuario.filtroUsuarioPesquisa.page = 1
  try {
    await sUsuario.todosUsuarios()
  } catch (error) {
    console.log(error)
  } finally {
    loading.value = false
    $q.loadingBar.stop()
  }
}, 500)

const scrollUsuario = async (_index, done) => {
  loading.value = true
  sUsuario.filtroUsuarioPesquisa.page++
  const ret = await sUsuario.todosUsuarios()
  loading.value = false
  if (ret.data.data.length === 0) {
    loading.value = true
  }
  await done()
}

// 8. Watchers
watch(
  () => sUsuario.filtroUsuarioPesquisa.inativo,
  () => buscarUsuarios(),
)

// 9. Lifecycle
onMounted(() => {
  if (sUsuario.usuarios.length === 0) {
    buscarUsuarios()
  }
})
</script>
