<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent, IonInputOtp, onIonViewDidLeave } from '@ionic/vue';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/authStore';
import { STORAGE_KEYS } from '@/constants/storage';

import Container from '@/components/layout/Container.vue';
import Button from '@/components/ui/Button.vue';

const router = useRouter();
const authStore = useAuthStore();
const isLoading = ref(false);
const otpValue = ref('');
const email = ref(localStorage.getItem(STORAGE_KEYS.FORGOT_EMAIL) || '');

const { showToast } = useToast();

const handleContinue = async () => {
  isLoading.value = true;

  try {
    await authStore.validateResetCode(otpValue.value);
    router.push({ name: 'Reset' });
  } catch (err) {
    console.error('Token validation failed:', err);
    showToast('error', err.response?.data?.message || 'Código inválido.');
  } finally {
    isLoading.value = false;
  }
};

const onOtpInput = (event) => {
  otpValue.value = event.detail.value ?? '';
};

const onOtpComplete = (event) => {
  otpValue.value = event.detail.value ?? '';
};

onIonViewDidLeave(() => {
  otpValue.value = '';
});
</script>

<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <Container>
        <form>
          <div class="logo-wrapper">
            <img
              src="../../../assets/logo.png"
              alt="Habitus"
              class="logo"
            >
          </div>

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
              color="primary"
              :is-loading="isLoading"
              :is-disabled="otpValue.length < 6"
              @click="handleContinue"
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
      </Container>
    </ion-content>
  </ion-page>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
  margin: 1rem 0;
  padding: 0 .5rem;
}

.logo-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: calc(env(safe-area-inset-top) + 1rem);
}

.logo {
  width: auto;
  height: 70px;
  object-fit: contain;
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

.separator {
  display: flex;
  align-items: center;
  margin: 1rem 0;
  color: var(--color-text-primary);
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
}

.separator::before,
.separator::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--color-text-primary);
  margin: 0 0.75rem;
}
</style>