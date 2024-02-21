<template>
    <MGLayout drawer>
        <template #tituloPagina>
            Cargos
        </template>
        <template #content v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
            <div class="row q-pa-md q-col-gutter-md">
                <div class="col-md-3 col-sm-6 col-xs-12 col-lg-3 col-xl-2" v-for="cargo in cargos"
                    v-bind:key="cargo.codcargo">
                    <q-card>
                        <q-list>
                            <q-item :to="'/cargo/' + cargo.codcargo" clickable>
                                <q-item-section avatar>
                                    <q-avatar color="primary" class="q-my-md" size="35px" text-color="white">
                                        {{ primeiraLetra(cargo.cargo) }}
                                    </q-avatar>
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="cargo.inativo ? 'text-strike text-red-14' : null">
                                        {{ cargo.cargo }}
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section avatar>
                                    <q-avatar icon="payments" color="grey-2" text-color="blue" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="cargo.inativo ? 'text-strike text-red-14' : null">
                                        {{ new Intl.NumberFormat('pt-BR', {
                                            style: 'currency', currency: 'BRL'
                                        }).format(cargo.salario) }}
                                    </q-item-label>
                                    <q-item-label caption>
                                        Salário
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-item>
                                <q-item-section avatar>
                                    <q-avatar icon="payments" color="grey-2" text-color="blue" />
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="cargo.inativo ? 'text-strike text-red-14' : null">
                                        <span v-if="!cargo.adicional">0 %</span>
                                        <span v-if="cargo.adicional">{{ cargo.adicional }} %</span>
                                    </q-item-label>
                                    <q-item-label caption>
                                        Adicional
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                        </q-list>
                    </q-card>
                </div>
            </div>

            <!-- Dialog novo Cargo / Editar Cargo -->
            <q-dialog v-model="dialogNovoCargo">
                <q-card style="min-width: 350px">
                    <q-form @submit="editCargo == true ? salvarCargo() : novoCargo()">
                        <q-card-section>
                            <div v-if="editCargo == true" class="text-h6">Editar Cargo</div>
                            <div v-else class="text-h6">Novo Cargo</div>

                        </q-card-section>
                        <q-card-section>

                            <q-input outlined v-model="modelNovoCargo.cargo" label="Cargo" :rules="[
                                val => val && val.length > 0 || 'Cargo obrigatório'
                            ]" />

                            <q-input outlined v-model="modelNovoCargo.salario" label="Salario" type="number">
                                <template v-slot:prepend>
                                    R$
                                </template>
                            </q-input>

                            <q-input outlined v-model="modelNovoCargo.adicional" type="number" mask="###" class="q-pt-md"
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

            <q-page-sticky position="bottom-right" :offset="[18, 18]"
                v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
                <q-fab icon="add" direction="up" color="accent"
                    @click="dialogNovoCargo = true, editCargo = false, modelNovoCargo = {}">
                </q-fab>
            </q-page-sticky>
        </template>
        <!-- Template Não Autorizado -->
        <template #content v-else>
            <nao-autorizado></nao-autorizado>
        </template>

        <template #drawer v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
            <div class="q-pa-none q-pt-sm">
                <q-card flat>
                    <q-list>
                        <q-item-label header>
                            Filtro Cargo
                            <q-btn icon="replay" @click="buscarCargos()" flat round no-caps />
                        </q-item-label>
                    </q-list>
                </q-card>
                <div class="q-pa-md q-gutter-md">
                    <q-input outlined v-model="filtroPesquisaCargo.cargo" label="Cargo" @change="buscarCargos()" />

                    <q-input outlined v-model="filtroPesquisaCargo.de" label="De" @change="buscarCargos()">
                        <template v-slot:prepend>
                            R$
                        </template>
                    </q-input>

                    <q-input outlined v-model="filtroPesquisaCargo.ate" label="Até" @change="buscarCargos()">
                        <template v-slot:prepend>
                            R$
                        </template>
                    </q-input>

                </div>
            </div>
        </template>
    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, ref, onMounted } from 'vue'
import { guardaToken } from 'src/stores'
import { pessoaStore } from 'stores/pessoa'
import { useQuasar, debounce } from 'quasar'


export default defineComponent({
    name: "Cargos",
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
        NaoAutorizado: defineAsyncComponent(() => import('components/NaoAutorizado.vue')),
    },

    methods: {

        primeiraLetra(grupoeconomico) {
            if (grupoeconomico.charAt(0) == ' ') {
                return grupoeconomico.charAt(1)
            }
            return grupoeconomico.charAt(0)
        },

        async novoCargo() {
            this.dialogNovoCargo = true
            if (this.modelNovoCargo.cargo) {
                try {
                    const ret = await this.sPessoa.novoCargo(this.modelNovoCargo)
                    if (ret.data.data) {
                        this.$q.notify({
                            color: 'green-5',
                            textColor: 'white',
                            icon: 'done',
                            message: 'Cargo criado!'
                        })
                        this.dialogNovoCargo = false
                        this.modelNovoCargo = {}
                        this.cargos.push(ret.data.data)
                    }
                } catch (error) {
                    this.$q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: error.response.data.message
                    })
                }
            }

        },
    },

    setup() {
        const user = guardaToken()
        const sPessoa = pessoaStore()
        const cargos = ref([])
        const dialogNovoCargo = ref(false)
        const modelNovoCargo = ref({})
        const editCargo = ref(false)
        const filtroPesquisaCargo = ref({})
        const $q = useQuasar()

        const buscarCargos = debounce(async () => {
            $q.loadingBar.start()
            try {
                const ret = await sPessoa.getCargos(filtroPesquisaCargo.value)
                cargos.value = ret.data.data
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
                console.log(error)
                $q.loadingBar.stop()
            }
        }, 500)


        return {
            user,
            sPessoa,
            cargos,
            dialogNovoCargo,
            buscarCargos,
            modelNovoCargo,
            editCargo,
            filtroPesquisaCargo
        }
    },

    async mounted() {
        const ret = await this.sPessoa.getCargos()
        this.cargos = ret.data.data
    }
})
</script>
  
<style scoped></style>
  