
<template>
    <MGLayout>

        <template #tituloPagina>
            Férias
        </template>

        <template #content v-if="user.verificaPermissaoUsuario('Recursos Humanos')">

            <div class="q-pa-md">
                <q-card bordered>
                    <!-- <pre>
                    {{ ferias }}
                    </pre> -->
                    <q-table title="Programação de férias" :filter="filter" :rows="ferias" :columns="columns"
                        no-data-label="Nenhum titulo encontrado" :separator="separator" emit-value>

                        <template v-slot:top-right>
                            <q-btn flat color="primary" icon="chevron_left" @click="filtroAno(-1)" />
                            {{ ano }}
                            <q-btn flat color="primary" icon="chevron_right" @click="filtroAno(+1)" />

                            <q-input v-if="show_filter" outlined dense debounce="300" class="q-pa-sm" v-model="filter"
                                placeholder="Pesquisar">
                                <template v-slot:append>
                                    <q-icon name="search" />
                                </template>
                            </q-input>
                            <q-btn class="q-ml-sm" icon="filter_list" @click="show_filter = !show_filter" flat />
                        </template>

                        <template v-slot:body="ferias">
                            <q-tr :props="ferias">
                                <q-td key="filial" :props="ferias">
                                    {{ ferias.row.filial }}
                                </q-td>
                                <q-td key="cargo" :props="ferias">
                                    {{ ferias.row.cargo }}
                                </q-td>

                                <q-td key="fantasia" :props="ferias">
                                    {{ ferias.row.fantasia }}
                                </q-td>
                                <q-td key="janeiro" colspan="12" :props="ferias">

                                    <div v-for="feriasS in ferias.row.ferias" v-bind:key="feriasS.codferias">

                                        <q-range
                                            :model-value="{ min: `${feriasS.diagozoinicio}`, max: `${feriasS.diagozofim}` }"
                                            v-model:model-value="modelRange[feriasS.codferias]" :min="1" :max="max"
                                            ref="range" :left-label-value="datedoRangeMin(modelRange[feriasS.codferias])"
                                            :right-label-value="datedoRangeMax(modelRange[feriasS.codferias])" label-always
                                            switch-label-side drag-range
                                            />
                                    </div>

                                    {{ modelRange }}
                                </q-td>
                                <q-td key="data" :props="ferias">
                                    <div v-for="feriasS in ferias.row.ferias" v-bind:key="feriasS.codferias">

                                        {{ Documentos.formataDatasemHr(feriasS.gozoinicio) }}
                                        a
                                        {{ Documentos.formataDatasemHr(feriasS.gozofim) }}
                                    </div>

                                </q-td>
                            </q-tr>
                        </template>
                    </q-table>
                </q-card>
            </div>

            <div class="text-right q-pr-md">
                <q-btn label="Salvar" @click="submit()" color="primary" />
            </div>

        </template>
        <!-- Template Não Autorizado -->
        <template #content v-else>
            <nao-autorizado></nao-autorizado>
        </template>
    </MGLayout>
</template>
  
<script>
import { ref, defineAsyncComponent } from 'vue'
import moment from 'moment'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { useQuasar } from 'quasar'
import { guardaToken } from 'src/stores'
import { useRoute, useRouter } from 'vue-router'

