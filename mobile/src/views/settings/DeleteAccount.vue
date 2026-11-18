<script setup lang="ts">
import { ref } from 'vue';
import { IonContent, IonPage, IonItem, IonLabel, useIonRouter } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useProfileStore } from '@/stores/profileStore';
import { useLoading } from '@/composables/useLoading';
import AppHeader from '@/components/app/AppHeader.vue';
import AppHeading from '@/components/app/AppHeading.vue';
import AppContainer from '@/components/app/AppContainer.vue';
import AppBackButton from '@/components/app/AppBackButton.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import AppConfirmModal from '@/components/app/AppConfirmModal.vue';

const authStore = useAuthStore();
const profileStore = useProfileStore();

const ionRouter = useIonRouter();

const { isLoading, withLoading } = useLoading();

const modalRef = ref<InstanceType<typeof AppConfirmModal> | null>(null);

const handleDeleteClick = () => {
  modalRef.value?.setOpen(true);
};

const confirmDelete = async () => {
  await withLoading(async () => {
    await profileStore.deleteAccount();
    authStore.clearTokens();
    ionRouter.navigate('/signin', 'root', 'replace');
  });
};
</script>

<template>
  <ion-page>
    <AppHeader>
      <AppBackButton />
    </AppHeader>

    <ion-content>
      <AppContainer>
        <AppHeading title="Exclusão de Conta" />

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

        <BaseButton
          color="danger"
          class="ion-margin-top ion-margin-bottom"
          :is-loading="isLoading"
          @click="handleDeleteClick"
        >
          Confirmar Exclusão
        </BaseButton>
      </AppContainer>
      
      <AppConfirmModal
        ref="modalRef"
        message="Tem certeza que deseja excluir sua conta? Esta ação é irreversível!"
        @on-confirm="confirmDelete"
      />
    </ion-content>
  </ion-page>
</template>
