<script setup>
import { ref, onMounted, computed } from "vue";
import { useQuasar } from "quasar";
import { useRouter, useRoute } from "vue-router";
import { usuarioStore } from "src/stores/usuario";
import SelectFilial from "components/pessoa/SelectFilial.vue";
import SelectPortador from "components/select/SelectPortador.vue";
import SelectPessoaUsuario from "components/Usuarios/SelectPessoaUsuario.vue";

const $q = useQuasar();
const router = useRouter();
const route = useRoute();
const sUsuario = usuarioStore();

// Refs
const model = ref({
  codusuario: null,
  usuario: null,
  codfilial: null,
  codportador: null,
  codpessoa: null,
  permissoes: {},
});
const grupos = ref([]);
const filiais = ref([]);

// Computed
const isNovo = computed(() => !route.params.codusuario);
const titulo = computed(() => (isNovo.value ? "Novo Usuário" : sUsuario.detalheUsuarios?.usuario || ""));

// Functions
const usuarioValido = (val) => {
  if (String(val).length < 4) {
    return "No mínimo 4 caracteres!";
  }
  const usernameRegex = /^[a-z0-9_.]+$/;
  if (!usernameRegex.test(val)) {
    return "Somente Letras, Números, traços e pontos são aceitos!";
  }
  return true;
};

const marcarTodos = (codgrupousuario) => {
  for (const codfilial of Object.keys(model.value.permissoes[codgrupousuario])) {
    model.value.permissoes[codgrupousuario][codfilial] = true;
  }
};

const marcarNenhum = (codgrupousuario) => {
  for (const codfilial of Object.keys(model.value.permissoes[codgrupousuario])) {
    model.value.permissoes[codgrupousuario][codfilial] = false;
  }
};

const criar = () => {
  $q.dialog({
    title: "Criar usuário",
    message: "Tem certeza que deseja criar esse usuário?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await sUsuario.postUsuario(model.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Usuário Criado!",
      });
      router.push("/usuarios/");
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.message || "Erro ao criar usuário",
      });
    }
  });
};

const alterar = () => {
  $q.dialog({
    title: "Alterar usuário",
    message: "Tem certeza que deseja alterar esse usuário?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await sUsuario.putUsuario(model.value);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Usuário alterado!",
      });
      router.push(`/usuarios/${sUsuario.detalheUsuarios.codusuario}`);
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response?.data?.message || "Erro ao alterar usuário",
      });
    }
  });
};

const submit = () => {
  if (isNovo.value) {
    criar();
  } else {
    alterar();
  }
};

// Lifecycle
onMounted(async () => {
  const retGrupos = await sUsuario.getGrupoUsuarios();
  const retFiliais = await sUsuario.getFilial();

  grupos.value = retGrupos.data.data.sort((a, b) =>
    a.grupousuario.localeCompare(b.grupousuario)
  );
  filiais.value = retFiliais.data.data.sort((a, b) => a.codfilial - b.codfilial);

  if (!isNovo.value) {
    model.value.codusuario = sUsuario.detalheUsuarios.codusuario;
    model.value.usuario = sUsuario.detalheUsuarios.usuario;
    model.value.codfilial = sUsuario.detalheUsuarios.codfilial;
    model.value.codportador = sUsuario.detalheUsuarios.codportador;
    model.value.codpessoa = sUsuario.detalheUsuarios.codpessoa;
  }

  // Inicializa permissões
  grupos.value.forEach((grupo) => {
    model.value.permissoes[grupo.codgrupousuario] = {};
    filiais.value.forEach((filial) => {
      let existe = false;
      if (!isNovo.value) {
        const permGrupo = sUsuario.detalheUsuarios.permissoes.find(
          (g) => g.codgrupousuario === grupo.codgrupousuario
        );
        if (permGrupo) {
          existe = permGrupo.filiais.some((f) => f.codfilial === filial.codfilial);
        }
      }
      model.value.permissoes[grupo.codgrupousuario][filial.codfilial] = existe;
    });
  });
});
</script>

<template>
  <q-form @submit.prevent="submit()">
    <q-card bordered flat>
      <q-card-section class="text-grey-9 text-overline q-pb-none">
        <div class="text-h6">{{ titulo }}</div>
        <q-badge v-if="sUsuario.detalheUsuarios?.inativo" color="red">
          Inativo
        </q-badge>
      </q-card-section>

      <q-card-section class="q-pt-sm">
        <div class="row q-col-gutter-md">
          <div class="col-md-6 col-12">
            <q-input
              outlined
              v-model="model.usuario"
              maxlength="20"
              :rules="[usuarioValido]"
              label="Usuário"
            >
              <template #prepend>
                <q-icon name="person" />
              </template>
            </q-input>
          </div>

          <div class="col-md-6 col-12">
            <select-filial outlined label="Filial" v-model="model.codfilial">
              <template #prepend>
                <q-icon name="corporate_fare" />
              </template>
            </select-filial>
          </div>

          <div class="col-md-6 col-12">
            <select-portador outlined label="Portador" v-model="model.codportador">
              <template #prepend>
                <q-icon name="wallet" />
              </template>
            </select-portador>
          </div>

          <div class="col-md-6 col-12">
            <select-pessoa-usuario
              outlined
              label="Pessoa"
              :modelcod-pessoa="model.codpessoa"
              v-model="model.codpessoa"
              :rules="[
                (val) =>
                  (val !== null && val !== '' && val !== undefined) || 'Pessoa Obrigatória',
              ]"
            >
              <template #prepend>
                <q-icon name="badge" />
              </template>
            </select-pessoa-usuario>
          </div>
        </div>
      </q-card-section>

      <q-separator inset class="q-mt-md" />

      <q-card-section>
        <div class="text-overline text-grey-9 q-mb-md">PERMISSÕES</div>

        <div class="row q-col-gutter-md">
          <div class="col-12" v-for="grupo in grupos" :key="grupo.codgrupousuario">
            <q-card bordered flat>
              <q-card-section class="text-grey-9 text-overline q-pb-none">
                {{ grupo.grupousuario }}
              </q-card-section>

              <q-separator inset class="q-mt-sm" />

              <q-card-section>
                <div class="row">
                  <div
                    class="col-xs-6 col-sm-4 col-md-4 col-lg-3 col-xl-2"
                    v-for="filial in filiais"
                    :key="filial.codfilial"
                  >
                    <q-checkbox
                      :label="filial.filial"
                      v-model="model.permissoes[grupo.codgrupousuario][filial.codfilial]"
                    />
                  </div>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions>
                <q-btn
                  flat
                  dense
                  size="sm"
                  color="primary"
                  label="Marcar todos"
                  @click="marcarTodos(grupo.codgrupousuario)"
                />
                <q-btn
                  flat
                  dense
                  size="sm"
                  color="grey-7"
                  label="Nenhum"
                  @click="marcarNenhum(grupo.codgrupousuario)"
                />
              </q-card-actions>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="save" color="primary" type="submit">
        <q-tooltip>Salvar</q-tooltip>
      </q-btn>
    </q-page-sticky>
  </q-form>
</template>
