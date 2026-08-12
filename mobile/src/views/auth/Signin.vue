<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, email, helpers } from '@vuelidate/validators';
import { IonPage, IonContent, IonFooter, onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import Container from '@/components/layout/Container.vue';
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
  } catch (err) {
    console.error('Login failed:', err);
    showToast('error', err.response?.data?.message || 'Erro ao fazer login.');
  } finally {
    isLoadingEmail.value = false;
  }
};


const rules = computed(() => {
  return {
    email: {
      required: helpers.withMessage('Informe seu e-mail', required),
      email: helpers.withMessage('Informe um e-mail válido', email)
    },
    password: {
      required: helpers.withMessage('Informe sua senha', required)
    }
  };
});

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
            label="Seu e-mail"
            placeholder="exemplo@email.com"
            :error-text="v$.email.$errors[0]?.$message"
            @blur="v$.email.$touch()"
          /> 

          <Input
            v-model="formData.password"
            type="password"
            label="Sua senha"
            placeholder="Digite sua senha"
            :error-text="v$.password.$errors[0]?.$message"
            @blur="v$.password.$touch()"
          /> 

          <router-link to="/forgot">
            Esqueci a senha
          </router-link>

          <Button
            color="primary"
            :is-loading="isLoadingEmail"
            :is-disabled="v$.$invalid"
            @click="submitForm"
          >
            Entrar
          </Button>

          <div class="separator">
            <span>ou</span>
          </div>

          <ButtonGoogle :is-disabled="isLoadingEmail" label="Entrar com Google" />
        </form>
      </Container>
    </ion-content>

    <ion-footer class="ion-no-border ion-padding">
      <div class="signup-prompt">
        Não tem uma conta? <router-link to="/signup">
          Cadastre-se
        </router-link>
      </div>
    </ion-footer>
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

form div {
  font-size: .85rem;
  text-align: center;
  line-height: 1.6;
}

form a {
  font-size: .85rem;
  text-decoration: none;
  letter-spacing: .25px;
  width: fit-content;
  margin: 1.25rem 0 1.25rem auto;
  color: var(--color-primary-hover);
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

.signup-prompt {
  text-align: center;
  padding: 1.5rem 1rem;
  font-size: 0.95rem;
  color: var(--color-text-secondary, #666);
  background-color: transparent;
}

.signup-prompt a {
  color: var(--color-primary-hover);
  font-weight: 700;
  text-decoration: none;
  margin-left: 0.25rem;
}
</style>