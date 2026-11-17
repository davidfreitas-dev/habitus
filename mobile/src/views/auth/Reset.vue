<script setup lang="ts">
import { useRouter } from 'vue-router';
import { ref, reactive, computed, unref } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, sameAs, helpers } from '@vuelidate/validators';
import { IonPage, IonContent, onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { getErrorMessage } from '@/types';
import Container from '@/components/layout/Container.vue';
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

const rules = computed(() => {
  return {
    password: {
      required: helpers.withMessage('Informe uma senha', required)
    },
    confPassword: {
      required: helpers.withMessage('Confirme a senha', required),
      sameAsPassword: helpers.withMessage('As senhas não coincidem', sameAs(computed(() => formData.password)))
    }
  };
});

const v$ = useVuelidate(rules, formData);

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Preencha os campos com senhas idênticas');
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
  <ion-page>
    <ion-content :fullscreen="true">
      <Container>
        <form @submit.prevent="submitForm">
          <div class="logo-wrapper">
            <img
              src="../../../assets/logo.png"
              alt="Habitus"
              class="logo"
            >
          </div>

          <Input
            v-model="formData.password"
            type="password"
            label="Digite a nova senha"
            placeholder="Sua nova senha"
            :error-text="String(unref(v$.password.$errors[0]?.$message) || '')"
            @blur="v$.password.$touch()"
          /> 

          <Input
            v-model="formData.confPassword"
            type="password"
            label="Confirme a nova senha"
            placeholder="Repita a senha"
            :error-text="String(unref(v$.confPassword.$errors[0]?.$message) || '')"
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
