<script setup>
import { ref, onMounted } from "vue";
import { usuarioStore } from "stores/usuario";
import moment from "moment/min/moment-with-locales";
moment.locale("pt-br");

const sUsuario = usuarioStore();
const dialogLogin = ref(false);
const usuario = ref(null);
const senha = ref(null);
const mostrarSenha = ref(false);
const inputSenha = ref();

const logout = async () => {
  sUsuario.logout();
};

const refresh = async () => {
  sUsuario.refreshToken();
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

const togglePassword = () => {
  mostrarSenha.value = !mostrarSenha.value;
  const temp = senha.value;
  inputSenha.value.focus();
  senha.value = temp;
};

const formataTempoPercorridoDesde = (desde) => {
  if (!desde) {
    return null;
  }
  return moment(desde).fromNow();
};

onMounted(() => {
  sUsuario.getUsuario();
});
</script>
<template>
  <q-dialog v-model="dialogLogin">
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
            @click="dialogLogin = false"
            tabindex="-1"
          />
          <q-btn flat label="Entrar" type="submit" color="primary" />
        </q-card-actions>
      </q-form>
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
        <q-avatar size="150px" color="yellow-8">
          <q-icon name="person_outline" color="grey-6" size="140px" />
          <!-- <img src="https://cdn.quasar.dev/img/boy-avatar.png" /> -->
        </q-avatar>
        <div class="text-subtitle1 q-mt-md q-mb-xs text-center">
          <b>{{ sUsuario.usuario.usuario }}</b>
          <div v-if="sUsuario.usuario.Pessoa" class="text-grey-8">
            {{ sUsuario.usuario.Pessoa.pessoa }}
          </div>
          <div class="text-grey-8">
            expira
            {{ moment(sUsuario.token.expires_at).fromNow() }}
          </div>
        </div>
        <q-btn-group>
          <q-btn
            color="primary"
            label="Renovar"
            push
            size="sm"
            v-close-popup
            @click="refresh()"
          />
          <q-btn
            color="primary"
            label="Sair"
            push
            size="sm"
            v-close-popup
            @click="logout()"
          />
        </q-btn-group>
      </div>
    </div>
  </q-btn-dropdown>
  <q-btn dense label="Conectar" v-else color="negative" @click="abrirLogin()" />
</template>
