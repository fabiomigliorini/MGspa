<script setup>
import { defineAsyncComponent, ref } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataData } from "src/utils/formatador";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";

const InputFiltered = defineAsyncComponent(() =>
  import("components/InputFiltered.vue")
);

const $q = useQuasar();
const route = useRoute();
const sPessoa = pessoaStore();
const user = guardaToken();

const dialogEmail = ref(false);
const emailNovo = ref(false);
const modelEmail = ref({
  codpessoa: "",
  email: "",
  apelido: "",
  verificacao: "",
  nfe: "",
  cobranca: "",
});

function linkEmail(email) {
  return "mailto:" + email;
}

async function modalNovoEmail() {
  dialogEmail.value = true;
  const cobranca =
    sPessoa.item.PessoaEmailS.filter((email) => email.cobranca == true)
      .length == 0;
  const nfe =
    sPessoa.item.PessoaEmailS.filter((email) => email.nfe == true).length == 0;
  modelEmail.value = { cobranca: cobranca, nfe: nfe };
  emailNovo.value = true;
}

async function novoEmail() {
  if (modelEmail.value.email !== "") {
    modelEmail.value.codpessoa = route.params.id;
    try {
      const ret = await sPessoa.emailNovo(route.params.id, modelEmail.value);
      if (ret.data.data) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Email criado.",
        });
        emailNovo.value = false;
        dialogEmail.value = false;
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  } else {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Campo Email é obrigatório!",
    });
  }
}

async function excluirEmail(codpessoaemail) {
  $q.dialog({
    title: "Excluir Email",
    message: "Tem certeza que deseja excluir esse email?",
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const ret = await sPessoa.emailExcluir(route.params.id, codpessoaemail);
      if (ret) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Email excluido",
        });
        sPessoa.get(route.params.id);
      }
    } catch (error) {
      $q.notify({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: error.response.data.message,
      });
    }
  });
}

function editarEmail(codpessoaemail, email, apelido, verificacao, nfe, cobranca) {
  dialogEmail.value = true;
  modelEmail.value = {
    codpessoaemail: codpessoaemail,
    email: email,
    apelido: apelido,
    verificacao: verificacao,
    nfe: nfe,
    cobranca: cobranca,
  };
}

async function salvarEmail(codpessoa) {
  try {
    const ret = await sPessoa.emailSalvar(
      codpessoa,
      modelEmail.value.codpessoaemail,
      modelEmail.value
    );
    if (ret.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Email alterado",
      });
      dialogEmail.value = false;
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
  }
}

async function inativar(codpessoa, codpessoaemail) {
  try {
    const ret = await sPessoa.emailInativar(codpessoa, codpessoaemail);
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
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
  }
}

async function ativar(codpessoa, codpessoaemail) {
  try {
    const ret = await sPessoa.emailAtivar(codpessoa, codpessoaemail);
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
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
  }
}

