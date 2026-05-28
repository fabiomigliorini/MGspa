<script setup>
import { ref } from 'vue'
import { negocioStore } from 'stores/negocio'
import moment from 'moment/min/moment-with-locales'
moment.locale('pt-br')
import MgSlim from '../../utils/pqina/slim/MgSlim.vue'
import MgAnexoImagem from './MgAnexoImagem.vue'
import { api } from 'boot/axios'
import { abrirPdf } from '@components/abrirPdf'
import { Dialog, Notify } from 'quasar'

const sNegocio = negocioStore()

const arquivosPdf = ref([])
const dialogConfissao = ref(false)
const dialogImagem = ref(false)
const dialogPdf = ref(false)

const abrirAnexoPdf = async (anexo) => {
  await abrirPdf(
    api,
    `/api/v1/pdv/negocio/${sNegocio.negocio.codnegocio}/anexo/pdf/${anexo}`,
    {},
    { title: 'PDF', size: 'a4' },
  )
}

const excluirAnexo = (pasta, anexo) => {
  Dialog.create({
    title: 'Excluir',
    message: 'Tem certeza que você deseja excluir esse anexo?',
    cancel: true,
  }).onOk(() => {
    sNegocio.deleteAnexo(pasta, anexo)
  })
}

const getBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => resolve(reader.result)
    reader.onerror = (error) => reject(error)
  })
}

const anexarPdf = async () => {
  if (arquivosPdf.value.length <= 0) {
    return
  }
  dialogPdf.value = false
  arquivosPdf.value.forEach(async (arquivo) => {
    const base64 = await getBase64(arquivo)
    await sNegocio.uploadAnexo('pdf', '', base64)
  })
  arquivosPdf.value = []
}

const onRejected = (rejectedEntries) => {
  Notify.create({
    type: 'negative',
    message: `${rejectedEntries.length} file(s) did not pass validation constraints`,
  })
}

const ratioOptions = ref([
  { value: '1:2', label: 'Confissão Impressora Térmica' },
  { value: 'free', label: 'Livre' },
])

const confissaoRatio = ref('1:2')
</script>
<template>
  <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
    <q-dialog v-model="dialogConfissao">
      <q-card>
        <q-card-section>
          <div class="text-h6">Anexar Foto da Confissão de Dívida</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
            outlined
            class="q-mb-md"
            :options="ratioOptions"
            v-model="confissaoRatio"
            label="Tamanho"
            map-options
            emit-value
          />
          <mg-slim :ratio="confissaoRatio" pasta="confissao" @upload="dialogConfissao = false" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogImagem">
      <q-card>
        <q-card-section>
          <div class="text-h6">Anexar Imagem</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <mg-slim ratio="free" pasta="imagem" @upload="dialogImagem = false" />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogPdf">
      <q-card>
        <q-card-section>
          <div class="text-h6">Anexar PDF</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-file
            style="min-width: 250px"
            v-model="arquivosPdf"
            outlined
            label="Selecione os arquivos PDF"
            multiple
            use-chips
            accept=".pdf"
            max-file-size="1024000"
            @rejected="onRejected"
            @update:model-value="anexarPdf"
            counter
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- CARD -->
    <q-card>
      <q-item>
        <q-item-section avatar>
          <q-avatar icon="mdi-paperclip" color="secondary" text-color="white" />
        </q-item-section>
        <q-item-section>
          <q-item-label> Anexos </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-item>
        <q-item-section>
          <q-item-label caption>
            Clique em um dos botões abaixo para anexar os documentos relativos à este negócio!
          </q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-card-actions vertical>
        <q-btn
          flat
          color="primary"
          align="left"
          style="width: 100%"
          icon="mdi-file-sign"
          @click="dialogConfissao = true"
        >
          &nbsp; Confissão
        </q-btn>
        <q-btn
          flat
          color="primary"
          align="left"
          style="width: 100%"
          icon="mdi-file-pdf-box"
          @click="dialogPdf = true"
        >
          &nbsp; PDF
        </q-btn>
        <q-btn
          flat
          color="primary"
          align="left"
          style="width: 100%"
          icon="image"
          @click="dialogImagem = true"
        >
          &nbsp; Imagem
        </q-btn>
      </q-card-actions>
    </q-card>
  </div>

  <!-- ASSINATURA CONFISSAO DE DIVIDA -->
  <MgAnexoImagem
    v-for="anexo in sNegocio.negocio.anexos.confissao"
    :key="anexo"
    pasta="confissao"
    :anexo="anexo"
    icon="mdi-file-sign"
    label="Anexo"
    caption="Assinatura"
    @excluir="excluirAnexo('confissao', anexo)"
  />

  <!-- OUTRAS IMAGENS -->
  <MgAnexoImagem
    v-for="anexo in sNegocio.negocio.anexos.imagem"
    :key="anexo"
    pasta="imagem"
    :anexo="anexo"
    icon="image"
    label="Anexo"
    caption="Imagem"
    @excluir="excluirAnexo('imagem', anexo)"
  />

  <!-- PDF -->
  <div
    v-for="anexo in sNegocio.negocio.anexos.pdf"
    :key="anexo"
    class="col-xs-12 col-sm-6 col-md-4 col-lg-3 col-xl-2"
  >
    <q-card>
      <q-item clickable v-ripple @click="abrirAnexoPdf(anexo)">
        <q-item-section avatar>
          <q-avatar icon="mdi-file-pdf-box" color="secondary" text-color="white" />
        </q-item-section>

        <q-item-section>
          <q-item-label class="ellipsis">PDF</q-item-label>
        </q-item-section>
      </q-item>
      <q-separator inset />
      <q-item
        clickable
        v-ripple
        @click="abrirAnexoPdf(anexo)"
        style="height: 250px"
        class="q-pa-none"
      >
        <q-icon name="picture_as_pdf" color="grey-7" size="150px" style="margin: auto" />
      </q-item>
      <q-card-actions>
        <q-btn-group flat>
          <q-btn
            dense
            flat
            round
            icon="delete"
            color="negative"
            @click="excluirAnexo('pdf', anexo)"
          >
            <q-tooltip class="bg-accent">Excluir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-card-actions>
    </q-card>
  </div>
</template>
