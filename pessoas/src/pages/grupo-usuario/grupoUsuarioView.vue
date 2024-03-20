<template>
    <MGLayout back-button>

        <template #botaoVoltar>
            <q-btn flat dense round :to="{ name: 'grupousuarios' }" icon="arrow_back" aria-label="Voltar">
            </q-btn>
        </template>

        <template #tituloPagina>
            Grupo Usuário
        </template>

        <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
            <q-card bordered class="col-12 q-ma-md">
                <q-item>
                    <q-toolbar class="text-black">
                        <q-avatar color="primary" size="100px" text-color="white" icon="groups"></q-avatar>
                        <q-item class="q-subtitle-1 q-pl-md">
                            <q-item-section>
                                <q-item-label header lines="1" class="text-h4 text-weight-bold"
                                    :class="sGrupoUsuario.detalheGrupoUsuarios.inativo ? 'text-strike text-red-14' : null">
                                    {{ sGrupoUsuario.detalheGrupoUsuarios.grupousuario }}
                                    <span v-if="sGrupoUsuario.detalheGrupoUsuarios.inativo"
                                        class="row text-caption text-red-14">
                                        Inativo desde: {{
                Documentos.formataData(sGrupoUsuario.detalheGrupoUsuarios.inativo) }}
                                    </span>
                                    <q-item-label caption>
                                        {{ sGrupoUsuario.detalheGrupoUsuarios.observacoes }}
                                    </q-item-label>
                                </q-item-label>

                            </q-item-section>
                        </q-item>
                        <q-btn v-if="user.verificaPermissaoUsuario('Administrador')" round flat icon="edit"
                            @click="editar()" />
                        <q-btn v-if="user.verificaPermissaoUsuario('Administrador')" round flat icon="delete"
                            @click="excluir(sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario)" />

                        <q-btn
                            v-if="user.verificaPermissaoUsuario('Administrador') && !sGrupoUsuario.detalheGrupoUsuarios.inativo"
                            round flat icon="pause"
                            @click="inativar(sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario)">
                            <q-tooltip>
                                Inativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn
                            v-if="user.verificaPermissaoUsuario('Administrador') && sGrupoUsuario.detalheGrupoUsuarios.inativo"
                            round flat icon="play_arrow"
                            @click="ativar(sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario)">
                            <q-tooltip>
                                Ativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn round flat icon="info">
                            <q-tooltip>
                                <q-item-label class="row">Criado por {{
                sGrupoUsuario.detalheGrupoUsuarios.usuariocriacao }} em {{
                Documentos.formataData(sGrupoUsuario.detalheGrupoUsuarios.criacao)
            }}</q-item-label>
                                <q-item-label class="row">Alterado por {{
                    sGrupoUsuario.detalheGrupoUsuarios.usuarioalteracao }} em {{
                Documentos.formataData(sGrupoUsuario.detalheGrupoUsuarios.alteracao)
            }}</q-item-label>
                            </q-tooltip>
                        </q-btn>
                    </q-toolbar>
                </q-item>
            </q-card>


            <!-- CARD USUARIOS DO GRUPO -->
            <div class="row q-col-gutter-md q-pa-md">
                <div class="col-md-3 col-sm-6 col-xs-12 col-lg-3 col-xl-2"
                    v-for="usuariosDoGrupo in sGrupoUsuario.detalheGrupoUsuarios.Usuarios"
                    v-bind:key="usuariosDoGrupo.codusuario">
                    <q-card>
                        <q-list>
                            <q-item :to="'/usuarios/' + usuariosDoGrupo.codusuario" clickable>
                                <q-item-section avatar>
                                    <q-avatar color="primary" class="q-my-md" size="35px" text-color="white">
                                        {{ primeiraLetra(usuariosDoGrupo.usuario).toUpperCase() }}
                                    </q-avatar>
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label>
                                        {{ usuariosDoGrupo.usuario }}
                                    </q-item-label>
                                    <q-item-label caption>
                                        <template v-for="(filial, i) in usuariosDoGrupo.filiais" v-bind:key="filial.codfilial">
                                            <span v-if="i != 0">
                                                |
                                            </span>
                                             {{ filial.filial }} 
                                        </template>
                                    </q-item-label>

                                    
                                </q-item-section>

                            </q-item>
                        </q-list>
                    </q-card>
                </div>
            </div>
            <!-- Dialog editar grupo usuario -->
            <q-dialog v-model="dialogEditarGrupoUsuario">
                <q-card style="min-width: 350px">
                    <q-form @submit="salvar()">
                        <q-card-section>
                            <div class="text-h6">Editar Grupo Usuário</div>

                        </q-card-section>
                        <q-card-section>

                            <q-input outlined v-model="modelGrupoUsuario.grupousuario" label="Grupo Usuário" :rules="[
                val => val && val.length > 0 || 'Grupo Usuário obrigatório'
                            ]" />


                            <q-input outlined v-model="modelGrupoUsuario.observacoes" label="Observações" type="area">
                            </q-input>
                        </q-card-section>

                        <q-card-actions align="right" class="text-primary">
                            <q-btn flat label="Cancelar" v-close-popup />
                            <q-btn flat label="Salvar" type="submit" />
                        </q-card-actions>
                    </q-form>
                </q-card>
            </q-dialog>
        </template>

        <template #content v-else>
            <nao-autorizado></nao-autorizado>
        </template>

    </MGLayout>
