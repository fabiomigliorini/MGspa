<template>
    <q-input outlined v-model="model" label="Inscrição Estadual" :mask="MascarasIe" class="q-mb-md" unmasked-value />
</template>
  
<script>
import { defineComponent } from 'vue'
import { ref } from 'vue'
import { pessoaStore } from 'src/stores/pessoa'

export default defineComponent({
    name: "InputIe",

    data() {
        return {
            model: null,
        }
    },

    async mounted() {
        const selectPagamento = await this.sPessoa.selectPagamento()
        this.selectFormaPagamento = selectPagamento.data


        var formatosIe = [
            { 'AC': '##.###.###/###-##' },
            { 'AL': '#########' },
            { 'AP': '#########' },
            { 'AM': '##.###.###-#' },
            { 'BA': '#######-##' },
            { 'CE': '########-#' },
            { 'DF': '###########-##' },
            { 'ES': '###.###.##-#' },
            { 'GO': '##.###.###-#' },
            { 'MA': '#########' },
            { 'MT': '##.###.###-#' },
            { 'MS': '#########' },
            { 'MG': '###.###.###/####' },
            { 'PA': '##-######-#' },
            { 'PB': '########-#' },
            { 'PR': '########-##' },
            { 'PE': '##.#.###.#######-#' },
            { 'PI': '#########' },
            { 'RJ': '##.###.##-#' },
            { 'RN': '##.###.###-#' },
            { 'RS': '###-#######' },
            { 'RO': '#############-#' },
            { 'RR': '########-#' },
            { 'SC': '###.###.###' },
            { 'SP': '###.###.###.###' },
            { 'SE': '#########-#' },
            { 'TO': '###########' },
        ]

        if (!this.sPessoa.item.PessoaEnderecoS) {
            return '##.###.###-#'
        }

        const end = this.sPessoa.item.PessoaEnderecoS.filter(end => end.nfe == true);
        if (end.length == 0) {
            return '##.###.###-#'
        }

        const uf = end[0].uf

        formatosIe.forEach(formatoIe => {
            if (formatoIe[uf] !== undefined) {
                this.MascarasIe = formatoIe[uf]
                return
            }
        });
    },

    setup() {
        const selectFormaPagamento = ref([])
        const sPessoa = pessoaStore()
        const MascarasIe = ref('')
        return {
            selectFormaPagamento,
            sPessoa,
            MascarasIe,
        }
    },

})
</script>
  
<style scoped></style>
  