<template>
    <MGLayout drawer>
        <template #tituloPagina>
            Grupo Usuários
        </template>

        <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
            <q-infinite-scroll @load="scrollGrupoUsuario" :disable="loading">
                <q-separator />
                <div class="row q-pa-md q-col-gutter-md">
                    <div class="xs-12 col-sm-6 col-md-4 col-lg-3" v-for="grupoUsuario in sGrupoUsuario.grupoUsuarios"
                        v-bind:key="grupoUsuario.codgrupousuario">
                        <q-card class="no-shadow cursor-pointer" bordered>
                            
                            <q-list>
                                <q-item :to="'/grupo-usuarios/' + grupoUsuario.codgrupousuario">

                                    <q-card-section class="text-center">
                                        <q-avatar size="100px" class="shadow-10">
                                            <q-icon name="groups_3" color="primary" />
                                        </q-avatar>
                                    </q-card-section>

                                    <q-card-section>
                                        <q-item-label :class="grupoUsuario.inativo ? 'text-strike text-red-14' : null">
                                            {{ grupoUsuario.grupousuario }}

                                        </q-item-label>
                                        <q-item-label caption :class="grupoUsuario.inativo ? 'text-strike text-red-14' : null">
                                            {{ grupoUsuario.observacoes }}

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
                            Filtro Grupo Usuário
                            <q-btn icon="replay" @click="buscarGruposUsuarios()" flat round no-caps />
                        </q-item-label>
                    </q-list>
                </q-card>
                <div class="q-pa-md q-gutter-md">
                    <q-input outlined v-model="sGrupoUsuario.filtroGrupoUsuarioPesquisa.grupo" label="Grupo Usuário"
                        @change="buscarGruposUsuarios()" />

                    <q-select outlined v-model="sGrupoUsuario.filtroGrupoUsuarioPesquisa.inativo" label="Ativo / Inativo"
                        :options="[
            { label: 'Ativos', value: 1 },
            { label: 'Inativos', value: 2 }]" map-options emit-value clearable />
                </div>
            </div>
        </template>
    </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, ref, onMounted, watch } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { guardaToken } from 'src/stores'
import { grupoUsuarioStore } from 'src/stores/grupo-usuario'

export default defineComponent({
    name: "Index",

    components: {
        MGLayout: defineAsyncComponent(() => import("layouts/MGLayout.vue")),
        NaoAutorizado: defineAsyncComponent(() =>
            import("components/NaoAutorizado.vue")
        ),
    },

    setup() {
        const sGrupoUsuario = grupoUsuarioStore()
        const loading = ref(false)
        const $q = useQuasar()
        const user = guardaToken()

        const buscarGruposUsuarios = debounce(async () => {
            $q.loadingBar.start()
            sGrupoUsuario.filtroGrupoUsuarioPesquisa.page = 1;
            try {
                const ret = await sGrupoUsuario.todosGruposUsuarios()
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
            () => sGrupoUsuario.filtroGrupoUsuarioPesquisa.inativo,
            () => buscarGruposUsuarios(),
        );


        onMounted(async () => {
            if (sGrupoUsuario.grupoUsuarios.length == 0) {
                buscarGruposUsuarios();
            }

        })

        return {
            sGrupoUsuario,
            loading,
            user,
            buscarGruposUsuarios,
            async scrollGrupoUsuario(index, done) {
                loading.value = true;
                // $q.loadingBar.start()
                sGrupoUsuario.filtroGrupoUsuarioPesquisa.page++;
                const ret = await sGrupoUsuario.todosGruposUsuarios();
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