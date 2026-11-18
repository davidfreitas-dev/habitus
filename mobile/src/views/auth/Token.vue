<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { IonInputOtp, onIonViewDidLeave } from '@ionic/vue';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/authStore';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/auth/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';

const router = useRouter();
const authStore = useAuthStore();
const isLoading = ref(false);
const otpValue = ref('');
const email = computed(() => authStore.resetFlowEmail || '');

const { showToast } = useToast();

const handleContinue = async () => {
  isLoading.value = true;

  try {
    await authStore.validateResetCode(otpValue.value);
    router.push({ name: 'Reset' });
  } catch (err: unknown) {
    console.error('Token validation failed:', err);
    showToast('error', getErrorMessage(err, 'Código inválido.'));
  } finally {
    isLoading.value = false;
  }
};

const onOtpInput = (event: CustomEvent<{ value?: string | number | null }>) => {
  otpValue.value = String(event.detail?.value ?? '');
};

const onOtpComplete = (event: CustomEvent<{ value?: string | number | null }>) => {
  otpValue.value = String(event.detail?.value ?? '');
};

onIonViewDidLeave(() => {
  otpValue.value = '';
});
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="handleContinue">
      <label class="otp-label">Informe o código</label>
      <p class="otp-description">
        Digite o código de 6 dígitos enviado para o e-mail <strong>{{ email }}</strong>
      </p>

      <div class="otp-wrapper">
        <ion-input-otp
          :length="6"
          type="number"
          fill="outline"
          shape="soft"
          size="large"
          class="custom-otp"
          @ion-input="onOtpInput"
          @ion-complete="onOtpComplete"
        />
      </div>

      <div class="ion-margin-top ion-padding-top">
        <Button
          type="submit"
          color="primary"
          :is-loading="isLoading"
          :is-disabled="otpValue.length < 6"
        >
          Continuar
        </Button>

        <div class="separator">
          <span>ou</span>
        </div>

        <Button router-link="/signin">
          Voltar ao login
        </Button>
      </div>
    </form>
  </AuthLayout>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
}

.otp-label {
  color: var(--color-text-primary);
  font-size: 1.1rem;
  font-weight: 700;
  margin-top: 1.5rem;
  display: block;
}

.otp-description {
  color: var(--color-text-primary);
  opacity: 0.7;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.otp-wrapper {
  display: flex;
  justify-content: center;
}

.custom-otp {
  --background: var(--color-background-secondary);
  --border-color: var(--color-border-default);
  --border-radius: 0.375rem;
  --border-width: 2px;
  --color: var(--color-text-primary);
  --highlight-color-focused: var(--color-primary);
  --highlight-color-valid: var(--color-primary);
  --highlight-color-invalid: var(--ion-color-danger);
  --height: 56px;
  --width: 44px;
}
</style>
