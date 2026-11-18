import { unref, type Ref } from 'vue';
import { required, email, minLength, sameAs, helpers } from '@vuelidate/validators';

export const MIN_PASSWORD_LENGTH = 8;

export const containsNameAndSurname = (value: string): boolean => {
  if (!value) return true;
  const parts = value.trim().split(/\s+/).filter(Boolean);
  return parts.length >= 2;
};

export const capitalizeName = (name: string): string => {
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

export const emailRules = () => ({
  required: helpers.withMessage('Informe seu e-mail', required),
  email: helpers.withMessage('Informe um e-mail válido', email),
});

export const requiredPasswordRules = (message = 'Informe sua senha') => ({
  required: helpers.withMessage(message, required),
});

export const passwordRules = (
  requiredMsg = 'Informe uma senha',
  minLengthCount = MIN_PASSWORD_LENGTH
) => ({
  required: helpers.withMessage(requiredMsg, required),
  minLength: helpers.withMessage(
    `A senha deve ter no mínimo ${minLengthCount} caracteres`,
    minLength(minLengthCount)
  ),
});

export const confirmPasswordRules = (
  targetPassword: string | Ref<string>,
  requiredMsg = 'Confirme a senha',
  mismatchMsg = 'As senhas não coincidem'
) => ({
  required: helpers.withMessage(requiredMsg, required),
  sameAsPassword: helpers.withMessage(mismatchMsg, sameAs(targetPassword)),
});

export const fullNameRules = () => ({
  required: helpers.withMessage('Informe seu nome', required),
  fullName: helpers.withMessage('Informe nome e sobrenome', containsNameAndSurname),
});

export function getFieldError(field?: { $errors?: Array<{ $message?: string | Ref<string> }> }): string {
  const firstError = field?.$errors?.[0]?.$message;
  return String(unref(firstError) || '');
}
