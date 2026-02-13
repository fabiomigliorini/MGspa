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

const dialogTel = ref(false);
const telNovo = ref(false);
const modelTel = ref({});
const pessoatelefonecod = ref("");

function iconeFone(tipo) {
  switch (tipo) {
    case 2:
      return "smartphone";
    case 1:
      return "phone";
    default:
      return "device_unknown";
  }
}

function formataFone(tipo, fone) {
  switch (tipo) {
    case 2:
      return formataCelular(fone);
    case 1:
      return formataFixo(fone);
    default:
      return fone;
  }
}

function formataCelular(cel) {
  if (cel == null) return cel;
  cel = cel.toString().padStart(9);
  return cel.slice(0, 1) + " " + cel.slice(1, 5) + "-" + cel.slice(5, 9);
}

function formataFixo(fixo) {
  if (fixo == null) return fixo;
  fixo = fixo.toString().padStart(9);
  return fixo.slice(0, 1) + "" + fixo.slice(1, 5) + "-" + fixo.slice(5, 9);
}

function linkTel(ddd, telefone) {
  return "tel:" + ddd + telefone;
}

function confirmaSmsCel(ddd, telefone, codpessoatelefone) {
  $q.dialog({
    title: "Verificação via SMS",
    message:
      "Digite o código enviado para o número " +
      "(" +
      ddd +
      ") " +
      formataCelular(telefone),
    prompt: { model: "", type: "number", step: "1" },
    cancel: true,
    persistent: true,
  }).onOk((codverificacao) => {
    postTelefone(ddd, telefone, codpessoatelefone, codverificacao);
  });
}

async function postTelefone(ddd, telefone, codpessoatelefone, codverificacao) {
  try {
    const ret = await sPessoa.telefoneConfirmaVerificacao(
      route.params.id,
      codpessoatelefone,
      codverificacao
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Telefone Verificado!",
      });
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.response.data.message,
    });
    confirmaSmsCel(ddd, telefone, codpessoatelefone);
  }
}

async function enviarSms(pais, ddd, telefone, codpessoatelefone) {
  $q.dialog({
    title: "Verificação via SMS",
    message:
      "Deseja enviar o código de verificação para o número " +
      "(" +
      ddd +
      ") " +
      formataCelular(telefone) +
      " ?",
    cancel: true,
  }).onOk(() => {
    sPessoa
      .telefoneVerificar(route.params.id, codpessoatelefone)
      .then((resp) => {
        if (resp.data["situacao"] == "OK") {
          $q.notify({
            color: "green-5",
            textColor: "white",
            icon: "done",
            message: "Código SMS enviado",
          });
          confirmaSmsCel(ddd, telefone, codpessoatelefone);
        } else {
          $q.notify({
            color: "red-5",
            textColor: "white",
            icon: "error",
            message: resp.data["descricao"],
          });
        }
      });
  });
}

async function salvarTel(codpessoa) {
  dialogTel.value = false;
  try {
    await sPessoa.telefoneAlterar(
      codpessoa,
      pessoatelefonecod.value,
      modelTel.value
    );
    $q.notify({
      color: "green-5",
      textColor: "white",
      icon: "done",
      message: "Telefone alterado",
    });
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: error.message,
    });
  }
}

