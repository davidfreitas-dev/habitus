<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, email, helpers } from '@vuelidate/validators';
import { IonPage, IonContent, onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';

import Container from '@/components/layout/Container.vue';
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
  } catch (err) {
    console.error('Forgot password failed:', err);
    showToast('error', err.response?.data?.message || 'Erro ao solicitar recuperação de senha.');
  } finally {
    isLoading.value = false;
  }
};

const rules = computed(() => {
  return {
    email: {
      required: helpers.withMessage('Informe seu e-mail', required),
      email: helpers.withMessage('Informe um e-mail válido', email)
    }
  };
});

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

          <Input
            v-model="formData.email"
            type="text"
            label="Endereço de e-mail"
            placeholder="exemplo@email.com"
            :error-text="v$.email.$errors[0]?.$message"
            @blur="v$.email.$touch()"
          /> 
          
          <div class="ion-margin-top ion-padding-top">
            <Button
              color="primary"
              :is-loading="isLoading"
              :is-disabled="v$.$invalid"
              @click="submitForm"
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
  margin: 3rem 0;
  padding: 0 .5rem;
}

.logo-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 1.5rem;
  margin-bottom: 1.5rem;
}

.logo {
  width: auto;
  height: 70px;
  object-fit: contain;
}

.separator {
  display: flex;
  align-items: center;
  margin: 1.25rem 0;
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