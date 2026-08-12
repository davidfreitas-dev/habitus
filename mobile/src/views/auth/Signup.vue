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
import ButtonGoogle from '@/components/auth/ButtonGoogle.vue';

const authStore = useAuthStore();
const isLoadingEmail = ref(false);
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
              :is-disabled="v$.$invalid || !formData.agreeToTerms"
              @click="submitForm"
            >
              Criar conta
            </Button>

            <div class="separator">
              <span>ou</span>
            </div>

            <ButtonGoogle :is-disabled="isLoadingEmail" label="Entrar com Google" />
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