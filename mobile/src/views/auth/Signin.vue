<script setup lang="ts">
import { ref, computed } from 'vue';
import { useIonRouter } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { useValidatedForm } from '@/composables/useValidatedForm';
import { emailRules, requiredPasswordRules, getFieldError } from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/features/auth/AuthLayout.vue';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import AuthGoogleButton from '@/components/features/auth/AuthGoogleButton.vue';

const authStore = useAuthStore();
const isLoadingEmail = ref(false);
const { showToast } = useToast();
const ionRouter = useIonRouter();

const rules = computed(() => ({
  email: emailRules(),
  password: requiredPasswordRules(),
}));

const { formData, v$, handleSubmit } = useValidatedForm(
  () => ({ email: '', password: '' }),
  rules,
  { invalidMessage: 'Informe um e-mail válido e a senha' }
);

const signIn = async () => {
  isLoadingEmail.value = true;

  try {
    await authStore.login(formData);
    ionRouter.navigate('/tabs/home', 'root', 'replace');
  } catch (err: unknown) {
    console.error('Login failed:', err);
    showToast('error', getErrorMessage(err, 'Erro ao fazer login.'));
  } finally {
    isLoadingEmail.value = false;
  }
};

const submitForm = () => handleSubmit(signIn);
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <BaseInput
        v-model="formData.email"
        type="text"
        label="Seu e-mail"
        placeholder="exemplo@email.com"
        :error-text="getFieldError(v$.email)"
        @blur="v$.email.$touch()"
      />

      <BaseInput
        v-model="formData.password"
        type="password"
        label="Sua senha"
        placeholder="Digite sua senha"
        :error-text="getFieldError(v$.password)"
        @blur="v$.password.$touch()"
      />

      <router-link to="/forgot" class="forgot-link">
        Esqueci a senha
      </router-link>

      <BaseButton
        type="submit"
        color="primary"
        :is-loading="isLoadingEmail"
        :is-disabled="v$.$invalid"
      >
        Entrar
      </BaseButton>

      <div class="separator">
        <span>ou</span>
      </div>

      <AuthGoogleButton :is-disabled="isLoadingEmail" label="Entrar com Google" />
    </form>

    <template #footer>
      Não tem uma conta? <router-link to="/signup">
        Cadastre-se
      </router-link>
    </template>
  </AuthLayout>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
}

.forgot-link {
  font-size: .85rem;
  text-decoration: none;
  letter-spacing: .25px;
  width: fit-content;
  margin: 1.25rem 0 1.25rem auto;
  color: var(--color-primary-hover);
}
</style>
