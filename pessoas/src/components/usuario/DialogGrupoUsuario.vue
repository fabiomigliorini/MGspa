<script setup>
import MgInput from '@components/MgInput.vue'
import { ref } from 'vue'
import { useQuasar } from 'quasar'
import { storeToRefs } from 'pinia'
import { grupoUsuarioStore } from 'src/stores/grupo-usuario'

const $q = useQuasar()
const sGrupoUsuario = grupoUsuarioStore()
const { dialogGrupoUsuario, formGrupoUsuario } = storeToRefs(sGrupoUsuario)

const salvando = ref(false)

const salvar = async () => {
  if (salvando.value) return
  salvando.value = true
  const novo = !formGrupoUsuario.value.codgrupousuario
  try {
    await sGrupoUsuario.salvarGrupo()
    $q.notify({
      color: 'green-5',
      textColor: 'white',
      icon: 'done',
      message: novo ? 'Grupo criado!' : 'Grupo alterado!',
    })
    dialogGrupoUsuario.value = false
  } catch (error) {
    $q.notify({
      color: 'red-5',
      textColor: 'white',
      icon: 'error',
      message: error.response?.data?.message || 'Erro ao salvar',
    })
  } finally {
    salvando.value = false
  }
}
</script>

<template>
  <q-dialog v-model="dialogGrupoUsuario">
    <q-card flat style="width: 400px; max-width: 95vw">
      <q-form @submit.prevent="salvar()">
        <q-card-section class="text-grey-9 text-overline">
          {{ formGrupoUsuario.codgrupousuario ? 'EDITAR GRUPO USUÁRIO' : 'NOVO GRUPO USUÁRIO' }}
        </q-card-section>

        <q-separator inset />

        <q-card-section class="q-gutter-md">
          <MgInput
            v-model="formGrupoUsuario.grupousuario"
            label="Grupo Usuário"
            maxlength="50"
            autofocus
            :rules="[(val) => (val && val.length > 0) || 'Campo obrigatório']"
          />

          <MgInput
            v-model="formGrupoUsuario.observacoes"
            label="Observações"
            type="textarea"
            maxlength="600"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" color="grey-8" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" :loading="salvando" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
</template>
