export type SyncActionType = 'TOGGLE_HABIT';

export interface SyncQueueItem {
  id: string;
  type: SyncActionType;
  habitId: number | string;
  date: string;
  timestamp: number;
}
