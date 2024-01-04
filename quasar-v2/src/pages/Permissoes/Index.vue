<template>
    <MGLayout>
        <template #tituloPagina>
            Permissões
        </template>

        <template #content v-if="user.usuarioLogado.permissoes.find
            (item => item.grupo === 'Administrador')">

            <q-page class="q-pa-md">
                <div class="row q-col-gutter-md">
                   
                    <tree-grupo-usuarios></tree-grupo-usuarios>

                    <div class="col-md-8 col-md-8 col-xs-12">
                        <q-card class="no-shadow" bordered>
                            <q-card-section>
                                <div class="text-h6 text-indigo-8">
                                    Permissões
                                    <q-btn round flat icon="add" />
                                </div>
                            </q-card-section>

                            <q-card-section class="q-pa-none">
                            </q-card-section>

                            <tabela-permissoes></tabela-permissoes>

                            <!-- <span>
                                    <q-hierarchy :columns="columns" classes="no-shadow" :data="data"></q-hierarchy>
                                </span> -->

                            <!-- <q-tree class="col-12 col-sm-6" :nodes="simple" node-key="label" tick-strategy="leaf"
                                    v-model:selected="selected" v-model:ticked="ticked" v-model:expanded="expanded" /> -->

                        </q-card>
                    </div>
                </div>
            </q-page>
        </template>

        <!-- Template Não Autorizado -->
        <template #content v-else>
            <nao-autorizado></nao-autorizado>
        </template>

    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, onMounted } from 'vue'
import { useQuasar } from "quasar"
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { guardaToken } from 'src/stores'
import { ref } from 'vue'


export default defineComponent({
    name: "Permissoes",
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
        TabelaPermissoes: defineAsyncComponent(() => import('components/Permissoes/TabelaPermissoes.vue')),
        NaoAutorizado: defineAsyncComponent(() => import('components/NaoAutorizado.vue')),
        TreeGrupoUsuarios: defineAsyncComponent(() => import('components/Permissoes/TreeGrupoUsuarios.vue'))

    },

    setup() {

        const $q = useQuasar()
        const route = useRoute()
        const sPessoa = pessoaStore()
        const user = guardaToken()

        return {
            sPessoa,
            user,
        }
    },
})
</script>