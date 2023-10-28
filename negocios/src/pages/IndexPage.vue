<template>
  <q-page class="flex flex-center">
    <!-- <img
      alt="Quasar logo"
      src="~assets/quasar-logo-vertical.svg"
      style="width: 200px; height: 200px"
    > -->
    <!-- {{store.listagemPdv.length}} -->
      <div class="q-pa-md">
        <q-table
          title="Produtos"
          :rows="store.listagemPdv"
          row-key="codprodutobarra"
        />
      </div>
  </q-page>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue'
import { api } from 'boot/axios'
import { produtosStore } from 'stores/produtos';

export default defineComponent({
  name: 'IndexPage',
  setup () {
    onMounted(() => {
      // getProdutos()
    })
    const store = produtosStore()
    const getProdutos = async () => {
      try {
        const limite = 10000
        var iteracoes = 0
        var codprodutobarra = 0
        store.listagemPdv = []
        do {
          var { data } = await api.get('/api/v1/produto/listagem-pdv', {
            params: {
              codprodutobarra,
              limite
            }
          })
          store.listagemPdv = [].concat(store.listagemPdv, data)
          // store.listagemPdv.concat(data);
          var codprodutobarra = data.slice(-1)[0].codprodutobarra
          console.log(codprodutobarra)
          console.log(store.listagemPdv.length)
          iteracoes++
        } while ((data.length >= limite) && (iteracoes <= 1000) )
        console.log(store.listagemPdv)
      } catch (e) {
        console.log(e.message)
      }
    }
    return {
      store
    }
  }
})
</script>
