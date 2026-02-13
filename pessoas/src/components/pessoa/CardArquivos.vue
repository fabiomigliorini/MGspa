<script setup>
import { onBeforeUnmount, computed, onMounted } from "vue";
import { Notify, Dialog } from "quasar";
import { ref } from "vue";
import { guardaToken } from "src/stores";
import { pessoaStore } from "stores/pessoa";
import { api } from "src/boot/axios";
import moment from "moment";

const sPessoa = pessoaStore();
const user = guardaToken();
const permitido = user.verificaPermissaoUsuario("Financeiro");

const tab = ref("ativos");

const files = ref(null);
const uploadProgress = ref([]);
const uploading = ref(null);

const dialogEditar = ref(false);
const model = ref({});

const arquivos = ref({
  ativos: [],
  inativos: [],
});

const fileSize = function (bytes) {
  if (bytes == 0) {
    return "0.00 B";
  }
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return (bytes / Math.pow(1024, e)).toFixed(2) + " " + " KMGTP".charAt(e) + "B";
};

const cleanUp = () => {
  clearTimeout(uploading.value);
  buscarListagem();
};

const cancelFile = (index) => {
  uploadProgress.value[index] = {
    ...uploadProgress.value[index],
    error: true,
    color: "orange-2",
  };
};

const updateFiles = (newFiles) => {
  files.value = newFiles;
  uploadProgress.value = (newFiles || []).map((file) => ({
    error: false,
    color: "green-2",
    percent: 0,
    icon:
      file.type.indexOf("video/") === 0
        ? "movie"
        : file.type.indexOf("image/") === 0
          ? "mdi-file-image"
          : file.type.indexOf("application/pdf") === 0
            ? "mdi-file-pdf"
            : "mdi-file",
  }));
};

const getBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });
};

