<script setup lang="ts">
import { useRouter } from 'vue-router';
import { ref, computed, watch } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { useValidatedForm } from '@/composables/useValidatedForm';
import { passwordRules, confirmPasswordRules, getFieldError } from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/features/auth/AuthLayout.vue';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';

const authStore = useAuthStore();
const isLoading = ref(false);
const { showToast } = useToast();
const router = useRouter();

const passwordRef = ref('');
const rules = computed(() => ({
  password: passwordRules('Informe uma senha'),
  confPassword: confirmPasswordRules(
    passwordRef,
    'Confirme a senha',
    'As senhas não coincidem'
  ),
}));

const { formData, v$, handleSubmit } = useValidatedForm(
  () => ({ password: '', confPassword: '' }),
  rules,
  { invalidMessage: 'Preencha os campos com senhas válidas e idênticas' }
);

// Keep passwordRef synced for confirmPasswordRules
watch(() => formData.password, (val) => { passwordRef.value = val; }, { immediate: true });

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

const submitForm = () => handleSubmit(handleConfirm);
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <BaseInput
        v-model="formData.password"
        type="password"
        label="Digite a nova senha"
        placeholder="Sua nova senha"
        :error-text="getFieldError(v$.password)"
        @blur="v$.password.$touch()"
      />

      <BaseInput
        v-model="formData.confPassword"
        type="password"
        label="Confirme a nova senha"
        placeholder="Repita a senha"
        :error-text="getFieldError(v$.confPassword)"
        @blur="v$.confPassword.$touch()"
      />

      <div class="ion-margin-top ion-padding-top">
        <BaseButton
          type="submit"
          color="primary"
          :is-loading="isLoading"
          :is-disabled="v$.$invalid"
        >
          Confirmar
        </BaseButton>

        <div class="separator">
          <span>ou</span>
        </div>

        <BaseButton router-link="/signin">
          Voltar ao login
        </BaseButton>
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
