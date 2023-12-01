
<template>
  <MGLayout>
    <template #content>
      <div class="q-pa-md q-gutter-sm" style="text-align: left; padding-top: 40px;">
        <!-- <q-btn :to="{ name: 'pessoanova' }" round color="primary" icon="add" dense /> -->
        <q-btn round color="primary" icon="add" dense />
        <q-btn color="primary" label="Importar" @click="dialogimportar = true" />
      </div>
      <q-separator></q-separator>
      <div class="q-pa-md q-gutter-sm" style="padding-top: 20px;">
        <div class="text-h6 text-grey-8">
          Pessoas
        </div>
      </div>
      <q-separator></q-separator>
      <!-- LISTAGEM DAS PESSOAS -->
      <div class="col-md-3 items-start q-gutter-md text-left">
        <q-infinite-scroll @load="onLoad" class="q-pa-md row items-start q-gutter-md text-left">
          <q-card class="col-md-3 bg-primary text-white" v-for="(listagempessoas, index) in listapessoas"
            v-bind:key="index">
            <q-card-section>
              <q-btn @click="urlviewpessoa(listagempessoas.codpessoa)" flat>
                <div class="text-h5 ellipsis">{{ listagempessoas.fantasia }}</div>
              </q-btn>
              <div class="text-caption text-right">{{ listagempessoas.cnpj }}</div>
              <div class="text-caption text-right">{{ listagempessoas.ie }}</div>
              <div class="text-h8">{{ listagempessoas.telefone1 + '/' + listagempessoas.telefone2 }}</div>
              <div class="text-caption">{{ listagempessoas.endereco }}</div>
            </q-card-section>
            <q-separator dark />
            <!-- <q-card-actions>
        <q-btn flat>Editar</q-btn>
        <q-btn flat>Remover</q-btn>
      </q-card-actions> -->
          </q-card>
          <template v-slot:loading>
            <div class="q-pa-md row justify-center">
              <q-spinner-dots color="primary" size="70px" />
            </div>
          </template>
        </q-infinite-scroll>
      </div>
      <q-separator />
      <!--  ABRE A JANELA PARA IMPORTAÇÃO DE CADASTRO SEFAZ -->
      <q-dialog v-model="dialogimportar" @keyup.enter="ImportarSefaz">
        <q-card>
          <q-card-section>
            <div class="text-h6">Importar Pessoa</div>
            <div class="text-caption">Importar cadastro da Receita Federal ou Sintegra</div>
          </q-card-section>

          <q-card-section class="q-pt-none">
            <q-input label="CNPJ" mask="##.###.###/####-##" dense v-model="importarsefazmodel.cnpj" autofocus
              @keyup.enter="dialogimportar = false" unmasked-value />
            <q-input label="CPF" mask="###.###.###-##" dense v-model="importarsefazmodel.cpf"
              @keyup.enter="dialogimportar = false" unmasked-value />
            <q-input label="IE" dense v-model="importarsefazmodel.ie" @keyup.enter="dialogimportar = false" />
            <q-select label="UF" dense v-model="importarsefazmodel.uf" :options="[
              { label: 'Acre', value: 'AC' },
              { label: 'Alagoas', value: 'AL' },
              { label: 'Amapá', value: 'AP' },
              { label: 'Amazonas', value: 'AM' },
              { label: 'Bahia', value: 'BA' },
              { label: 'Espírito Santo', value: 'ES' },
              { label: 'Goiás', value: 'GO' },
              { label: 'Maranhão', value: 'MA' },
              { label: 'Mato Grosso', value: 'MT' },
              { label: 'Mato Grosso do Sul', value: 'MS' },
              { label: 'Minas Gerais', value: 'MG' },
              { label: 'Pará', value: 'PA' },
              { label: 'Paraíba', value: 'PB' },
              { label: 'Paraná', value: 'PR' },
              { label: 'Pernambuco', value: 'PE' },
              { label: 'Piauí', value: 'PI' },
              { label: 'Rio de Janeiro', value: 'RJ' },
              { label: 'Rio Grande do Norte', value: 'RN' },
              { label: 'Rio Grande do Sul', value: 'RS' },
              { label: 'Rondônia', value: 'RO' },
              { label: 'Roraima', value: 'RR' },
              { label: 'Santa Catarina', value: 'SC' },
              { label: 'São Paulo', value: 'SP' },
              { label: 'Sergipe', value: 'SE' },
              { label: 'Tocantins', value: 'TO' },
              { label: 'Distrito Federal', value: 'DF' },
              { label: 'Pará', value: 'PA' }
            ]" autofocus @keyup.enter="dialogimportar = false" />
            <q-select label="Filial" v-model="importarsefazmodel.codfilial" dense autofocus
              @keyup.enter="dialogimportar = false" />
          </q-card-section>

          <q-card-actions align="right" class="text-primary">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn flat label="Importar" @click="ImportarSefaz" v-close-popup />
          </q-card-actions>
        </q-card>
      </q-dialog>
      <!-- Menu Drawer personalizado filtro -->
    </template>
    <template #drawer>
      <q-separator></q-separator>
      <q-item-label header>Filtro Pessoa</q-item-label>
      <q-separator></q-separator>
      <br>

      <div class="q-pa-md-float" @keyup.enter="filtropessoa">
        <q-input filled v-model="filtro.codpessoa" label="#" />
        <q-input filled v-model="filtro.nome" label="Nome" autofocus unmasked-value />
        <q-input filled v-model="filtro.cnpj" label="Cnpj/Cpf" unmasked-value />
        <q-input filled v-model="filtro.email" label="Email" />
        <q-input filled v-model="filtro.fone" label="Fone" />
      </div>
      <q-separator></q-separator>
      <br>
      <q-separator></q-separator>
      <div class="col-sm-4 col-lg-6 q-py-md text-center">
        <q-btn color="primary" class="text-center" icon="search" label="Pesquisar" @click="filtropessoa" />
      </div>
    </template>
  </MGLayout>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import { Notify } from 'quasar'
