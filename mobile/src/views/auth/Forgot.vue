<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { emailRules, getFieldError } from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/auth/AuthLayout.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const router = useRouter();
const authStore = useAuthStore();
const isLoading = ref(false);
const formData = reactive({
  email: ''
});

const { showToast } = useToast();

const handleContinue = async () => {
  isLoading.value = true;

  try {
    const response = await authStore.forgotPassword(formData.email);
    showToast('success', response.message || 'E-mail de recuperação enviado!');
    router.push('/forgot/token');
  } catch (err: unknown) {
    console.error('Forgot password failed:', err);
    showToast('error', getErrorMessage(err, 'Erro ao solicitar recuperação de senha.'));
  } finally {
    isLoading.value = false;
  }
};

const rules = computed(() => ({
  email: emailRules(),
}));

const v$ = useVuelidate(rules, formData);

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Informe um e-mail válido');
    return;
  }

  handleContinue();
};

onIonViewDidLeave(() => {
  formData.email = '';
  v$.value.$reset();
});
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <Input
        v-model="formData.email"
        type="text"
        label="Endereço de e-mail"
        placeholder="exemplo@email.com"
        :error-text="getFieldError(v$.email)"
        @blur="v$.email.$touch()"
      />

      <div class="ion-margin-top ion-padding-top">
        <Button
          type="submit"
          color="primary"
          :is-loading="isLoading"
          :is-disabled="v$.$invalid"
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
</style>
