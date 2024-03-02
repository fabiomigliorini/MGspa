<template>
    <MGLayout drawer>
        <template #tituloPagina>
            Usuários
        </template>
        <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
            <q-infinite-scroll @load="scrollUsuario" :disable="loading">
                    <q-separator />
                        <div class="row q-pa-md q-col-gutter-md">
                            <div class="xs-12 col-sm-6 col-md-4 col-lg-3" v-for="usuario in sUsuario.usuarios"
                                v-bind:key="usuario.codusuario">
                                <q-card class="no-shadow cursor-pointer" bordered>
                                    <q-list>
                                        <q-item :to="'/usuarios/' + usuario.codusuario">

                                            <q-card-section class="text-center">
                                                <q-avatar size="100px"  class="shadow-10">
                                                    <q-icon name="people" color="primary" />
                                                </q-avatar>
                                            </q-card-section>

                                            <q-card-section>
                                                <q-item-label :class="usuario.inativo ? 'text-strike text-red-14' : null">
                                                    {{ usuario.usuario }}
                                                    
                                                </q-item-label>

                                            </q-card-section>
                                        </q-item>
                                    </q-list>


                                </q-card>
                            </div>

                        </div>
            </q-infinite-scroll>
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
                </div>
            </div>
        </template>
    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, ref, onMounted } from 'vue'
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


        onMounted(async () => {
            if (sUsuario.usuarios.length == 0) {
                buscarUsuarios();
            }

        })

        return {
            sUsuario,
            loading,
            user,
            buscarUsuarios,
            async scrollUsuario(index, done) {
                loading.value = true;
                // $q.loadingBar.start()
                sUsuario.filtroUsuarioPesquisa.page++;
                const ret = await sUsuario.todosUsuarios();
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
  