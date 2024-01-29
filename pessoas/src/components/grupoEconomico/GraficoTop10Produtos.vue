<template>
    <q-card>
        <q-item-label header>
            Top 10 Produtos
        </q-item-label>

        <div class="row">
            <div class="q-pl-md col-md-6">
                <select-pessoas label="Filtrar pessoa" v-model="filtroPessoa.codpessoa"></select-pessoas>
            </div>

            <div class="col-md-6 q-pl-md q-pr-md">
                <q-select outlined v-model="filtroPessoa.desde" emit-value map-options :options="opcoesDesde" label="Data" dense />
            </div>
        </div>
        <canvas id="graficoTopProdutos" height="200" width="200">
        </canvas>
    </q-card>
</template>
  
<script>
import { defineComponent, defineAsyncComponent, watch } from 'vue'
import { ref } from 'vue'
import Chart from 'chart.js/auto'
import { GrupoEconomicoStore } from 'src/stores/GrupoEconomico';
import { formataDocumetos } from 'src/stores/formataDocumentos';
import moment from 'moment'

export default defineComponent({
    name: "GraficoTop10Produtos",

    components: {
        SelectPessoas: defineAsyncComponent(() => import('components/pessoa/SelectPessoas.vue')),

    },

    methods: {

        async montaGrafico() {
            let valortotal = []
            let label = []
            this.produtos = []

            const ret = await this.sGrupoEconomico.getTopProdutos(this.$route.params.id, this.filtroPessoa);

            ret.data.forEach(valores => {
                valortotal.push(valores.valortotal)
                label.push(valores.produto)
                this.produtos.push(valores.produto)
            });

            const data = {
                labels: this.produtos,
                datasets: [
                    {
                        label: 'Valor Total',
                        data: null,
                    },
                ]
            };

            const config = {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                        }
                    }
                },
            };

            data.datasets[0].data = valortotal
            this.graficoTopProdutos = new Chart(document.getElementById('graficoTopProdutos'), config)
        },

        atualizaGrafico() {
            this.graficoTopProdutos.destroy()
            this.montaGrafico()
        },


        async filtroDesde() {
            this.opcoesDesde = [
                {
                    label: 'Este Ano',
                    value: moment().startOf('year').format('YYYY-MM-DD')
                },
                {
                    label: '1 Ano',
                    value: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD')
                },
                {
                    label: '2 Anos',
                    value: moment().subtract(2, 'year').startOf('month').format('YYYY-MM-DD')
                },
                {
                    label: 'Tudo',
                    value: null
                },
            ]
        },
    },

    data() {

        watch(
            () => this.filtroPessoa,
            () => this.atualizaGrafico(),
            { deep: true }
        );

        return {
            model: null,
        }
    },

    async mounted() {
        this.filtroDesde()
        this.montaGrafico()
    },

    setup() {

        const sGrupoEconomico = GrupoEconomicoStore()
        const filtroPessoa = ref({
            desde: moment().subtract(1, 'year').startOf('month').format('YYYY-MM-DD')
        })
        const graficoTopProdutos = ref('')
        const produtos = ref([])
        const Documentos = formataDocumetos()
        const opcoesDesde = ref([])


        return {
            sGrupoEconomico,
            filtroPessoa,
            graficoTopProdutos,
            opcoesDesde,
            produtos,
            Documentos
        }
    },

})
</script>
  