<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useOnboarding } from '@/composables/useOnboarding';
import { useNotifications } from '@/composables/useNotifications';
import { useToast } from '@/composables/useToast';
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
      showToast('success', 'Lembretes ativados com sucesso!');
    } else {
      showToast('info', 'Você pode ativar os lembretes depois nas configurações do dispositivo.');
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
          <div class="welcome-illustration">
            <span class="welcome-emoji">🎯</span>
          </div>

          <h1 class="welcome-title">
            Bem-vindo ao Habitus!
          </h1>

          <p class="welcome-description">
            Acompanhe seus hábitos diários, visualize seu progresso
            e construa rotinas saudáveis de forma simples e eficiente.
          </p>

          <div class="welcome-features">
            <div class="feature-item">
              <span class="feature-icon">✅</span>
              <span class="feature-text">Registre seus hábitos diários</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">📊</span>
              <span class="feature-text">Acompanhe suas sequências</span>
            </div>
            <div class="feature-item">
              <span class="feature-icon">🔔</span>
              <span class="feature-text">Receba lembretes personalizados</span>
            </div>
          </div>

          <div class="welcome-actions">
            <Button
              color="primary"
              :is-loading="isRequesting"
              @click="requestNotificationPermission"
            >
              Ativar lembretes
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
  justify-content: center;
  min-height: 80vh;
  padding: 2rem 1rem;
  text-align: center;
}

.welcome-illustration {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: var(--color-background-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 2rem;
}

.welcome-emoji {
  font-size: 48px;
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

.welcome-features {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 2.5rem;
  width: 100%;
  max-width: 300px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--color-background-secondary);
  border-radius: var(--radius-xl);
}

.feature-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.feature-text {
  color: var(--color-text-accent);
  font-size: 14px;
  font-weight: 600;
  text-align: left;
}

.welcome-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  max-width: 300px;
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
