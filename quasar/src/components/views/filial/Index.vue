<template>
  <mg-layout drawer>

    <template slot="title">
        Filiais
    </template>

    <div slot="drawer">

      <form>
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="search"/>
          </q-item-section>
          <q-item-section>
            <q-input v-model="filter.filial" label="Descrição"/>
          </q-item-section>
        </q-item>

        <q-item-label class="text-subtitle1 q-pa-sm">Ativos</q-item-label>
        <!-- Filtra Ativos -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="thumb_up"/>
          </q-item-section>
          <q-item-section>
            Ativos
          </q-item-section>
          <q-item-section side>
            <q-radio v-model="filter.inativo" :val="1"/>
          </q-item-section>
        </q-item>

        <!-- Filtra Inativos -->
        <q-item dense>
          <q-item-section>
            <q-icon name="thumb_down"/>
          </q-item-section>
          <q-item-section>
            Inativos
          </q-item-section>
          <q-item-section side>
            <q-radio v-model="filter.inativo" :val="2" />
          </q-item-section>
        </q-item>

        <!-- Filtra Ativos e Inativos -->
        <q-item dense>
          <q-item-section avatar>
            <q-icon name="thumbs_up_down"/>
          </q-item-section>
          <q-item-section>
            Ativos e Inativos
          </q-item-section>
          <q-item-section side>
            <q-radio v-model="filter.inativo" :val="9" />
          </q-item-section>
        </q-item>

      </form>
    </div>
    <div slot="content">
        <p v-for="item in data">
          {{ item.filial }}
        </p>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import MgNoData from '../../utils/MgNoData'
import { debounce } from 'quasar'
export default {
  name: 'grupo-usuario',
  components: {
    MgLayout,
    MgAutor,
    MgNoData
  },
  data () {
    return {
      data: [],
      page: 1,
      filter: {},
      loading: true,
      erros: false,
      teste: 'Aqui é o teste!!!'
    }
  },
  watch: {
    filter: {
      handler: function (val, oldVal) {
        this.page = 1;
        this.loadData(false, null)
      },
      deep: true
    }
  },
  methods: {
    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++;
      this.loadData(true, done)
    },

    // carrega registros da api
    loadData: debounce(function (concat, done) {

      // inicializa variaveis
      var vm = this;

      // salva no Vuex filtro da filial
      vm.$store.commit('filtroFilial/updateFiltroFilial', vm.filter);
      var params = vm.filter;
      params.page = this.page;
      params.sort = 'filial';
      params.fields = 'filial,codfilial,inativo';
      vm.loading = true;

      // faz chamada api
      vm.$axios.get('filial', {params}).then(response => {
        // Se for para concatenar, senao inicializa
        if (concat) {
          vm.data = vm.data.concat(response.data.data)
        }
        else {
          vm.data = response.data.data
        }

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (response.data.data.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // desmarca flag de carregando
        this.loading = false;

        // Executa done do scroll infinito
        if (done) {
          done()
        }
      })
    }, 500),
    destroy: function () {
      let vm = this;
      vm.$q.dialog({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        ok: 'Excluir',
        cancel: 'Cancelar'
      }).onOk(() => {
        vm.$axios.delete('grupo-usuario/' + vm.grupousuario.codgrupousuario).then(function (request) {
          vm.$q.notify({
            message: 'Registro excluido',
            type: 'positive',
          });
          vm.filter.grupo = null;
          vm.loadDataGrupos();
          vm.grupousuario = false
        }).catch(function (error) {
          console.log(error)
        })
      })
    },
    refresher (index, done) {
      this.page++;
      this.loadData(true, done)
    },

  },
  computed: {
    filialState: {
      get () {
        return this.$store.state.filtroFilial.filialState
      }
    }
  },
  created () {
    this.filter = this.filialState;
    this.loadData()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.q-item-sublabel > span {
  font-weight: normal;
}
</style>
