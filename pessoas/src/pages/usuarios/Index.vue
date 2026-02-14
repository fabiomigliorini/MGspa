<template>
  <MGLayout drawer>
    <template #tituloPagina>
      Usuários
    </template>

    <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
      <q-infinite-scroll @load="scrollUsuario" :disable="loading" style="min-height: 100vh">
        <q-separator />
        <div class="row q-pa-md q-col-gutter-md">
          <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" v-for="usuario in sUsuario.usuarios"
            v-bind:key="usuario.codusuario">
            <q-card class="no-shadow cursor-pointer" bordered>
              <q-list>
                <q-item :to="'/usuarios/' + usuario.codusuario">

                  <q-item-section avatar v-if="usuario.usuario">
                    <q-avatar color="primary" class="q-my-md" size="40px" text-color="white">
                      {{ primeiraLetra(usuario.usuario) }}
                    </q-avatar>
                  </q-item-section>
                  <q-card-section>
                    <q-item-label :class="usuario.inativo ? 'text-strike text-red-14' : null">
                      {{ usuario.usuario }}
                    </q-item-label>
                    <q-item-label v-if="usuario.Pessoa" caption>
                      {{ usuario.Pessoa.pessoa }}
                    </q-item-label>
                    <q-item-label v-for="permissao in usuario.permissoes" caption
                      v-bind:key="permissao.codgrupousuario">
                      {{ permissao.grupousuario }}
                    </q-item-label>
                  </q-card-section>
                </q-item>
              </q-list>
            </q-card>
          </div>

        </div>
      </q-infinite-scroll>

      <q-page-sticky position="bottom-right" :offset="[18, 18]" v-if="user.verificaPermissaoUsuario('Administrador')">
        <q-btn fab icon="add" color="accent" :to="{ name: 'usuarionovo' }" />
      </q-page-sticky>

    </template>

    <template #content v-else>
      <nao-autorizado></nao-autorizado>
    </template>

    <template #drawer v-if="user.verificaPermissaoUsuario('Administrador')">
      <div class="q-pa-none q-pt-sm">
        <q-card flat>
          <q-list>
            <q-item-label header>
              Filtro Usuário
              <q-btn icon="replay" @click="buscarUsuarios()" flat round no-caps />
            </q-item-label>
          </q-list>
        </q-card>
        <div class="q-pa-md q-gutter-md">
          <q-input outlined v-model="sUsuario.filtroUsuarioPesquisa.usuario" label="Usuário"
            @change="buscarUsuarios()" />

          <q-select outlined v-model="sUsuario.filtroUsuarioPesquisa.inativo" label="Ativo / Inativo" :options="[
            { label: 'Ativos', value: 1 },
            { label: 'Inativos', value: 2 }]" map-options emit-value clearable />
        </div>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, ref, onMounted, watch } from 'vue'
import { usuarioStore } from 'src/stores/usuario'
import { useQuasar, debounce } from 'quasar'
import { guardaToken } from 'src/stores'

export default defineComponent({
  name: "Index",

  components: {
    MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
    NaoAutorizado: defineAsyncComponent(() =>
      import("components/NaoAutorizado.vue")
    ),
  },
  methods: {
    primeiraLetra(usuario) {
      if (usuario.charAt(0) == ' ') {
        return usuario.charAt(1)
      }
      return usuario.charAt(0)
    },

  },

  setup() {
    const sUsuario = usuarioStore()
    const loading = ref(false)
    const $q = useQuasar()
    const user = guardaToken()

    const buscarUsuarios = debounce(async () => {
      $q.loadingBar.start()
      sUsuario.filtroUsuarioPesquisa.page = 1;
      try {
        const ret = await sUsuario.todosUsuarios()
        loading.value = false;
        $q.loadingBar.stop()
        if (ret.data.data.length == 0) {
          return $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'Nenhum Registro encontrado'
          })
        }
      } catch (error) {
        $q.loadingBar.stop()
      }
    }, 500)


    watch(
      () => sUsuario.filtroUsuarioPesquisa.inativo,
      () => buscarUsuarios(),
    );


    onMounted(async () => {
      buscarUsuarios();
    })

    return {
      sUsuario,
      loading,
      user,
      buscarUsuarios,
      async scrollUsuario(index, done) {
        loading.value = true;
        // $q.loadingBar.start()
        const ret = await sUsuario.todosUsuarios();
        sUsuario.filtroUsuarioPesquisa.page++;
        loading.value = false
        // $q.loadingBar.stop()
        if (ret.data.data.length == 0) {
          loading.value = true
        }
        await done();
      },
    }
  },
})
</script>

<style scoped></style>
