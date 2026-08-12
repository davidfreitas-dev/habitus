<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonIcon } from '@ionic/vue';
import { notificationsOutline } from 'ionicons/icons';
import { useOnboarding } from '@/composables/useOnboarding';
import { useNotifications } from '@/composables/useNotifications';
import { useToast } from '@/composables/useToast';
import { useHabitStore } from '@/stores/habitsStore';
import { notificationService } from '@/services/notificationService';
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
        await notificationService.rescheduleAllNotifications(habits);
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
              <div class="waves left">
                <span /><span /><span />
              </div>
              <ion-icon :icon="notificationsOutline" class="welcome-icon" />
              <div class="waves right">
                <span /><span /><span />
              </div>
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
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 2rem;
  margin-top: 1rem;
  position: relative;
}

.waves {
  position: absolute;
  top: 15px;
  width: 50px;
  height: 50px;
}

.waves.left {
  left: calc(50% - 85px);
  transform: rotate(-10deg);
}

.waves.right {
  right: calc(50% - 85px);
  transform: rotate(10deg);
}

.waves span {
  position: absolute;
  bottom: 0;
  border: 3px solid transparent;
  /* Making it less circular by using an elliptical border-radius */
  border-radius: 100% 0 0 0;
  animation: waveSequential 2s infinite ease-out;
  opacity: 0;
}

.waves.left span {
  right: 0;
  border-top-color: var(--color-primary);
  border-left-color: var(--color-primary);
  border-radius: 100% 0 0 0;
}

.waves.right span {
  left: 0;
  border-top-color: var(--color-primary);
  border-right-color: var(--color-primary);
  border-radius: 0 100% 0 0;
}

/* Sequential sizing for perfect arcs */
.waves span:nth-child(1) {
  width: 12px; height: 12px;
  animation-delay: 0s;
}

.waves span:nth-child(2) {
  width: 24px; height: 24px;
  animation-delay: 0.15s;
}

.waves span:nth-child(3) {
  width: 36px; height: 36px;
  animation-delay: 0.3s;
}

@keyframes waveSequential {
  0% { opacity: 0; }
  10% { opacity: 1; }
  40% { opacity: 0; }
  100% { opacity: 0; }
}

.welcome-icon {
  font-size: 120px;
  color: var(--color-primary);
  animation: ring 2s ease-in-out infinite;
  transform-origin: top center;
  z-index: 2;
}

@keyframes ring {
  0% { transform: rotate(0); }
  5% { transform: rotate(15deg); }
  10% { transform: rotate(-10deg); }
  15% { transform: rotate(5deg); }
  20% { transform: rotate(-5deg); }
  25% { transform: rotate(2deg); }
  30% { transform: rotate(0); }
  100% { transform: rotate(0); }
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
