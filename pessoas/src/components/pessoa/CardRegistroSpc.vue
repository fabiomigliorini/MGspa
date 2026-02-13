<script setup>
import { ref, onMounted } from "vue";
import { useQuasar } from "quasar";
import { useRoute } from "vue-router";
import { pessoaStore } from "stores/pessoa";
import { guardaToken } from "src/stores";
import { formataDocumetos } from "src/stores/formataDocumentos";
import IconeInfoCriacao from "components/IconeInfoCriacao.vue";

const $q = useQuasar();
const sPessoa = pessoaStore();
const route = useRoute();
const user = guardaToken();
const Documentos = formataDocumetos();

const dialogNovoRegistroSpc = ref(false);
const modelRegistroSpc = ref({});
const editarRegistro = ref(false);
const filtroRegistroSpc = ref("abertos");
const registrosS = ref([]);

const brasil = {
  days: "Domingo_Segunda_Terça_Quarta_Quinta_Sexta_Sábado".split("_"),
  daysShort: "Dom_Seg_Ter_Qua_Qui_Sex_Sáb".split("_"),
  months:
    "Janeiro_Fevereiro_Março_Abril_Maio_Junho_Julho_Agosto_Setembro_Outubro_Novembro_Dezembro".split(
      "_"
    ),
  monthsShort: "Jan_Fev_Mar_Abr_Mai_Jun_Jul_Ago_Set_Out_Nov_Dez".split("_"),
  firstDayOfWeek: 0,
  format24h: true,
  pluralDay: "dias",
};

function filtroSpc() {
  if (filtroRegistroSpc.value == "abertos") {
    let todos = sPessoa.item.RegistroSpc.filter((x) => !x.baixa);
    sPessoa.item.RegistroSpc = todos;
  }
  if (filtroRegistroSpc.value == "todos") {
    sPessoa.item.RegistroSpc = registrosS.value;
  }
}

async function novoRegistroSpc() {
  modelRegistroSpc.value.codpessoa = route.params.id;

  const novoRegistro = { ...modelRegistroSpc.value };

  if (novoRegistro.inclusao) {
    novoRegistro.inclusao = Documentos.dataFormatoSql(novoRegistro.inclusao);
  }
  if (novoRegistro.baixa) {
    novoRegistro.baixa = Documentos.dataFormatoSql(novoRegistro.baixa);
  }

  if (novoRegistro.valor.indexOf(",") > -1) {
    var removeVirgula = novoRegistro.valor.replace(/,([^,]*)$/, ".$1");
    novoRegistro.valor = removeVirgula;
  }

  try {
    const ret = await sPessoa.novoRegistroSpc(route.params.id, novoRegistro);
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Registro Spc criado!",
      });
      dialogNovoRegistroSpc.value = false;
      sPessoa.item.RegistroSpc.push(ret.data.data);
    }
  } catch (error) {
    $q.notify({
      color: "red-5",
      textColor: "white",
      icon: "warning",
      message: error.response.data.message,
    });
  }
}

function editarRegistroSpc(
  codregistrospc,
  valor,
  inclusao,
  baixa,
  observacoes
) {
  dialogNovoRegistroSpc.value = true;
  editarRegistro.value = true;
  modelRegistroSpc.value = {
    codregistrospc: codregistrospc,
    valor: valor,
    inclusao: inclusao ? Documentos.formataDataInput(inclusao) : null,
    baixa: baixa ? Documentos.formataDataInput(baixa) : null,
    observacoes: observacoes,
  };
}

