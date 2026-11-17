# Notificações Locais (`@capacitor/local-notifications`)

O Habitus utiliza o plugin `@capacitor/local-notifications` para alertar usuários sobre o horário de execução de seus hábitos diários sem depender de servidores push externos.

---

## 1. Configuração Nativa e Permissões

### Android 13+ (API 33+):
Requer a permissão em tempo de execução `POST_NOTIFICATIONS` declarada no `AndroidManifest.xml`:
```xml
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

### Configuração no `capacitor.config.json`:
```json
{
  "plugins": {
    "LocalNotifications": {
      "smallIcon": "ic_stat_habitus",
      "iconColor": "#C6FF34",
      "sound": "beep.wav"
    }
  }
}
```
> O ícone monocromático deve residir em `android/app/src/main/res/drawable/ic_stat_habitus.png`.

---

## 2. Solicitação de Permissões com TypeScript

Antes de agendar qualquer notificação, verifique e solicite a permissão explicitamente:

```ts
import { LocalNotifications, type PermissionStatus } from '@capacitor/local-notifications';

export async function requestNotificationPermissions(): Promise<boolean> {
  const checkStatus: PermissionStatus = await LocalNotifications.checkPermissions();

  if (checkStatus.display === 'granted') {
    return true;
  }

  const requestStatus: PermissionStatus = await LocalNotifications.requestPermissions();
  return requestStatus.display === 'granted';
}
```

---

## 3. Criação de Canais de Notificação (Android)

No Android 8.0+ (Oreo), notificações **devem** pertencer a um canal (`NotificationChannel`):

```ts
export async function setupNotificationChannels(): Promise<void> {
  await LocalNotifications.createChannel({
    id: 'habit-reminders',
    name: 'Lembretes de Hábitos',
    description: 'Notificações diárias para lembrar de praticar seus hábitos',
    importance: 4, // High importance (aparece com som/banner)
    visibility: 1, // Public
    sound: 'beep.wav',
    vibration: true,
  });
}
```

---

## 4. Agendamento Recorrente de Hábitos (`schedule`)

Para agendar notificações baseadas no horário configurado no hábito (`reminderTime` no formato `HH:mm`) e nos dias da semana (`weekDays` de 0 a 6):

```ts
import { LocalNotifications, type ScheduleOptions } from '@capacitor/local-notifications';

export interface HabitNotificationConfig {
  id: number;
  title: string;
  hour: number;
  minute: number;
  weekDay?: number; // 1 = Domingo no Capacitor (ou 0-6 dependendo do mapeamento)
}

export async function scheduleHabitReminder(config: HabitNotificationConfig): Promise<void> {
  const options: ScheduleOptions = {
    notifications: [
      {
        id: config.id,
        title: 'Hora do seu hábito!',
        body: `Não se esqueça de realizar: ${config.title}`,
        channelId: 'habit-reminders',
        smallIcon: 'ic_stat_habitus',
        iconColor: '#C6FF34',
        schedule: {
          on: {
            hour: config.hour,
            minute: config.minute,
            weekday: config.weekDay,
          },
          repeats: true,
          allowWhileIdle: true,
        },
        extra: {
          habitId: config.id,
        },
      },
    ],
  };

  await LocalNotifications.schedule(options);
}

export async function cancelHabitReminder(habitId: number): Promise<void> {
  await LocalNotifications.cancel({
    notifications: [{ id: habitId }],
  });
}
```

---

## 5. Listeners de Ação (Abertura da Notificação)

No arquivo `App.vue` ou `src/main.ts`, registre o listener para redirecionar o usuário para a tela do hábito quando ele tocar na notificação:

```ts
import { LocalNotifications, type ActionPerformed } from '@capacitor/local-notifications';
import { useRouter } from 'vue-router';

export function setupNotificationListeners(router: ReturnType<typeof useRouter>): void {
  LocalNotifications.addListener('localNotificationActionPerformed', (action: ActionPerformed) => {
    const habitId = action.notification.extra?.habitId;
    if (habitId) {
      router.push(`/habits/${habitId}`);
    }
  });
}
```