import { useRouter } from 'vue-router'
import { guardaToken } from 'src/stores'

export default {
  components: { MGLayout: defineAsyncComponent(() => import('layouts/MGLayout.vue')) },

  setup() {

    const loading = ref(true)
    const $q = useQuasar()
    const router = useRouter()
    const auth = guardaToken()

    const listapessoas = ref([])

    const filtro = ref({

    })

    const importarsefazmodel = ref({
      codfilial: '101'
    })

    // Faz as listagem de todas as pessoas 
    const getValores = async () => {
      $q.loading.show({
      })
      try {
        const { data } = await api.get('v1/pessoa')
        loading.value = false
        listapessoas.value = data.data
        $q.loading.hide()
      } catch (error) {
        console.log(error)
      }
    }

    //Monta a URL para vizualizar detalhes da pessoa
    const urlviewpessoa = async (codpessoa) => {

      if (codpessoa) {
        var a = document.createElement('a');
        a.href = "/#/pessoa/" + codpessoa
        a.click();
      }
    }

    // Pega o código da filial pelo usuario logado
    const codfilial = async () => {

      const codfilial = await auth.verificaToken()
      // if(codfilial.codfilial){
      //   importarsefazmodel.value.codfilial = codfilial.codfilial
      // }

    }


    // Pesquisa de pessoa pelo filtro
    const filtropessoa = async () => {

      $q.loading.show({
      })

      try {
        if (filtro.value.cnpj) {
          filtro.value.cnpj = filtro.value.cnpj.replace(/[^\d]+/g, '')
          const { data } = await api.get('v1/pessoa/search?cnpj=' + filtro.value.cnpj)

          if (data.total > 0) {
            listapessoas.value = data.data
            $q.loading.hide()
            return
          } else {
            $q.notify({
              color: 'red-5',
              textColor: 'white',
              icon: 'warning',
              message: 'Nenhum registro encontrado!'
            })
            $q.loading.hide()
          }
        }

        if (filtro.value.codpessoa) {

          const { data } = await api.get('v1/pessoa/search?codpessoa=' + filtro.value.codpessoa)

          if (data.total > 0) {
            listapessoas.value = data.data
            $q.loading.hide()
            return
          } else {
            $q.notify({
              color: 'red-5',
              textColor: 'white',
              icon: 'warning',
              message: 'Nenhum registro encontrado!'
            })
            $q.loading.hide()
          }
        }

        if (filtro.value.email) {

          const { data } = await api.get('v1/pessoa/search?email=' + filtro.value.email)

          if (data.total > 0) {
            listapessoas.value = data.data
            $q.loading.hide()
            return
          } else {
            $q.notify({
              color: 'red-5',
              textColor: 'white',
              icon: 'warning',
              message: 'Nenhum registro encontrado!'
            })
            $q.loading.hide()
          }
        }

        const { data } = await api.get('v1/pessoa/search?search=' + filtro.value.nome)

        if (data.total > 0) {
          listapessoas.value = data.data
          $q.loading.hide()
          return
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'Nenhum registro encontrado!'
          })
          $q.loading.hide()
        }
      } catch (error) {
        console.log(error)
      }

      // setTimeout(async () => {
      //     const paginapessoa = index++
      //     const query = `?page=${paginapessoa}`
      //     const {data} = await api.get('v1/pessoa' + query)
      //     listapessoas.value.push(...data.data)
      //     done()
      //   }, 2000)
    }

    // Importa cadastro da sefaz/receitaws
    const ImportarSefaz = async () => {
      $q.loading.show({
      })
      try {
        const { data } = await api.post('v1/pessoa/importar', importarsefazmodel.value)

        if (data.data && data.data.length > 0) {
          $q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'done',
            message: data.data.length + ' cadastro(s) importados!'
          })
          listapessoas.value = data.data
          $q.loading.hide()
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'Erro ao importar cadastro, verifique se o cnpj ou cpf está correto'
          })
          $q.loading.hide()
        }
      } catch (error) {
        if (error.response.data.message) {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: error.response.data.message ?? 'Erro ao importar cadastro'
          })
          $q.loading.hide()
        } else {
          $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'Erro interno importar cadastro, por favor tente novamente mais tarde'
          })
          $q.loading.hide()
        }
      }
    }

    onMounted(() => {
      // getValores()
      codfilial()
    })

    return {
      model: ref(null),
      listapessoas,
      codfilial,
      importarsefazmodel,
      filtro,
      ImportarSefaz,
      urlviewpessoa,
      onLoad(index, done) {
        setTimeout(async () => {
          const paginapessoa = index++
          const query = `?page=${paginapessoa}`
          const { data } = await api.get('v1/pessoa' + query)
          listapessoas.value.push(...data.data)
          done()
        }, 2000)
      },
      filtropessoa,
      dialogimportar: ref(false)
    }
  },
}
</script>