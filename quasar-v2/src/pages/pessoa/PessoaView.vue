<template>
     <div class="q-pa-md q-gutter-sm" style="text-align: left; padding-top: 20px;">
        <q-btn :to="{name: 'pessoa'}" color="grey" icon="list" label="Listagem"   dense flat/>
        <q-btn :to="{name: 'pessoanova'}" color="grey" icon="add" label="Nova"   dense flat/>
        <q-btn @click="editar" color="grey" icon="edit" label="Alterar"   dense flat/>
        <q-btn color="grey" icon="delete" label="Excluir" @click="ExcluirPessoa"   dense flat/>
    </div>
  <q-separator></q-separator>

    <div class="q-pa-md q-gutter-sm" style="padding-top: 20px;">
        <div class="text-h6 text-grey-8">
            Detalhes da Pessoa
        </div>
    </div>
    <q-separator></q-separator>
        <div class="q-pa-md q-gutter-sm" style="padding-top: 20px;">
            <div class="text-h4 text-grey-8">
                {{ listapessoasview.pessoa }}
            </div>
        </div>
    <q-separator></q-separator>
    <div class="q-pa-md float-left" style="max-width: 350px">
        <q-list bordered separator>
        <q-item>
            <q-item-section>
            <q-item-label overline bold>Razão Social</q-item-label>
            <q-item-label>{{ listapessoasview.pessoa }}</q-item-label>
            </q-item-section>
        </q-item>

        <q-item>
            <q-item-section>
            <q-item-label overline>Telefone</q-item-label>
            <q-item-label>{{ listapessoasview.telefone1 }}</q-item-label>
            </q-item-section>
        </q-item>

        <q-item>
            <q-item-section>
            <q-item-label overline>Contato</q-item-label>
            <q-item-label>{{ listapessoasview.telefone2 }}</q-item-label>
            </q-item-section>
        </q-item>

        <q-item>
            <q-item-section>
            <q-item-label overline>CNPJ/CPF</q-item-label>
            <q-item-label>{{ listapessoasview.cnpj }}</q-item-label>
            </q-item-section>
        </q-item>

        <q-item>
            <q-item-section>
            <q-item-label overline>Inscrição Estadual</q-item-label>
            <q-item-label>{{ listapessoasview.ie }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>RNTRC</q-item-label>
            <q-item-label>{{ listapessoasview.rntrc }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Tipo Transportador</q-item-label>
            <q-item-label>{{ listapessoasview.tipotransportador }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Endereço</q-item-label>
            <q-item-label>{{ listapessoasview.endereco + ' N°' + listapessoasview.numero }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Email</q-item-label>
            <q-item-label>{{ listapessoasview.email }}</q-item-label>
            </q-item-section>
        </q-item>
        </q-list>
    </div>
    <div class="q-pa-md float-left" style="max-width: 350px">
        <q-list bordered separator>
        <q-item>
            <q-item-section>
            <q-item-label overline bold>Cliente</q-item-label>
            <q-item-label v-if="listapessoasview.cliente == true">Sim</q-item-label>
            <q-item-label v-else>Não</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Grupo de Cliente</q-item-label>
            <q-item-label>{{ listapessoasview.GrupoCliente }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Nota Fiscal</q-item-label>
            <q-item-label v-if="listapessoasview.notafiscal == 1">Sempre</q-item-label>
            <q-item-label v-else></q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Consumidor Final</q-item-label>
            <q-item-label v-if="listapessoasview.consumidor == true">Sim</q-item-label>
            <q-item-label v-else>Não</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Forma de Pagamento</q-item-label>
            <q-item-label>{{ listapessoasview.codformapagamento }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Desconto</q-item-label>
            <q-item-label>{{ listapessoasview.desconto }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Credito Bloqueado</q-item-label>
            <q-item-label v-if="listapessoasview.creditobloqueado == false">Não</q-item-label>
            <q-item-label v-else>Sim</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Saldo em Aberto</q-item-label>
            <q-item-label>{{ listapessoasview.sld }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Limite de Credito</q-item-label>
            <q-item-label>{{ listapessoasview.credito }}</q-item-label>
            </q-item-section>
        </q-item>
        </q-list>
    </div>

    <div class="q-pa-md float-left" style="max-width: 350px">
        <q-list bordered separator>
        <q-item>
            <q-item-section>
            <q-item-label overline>Primeiro Vencimento</q-item-label>
            <q-item-label>Vazio</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Tolerância de Atraso</q-item-label>
            <q-item-label>{{ listapessoasview.toleranciaatraso + ' '+ 'Dias' }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Mensagem de Venda</q-item-label>
            <q-item-label>{{ listapessoasview.mensagemvenda }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Observações</q-item-label>
            <q-item-label>{{ listapessoasview.observacoes }}</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Vendedor</q-item-label>
            <q-item-label v-if="listapessoasview.vendedor == true">Sim</q-item-label>
            <q-item-label v-else>Não</q-item-label>
            </q-item-section>
        </q-item>
        <q-item>
            <q-item-section>
            <q-item-label overline>Fornecedor</q-item-label>
            <q-item-label v-if="listapessoasview.fornecedor == true">Sim</q-item-label>
            <q-item-label v-else>Não</q-item-label>
            </q-item-section>
        </q-item>
        </q-list>
    </div>
    <br />
    <div class="row float-left" style="width: 100%;">
      
        <q-tabs
          v-model="tab"
          dense
          class="text-grey"
          active-color="primary"
          indicator-color="primary"
          
          narrow-indicator
        >
      
          <q-tab name="hist" label="Histórico de Cobrança" />
          <q-tab name="spc" label="Registro SPC" />
          <q-tab name="certidoes" label="Certidões" />
        </q-tabs>
    </div>
       
        <div class="row float-left" style="width: 100%;">
        <q-tab-panels v-model="tab" animated>
          <q-tab-panel name="hist">
            <div class="text-h6 clear-left">Histórico de Cobranças 
                <q-btn  round color="primary" size="10px" icon="add"   dense/>
            </div>
            Nenhum resultado encontrado.
          </q-tab-panel>

          <q-tab-panel name="spc">
            <div class="text-h6">Registro SPC 
                <q-btn  round color="primary" size="10px" icon="add"   dense/>
            </div>
            Nenhum resultado encontrado.
          </q-tab-panel>

          <q-tab-panel name="certidoes">
            <div class="text-h6">Certidões
                <q-btn  round color="primary" size="10px" icon="add"   dense/>
            </div>
            Nenhum resultado encontrado.
          </q-tab-panel>
        </q-tab-panels>
    </div>   
</template>

<script>
import { ref, onMounted , defineAsyncComponent } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router'

export default {
    setup () {
    const loading = ref(true)
    const $q = useQuasar()
    const route = useRoute()
    const router = useRouter()

    var listapessoasview = ref([])

     // Faz as listagem dos detalhes da pessoa
     const getDetalhesPessoa = async () => {
            $q.loading.show({
              })
            try {
                const {data} = await api.get('v1/pessoa/' + route.params.id )
                loading.value = false
                listapessoasview.value = data.data
                $q.loading.hide()
            } catch (error) {
            $q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'Erro registro não encontrado.'
            })
            router.push('/pessoa/')
            }
        }
       
        const editar = async () => {
            
            if (route.params.id){
              var a = document.createElement('a');
              // a.target="_blank";
              a.href="/#/pessoa/edit/" + route.params.id
              a.click();
              }

        }

        const ExcluirPessoa = async () => {

        $q.dialog({
        title: 'Excluir Pessoa',
        message: 'Tem certeza que deseja excluir essa pessoa?',
        cancel: true,
        persistent: false
        }).onOk(async () => {
         const {data} = await api.delete('v1/pessoa/' + route.params.id)
        
         if(data.result == true){
            $q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'done',
            message: 'Pessoa excluida com sucesso'
          })
          router.push('/pessoa/')
         }
        })

        }
        
        onMounted(() => {
         getDetalhesPessoa()
          
        })

    return {
    listapessoasview,
    tab: ref('hist'),
    editar,
    ExcluirPessoa
    }
  },
}
</script>