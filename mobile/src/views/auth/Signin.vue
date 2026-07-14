<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useVuelidate } from '@vuelidate/core';
import { required, email } from '@vuelidate/validators';
import { IonPage, IonContent, onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import Container from '@/components/layout/Container.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const authStore = useAuthStore();
const isLoading = ref(false);
const formData = reactive({
  email: '',
  password: ''
});

const { showToast } = useToast();
const router = useRouter();

const signIn = async () => {
  isLoading.value = true;

  try {
    await authStore.login(formData);
    router.push('/');
  } catch (err) {
    console.error('Login failed:', err);
    showToast('error', err.response?.data?.message || 'Erro ao fazer login.');
  } finally {
    isLoading.value = false;
  }
};

const rules = computed(() => {
  return {
    email: { required, email },
    password: { required }
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
          /> 

          <Input
            v-model="formData.password"
            type="password"
            label="Sua senha"
            placeholder="Digite sua senha"
          /> 

          <router-link to="/forgot">
            Esqueci a senha
          </router-link>

          <Button
            color="primary"
            :is-loading="isLoading"
            :is-disabled="v$.$invalid"
            @click="submitForm"
          >
            Entrar
          </Button>

          <div class="separator">
            <span>ou</span>
          </div>

          <Button router-link="/signup">
            Criar minha conta
          </Button>
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

form a {
  font-size: .85rem;
  text-decoration: none;
  letter-spacing: .25px;
  width: fit-content;
  margin: 1.25rem 0 1.25rem auto;
  color: var(--color-primary);
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