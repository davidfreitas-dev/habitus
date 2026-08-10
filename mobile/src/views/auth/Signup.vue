<script setup>
import { useRouter } from 'vue-router';
import { ref, reactive, computed } from 'vue';
import { useVuelidate } from '@vuelidate/core';
import { required, email, minLength, helpers } from '@vuelidate/validators';
import { IonPage, IonContent, IonFooter, onIonViewDidLeave } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import Container from '@/components/layout/Container.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import { loginWithGoogle } from '@/composables/useSocialAuth';

const authStore = useAuthStore();
const isLoadingEmail = ref(false);
const isLoadingGoogle = ref(false);
const formData = reactive({
  name: '',
  email: '',
  password: '',
  website: '', // honeypot
  agreeToTerms: false
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
  isLoadingEmail.value = true;

  try {
    if (formData.website) {
      // honeypot filled, ignore silently or throw error
      throw new Error('Bot detectado');
    }

    formData.name = capitalizeName(formData.name);
    await authStore.register(formData);
    router.push('/');
  } catch (err) {
    console.error('Registration failed:', err);
    showToast('error', err.response?.data?.message || 'Erro ao criar conta.');
  } finally {
    isLoadingEmail.value = false;
  }
};

const doGoogleLogin = async () => {
  try {
    const idToken = await loginWithGoogle();
    if (idToken) {
      isLoadingGoogle.value = true;
      await authStore.socialLogin('google', idToken);
      router.push('/');
    }
  } catch (err) {
    console.error('Google Login failed:', err);
    showToast('error', 'Erro ao continuar com o Google.');
  } finally {
    isLoadingGoogle.value = false;
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
      minLength: helpers.withMessage('A senha deve ter no mínimo 8 caracteres', minLength(8))
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
  formData.website = '';
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

          <div class="hp-field">
            <Input
              v-model="formData.website"
              type="text"
              label="Website"
              tabindex="-1"
              autocomplete="off"
            />
          </div>

          <div class="terms-checkbox">
            <Checkbox
              size="small"
              :is-checked="formData.agreeToTerms"
              @handle-checkbox-change="formData.agreeToTerms = !formData.agreeToTerms"
              @handle-item="formData.agreeToTerms = !formData.agreeToTerms"
            >
              <span class="terms-text">
                Li e concordo com os 
                <router-link to="/about">
                  Termos de Uso
                </router-link> e a
                <router-link to="/about">
                  Política de Privacidade
                </router-link>
              </span>
            </Checkbox>
          </div>

          <div class="ion-margin-top">
            <Button
              color="primary"
              :is-loading="isLoadingEmail"
              :is-disabled="v$.$invalid || !formData.agreeToTerms || isLoadingGoogle"
              @click="submitForm"
            >
              Criar conta
            </Button>

            <div class="separator">
              <span>ou</span>
            </div>

            <Button
              color="light"
              class="google-btn"
              :is-loading="isLoadingGoogle"
              :is-disabled="isLoadingEmail"
              @click="doGoogleLogin"
              type="button"
            >
              <!-- Minimal Google Icon -->
              <svg
                class="google-icon"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 48 48"
                width="20px"
                height="20px"
              >
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                <path fill="none" d="M0 0h48v48H0z" />
              </svg>
              Entrar com Google
            </Button>
          </div>
        </form>
      </Container>
    </ion-content>

    <ion-footer class="ion-no-border ion-padding">
      <div class="signup-prompt">
        Já tem uma conta? <router-link to="/signin">
          Fazer login
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
  margin: 1.25rem 0;
  color: var(--color-primary);
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

.hp-field {
  opacity: 0;
  position: absolute;
  top: -9999px;
  left: -9999px;
}

.terms-checkbox {
  margin-top: 1.25rem;
}

.terms-text {
  font-size: 0.85rem;
  color: var(--color-text-primary);
}

.terms-text a {
  margin: 0 !important;
  display: inline;
}

.google-btn {
  margin-top: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.google-icon {
  margin-right: 8px;
}

.signup-prompt {
  text-align: center;
  padding: 1.5rem 1rem;
  font-size: 0.95rem;
  color: var(--color-text-secondary, #666);
  background-color: transparent;
}

.signup-prompt a {
  color: var(--color-primary);
  font-weight: 700;
  text-decoration: none;
  margin-left: 0.25rem;
}
</style>