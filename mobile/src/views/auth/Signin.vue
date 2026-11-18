<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { emailRules, requiredPasswordRules, getFieldError } from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/auth/AuthLayout.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';
import ButtonGoogle from '@/components/auth/ButtonGoogle.vue';

const authStore = useAuthStore();
const isLoadingEmail = ref(false);
const formData = reactive({
  email: '',
  password: ''
});

const { showToast } = useToast();
const router = useRouter();

const signIn = async () => {
  isLoadingEmail.value = true;

  try {
    await authStore.login(formData);
    router.push('/');
  } catch (err: unknown) {
    console.error('Login failed:', err);
    showToast('error', getErrorMessage(err, 'Erro ao fazer login.'));
  } finally {
    isLoadingEmail.value = false;
  }
};

const rules = computed(() => ({
  email: emailRules(),
  password: requiredPasswordRules(),
}));

const v$ = useVuelidate(rules, formData);

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Informe um e-mail válido e a senha');
    return;
  }

  signIn();
};

onIonViewDidLeave(() => {
  formData.email = '';
  formData.password = '';
  v$.value.$reset();
});
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <Input
        v-model="formData.email"
        type="text"
        label="Seu e-mail"
        placeholder="exemplo@email.com"
        :error-text="getFieldError(v$.email)"
        @blur="v$.email.$touch()"
      />

      <Input
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

      <Button
        type="submit"
        color="primary"
        :is-loading="isLoadingEmail"
        :is-disabled="v$.$invalid"
      >
        Entrar
      </Button>

      <div class="separator">
        <span>ou</span>
      </div>

      <ButtonGoogle :is-disabled="isLoadingEmail" label="Entrar com Google" />
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
