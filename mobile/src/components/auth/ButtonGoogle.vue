<template>
  <ion-button
    @click="doGoogleLogin"
    :disabled="isDisabled || isLoadingGoogle"
    type="button"
  >
    <ion-spinner v-if="isLoadingGoogle" name="dots" />
    <template v-else>
      <svg
        class="google-icon"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 48 48"
        width="20px"
        height="20px"
      >
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
        <path fill="none" d="M0 0h48v48H0z" />
      </svg>
      <span>{{ label }}</span>
    </template>
  </ion-button>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonButton, IonSpinner } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { loginWithGoogle } from '@/composables/useSocialAuth';

const props = defineProps({
  isDisabled: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: 'Entrar com Google'
  }
});

const authStore = useAuthStore();
const router = useRouter();
const { showToast } = useToast();
const isLoadingGoogle = ref(false);

const emit = defineEmits(['login-start', 'login-end']);

const doGoogleLogin = async () => {
  try {
    const idToken = await loginWithGoogle();
    if (idToken) {
      isLoadingGoogle.value = true;
      emit('login-start');
      await authStore.socialLogin('google', idToken);
      router.push('/');
    }
  } catch (err) {
    console.error('Google Login failed:', err);
    showToast('error', 'Erro ao continuar com o Google.');
  } finally {
    isLoadingGoogle.value = false;
    emit('login-end');
  }
};
</script>

<style scoped>
ion-button {
  width: 100%;
  height: 3.5rem;
  font-size: 1rem;
  font-weight: 700;
  text-transform: unset;
  letter-spacing: .0225rem;

  --padding-start: 1.5rem;
  --padding-end: 1.5rem;

  --color: #09090a;
  --background: #ffffff;
  --background-hover: #f1f1f1;
  --background-activated: #f1f1f1;
  --background-focused: #f1f1f1;
  --border-radius: var(--border-radius-default);
}

ion-button .google-icon {
  margin-right: 8px;
}
</style>
