<script setup>
import { useQuasar } from "quasar";
import { useRouter, useRoute } from "vue-router";
import { usuarioStore } from "src/stores/usuario";
import { guardaToken } from "src/stores";
import moment from "moment";
import "moment/min/locales";
moment.locale("pt-br");

const $q = useQuasar();
const router = useRouter();
const route = useRoute();
const sUsuario = usuarioStore();
const user = guardaToken();

const resetarSenha = (codusuario) => {
  $q.dialog({
    title: "Reset de senha",
    message: "Tem certeza que deseja resetar a senha desse usuário?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sUsuario.resetarSenha(codusuario);
      if (ret.data) {
        $q.dialog({
          title: "Senha gerada",
          message: `A nova senha é: <b class="text-h6">${ret.data}</b>`,
          html: true,
        });
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "warning",
        message: error.response?.data?.message || "Erro ao resetar senha",
      });
    }
  });
};

const editar = () => {
  router.push(`/usuarios/${route.params.codusuario}/editar`);
};

const excluir = (codusuario) => {
  $q.dialog({
    title: "Excluir usuário",
    message: "Tem certeza que deseja excluir esse usuário?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sUsuario.excluirUsuario(codusuario);
      if (ret.data.result) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Removido",
        });
        router.push("/usuarios");
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "warning",
        message: error.response?.data?.message || "Erro ao excluir",
      });
    }
  });
};

const inativar = async (codusuario) => {
  try {
    const ret = await sUsuario.inativar(codusuario);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Inativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "negative",
      textColor: "white",
      icon: "error",
      message: error.response?.data || "Erro ao inativar",
    });
  }
};

const ativar = async (codusuario) => {
  try {
    const ret = await sUsuario.ativar(codusuario);
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Ativado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "negative",
      textColor: "white",
      icon: "error",
      message: error.response?.data || "Erro ao ativar",
    });
  }
};
</script>

<template>
  <q-card bordered flat v-if="sUsuario.detalheUsuarios">
    <q-card-section class="text-grey-9 text-overline row items-center">
      DETALHES DO USUÁRIO
      <q-space />
      <q-btn
        v-if="user.verificaPermissaoUsuario('Administrador')"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="edit"
        @click="editar()"
      >
        <q-tooltip>Editar</q-tooltip>
      </q-btn>

      <q-btn
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="delete"
        @click="excluir(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Excluir</q-tooltip>
      </q-btn>

      <q-btn
        v-if="user.verificaPermissaoUsuario('Administrador') && !sUsuario.detalheUsuarios.inativo"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="pause"
        @click="inativar(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Inativar</q-tooltip>
      </q-btn>

      <q-btn
        v-if="user.verificaPermissaoUsuario('Administrador') && sUsuario.detalheUsuarios.inativo"
        flat
        round
        dense
        size="sm"
        color="grey-7"
        icon="play_arrow"
        @click="ativar(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Ativar</q-tooltip>
      </q-btn>

      <q-btn
        flat
        round
        dense
        size="sm"
        color="primary"
        icon="lock_reset"
        @click="resetarSenha(sUsuario.detalheUsuarios.codusuario)"
      >
        <q-tooltip>Resetar Senha</q-tooltip>
      </q-btn>
    </q-card-section>

    <q-separator inset />

    <q-list>
      <!-- PESSOA -->
      <q-item
        v-if="sUsuario.detalheUsuarios.codpessoa"
        :to="'/pessoa/' + sUsuario.detalheUsuarios.codpessoa"
      >
        <q-item-section avatar>
          <q-icon color="primary" name="badge" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ sUsuario.detalheUsuarios.Pessoa?.pessoa }}
          </q-item-label>
          <q-item-label caption>Pessoa</q-item-label>
        </q-item-section>
      </q-item>

      <!-- FILIAL -->
      <q-item v-if="sUsuario.detalheUsuarios.codfilial">
        <q-item-section avatar>
          <q-icon color="primary" name="corporate_fare" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ sUsuario.detalheUsuarios.filial }}
          </q-item-label>
          <q-item-label caption>Filial</q-item-label>
        </q-item-section>
      </q-item>

      <!-- PORTADOR -->
      <q-item v-if="sUsuario.detalheUsuarios.codportador">
        <q-item-section avatar>
          <q-icon color="primary" name="wallet" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ sUsuario.detalheUsuarios.portador }}
          </q-item-label>
          <q-item-label caption>Portador</q-item-label>
        </q-item-section>
      </q-item>

      <!-- ÚLTIMO ACESSO -->
      <q-item v-if="sUsuario.detalheUsuarios.ultimoacesso">
        <q-item-section avatar>
          <q-icon color="primary" name="login" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ moment(sUsuario.detalheUsuarios.ultimoacesso).format("DD/MMM/YYYY") }}
            - {{ moment(sUsuario.detalheUsuarios.ultimoacesso).fromNow() }}
          </q-item-label>
          <q-item-label caption>Último acesso</q-item-label>
        </q-item-section>
      </q-item>

      <!-- DATA INATIVAÇÃO -->
      <q-item v-if="sUsuario.detalheUsuarios.inativo">
        <q-item-section avatar>
          <q-icon color="red" name="event_busy" size="xs" />
        </q-item-section>
        <q-item-section>
          <q-item-label class="ellipsis text-caption">
            {{ moment(sUsuario.detalheUsuarios.inativo).format("DD/MM/YYYY HH:mm") }}
          </q-item-label>
          <q-item-label caption>Data de inativação</q-item-label>
        </q-item-section>
      </q-item>
    </q-list>
  </q-card>
</template>
