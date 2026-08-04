<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonContent, IonPage, IonItem, IonLabel, IonList } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useProfileStore } from '@/stores/profileStore';
import { useToast } from '@/composables/useToast';
import { useLoading } from '@/composables/useLoading';
import Header from '@/components/layout/Header.vue';
import Heading from '@/components/layout/Heading.vue';
import Container from '@/components/layout/Container.vue';
import BackButton from '@/components/layout/BackButton.vue';
import Button from '@/components/ui/Button.vue';
import ModalDialog from '@/components/layout/ModalDialog.vue';

const authStore = useAuthStore();
const profileStore = useProfileStore();

const router = useRouter();

const { showToast } = useToast();
const { isLoading, withLoading } = useLoading();

const modalRef = ref(null);

const handleDeleteClick = () => {
  modalRef.value?.setOpen(true);
};

const confirmDelete = async () => {
  await withLoading(async () => {
    await profileStore.deleteAccount();
    authStore.clearTokens();
    router.push('/signin');
  });
};
</script>

<template>
  <ion-page>
    <Header>
      <BackButton />
    </Header>

    <ion-content>
      <Container>
        <Heading title="Exclusão de Conta" />

        <div>
          <p><strong>Tem certeza de que deseja excluir sua conta?</strong></p>
          <p>
            Ao confirmar, as seguintes ações serão realizadas:
          </p>
          <ion-item lines="none">
            <ion-label>
              <strong>1. Remoção dos dados pessoais:</strong> Todos os seus dados pessoais armazenados em nossos sistemas serão apagados, incluindo informações de perfil.
            </ion-label>
          </ion-item>
          <ion-item lines="none">
            <ion-label>
              <strong>2. Perda de acesso:</strong> Você não poderá mais acessar sua conta ou utilizar nossos serviços associados ao seu login.
            </ion-label>
          </ion-item>
          <ion-item lines="none">
            <ion-label>
              <strong>3. Remoção dos dados de hábitos:</strong> Todos os seus dados registrados, incluindo hábitos rastreados, metas alcançadas e estatísticas de progresso, serão apagados de forma irreversível.
            </ion-label>
          </ion-item>
          <ion-item lines="none">
            <ion-label>
              <strong>4. Perda de lembretes e notificações:</strong> Qualquer lembrete ou notificação configurada será desativado e não será mais enviado.
            </ion-label>
          </ion-item>
          <ion-item lines="none">
            <ion-label>
              <strong>5. Exclusão de conquistas:</strong> Todas as conquistas desbloqueadas e registros históricos de desempenho serão apagados.
            </ion-label>
          </ion-item>
          <p><strong>Esta ação é irreversível!</strong></p>
          <p>Caso tenha dúvidas ou precise de assistência, clique em <strong>Cancelar</strong> e entre em contato com nosso suporte.</p>
        </div>

        <Button
          color="danger"
          class="ion-margin-top ion-margin-bottom"
          :is-loading="isLoading"
          @click="handleDeleteClick"
        >
          Confirmar Exclusão
        </Button>
      </Container>
      
      <ModalDialog
        ref="modalRef"
        message="Tem certeza que deseja excluir sua conta? Esta ação é irreversível!"
        @on-confirm="confirmDelete"
      />
    </ion-content>
  </ion-page>
</template>