async function cima(codpessoa, codpessoaemail) {
  try {
    await sPessoa.emailParaCima(codpessoa, codpessoaemail);
    $q.notify({
      color: "green-4",
      textColor: "white",
      icon: "done",
      message: "Movido para cima",
    });
    sPessoa.get(codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
    sPessoa.get(codpessoa);
  }
}

async function baixo(codpessoa, codpessoaemail) {
  try {
    await sPessoa.emailParaBaixo(codpessoa, codpessoaemail);
    $q.notify({
      color: "green-4",
      textColor: "white",
      icon: "done",
      message: "Movido para baixo",
    });
    sPessoa.get(codpessoa);
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
    sPessoa.get(codpessoa);
  }
}

function enviarEmail(email, codpessoaemail) {
  $q.dialog({
    title: "Verificação de E-mail",
    message:
      "Deseja enviar o código de verificação para o e-mail  " + email + " ?",
    cancel: true,
  }).onOk(() => {
    sPessoa
      .emailVerificar(route.params.id, codpessoaemail)
      .then((resp) => {
        if (resp.data) {
          $q.notify({
            color: "green-5",
            textColor: "white",
            icon: "done",
            message: "Código enviado para o e-mail",
          });
        }
      });
    confirmaEmail(email, codpessoaemail);
  });
}

function confirmaEmail(email, codpessoaemail) {
  $q.dialog({
    title: "Verificação de E-mail",
    message: "Digite o código enviado para o e-mail " + email,
    prompt: { model: "", type: "number", step: "1" },
    cancel: true,
    persistent: true,
  }).onOk((codverificacao) => {
    postEmail(email, codpessoaemail, codverificacao);
  });
}

async function postEmail(email, codpessoaemail, codverificacao) {
  try {
    const ret = await sPessoa.emailConfirmaVerificacao(
      route.params.id,
      codpessoaemail,
      codverificacao
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "E-mail Verificado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
    confirmaEmail(email, codpessoaemail);
  }
}
</script>

<template>
  <div>
    <!-- DIALOG NOVO EMAIL/EDITAR EMAIL -->
    <q-dialog v-model="dialogEmail">
      <q-card style="min-width: 350px">
        <q-form
          @submit="
            emailNovo == true
              ? novoEmail(route.params.id)
              : salvarEmail(route.params.id)
          "
        >
          <q-card-section>
            <div v-if="emailNovo" class="text-h6">Novo Email</div>
            <div v-else class="text-h6">Editar Email</div>
          </q-card-section>
          <q-card-section>
            <q-input
              outlined
              v-model="modelEmail.email"
              autofocus
              label="Email"
              :rules="[(val) => (val && val.length > 0) || 'Email obrigatório']"
            />
            <input-filtered
              outlined
              v-model="modelEmail.apelido"
              label="Apelido"
            />
            <q-toggle v-model="modelEmail.cobranca" label="Cobrança" />
            <q-toggle v-model="modelEmail.nfe" label="Envio de NFe" />
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Salvar" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <q-card bordered>
      <q-card-section class="bg-yellow text-grey-9 q-py-sm">
        <div class="row items-center no-wrap q-gutter-x-sm">
          <q-icon name="email" size="sm" />
          <span class="text-subtitle1 text-weight-medium">Email</span>
          <q-space />
          <q-btn
            flat
            round
            dense
            icon="add"
            size="sm"
            color="grey-9"
            v-if="user.verificaPermissaoUsuario('Publico')"
            @click="modalNovoEmail()"
          />
        </div>
      </q-card-section>

      <q-list separator>
        <template
          v-for="(element, i) in sPessoa.item?.PessoaEmailS"
          v-bind:key="element.codpessoaemail"
        >
          <q-item>
            <!-- BOTAO EMAIL -->
            <q-item-section avatar>
              <q-btn
                round
                icon="email"
                color="primary"
                flat
                :href="linkEmail(element.email)"
              />
            </q-item-section>

            <q-item-section>
              <!-- EMAIL -->
              <q-item-label :class="element.inativo ? 'text-strike' : null">
                {{ element.email }}
                <q-icon
                  v-if="element.verificacao"
                  color="primary"
                  name="verified"
                  class="q-ml-xs"
                >
                  <q-tooltip>Verificado</q-tooltip>
                </q-icon>
                <q-icon
                  v-if="element.cobranca"
                  color="green"
                  name="paid"
                  class="q-ml-xs"
                >
                  <q-tooltip>Cobrança</q-tooltip>
                </q-icon>
                <q-icon
                  v-if="element.nfe"
                  color="green"
                  name="description"
                  class="q-ml-xs"
                >
                  <q-tooltip>Envio de NFe</q-tooltip>
                </q-icon>

                <!-- INFO -->
                <icone-info-criacao
                  :usuariocriacao="element.usuariocriacao"
                  :criacao="element.criacao"
                  :usuarioalteracao="element.usuarioalteracao"
                  :alteracao="element.alteracao"
                />
              </q-item-label>

              <!-- INATIVO -->
              <q-item-label caption class="text-red-14" v-if="element.inativo">
                Inativo desde: {{ formataData(element.inativo) }}
              </q-item-label>

              <!-- APELIDO -->
              <q-item-label caption v-if="element.apelido">
                {{ element.apelido }}
              </q-item-label>

              <!-- VERIFICAR -->
              <q-item-label
                caption
                v-if="user.verificaPermissaoUsuario('Publico')"
              >
                <q-btn
                  v-if="!element.verificacao"
                  flat
                  dense
                  size="sm"
                  label="Verificar"
                  color="primary"
                  @click="enviarEmail(element.email, element.codpessoaemail)"
                />
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <!-- BOTOES -->
              <q-item-label
                caption
                v-if="user.verificaPermissaoUsuario('Publico')"
              >
                <template v-if="sPessoa.item?.PessoaEmailS.length > 1">
                  <!-- CIMA -->
                  <q-btn
                    flat
                    dense
                    round
                    icon="north"
                    size="sm"
                    color="grey-7"
                    @click="cima(element.codpessoa, element.codpessoaemail)"
                    v-if="i != 0"
                  >
                    <q-tooltip>Mover para cima</q-tooltip>
                  </q-btn>

                  <!-- BAIXO -->
                  <q-btn
                    flat
                    dense
                    round
                    icon="south"
                    size="sm"
                    color="grey-7"
                    @click="baixo(element.codpessoa, element.codpessoaemail)"
                    v-else
                  >
                    <q-tooltip>Mover para baixo</q-tooltip>
                  </q-btn>
                </template>

                <!-- EDITAR -->
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  size="sm"
                  color="grey-7"
                  @click="
                    editarEmail(
                      element.codpessoaemail,
                      element.email,
                      element.apelido,
                      element.verificacao,
                      element.nfe,
                      element.cobranca
                    ),
                      (emailNovo = false)
                  "
                >
                  <q-tooltip>Editar</q-tooltip>
                </q-btn>

                <!-- INATIVAR -->
                <q-btn
                  v-if="!element.inativo"
                  flat
                  dense
                  round
                  icon="pause"
                  size="sm"
                  color="grey-7"
                  @click="inativar(element.codpessoa, element.codpessoaemail)"
                >
                  <q-tooltip>Inativar</q-tooltip>
                </q-btn>

                <!-- ATIVAR -->
                <q-btn
                  v-if="element.inativo"
                  flat
                  dense
                  round
                  icon="play_arrow"
                  size="sm"
                  color="grey-7"
                  @click="ativar(element.codpessoa, element.codpessoaemail)"
                >
                  <q-tooltip>Ativar</q-tooltip>
                </q-btn>

                <!-- EXCLUIR -->
                <q-btn
                  flat
                  dense
                  round
                  icon="delete"
                  size="sm"
                  color="grey-7"
                  @click="excluirEmail(element.codpessoaemail)"
                >
                  <q-tooltip>Excluir</q-tooltip>
                </q-btn>
              </q-item-label>
            </q-item-section>
          </q-item>
        </template>
      </q-list>
    </q-card>
  </div>
</template>

<style scoped></style>
