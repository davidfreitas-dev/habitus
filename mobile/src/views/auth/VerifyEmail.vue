<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonSpinner, IonIcon } from '@ionic/vue';
import { checkmarkCircleOutline, closeCircleOutline } from 'ionicons/icons';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/auth/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';

const props = withDefaults(
  defineProps<{
    token?: string;
  }>(),
  {
    token: '',
  }
);

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
    message.value = response.message || response.data?.message || 'E-mail verificado com sucesso!';
    showToast('success', message.value);
  } catch (err: unknown) {
    console.error('Email verification failed:', err);
    success.value = false;
    message.value = getErrorMessage(err, 'Erro ao verificar e-mail. O link pode ter expirado ou é inválido.');
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
  <AuthLayout>
    <div class="verification-wrapper ion-text-center">
      <div v-if="isLoading" class="loading-state">
        <ion-spinner
          name="crescent"
          color="primary"
          class="verification-spinner"
        />
        <p class="loading-text">
          Verificando seu e-mail, por favor aguarde...
        </p>
      </div>

      <div v-else class="result-state">
        <div :class="['status-icon', success ? 'success' : 'error']">
          <ion-icon :icon="success ? checkmarkCircleOutline : closeCircleOutline" />
        </div>

        <h2 class="title">
          {{ success ? 'Sucesso!' : 'Ops!' }}
        </h2>
        <p class="description">
          {{ message }}
        </p>

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
  </AuthLayout>
</template>

<style scoped>
.verification-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 55vh;
  padding: 2rem 1rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.verification-spinner {
  width: 48px;
  height: 48px;
  margin-bottom: 1.5rem;
}

.loading-text {
  color: var(--color-text-primary);
  opacity: 0.7;
  font-size: 1rem;
}

.status-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.75rem;
  margin: 0 auto 1.5rem;
}

.status-icon.success {
  background: rgba(163, 230, 53, 0.1);
  color: var(--color-primary, #a3e635);
  border: 2px solid rgba(163, 230, 53, 0.2);
}

.status-icon.error {
  background: rgba(239, 68, 68, 0.1);
  color: var(--color-danger, #ef4444);
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
  margin: 0 auto;
}
</style>
