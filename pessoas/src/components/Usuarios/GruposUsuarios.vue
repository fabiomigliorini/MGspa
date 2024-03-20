<script setup>

import { ref, computed, onMounted } from 'vue'
import { useQuasar, debounce } from 'quasar'
import { useRoute } from 'vue-router'
import moment from 'moment'
import { usuarioStore } from 'src/stores/usuario'


const $q = useQuasar()
const route = useRoute()
const grupoUsuarios = ref([])
const filial = ref([])

const sUsuario = usuarioStore()

onMounted(async () => {
  const ret = await sUsuario.getGrupoUsuarios()
  const filiais = await sUsuario.getFilial()

  grupoUsuarios.value = ret.data.data
  filial.value = filiais.data.data

})  
</script>

<template>
  <q-card>
    <q-item>
      <q-item-label header>
        Permissões
      </q-item-label>
    </q-item>
    <div class="row q-pa-md q-col-gutter-md">
      <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" v-for="gruposDoUsuario in sUsuario.detalheUsuarios.permissoes"
        v-bind:key="gruposDoUsuario.codgrupousuario">
        <q-card class="no-shadow cursor-pointer" bordered>
          <q-list>
            <q-item :to="'/grupo-usuarios/' + gruposDoUsuario.codgrupousuario">
              <q-card-section class="text-center">
                <q-avatar size="100px" class="shadow-10">
                  <q-icon name="groups_3" color="primary" />
                </q-avatar>
              </q-card-section>

              <q-card-section>
                <q-item-label :class="gruposDoUsuario.inativo ? 'text-strike text-red-14' : null">
                  {{ gruposDoUsuario.grupousuario }}

                </q-item-label>

                <q-item-label caption>
                  {{ gruposDoUsuario.observacoes }}

                </q-item-label>
               
                <q-item-label caption>
                  <template v-for="(filial, i) in gruposDoUsuario.filiais" v-bind:key="filial.codfilial">
                    <span v-if="i != 0">
                      |
                    </span>
                    {{ filial.filial }}
                  </template>
                </q-item-label>

              </q-card-section>
            </q-item>
          </q-list>
        </q-card>
      </div>

    </div>
  </q-card>
</template>