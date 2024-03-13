<template>
    <MGLayout back-button>
        <template #tituloPagina>
            <span class="q-pl-sm">Usuário - Editar</span>
        </template>

        <template #botaoVoltar>
            <q-btn flat dense round :to="{ name: 'usuarios' }" icon="arrow_back" aria-label="Voltar">
            </q-btn>
        </template>

        <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
            <q-page class="bg-white">
                <div class="row q-pa-md flex flex-center" v-if="sUsuario.detalheUsuarios">
                    <!-- <div class="col-lg-4 col-md-8 col-sm-12 col-xs-12 q-pl-md"> -->
                    <div class="col-md-4 col-sm-12 col-xs-12" v-if="sUsuario.detalheUsuarios">

                        <!-- AQUI vai ser onde edita os dados do usuario  -->
                        <form-usuario></form-usuario>
                    </div>
                </div>
            </q-page>
        </template>
        <template #content v-else>
            <nao-autorizado></nao-autorizado>
        </template>

    </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, onMounted, ref } from 'vue'
import { useQuasar } from "quasar"
import { useRoute } from 'vue-router'
import { pessoaStore } from 'stores/pessoa'
import { usuarioStore } from 'src/stores/usuario'
import { guardaToken } from 'src/stores'


export default defineComponent({
    name: "usuarioseditar",
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
        FormUsuario: defineAsyncComponent(() => import('components/Usuarios/FormUsuario.vue')),
        // GruposUsuarios: defineAsyncComponent(() => import('components/Usuarios/GruposUsuarios.vue')),
        NaoAutorizado: defineAsyncComponent(() =>
            import("components/NaoAutorizado.vue")
        ),
    },

    methods: {

    },

    setup() {

        const $q = useQuasar()
        const route = useRoute()
        const sPessoa = pessoaStore()
        const totalNegocioPessoa = ref([])
        const sUsuario = usuarioStore()
        const user = guardaToken()

        onMounted(async () => {
            sUsuario.detalheUsuarios = []
            await sUsuario.getUsuario(route.params.codusuario)
        })

        return {
            sPessoa,
            sUsuario,
            user,
            totalNegocioPessoa,
        }
    },
})
</script>

<style scoped></style>