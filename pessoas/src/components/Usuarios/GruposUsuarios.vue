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
const modelGrupoUsuario = ref({})
const separator = ref('cell')


onMounted(async () => {
  const ret = await sUsuario.getGrupoUsuarios()
  const filiais = await sUsuario.getFilial()

  grupoUsuarios.value = ret.data.data
  console.log(grupoUsuarios.value)
  filial.value = filiais.data.data
  console.log(sUsuario.detalheUsuarios.grupo_usuario_usuario_s)
  
})  
</script>

<template>
  <q-card bordered>
  
    <q-markup-table :separator="separator" flat bordered>
      <thead>
        <tr>
          <th class="text-left">Grupo</th>
          <th v-for="filiais in filial" v-bind:key="filiais.codfilial" class="text-left">{{ filiais.filial }} <br> {{
            filiais.codfilial }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="grupos in grupoUsuarios" v-bind:key="grupos.codgrupousuario">

          <th scope="row">{{ grupos.grupousuario }}</th>

          <td v-for="filiais in filial" v-bind:key="filiais.codfilial" class="text-left">
            
            <!-- <div v-for="gruposDoUsuario in sUsuario.detalheUsuarios.grupo_usuario_usuario_s" v-bind:key="gruposDoUsuario.codgrupousuario"> -->

              <q-btn-toggle v-model:model-value="modelGrupoUsuario" class="my-custom-toggle" no-caps unelevated
              toggle-color="red" color="white" text-color="primary" :options="[
                { label: 'Sim', value: grupos.codgrupousuario },
                { label: 'Não', value: 'nao' }
              ]" />
            <!-- </div> -->
           
          </td>

          <q-separator />
        </tr>
      </tbody>
    </q-markup-table>
  </q-card>
</template>