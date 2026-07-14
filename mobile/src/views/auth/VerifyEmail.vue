<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import Container from '@/components/layout/Container.vue';
import Button from '@/components/ui/Button.vue';

const props = defineProps({
  token: {
    type: String,
    required: false,
    default: '',
  }
});

const router = useRouter();
const authStore = useAuthStore();
const isLoading = ref(true);
const success = ref(false);
const message = ref('');

const { showToast } = useToast();

const performVerification = async () => {
  if (!props.token) {
    isLoading.value = false;
    success.value = false;
    message.value = 'Token de verificação inválido ou ausente.';
    return;
  }

  try {
    const response = await authStore.verifyEmail(props.token);
    success.value = true;
    message.value = response.data?.message || 'E-mail verificado com sucesso!';
    showToast('success', message.value);
  } catch (err) {
    console.error('Email verification failed:', err);
    success.value = false;
    message.value = err.response?.data?.message || 'Erro ao verificar e-mail. O link pode ter expirado ou é inválido.';
    showToast('error', message.value);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  performVerification();
});
</script>

<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <Container class="ion-text-center">
        <div class="verification-wrapper">
          <div class="logo-wrapper">
            <img
              src="../../../assets/logo.png"
              alt="Habitus"
              class="logo"
            >
          </div>

          <div v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Verificando seu e-mail, por favor aguarde...</p>
          </div>

          <div v-else class="result-state">
            <div :class="['status-icon', success ? 'success' : 'error']">
              <span v-if="success">✓</span>
              <span v-else>✗</span>
            </div>

            <h2 class="title">{{ success ? 'Sucesso!' : 'Ops!' }}</h2>
            <p class="description">{{ message }}</p>

            <div class="actions">
              <Button
                color="primary"
                @click="router.push('/signin')"
              >
                Ir para o Login
              </Button>
            </div>
          </div>
        </div>
      </Container>
    </ion-content>
  </ion-page>
</template>

<style scoped>
.verification-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  padding: 2rem 1rem;
}

.logo-wrapper {
  margin-bottom: 3rem;
  display: flex;
  justify-content: center;
}

.logo {
  height: 70px;
  width: auto;
  object-fit: contain;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.loading-text {
  color: var(--color-text-primary);
  opacity: 0.7;
  font-size: 1rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(163, 230, 53, 0.1);
  border-top-color: var(--color-primary, #a3e635);
  border-radius: 50%;
  animation: spin 1s infinite linear;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.status-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  font-weight: bold;
  margin: 0 auto 1.5rem;
}

.status-icon.success {
  background: rgba(163, 230, 53, 0.1);
  color: var(--color-primary, #a3e635);
  border: 2px solid rgba(163, 230, 53, 0.2);
}

.status-icon.error {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 2px solid rgba(239, 68, 68, 0.2);
}

.title {
  font-size: 1.75rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  color: var(--color-text-primary, #ffffff);
}

.description {
  color: var(--color-text-primary);
  opacity: 0.7;
  font-size: 1rem;
  line-height: 1.5;
  margin-bottom: 2.5rem;
  max-width: 320px;
}

.actions {
  width: 100%;
  max-width: 280px;
}
</style>
