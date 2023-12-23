<script setup>
import { ref, onMounted } from "vue";
import { usuarioStore } from "stores/usuario";
const sUsuario = usuarioStore();

const logout = async () => {
  sUsuario.logout();
};

const abrirLogin = () => {
  usuario.value = null;
  senha.value = null;
  dialogLogin.value = true;
};

const login = async () => {
  const ret = await sUsuario.login(usuario.value, senha.value);
  if (ret) {
    dialogLogin.value = false;
  }
};

const dialogLogin = ref(false);
const usuario = ref(null);
const senha = ref(null);
const mostrarSenha = ref(false);
const inputSenha = ref();

const togglePassword = () => {
  mostrarSenha.value = !mostrarSenha.value;
  const temp = senha.value;
  inputSenha.value.focus();
  senha.value = temp;
};

onMounted(() => {
  sUsuario.inicializar();
  sUsuario.getUsuario();
});
</script>
<template>
  <q-dialog v-model="dialogLogin">
    <q-card>
      <div class="q-pa-md" style="max-width: 400px">
        <q-form @submit="login()" class="q-gutter-md">
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

          <div>
            <q-btn label="Entrar" type="submit" color="primary" />
            <q-btn
              label="Cancelar"
              color="primary"
              flat
              class="q-ml-sm"
              @click="dialogLogin = false"
            />
          </div>
        </q-form>
      </div>
    </q-card>
  </q-dialog>
  <q-btn-dropdown
    dense
    flat
    :label="sUsuario.usuario.usuario"
    v-if="sUsuario.usuario.usuario"
  >
    <div class="row no-wrap q-pa-md justify-center">
      <div class="column items-center">
        <q-avatar size="72px">
          <img src="https://cdn.quasar.dev/img/boy-avatar.png" />
        </q-avatar>
        <div class="text-subtitle1 q-mt-md q-mb-xs">
          {{ sUsuario.usuario.usuario }}
        </div>

        <q-btn
          color="primary"
          label="Sair"
          push
          size="sm"
          v-close-popup
          @click="logout()"
        />
      </div>
    </div>
  </q-btn-dropdown>
  <q-btn dense label="Conectar" v-else color="negative" @click="abrirLogin()" />
</template>
