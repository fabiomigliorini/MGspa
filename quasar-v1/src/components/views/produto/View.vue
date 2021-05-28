<template>
  <mg-layout back-path="/produto/">

    <!-- Título da Página -->
    <template slot="title">
      Cadastro de Produto
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">
      <div class="q-pa-sm">
        <q-card class="shadow-6" bordered>
          <q-card-section class="bg-primary text-white">
            <div class="text-h6">
              #{{ numeral(parseFloat(codproduto)).format('000000') }} -
              {{produto.produto}}
            </div>
          </q-card-section>
          <q-card-section horizontal>
            <q-card-section class="col-xs-6 col-sm-3 col-md-2 col-lg-2 col-xl-1">
              <q-img :src="produto.url" :ratio="1"/>
              <div class="absolute-top text-subtitle2 text-right">
                <div class="col-auto">
                  <q-btn color="grey-7" round flat icon="more_vert">
                    <q-menu cover auto-close>
                      <q-list>
                        <q-item clickable>
                          <q-item-section>Definir Imagem Principal</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                  </q-btn>
                </div>
              </div>
            </q-card-section>
            <q-card-section class="col">
              <q-chip icon="star" color="green" >
                {{produto.abc}}
              </q-chip>
              <q-chip icon="bookmark" color="red" >
                {{produto.marca}}
              </q-chip>
              <q-chip icon="bookmark" color="primary" >
                {{produto.secaoproduto}} |
                {{produto.familiaproduto}} |
                {{produto.grupoproduto}} |
                {{produto.subgrupoproduto}}
              </q-chip>
              <q-list>
                <!-- <q-item-label header>{{produto.produto}}</q-item-label> -->
                <q-item>
                  <q-item-section>
                    <q-item-label>{{produto.produto}}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-item>
                  <q-item-section>
                    <q-item-label>
                      <div class="text-subtitle2">
                      </div>
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>

            </q-card-section>
          </q-card-section>
        </q-card>

        <div>
          <q-list bordered padding>
            <q-item-label header>User Controls</q-item-label>

            <q-item clickable v-ripple>
              <q-item-section>
                <q-item-label>Content filtering</q-item-label>
                <q-item-label caption>
                  Set the content filtering level to restrict
                  apps that can be downloaded
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item clickable v-ripple>
              <q-item-section>
                <q-item-label>Password</q-item-label>
                <q-item-label caption>
                  Require password for purchase or use
                  password to restrict purchase
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-separator spaced />
            <q-item-label header>General</q-item-label>

            <q-item tag="label" v-ripple>
              <q-item-section side top>
                <q-checkbox v-model="check1" />
              </q-item-section>

              <q-item-section>
                <q-item-label>Notifications</q-item-label>
                <q-item-label caption>
                  Notify me about updates to apps or games that I downloaded
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item tag="label" v-ripple>
              <q-item-section side top>
                <q-checkbox v-model="check2" />
              </q-item-section>

              <q-item-section>
                <q-item-label>Sound</q-item-label>
                <q-item-label caption>
                  Auto-update apps at anytime. Data charges may apply
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-item tag="label" v-ripple>
              <q-item-section side top>
                <q-checkbox v-model="check3" />
              </q-item-section>

              <q-item-section>
                <q-item-label>Auto-add widgets</q-item-label>
                <q-item-label caption>
                  Automatically add home screen widgets
                </q-item-label>
              </q-item-section>
            </q-item>

            <q-separator spaced />
            <q-item-label header>Notifications</q-item-label>

            <q-item tag="label" v-ripple>
              <q-item-section>
                <q-item-label>Battery too low</q-item-label>
              </q-item-section>
              <q-item-section side >
                <q-toggle color="blue" v-model="notif1" val="battery" />
              </q-item-section>
            </q-item>

            <q-item tag="label" v-ripple>
              <q-item-section>
                <q-item-label>Friend request</q-item-label>
                <q-item-label caption>Allow notification</q-item-label>
              </q-item-section>
              <q-item-section side top>
                <q-toggle color="green" v-model="notif2" val="friend" />
              </q-item-section>
            </q-item>

            <q-item tag="label" v-ripple>
              <q-item-section>
                <q-item-label>Picture uploaded</q-item-label>
                <q-item-label caption>Allow notification when uploading images</q-item-label>
              </q-item-section>
              <q-item-section side top>
                <q-toggle color="red" v-model="notif3" val="picture" />
              </q-item-section>
            </q-item>

            <q-separator spaced />
            <q-item-label header>Other settings</q-item-label>

            <q-item>
              <q-item-section side>
                <q-icon color="teal" name="volume_down" />
              </q-item-section>
              <q-item-section>
                <q-slider
                  v-model="volume"
                  :min="0"
                  :max="10"
                  label
                  color="teal"
                />
              </q-item-section>
              <q-item-section side>
                <q-icon color="teal" name="volume_up" />
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section side>
                <q-icon color="deep-orange" name="brightness_medium" />
              </q-item-section>
              <q-item-section>
                <q-slider
                  v-model="brightness"
                  :min="0"
                  :max="10"
                  label
                  color="deep-orange"
                />
              </q-item-section>
            </q-item>

            <q-item>
              <q-item-section side>
                <q-icon color="primary" name="mic" />
              </q-item-section>
              <q-item-section>
                <q-slider
                  v-model="mic"
                  :min="0"
                  :max="50"
                  label
                />
              </q-item-section>
            </q-item>
          </q-list>
        </div>


        <div class="row q-col-gutter-sm">
          <div class="col-xs-6 col-sm-3 col-md-2 col-lg-2 col-xl-1" v-for="pi in produto.ProdutoImagemS">
            <q-card class="shadow-6 q-pa-md" bordered>
              <a :href="pi.url" target="_blank">
                <q-img :src="pi.url" :ratio="1" />
              </a>
              <div class="absolute-top text-subtitle2 text-right">
                <div class="col-auto">
                  <q-btn color="grey-7" round flat icon="more_vert">
                    <q-menu cover auto-close>
                      <q-list>
                        <q-item clickable>
                          <q-item-section>Excluir Imagem</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                  </q-btn>
                </div>
              </div>
            </q-card>
          </div>
        </div>

      </div>
    </div>

    <div slot="footer">
      <mg-autor :data="produto"></mg-autor>
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import { debounce } from 'quasar'

export default {
  components: {
    MgLayout,
    MgAutor,
    debounce
  },

  data () {
    return {
      produto: false,
      codproduto: null,
      carouselImagemSlide: 1,
      carouselImagemAutoplay: true
    }
  },
  computed: {
  },
  methods: {
    // carrega registros da api
    loadData: debounce(function () {
      var vm = this;
      vm.$axios.get('produto/' + this.codproduto).then(response => {
        vm.produto = response.data.data;
      })
    }, 500),
  },
  created () {
    this.codproduto = this.$route.params.codproduto;
    this.loadData()
  }

}
</script>

<style lang="sass" scoped>
  .my-content
    padding: 10px 15px
    background: rgba(86,61,124,.15)
    border: 1px solid rgba(86,61,124,.2)
</style>