export default {
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
        NaoAutorizado: defineAsyncComponent(() => import('components/NaoAutorizado.vue')),
    },


    methods: {

        teste(ferias, min, max) {

       


            const acumulaRangeFerias = []

            var params = Object.assign(ferias, min, max)

            acumulaRangeFerias.push(params)




        },

        async filtroAno(anoFiltro) {
            var anoAtual = moment().year();

            if (anoFiltro == -1) {
                this.ano = (this.ano - 1)
            }

            if (anoFiltro == 1) {
                this.ano = (this.ano + 1)
            }

            try {
                const ret = await this.atualizaAno(this.ano)
                if (ret.data.length == 0) {
                    this.$q.notify({
                        color: 'red-5',
                        textColor: 'white',
                        icon: 'error',
                        message: 'Nenhuma programação de férias encontrada!'
                    })
                }

            } catch (error) {
                console.log(error)
            }

        },

        datedoRangeMin(min) {
            if (min == undefined) {
                return '-'
            }
            if (min && min.min) {
                min = min.min
            }

            if (min == 0) {
                return '-'
            }

            var ano = (new Date()).getFullYear();
            var inicio = new Date("1/1/" + ano);
            var primeiroDia = moment(inicio.valueOf());
            var range = moment(primeiroDia).date(min)
            var converteData = moment(range).format('DD/MM/YYYY')
            return converteData
        },

        datedoRangeMax(max) {
            if (max == undefined) {
                return '-'
            }
            if (max && max.max) {
                max = max.max
            }

            if (max == 0) {
                return '-'
            }

            var ano = (new Date()).getFullYear();
            var inicio = new Date("1/1/" + ano);
            var primeiroDia = moment(inicio.valueOf());
            var range = moment(primeiroDia).date(max)
            var converteData = moment(range).format('DD/MM/YYYY')
            return converteData

        },

        async submit() {
            // console.log(this.$refs.range)

            // this.modelRange.forEach(item => {
            //     console.log(item)
            // });

            // console.log(this.modelRange)
            // const keys = Object.keys(this.modelRange)
            // const valores = Object.values(this.modelRange)
            // console.log(keys)

            delete this.modelRange.max;
            delete this.modelRange.min;

            console.log(this.modelRange)


            var keys = Object.keys(this.modelRange)

            var values = Object.values(this.modelRange)
            let model = []

            keys.forEach(element => {

                values.forEach(elvalue => {
                    model.push({
                        codferias: element,
                        min: elvalue.min,
                        max: elvalue.max
                    })

                });
            });
            console.log(model)


            // let feriasAcumula = []


            // keys.forEach(element => {

            //     valores.forEach(value => {

            //         feriasAcumula.push({
            //             codferias: element,
            //             min: value.min,
            //             max: value.max
            //         })
            //     });
            //     console.log(feriasAcumula)

            // });


            if (this.modelRange.max - this.modelRange.min > 30) {
                this.$q.notify({
                    color: 'red-5',
                    textColor: 'white',
                    icon: 'error',
                    message: 'A férias pode ser no máximo 30 dias!'
                })
                return
            }

            var ano = (new Date()).getFullYear();
            var inicio = new Date("1/1/" + ano);
            var primeiroDia = moment(inicio.valueOf());


            var rangeMin = moment(primeiroDia).date(this.modelRange.min)

            var converteDataMin = moment(rangeMin).format('YYYY-MM-DD')
            var rangeMax = moment(primeiroDia).date(this.modelRange.max)
            var converteDataMax = moment(rangeMax).format('YYYY-MM-DD')

            // this.modelRange


            const periodoGozo = {
                gozoinicio: converteDataMin,
                gozofim: converteDataMax
            }

            // try {
            //     const ret = await this.sPessoa.updateFeriasRange(codferias, periodoGozo)
            //     if (ret.data.data) {
            //         this.$q.notify({
            //             color: 'green-5',
            //             textColor: 'white',
            //             icon: 'done',
            //             message: 'Salvo!'
            //         })
            //         const i = this.ferias.findIndex(item => item.codcolaborador === codcolaborador)
            //         const l = this.ferias[i].ferias.findIndex(item => item.codferias === codferias)
            //         this.ferias[i].ferias[l] = ret.data.data
            //     }
            // } catch (error) {
            //     this.$q.notify({
            //         color: 'green-5',
            //         textColor: 'white',
            //         icon: 'done',
            //         message: error.response.data.message
            //     })
            // }

        },

        async atualizaAno(ano) {
            if (ano == null) {
                ano = this.route.params.ano
            }

            const ret = await this.sPessoa.programacaoFerias(ano)
            this.ferias = ret.data
            this.router.push('/ferias/' + ano)
            this.ano = ano
            return ret;
        },
    },

    setup(props, ctx) {

        const modelRange = ref({
            min: 0,
            max: 0
        })
        const sPessoa = pessoaStore()
        const ferias = ref([])
        const Documentos = formataDocumetos()
        const $q = useQuasar()
        const max = ref([])
        const ano = ref([])
        const user = guardaToken()
        const filter = ref('')
        const show_filter = ref(true)
        const separator = ref('cell')
        const range = ref(null)
        const route = useRoute()
        const router = useRouter()


        const emit = async (ret, codferias) => {
            console.log(ret)
            console.log(codferias)
            // const item = range.value.find(item => item.value)
            // console.log(item)

            // range.value[0].modelValue.min = ret.min
            // range.value[0].modelValue.max = ret.max
            console.log(range.value[0])
            return ctx.emit('update:modelRange', ret)
        }

        const columns = [
            { name: 'filial', label: 'Filial', field: 'filial', align: 'top-left', sortable: true },
            { name: 'cargo', label: 'Cargo', field: 'cargo', align: 'top-left', sortable: true },
            { name: 'fantasia', label: 'Nome', field: 'fantasia', align: 'top-left', sortable: true },

            { name: 'janeiro', label: 'Janeiro', field: 'janeiro', align: 'top-left', sortable: true },
            { name: 'Fevereiro', label: 'Fevereiro', field: 'fevereiro', align: 'top-left', sortable: true },
            { name: 'Março', label: 'Março', field: 'marco', align: 'top-left', sortable: true },
            { name: 'Abril', label: 'Abril', field: 'abril', align: 'top-left', sortable: true },
            { name: 'Maio', label: 'Maio', field: 'maio', align: 'top-left', sortable: true },
            { name: 'Junho', label: 'Junho', field: 'junho', align: 'top-left', sortable: true },
            { name: 'Julho', label: 'Julho', field: 'julho', align: 'top-left', sortable: true },
            { name: 'Agosto', label: 'Agosto', field: 'agosto', align: 'top-left', sortable: true },
            { name: 'Setembro', label: 'Setembro', field: 'setembro', align: 'top-left', sortable: true },
            { name: 'Outubro', label: 'Outubro', field: 'outubro', align: 'top-left', sortable: true },
            { name: 'Novembro', label: 'Novembro', field: 'novembro', align: 'top-left', sortable: true },
            { name: 'Dezembro', label: 'Dezembro', field: 'dezembro', align: 'top-left', sortable: true },
            { name: 'data', label: 'Data', field: 'data', align: 'top-left', sortable: true },
        ];

        return {
            modelRange,
            sPessoa,
            ferias,
            Documentos,
            max,
            ano,
            user,
            filter,
            show_filter,
            separator,
            columns,
            emit,
            range,
            route,
            router
        }
    },
    async mounted() {
        var year = moment().year()
        this.atualizaAno(year)

        var bissexto = moment([year]).isLeapYear()
        if (bissexto) {
            this.max = 366
        } else {
            this.max = 365
        }
    }
}
</script>