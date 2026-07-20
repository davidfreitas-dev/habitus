<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useOnboarding } from '@/composables/useOnboarding';
import { useNotifications } from '@/composables/useNotifications';
import { useToast } from '@/composables/useToast';
import { useHabitStore } from '@/stores/habits';
import { NotificationService } from '@/services/NotificationService';
import Container from '@/components/layout/Container.vue';
import Button from '@/components/ui/Button.vue';

const router = useRouter();
const { markStepSeen } = useOnboarding();
const { requestPermission } = useNotifications();
const { showToast } = useToast();
const isRequesting = ref(false);

const proceed = async () => {
  await markStepSeen('welcome');
  router.replace('/tabs/home');
};

const requestNotificationPermission = async () => {
  isRequesting.value = true;

  try {
    const granted = await requestPermission();

    if (granted) {
      try {
        const habitStore = useHabitStore();
        const habits = await habitStore.fetchAllHabits();
        await NotificationService.rescheduleAllNotifications(habits);
      } catch (error) {
        console.error('Error rescheduling notifications on onboarding welcome:', error);
      }
    } else {
      showToast('info', 'Você pode ativar as notificações depois nas configurações do dispositivo.');
    }
  } catch (err) {
    console.error('Error requesting notification permission:', err);
    showToast('info', 'Não foi possível solicitar permissão. Você pode ativar depois.');
  } finally {
    isRequesting.value = false;
    proceed();
  }
};

const skipNotifications = () => {
  proceed();
};
</script>

<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <Container>
        <div class="welcome-container">
          <div class="welcome-content">
            <div class="welcome-illustration">
              <img
                src="../../../assets/notifications.png"
                alt="Notificações"
                class="welcome-image"
              >
            </div>

            <h1 class="welcome-title">
              Não Perca o Ritmo!
            </h1>

            <p class="welcome-description">
              Ative as notificações para receber lembretes nos horários certos e manter seus hábitos em dia.
            </p>
          </div>

          <div class="welcome-actions">
            <Button
              color="primary"
              :is-loading="isRequesting"
              @click="requestNotificationPermission"
            >
              Ativar notificações
            </Button>

            <button
              class="skip-button"
              :disabled="isRequesting"
              @click="skipNotifications"
            >
              Agora não
            </button>
          </div>
        </div>
      </Container>
    </ion-content>
  </ion-page>
</template>

<style scoped>
.welcome-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  min-height: calc(100vh - 32px);
  padding: 2rem 1rem;
  text-align: center;
}

.welcome-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 2rem;
}

.welcome-illustration {
  width: 250px;
  height: 250px;
  border-radius: 50%;
  background: var(--color-background-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 2rem;
}

.welcome-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transform: scale(1.3);
}

.welcome-title {
  color: var(--color-text-primary);
  font-size: 28px;
  font-weight: 800;
  margin: 0 0 1rem 0;
  line-height: 1.2;
}

.welcome-description {
  color: var(--color-text-secondary);
  font-size: 16px;
  line-height: 1.6;
  margin: 0 0 2rem 0;
  max-width: 320px;
}

.welcome-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  max-width: 320px;
  margin: 0 auto;
  padding-bottom: env(safe-area-inset-bottom, 16px);
}

.skip-button {
  background: none;
  border: none;
  color: var(--color-text-secondary);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  padding: 12px;
}

.skip-button:disabled {
  opacity: 0.5;
}
</style>
