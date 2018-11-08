<template>
  <mg-layout>

    <q-side-link :to="'/usuario/perfil'" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <q-btn flat icon="done" slot="menuRight" @click.prevent="update()" />

    <template slot="title">
      Alterar impressoras
    </template>

    <div slot="content">
      <div class="layout-padding">

        <div class="row">
          <div class="col-md-4">
            <q-card>
              <q-card-main>
                <form @submit.prevent="update()">
                  <div class="row">
                    <div class="col-md-12">
                      <mg-select-impressora
                        label="Impressora Matricial"
                        v-model="data.impressoramatricial">
                      </mg-select-impressora>
                      <mg-erros-validacao :erros="erros.impressoramatricial"></mg-erros-validacao>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12">
                      <mg-select-impressora
                        label="Impressora Térmica"
                        v-model="data.impressoratermica">
                      </mg-select-impressora>
                      <mg-erros-validacao :erros="erros.impressoratermica"></mg-erros-validacao>
                    </div>
                  </div>
                </form>
              </q-card-main>
            </q-card>
          </div>

        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import {
  Dialog,
  Toast,
  QBtn,
  QField,
  QInput,
  QSelect,
  QSideLink,
  QUploader,
  QCardMedia,
  QCard,
  QCardMain,
  QCardTitle

} from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgSelectImpressora from '../../utils/select/MgSelectImpressora'

export default {
  name: 'usuario-profile',
  components: {
    MgLayout,
    MgErrosValidacao,
    QBtn,
    QField,
    QInput,
    QSelect,
    QSideLink,
    QUploader,
    QCardMedia,
    QCard,
    QCardMain,
    QCardTitle,
    MgSelectImpressora
  },
  data () {
    return {
      data: {},
      erros: false
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this
      let params = {
        fields: ['codusuario', 'usuario', 'impressoratermica', 'impressoramatricial']
      }
      window.axios.get('usuario/' + id, { params }).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    },
    update: function () {
      var vm = this
      Dialog.create({
        title: 'Salvar',
        message: 'Tem certeza que deseja salvar?',
        buttons: [
          {
            label: 'Cancelar',
            handler () {}
          },
          {
            label: 'Salvar',
            handler () {
              window.axios.put('usuario/' + vm.data.codusuario, vm.data).then(function (request) {
                Toast.create.positive('Registro atualizado')
                vm.$router.push('/usuario/perfil')
              }).catch(function (error) {
                vm.erros = error.response.data.erros
              })
            }
          }
        ]
      })
    }
  },
  created () {
    this.loadData(localStorage.getItem('auth.usuario.codusuario'))
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
