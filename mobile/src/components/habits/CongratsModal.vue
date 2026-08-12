<script setup>
import { ref } from 'vue';
import { IonModal } from '@ionic/vue';
import confetti from 'canvas-confetti';
import Button from '@/components/ui/Button.vue';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['update:isOpen']);

const messages = [
  { title: 'Missão Cumprida! 🚀', text: 'Você fechou o dia com chave de ouro e completou todos os seus hábitos. O amanhã começa agora!' },
  { title: 'Você é Imparável! ⚡', text: 'Nenhum hábito ficou para trás. Mantenha esse ritmo e os resultados virão de forma extraordinária.' },
  { title: '100% Concluído! ✨', text: 'Incrível! Você dominou o seu dia e cumpriu todas as metas. Descanse e prepare-se para o próximo round.' },
  { title: 'Invicto! 🏆', text: 'Todos os hábitos de hoje foram concluídos. Você está construindo uma versão incrível de si mesmo!' }
];

const currentMessage = ref(messages[0]);

const closeModal = () => {
  emit('update:isOpen', false);
};

const onWillPresent = () => {
  currentMessage.value = messages[Math.floor(Math.random() * messages.length)];
};

const onDidPresent = () => {
  fireConfetti();
};

const fireConfetti = () => {
  confetti({
    particleCount: 100,
    spread: 70,
    origin: { y: 0.6 },
    colors: ['#a3e635', '#9333ea', '#0d9488']
  });
};

</script>

<template>
  <ion-modal
    :is-open="isOpen"
    @will-present="onWillPresent"
    @did-dismiss="closeModal"
    @did-present="onDidPresent"
    class="congrats-modal"
  >
    <div class="modal-content">
      <div class="icon-wrapper">
        🎉
      </div>
      <h2>{{ currentMessage.title }}</h2>
      <p>{{ currentMessage.text }}</p>
      
      <Button
        color="primary"
        @click="closeModal"
        class="full-width"
      >
        Incrível!
      </Button>
    </div>
  </ion-modal>
</template>

<style scoped>
.congrats-modal {
  --height: auto;
  --width: 90%;
  --max-width: 400px;
  --border-radius: var(--border-radius-default);
  --background: var(--color-background-secondary);
  --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-content {
  padding: 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: var(--color-background-secondary);
}

.icon-wrapper {
  font-size: 5rem;
  margin-bottom: 1rem;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-20px);
  }
  60% {
    transform: translateY(-10px);
  }
}

h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text-primary);
  margin-bottom: 0.5rem;
  margin-top: 0;
}

p {
  font-size: 0.875rem;
  color: var(--color-text-accent);
  line-height: 1.4;
  margin-bottom: 2rem;
}

.full-width {
  width: 100%;
}
</style>
