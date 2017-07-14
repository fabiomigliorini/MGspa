<template>
  <v-layout row wrap>
    <template v-for="produto in produtos">
        <v-flex lg3 md4 sm6 xs12 style="height:100%">
          <v-card hover >
            <v-card-media class="" height="200px" :src="produto.imagem" v-if="produto.imagem">
            </v-card-media>
            <v-card-title>
              <h3 class="headline mb-0" style="width: 100%">
                {{produto.produto}}
              </h3>
              <span  v-if="produto.variacao" style="width: 100%">
                {{produto.variacao}}
              </span>
              <span class="grey--text text--darken-2">
                R$ {{ parseFloat(produto.preco).toLocaleString('pt-BR', { maximumFractionDigits: 2, minimumFractionDigits: 2 }) }}
              </span>
            </v-card-title>
            <v-card-text class="pt-0">
              <small class="grey--text text--darken-2">

                #{{ parseInt(produto.codproduto).toLocaleString('pt-BR', { minimumIntegerDigits: 6, useGrouping: false }) }}
                Referencia {{ produto.referencia }}

                <br>

                Saldo:
                <span class="red--text">
                  {{ parseFloat(produto.saldoquantidade).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }}
                  {{ produto.unidademedida }}
                </span>

                ({{ parseInt(produto.estoqueminimo).toLocaleString('pt-BR') }}<v-icon>arrow_downward</v-icon><v-icon>arrow_upward</v-icon>{{ parseInt(produto.estoquemaximo).toLocaleString('pt-BR') }})
                <span v-if="produto.dias">
                  /
                  <span class="red--text">
                    {{ parseFloat(produto.dias).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }} dias
                  </span>
                </span>

                <br>

                <span v-if="produto.saldovalor >0">
                  Valor do Estoque: R$ {{ parseFloat(produto.saldovalor).toLocaleString('pt-BR', { minimumFractionDigits:2, maximumFractionDigits: 2 }) }}
                  <br>
                </span>


                <span v-if="produto.quantidadeultimacompra">
                  Comprado
                  {{ moment(produto.dataultimacompra).fromNow() }}
                  {{ parseFloat(produto.quantidadeultimacompra).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) }}
                  {{ produto.unidademedida }}
                  por R$
                  {{ parseFloat(produto.custoultimacompra).toLocaleString('pt-BR', { maximumFractionDigits: 2, minimumFractionDigits: 2 }) }}
                </span>

              </small>
            </v-card-text>
          </v-card>
        </v-flex>
    </template>
  </v-layout>
</template>

<script>

export default {
  name: 'detalhe-produtos',
  props: {
    produtos: {
    }
  },
  mounted () {
    console.log(this.produtos)
  }

}
</script>

<style scoped>
</style>