async function novoTel(codpessoa) {
  dialogTel.value = false;
  try {
    const ret = await sPessoa.telefoneNovo(codpessoa, modelTel.value);
    if (ret.data.data) {
      telNovo.value = false;
      sPessoa.item.PessoaTelefoneS = ret.data.data;
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Telefone criado.",
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

function editarTel(
  codpessoatelefone,
  ddd,
  telefone,
  apelido,
  tipo,
  verificacao
) {
  dialogTel.value = true;
  modelTel.value = {
    ddd: ddd,
    telefone: telefone,
    apelido: apelido,
    tipo: tipo,
    verificacao: verificacao,
    pais: "+55",
  };
  pessoatelefonecod.value = codpessoatelefone;
}

async function inativar(codpessoa, codpessoatelefone) {
  try {
    const ret = await sPessoa.telefoneInativar(codpessoa, codpessoatelefone);
    if (ret.data) {
      const i = sPessoa.item.PessoaTelefoneS.findIndex(
        (item) => item.codpessoatelefone === codpessoatelefone
      );
      sPessoa.item.PessoaTelefoneS[i] = ret.data.data;
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

async function ativar(codpessoa, codpessoatelefone) {
  try {
    const ret = await sPessoa.telefoneAtivar(codpessoa, codpessoatelefone);
    if (ret.data) {
      const i = sPessoa.item.PessoaTelefoneS.findIndex(
        (item) => item.codpessoatelefone === codpessoatelefone
      );
      sPessoa.item.PessoaTelefoneS[i] = ret.data.data;
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

async function excluirTel(codpessoatelefone) {
  $q.dialog({
    title: "Excluir Contato",
    message: "Tem certeza que deseja excluir esse telefone?",
    cancel: true,
  }).onOk(async () => {
    try {
      const ret = await sPessoa.telefoneExcluir(
        route.params.id,
        codpessoatelefone
      );
      if (ret) {
        $q.notify({
          color: "green-5",
          textColor: "white",
          icon: "done",
          message: "Telefone excluido",
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

async function cima(codpessoa, codpessoatelefone) {
  try {
    await sPessoa.telefoneParaCima(codpessoa, codpessoatelefone);
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

async function baixo(codpessoa, codpessoatelefone) {
  try {
    await sPessoa.telefoneParaBaixo(codpessoa, codpessoatelefone);
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
</script>

<template>
  <!-- DIALOG NOVO/EDITAR TELEFONE -->
  <q-dialog v-model="dialogTel">
    <q-card style="min-width: 350px">
      <q-form
        @submit="
          telNovo == true
            ? novoTel(route.params.id)
            : salvarTel(route.params.id)
        "
      >
        <q-card-section>
          <div v-if="telNovo" class="text-h6">Novo Telefone</div>
          <div v-else class="text-h6">Editar Telefone</div>
        </q-card-section>

        <q-card-section>
          <q-separator spaced />
          <small class="text-h8-grey">Tipo:</small>
          <q-radio
            v-model="modelTel.tipo"
            checked-icon="task_alt"
            unchecked-icon="panorama_fish_eye"
            :val="1"
            label="Fixo"
            outlined
          />
          <q-radio
            v-model="modelTel.tipo"
            checked-icon="task_alt"
            unchecked-icon="panorama_fish_eye"
            :val="2"
            label="Celular"
            outlined
          />
          <q-radio
            v-model="modelTel.tipo"
            checked-icon="task_alt"
            unchecked-icon="panorama_fish_eye"
            :val="9"
            label="Outro"
            outlined
          />
          <q-separator spaced />

          <q-input
            outlined
            v-model="modelTel.pais"
            mask="(+##)"
            value="+55"
            label="País"
            :rules="[(val) => (val && val.length > 0) || 'Pais obrigatório']"
            unmasked-value
          />

          <q-input
            outlined
            v-model="modelTel.ddd"
            mask="(##)"
            label="DDD"
            :rules="[
              telNovo == false
                ? null
                : (val) => (val && val.length > 0) || 'DDD obrigatório',
            ]"
            unmasked-value
            v-if="modelTel.tipo != '9'"
          />

          <q-input
            v-if="modelTel.tipo == '2'"
            outlined
            v-model="modelTel.telefone"
            mask="# ####-####"
            label="Telefone"
            unmasked-value
            :rules="[
              telNovo == false
                ? null
                : (val) => (val && val.length > 0) || 'Telefone obrigatório',
            ]"
            inputmode="numeric"
          />

          <q-input
            v-if="modelTel.tipo == '1'"
            outlined
            v-model="modelTel.telefone"
            mask="####-####"
            label="Telefone"
            unmasked-value
            :rules="[
              telNovo == false
                ? null
                : (val) => (val && val.length > 0) || 'Telefone obrigatório',
            ]"
            inputmode="numeric"
          />

          <q-input
            v-if="modelTel.tipo == '9'"
            outlined
            v-model="modelTel.telefone"
            label="Telefone"
            :rules="[
              telNovo == false
                ? null
                : (val) => (val && val.length > 0) || 'Telefone obrigatório',
            ]"
            inputmode="tel"
          />

          <input-filtered
            outlined
            v-model="modelTel.apelido"
            label="Apelido"
            :rules="[]"
          />
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>

  <q-card bordered>
    <q-card-section class="bg-grey-3 text-grey-9 q-py-sm">
      <div class="row items-center no-wrap q-gutter-x-sm">
        <q-icon name="phone" size="sm" />
        <span class="text-subtitle1 text-weight-medium">Telefone</span>
        <q-space />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="grey-9"
          v-if="user.verificaPermissaoUsuario('Publico')"
          @click="
            (dialogTel = true),
              (modelTel = { tipo: 2, pais: '+55' }),
              (telNovo = true)
          "
        />
      </div>
    </q-card-section>

    <q-list separator>
      <template
        v-for="(element, i) in sPessoa.item?.PessoaTelefoneS"
        v-bind:key="element.codpessoatelefone"
      >
        <q-item>
          <!-- BOTAO TELEFONE -->
          <q-item-section avatar>
            <q-btn
              round
              :icon="iconeFone(element.tipo)"
              color="primary"
              flat
              :href="linkTel(element.ddd, element.telefone)"
            />
          </q-item-section>

          <q-item-section>
            <!-- NUMERO -->
            <q-item-label :class="element.inativo ? 'text-strike' : null">
              <template v-if="element.ddd"> ({{ element.ddd }}) </template>
              {{ formataFone(element.tipo, element.telefone) }}
              <q-icon
                v-if="element.verificacao"
                color="primary"
                name="verified"
                class="q-ml-xs"
              >
                <q-tooltip>Verificado</q-tooltip>
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
                v-if="!element.verificacao && element.tipo === 2"
                flat
                dense
                size="sm"
                label="Verificar"
                color="primary"
                @click="
                  enviarSms(
                    element.pais,
                    element.ddd,
                    element.telefone,
                    element.codpessoatelefone
                  )
                "
              />
            </q-item-label>
          </q-item-section>
          <q-item-section side>
            <!-- BOTOES -->
            <q-item-label
              caption
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <template v-if="sPessoa.item?.PessoaTelefoneS.length > 1">
                <!-- CIMA -->
                <q-btn
                  flat
                  dense
                  round
                  icon="north"
                  size="sm"
                  color="grey-7"
                  @click="cima(element.codpessoa, element.codpessoatelefone)"
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
                  @click="baixo(element.codpessoa, element.codpessoatelefone)"
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
                  editarTel(
                    element.codpessoatelefone,
                    element.ddd,
                    element.telefone,
                    element.apelido,
                    element.tipo,
                    element.verificacao
                  ),
                    (telNovo = false)
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
                @click="inativar(element.codpessoa, element.codpessoatelefone)"
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
                @click="ativar(element.codpessoa, element.codpessoatelefone)"
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
                @click="excluirTel(element.codpessoatelefone)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </q-card>
</template>
