<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from 'stores/auth'
import { useAuth } from 'src/composables/useAuth'
import MgUserMenu from '@components/MgUserMenu.vue'

const store = useAuthStore()
const auth = useAuth()

const usuario = ref(null)
const senha = ref(null)
const mostrarSenha = ref(false)
const inputSenha = ref()

const login = async () => {
  const ret = await store.login(usuario.value, senha.value)
  if (ret) {
    store.dialog.login = false
  }
}

const togglePassword = () => {
  mostrarSenha.value = !mostrarSenha.value
  const temp = senha.value
  inputSenha.value.focus()
  senha.value = temp
}

onMounted(() => {
  store.carregarUsuario()
})
</script>
<template>
  <q-dialog v-model="store.dialog.login">
    <q-card style="width: 350px; max-width: 80vw">
      <q-form @submit="login()">
        <q-card-section>
          <div class="q-gutter-md">
            <q-input
              autofocus
              outlined
              v-model="usuario"
              label="Usuário"
              :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
            />

            <q-input
              outlined
              ref="inputSenha"
              v-model="senha"
              :type="mostrarSenha ? 'text' : 'password'"
              label="Senha"
              :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
            >
              <template v-slot:append>
                <q-icon
                  :name="mostrarSenha ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="togglePassword()"
                />
              </template>
            </q-input>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="primary"
            class="q-ml-sm"
            @click="store.dialog.login = false"
            tabindex="-1"
          />
          <q-btn flat label="Entrar" type="submit" color="primary" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <MgUserMenu :auth="auth" />
</template>
