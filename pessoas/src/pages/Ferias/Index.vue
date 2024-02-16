
<template v-if="user.verificaPermissaoUsuario('Recursos Humanos')">
    <MGLayout>

        <template #tituloPagina>
            Férias
        </template>

        <template #content>

            <div class="q-pa-md">
                <q-card bordered>
                    <q-list>
                        <q-item-label header>
                            Programação Férias
                        </q-item-label>
                        <q-toolbar>
                            <q-space />
                            <q-btn flat color="primary" icon="chevron_left" @click="filtroAno(-1)" />
                            {{ ano }}
                            <q-btn flat color="primary" icon="chevron_right" @click="filtroAno(+1)" />
                        </q-toolbar>
                    </q-list>
                </q-card>
            </div>

            <div class="row" v-for="rangeFerias in ferias" v-bind:key="rangeFerias.codferias">
                <div class="col-md-4 col-sm-12 col-xs-12 q-pa-md">
                    <q-card bordered>
                        <q-markup-table separator="vertical">
                            <thead>
                                <q-tr>
                                    <q-th class="text-left">Filial</q-th>
                                    <q-th class="text-left">Cargo</q-th>
                                    <q-th class="text-left">Nome</q-th>
                                </q-tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-left">{{ rangeFerias.filial }}</td>
                                    <td class="text-left">{{ rangeFerias.cargo }}</td>
                                    <td class="text-left">{{ rangeFerias.fantasia }}</td>
                                </tr>
                            </tbody>
                        </q-markup-table>
                    </q-card>
                </div>

                <div class="col-md-8 col-sm-12 col-xs-12 q-pt-md q-pr-md">
                    <q-card bordered>
                        <q-markup-table separator="vertical">
                            <thead>
                                <q-tr>
                                    <q-th class="text-right">Janeiro</q-th>
                                    <q-th class="text-right">Feverei</q-th>
                                    <q-th class="text-right">Março</q-th>&nbsp; &nbsp;
                                    <q-th class="text-right">Abril</q-th>&nbsp; &nbsp; &nbsp;
                                    <q-th class="text-right">Maio</q-th> &nbsp; &nbsp; &nbsp;
                                    <q-th class="text-right">Junho</q-th> &nbsp; &nbsp;
                                    <q-th class="text-right">Julho</q-th> &nbsp; &nbsp;
                                    <q-th class="text-right">Agosto</q-th> &nbsp; &nbsp;
                                    <q-th class="text-right">Setembro</q-th>
                                    <q-th class="text-right">Outubro</q-th>
                                    <q-th class="text-right">Novembro</q-th>
                                    <q-th class="text-right">Dezembro</q-th>
                                </q-tr>
                            </thead>
                        </q-markup-table>
                        <div v-for="feriasS in rangeFerias.ferias" v-bind:key="feriasS.codferias">
                            <q-form @submit="submit(feriasS.codferias, feriasS.codcolaborador)">

                                <q-range :model-value="{ min: `${feriasS.diagozoinicio}`, max: `${feriasS.diagozofim}` }"
                                    v-model:model-value="modelRange" :min="1" :max="max" ref="range"
                                    :left-label-value="datedoRangeMin(modelRange.min)"
                                    :right-label-value="datedoRangeMax(modelRange.max)" label-always switch-label-side
                                    drag-range />

                                <div class="text-right q-pr-md">
                                    <q-btn label="Salvar" type="submit" color="primary" />
                                </div>

                            </q-form>

                            <q-item-label class="text-center">De: {{ Documentos.formataDatasemHr(feriasS.gozoinicio) }}
                                a
                                {{ Documentos.formataDatasemHr(feriasS.gozofim) }}</q-item-label>
                        </div>
                    </q-card>

                </div>
            </div>
        </template>
    </MGLayout>
</template>
  
<script>
import { ref, defineAsyncComponent } from 'vue'
import moment from 'moment'
import { pessoaStore } from 'src/stores/pessoa'
import { formataDocumetos } from 'src/stores/formataDocumentos'
import { useQuasar } from 'quasar'

export default {
    components: {
        MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')),
    },

    methods: {

        async filtroAno(anoFiltro) {
            var anoAtual = moment().year();

            if (anoFiltro == -1) {
                this.ano = (this.ano - 1)
            }

            if (anoFiltro == 1 && this.ano < anoAtual) {
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

        async submit(codferias, codcolaborador) {

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

            const periodoGozo = {
                gozoinicio: converteDataMin,
                gozofim: converteDataMax
            }

            try {
                const ret = await this.sPessoa.updateFeriasRange(codferias, periodoGozo)
                if (ret.data.data) {
                    this.$q.notify({
                        color: 'green-5',
                        textColor: 'white',
                        icon: 'done',
                        message: 'Salvo!'
                    })
                    const i = this.ferias.findIndex(item => item.codcolaborador === codcolaborador)
                    const l = this.ferias[i].ferias.findIndex(item => item.codferias === codferias)
                    this.ferias[i].ferias[l] = ret.data.data
                }
            } catch (error) {
                this.$q.notify({
                    color: 'green-5',
                    textColor: 'white',
                    icon: 'done',
                    message: error.response.data.message
                })
            }

        },

        async atualizaAno(ano) {
            const ret = await this.sPessoa.programacaoFerias({ ano: ano })
            this.ferias = ret.data
            this.ano = ano
            return ret;
        },
    },

    setup() {

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

        return {
            modelRange,
            sPessoa,
            ferias,
            Documentos,
            max,
            ano
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