const uploadFile = async (index) => {
  const file = files.value[index];
  const progress = uploadProgress.value[index];
  try {
    const base64 = await getBase64(file);
    const payload = {
      nome: file.name,
      base64: base64,
    };
    const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo`;
    const ret = await api.post(url, payload);
    arquivos.value = ret.data.data;
    progress.percent = 0.5;
    setTimeout(() => {
      progress.color = "green-2";
      progress.percent = 1;
      progress.error = false;
    }, 1000);
  } catch (error) {
    Notify.create({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Falha ao carregar os anexos!",
    });
    console.log(error);
    setTimeout(() => {
      progress.color = "red-2";
      progress.percent = 1;
      progress.error = true;
    }, 1000);
  }
};

const upload = () => {
  cleanUp();
  let index = 0;
  const progress = uploadProgress.value[index];
  for (const file in files.value) {
    progress.color = "yellow-8";
    progress.percent = 0;
    progress.error = false;
    uploadFile(index);
    index++;
  }
};

const icone = (arquivo) => {
  switch (arquivo.tipo) {
    case "pdf":
      return "mdi-file-pdf";
    case "jpg":
    case "jpeg":
    case "png":
      return "mdi-file-image";
    default:
      return "mdi-file";
  }
};

const abrir = async (status, arquivo) => {
  const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/${status}/${arquivo.nome}`;
  const response = await api.get(url, {
    responseType: "blob",
  });
  window.open(URL.createObjectURL(response.data));
};

const editar = (arquivo) => {
  model.value = { ...arquivo };
  dialogEditar.value = true;
};

const salvar = async (evt) => {
  evt.preventDefault();

  Dialog.create({
    title: "Salvar",
    message: "Tem certeza que deseja salvar as alterações?",
    cancel: true,
  }).onOk(async () => {
    try {
      const payload = {
        label: model.value.label,
        observacoes: model.value.observacoes,
      };
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/ativo/${model.value.nome}`;
      const ret = await api.put(url, payload);
      arquivos.value = ret.data.data;
      dialogEditar.value = false;
    } catch (error) {
      console.log(error);
      Notify.create({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao salvar!",
      });
    }
  });
};

const inativar = (arquivo) => {
  Dialog.create({
    title: "Excluir",
    message: "Tem certeza que deseja mover o arquivo para lixeira?",
    cancel: true,
  }).onOk(async () => {
    try {
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/ativo/${arquivo.nome}`;
      const ret = await api.delete(url);
      arquivos.value = ret.data.data;
    } catch (error) {
      console.log(error);
      Notify.create({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao mover o arquivo para lixeira!",
      });
    }
  });
};

const restaurar = (arquivo) => {
  Dialog.create({
    title: "Restaurar",
    message: "Deseja restaurar o arquivo?",
    cancel: true,
  }).onOk(async () => {
    try {
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/inativo/${arquivo.nome}`;
      const ret = await api.patch(url);
      arquivos.value = ret.data.data;
    } catch (error) {
      console.log(error);
      Notify.create({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao restaurar o arquivo!",
      });
    }
  });
};

const excluir = (arquivo) => {
  Dialog.create({
    title: "Excluir",
    message:
      "Tem certeza que deseja excluir permantentemente o arquivo? Essa operação não poderá ser desfeita!",
    cancel: true,
  }).onOk(async () => {
    try {
      const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo/inativo/${arquivo.nome}`;
      const ret = await api.delete(url);
      arquivos.value = ret.data.data;
    } catch (error) {
      console.log(error);
      Notify.create({
        color: "red-5",
        textColor: "white",
        icon: "error",
        message: "Falha ao excluir o arquivo!",
      });
    }
  });
};

const isUploading = computed(() => uploading.value !== null);

const canUpload = computed(() => files.value !== null);

onBeforeUnmount(() => {
  clearTimeout(uploading.value);
});

onMounted(() => {
  buscarListagem();
});

const buscarListagem = async () => {
  if (!sPessoa.item) return;
  try {
    const url = `v1/pessoa/${sPessoa.item.codpessoa}/anexo`;
    const ret = await api.get(url);
    arquivos.value = ret.data.data;
    if (arquivos.value.ativos.length > 0 || !permitido) {
      tab.value = "ativos";
    } else {
      tab.value = "novo";
    }
  } catch (error) {
    Notify.create({
      color: "red-5",
      textColor: "white",
      icon: "error",
      message: "Falha ao buscar a listagem dos anexos!",
    });
    console.log(error);
  }
};
</script>

<template>
  <div>
  <q-card bordered>
    <q-card-section class="bg-yellow text-grey-9 q-py-none q-px-none">
      <q-tabs
        v-model="tab"
        class="text-grey-9"
        active-color="grey-9"
        indicator-color="grey-9"
        inline-label
        align="justify"
        dense
      >
        <q-tab icon="attachment" label="Anexos" name="ativos" />
        <q-tab icon="add" label="Novo" name="novo" v-if="permitido" />
        <q-tab icon="delete" label="Lixeira" name="inativos" />
      </q-tabs>
    </q-card-section>

    <q-separator />

    <q-tab-panels v-model="tab" animated>
      <!-- ATIVOS -->
      <q-tab-panel name="ativos" class="q-pa-none">
        <q-list separator v-if="arquivos.ativos.length > 0">
          <template v-for="arquivo in arquivos.ativos" :key="arquivo.nome">
            <q-item>
              <q-item-section avatar>
                <q-btn
                  round
                  flat
                  :icon="icone(arquivo)"
                  color="primary"
                  @click="abrir('ativos', arquivo)"
                />
              </q-item-section>

              <q-item-section class="cursor-pointer" @click="abrir('ativos', arquivo)">
                <q-item-label>{{ arquivo.label }}</q-item-label>
                <q-item-label
                  caption
                  v-if="arquivo.observacoes"
                  style="white-space: pre-wrap"
                >
                  {{ arquivo.observacoes }}
                </q-item-label>
                <q-item-label caption>
                  {{ fileSize(arquivo.size) }} |
                  {{ moment(arquivo.lastModified).fromNow() }} |
                  {{ moment(arquivo.lastModified).format("llll") }}
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-item-label caption v-if="permitido">
                  <q-btn
                    flat
                    dense
                    round
                    icon="edit"
                    size="sm"
                    color="grey-7"
                    @click="editar(arquivo)"
                  >
                    <q-tooltip>Editar</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    icon="delete"
                    size="sm"
                    color="grey-7"
                    @click="inativar(arquivo)"
                  >
                    <q-tooltip>Mover para lixeira</q-tooltip>
                  </q-btn>
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-list>
        <div v-else class="q-pa-md text-center text-grey">
          Nenhum anexo...
        </div>
      </q-tab-panel>

      <!-- NOVO -->
      <q-tab-panel name="novo" v-if="permitido">
        <div class="column items-start q-gutter-y-md">
          <q-file
            :model-value="files"
            @update:model-value="updateFiles"
            label="Selecione os arquivos"
            outlined
            multiple
            :clearable="!isUploading"
            style="width: 100%; max-width: 100%"
            counter
          >
            <template v-slot:file="{ index, file }">
              <q-chip
                class="full-width q-my-xs"
                :removable="isUploading && uploadProgress[index].percent < 1"
                square
                @remove="cancelFile(index)"
              >
                <q-linear-progress
                  class="absolute-full full-height"
                  :value="uploadProgress[index].percent"
                  :color="uploadProgress[index].color"
                  track-color="grey-2"
                />

                <q-avatar>
                  <q-icon :name="uploadProgress[index].icon" />
                </q-avatar>

                <div class="ellipsis relative-position">
                  {{ file.name }}
                </div>

                <q-tooltip>
                  {{ file.name }}
                </q-tooltip>
              </q-chip>
            </template>

            <template v-slot:after v-if="canUpload">
              <q-btn
                color="primary"
                dense
                icon="cloud_upload"
                round
                @click="upload"
                :disable="!canUpload"
                :loading="isUploading"
              />
            </template>
          </q-file>
        </div>
      </q-tab-panel>

      <!-- INATIVOS -->
      <q-tab-panel name="inativos" class="q-pa-none">
        <q-list separator v-if="arquivos.inativos.length > 0">
          <template v-for="arquivo in arquivos.inativos" :key="arquivo.nome">
            <q-item>
              <q-item-section avatar>
                <q-btn
                  round
                  flat
                  :icon="icone(arquivo)"
                  color="primary"
                  @click="abrir('inativos', arquivo)"
                />
              </q-item-section>

              <q-item-section class="cursor-pointer" @click="abrir('inativos', arquivo)">
                <q-item-label>{{ arquivo.label }}</q-item-label>
                <q-item-label
                  caption
                  v-if="arquivo.observacoes"
                  style="white-space: pre-wrap"
                >
                  {{ arquivo.observacoes }}
                </q-item-label>
                <q-item-label caption>
                  {{ fileSize(arquivo.size) }} |
                  {{ moment(arquivo.lastModified).fromNow() }} |
                  {{ moment(arquivo.lastModified).format("llll") }}
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                <q-item-label caption v-if="permitido">
                  <q-btn
                    flat
                    dense
                    round
                    icon="mdi-restore"
                    size="sm"
                    color="grey-7"
                    @click="restaurar(arquivo)"
                  >
                    <q-tooltip>Restaurar</q-tooltip>
                  </q-btn>
                  <q-btn
                    flat
                    dense
                    round
                    icon="mdi-delete-forever"
                    size="sm"
                    color="grey-7"
                    @click="excluir(arquivo)"
                  >
                    <q-tooltip>Excluir permanentemente</q-tooltip>
                  </q-btn>
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
        </q-list>
        <div v-else class="q-pa-md text-center text-grey">
          Lixeira vazia...
        </div>
      </q-tab-panel>
    </q-tab-panels>
  </q-card>

  <q-dialog v-model="dialogEditar">
    <q-card style="min-width: 350px">
      <q-form @submit="salvar">
        <q-card-section>
          <div class="text-h6">Detalhes do Arquivo</div>
        </q-card-section>
        <q-card-section>
          <q-input
            outlined
            v-model="model.label"
            autofocus
            label="Nome"
            :rules="[(val) => (val && val.length > 0) || 'Obrigatório']"
          />
          <q-input
            outlined
            v-model="model.observacoes"
            autofocus
            label="Observações"
            type="textarea"
          />
        </q-card-section>
        <q-card-actions align="right" class="text-primary">
          <q-btn flat label="Cancelar" v-close-popup tabindex="-1" />
          <q-btn flat label="Salvar" type="submit" />
        </q-card-actions>
      </q-form>
    </q-card>
  </q-dialog>
  </div>
</template>

<style scoped></style>