</template>

<script>
import { defineComponent, defineAsyncComponent, onMounted } from 'vue'
import { useQuasar } from "quasar"
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico'
import { guardaToken } from 'src/stores'
import { pessoaStore } from 'src/stores/pessoa'
import { grupoUsuarioStore } from 'src/stores/grupo-usuario'


export default defineComponent({
    name: "grupoUsuarioView",

    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
        NaoAutorizado: defineAsyncComponent(() =>
            import("components/NaoAutorizado.vue")
        ),
    },


    methods: {
        primeiraLetra(grupoeconomico) {
            if (grupoeconomico.charAt(0) == ' ') {
                return grupoeconomico.charAt(1)
            }
            return grupoeconomico.charAt(0)
        },

        editar() {
            this.dialogEditarGrupoUsuario = true

            this.modelGrupoUsuario = {
                codgrupousuario: this.sGrupoUsuario.detalheGrupoUsuarios.codgrupousuario,
                grupousuario: this.sGrupoUsuario.detalheGrupoUsuarios.grupousuario,
                observacoes: this.sGrupoUsuario.detalheGrupoUsuarios.observacoes
            }

        },

        async salvar() {

            try {
                const ret = await this.sGrupoUsuario.alterarGrupo(this.modelGrupoUsuario)
                if (ret.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Grupo alterado!'
                    })
                    this.dialogEditarGrupoUsuario = false
                }
            } catch (error) {
                console.log(error)
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.response.data.message
                })
            }

        },

        async excluir(codgrupousuario) {

            this.$q.dialog({
                title: 'Excluir Grupo',
                message: 'Tem certeza que deseja excluir esse grupo de usuário?',
                cancel: true,
            }).onOk(async () => {
                try {
                    const ret = await this.sGrupoUsuario.excluir(codgrupousuario)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Grupo excluido'
                    })

                    this.router.push('/grupo-usuarios')
                } catch (error) {
                    this.$q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: error.response.data.message
                    })
                }
            })
        },

        async inativar(codgrupousuario) {
            try {
                const ret = await this.sGrupoUsuario.inativar(codgrupousuario)
                if (ret.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Inativado!'
                    })

                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },

        async ativar(codgrupousuario) {

            try {
                const ret = await this.sGrupoUsuario.ativar(codgrupousuario)
                if (ret.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Ativado!'
                    })

                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.message
                })
            }
        },

    },


    setup() {

        const sGrupoUsuario = grupoUsuarioStore()
        const user = guardaToken()
        const route = useRoute()
        const Documentos = formataDocumetos()
        const dialogEditarGrupoUsuario = ref(false)
        const modelGrupoUsuario = ref({})
        const router = useRouter()
        return {
            sGrupoUsuario,
            user,
            route,
            Documentos,
            modelGrupoUsuario,
            dialogEditarGrupoUsuario,
            router
        }
    },
    async mounted() {
        await this.sGrupoUsuario.getGrupoUsuarioDetalhes(this.route.params.codgrupousuario)

    }
})
</script>

<style scoped></style>