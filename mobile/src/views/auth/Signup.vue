<script setup>
import { useRouter } from 'vue-router';
import { ref, reactive, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, email, minLength, helpers } from '@vuelidate/validators';
import { IonPage, IonContent, onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/auth';
import { useToast } from '@/composables/useToast';
import Container from '@/components/layout/Container.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const authStore = useAuthStore();
const isLoading = ref(false);
const formData = reactive({
  name: '',
  email: '',
  password: ''
});

const { showToast } = useToast();
const router = useRouter();

const containsNameAndSurname = (value) => {
  if (!value) return true;
  const parts = value.trim().split(/\s+/).filter(Boolean);
  return parts.length >= 2;
};

const capitalizeName = (name) => {
  if (!name) return '';
  const prepositions = ['de', 'da', 'do', 'dos', 'das', 'e'];
  return name
    .trim()
    .toLowerCase()
    .split(/\s+/)
    .map((word, index) => {
      if (prepositions.includes(word) && index > 0) {
        return word;
      }
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
};

const signUp = async () => {
  isLoading.value = true;

  try {
    formData.name = capitalizeName(formData.name);
    await authStore.register(formData);
    router.push('/');
  } catch (err) {
    console.error('Registration failed:', err);
    showToast('error', err.response?.data?.message || 'Erro ao criar conta.');
  } finally {
    isLoading.value = false;
  }
};

const rules = computed(() => {
  return {
    name: { 
      required: helpers.withMessage('Informe seu nome', required),
      fullName: helpers.withMessage('Informe nome e sobrenome', containsNameAndSurname)
    },
    email: { 
      required: helpers.withMessage('Informe seu e-mail', required),
      email: helpers.withMessage('Informe um e-mail válido', email)
    },
    password: { 
      required: helpers.withMessage('Informe uma senha', required),
      minLength: helpers.withMessage('A senha deve ter no mínimo 6 caracteres', minLength(6))
    }
  };
});

const v$ = useVuelidate(rules, formData);

const submitForm = async () => {
  const isFormCorrect = await v$.value.$validate();

  if (!isFormCorrect) {
    showToast('info', 'Preencha todos os campos corretamente');
    return;
  } 
  
  signUp();
};

onIonViewDidLeave(() => {
  formData.name = '';
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
            v-model="formData.name"
            type="text"
            label="Seu nome e sobrenome"
            placeholder="Fulano de Tal"
            :error-text="v$.name.$errors[0]?.$message"
            @blur="v$.name.$touch()"
          /> 

          <Input
            v-model="formData.email"
            type="text"
            label="Seu melhor e-mail"
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

          <div class="ion-margin-top ion-padding-top">
            <Button
              color="primary"
              :is-loading="isLoading"
              :is-disabled="v$.$invalid"
              @click="submitForm"
            >
              Criar conta
            </Button>

            <div class="separator">
              <span>ou</span>
            </div>

            <Button router-link="/signin">
              Já tenho uma conta
            </Button>

            <div class="ion-padding-top">
              Ao criar uma conta, você concorda com nossos 
              <router-link to="/about">
                Termos de Uso e Política de Privacidade
              </router-link>.
            </div>
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

form div {
  font-size: .85rem;
  text-align: center;
  line-height: 1.6;
  margin: 1rem 0;
}

form a {
  font-size: .85rem;
  text-decoration: none;
  letter-spacing: .25px;
  margin: 1.25rem 0;
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