async function salvarRegistro() {
  const editRegistro = { ...modelRegistroSpc.value };

  if (editRegistro.inclusao) {
    editRegistro.inclusao = Documentos.dataFormatoSql(editRegistro.inclusao);
  }
  if (editRegistro.baixa) {
    editRegistro.baixa = Documentos.dataFormatoSql(editRegistro.baixa);
  }

  if (editRegistro.valor.toString().indexOf(",") > -1) {
    var removeVirgula = editRegistro.valor.replace(/,([^,]*)$/, ".$1");
    editRegistro.valor = removeVirgula;
  }

  try {
    const ret = await sPessoa.salvarEdicaoRegistro(
      route.params.id,
      editRegistro.codregistrospc,
      editRegistro
    );
    if (ret.data.data) {
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Registro Spc alterado!",
      });
      const i = sPessoa.item.RegistroSpc.findIndex(
        (item) => item.codregistrospc === modelRegistroSpc.value.codregistrospc
      );
      sPessoa.item.RegistroSpc[i] = ret.data.data;
      dialogNovoRegistroSpc.value = false;
      editarRegistro.value = false;
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

async function excluirRegistro(codregistrospc) {
  $q.dialog({
    title: "Excluir Registro Spc",
    message: "Tem certeza que deseja excluir esse registro?",
    cancel: true,
  }).onOk(async () => {
    try {
      await sPessoa.excluirRegistroSpc(route.params.id, codregistrospc);
      $q.notify({
        color: "green-5",
        textColor: "white",
        icon: "done",
        message: "Registro excluido",
      });
      sPessoa.get(route.params.id);
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

onMounted(() => {
  if (!sPessoa.item) return;
  registrosS.value = sPessoa.item.RegistroSpc;
  let todos = sPessoa.item.RegistroSpc.filter((x) => !x.baixa);
  sPessoa.item.RegistroSpc = todos;
});
</script>

<template>
  <div>
  <q-card bordered>
    <q-card-section class="bg-yellow text-grey-9 q-py-sm">
      <div class="row items-center no-wrap q-gutter-x-sm">
        <q-icon name="gavel" size="sm" />
        <span class="text-subtitle1 text-weight-medium">SPC</span>
        <q-space />
        <q-radio
          v-model="filtroRegistroSpc"
          val="todos"
          label="Todos"
          dense
          @click="filtroSpc()"
        />
        <q-radio
          v-model="filtroRegistroSpc"
          val="abertos"
          label="Abertos"
          dense
          @click="filtroSpc()"
        />
        <q-btn
          flat
          round
          dense
          icon="add"
          size="sm"
          color="grey-9"
          v-if="user.verificaPermissaoUsuario('Publico')"
          @click="
            (dialogNovoRegistroSpc = true),
              (editarRegistro = false),
              (modelRegistroSpc = {})
          "
        />
      </div>
    </q-card-section>

    <q-list separator>
      <template
        v-for="registro in sPessoa.item?.RegistroSpc"
        v-bind:key="registro.codregistrospc"
      >
        <q-item>
          <q-item-section avatar>
            <q-btn
              round
              flat
              icon="gavel"
              color="primary"
            />
          </q-item-section>

          <q-item-section>
            <q-item-label class="text-weight-bold">
              {{
                registro.valor.toLocaleString("pt-br", {
                  style: "currency",
                  currency: "BRL",
                })
              }}

              <!-- INFO -->
              <icone-info-criacao
                :usuariocriacao="registro.usuariocriacao"
                :criacao="registro.criacao"
                :usuarioalteracao="registro.usuarioalteracao"
                :alteracao="registro.alteracao"
              />
            </q-item-label>

            <q-item-label caption v-if="registro.observacoes">
              {{ registro.observacoes }}
            </q-item-label>
            <q-item-label caption v-if="registro.baixa">
              Baixado em:
              <span class="text-weight-bold">{{
                Documentos.formataDatasemHr(registro.baixa)
              }}</span>
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <q-item-label
              caption
              v-if="user.verificaPermissaoUsuario('Publico')"
            >
              <!-- EDITAR -->
              <q-btn
                flat
                dense
                round
                icon="edit"
                size="sm"
                color="grey-7"
                @click="
                  editarRegistroSpc(
                    registro.codregistrospc,
                    registro.valor,
                    registro.inclusao,
                    registro.baixa,
                    registro.observacoes
                  )
                "
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>

              <!-- EXCLUIR -->
              <q-btn
                flat
                dense
                round
                icon="delete"
                size="sm"
                color="grey-7"
                @click="excluirRegistro(registro.codregistrospc)"
              >
                <q-tooltip>Excluir</q-tooltip>
              </q-btn>
            </q-item-label>
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </q-card>

  <!-- Dialog novo Registro Spc -->
  <q-dialog v-model="dialogNovoRegistroSpc">
    <q-card style="min-width: 350px">
      <q-form
        @submit="editarRegistro == false ? novoRegistroSpc() : salvarRegistro()"
      >
        <q-card-section>
          <div v-if="editarRegistro" class="text-h6">Editar Registro Spc</div>
          <div v-else class="text-h6">Novo Registro Spc</div>
        </q-card-section>
        <q-card-section>
          <div class="col-6">
            <q-input
              outlined
              v-model="modelRegistroSpc.inclusao"
              mask="##/##/####"
              label="Inclusão"
              :rules="[
                (val) => (val && val.length > 0) || 'Inclusão obrigatório',
              ]"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-date
                      v-model="modelRegistroSpc.inclusao"
                      :locale="brasil"
                      mask="DD/MM/YYYY"
                    >
                      <div class="row items-center justify-end">
                        <q-btn
                          v-close-popup
                          label="Fechar"
                          color="primary"
                          flat
                        />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input
              outlined
              v-model="modelRegistroSpc.baixa"
              mask="##/##/####"
              class="q-mb-md"
              label="Baixa"
            >
              <template v-slot:append>
                <q-icon name="event" class="cursor-pointer">
                  <q-popup-proxy
                    cover
                    transition-show="scale"
                    transition-hide="scale"
                  >
                    <q-date
                      v-model="modelRegistroSpc.baixa"
                      :locale="brasil"
                      mask="DD/MM/YYYY"
                    >
                      <div class="row items-center justify-end">
                        <q-btn
                          v-close-popup
                          label="Fechar"
                          color="primary"
                          flat
                        />
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input
              outlined
              v-model="modelRegistroSpc.valor"
              label="Valor"
              type="numeric"
              :rules="[
                (val) =>
                  (val !== null && val !== '' && val !== undefined) ||
                  'Valor obrigatório',
              ]"
            />

            <q-input
              outlined
              v-model="modelRegistroSpc.observacoes"
              label="Observações"
              borderless
              autogrow
              type="textarea"
            />
          </div>
        </q-card-section>

        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
  </div>
</template>

<style scoped></style>
