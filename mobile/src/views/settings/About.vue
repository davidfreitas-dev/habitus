<template>
  <ion-page>
    <Header>
      <BackButton />
    </Header>

    <ion-content>
      <Container>
        <Segment v-model="selectedSegment" :segments="segments" />

        <div v-if="selectedSegment === 'first'" class="ion-padding">
          <h1>Política de Privacidade</h1>
          <p><em>Última atualização: 13 de julho de 2026</em></p>
          <section>
            <h2>Informações Gerais</h2>
            <p>A sua privacidade é importante para nós. Esta Política de Privacidade explica como o aplicativo *Habitus - Rastreador de Hábitos* ("nós", "nosso"), desenvolvido por David Freitas, coleta, utiliza e protege suas informações. Ao usar nosso Aplicativo, você concorda com a coleta e uso de informações de acordo com esta política.</p>
          </section>
          <section>
            <h2>Coleta e Uso de Informações</h2>
            <p>Para fornecer e melhorar nosso serviço, coletamos os seguintes tipos de informações:</p>
            <ul>
              <li><strong>Informações Pessoais:</strong> Ao criar uma conta, solicitamos seu nome, endereço de e-mail e uma senha (armazenada de forma segura). Utilizamos esses dados para identificar você, permitir o acesso à sua conta e nos comunicarmos sobre o serviço.</li>
              <li><strong>Dados de Hábitos:</strong> Armazenamos os hábitos e o progresso que você registra no Aplicativo. Esses dados são essenciais para o funcionamento do serviço e para que você possa acompanhar sua evolução.</li>
              <li><strong>Dados de Uso e Técnicos:</strong> Coletamos informações como seu endereço IP, tipo de dispositivo, sistema operacional e interações com o Aplicativo. Esses dados nos ajudam a entender como nosso serviço é utilizado, a diagnosticar problemas técnicos e a melhorar a experiência do usuário.</li>
            </ul>
            <p>O Aplicativo não coleta informações de localização precisa do dispositivo.</p>
          </section>
          <section>
            <h2>Direitos do Usuário</h2>
            <p>Respeitamos seus direitos sobre seus dados. A qualquer momento, você pode:</p>
            <ul>
              <li><strong>Acessar e Corrigir:</strong> Você pode visualizar e editar suas informações de perfil diretamente no aplicativo.</li>
              <li><strong>Excluir:</strong> Você pode excluir sua conta e todos os dados associados através da seção de configurações do Aplicativo. A exclusão é permanente.</li>
            </ul>
          </section>
          <section>
            <h2>Retenção e Exclusão de Dados</h2>
            <p>Manteremos suas informações apenas pelo tempo necessário para fornecer o serviço. Se você decidir excluir sua conta, todos os seus dados pessoais e de hábitos serão excluídos permanentemente de nossos servidores em um prazo de até 30 dias.</p>
          </section>
          <section>
            <h2>Terceiros</h2>
            <p>Os dados podem ser compartilhados com terceiros confiáveis para análise e melhorias, respeitando esta política de privacidade.</p>
            <p>Serviços de terceiros utilizados:</p>
            <ul>
              <li><a href="https://www.google.com/policies/privacy/" target="_blank">Google Play Services</a></li>
            </ul>
          </section>
          <section>
            <h2>Privacidade Infantil</h2>
            <p>Nossos serviços não se destinam a menores de 13 anos. Não coletamos intencionalmente informações de identificação pessoal de crianças menores de 13 anos. Se descobrirmos que uma criança menor de 13 anos nos forneceu informações pessoais, as excluiremos imediatamente de nossos servidores.</p>
          </section>
          <section>
            <h2>Segurança</h2>
            <p>O Provedor de Serviço implementa medidas físicas, eletrônicas e procedimentais para proteger as informações coletadas. No entanto, lembre-se de que nenhum método de transmissão pela internet ou de armazenamento eletrônico é 100% seguro.</p>
          </section>
          <section>
            <h2>Alterações</h2>
            <p>Esta política pode ser atualizada periodicamente. Notificaremos você sobre quaisquer alterações, publicando a nova política de privacidade nesta página. É aconselhável revisar esta página periodicamente para verificar quaisquer alterações.</p>
          </section>
          <section>
            <h2>Contato</h2>
            <p>Para dúvidas ou sugestões sobre privacidade, envie um e-mail para <a href="mailto:davidfreitas.dev@outlook.com">davidfreitas.dev@outlook.com</a>.</p>
          </section>
        </div>

        <div v-if="selectedSegment === 'second'" class="ion-padding">
          <h1>Termos de Uso</h1>
          <p><em>Última atualização: 13 de julho de 2026</em></p>
          <section>
            <h2>1. Acordo com os Termos</h2>
            <p>Ao baixar e utilizar o aplicativo *Habitus - Rastreador de Hábitos*, você concorda em cumprir estes Termos de Uso. Se você não concorda com estes termos, não utilize o Aplicativo.</p>
          </section>
          <section>
            <h2>2. Propriedade Intelectual</h2>
            <p>O Aplicativo e seu conteúdo original, funcionalidades e design são de propriedade de David Freitas e protegidos por leis de direitos autorais. Você não tem permissão para copiar, modificar, distribuir ou criar trabalhos derivados baseados no Aplicativo.</p>
          </section>
          <section>
            <h2>3. Contas de Usuário</h2>
            <p>Você é responsável por manter a confidencialidade de sua conta e senha. Você concorda em aceitar a responsabilidade por todas as atividades que ocorram em sua conta.</p>
          </section>
          <section>
            <h2>4. Isenção de Garantia</h2>
            <p>O Aplicativo é fornecido "COMO ESTÁ" e "COMO DISPONÍVEL", sem garantias de qualquer tipo, expressas ou implícitas. Não garantimos que o serviço será ininterrupto, seguro ou livre de erros.</p>
          </section>
          <section>
            <h2>5. Limitação de Responsabilidade</h2>
            <p>Em nenhuma circunstância o provedor do serviço será responsável por quaisquer danos diretos, indiretos, incidentais ou consequentes resultantes do uso ou da incapacidade de usar o Aplicativo.</p>
          </section>
          <section>
            <h2>6. Alterações nos Termos</h2>
            <p>Reservamo-nos o direito de modificar estes termos a qualquer momento. Notificaremos sobre as alterações publicando os novos termos nesta página. É sua responsabilidade revisar estes Termos de Uso periodicamente.</p>
          </section>
          <section>
            <h2>7. Contato</h2>
            <p>Para dúvidas ou sugestões sobre os Termos de Uso, envie um e-mail para <a href="mailto:davidfreitas.dev@outlook.com">davidfreitas.dev@outlook.com</a>.</p>
          </section>
        </div>
      </Container>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { ref } from 'vue';
import { IonPage, IonContent, IonLabel, IonSegment, IonSegmentButton } from '@ionic/vue';
import Header from '@/components/layout/Header.vue';
import Container from '@/components/layout/Container.vue';
import Segment from '@/components/ui/Segment.vue';
import BackButton from '@/components/layout/BackButton.vue';

const segments = ref([
  { 
    label: 'Política de Privacidade', 
    value: 'first' 
  },
  { 
    label: 'Termos de Uso', 
    value: 'second' 
  }
]);

const selectedSegment = ref('first');
</script>

<style scoped>
ion-content {
  overflow-y: auto;
}

h1 {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-primary);
  margin-bottom: 1rem;
}

h2 {
  font-size: 1.2rem;
  color: var(--color-text-primary);
  font-weight: 600;
  margin-bottom: 0.5rem;
}

p, ul {
  font-size: 1rem;
  color: var(--color-text-secondary);
  margin-bottom: 1rem;
}

ul {
  list-style-type: disc;
}

a {
  color: var(--color-success);
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}
</style>
