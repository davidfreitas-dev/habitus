<script setup lang="ts">
import { useRouter } from 'vue-router';
import { ref, reactive, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { passwordRules, confirmPasswordRules, getFieldError } from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/auth/AuthLayout.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const authStore = useAuthStore();
const isLoading = ref(false);
const formData = reactive({
  password: '',
  confPassword: ''
});

const { showToast } = useToast();
const router = useRouter();

const handleConfirm = async () => {
  isLoading.value = true;

  try {
    const response = await authStore.resetPassword(formData.password, formData.confPassword);
    showToast('success', response.message || 'Senha redefinida com sucesso!');
    router.push('/signin');
  } catch (err: unknown) {
    console.error('Password reset failed:', err);
    showToast('error', getErrorMessage(err, 'Erro ao redefinir senha.'));
  } finally {
    isLoading.value = false;
  }
};

const rules = computed(() => ({
  password: passwordRules('Informe uma senha'),
  confPassword: confirmPasswordRules(
    computed(() => formData.password),
    'Confirme a senha',
    'As senhas não coincidem'
  ),
}));

const v$ = useVuelidate(rules, formData);

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Preencha os campos com senhas válidas e idênticas');
    return;
  }

  handleConfirm();
};

onIonViewDidLeave(() => {
  formData.password = '';
  formData.confPassword = '';
  v$.value.$reset();
});
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <Input
        v-model="formData.password"
        type="password"
        label="Digite a nova senha"
        placeholder="Sua nova senha"
        :error-text="getFieldError(v$.password)"
        @blur="v$.password.$touch()"
      />

      <Input
        v-model="formData.confPassword"
        type="password"
        label="Confirme a nova senha"
        placeholder="Repita a senha"
        :error-text="getFieldError(v$.confPassword)"
        @blur="v$.confPassword.$touch()"
      />

      <div class="ion-margin-top ion-padding-top">
        <Button
          type="submit"
          color="primary"
          :is-loading="isLoading"
          :is-disabled="v$.$invalid"
        >
          Confirmar
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
</style>
