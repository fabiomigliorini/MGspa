<template>
    <MGLayout back-button>

        <template #botaoVoltar>
            <q-btn flat dense round :to="{ name: 'cargosindex' }" icon="arrow_back" aria-label="Voltar">
            </q-btn>
        </template>

        <template #tituloPagina>
            Cargo
        </template>
        <template #content v-if="user.verificaPermissaoUsuario('Administrador')">
            <q-card bordered class="col-12 q-ma-md">
                <q-item>
                    <q-toolbar class="text-black">
                        <q-avatar color="primary" size="100px" text-color="white" icon="work"></q-avatar>
                        <q-item class="q-subtitle-1 q-pl-md">
                            <q-item-section>
                                <q-item-label header lines="1" class="text-h4 text-weight-bold"
                                    :class="cargo.inativo ? 'text-strike text-red-14' : null">
                                    {{ cargo.cargo }}
                                    <span v-if="cargo.inativo" class="row text-caption text-red-14">
                                        Inativo desde: {{ Documentos.formataData(cargo.inativo) }}
                                    </span>
                                </q-item-label>

                                <q-item-label caption>

                                </q-item-label>
                            </q-item-section>
                        </q-item>
                        <q-btn v-if="user.verificaPermissaoUsuario('Recursos Humanos')" round flat icon="edit"
                            @click="editarCargo(cargo.codcargo, cargo.cargo, cargo.salario, cargo.adicional)" />
                        <q-btn v-if="user.verificaPermissaoUsuario('Recursos Humanos')" round flat icon="delete"
                            @click="excluirCargo(cargo.codcargo)" />

                        <q-btn v-if="user.verificaPermissaoUsuario('Recursos Humanos') && !cargo.inativo" round flat icon="pause"
                            @click="inativar(cargo.codcargo)">
                            <q-tooltip>
                                Inativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn v-if="user.verificaPermissaoUsuario('Recursos Humanos') && cargo.inativo" round flat
                            icon="play_arrow" @click="ativar(cargo.codcargo)">
                            <q-tooltip>
                                Ativar
                            </q-tooltip>
                        </q-btn>

                        <q-btn round flat icon="info">
                            <q-tooltip>
                                <q-item-label class="row">Criado por {{ cargo.usuariocriacao }} em {{
                                    Documentos.formataData(cargo.criacao)
                                    }}</q-item-label>
                                <q-item-label class="row">Alterado por {{ cargo.usuarioalteracao }} em {{
                                    Documentos.formataData(cargo.alteracao) }}</q-item-label>
                            </q-tooltip>
                        </q-btn>
                    </q-toolbar>
                </q-item>
            </q-card>


            <!-- CARD DAS PESSOAS DO CARGO -->
            <div class="row q-col-gutter-md q-pa-md">
                <div class="col-md-3 col-sm-6 col-xs-12 col-lg-3 col-xl-2" v-for="pessoaCargo in pessoasCargo"
                    v-bind:key="pessoaCargo.codcolaboradorcargo">
                    <q-card>
                        <q-list>
                            <q-item :to="'/pessoa/' + pessoaCargo.codpessoa" clickable>
                                <q-item-section avatar>
                                    <q-avatar color="primary" class="q-my-md" size="35px" text-color="white">
                                        {{ primeiraLetra(pessoaCargo.fantasia) }}
                                    </q-avatar>
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="pessoaCargo.inativo ? 'text-strike text-red-14' : null">
                                        {{ pessoaCargo.fantasia }}
                                    </q-item-label>
                                </q-item-section>

                            </q-item>

                            <q-item>
                                <q-item-section avatar>
                                    <q-avatar icon="corporate_fare" color="grey-2" text-color="blue" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="pessoaCargo.inativo ? 'text-strike text-red-14' : null">
                                        {{ pessoaCargo.filial }}
                                    </q-item-label>
                                    <q-item-label caption>
                                        FIlial
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section avatar>
                                    <q-avatar icon="event" color="grey-2" text-color="blue" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="pessoaCargo.inativo ? 'text-strike text-red-14' : null">
                                        {{ Documentos.formataDatasemHr(pessoaCargo.inicio) }}
                                    </q-item-label>
                                    <q-item-label caption>
                                        Início
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                        </q-list>
                    </q-card>
                </div>
            </div>
            <!-- Dialog novo Cargo / Editar Cargo -->
            <q-dialog v-model="dialogEditarCargo">
                <q-card style="min-width: 350px">
                    <q-form @submit="salvarCargo()">
                        <q-card-section>
                            <div class="text-h6">Editar Cargo</div>

                        </q-card-section>
                        <q-card-section>

                            <q-input outlined v-model="modelEditarCargo.cargo" label="Cargo" :rules="[
                                val => val && val.length > 0 || 'Cargo obrigatório'
                            ]" />

                            <q-input outlined v-model="modelEditarCargo.salario" label="Salario" type="number">
                                <template v-slot:prepend>
                                    R$
                                </template>
                            </q-input>

                            <q-input outlined v-model="modelEditarCargo.adicional" type="number" class="q-pt-md"
                                label="Adicional">
                                <template v-slot:append>
                                    %
                                </template>
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

export default defineComponent({
    name: "cargoView",

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

        editarCargo(codcargo, cargo, salario, adicional) {
            this.dialogEditarCargo = true

            this.modelEditarCargo = {
                codcargo: codcargo,
                cargo: cargo,
                salario: salario,
                adicional: adicional
            }

        },

        async salvarCargo() {

            try {
                const ret = await this.sPessoa.alterarCargo(this.modelEditarCargo)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Cargo alterado!'
                    })
                    this.cargo = ret.data.data
                    this.dialogEditarCargo = false
                }
            } catch (error) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: error.response.data.message
                })
            }

        },

        async excluirCargo(codcargo) {

            this.$q.dialog({
                title: 'Excluir Cargo',
                message: 'Tem certeza que deseja excluir esse cargo?',
                cancel: true,
            }).onOk(async () => {
                try {
                    const ret = await this.sPessoa.excluirCargo(codcargo)
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Cargo excluido'
                    })

                    this.router.push('/cargo')
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

        async inativar(codcargo) {
            try {
                const ret = await this.sPessoa.cargoInativar(codcargo)
                if (ret.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Inativado!'
                    })
                    this.cargo = ret.data.data
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

        async ativar(codcargo) {

            try {
                const ret = await this.sPessoa.cargoAtivar(codcargo)
                if (ret.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Ativado!'
                    })
                    this.cargo = ret.data.data
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

        const pessoasCargo = ref([
        ])
        const sPessoa = pessoaStore()
        const user = guardaToken()
        const route = useRoute()
        const Documentos = formataDocumetos()
        const cargo = ref([])
        const dialogEditarCargo = ref(false)
        const modelEditarCargo = ref({})
        const router = useRouter()
        return {
            pessoasCargo,
            sPessoa,
            user,
            route,
            cargo,
            Documentos,
            modelEditarCargo,
            dialogEditarCargo,
            router
        }
    },
    async mounted() {
        const ret = await this.sPessoa.pessoasColaboradorCargo(this.route.params.id)
        this.pessoasCargo = ret.data.data.pessoasCargo
        this.cargo = ret.data.data.cargoS

    }
})
</script>
  
<style scoped></style>
  