<script setup lang="ts">
import { ref, computed } from 'vue';
import { useIonRouter } from '@ionic/vue';
import { useAuthStore } from '@/stores/authStore';
import { useToast } from '@/composables/useToast';
import { useValidatedForm } from '@/composables/useValidatedForm';
import {
  fullNameRules,
  emailRules,
  passwordRules,
  capitalizeName,
  getFieldError,
} from '@/utils/validators';
import { getErrorMessage } from '@/types';
import AuthLayout from '@/components/features/auth/AuthLayout.vue';
import BaseInput from '@/components/base/BaseInput.vue';
import BaseButton from '@/components/base/BaseButton.vue';
import BaseCheckbox from '@/components/base/BaseCheckbox.vue';
import AuthGoogleButton from '@/components/features/auth/AuthGoogleButton.vue';

const authStore = useAuthStore();
const isLoadingEmail = ref(false);
const { showToast } = useToast();
const ionRouter = useIonRouter();

const rules = computed(() => ({
  name: fullNameRules(),
  email: emailRules(),
  password: passwordRules(),
}));

const { formData, v$, handleSubmit } = useValidatedForm(
  () => ({
    name: '',
    email: '',
    password: '',
    website: '', // honeypot
    agreeToTerms: false,
  }),
  rules
);

const signUp = async () => {
  isLoadingEmail.value = true;

  try {
    if (formData.website) {
      throw new Error('Bot detectado');
    }

    formData.name = capitalizeName(formData.name);
    await authStore.register(formData);
    ionRouter.navigate('/tabs/home', 'root', 'replace');
  } catch (err: unknown) {
    console.error('Registration failed:', err);
    showToast('error', getErrorMessage(err, 'Erro ao criar conta.'));
  } finally {
    isLoadingEmail.value = false;
  }
};

const submitForm = () => handleSubmit(signUp);
</script>

<template>
  <AuthLayout>
    <form @submit.prevent="submitForm">
      <BaseInput
        v-model="formData.name"
        type="text"
        label="Seu nome e sobrenome"
        placeholder="Fulano de Tal"
        :error-text="getFieldError(v$.name)"
        @blur="v$.name.$touch()"
      />

      <BaseInput
        v-model="formData.email"
        type="text"
        label="Seu melhor e-mail"
        placeholder="exemplo@email.com"
        :error-text="getFieldError(v$.email)"
        @blur="v$.email.$touch()"
      />

      <BaseInput
        v-model="formData.password"
        type="password"
        label="Sua senha"
        placeholder="Digite sua senha"
        :error-text="getFieldError(v$.password)"
        @blur="v$.password.$touch()"
      />

      <div class="hp-field">
        <BaseInput
          v-model="formData.website"
          type="text"
          label="Website"
          tabindex="-1"
          autocomplete="off"
        />
      </div>

      <div class="terms-checkbox">
        <BaseCheckbox
          size="small"
          :is-checked="formData.agreeToTerms"
          @handle-checkbox-change="formData.agreeToTerms = !formData.agreeToTerms"
          @handle-item="formData.agreeToTerms = !formData.agreeToTerms"
        >
          <span class="terms-text">
            Li e concordo com os
            <router-link to="/about?tab=terms" @click.stop>
              Termos de Uso
            </router-link> e a
            <router-link to="/about?tab=privacy" @click.stop>
              Política de Privacidade
            </router-link>
          </span>
        </BaseCheckbox>
      </div>

      <div class="ion-margin-top">
        <BaseButton
          type="submit"
          color="primary"
          :is-loading="isLoadingEmail"
          :is-disabled="v$.$invalid || !formData.agreeToTerms"
        >
          Criar conta
        </BaseButton>

        <div class="separator">
          <span>ou</span>
        </div>

        <AuthGoogleButton :is-disabled="isLoadingEmail" label="Entrar com Google" />
      </div>
    </form>

    <template #footer>
      Já tem uma conta? <router-link to="/signin">
        Fazer login
      </router-link>
    </template>
  </AuthLayout>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
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
  font-size: .85rem;
  text-decoration: none;
  letter-spacing: .25px;
  color: var(--color-primary-hover);
  margin: 0 !important;
  display: inline;
}
</style>
