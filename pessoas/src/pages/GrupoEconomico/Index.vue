<template>
    <MGLayout drawer>
        <template #tituloPagina>
            Grupo Econômico
        </template>
        <template #content>
            <q-infinite-scroll @load="scrollGrupo" :disable="loading">
                <div class="row q-pa-md q-col-gutter-md">
                    <div class="col-md-4 col-sm-6 col-xs-12 col-lg-3 col-xl-2" v-for="grupoEconomico in sPessoa.arrGrupos"
                        v-bind:key="grupoEconomico.codgrupoeconomico">
                        <q-card v-ripple>
                            <q-item :to="'/grupoeconomico/' + grupoEconomico.codgrupoeconomico" clickable>
                                <q-item-section avatar>
                                    <q-avatar color="primary" class="q-my-md" size="35px" text-color="white">
                                        {{ primeiraLetra(grupoEconomico.grupoeconomico) }}
                                    </q-avatar>
                                </q-item-section>
                                <q-item-section>
                                    <q-item-label :class="grupoEconomico.inativo ? 'text-strike text-red-14' : null">
                                        {{ grupoEconomico.grupoeconomico }}
                                    </q-item-label>
                                    <q-item-label caption class="ellipsis">
                                        {{ grupoEconomico.observacoes }}
                                    </q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-card>
                    </div>
                </div>
            </q-infinite-scroll>

            <q-page-sticky position="bottom-right" :offset="[18, 18]" v-if="user.verificaPermissaoUsuario('Financeiro')">
                <q-fab icon="add" direction="up" color="accent" @click="novoGrupo()">
                </q-fab>
            </q-page-sticky>
        </template>

        <template #drawer>
            <div class="q-pa-none q-pt-sm">
                <q-card flat>
                    <q-list>
                        <q-item-label header>
                            Filtro Grupo Econômico
                            <q-btn icon="replay" @click="buscarGrupos()" flat round no-caps />
                        </q-item-label>
                    </q-list>
                </q-card>
                <div class="q-pa-md q-gutter-md">
                    <q-input outlined v-model="sPessoa.filtroGrupoPesquisa.nome" label="Nome" @change="buscarGrupos()" />
                </div>
            </div>
        </template>
    </MGLayout>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, ref, onMounted } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import { GrupoEconomicoStore } from 'stores/GrupoEconomico'
import { guardaToken } from 'src/stores'


export default defineComponent({
    name: "GrupoEconomico",
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
    },

    methods: {
        primeiraLetra(grupoeconomico) {
            if (grupoeconomico.charAt(0) == ' ') {
                return grupoeconomico.charAt(1)
            }
            return grupoeconomico.charAt(0)
        },

        novoGrupo() {
            this.$q.dialog({
                title: 'Novo Grupo Econômico',
                prompt: {
                    model: '',
                    type: 'text',
                },
                cancel: true,
            }).onOk(async grupoeconomico => {
                const ret = await this.sPessoa.novoGrupoEconomico(grupoeconomico)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Grupo Criado!'
                    })

                    this.$router.push('/grupoeconomico/' + ret.data.data.codgrupoeconomico)
                }
            })
        }
    },

    setup() {

        const $q = useQuasar()
        const route = useRoute()
        const sPessoa = GrupoEconomicoStore()
        const arrGrupos = ref([])
        const loading = ref(true)
        const user = guardaToken()

        const buscarGrupos = debounce(async () => {
            $q.loadingBar.start()
            sPessoa.filtroGrupoPesquisa.page = 1;
            try {
                const ret = await sPessoa.buscaGrupos()
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
            if (sPessoa.arrGrupos.length == 0) {
                buscarGrupos();
            }

        })
        return {
            sPessoa,
            arrGrupos,
            loading,
            buscarGrupos,
            user,
            async scrollGrupo(index, done) {
                loading.value = true;
                $q.loadingBar.start()
                sPessoa.filtroGrupoPesquisa.page++;
                const ret = await sPessoa.buscaGrupos();
                loading.value = false
                $q.loadingBar.stop()
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
  